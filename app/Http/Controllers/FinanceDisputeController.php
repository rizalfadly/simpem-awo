<?php

namespace App\Http\Controllers;

use App\Models\FinanceDispute;
use App\Models\FinanceDisputeFile;
use App\Models\FinanceReport;
use App\Models\User;
use App\Support\FinanceAccess;
use App\Support\FinanceFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sanggahan keuangan: Anggota mengajukan (alasan + bukti) -> Bendahara mengklarifikasi
 * -> Wakil Ketua mereview -> Ketua memutuskan. Pemisahan tugas berlaku per sanggahan.
 */
class FinanceDisputeController extends Controller
{
    private const MAX_OPEN_PER_REPORT = 5;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $internal = FinanceAccess::internal($user);
        $status = (string) $request->query('status', '');

        $items = FinanceDispute::query()
            ->with(['report:id,year,month', 'filer:id,name', 'transaction:id,reference'])
            ->when(! $internal, fn ($q) => $q->where('filed_by', $user->id))
            ->when(array_key_exists($status, FinanceDispute::STATUSES), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (FinanceDispute $d) => [
                'id' => $d->id, 'report' => $d->report->label(), 'report_id' => $d->report_id,
                'status' => $d->status, 'status_label' => $d->statusLabel(),
                'reason' => str($d->reason)->limit(140)->toString(), 'filer' => $d->filer?->name,
                'transaction' => $d->transaction?->reference, 'created_at' => $d->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Finance/Disputes', [
            'items'    => $items,
            'status'   => $status,
            'statuses' => FinanceDispute::STATUSES,
            'internal' => $internal,
        ]);
    }

    public function store(Request $request, FinanceReport $report): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->member_id, 403);

        if ($report->status !== 'published') {
            return back()->with('error', 'Sanggahan hanya dapat diajukan pada laporan yang sudah dipublikasikan.');
        }

        $data = $request->validate([
            'transaction_id' => ['nullable', 'integer', Rule::exists('finance_transactions', 'id')->where('report_id', $report->id)->whereNull('deleted_at')],
            'reason'         => ['required', 'string', 'min:20', 'max:3000'],
            'files'          => ['nullable', 'array', 'max:3'],
            'files.*'        => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'reason.required' => 'Alasan sanggahan wajib diisi.',
            'reason.min'      => 'Jelaskan alasan minimal 20 karakter agar bisa ditindaklanjuti.',
            'files.max'       => 'Maksimal 3 berkas pendukung.',
            'files.*.mimes'   => 'Berkas harus PDF, JPG, atau PNG.',
            'files.*.max'     => 'Ukuran tiap berkas maksimal 5 MB.',
        ]);

        $open = FinanceDispute::where('filed_by', $user->id)->where('report_id', $report->id)
            ->whereIn('status', ['open', 'clarified', 'reviewed'])->count();
        if ($open >= self::MAX_OPEN_PER_REPORT) {
            return back()->with('error', 'Anda masih memiliki '.self::MAX_OPEN_PER_REPORT.' sanggahan yang belum selesai pada laporan ini.');
        }

        $dispute = DB::transaction(function () use ($request, $user, $report, $data) {
            $d = FinanceDispute::create([
                'report_id' => $report->id, 'transaction_id' => $data['transaction_id'] ?? null,
                'filed_by' => $user->id, 'reason' => $data['reason'], 'status' => 'open',
            ]);
            $this->attach($d, $request->file('files') ?? [], 'filing', $user);

            return $d;
        });

        return to_route('finance.disputes.show', $dispute)->with('success', 'Sanggahan diajukan. Bendahara akan memberikan klarifikasi.');
    }

    public function show(Request $request, FinanceDispute $dispute): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $dispute), 403);

        $dispute->load(['report', 'transaction.category', 'filer:id,name', 'clarifier:id,name', 'reviewer:id,name', 'decider:id,name', 'files']);
        $fmt = fn ($d) => $d?->format('d/m/Y H:i');
        $file = fn (FinanceDisputeFile $f) => ['id' => $f->id, 'name' => $f->original_name, 'stage' => $f->stage, 'url' => "/finance/dispute-files/{$f->id}"];

        return Inertia::render('Finance/DisputeShow', [
            'dispute' => [
                'id' => $dispute->id, 'status' => $dispute->status, 'status_label' => $dispute->statusLabel(),
                'report' => ['id' => $dispute->report->id, 'label' => $dispute->report->label()],
                'transaction' => $dispute->transaction ? [
                    'id' => $dispute->transaction->id, 'reference' => $dispute->transaction->reference,
                    'description' => $dispute->transaction->description, 'amount' => $dispute->transaction->amount,
                ] : null,
                'filer' => $dispute->filer?->name, 'filed_at' => $fmt($dispute->created_at), 'reason' => $dispute->reason,
                'clarification' => $dispute->clarification, 'clarified_by' => $dispute->clarifier?->name, 'clarified_at' => $fmt($dispute->clarified_at),
                'review_note' => $dispute->review_note, 'reviewed_by' => $dispute->reviewer?->name, 'reviewed_at' => $fmt($dispute->reviewed_at),
                'decision_note' => $dispute->decision_note, 'decided_by' => $dispute->decider?->name, 'decided_at' => $fmt($dispute->decided_at),
                'filing_files' => $dispute->files->where('stage', 'filing')->map($file)->values(),
                'clarification_files' => $dispute->files->where('stage', 'clarification')->map($file)->values(),
            ],
            'can' => [
                'clarify'  => $dispute->status === 'open' && $user->can('finance.record'),
                'review'   => $dispute->status === 'clarified' && $user->can('finance.review'),
                'decide'   => $dispute->status === 'reviewed' && $user->can('finance.approve'),
                'withdraw' => $dispute->filed_by === $user->id && in_array($dispute->status, ['open', 'clarified'], true),
            ],
        ]);
    }

    public function clarify(Request $request, FinanceDispute $dispute): RedirectResponse
    {
        $data = $request->validate([
            'clarification' => ['required', 'string', 'min:10', 'max:3000'],
            'files'         => ['nullable', 'array', 'max:3'],
            'files.*'       => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], ['clarification.required' => 'Klarifikasi wajib diisi.', 'clarification.min' => 'Klarifikasi minimal 10 karakter.']);

        if ($dispute->status !== 'open') {
            return back()->with('error', 'Sanggahan ini tidak sedang menunggu klarifikasi.');
        }
        if ($this->separationViolated($request, [$dispute->filed_by])) {
            return back()->with('error', 'Pemisahan tugas: pengaju sanggahan tidak boleh mengklarifikasi sanggahannya sendiri.');
        }

        DB::transaction(function () use ($request, $dispute, $data) {
            $dispute->update(['status' => 'clarified', 'clarification' => $data['clarification'], 'clarified_by' => $request->user()->id, 'clarified_at' => now()]);
            $this->attach($dispute, $request->file('files') ?? [], 'clarification', $request->user());
        });

        return back()->with('success', 'Klarifikasi disimpan dan diteruskan untuk review.');
    }

    public function review(Request $request, FinanceDispute $dispute): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], ['note.required' => 'Catatan review wajib diisi.']);

        if ($dispute->status !== 'clarified') {
            return back()->with('error', 'Sanggahan ini tidak sedang menunggu review.');
        }
        if ($this->separationViolated($request, [$dispute->filed_by, $dispute->clarified_by])) {
            return back()->with('error', 'Pemisahan tugas: pereview harus berbeda dari pengaju sanggahan dan pemberi klarifikasi.');
        }

        $dispute->update(['status' => 'reviewed', 'review_note' => $data['note'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        return back()->with('success', 'Review disimpan dan diteruskan untuk keputusan akhir.');
    }

    public function decide(Request $request, FinanceDispute $dispute): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['upheld', 'rejected'])],
            'note'     => ['required', 'string', 'max:1000'],
        ], ['note.required' => 'Alasan keputusan wajib diisi.']);

        if ($dispute->status !== 'reviewed') {
            return back()->with('error', 'Sanggahan ini tidak sedang menunggu keputusan akhir.');
        }
        if ($this->separationViolated($request, [$dispute->filed_by, $dispute->clarified_by, $dispute->reviewed_by])) {
            return back()->with('error', 'Pemisahan tugas: pemutus harus berbeda dari pengaju, pemberi klarifikasi, dan pereview.');
        }

        $dispute->update(['status' => $data['decision'], 'decision_note' => $data['note'], 'decided_by' => $request->user()->id, 'decided_at' => now()]);

        return back()->with('success', $data['decision'] === 'upheld'
            ? 'Sanggahan diterima. Bendahara perlu mencatat koreksi pada bulan yang masih terbuka.'
            : 'Sanggahan ditolak.');
    }

    public function withdraw(Request $request, FinanceDispute $dispute): RedirectResponse
    {
        abort_unless($dispute->filed_by === $request->user()->id, 403);

        if (! in_array($dispute->status, ['open', 'clarified'], true)) {
            return back()->with('error', 'Sanggahan ini tidak dapat dicabut lagi.');
        }

        $dispute->update(['status' => 'withdrawn']);

        return back()->with('success', 'Sanggahan dicabut.');
    }

    public function file(Request $request, FinanceDisputeFile $file): BinaryFileResponse
    {
        abort_unless($request->user()->can('view', $file->dispute), 403);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return response()->file(Storage::disk('local')->path($file->path), [
            'Content-Type' => $file->mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** @param  array<int, \Illuminate\Http\UploadedFile>  $files */
    private function attach(FinanceDispute $dispute, array $files, string $stage, User $by): void
    {
        $stored = [];

        try {
            foreach ($files as $file) {
                $f = FinanceFiles::store($file, "finance/disputes/{$dispute->id}");
                $stored[] = $f['path'];
                $dispute->files()->create([
                    'stage' => $stage, 'path' => $f['path'], 'original_name' => $f['original_name'],
                    'mime' => $f['mime'], 'size_bytes' => $f['size'], 'uploaded_by' => $by->id,
                ]);
            }
        } catch (\Throwable) {
            array_map(fn ($p) => FinanceFiles::delete($p), $stored);

            throw ValidationException::withMessages(['files' => 'Salah satu berkas tidak dapat diproses. Pastikan gambar/PDF valid.']);
        }
    }

    /** @param  array<int|null>  $others */
    private function separationViolated(Request $request, array $others): bool
    {
        return FinanceAccess::separationEnforced() && in_array($request->user()->id, array_filter($others), true);
    }
}
