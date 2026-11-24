<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityCommitteeMember;
use App\Models\ActivityReport;
use App\Models\ActivityTimeline;
use App\Models\Division;
use App\Models\Member;
use App\Models\Period;
use App\Support\ActivityCodeGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = [
            'q'        => trim((string) $request->query('q', '')),
            'status'   => (string) $request->query('status', ''),
            'period'   => (string) $request->query('period', ''),
            'division' => (string) $request->query('division', ''),
            'mine'     => (string) $request->query('mine', ''),
        ];

        $activities = Activity::query()
            ->visibleTo($user)
            ->with(['period:id,name', 'division:id,name', 'pic:id,name'])
            ->when($filters['q'] !== '', fn ($q) => $q->where(function ($q) use ($filters) {
                $s = '%'.$filters['q'].'%';
                $q->where('title', 'like', $s)->orWhere('code', 'like', $s)->orWhere('location', 'like', $s);
            }))
            ->when(array_key_exists($filters['status'], Activity::STATUSES), fn ($q) => $q->where('status', $filters['status']))
            ->when(ctype_digit($filters['period']) && $filters['period'] !== '', fn ($q) => $q->where('period_id', (int) $filters['period']))
            ->when(ctype_digit($filters['division']) && $filters['division'] !== '', fn ($q) => $q->where('division_id', (int) $filters['division']))
            ->when($filters['mine'] === '1' && $user->member_id, fn ($q) => $q->where(function ($q) use ($user) {
                $q->where('pic_member_id', $user->member_id)
                    ->orWhereHas('committee', fn ($c) => $c->where('member_id', $user->member_id));
            }))
            ->orderByDesc('starts_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Activity $a) => [
                'id'           => $a->id,
                'code'         => $a->code,
                'title'        => $a->title,
                'category'     => $a->category,
                'status'       => $a->status,
                'status_label' => $a->statusLabel(),
                'when'         => $this->range($a),
                'location'     => $a->location,
                'period'       => $a->period?->name,
                'division'     => $a->division?->name,
                'pic'          => $a->pic?->name,
            ]);

        return Inertia::render('Activities/Index', [
            'activities' => $activities,
            'filters'    => $filters,
            'statuses'   => Activity::STATUSES,
            'periods'    => Period::orderByDesc('start_date')->get(['id', 'name']),
            'divisions'  => Division::orderBy('name')->get(['id', 'name']),
            'has_member' => (bool) $user->member_id,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Activities/Form', [
            'activity' => null,
            'options'  => $this->formOptions(),
            'defaults' => [
                'period_id'     => Period::where('is_current', true)->value('id'),
                'pic_member_id' => $request->user()->member_id,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages());

        $activity = DB::transaction(function () use ($request, $data) {
            $data['code'] = ActivityCodeGenerator::next((int) date('Y', strtotime($data['starts_at'])));
            $data['status'] = 'draft';
            $data['created_by'] = $request->user()->id;

            return Activity::create($data);
        });

        return to_route('activities.show', $activity)->with('success', "Kegiatan {$activity->code} dibuat sebagai draf.");
    }

    public function show(Request $request, Activity $activity): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $activity), 403);

        $full = $user->can('viewFull', $activity);
        $manage = $user->can('manage', $activity);
        $activity->load(['period:id,name', 'division:id,name', 'pic:id,name', 'report', 'decider:id,name']);

        $committee = $activity->committee()->with('member:id,name,nomor_anggota,photo_path')->get()
            ->sort(fn ($a, $b) => [ActivityCommitteeMember::roleRank($a->role), $a->section ?? '', $a->member->name]
                <=> [ActivityCommitteeMember::roleRank($b->role), $b->section ?? '', $b->member->name])
            ->map(fn (ActivityCommitteeMember $c) => [
                'id'         => $c->id,
                'member_id'  => $c->member_id,
                'name'       => $c->member->name,
                'photo_url'  => $c->member->photo_url,
                'role'       => $c->role,
                'role_label' => ActivityCommitteeMember::ROLES[$c->role] ?? $c->role,
                'section'    => $c->section,
                'notes'      => $c->notes,
            ])->values();

        $timelines = $activity->timelines()->with('pic:id,name')
            ->when(! $full, fn ($q) => $q->where('kind', 'rundown'))
            ->orderBy('starts_at')->get()
            ->map(fn (ActivityTimeline $t) => [
                'id'          => $t->id,
                'kind'        => $t->kind,
                'title'       => $t->title,
                'description' => $t->description,
                'starts_at'   => $t->starts_at->format('d/m/Y H:i'),
                'ends_at'     => $t->ends_at?->format('d/m/Y H:i'),
                'starts_at_iso' => $t->starts_at->format('Y-m-d\TH:i'),
                'ends_at_iso'   => $t->ends_at?->format('Y-m-d\TH:i'),
                'pic_member_id' => $t->pic_member_id,
                'pic'         => $t->pic?->name,
                'status'      => $t->status,
                'can_status'  => $activity->isOpen() && ($manage || ($user->member_id && $t->pic_member_id === $user->member_id)),
            ])->values();

        $report = $activity->report;
        $editBudget = $manage && $activity->canEditProposal();
        $editActual = $manage && $activity->status === 'completed' && $report?->isEditable();

        return Inertia::render('Activities/Show', [
            'activity' => [
                'id'            => $activity->id,
                'code'          => $activity->code,
                'title'         => $activity->title,
                'category'      => $activity->category,
                'status'        => $activity->status,
                'status_label'  => $activity->statusLabel(),
                'period'        => $activity->period?->name,
                'division'      => $activity->division?->name,
                'pic'           => $activity->pic ? ['id' => $activity->pic->id, 'name' => $activity->pic->name] : null,
                'description'   => $activity->description,
                'location'      => $activity->location,
                'when'          => $this->range($activity),
                'cancelled_reason' => $activity->cancelled_reason,
                'decision_note' => $full ? $activity->decision_note : null,
                'decided_by'    => $full ? $activity->decider?->name : null,
                'decided_at'    => $full ? $activity->decided_at?->format('d/m/Y H:i') : null,
            ],
            'can' => [
                'full'          => $full,
                'manage'        => $manage,
                'approve'       => $user->can('activities.approve'),
                'edit_core'     => $manage && $activity->isOpen(),
                'edit_structure' => $manage && $activity->isOpen(),
                'edit_proposal' => $manage && $activity->canEditProposal(),
                'edit_budget'   => $editBudget,
                'edit_actual'   => (bool) $editActual,
                'delete'        => $user->can('activities.delete') && in_array($activity->status, ['draft', 'cancelled'], true),
            ],
            'committee' => $committee,
            'timelines' => $timelines,
            'proposal'  => $full ? [
                'background'   => $activity->proposal_background,
                'objectives'   => $activity->proposal_objectives,
                'target_audience' => $activity->proposal_target_audience,
                'target_participants' => $activity->proposal_target_participants,
                'has_file'     => (bool) $activity->proposal_file_path,
                'submitted_at' => $activity->proposal_submitted_at?->format('d/m/Y H:i'),
            ] : null,
            'budget' => $full ? $this->budget($activity) : null,
            'report' => $full && $report ? [
                'status'       => $report->status,
                'status_label' => ActivityReport::STATUSES[$report->status] ?? $report->status,
                'summary'      => $report->summary,
                'evaluation'   => $report->evaluation,
                'realized_participants' => $report->realized_participants,
                'has_file'     => (bool) $report->file_path,
                'submitted_at' => $report->submitted_at?->format('d/m/Y H:i'),
                'reviewed_at'  => $report->reviewed_at?->format('d/m/Y H:i'),
                'review_note'  => $report->review_note,
                'editable'     => $report->isEditable() && $activity->status === 'completed',
            ] : null,
            'publications' => [
                'albums' => \App\Models\Album::visibleTo($user)->where('activity_id', $activity->id)
                    ->withCount(['media as photos_count' => fn ($q) => $q->where('type', 'photo')])
                    ->orderByDesc('id')->get()
                    ->map(fn ($a) => ['id' => $a->id, 'title' => $a->title, 'status_label' => $a->statusLabel(), 'status' => $a->status, 'photos' => $a->photos_count]),
                'news' => \App\Models\NewsPost::visibleTo($user)->where('activity_id', $activity->id)
                    ->orderByDesc('id')->get()
                    ->map(fn ($n) => ['id' => $n->id, 'title' => $n->title, 'status_label' => $n->statusLabel(), 'status' => $n->status]),
                'can_create_album' => $user->can('gallery.manage') || $manage || $activity->hasCommitteeMember($user->member_id),
                'can_create_news'  => $user->can('news.manage'),
            ],
            'options' => $manage ? [
                'members'  => Member::where('status', Member::ACTIVE)->orderBy('name')->get(['id', 'name', 'nomor_anggota']),
                'roles'    => ActivityCommitteeMember::ROLES,
                'sections' => $activity->committee()->whereNotNull('section')->distinct()->orderBy('section')->pluck('section'),
                'timeline_statuses' => ActivityTimeline::STATUSES,
            ] : null,
        ]);
    }

    public function edit(Request $request, Activity $activity): Response|RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->isOpen()) {
            return to_route('activities.show', $activity)->with('error', 'Kegiatan yang sudah selesai atau dibatalkan tidak dapat diubah.');
        }

        return Inertia::render('Activities/Form', [
            'activity' => [
                'id'            => $activity->id,
                'code'          => $activity->code,
                'title'         => $activity->title,
                'category'      => $activity->category,
                'period_id'     => $activity->period_id,
                'division_id'   => $activity->division_id,
                'pic_member_id' => $activity->pic_member_id,
                'description'   => $activity->description,
                'location'      => $activity->location,
                'starts_at'     => $activity->starts_at->format('Y-m-d\TH:i'),
                'ends_at'       => $activity->ends_at->format('Y-m-d\TH:i'),
            ],
            'options'  => $this->formOptions(),
            'defaults' => [],
        ]);
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->isOpen()) {
            return back()->with('error', 'Kegiatan yang sudah selesai atau dibatalkan tidak dapat diubah.');
        }

        $activity->update($request->validate($this->rules(), $this->messages()));

        return to_route('activities.show', $activity)->with('success', 'Data kegiatan diperbarui.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        if (! in_array($activity->status, ['draft', 'cancelled'], true)) {
            return back()->with('error', 'Hanya kegiatan berstatus Draf atau Dibatalkan yang dapat dihapus.');
        }

        $activity->delete();

        return to_route('activities.index')->with('success', "Kegiatan {$activity->code} dihapus.");
    }

    // ---------- Proposal ----------
    public function updateProposal(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->canEditProposal()) {
            return back()->with('error', 'Proposal tidak dapat diubah setelah diajukan.');
        }

        $data = $request->validate([
            'proposal_background'          => ['nullable', 'string', 'max:10000'],
            'proposal_objectives'          => ['nullable', 'string', 'max:10000'],
            'proposal_target_audience'     => ['nullable', 'string', 'max:190'],
            'proposal_target_participants' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'proposal_file'                => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ], [
            'proposal_file.mimes' => 'Berkas proposal harus PDF, DOC, atau DOCX.',
            'proposal_file.max'   => 'Ukuran berkas maksimal 5 MB.',
        ]);

        if ($request->hasFile('proposal_file')) {
            $this->deleteFile($activity->proposal_file_path);
            $data['proposal_file_path'] = $request->file('proposal_file')->store("activities/{$activity->id}/proposal", 'local');
        }
        unset($data['proposal_file']);

        $activity->update($data);

        return back()->with('success', 'Proposal disimpan.');
    }

    public function downloadProposal(Request $request, Activity $activity): StreamedResponse|RedirectResponse
    {
        abort_unless($request->user()->can('viewFull', $activity), 403);

        return $this->download($activity->proposal_file_path, "Proposal-{$activity->code}");
    }

    // ---------- Alur status ----------
    public function submit(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        if (! $activity->canEditProposal()) {
            return back()->with('error', 'Kegiatan ini sudah diajukan.');
        }

        $missing = [];
        if (! $activity->pic_member_id) {
            $missing[] = 'PIC kegiatan';
        }
        if (! $activity->proposal_file_path && (blank($activity->proposal_background) || blank($activity->proposal_objectives))) {
            $missing[] = 'proposal (latar belakang dan tujuan, atau unggah berkas)';
        }
        if (! $activity->budgetItems()->where('type', 'expense')->exists()) {
            $missing[] = 'rencana anggaran (minimal satu pengeluaran)';
        }
        if ($missing) {
            return back()->with('error', 'Lengkapi dulu: '.implode(', ', $missing).'.');
        }

        $activity->update([
            'status'                => 'proposed',
            'proposal_submitted_at' => now(),
            'decided_at'            => null,
            'decided_by'            => null,
            'decision_note'         => null,
        ]);

        return back()->with('success', 'Proposal diajukan untuk persetujuan.');
    }

    public function decide(Request $request, Activity $activity): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note'     => ['nullable', 'string', 'max:500', 'required_if:decision,reject'],
        ], ['note.required_if' => 'Alasan penolakan wajib diisi.']);

        if ($activity->status !== 'proposed') {
            return back()->with('error', 'Hanya proposal berstatus Diajukan yang dapat diputuskan.');
        }

        $approve = $data['decision'] === 'approve';
        $activity->update([
            'status'        => $approve ? 'approved' : 'rejected',
            'decided_at'    => now(),
            'decided_by'    => $request->user()->id,
            'decision_note' => $data['note'] ?? null,
        ]);

        return back()->with('success', $approve ? 'Proposal disetujui.' : 'Proposal ditolak dan dikembalikan ke pengaju.');
    }

    public function complete(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        if ($activity->status !== 'approved') {
            return back()->with('error', 'Hanya kegiatan yang disetujui yang dapat ditandai selesai.');
        }
        if ($activity->starts_at->isFuture()) {
            return back()->with('error', 'Kegiatan belum dimulai.');
        }

        DB::transaction(function () use ($request, $activity) {
            $activity->update(['status' => 'completed', 'completed_at' => now()]);
            ActivityReport::firstOrCreate(['activity_id' => $activity->id], ['status' => 'draft', 'created_by' => $request->user()->id]);
        });

        return back()->with('success', 'Kegiatan ditandai selesai. Silakan susun LPJ.');
    }

    public function cancel(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], ['reason.required' => 'Alasan pembatalan wajib diisi.']);

        if (! $activity->isOpen()) {
            return back()->with('error', 'Kegiatan ini tidak dapat dibatalkan.');
        }

        $activity->update(['status' => 'cancelled', 'cancelled_reason' => $data['reason'], 'is_public' => false]);

        return back()->with('success', 'Kegiatan dibatalkan.');
    }

    // ---------- helpers ----------
    private function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:150'],
            'category'      => ['nullable', 'string', 'max:60'],
            'period_id'     => ['nullable', 'integer', 'exists:periods,id'],
            'division_id'   => ['nullable', 'integer', 'exists:divisions,id'],
            'pic_member_id' => ['required', 'integer', Rule::exists('members', 'id')->where('status', Member::ACTIVE)->whereNull('deleted_at')],
            'description'   => ['nullable', 'string', 'max:5000'],
            'location'      => ['nullable', 'string', 'max:190'],
            'starts_at'     => ['required', 'date'],
            'ends_at'       => ['required', 'date', 'after_or_equal:starts_at'],
        ];
    }

    private function messages(): array
    {
        return [
            'title.required'          => 'Nama kegiatan wajib diisi.',
            'pic_member_id.required'  => 'Pilih PIC kegiatan.',
            'pic_member_id.exists'    => 'PIC harus anggota berstatus Aktif.',
            'starts_at.required'      => 'Waktu mulai wajib diisi.',
            'ends_at.required'        => 'Waktu selesai wajib diisi.',
            'ends_at.after_or_equal'  => 'Waktu selesai tidak boleh sebelum waktu mulai.',
        ];
    }

    private function formOptions(): array
    {
        return [
            'periods'   => Period::orderByDesc('start_date')->get(['id', 'name', 'is_current']),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'members'   => Member::where('status', Member::ACTIVE)->orderBy('name')->get(['id', 'name', 'nomor_anggota']),
        ];
    }

    private function range(Activity $a): string
    {
        $same = $a->starts_at->isSameDay($a->ends_at);

        return $a->starts_at->format('d/m/Y H:i').' - '.$a->ends_at->format($same ? 'H:i' : 'd/m/Y H:i');
    }

    private function budget(Activity $activity): array
    {
        $items = $activity->budgetItems()->orderBy('type', 'desc')->orderBy('category')->orderBy('id')->get();

        $sum = fn (string $type, string $col) => (int) $items->where('type', $type)->sum($col);
        $hasActual = $items->whereNotNull('actual_amount')->isNotEmpty();

        return [
            'items' => $items->map(fn ($i) => [
                'id'             => $i->id,
                'type'           => $i->type,
                'category'       => $i->category,
                'description'    => $i->description,
                'quantity'       => $i->quantity,
                'unit'           => $i->unit,
                'unit_price'     => $i->unit_price,
                'planned_amount' => $i->planned_amount,
                'actual_amount'  => $i->actual_amount,
                'notes'          => $i->notes,
            ])->values(),
            'totals' => [
                'plan_income'    => $sum('income', 'planned_amount'),
                'plan_expense'   => $sum('expense', 'planned_amount'),
                'actual_income'  => $hasActual ? $sum('income', 'actual_amount') : null,
                'actual_expense' => $hasActual ? $sum('expense', 'actual_amount') : null,
            ],
            'categories' => $items->pluck('category')->unique()->values(),
        ];
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    private function download(?string $path, string $baseName): StreamedResponse|RedirectResponse
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return back()->with('error', 'Berkas tidak ditemukan.');
        }

        return Storage::disk('local')->download($path, $baseName.'.'.pathinfo($path, PATHINFO_EXTENSION));
    }
}
