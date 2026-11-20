<?php

namespace App\Http\Controllers;

use App\Models\FinanceReport;
use App\Models\FinanceReportEvent;
use App\Support\FinanceAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Laporan keuangan bulanan. Alur: Bendahara (ajukan) -> Wakil Ketua (review) -> Ketua (setujui) -> Publikasi.
 * Dengan pemisahan tugas aktif, tiap tahap dilakukan orang yang berbeda.
 */
class FinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $internal = FinanceAccess::internal($user);

        $reports = FinanceReport::query()
            ->when(! $internal, fn ($q) => $q->where('status', 'published'))
            ->orderByDesc('year')->orderByDesc('month')
            ->paginate(12)
            ->through(fn (FinanceReport $r) => [
                'id'           => $r->id,
                'label'        => $r->label(),
                'status'       => $r->status,
                'status_label' => $r->statusLabel(),
                'totals'       => $r->totals(),
                'published_at' => $r->published_at?->format('d/m/Y'),
            ]);

        $latest = FinanceReport::where('status', 'published')->orderByDesc('year')->orderByDesc('month')->first();

        return Inertia::render('Finance/Index', [
            'reports' => $reports,
            'summary' => $latest ? ['label' => $latest->label(), 'closing' => $latest->totals()['closing'], 'id' => $latest->id] : null,
            'can'     => [
                'internal' => $internal,
                'record'   => $user->can('finance.record'),
            ],
        ]);
    }

    public function show(Request $request, FinanceReport $report): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $report), 403);

        $internal = FinanceAccess::internal($user);
        $editable = $report->isEditable() && $user->can('finance.record');

        $transactions = $report->transactions()
            ->with(['category:id,name', 'activity:id,code,title', 'recorder:id,name'])
            ->orderBy('transaction_date')->orderBy('id')->get()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'reference'   => $t->reference,
                'type'        => $t->type,
                'date'        => $t->transaction_date->format('d/m/Y'),
                'category'    => $t->category->name,
                'description' => $t->description,
                'counterparty' => $t->counterparty,
                'amount'      => $t->amount,
                'has_proof'   => $t->hasProof(),
                'proof_note'  => $t->proof_note,
                'activity'    => $t->activity?->title,
                'recorder'    => $t->recorder?->name,
                'can_edit'    => $editable,
            ]);

        $disputes = $report->disputes()->with(['filer:id,name', 'transaction:id,reference'])
            ->when(! $internal, fn ($q) => $q->where('filed_by', $user->id))
            ->orderByDesc('id')->get()
            ->map(fn ($d) => [
                'id' => $d->id, 'status' => $d->status, 'status_label' => $d->statusLabel(),
                'reason' => str($d->reason)->limit(120)->toString(), 'filer' => $d->filer?->name,
                'transaction' => $d->transaction?->reference, 'created_at' => $d->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Finance/ReportShow', [
            'report' => [
                'id'           => $report->id,
                'label'        => $report->label(),
                'status'       => $report->status,
                'status_label' => $report->statusLabel(),
                'totals'       => $report->totals(),
                'published_at' => $report->published_at?->format('d/m/Y H:i'),
                'published_by' => $report->publisher?->name,
            ],
            'workflow' => $internal ? [
                'submitted_by' => $report->submitter?->name, 'submitted_at' => $report->submitted_at?->format('d/m/Y H:i'),
                'reviewed_by' => $report->reviewer?->name, 'reviewed_at' => $report->reviewed_at?->format('d/m/Y H:i'), 'review_note' => $report->review_note,
                'approved_by' => $report->approver?->name, 'approved_at' => $report->approved_at?->format('d/m/Y H:i'), 'approval_note' => $report->approval_note,
            ] : null,
            'events' => $internal ? $report->events()->get()->map(fn ($e) => [
                'id' => $e->id, 'action' => FinanceReportEvent::ACTIONS[$e->action] ?? $e->action, 'user' => $e->user_label ?? 'Sistem',
                'note' => $e->note, 'at' => $e->created_at->format('d/m/Y H:i'),
            ]) : [],
            'transactions' => $transactions,
            'disputes'     => $disputes,
            'can' => [
                'internal'   => $internal,
                'record'     => $editable,
                'submit'     => $editable,
                'review'     => $user->can('finance.review') && $report->status === 'submitted',
                'approve'    => $user->can('finance.approve') && $report->status === 'reviewed',
                'publish'    => $user->can('finance.publish') && $report->status === 'approved',
                'dispute'    => $report->status === 'published' && $user->member_id !== null,
            ],
            'separation' => FinanceAccess::separationEnforced(),
        ]);
    }

    public function submit(Request $request, FinanceReport $report): RedirectResponse
    {
        if (! $report->isEditable()) {
            return back()->with('error', 'Laporan ini sudah diajukan.');
        }

        if (! config('simpem.finance.allow_early_submission') && $report->start()->endOfMonth()->isFuture()) {
            return back()->with('error', "Laporan {$report->label()} baru dapat diajukan setelah bulannya berakhir.");
        }

        $transactions = $report->transactions()->get();
        if ($transactions->isEmpty()) {
            return back()->with('error', 'Belum ada transaksi pada laporan ini.');
        }

        $missing = $transactions->where('type', 'expense')->filter(fn ($t) => ! $t->proof_path && blank($t->proof_note))->count();
        if ($missing) {
            return back()->with('error', "{$missing} transaksi kas keluar belum punya bukti. Unggah bukti, atau isi alasan jika memang tanpa bukti.");
        }

        DB::transaction(function () use ($request, $report) {
            $report->freezeTotals();
            $report->update([
                'status' => 'submitted', 'submitted_by' => $request->user()->id, 'submitted_at' => now(),
                'reviewed_by' => null, 'reviewed_at' => null, 'review_note' => null,
                'approved_by' => null, 'approved_at' => null, 'approval_note' => null,
            ]);
            FinanceReportEvent::log($report, 'submitted');
        });

        return back()->with('success', 'Laporan diajukan untuk direview. Transaksi kini terkunci.');
    }

    public function review(Request $request, FinanceReport $report): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['forward', 'return'])],
            'note'     => ['nullable', 'string', 'max:500', 'required_if:decision,return'],
        ], ['note.required_if' => 'Catatan wajib diisi saat mengembalikan laporan.']);

        if ($report->status !== 'submitted') {
            return back()->with('error', 'Laporan ini tidak sedang menunggu review.');
        }
        if ($this->separationViolated($request, [$report->submitted_by])) {
            return back()->with('error', 'Pemisahan tugas: pengaju laporan tidak boleh mereviewnya sendiri.');
        }

        $forward = $data['decision'] === 'forward';

        DB::transaction(function () use ($request, $report, $data, $forward) {
            $report->update($forward
                ? ['status' => 'reviewed', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['note'] ?? null]
                : ['status' => 'revision', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['note']]);
            FinanceReportEvent::log($report, $forward ? 'reviewed' : 'returned_by_reviewer', $data['note'] ?? null);
        });

        return back()->with('success', $forward ? 'Review selesai, diteruskan ke Ketua.' : 'Laporan dikembalikan ke bendahara.');
    }

    public function approve(Request $request, FinanceReport $report): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'return'])],
            'note'     => ['nullable', 'string', 'max:500', 'required_if:decision,return'],
        ], ['note.required_if' => 'Catatan wajib diisi saat mengembalikan laporan.']);

        if ($report->status !== 'reviewed') {
            return back()->with('error', 'Laporan ini tidak sedang menunggu persetujuan.');
        }
        if ($this->separationViolated($request, [$report->submitted_by, $report->reviewed_by])) {
            return back()->with('error', 'Pemisahan tugas: penyetuju harus berbeda dari pengaju dan pereview.');
        }

        $approve = $data['decision'] === 'approve';

        DB::transaction(function () use ($request, $report, $data, $approve) {
            $report->update($approve
                ? ['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now(), 'approval_note' => $data['note'] ?? null]
                : ['status' => 'revision', 'approved_by' => $request->user()->id, 'approved_at' => now(), 'approval_note' => $data['note']]);
            FinanceReportEvent::log($report, $approve ? 'approved' : 'returned_by_approver', $data['note'] ?? null);
        });

        return back()->with('success', $approve ? 'Laporan disetujui. Siap dipublikasikan.' : 'Laporan dikembalikan ke bendahara.');
    }

    public function publish(Request $request, FinanceReport $report): RedirectResponse
    {
        if ($report->status !== 'approved') {
            return back()->with('error', 'Hanya laporan yang sudah disetujui yang dapat dipublikasikan.');
        }

        DB::transaction(function () use ($request, $report) {
            $report->update(['status' => 'published', 'published_by' => $request->user()->id, 'published_at' => now()]);
            FinanceReportEvent::log($report, 'published');
        });

        return back()->with('success', 'Laporan dipublikasikan dan dapat dilihat semua anggota.');
    }

    /** @param  array<int|null>  $others */
    private function separationViolated(Request $request, array $others): bool
    {
        return \App\Support\FinanceAccess::separationEnforced()
            && in_array($request->user()->id, array_filter($others), true);
    }
}
