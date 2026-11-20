<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FinanceCategory;
use App\Models\FinanceDispute;
use App\Models\FinanceReport;
use App\Models\FinanceReportEvent;
use App\Models\FinanceTransaction;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\FinanceCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $this->seed(FinanceCategorySeeder::class);
    }

    private function user(array $permissions = []): User
    {
        $m = Member::factory()->create();
        $u = User::factory()->create(['member_id' => $m->id, 'nomor_anggota' => $m->nomor_anggota]);
        $permissions && $u->givePermissionTo($permissions);

        return $u;
    }

    /** Tanggal pada bulan lampau. startOfMonth() dulu agar tidak terkena limpahan hari (mis. 31 Mei - 3 bulan). */
    private function monthDate(int $monthsBack, int $day = 9): string
    {
        return now()->startOfMonth()->subMonths($monthsBack)->addDays($day)->toDateString();
    }

    private function pastDate(): string
    {
        return $this->monthDate(2);
    }

    private function cat(string $type): int
    {
        return FinanceCategory::where('type', $type)->orderBy('id')->value('id');
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('nota.pdf', 20, 'application/pdf');
    }

    /** Catat transaksi lewat HTTP seperti pengguna sungguhan. */
    private function record(User $by, string $type = 'expense', int $amount = 50000, ?string $date = null, bool $proof = true): FinanceTransaction
    {
        $this->actingAs($by)->post('/finance/transactions', array_filter([
            'type' => $type, 'category_id' => $this->cat($type), 'transaction_date' => $date ?? $this->pastDate(),
            'amount' => $amount, 'description' => 'Transaksi uji '.$amount,
            'proof' => $proof ? $this->pdf() : null,
        ]))->assertSessionHasNoErrors();

        return FinanceTransaction::orderByDesc('id')->firstOrFail();
    }

    /** Alur penuh: bendahara -> reviewer -> penyetuju -> penerbit, masing-masing orang berbeda. */
    private function publishedReport(): array
    {
        $b = $this->user(['finance.record', 'finance.view']);
        $r = $this->user(['finance.review']);
        $a = $this->user(['finance.approve']);
        $p = $this->user(['finance.publish']);

        $this->record($b, 'income', 1000000);
        $report = $this->record($b, 'expense', 250000)->report;

        $this->actingAs($b)->post("/finance/reports/{$report->id}/submit")->assertSessionHas('success');
        $this->actingAs($r)->post("/finance/reports/{$report->id}/review", ['decision' => 'forward'])->assertSessionHas('success');
        $this->actingAs($a)->post("/finance/reports/{$report->id}/approve", ['decision' => 'approve'])->assertSessionHas('success');
        $this->actingAs($p)->post("/finance/reports/{$report->id}/publish")->assertSessionHas('success');

        return [$report->fresh(), compact('b', 'r', 'a', 'p')];
    }

    // ---------- pencatatan ----------
    public function test_only_recorders_can_enter_transactions_and_references_are_sequential(): void
    {
        $this->actingAs($this->user())->get('/finance/transactions/create')->assertForbidden();

        $b = $this->user(['finance.record']);
        $first = $this->record($b, 'expense');
        $second = $this->record($b, 'expense');
        $income = $this->record($b, 'income');

        $year = (int) substr($this->pastDate(), 0, 4);
        $this->assertSame(sprintf('KK-%d-0001', $year), $first->reference);
        $this->assertSame(sprintf('KK-%d-0002', $year), $second->reference);
        $this->assertSame(sprintf('KM-%d-0001', $year), $income->reference);
        $this->assertSame('draft', $first->report->status);
        $this->assertSame($b->id, $first->recorded_by);
    }

    public function test_transaction_validation_rules(): void
    {
        $b = $this->user(['finance.record']);
        $base = ['type' => 'expense', 'category_id' => $this->cat('expense'), 'transaction_date' => $this->pastDate(), 'amount' => 1000, 'description' => 'x'];

        $this->actingAs($b)->post('/finance/transactions', ['category_id' => $this->cat('income')] + $base)->assertSessionHasErrors('category_id');
        $this->actingAs($b)->post('/finance/transactions', ['transaction_date' => now()->addDay()->toDateString()] + $base)->assertSessionHasErrors('transaction_date');
        $this->actingAs($b)->post('/finance/transactions', ['amount' => 0] + $base)->assertSessionHasErrors('amount');
        $this->actingAs($b)->post('/finance/transactions', ['amount' => 1500.5] + $base)->assertSessionHasErrors('amount');
        $this->assertSame(0, FinanceTransaction::count());
    }

    public function test_proofs_are_private_and_bad_files_are_rejected(): void
    {
        $b = $this->user(['finance.record']);
        $tx = $this->record($b);
        Storage::disk('local')->assertExists($tx->proof_path);
        $this->assertStringStartsWith('finance/proofs/', $tx->proof_path);

        $this->actingAs($b)->post('/finance/transactions', [
            'type' => 'expense', 'category_id' => $this->cat('expense'), 'transaction_date' => $this->pastDate(), 'amount' => 1000, 'description' => 'x',
            'proof' => UploadedFile::fake()->create('virus.exe', 5, 'application/octet-stream'),
        ])->assertSessionHasErrors('proof');

        $this->actingAs($b)->post('/finance/transactions', [
            'type' => 'expense', 'category_id' => $this->cat('expense'), 'transaction_date' => $this->pastDate(), 'amount' => 1000, 'description' => 'x',
            'proof' => UploadedFile::fake()->create('rusak.jpg', 5, 'image/jpeg'),
        ])->assertSessionHasErrors('proof');
    }

    public function test_opening_balance_carries_over_from_earlier_months(): void
    {
        $b = $this->user(['finance.record']);
        $this->record($b, 'income', 1000000, $this->monthDate(3, 2));
        $this->record($b, 'expense', 200000, $this->monthDate(3, 3));
        $report = $this->record($b, 'income', 500000)->report;

        $t = $report->liveTotals();
        $this->assertSame(800000, $t['opening']);
        $this->assertSame(500000, $t['income']);
        $this->assertSame(1300000, $t['closing']);
    }

    // ---------- pengajuan & penguncian ----------
    public function test_submission_requirements(): void
    {
        $b = $this->user(['finance.record']);

        // bulan berjalan belum boleh diajukan
        $current = $this->record($b, 'income', 1000, now()->toDateString())->report;
        $this->actingAs($b)->post("/finance/reports/{$current->id}/submit")->assertSessionHas('error');
        $this->assertSame('draft', $current->fresh()->status);

        // kas keluar tanpa bukti/alasan ditolak
        $tx = $this->record($b, 'expense', 5000, null, proof: false);
        $report = $tx->report;
        $this->actingAs($b)->post("/finance/reports/{$report->id}/submit")->assertSessionHas('error');

        // dengan alasan -> lolos
        $this->actingAs($b)->put("/finance/transactions/{$tx->id}", [
            'category_id' => $this->cat('expense'), 'transaction_date' => $this->pastDate(), 'amount' => 5000,
            'description' => 'Parkir', 'proof_note' => 'Parkir tidak ada nota',
        ])->assertSessionHasNoErrors();
        $this->actingAs($b)->post("/finance/reports/{$report->id}/submit")->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('submitted', $report->status);
        $this->assertSame(-5000, $report->closing_balance);
        $this->assertSame($b->id, $report->submitted_by);
    }

    public function test_submitted_reports_lock_their_transactions(): void
    {
        $b = $this->user(['finance.record']);
        $tx = $this->record($b);
        $this->actingAs($b)->post("/finance/reports/{$tx->report_id}/submit")->assertSessionHas('success');

        $this->actingAs($b)->put("/finance/transactions/{$tx->id}", [
            'category_id' => $this->cat('expense'), 'transaction_date' => $this->pastDate(), 'amount' => 999999, 'description' => 'diubah',
        ])->assertSessionHas('error');
        $this->actingAs($b)->delete("/finance/transactions/{$tx->id}")->assertSessionHas('error');
        $this->actingAs($b)->post('/finance/transactions', [
            'type' => 'expense', 'category_id' => $this->cat('expense'), 'transaction_date' => $this->pastDate(),
            'amount' => 1000, 'description' => 'susulan', 'proof' => $this->pdf(),
        ])->assertSessionHasErrors('transaction_date');

        $this->assertSame(50000, $tx->fresh()->amount);
        $this->assertSame(1, FinanceTransaction::count());
    }

    // ---------- alur & pemisahan tugas ----------
    public function test_full_approval_chain_records_every_actor(): void
    {
        [$report, $u] = $this->publishedReport();

        $this->assertSame('published', $report->status);
        $this->assertSame($u['b']->id, $report->submitted_by);
        $this->assertSame($u['r']->id, $report->reviewed_by);
        $this->assertSame($u['a']->id, $report->approved_by);
        $this->assertSame($u['p']->id, $report->published_by);
        $this->assertSame(['submitted', 'reviewed', 'approved', 'published'], FinanceReportEvent::orderBy('id')->pluck('action')->all());
        $this->assertSame(750000, $report->totals()['closing']);
    }

    public function test_separation_of_duties_is_enforced(): void
    {
        $b = $this->user(['finance.record', 'finance.review', 'finance.approve']);
        $r = $this->user(['finance.review', 'finance.approve']);
        $report = $this->record($b)->report;
        $this->actingAs($b)->post("/finance/reports/{$report->id}/submit");

        // pengaju tidak boleh mereview sendiri
        $this->actingAs($b)->post("/finance/reports/{$report->id}/review", ['decision' => 'forward'])->assertSessionHas('error');
        $this->assertSame('submitted', $report->fresh()->status);

        $this->actingAs($r)->post("/finance/reports/{$report->id}/review", ['decision' => 'forward'])->assertSessionHas('success');

        // pereview tidak boleh menyetujui; pengaju juga tidak
        $this->actingAs($r)->post("/finance/reports/{$report->id}/approve", ['decision' => 'approve'])->assertSessionHas('error');
        $this->actingAs($b)->post("/finance/reports/{$report->id}/approve", ['decision' => 'approve'])->assertSessionHas('error');
        $this->assertSame('reviewed', $report->fresh()->status);
    }

    public function test_separation_can_be_switched_off_for_tiny_organizations(): void
    {
        config(['simpem.finance.separation_of_duties' => false]);
        $b = $this->user(['finance.record', 'finance.review']);
        $report = $this->record($b)->report;
        $this->actingAs($b)->post("/finance/reports/{$report->id}/submit");

        $this->actingAs($b)->post("/finance/reports/{$report->id}/review", ['decision' => 'forward'])->assertSessionHas('success');
    }

    public function test_returning_a_report_unlocks_it_and_needs_a_note(): void
    {
        $b = $this->user(['finance.record']);
        $r = $this->user(['finance.review']);
        $tx = $this->record($b);
        $report = $tx->report;
        $this->actingAs($b)->post("/finance/reports/{$report->id}/submit");

        $this->actingAs($r)->post("/finance/reports/{$report->id}/review", ['decision' => 'return'])->assertSessionHasErrors('note');
        $this->actingAs($r)->post("/finance/reports/{$report->id}/review", ['decision' => 'return', 'note' => 'Nota belum lengkap'])->assertSessionHas('success');

        $this->assertSame('revision', $report->fresh()->status);
        $this->actingAs($b)->put("/finance/transactions/{$tx->id}", [
            'category_id' => $this->cat('expense'), 'transaction_date' => $this->pastDate(), 'amount' => 60000, 'description' => 'Diperbaiki',
        ])->assertSessionHasNoErrors();
        $this->assertSame(60000, $tx->fresh()->amount);
    }

    public function test_only_the_right_permission_can_act_at_each_stage(): void
    {
        $b = $this->user(['finance.record']);
        $report = $this->record($b)->report;
        $plain = $this->user(['finance.view']);

        $this->actingAs($plain)->post("/finance/reports/{$report->id}/submit")->assertForbidden();
        $this->actingAs($plain)->post("/finance/reports/{$report->id}/review", ['decision' => 'forward'])->assertForbidden();
        $this->actingAs($plain)->post("/finance/reports/{$report->id}/approve", ['decision' => 'approve'])->assertForbidden();
        $this->actingAs($plain)->post("/finance/reports/{$report->id}/publish")->assertForbidden();
    }

    // ---------- transparansi ----------
    public function test_members_only_see_published_reports(): void
    {
        $b = $this->user(['finance.record']);
        $draft = $this->record($b, 'expense', 50000, $this->monthDate(4))->report;   // bulan berbeda dari laporan terbit
        [$published] = $this->publishedReport();
        $member = $this->user();

        $this->assertNotSame($draft->id, $published->id);
        $this->actingAs($member)->get("/finance/reports/{$draft->id}")->assertForbidden();
        $this->actingAs($member)->get("/finance/reports/{$published->id}")->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Finance/ReportShow')->where('workflow', null)->where('events', []));
        $this->actingAs($member)->get('/finance')->assertInertia(fn (Assert $p) => $p->has('reports.data', 1));
        $this->actingAs($member)->get('/finance/transactions')->assertForbidden();
        $this->actingAs($b)->get('/finance/transactions')->assertOk();
    }

    public function test_proof_files_follow_report_visibility(): void
    {
        $b = $this->user(['finance.record']);
        $draftTx = $this->record($b);
        $member = $this->user();

        $this->actingAs($member)->get("/finance/transactions/{$draftTx->id}/proof")->assertForbidden();
        $this->actingAs($b)->get("/finance/transactions/{$draftTx->id}/proof")->assertOk();

        [$published] = $this->publishedReport();
        $publishedTx = $published->transactions()->where('type', 'expense')->first();
        $this->actingAs($member)->get("/finance/transactions/{$publishedTx->id}/proof")->assertOk();
    }

    public function test_published_total_is_visible_on_dashboard(): void
    {
        $this->publishedReport();

        $this->actingAs($this->user())->get('/dashboard')
            ->assertInertia(fn (Assert $p) => $p->where('stats.0.value', 'Rp 750.000'));
    }

    // ---------- jejak audit ----------
    public function test_transaction_changes_are_audited_with_old_and_new_values(): void
    {
        $b = $this->user(['finance.record', 'finance.view']);
        $tx = $this->record($b, 'expense', 50000);

        $this->actingAs($b)->put("/finance/transactions/{$tx->id}", [
            'category_id' => $this->cat('expense'), 'transaction_date' => $this->pastDate(), 'amount' => 75000, 'description' => $tx->description,
        ])->assertSessionHasNoErrors();

        $log = AuditLog::where('auditable_type', $tx->getMorphClass())->where('auditable_id', $tx->id)->where('event', 'updated')->firstOrFail();
        $this->assertEquals(50000, $log->old_values['amount']);
        $this->assertEquals(75000, $log->new_values['amount']);

        $this->actingAs($b)->get("/finance/transactions/{$tx->id}")
            ->assertInertia(fn (Assert $p) => $p->component('Finance/TransactionShow')->has('history', 2));
    }

    public function test_history_is_hidden_from_regular_members(): void
    {
        [$published] = $this->publishedReport();
        $tx = $published->transactions()->first();

        $this->actingAs($this->user())->get("/finance/transactions/{$tx->id}")->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('history', []));
    }

    // ---------- kategori ----------
    public function test_categories_are_managed_by_recorders_and_protected_when_used(): void
    {
        $this->actingAs($this->user())->get('/finance/categories')->assertForbidden();

        $b = $this->user(['finance.record']);
        $this->actingAs($b)->post('/finance/categories', ['name' => 'Zakat', 'type' => 'income', 'is_active' => true, 'sort_order' => 5])->assertSessionHasNoErrors();
        $this->actingAs($b)->post('/finance/categories', ['name' => 'Zakat', 'type' => 'income', 'is_active' => true, 'sort_order' => 5])->assertSessionHasErrors('name');

        $used = FinanceCategory::find($this->cat('expense'));
        $this->record($b);
        $this->actingAs($b)->delete("/finance/categories/{$used->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('finance_categories', ['id' => $used->id]);
    }

    // ---------- sanggahan ----------
    public function test_disputes_can_only_be_filed_on_published_reports(): void
    {
        $b = $this->user(['finance.record']);
        $draft = $this->record($b)->report;
        $member = $this->user();

        $this->actingAs($member)->post("/finance/reports/{$draft->id}/disputes", ['reason' => str_repeat('alasan ', 5)])->assertSessionHas('error');
        $this->assertSame(0, FinanceDispute::count());

        [$published] = $this->publishedReport();
        $this->actingAs($member)->post("/finance/reports/{$published->id}/disputes", ['reason' => 'pendek'])->assertSessionHasErrors('reason');
        $this->actingAs($member)->post("/finance/reports/{$published->id}/disputes", [
            'reason' => 'Nominal konsumsi terlihat terlalu besar dibanding peserta.', 'files' => [$this->pdf()],
        ])->assertSessionHasNoErrors();

        $d = FinanceDispute::firstOrFail();
        $this->assertSame('open', $d->status);
        $this->assertSame(1, $d->files()->where('stage', 'filing')->count());
        Storage::disk('local')->assertExists($d->files()->first()->path);
    }

    public function test_dispute_flow_with_separate_people_and_privacy(): void
    {
        [$published, $u] = $this->publishedReport();
        $filer = $this->user();
        $stranger = $this->user();
        $this->actingAs($filer)->post("/finance/reports/{$published->id}/disputes", [
            'transaction_id' => $published->transactions()->where('type', 'expense')->value('id'),
            'reason' => 'Mohon dijelaskan rincian pengeluaran ini karena tidak sesuai anggaran.',
        ]);
        $d = FinanceDispute::firstOrFail();

        // privasi
        $this->actingAs($stranger)->get("/finance/disputes/{$d->id}")->assertForbidden();
        $this->actingAs($filer)->get("/finance/disputes/{$d->id}")->assertOk();
        $this->actingAs($u['b'])->get("/finance/disputes/{$d->id}")->assertOk();
        $this->actingAs($stranger)->get('/finance/disputes')->assertInertia(fn (Assert $p) => $p->has('items.data', 0));

        // urutan tahap
        $this->actingAs($u['r'])->post("/finance/disputes/{$d->id}/review", ['note' => 'terlalu awal'])->assertSessionHas('error');
        $this->actingAs($u['b'])->post("/finance/disputes/{$d->id}/clarify", ['clarification' => 'Pengeluaran sesuai nota terlampir.', 'files' => [$this->pdf()]])->assertSessionHas('success');
        $this->assertSame('clarified', $d->fresh()->status);

        // pemutus tidak boleh sama dengan pereview
        $this->actingAs($u['r'])->post("/finance/disputes/{$d->id}/review", ['note' => 'Klarifikasi cukup jelas.'])->assertSessionHas('success');
        $reviewerAlsoApprover = $this->user(['finance.approve']);
        $d->update(['reviewed_by' => $reviewerAlsoApprover->id]);
        $this->actingAs($reviewerAlsoApprover)->post("/finance/disputes/{$d->id}/decide", ['decision' => 'rejected', 'note' => 'x'])->assertSessionHas('error');

        $this->actingAs($u['a'])->post("/finance/disputes/{$d->id}/decide", ['decision' => 'upheld', 'note' => 'Perlu koreksi bulan berikutnya.'])->assertSessionHas('success');

        $d->refresh();
        $this->assertSame('upheld', $d->status);
        $this->assertSame($u['b']->id, $d->clarified_by);
        $this->assertSame($u['a']->id, $d->decided_by);
        $this->assertSame(1, $d->files()->where('stage', 'clarification')->count());
    }

    public function test_recorder_cannot_clarify_their_own_dispute_and_filer_can_withdraw(): void
    {
        [$published, $u] = $this->publishedReport();
        $this->actingAs($u['b'])->post("/finance/reports/{$published->id}/disputes", ['reason' => 'Saya ingin menanyakan sesuatu soal laporan ini.']);
        $d = FinanceDispute::firstOrFail();

        $this->actingAs($u['b'])->post("/finance/disputes/{$d->id}/clarify", ['clarification' => 'Saya jawab sendiri sanggahan saya.'])->assertSessionHas('error');
        $this->assertSame('open', $d->fresh()->status);

        $this->actingAs($u['b'])->post("/finance/disputes/{$d->id}/withdraw")->assertSessionHas('success');
        $this->assertSame('withdrawn', $d->fresh()->status);
    }
}
