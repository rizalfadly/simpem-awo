<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\Member;
use App\Support\AssetCodes;
use App\Support\InventoryAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Peminjaman barang: Diajukan -> Disetujui (stok dipesan untuk tanggal itu) -> Sedang dipinjam (serah terima)
 * -> Dikembalikan (kondisi tiap barang dicatat). Penolakan/pembatalan dimungkinkan sebelum serah terima.
 */
class AssetLoanController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $viewer = InventoryAccess::viewer($user);
        $filters = [
            'status' => (string) $request->query('status', ''),
            'q'      => trim((string) $request->query('q', '')),
            'mine'   => (string) $request->query('mine', ''),
        ];

        $mineOnly = ! $viewer || $filters['mine'] === '1';

        $loans = AssetLoan::query()
            ->with(['borrower:id,name', 'items.asset:id,name,unit'])
            ->when($mineOnly, fn ($q) => $q->where(function ($q) use ($user) {
                $q->where('requested_by', $user->id);
                if ($user->member_id) {
                    $q->orWhere('borrower_member_id', $user->member_id);
                }
            }))
            ->when($filters['status'] === 'overdue', fn ($q) => $q->where('status', 'lent')->where('due_date', '<', today()))
            ->when(array_key_exists($filters['status'], AssetLoan::STATUSES), fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['q'] !== '', fn ($q) => $q->where(function ($q) use ($filters) {
                $s = '%'.$filters['q'].'%';
                $q->where('code', 'like', $s)->orWhere('purpose', 'like', $s)
                    ->orWhereHas('borrower', fn ($m) => $m->where('name', 'like', $s));
            }))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AssetLoan $l) => [
                'id'        => $l->id,
                'code'      => $l->code,
                'borrower'  => $l->borrower?->name,
                'purpose'   => $l->purpose,
                'period'    => $l->loan_date->format('d/m/Y').' - '.$l->due_date->format('d/m/Y'),
                'status'    => $l->status,
                'status_label' => $l->statusLabel(),
                'overdue'   => $l->isOverdue(),
                'late_days' => $l->lateDays(),
                'items'     => $l->items->map(fn ($i) => "{$i->asset->name} x{$i->quantity}")->join(', '),
            ]);

        return Inertia::render('Inventory/Loans/Index', [
            'loans'    => $loans,
            'filters'  => $filters,
            'statuses' => AssetLoan::STATUSES,
            'viewer'   => $viewer,
            'can_request' => $user->member_id !== null || $user->can('assets.loans'),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $staff = $user->can('assets.loans');
        abort_unless($user->member_id || $staff, 403);

        return Inertia::render('Inventory/Loans/Create', [
            'assets' => Asset::with('category:id,name')->where('status', 'active')->where('is_loanable', true)->orderBy('name')->get()
                ->map(fn (Asset $a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name, 'unit' => $a->unit, 'category' => $a->category?->name, 'available' => $a->availableToday()]),
            'activities' => Activity::visibleTo($user)->whereIn('status', Activity::PUBLIC_STATUSES)->orderByDesc('starts_at')->limit(200)->get(['id', 'code', 'title']),
            'members'    => $staff ? Member::where('status', Member::ACTIVE)->orderBy('name')->get(['id', 'name', 'nomor_anggota']) : [],
            'staff'      => $staff,
            'defaults'   => [
                'borrower_member_id' => $user->member_id,
                'asset_id'           => $request->integer('asset') ?: null,
                'activity_id'        => $request->integer('activity') ?: null,
            ],
            'max_days'   => (int) config('simpem.assets.max_loan_days', 30),
        ]);
    }

    /** Ketersediaan tiap barang pada rentang tanggal (dipakai formulir untuk umpan balik langsung). */
    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']]);

        $map = Asset::where('status', 'active')->where('is_loanable', true)->get()
            ->mapWithKeys(fn (Asset $a) => [$a->id => $a->availableFor($data['from'], $data['to'])]);

        return response()->json($map);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $staff = $user->can('assets.loans');
        $max = (int) config('simpem.assets.max_loan_days', 30);

        $data = $request->validate([
            'borrower_member_id' => ['nullable', 'integer'],
            'activity_id'        => ['nullable', 'integer', 'exists:activities,id'],
            'purpose'            => ['required', 'string', 'max:255'],
            'loan_date'          => ['required', 'date', 'after_or_equal:today'],
            'due_date'           => ['required', 'date', 'after_or_equal:loan_date'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'items'              => ['required', 'array', 'min:1', 'max:30'],
            'items.*.asset_id'   => ['required', 'integer', 'distinct', 'exists:assets,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:100000'],
        ], [
            'purpose.required'          => 'Tujuan peminjaman wajib diisi.',
            'loan_date.required'        => 'Tanggal pinjam wajib diisi.',
            'loan_date.after_or_equal'  => 'Tanggal pinjam tidak boleh di masa lalu.',
            'due_date.required'         => 'Tanggal kembali wajib diisi.',
            'due_date.after_or_equal'   => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
            'items.required'            => 'Pilih minimal satu barang.',
            'items.*.asset_id.distinct' => 'Barang yang sama dipilih lebih dari sekali.',
            'items.*.quantity.min'      => 'Jumlah minimal 1.',
        ]);

        $borrowerId = ($staff && ! empty($data['borrower_member_id'])) ? (int) $data['borrower_member_id'] : $user->member_id;
        if (! $borrowerId) {
            throw ValidationException::withMessages(['borrower_member_id' => $staff ? 'Pilih peminjam.' : 'Akun Anda belum terhubung ke data anggota.']);
        }
        if (! Member::where('id', $borrowerId)->where('status', Member::ACTIVE)->exists()) {
            throw ValidationException::withMessages(['borrower_member_id' => 'Peminjam harus anggota berstatus Aktif.']);
        }

        $from = Carbon::parse($data['loan_date']);
        $to = Carbon::parse($data['due_date']);
        if ($from->diffInDays($to) + 1 > $max) {
            throw ValidationException::withMessages(['due_date' => "Lama peminjaman maksimal {$max} hari."]);
        }

        if (AssetLoan::where('borrower_member_id', $borrowerId)->where('status', 'lent')->where('due_date', '<', today())->exists()) {
            throw ValidationException::withMessages(['items' => 'Peminjam masih memiliki barang yang terlambat dikembalikan. Selesaikan dulu sebelum meminjam lagi.']);
        }

        $assets = Asset::whereIn('id', collect($data['items'])->pluck('asset_id'))->get()->keyBy('id');
        $errors = [];
        foreach ($data['items'] as $i => $line) {
            $asset = $assets[$line['asset_id']];
            if (! $asset->canBeLoaned()) {
                $errors["items.{$i}.asset_id"] = "{$asset->name} tidak dapat dipinjam.";
            } elseif (($avail = $asset->availableFor($from, $to)) < $line['quantity']) {
                $errors["items.{$i}.quantity"] = "Stok {$asset->name} pada tanggal itu hanya tersisa {$avail} {$asset->unit}.";
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $loan = DB::transaction(function () use ($data, $user, $borrowerId) {
            $loan = AssetLoan::create([
                'code'               => AssetCodes::loan((int) now()->format('Y')),
                'borrower_member_id' => $borrowerId,
                'requested_by'       => $user->id,
                'activity_id'        => $data['activity_id'] ?? null,
                'purpose'            => $data['purpose'],
                'loan_date'          => $data['loan_date'],
                'due_date'           => $data['due_date'],
                'notes'              => $data['notes'] ?? null,
                'status'             => 'requested',
            ]);
            foreach ($data['items'] as $line) {
                $loan->items()->create(['asset_id' => $line['asset_id'], 'quantity' => $line['quantity']]);
            }

            return $loan;
        });

        return to_route('inventory.loans.show', $loan)->with('success', "Peminjaman {$loan->code} diajukan. Menunggu persetujuan pengelola.");
    }

    public function show(Request $request, AssetLoan $loan): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $loan), 403);

        $loan->load(['borrower:id,name,nomor_anggota', 'requester:id,name', 'activity:id,title', 'approver:id,name', 'handedBy:id,name', 'receiver:id,name', 'items.asset']);
        $staff = $user->can('assets.loans');
        $fmt = fn ($d) => $d?->format('d/m/Y H:i');

        return Inertia::render('Inventory/Loans/Show', [
            'loan' => [
                'id' => $loan->id, 'code' => $loan->code, 'status' => $loan->status, 'status_label' => $loan->statusLabel(),
                'borrower' => $loan->borrower?->name, 'borrower_number' => $loan->borrower?->nomor_anggota,
                'purpose' => $loan->purpose, 'notes' => $loan->notes, 'status_note' => $loan->status_note,
                'activity' => $loan->activity ? ['id' => $loan->activity->id, 'title' => $loan->activity->title] : null,
                'loan_date' => $loan->loan_date->format('d/m/Y'), 'due_date' => $loan->due_date->format('d/m/Y'),
                'requested_by' => $loan->requester?->name, 'requested_at' => $fmt($loan->created_at),
                'approved_by' => $loan->approver?->name, 'approved_at' => $fmt($loan->approved_at),
                'handed_by' => $loan->handedBy?->name, 'lent_at' => $fmt($loan->lent_at),
                'received_by' => $loan->receiver?->name, 'returned_at' => $fmt($loan->returned_at), 'return_note' => $loan->return_note,
                'overdue' => $loan->isOverdue(), 'late_days' => $loan->lateDays(),
                'items' => $loan->items->map(fn ($i) => [
                    'id' => $i->id, 'quantity' => $i->quantity,
                    'asset' => ['id' => $i->asset->id, 'code' => $i->asset->code, 'name' => $i->asset->name, 'unit' => $i->asset->unit],
                    'returned' => $i->returned_good === null ? null : ['good' => $i->returned_good, 'minor' => $i->returned_minor, 'broken' => $i->returned_broken, 'lost' => $i->returned_lost],
                ]),
            ],
            'can' => [
                'approve'  => $staff && $loan->status === 'requested',
                'reject'   => $staff && $loan->status === 'requested',
                'handover' => $staff && $loan->status === 'approved',
                'return'   => $staff && $loan->status === 'lent',
                'cancel'   => in_array($loan->status, ['requested', 'approved'], true) && ($staff || $user->can('owns', $loan)),
            ],
            'separation' => InventoryAccess::separationEnforced(),
        ]);
    }

    public function approve(Request $request, AssetLoan $loan): RedirectResponse
    {
        $user = $request->user();

        if ($loan->status !== 'requested') {
            return back()->with('error', 'Peminjaman ini tidak sedang menunggu persetujuan.');
        }
        if (InventoryAccess::separationEnforced() && $user->member_id !== null && $user->member_id === $loan->borrower_member_id) {
            return back()->with('error', 'Pemisahan tugas: Anda tidak dapat menyetujui peminjaman atas nama Anda sendiri.');
        }

        $error = DB::transaction(function () use ($loan, $user) {
            $loan->load('items');
            $assets = Asset::whereIn('id', $loan->items->pluck('asset_id'))->lockForUpdate()->get()->keyBy('id');

            $problems = [];
            foreach ($loan->items as $item) {
                $asset = $assets[$item->asset_id];
                if (! $asset->canBeLoaned()) {
                    $problems[] = "{$asset->name} sudah tidak dapat dipinjam.";
                } elseif (($avail = $asset->availableFor($loan->loan_date, $loan->due_date, $loan->id)) < $item->quantity) {
                    $problems[] = "Stok {$asset->name} pada tanggal itu hanya tersisa {$avail} {$asset->unit} (diminta {$item->quantity}).";
                }
            }
            if ($problems) {
                return implode(' ', $problems);
            }

            $loan->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);

            return null;
        });

        return $error
            ? back()->with('error', $error)
            : back()->with('success', 'Peminjaman disetujui. Stok dipesan untuk tanggal tersebut.');
    }

    public function reject(Request $request, AssetLoan $loan): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Alasan penolakan wajib diisi.']);

        if ($loan->status !== 'requested') {
            return back()->with('error', 'Peminjaman ini tidak sedang menunggu persetujuan.');
        }

        $loan->update(['status' => 'rejected', 'status_note' => $data['reason']]);

        return back()->with('success', 'Peminjaman ditolak.');
    }

    public function cancel(Request $request, AssetLoan $loan): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('assets.loans') || $user->can('owns', $loan), 403);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        if (! in_array($loan->status, ['requested', 'approved'], true)) {
            return back()->with('error', 'Peminjaman ini tidak dapat dibatalkan lagi.');
        }

        $loan->update(['status' => 'cancelled', 'status_note' => $data['reason'] ?? null]);

        return back()->with('success', 'Peminjaman dibatalkan.');
    }

    public function handover(Request $request, AssetLoan $loan): RedirectResponse
    {
        $user = $request->user();

        if ($loan->status !== 'approved') {
            return back()->with('error', 'Hanya peminjaman yang sudah disetujui yang dapat diserahkan.');
        }

        $error = DB::transaction(function () use ($loan, $user) {
            $loan->load('items');
            $assets = Asset::whereIn('id', $loan->items->pluck('asset_id'))->lockForUpdate()->get()->keyBy('id');

            foreach ($loan->items as $item) {
                $asset = $assets[$item->asset_id];
                if (! $asset->isActive()) {
                    return "{$asset->name} sudah dipensiunkan.";
                }
                if ($asset->qty_good < $asset->lentQuantity() + $item->quantity) {
                    return "Jumlah {$asset->name} yang berkondisi baik tidak mencukupi untuk diserahkan sekarang.";
                }
            }

            $loan->update(['status' => 'lent', 'lent_at' => now(), 'handed_by' => $user->id]);

            return null;
        });

        return $error ? back()->with('error', $error) : back()->with('success', 'Barang diserahkan ke peminjam.');
    }

    public function giveBack(Request $request, AssetLoan $loan): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'items'           => ['required', 'array', 'min:1'],
            'items.*.id'      => ['required', 'integer'],
            'items.*.good'    => ['required', 'integer', 'min:0'],
            'items.*.minor'   => ['required', 'integer', 'min:0'],
            'items.*.broken'  => ['required', 'integer', 'min:0'],
            'items.*.lost'    => ['required', 'integer', 'min:0'],
            'note'            => ['nullable', 'string', 'max:500'],
        ]);

        if ($loan->status !== 'lent') {
            return back()->with('error', 'Hanya barang yang sedang dipinjam yang dapat dikembalikan.');
        }

        $loan->load('items.asset');
        $byId = collect($data['items'])->keyBy('id');

        foreach ($loan->items as $item) {
            $r = $byId->get($item->id);
            if (! $r) {
                throw ValidationException::withMessages(['items' => "Kondisi {$item->asset->name} belum diisi."]);
            }
            if ($r['good'] + $r['minor'] + $r['broken'] + $r['lost'] !== $item->quantity) {
                throw ValidationException::withMessages(['items' => "Jumlah semua kondisi {$item->asset->name} harus sama dengan jumlah yang dipinjam ({$item->quantity})."]);
            }
        }

        DB::transaction(function () use ($loan, $byId, $data, $user) {
            $assets = Asset::whereIn('id', $loan->items->pluck('asset_id'))->lockForUpdate()->get()->keyBy('id');

            foreach ($loan->items as $item) {
                $r = $byId[$item->id];
                $asset = $assets[$item->asset_id];

                // Unit yang kembali tidak baik dipindah dari "baik" ke kondisinya masing-masing (tercatat di audit log barang).
                $asset->update([
                    'qty_good'   => max(0, $asset->qty_good - ($item->quantity - $r['good'])),
                    'qty_minor'  => $asset->qty_minor + $r['minor'],
                    'qty_broken' => $asset->qty_broken + $r['broken'],
                    'qty_lost'   => $asset->qty_lost + $r['lost'],
                ]);

                $item->update(['returned_good' => $r['good'], 'returned_minor' => $r['minor'], 'returned_broken' => $r['broken'], 'returned_lost' => $r['lost']]);
            }

            $loan->update(['status' => 'returned', 'returned_at' => now(), 'received_by' => $user->id, 'return_note' => $data['note'] ?? null]);
        });

        return back()->with('success', 'Pengembalian dicatat. Kondisi barang diperbarui.');
    }
}
