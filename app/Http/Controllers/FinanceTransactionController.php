<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\FinanceCategory;
use App\Models\FinanceReport;
use App\Models\FinanceTransaction;
use App\Support\FinanceAccess;
use App\Support\FinanceFiles;
use App\Support\FinanceReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceTransactionController extends Controller
{
    /** Buku kas lengkap (termasuk draf). Untuk pengurus keuangan. */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(FinanceAccess::internal($user), 403);

        $filters = [
            'q'        => trim((string) $request->query('q', '')),
            'type'     => (string) $request->query('type', ''),
            'category' => (string) $request->query('category', ''),
            'activity' => (string) $request->query('activity', ''),
            'month'    => (string) $request->query('month', ''),
        ];

        $items = FinanceTransaction::query()
            ->with(['category:id,name', 'report:id,year,month,status', 'activity:id,title'])
            ->when($filters['q'] !== '', fn ($q) => $q->where(function ($q) use ($filters) {
                $s = '%'.$filters['q'].'%';
                $q->where('description', 'like', $s)->orWhere('reference', 'like', $s)->orWhere('counterparty', 'like', $s);
            }))
            ->when(array_key_exists($filters['type'], FinanceTransaction::TYPES), fn ($q) => $q->where('type', $filters['type']))
            ->when(ctype_digit($filters['category']) && $filters['category'] !== '', fn ($q) => $q->where('category_id', (int) $filters['category']))
            ->when(ctype_digit($filters['activity']) && $filters['activity'] !== '', fn ($q) => $q->where('activity_id', (int) $filters['activity']))
            ->when(preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $filters['month']), fn ($q) => $q
                ->whereYear('transaction_date', (int) substr($filters['month'], 0, 4))
                ->whereMonth('transaction_date', (int) substr($filters['month'], 5, 2)))
            ->orderByDesc('transaction_date')->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (FinanceTransaction $t) => [
                'id' => $t->id, 'reference' => $t->reference, 'date' => $t->transaction_date->format('d/m/Y'),
                'type' => $t->type, 'category' => $t->category->name, 'description' => $t->description,
                'amount' => $t->amount, 'has_proof' => $t->hasProof(), 'activity' => $t->activity?->title,
                'report_id' => $t->report_id, 'report_label' => $t->report->label(), 'report_status' => $t->report->statusLabel(),
            ]);

        return Inertia::render('Finance/Ledger', [
            'items'      => $items,
            'filters'    => $filters,
            'categories' => FinanceCategory::orderBy('type')->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'type']),
            'can_record' => $user->can('finance.record'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Finance/TransactionForm', [
            'transaction' => null,
            'options'     => $this->options($request),
            'defaults'    => ['type' => $request->query('type') === 'income' ? 'income' : 'expense', 'activity_id' => $request->integer('activity') ?: null],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureCategoryMatches($data['category_id'], $data['type']);

        $report = FinanceReport::forDate($data['transaction_date']);
        $this->ensureEditable($report);

        $proof = $this->storeProof($request, (int) date('Y', strtotime($data['transaction_date'])));
        unset($data['proof'], $data['remove_proof']);

        $tx = DB::transaction(fn () => FinanceTransaction::create($data + $proof + [
            'reference'   => FinanceReference::next($data['type'], (int) date('Y', strtotime($data['transaction_date']))),
            'report_id'   => $report->id,
            'recorded_by' => $request->user()->id,
        ]));

        return to_route('finance.reports.show', $report)->with('success', "Transaksi {$tx->reference} dicatat.");
    }

    public function show(Request $request, FinanceTransaction $transaction): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $transaction), 403);

        $transaction->load(['category:id,name', 'activity:id,code,title', 'recorder:id,name', 'report']);

        $history = $user->can('viewHistory', $transaction)
            ? AuditLog::where('auditable_type', $transaction->getMorphClass())->where('auditable_id', $transaction->id)
                ->orderBy('id')->get()->map(fn ($l) => [
                    'id' => $l->id, 'at' => $l->created_at?->format('d/m/Y H:i'), 'user' => $l->user_label ?? 'Sistem',
                    'event' => $l->event, 'old' => $l->old_values, 'new' => $l->new_values,
                ])
            : [];

        return Inertia::render('Finance/TransactionShow', [
            'transaction' => [
                'id' => $transaction->id, 'reference' => $transaction->reference, 'type' => $transaction->type,
                'type_label' => FinanceTransaction::TYPES[$transaction->type], 'date' => $transaction->transaction_date->format('d/m/Y'),
                'category' => $transaction->category->name, 'amount' => $transaction->amount, 'description' => $transaction->description,
                'counterparty' => $transaction->counterparty, 'activity' => $transaction->activity ? ['id' => $transaction->activity->id, 'title' => $transaction->activity->title] : null,
                'recorder' => $transaction->recorder?->name, 'proof_note' => $transaction->proof_note,
                'proof_url' => $transaction->hasProof() ? "/finance/transactions/{$transaction->id}/proof" : null,
                'proof_name' => $transaction->proof_name, 'proof_is_image' => str_starts_with((string) $transaction->proof_mime, 'image/'),
                'report' => ['id' => $transaction->report->id, 'label' => $transaction->report->label(), 'status_label' => $transaction->report->statusLabel()],
            ],
            'history' => $history,
            'can' => ['edit' => $user->can('finance.record') && $transaction->report->isEditable()],
        ]);
    }

    public function edit(Request $request, FinanceTransaction $transaction): Response|RedirectResponse
    {
        if (! $transaction->report->isEditable()) {
            return to_route('finance.transactions.show', $transaction)->with('error', 'Laporan sudah diajukan; transaksi terkunci.');
        }

        return Inertia::render('Finance/TransactionForm', [
            'transaction' => [
                'id' => $transaction->id, 'reference' => $transaction->reference, 'type' => $transaction->type,
                'category_id' => $transaction->category_id, 'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                'amount' => $transaction->amount, 'description' => $transaction->description, 'counterparty' => $transaction->counterparty,
                'activity_id' => $transaction->activity_id, 'proof_note' => $transaction->proof_note,
                'has_proof' => $transaction->hasProof(), 'proof_name' => $transaction->proof_name,
            ],
            'options'  => $this->options($request),
            'defaults' => [],
        ]);
    }

    public function update(Request $request, FinanceTransaction $transaction): RedirectResponse
    {
        if (! $transaction->report->isEditable()) {
            return back()->with('error', 'Laporan sudah diajukan; transaksi terkunci.');
        }

        $data = $this->validated($request, forType: $transaction->type);
        $data['type'] = $transaction->type;                      // jenis tidak dapat diubah (nomor bukti mengikuti jenis)
        $this->ensureCategoryMatches($data['category_id'], $transaction->type);

        $report = FinanceReport::forDate($data['transaction_date']);
        $this->ensureEditable($report);

        $extra = ['report_id' => $report->id];
        if ($request->hasFile('proof')) {
            FinanceFiles::delete($transaction->proof_path);
            $p = $this->storeProof($request, (int) $transaction->transaction_date->format('Y'));
            $extra += $p;
        } elseif ($request->boolean('remove_proof')) {
            FinanceFiles::delete($transaction->proof_path);
            $extra += ['proof_path' => null, 'proof_name' => null, 'proof_mime' => null];
        }
        unset($data['proof'], $data['remove_proof']);

        $transaction->update($data + $extra);

        return to_route('finance.reports.show', $report)->with('success', "Transaksi {$transaction->reference} diperbarui.");
    }

    public function destroy(FinanceTransaction $transaction): RedirectResponse
    {
        if (! $transaction->report->isEditable()) {
            return back()->with('error', 'Laporan sudah diajukan; transaksi terkunci.');
        }

        $report = $transaction->report;
        $transaction->delete();   // soft delete + tercatat di audit log

        return to_route('finance.reports.show', $report)->with('success', 'Transaksi dihapus.');
    }

    public function proof(Request $request, FinanceTransaction $transaction): BinaryFileResponse
    {
        abort_unless($request->user()->can('view', $transaction), 403);
        abort_unless($transaction->proof_path && Storage::disk('local')->exists($transaction->proof_path), 404);

        return response()->file(Storage::disk('local')->path($transaction->proof_path), [
            'Content-Type' => $transaction->proof_mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validated(Request $request, ?string $forType = null): array
    {
        return $request->validate([
            'type'             => [$forType ? 'nullable' : 'required', Rule::in(array_keys(FinanceTransaction::TYPES))],
            'category_id'      => ['required', 'integer', Rule::exists('finance_categories', 'id')->where('is_active', true)],
            'transaction_date' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'amount'           => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'description'      => ['required', 'string', 'max:255'],
            'counterparty'     => ['nullable', 'string', 'max:120'],
            'activity_id'      => ['nullable', 'integer', 'exists:activities,id'],
            'proof'            => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'proof_note'       => ['nullable', 'string', 'max:255'],
            'remove_proof'     => ['nullable', 'boolean'],
        ], [
            'category_id.required'          => 'Pilih kategori.',
            'category_id.exists'            => 'Kategori tidak tersedia.',
            'transaction_date.required'     => 'Tanggal transaksi wajib diisi.',
            'transaction_date.before_or_equal' => 'Tanggal transaksi tidak boleh di masa depan.',
            'amount.required'               => 'Nominal wajib diisi.',
            'amount.integer'                => 'Nominal harus berupa angka bulat (rupiah).',
            'amount.min'                    => 'Nominal minimal Rp 1.',
            'description.required'          => 'Uraian wajib diisi.',
            'proof.mimes'                   => 'Bukti harus berformat PDF, JPG, atau PNG.',
            'proof.max'                     => 'Ukuran bukti maksimal 5 MB.',
        ]);
    }

    private function ensureCategoryMatches(int $categoryId, string $type): void
    {
        if (FinanceCategory::whereKey($categoryId)->value('type') !== $type) {
            throw ValidationException::withMessages(['category_id' => 'Kategori tidak sesuai dengan jenis transaksi.']);
        }
    }

    private function ensureEditable(FinanceReport $report): void
    {
        if (! $report->isEditable()) {
            throw ValidationException::withMessages([
                'transaction_date' => "Laporan {$report->label()} sudah ditutup (status: {$report->statusLabel()}). Catat sebagai koreksi pada tanggal di bulan yang masih terbuka.",
            ]);
        }
    }

    /** @return array{proof_path?:string, proof_name?:string, proof_mime?:string} */
    private function storeProof(Request $request, int $year): array
    {
        if (! $request->hasFile('proof')) {
            return [];
        }

        try {
            $f = FinanceFiles::store($request->file('proof'), "finance/proofs/{$year}");
        } catch (\Throwable) {
            throw ValidationException::withMessages(['proof' => 'Berkas bukti tidak dapat diproses. Pastikan gambar/PDF valid.']);
        }

        return ['proof_path' => $f['path'], 'proof_name' => $f['original_name'], 'proof_mime' => $f['mime']];
    }

    private function options(Request $request): array
    {
        return [
            'categories' => FinanceCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'type']),
            'activities' => Activity::visibleTo($request->user())->orderByDesc('starts_at')->limit(200)->get(['id', 'code', 'title']),
        ];
    }
}
