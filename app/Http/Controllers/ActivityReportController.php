<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** LPJ (Laporan Pertanggungjawaban) kegiatan. Diterima -> kegiatan berstatus Ditutup. */
class ActivityReportController extends Controller
{
    public function save(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        $report = $this->editableReport($activity);
        if (! $report) {
            return back()->with('error', 'LPJ tidak dapat diubah pada status saat ini.');
        }

        $data = $request->validate([
            'summary'               => ['nullable', 'string', 'max:10000'],
            'evaluation'            => ['nullable', 'string', 'max:10000'],
            'realized_participants' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'report_file'           => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ], [
            'report_file.mimes' => 'Berkas LPJ harus PDF, DOC, atau DOCX.',
            'report_file.max'   => 'Ukuran berkas maksimal 5 MB.',
        ]);

        if ($request->hasFile('report_file')) {
            if ($report->file_path) {
                Storage::disk('local')->delete($report->file_path);
            }
            $data['file_path'] = $request->file('report_file')->store("activities/{$activity->id}/report", 'local');
        }
        unset($data['report_file']);

        $report->update($data);

        return back()->with('success', 'LPJ disimpan.');
    }

    public function submit(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $activity), 403);

        $report = $this->editableReport($activity);
        if (! $report) {
            return back()->with('error', 'LPJ tidak dapat diajukan pada status saat ini.');
        }

        $missing = [];
        if (blank($report->summary) && ! $report->file_path) {
            $missing[] = 'ringkasan pelaksanaan (atau unggah berkas LPJ)';
        }
        if ($report->realized_participants === null) {
            $missing[] = 'jumlah peserta sebenarnya';
        }
        if ($activity->budgetItems()->where('type', 'expense')->whereNull('actual_amount')->exists()) {
            $missing[] = 'realisasi semua item pengeluaran (isi 0 bila tidak terpakai)';
        }
        if ($missing) {
            return back()->with('error', 'Lengkapi dulu: '.implode(', ', $missing).'.');
        }

        $report->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
            'reviewed_at'  => null,
            'reviewed_by'  => null,
            'review_note'  => null,
        ]);

        return back()->with('success', 'LPJ diajukan untuk direview.');
    }

    public function review(Request $request, Activity $activity): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'revise'])],
            'note'     => ['nullable', 'string', 'max:500', 'required_if:decision,revise'],
        ], ['note.required_if' => 'Catatan revisi wajib diisi.']);

        $report = $activity->report;
        if (! $report || $report->status !== 'submitted') {
            return back()->with('error', 'Tidak ada LPJ yang menunggu review.');
        }

        $accept = $data['decision'] === 'accept';

        DB::transaction(function () use ($request, $activity, $report, $data, $accept) {
            $report->update([
                'status'      => $accept ? 'accepted' : 'revision',
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
                'review_note' => $data['note'] ?? null,
            ]);
            if ($accept) {
                $activity->update(['status' => 'closed']);
            }
        });

        return back()->with('success', $accept ? 'LPJ diterima. Kegiatan ditutup.' : 'LPJ dikembalikan untuk direvisi.');
    }

    public function download(Request $request, Activity $activity): StreamedResponse|RedirectResponse
    {
        abort_unless($request->user()->can('viewFull', $activity), 403);

        $path = $activity->report?->file_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return back()->with('error', 'Berkas tidak ditemukan.');
        }

        return Storage::disk('local')->download($path, "LPJ-{$activity->code}.".pathinfo($path, PATHINFO_EXTENSION));
    }

    private function editableReport(Activity $activity): ?ActivityReport
    {
        if ($activity->status !== 'completed') {
            return null;
        }

        $report = ActivityReport::firstOrCreate(['activity_id' => $activity->id], ['status' => 'draft']);

        return $report->isEditable() ? $report : null;
    }
}
