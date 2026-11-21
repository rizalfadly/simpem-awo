<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetLoan;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
    }

    private function user(array $permissions = []): User
    {
        $m = Member::factory()->create();
        $u = User::factory()->create(['member_id' => $m->id, 'nomor_anggota' => $m->nomor_anggota]);
        $permissions && $u->givePermissionTo($permissions);

        return $u;
    }

    private function manager(): User
    {
        return $this->user(['assets.view', 'assets.manage', 'assets.loans']);
    }

    private function day(int $offset): string
    {
        return today()->addDays($offset)->toDateString();
    }

    private function asset(array $attrs = []): Asset
    {
        return Asset::factory()->create($attrs);
    }

    private function payload(array $items, int $start = 1, int $length = 2, array $extra = []): array
    {
        return $extra + [
            'purpose' => 'Bakti sosial', 'loan_date' => $this->day($start), 'due_date' => $this->day($start + $length - 1),
            'items' => $items,
        ];
    }

    /** Peminjaman langsung lewat model (untuk menyiapkan keadaan tertentu). */
    private function loan(User $borrower, Asset $asset, int $qty, string $status, int $from = 1, int $to = 2): AssetLoan
    {
        $loan = AssetLoan::create([
            'code' => 'PJM-T-'.++$this->seq, 'borrower_member_id' => $borrower->member_id, 'requested_by' => $borrower->id,
            'purpose' => 'uji', 'loan_date' => $this->day($from), 'due_date' => $this->day($to), 'status' => $status,
        ]);
        $loan->items()->create(['asset_id' => $asset->id, 'quantity' => $qty]);

        return $loan;
    }

    // ---------- katalog & hak akses ----------
    public function test_members_only_see_active_loanable_assets_and_no_internal_details(): void
    {
        $this->asset(['name' => 'Tenda', 'qty_good' => 3]);
        $this->asset(['name' => 'Server', 'is_loanable' => false]);
        $this->asset(['name' => 'Tua', 'status' => 'retired']);

        $this->actingAs($this->user())->get('/inventory')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Inventory/Index')->has('assets.data', 1)->where('assets.data.0.conditions', null));

        $this->actingAs($this->manager())->get('/inventory')
            ->assertInertia(fn (Assert $p) => $p->has('assets.data', 3)->where('assets.data.0.conditions.good', fn ($v) => is_int($v)));
    }

    public function test_non_loanable_asset_pages_are_hidden_from_members(): void
    {
        $hidden = $this->asset(['is_loanable' => false]);
        $open = $this->asset();
        $member = $this->user();

        $this->actingAs($member)->get("/inventory/{$hidden->id}")->assertForbidden();
        $this->actingAs($member)->get("/inventory/{$open->id}")->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('asset.details', null)->where('usage', []));
    }

    // ---------- data barang ----------
    public function test_creating_assets_needs_permission_and_codes_are_sequential(): void
    {
        $body = ['name' => 'Proyektor', 'unit' => 'unit', 'qty_good' => 2, 'qty_minor' => 0, 'qty_broken' => 0, 'qty_lost' => 0, 'is_loanable' => true];

        $this->actingAs($this->user())->post('/inventory', $body)->assertForbidden();

        $m = $this->manager();
        $this->actingAs($m)->post('/inventory', $body)->assertSessionHasNoErrors();
        $this->actingAs($m)->post('/inventory', ['name' => 'Kursi'] + $body)->assertSessionHasNoErrors();

        $this->assertSame(['AST-0001', 'AST-0002'], Asset::orderBy('id')->pluck('code')->all());
    }

    public function test_an_asset_needs_at_least_one_unit(): void
    {
        $this->actingAs($this->manager())->post('/inventory', [
            'name' => 'Kosong', 'unit' => 'unit', 'qty_good' => 0, 'qty_minor' => 0, 'qty_broken' => 0, 'qty_lost' => 0, 'is_loanable' => true,
        ])->assertSessionHasErrors('qty_good');
    }

    public function test_condition_changes_are_audited_and_cannot_undercut_lent_units(): void
    {
        $m = $this->manager();
        $asset = $this->asset(['qty_good' => 10]);
        $base = ['name' => $asset->name, 'unit' => 'unit', 'qty_minor' => 0, 'qty_broken' => 0, 'qty_lost' => 0, 'is_loanable' => true];

        $this->actingAs($m)->put("/inventory/{$asset->id}", ['qty_good' => 8] + $base)->assertSessionHasNoErrors();
        $log = AuditLog::where('auditable_type', $asset->getMorphClass())->where('auditable_id', $asset->id)->where('event', 'updated')->firstOrFail();
        $this->assertEquals(10, $log->old_values['qty_good']);
        $this->assertEquals(8, $log->new_values['qty_good']);

        $this->loan($this->user(), $asset, 6, 'lent');
        $this->actingAs($m)->put("/inventory/{$asset->id}", ['qty_good' => 4] + $base)->assertSessionHasErrors('qty_good');
        $this->assertSame(8, $asset->fresh()->qty_good);
    }

    public function test_photos_are_processed_and_follow_visibility(): void
    {
        $m = $this->manager();
        $this->actingAs($m)->post('/inventory', [
            'name' => 'Speaker', 'unit' => 'unit', 'qty_good' => 1, 'qty_minor' => 0, 'qty_broken' => 0, 'qty_lost' => 0, 'is_loanable' => true,
            'photo' => UploadedFile::fake()->image('p.jpg', 1200, 800),
        ])->assertSessionHasNoErrors();

        $asset = Asset::firstOrFail();
        Storage::disk('local')->assertExists($asset->photo_path);
        Storage::disk('local')->assertExists($asset->photo_thumb_path);

        $member = $this->user();
        $this->actingAs($member)->get("/inventory/{$asset->id}/photo")->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $asset->update(['is_loanable' => false]);
        $this->actingAs($member)->get("/inventory/{$asset->id}/photo")->assertForbidden();
    }

    public function test_categories_are_protected_when_used(): void
    {
        $m = $this->manager();
        $this->actingAs($m)->post('/inventory/categories', ['name' => 'Tenda', 'is_active' => true])->assertSessionHasNoErrors();
        $this->actingAs($m)->post('/inventory/categories', ['name' => 'Tenda', 'is_active' => true])->assertSessionHasErrors('name');

        $cat = AssetCategory::firstOrFail();
        $this->asset(['category_id' => $cat->id]);
        $this->actingAs($m)->delete("/inventory/categories/{$cat->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('asset_categories', ['id' => $cat->id]);
    }

    // ---------- pengajuan ----------
    public function test_a_member_can_request_a_loan_and_requests_do_not_reserve_stock(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $member = $this->user();

        $this->actingAs($member)->post('/inventory/loans', $this->payload([['asset_id' => $asset->id, 'quantity' => 3]]))
            ->assertSessionHasNoErrors();

        $loan = AssetLoan::firstOrFail();
        $this->assertSame('requested', $loan->status);
        $this->assertMatchesRegularExpression('/^PJM-\d{4}-0001$/', $loan->code);
        $this->assertSame($member->member_id, $loan->borrower_member_id);
        $this->assertSame(5, $asset->availableFor($this->day(1), $this->day(2)));
    }

    public function test_loan_request_validation(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $locked = $this->asset(['is_loanable' => false]);
        $u = $this->user();
        $ok = [['asset_id' => $asset->id, 'quantity' => 1]];

        $this->actingAs($u)->post('/inventory/loans', $this->payload($ok, -1))->assertSessionHasErrors('loan_date');
        $this->actingAs($u)->post('/inventory/loans', ['due_date' => $this->day(1), 'loan_date' => $this->day(3)] + $this->payload($ok))->assertSessionHasErrors('due_date');
        $this->actingAs($u)->post('/inventory/loans', $this->payload($ok, 1, 45))->assertSessionHasErrors('due_date');
        $this->actingAs($u)->post('/inventory/loans', $this->payload([['asset_id' => $asset->id, 'quantity' => 6]]))->assertSessionHasErrors('items.0.quantity');
        $this->actingAs($u)->post('/inventory/loans', $this->payload([['asset_id' => $locked->id, 'quantity' => 1]]))->assertSessionHasErrors('items.0.asset_id');
        $this->actingAs($u)->post('/inventory/loans', $this->payload([['asset_id' => $asset->id, 'quantity' => 1], ['asset_id' => $asset->id, 'quantity' => 1]]))->assertSessionHasErrors();

        $this->assertSame(0, AssetLoan::count());
    }

    public function test_accounts_without_a_member_cannot_borrow(): void
    {
        $asset = $this->asset();
        $loner = User::factory()->create(['member_id' => null]);

        $this->actingAs($loner)->get('/inventory/loans/create')->assertForbidden();
        $this->actingAs($loner)->post('/inventory/loans', $this->payload([['asset_id' => $asset->id, 'quantity' => 1]]))
            ->assertSessionHasErrors('borrower_member_id');
    }

    public function test_borrowers_with_overdue_items_cannot_request_more(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $other = $this->asset(['qty_good' => 5]);
        $u = $this->user();
        $this->loan($u, $asset, 1, 'lent', -5, -1);

        $this->actingAs($u)->post('/inventory/loans', $this->payload([['asset_id' => $other->id, 'quantity' => 1]]))
            ->assertSessionHasErrors('items');
        $this->assertSame(1, AssetLoan::count());
    }

    // ---------- reservasi per tanggal ----------
    public function test_approved_loans_reserve_stock_only_on_overlapping_dates(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $first = $this->user();
        $this->loan($first, $asset, 4, 'approved', 1, 3);

        $second = $this->user();
        $this->actingAs($second)->post('/inventory/loans', $this->payload([['asset_id' => $asset->id, 'quantity' => 2]], 2, 2))
            ->assertSessionHasErrors('items.0.quantity');

        $this->actingAs($second)->post('/inventory/loans', $this->payload([['asset_id' => $asset->id, 'quantity' => 2]], 5, 2))
            ->assertSessionHasNoErrors();   // tanggal tidak beririsan
        $this->assertSame(1, $asset->availableFor($this->day(2), $this->day(2)));
    }

    public function test_overdue_lent_items_keep_blocking_stock(): void
    {
        $asset = $this->asset(['qty_good' => 3]);
        $this->loan($this->user(), $asset, 3, 'lent', -10, -2);   // belum kembali walau jatuh tempo sudah lewat

        $this->assertSame(0, $asset->availableFor($this->day(1), $this->day(2)));
        $this->assertSame(0, $asset->availableToday());
    }

    public function test_approval_rechecks_stock_so_two_requests_cannot_both_win(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $mgr = $this->manager();
        $a = $this->user();
        $b = $this->user();
        $items = [['asset_id' => $asset->id, 'quantity' => 4]];

        $this->actingAs($a)->post('/inventory/loans', $this->payload($items));
        $this->actingAs($b)->post('/inventory/loans', $this->payload($items));
        [$first, $second] = [AssetLoan::orderBy('id')->first(), AssetLoan::orderByDesc('id')->first()];

        $this->actingAs($mgr)->post("/inventory/loans/{$first->id}/approve")->assertSessionHas('success');
        $this->actingAs($mgr)->post("/inventory/loans/{$second->id}/approve")->assertSessionHas('error');

        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('requested', $second->fresh()->status);
    }

    // ---------- persetujuan & pemisahan tugas ----------
    public function test_only_loan_managers_can_process_loans(): void
    {
        $asset = $this->asset();
        $borrower = $this->user();
        $loan = $this->loan($borrower, $asset, 1, 'requested');
        $member = $this->user();

        $this->actingAs($member)->post("/inventory/loans/{$loan->id}/approve")->assertForbidden();
        $this->actingAs($member)->post("/inventory/loans/{$loan->id}/handover")->assertForbidden();
        $this->actingAs($member)->post("/inventory/loans/{$loan->id}/return", ['items' => []])->assertForbidden();
    }

    public function test_managers_cannot_approve_their_own_loans_unless_separation_is_off(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $mgr = $this->manager();
        $loan = $this->loan($mgr, $asset, 1, 'requested');

        $this->actingAs($mgr)->post("/inventory/loans/{$loan->id}/approve")->assertSessionHas('error');
        $this->assertSame('requested', $loan->fresh()->status);

        $this->actingAs($this->manager())->post("/inventory/loans/{$loan->id}/approve")->assertSessionHas('success');

        config(['simpem.assets.separation_of_duties' => false]);
        $own = $this->loan($mgr, $asset, 1, 'requested', 10, 11);
        $this->actingAs($mgr)->post("/inventory/loans/{$own->id}/approve")->assertSessionHas('success');
    }

    public function test_rejection_needs_a_reason(): void
    {
        $loan = $this->loan($this->user(), $this->asset(), 1, 'requested');
        $mgr = $this->manager();

        $this->actingAs($mgr)->post("/inventory/loans/{$loan->id}/reject")->assertSessionHasErrors('reason');
        $this->actingAs($mgr)->post("/inventory/loans/{$loan->id}/reject", ['reason' => 'Dipakai kegiatan lain'])->assertSessionHas('success');

        $this->assertSame('rejected', $loan->fresh()->status);
    }

    public function test_borrowers_can_cancel_their_own_loans_and_strangers_cannot_even_see_them(): void
    {
        $borrower = $this->user();
        $stranger = $this->user();
        $loan = $this->loan($borrower, $this->asset(), 1, 'approved');

        $this->actingAs($stranger)->get("/inventory/loans/{$loan->id}")->assertForbidden();
        $this->actingAs($stranger)->post("/inventory/loans/{$loan->id}/cancel")->assertForbidden();

        $this->actingAs($borrower)->post("/inventory/loans/{$loan->id}/cancel", ['reason' => 'Acara batal'])->assertSessionHas('success');
        $this->assertSame('cancelled', $loan->fresh()->status);
        $this->actingAs($borrower)->post("/inventory/loans/{$loan->id}/cancel")->assertSessionHas('error');
    }

    // ---------- serah terima & pengembalian ----------
    public function test_handover_and_return_follow_the_state_machine(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $loan = $this->loan($this->user(), $asset, 2, 'requested');
        $mgr = $this->manager();

        $this->actingAs($mgr)->post("/inventory/loans/{$loan->id}/handover")->assertSessionHas('error');   // belum disetujui
        $loan->update(['status' => 'approved']);
        $this->actingAs($mgr)->post("/inventory/loans/{$loan->id}/handover")->assertSessionHas('success');
        $this->assertSame('lent', $loan->fresh()->status);
        $this->assertSame($mgr->id, $loan->fresh()->handed_by);
        $this->assertSame(2, $asset->lentQuantity());

        $this->actingAs($mgr)->post("/inventory/loans/{$loan->id}/cancel")->assertSessionHas('error');   // sudah diserahkan
    }

    public function test_return_updates_condition_buckets_and_records_history(): void
    {
        $asset = $this->asset(['qty_good' => 10]);
        $loan = $this->loan($this->user(), $asset, 6, 'lent');
        $item = $loan->items()->first();
        $mgr = $this->manager();

        // jumlah kondisi harus sama dengan jumlah dipinjam
        $this->actingAs($mgr)->post("/inventory/loans/{$loan->id}/return", [
            'items' => [['id' => $item->id, 'good' => 3, 'minor' => 1, 'broken' => 0, 'lost' => 0]],
        ])->assertSessionHasErrors('items');
        $this->assertSame('lent', $loan->fresh()->status);

        $this->actingAs($mgr)->post("/inventory/loans/{$loan->id}/return", [
            'items' => [['id' => $item->id, 'good' => 3, 'minor' => 1, 'broken' => 1, 'lost' => 1]], 'note' => 'Satu hilang di lokasi',
        ])->assertSessionHas('success');

        $asset->refresh();
        $this->assertSame(7, $asset->qty_good);      // 10 - (6 - 3)
        $this->assertSame(1, $asset->qty_minor);
        $this->assertSame(1, $asset->qty_broken);
        $this->assertSame(1, $asset->qty_lost);
        $this->assertSame(10, $asset->total());

        $loan->refresh();
        $this->assertSame('returned', $loan->status);
        $this->assertSame($mgr->id, $loan->received_by);
        $this->assertSame(3, $item->fresh()->returned_good);
        $this->assertSame(1, $item->fresh()->returned_lost);

        $this->actingAs($mgr)->get("/inventory/{$asset->id}")->assertInertia(fn (Assert $p) => $p
            ->has('usage', 1)->where('usage.0.returned.lost', 1)->has('history'));
    }

    public function test_lateness_is_detected_and_surfaces_on_the_dashboard(): void
    {
        $asset = $this->asset(['qty_good' => 3]);
        $borrower = $this->user();
        $loan = $this->loan($borrower, $asset, 1, 'lent', -6, -3);
        $mgr = $this->manager();

        $this->assertTrue($loan->isOverdue());
        $this->assertSame(3, $loan->lateDays());

        $this->actingAs($mgr)->get('/inventory/loans?status=overdue')->assertInertia(fn (Assert $p) => $p->has('loans.data', 1));
        $this->actingAs($mgr)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('stats', fn ($stats) => collect($stats)
            ->contains(fn ($s) => $s['label'] === 'Peminjaman perlu tindakan' && $s['value'] === 1)));
        $this->actingAs($borrower)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('my_loans', 1)->where('my_loans.0.overdue', true));
    }

    public function test_handover_is_blocked_when_condition_changes_left_too_little_good_stock(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $loan = $this->loan($this->user(), $asset, 4, 'approved');
        $asset->update(['qty_good' => 2, 'qty_broken' => 3]);   // pengelola mencatat kerusakan setelah disetujui

        $this->actingAs($this->manager())->post("/inventory/loans/{$loan->id}/handover")->assertSessionHas('error');
        $this->assertSame('approved', $loan->fresh()->status);
    }

    // ---------- pensiun & hapus ----------
    public function test_retiring_is_blocked_by_active_loans_and_retired_assets_cannot_be_borrowed(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $loan = $this->loan($this->user(), $asset, 1, 'approved');
        $mgr = $this->manager();

        $this->actingAs($mgr)->post("/inventory/{$asset->id}/retire", ['note' => 'Rusak'])->assertSessionHas('error');

        $loan->update(['status' => 'cancelled']);
        $this->actingAs($mgr)->post("/inventory/{$asset->id}/retire")->assertSessionHasErrors('note');
        $this->actingAs($mgr)->post("/inventory/{$asset->id}/retire", ['note' => 'Dijual'])->assertSessionHas('success');
        $this->assertSame('retired', $asset->fresh()->status);

        $this->actingAs($this->user())->post('/inventory/loans', $this->payload([['asset_id' => $asset->id, 'quantity' => 1]], 3))
            ->assertSessionHasErrors('items.0.asset_id');

        $this->actingAs($mgr)->post("/inventory/{$asset->id}/reactivate")->assertSessionHas('success');
        $this->assertSame('active', $asset->fresh()->status);
    }

    public function test_assets_with_loan_history_cannot_be_deleted(): void
    {
        $asset = $this->asset();
        $this->loan($this->user(), $asset, 1, 'returned');
        $clean = $this->asset();
        $mgr = $this->manager();

        $this->actingAs($mgr)->delete("/inventory/{$asset->id}")->assertSessionHas('error');
        $this->assertNotNull($asset->fresh());

        $this->actingAs($mgr)->delete("/inventory/{$clean->id}")->assertSessionHas('success');
        $this->assertNull(Asset::find($clean->id));
    }

    // ---------- ketersediaan (JSON) ----------
    public function test_availability_endpoint_reflects_reservations(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $this->loan($this->user(), $asset, 2, 'approved', 1, 2);

        $this->actingAs($this->user())->getJson("/inventory/availability?from={$this->day(1)}&to={$this->day(2)}")
            ->assertOk()->assertJson([(string) $asset->id => 3]);
        $this->actingAs($this->user())->getJson("/inventory/availability?from={$this->day(5)}&to={$this->day(6)}")
            ->assertOk()->assertJson([(string) $asset->id => 5]);
    }

    public function test_a_manager_can_record_a_loan_on_behalf_of_a_member(): void
    {
        $asset = $this->asset(['qty_good' => 5]);
        $mgr = $this->manager();
        $member = $this->user();

        $this->actingAs($mgr)->post('/inventory/loans', $this->payload([['asset_id' => $asset->id, 'quantity' => 1]], 1, 2, ['borrower_member_id' => $member->member_id]))
            ->assertSessionHasNoErrors();

        $loan = AssetLoan::firstOrFail();
        $this->assertSame($member->member_id, $loan->borrower_member_id);
        $this->assertSame($mgr->id, $loan->requested_by);

        // anggota biasa tidak bisa memakai nama orang lain
        $this->actingAs($member)->post('/inventory/loans', $this->payload([['asset_id' => $asset->id, 'quantity' => 1]], 6, 2, ['borrower_member_id' => $mgr->member_id]))
            ->assertSessionHasNoErrors();
        $this->assertSame($member->member_id, AssetLoan::orderByDesc('id')->first()->borrower_member_id);
    }
}
