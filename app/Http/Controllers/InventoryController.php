<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetLoanItem;
use App\Models\AuditLog;
use App\Support\AssetCodes;
use App\Support\ImageProcessor;
use App\Support\InventoryAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $viewer = InventoryAccess::viewer($user);
        $filters = [
            'q'        => trim((string) $request->query('q', '')),
            'category' => (string) $request->query('category', ''),
            'status'   => (string) $request->query('status', ''),
        ];

        $assets = Asset::query()
            ->with('category:id,name')
            ->when(! $viewer, fn ($q) => $q->where('status', 'active')->where('is_loanable', true))
            ->when($filters['q'] !== '', fn ($q) => $q->where(function ($q) use ($filters) {
                $s = '%'.$filters['q'].'%';
                $q->where('name', 'like', $s)->orWhere('code', 'like', $s)->orWhere('location', 'like', $s);
            }))
            ->when(ctype_digit($filters['category']) && $filters['category'] !== '', fn ($q) => $q->where('category_id', (int) $filters['category']))
            ->when($viewer && array_key_exists($filters['status'], Asset::STATUSES), fn ($q) => $q->where('status', $filters['status']))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Asset $a) => [
                'id'          => $a->id,
                'code'        => $a->code,
                'name'        => $a->name,
                'category'    => $a->category?->name,
                'location'    => $a->location,
                'unit'        => $a->unit,
                'available'   => $a->canBeLoaned() ? $a->availableToday() : 0,
                'loanable'    => $a->canBeLoaned(),
                'status'      => $a->status,
                'status_label' => Asset::STATUSES[$a->status] ?? $a->status,
                'thumb'       => $a->photo_thumb_path ? "/inventory/{$a->id}/photo?thumb=1" : null,
                // rincian kondisi hanya untuk pengurus inventaris
                'conditions'  => $viewer ? ['good' => $a->qty_good, 'minor' => $a->qty_minor, 'broken' => $a->qty_broken, 'lost' => $a->qty_lost] : null,
                'total'       => $viewer ? $a->total() : null,
            ]);

        return Inertia::render('Inventory/Index', [
            'assets'     => $assets,
            'filters'    => $filters,
            'categories' => AssetCategory::orderBy('name')->get(['id', 'name']),
            'statuses'   => Asset::STATUSES,
            'can'        => [
                'viewer'  => $viewer,
                'manage'  => $user->can('assets.manage'),
                'loans'   => $user->can('assets.loans'),
                'request' => $user->member_id !== null || $user->can('assets.loans'),
            ],
        ]);
    }

    public function show(Request $request, Asset $asset): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $asset), 403);

        $details = $user->can('viewDetails', $asset);
        $asset->load('category:id,name');

        $usage = $details
            ? AssetLoanItem::query()
                ->where('asset_id', $asset->id)
                ->with(['loan.borrower:id,name', 'loan.activity:id,title'])
                ->latest('id')->limit(100)->get()
                ->map(fn (AssetLoanItem $i) => [
                    'id'        => $i->id,
                    'loan_id'   => $i->loan_id,
                    'code'      => $i->loan->code,
                    'borrower'  => $i->loan->borrower?->name,
                    'activity'  => $i->loan->activity?->title,
                    'purpose'   => $i->loan->purpose,
                    'period'    => $i->loan->loan_date->format('d/m/Y').' - '.$i->loan->due_date->format('d/m/Y'),
                    'status'    => $i->loan->statusLabel(),
                    'quantity'  => $i->quantity,
                    'returned'  => $i->returned_good === null ? null : [
                        'good' => $i->returned_good, 'minor' => $i->returned_minor, 'broken' => $i->returned_broken, 'lost' => $i->returned_lost,
                    ],
                    'late_days' => $i->loan->lateDays(),
                ])
            : [];

        $history = $details
            ? AuditLog::where('auditable_type', $asset->getMorphClass())->where('auditable_id', $asset->id)
                ->orderBy('id')->get()->map(fn ($l) => [
                    'id' => $l->id, 'at' => $l->created_at?->format('d/m/Y H:i'), 'user' => $l->user_label ?? 'Sistem',
                    'event' => $l->event, 'old' => $l->old_values, 'new' => $l->new_values,
                ])
            : [];

        return Inertia::render('Inventory/Show', [
            'asset' => [
                'id' => $asset->id, 'code' => $asset->code, 'name' => $asset->name, 'category' => $asset->category?->name,
                'description' => $asset->description, 'location' => $asset->location, 'unit' => $asset->unit,
                'status' => $asset->status, 'status_label' => Asset::STATUSES[$asset->status] ?? $asset->status,
                'loanable' => $asset->canBeLoaned(), 'available' => $asset->canBeLoaned() ? $asset->availableToday() : 0,
                'photo' => $asset->photo_path ? "/inventory/{$asset->id}/photo" : null,
                'retired_note' => $details ? $asset->retired_note : null,
                'details' => $details ? [
                    'serial_number' => $asset->serial_number, 'acquired_at' => $asset->acquired_at?->format('d/m/Y'),
                    'acquisition_source' => $asset->acquisition_source, 'acquisition_cost' => $asset->acquisition_cost,
                    'total' => $asset->total(), 'lent' => $asset->lentQuantity(),
                    'conditions' => ['good' => $asset->qty_good, 'minor' => $asset->qty_minor, 'broken' => $asset->qty_broken, 'lost' => $asset->qty_lost],
                ] : null,
            ],
            'usage'   => $usage,
            'history' => $history,
            'can'     => [
                'details' => $details,
                'manage'  => $user->can('assets.manage'),
                'request' => $asset->canBeLoaned() && ($user->member_id !== null || $user->can('assets.loans')),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Form', ['asset' => null, 'categories' => $this->categories()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureHasStock($data);
        $photo = $this->processPhoto($request);
        unset($data['photo'], $data['remove_photo']);

        $asset = DB::transaction(fn () => Asset::create($data + $photo + [
            'code' => AssetCodes::asset(), 'status' => 'active', 'created_by' => $request->user()->id,
        ]));

        return to_route('inventory.show', $asset)->with('success', "Barang {$asset->code} ditambahkan.");
    }

    public function edit(Asset $asset): Response
    {
        return Inertia::render('Inventory/Form', [
            'asset' => [
                'id' => $asset->id, 'code' => $asset->code, 'name' => $asset->name, 'category_id' => $asset->category_id,
                'description' => $asset->description, 'location' => $asset->location, 'unit' => $asset->unit,
                'serial_number' => $asset->serial_number, 'acquired_at' => $asset->acquired_at?->format('Y-m-d'),
                'acquisition_source' => $asset->acquisition_source, 'acquisition_cost' => $asset->acquisition_cost,
                'qty_good' => $asset->qty_good, 'qty_minor' => $asset->qty_minor, 'qty_broken' => $asset->qty_broken, 'qty_lost' => $asset->qty_lost,
                'is_loanable' => $asset->is_loanable, 'has_photo' => (bool) $asset->photo_path, 'lent' => $asset->lentQuantity(),
            ],
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureHasStock($data);

        $lent = $asset->lentQuantity();
        if ($data['qty_good'] < $lent) {
            throw ValidationException::withMessages([
                'qty_good' => "{$lent} unit sedang dipinjam dan dihitung sebagai kondisi Baik. Jumlah baik tidak boleh kurang dari itu; catat kondisi sebenarnya saat pengembalian.",
            ]);
        }

        $extra = [];
        if ($request->hasFile('photo')) {
            $this->deletePhoto($asset);
            $extra = $this->processPhoto($request);
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($asset);
            $extra = ['photo_path' => null, 'photo_thumb_path' => null];
        }
        unset($data['photo'], $data['remove_photo']);

        $asset->update($data + $extra);   // perubahan jumlah/kondisi tercatat di audit log

        return to_route('inventory.show', $asset)->with('success', 'Data barang diperbarui.');
    }

    public function retire(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:255']], ['note.required' => 'Alasan wajib diisi (mis. rusak berat, dijual, dihibahkan).']);

        if (! $asset->isActive()) {
            return back()->with('error', 'Barang ini sudah dipensiunkan.');
        }
        if ($asset->loanItems()->whereHas('loan', fn ($q) => $q->whereIn('status', ['approved', 'lent']))->exists()) {
            return back()->with('error', 'Masih ada peminjaman yang disetujui atau sedang berjalan untuk barang ini.');
        }

        $asset->update(['status' => 'retired', 'retired_at' => now(), 'retired_note' => $data['note']]);

        return back()->with('success', 'Barang dipensiunkan dan tidak dapat dipinjam.');
    }

    public function reactivate(Asset $asset): RedirectResponse
    {
        $asset->update(['status' => 'active', 'retired_at' => null, 'retired_note' => null]);

        return back()->with('success', 'Barang diaktifkan kembali.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        if ($asset->loanItems()->exists()) {
            return back()->with('error', 'Barang sudah punya riwayat peminjaman. Gunakan "Pensiunkan", bukan hapus.');
        }

        $asset->delete();

        return to_route('inventory.index')->with('success', 'Barang dihapus.');
    }

    public function photo(Request $request, Asset $asset): BinaryFileResponse
    {
        abort_unless($request->user()->can('view', $asset), 403);

        $path = $request->boolean('thumb') ? $asset->photo_thumb_path : $asset->photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=86400', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:150'],
            'category_id'        => ['nullable', 'integer', 'exists:asset_categories,id'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'location'           => ['nullable', 'string', 'max:120'],
            'unit'               => ['required', 'string', 'max:20'],
            'serial_number'      => ['nullable', 'string', 'max:80'],
            'acquired_at'        => ['nullable', 'date', 'before_or_equal:today'],
            'acquisition_source' => ['nullable', 'string', 'max:60'],
            'acquisition_cost'   => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
            'qty_good'           => ['required', 'integer', 'min:0', 'max:100000'],
            'qty_minor'          => ['required', 'integer', 'min:0', 'max:100000'],
            'qty_broken'         => ['required', 'integer', 'min:0', 'max:100000'],
            'qty_lost'           => ['required', 'integer', 'min:0', 'max:100000'],
            'is_loanable'        => ['required', 'boolean'],
            'photo'              => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_photo'       => ['nullable', 'boolean'],
        ], [
            'name.required'      => 'Nama barang wajib diisi.',
            'unit.required'      => 'Satuan wajib diisi (mis. unit, buah, set).',
            'qty_good.required'  => 'Isi jumlah kondisi baik (0 jika tidak ada).',
            'qty_minor.required' => 'Isi jumlah rusak ringan (0 jika tidak ada).',
            'qty_broken.required' => 'Isi jumlah rusak berat (0 jika tidak ada).',
            'qty_lost.required'  => 'Isi jumlah hilang (0 jika tidak ada).',
            'photo.image'        => 'Foto harus berupa gambar.',
            'photo.max'          => 'Ukuran foto maksimal 8 MB.',
        ]);

        return $data;
    }

    private function ensureHasStock(array $data): void
    {
        if (($data['qty_good'] + $data['qty_minor'] + $data['qty_broken'] + $data['qty_lost']) < 1) {
            throw ValidationException::withMessages(['qty_good' => 'Total jumlah barang minimal 1.']);
        }
    }

    /** @return array{photo_path?:string, photo_thumb_path?:string} */
    private function processPhoto(Request $request): array
    {
        if (! $request->hasFile('photo')) {
            return [];
        }

        try {
            $r = ImageProcessor::store($request->file('photo'), 'assets');
        } catch (\Throwable) {
            throw ValidationException::withMessages(['photo' => 'Foto tidak dapat diproses. Pastikan berkas gambar valid.']);
        }

        return ['photo_path' => $r['path'], 'photo_thumb_path' => $r['thumb']];
    }

    private function deletePhoto(Asset $asset): void
    {
        foreach ([$asset->photo_path, $asset->photo_thumb_path] as $path) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
        }
    }

    private function categories()
    {
        return AssetCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
