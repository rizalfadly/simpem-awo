<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Member;
use App\Models\Partner;
use App\Models\Partnership;
use App\Support\FinanceFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Kerja sama organisasi (sponsorship, MoU, barter, kegiatan). Data internal: hanya pengurus kemitraan. */
class PartnershipController extends Controller
{
    private const TRANSITIONS = ['draft' => ['active'], 'active' => ['completed', 'terminated']];

    public function index(Request $request): Response
    {
        $filters = [
            'q'      => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
        ];

        $items = Partnership::query()
            ->with('partner:id,name')
            ->when($filters['q'] !== '', fn ($q) => $q->where(function ($q) use ($filters) {
                $s = '%'.$filters['q'].'%';
                $q->where('title', 'like', $s)->orWhereHas('partner', fn ($p) => $p->where('name', 'like', $s));
            }))
            ->when($filters['status'] === 'expiring', fn ($q) => $q->where('status', 'active')->whereBetween('ends_on', [today(), today()->addDays(30)]))
            ->when($filters['status'] === 'lapsed', fn ($q) => $q->where('status', 'active')->where('ends_on', '<', today()))
            ->when(array_key_exists($filters['status'], Partnership::STATUSES), fn ($q) => $q->where('status', $filters['status']))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Partnership $p) => [
                'id' => $p->id, 'title' => $p->title, 'partner' => $p->partner?->name,
                'type_label' => Partnership::TYPES[$p->type] ?? $p->type, 'status' => $p->status,
                'status_label' => Partnership::STATUSES[$p->status] ?? $p->status,
                'period' => ($p->starts_on?->format('d/m/Y') ?? '?').' - '.($p->ends_on?->format('d/m/Y') ?? 'tanpa batas'),
                'value' => $p->value, 'lapsed' => $p->isLapsed(), 'expiring' => $p->expiresSoon(),
            ]);

        return Inertia::render('Partners/Partnerships/Index', [
            'items' => $items, 'filters' => $filters, 'statuses' => Partnership::STATUSES, 'can_manage' => $request->user()->can('partners.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Partners/Partnerships/Form', [
            'partnership' => null,
            'options'     => $this->options($request),
            'defaults'    => ['partner_id' => $request->integer('partner') ?: null],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $doc = $this->storeDocument($request);
        unset($data['document'], $data['remove_document']);

        $p = Partnership::create($data + $doc + ['status' => 'draft', 'created_by' => $request->user()->id]);

        return to_route('partners.partnerships.show', $p)->with('success', 'Kerja sama dicatat sebagai draf.');
    }

    public function show(Partnership $partnership): Response
    {
        $partnership->load(['partner:id,name,slug', 'pic:id,name', 'activity:id,code,title']);

        return Inertia::render('Partners/Partnerships/Show', [
            'item' => [
                'id' => $partnership->id, 'title' => $partnership->title, 'type_label' => Partnership::TYPES[$partnership->type] ?? $partnership->type,
                'description' => $partnership->description, 'status' => $partnership->status,
                'status_label' => Partnership::STATUSES[$partnership->status] ?? $partnership->status, 'status_note' => $partnership->status_note,
                'partner' => ['id' => $partnership->partner->id, 'name' => $partnership->partner->name],
                'starts_on' => $partnership->starts_on?->format('d/m/Y'), 'ends_on' => $partnership->ends_on?->format('d/m/Y'),
                'signed_on' => $partnership->signed_on?->format('d/m/Y'), 'value' => $partnership->value,
                'pic' => $partnership->pic?->name, 'activity' => $partnership->activity ? ['id' => $partnership->activity->id, 'title' => $partnership->activity->title] : null,
                'document' => $partnership->document_path ? ['name' => $partnership->document_name, 'url' => "/partners/partnerships/{$partnership->id}/document"] : null,
                'lapsed' => $partnership->isLapsed(), 'expiring' => $partnership->expiresSoon(),
            ],
            'can' => [
                'manage'   => request()->user()->can('partners.manage'),
                'edit'     => request()->user()->can('partners.manage') && in_array($partnership->status, ['draft', 'active'], true),
                'activate' => request()->user()->can('partners.manage') && $partnership->status === 'draft',
                'finish'   => request()->user()->can('partners.manage') && $partnership->status === 'active',
                'delete'   => request()->user()->can('partners.manage') && $partnership->status === 'draft',
            ],
        ]);
    }

    public function edit(Request $request, Partnership $partnership): Response|RedirectResponse
    {
        if (! in_array($partnership->status, ['draft', 'active'], true)) {
            return to_route('partners.partnerships.show', $partnership)->with('error', 'Kerja sama yang sudah selesai atau dihentikan tidak dapat diubah.');
        }

        return Inertia::render('Partners/Partnerships/Form', [
            'partnership' => [
                'id' => $partnership->id, 'partner_id' => $partnership->partner_id, 'title' => $partnership->title, 'type' => $partnership->type,
                'description' => $partnership->description, 'starts_on' => $partnership->starts_on?->format('Y-m-d'),
                'ends_on' => $partnership->ends_on?->format('Y-m-d'), 'signed_on' => $partnership->signed_on?->format('Y-m-d'),
                'value' => $partnership->value, 'pic_member_id' => $partnership->pic_member_id, 'activity_id' => $partnership->activity_id,
                'document_name' => $partnership->document_name,
            ],
            'options'  => $this->options($request),
            'defaults' => [],
        ]);
    }

    public function update(Request $request, Partnership $partnership): RedirectResponse
    {
        if (! in_array($partnership->status, ['draft', 'active'], true)) {
            return back()->with('error', 'Kerja sama yang sudah selesai atau dihentikan tidak dapat diubah.');
        }

        $data = $this->validated($request);
        $extra = [];

        if ($request->hasFile('document')) {
            FinanceFiles::delete($partnership->document_path);
            $extra = $this->storeDocument($request);
        } elseif ($request->boolean('remove_document')) {
            FinanceFiles::delete($partnership->document_path);
            $extra = ['document_path' => null, 'document_name' => null, 'document_mime' => null];
        }
        unset($data['document'], $data['remove_document']);

        $partnership->update($data + $extra);

        return to_route('partners.partnerships.show', $partnership)->with('success', 'Kerja sama diperbarui.');
    }

    public function status(Request $request, Partnership $partnership): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Partnership::STATUSES))],
            'note'   => ['nullable', 'string', 'max:500', 'required_if:status,terminated'],
        ], ['note.required_if' => 'Alasan penghentian wajib diisi.']);

        if (! in_array($data['status'], self::TRANSITIONS[$partnership->status] ?? [], true)) {
            return back()->with('error', 'Perubahan status tidak diperbolehkan.');
        }

        $partnership->update(['status' => $data['status'], 'status_note' => $data['note'] ?? $partnership->status_note]);

        return back()->with('success', 'Status kerja sama: '.Partnership::STATUSES[$data['status']].'.');
    }

    public function destroy(Partnership $partnership): RedirectResponse
    {
        if ($partnership->status !== 'draft') {
            return back()->with('error', 'Hanya kerja sama berstatus Draf yang dapat dihapus.');
        }

        $partnership->delete();

        return to_route('partners.partnerships.index')->with('success', 'Kerja sama dihapus.');
    }

    public function document(Partnership $partnership): BinaryFileResponse
    {
        abort_unless($partnership->document_path && Storage::disk('local')->exists($partnership->document_path), 404);

        return response()->file(Storage::disk('local')->path($partnership->document_path), [
            'Content-Type' => $partnership->document_mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($partnership->document_name ?? 'naskah').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'partner_id'      => ['required', 'integer', Rule::exists('partners', 'id')->whereNull('deleted_at')],
            'title'           => ['required', 'string', 'max:150'],
            'type'            => ['required', Rule::in(array_keys(Partnership::TYPES))],
            'description'     => ['nullable', 'string', 'max:1500'],
            'starts_on'       => ['nullable', 'date'],
            'ends_on'         => ['nullable', 'date', 'after_or_equal:starts_on'],
            'signed_on'       => ['nullable', 'date', 'before_or_equal:today'],
            'value'           => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
            'pic_member_id'   => ['nullable', 'integer', Rule::exists('members', 'id')->where('status', Member::ACTIVE)->whereNull('deleted_at')],
            'activity_id'     => ['nullable', 'integer', 'exists:activities,id'],
            'document'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'remove_document' => ['nullable', 'boolean'],
        ], [
            'partner_id.required'    => 'Pilih mitra.',
            'title.required'         => 'Judul kerja sama wajib diisi.',
            'ends_on.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'value.integer'          => 'Nilai harus berupa angka bulat (rupiah).',
            'pic_member_id.exists'   => 'PIC harus anggota berstatus Aktif.',
            'document.mimes'         => 'Naskah harus berformat PDF, JPG, atau PNG.',
            'document.max'           => 'Ukuran naskah maksimal 10 MB.',
        ]);
    }

    /** @return array{document_path?:string, document_name?:string, document_mime?:string} */
    private function storeDocument(Request $request): array
    {
        if (! $request->hasFile('document')) {
            return [];
        }

        try {
            $f = FinanceFiles::store($request->file('document'), 'partners/agreements');
        } catch (\Throwable) {
            throw ValidationException::withMessages(['document' => 'Naskah tidak dapat diproses. Pastikan PDF/gambar valid.']);
        }

        return ['document_path' => $f['path'], 'document_name' => $f['original_name'], 'document_mime' => $f['mime']];
    }

    private function options(Request $request): array
    {
        return [
            'types'      => Partnership::TYPES,
            'partners'   => Partner::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'members'    => Member::where('status', Member::ACTIVE)->orderBy('name')->get(['id', 'name', 'nomor_anggota']),
            'activities' => Activity::visibleTo($request->user())->orderByDesc('starts_at')->limit(200)->get(['id', 'code', 'title']),
        ];
    }
}
