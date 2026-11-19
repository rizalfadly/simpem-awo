<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Member;
use App\Models\MemberPosition;
use App\Models\Period;
use App\Models\Position;
use App\Models\User;
use App\Support\MemberNumberGenerator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        return tap(User::factory()->create(), fn (User $u) => $u->assignRole(User::SUPER_ADMIN));
    }

    private function period(bool $current = true): Period
    {
        return Period::create([
            'name' => $current ? '2026/2027' : '2025/2026',
            'start_date' => $current ? '2026-01-01' : '2025-01-01',
            'end_date' => $current ? '2026-12-31' : '2025-12-31',
            'is_current' => $current,
        ]);
    }

    private function position(string $scope = 'organization', ?int $max = 1, string $name = 'Ketua'): Position
    {
        return Position::create(['name' => $name, 'scope' => $scope, 'max_holders' => $max, 'sort_order' => 1, 'is_active' => true]);
    }

    // ---------- nomor anggota ----------
    public function test_member_number_is_generated_sequentially(): void
    {
        $admin = $this->superAdmin();
        $payload = ['name' => 'Ani', 'joined_at' => now()->toDateString(), 'status' => 'active'];

        $this->actingAs($admin)->post('/members', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/members', ['name' => 'Budi'] + $payload)->assertRedirect();

        $year = now()->year;
        $this->assertDatabaseHas('members', ['name' => 'Ani', 'nomor_anggota' => "AWO-{$year}-0001"]);
        $this->assertDatabaseHas('members', ['name' => 'Budi', 'nomor_anggota' => "AWO-{$year}-0002"]);
    }

    public function test_generator_skips_numbers_already_taken(): void
    {
        Member::factory()->create(['nomor_anggota' => 'AWO-2026-0001']);

        $this->assertSame('AWO-2026-0002', MemberNumberGenerator::next(2026));
        $this->assertSame('AWO-2026-0003', MemberNumberGenerator::next(2026));
    }

    // ---------- akses ----------
    public function test_member_module_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())->get('/members')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/organisasi')->assertForbidden();
    }

    public function test_user_with_permission_sees_member_list(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('members.view');
        Member::factory()->create(['name' => 'Citra']);

        $this->actingAs($u)->get('/members')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Members/Index')->has('members.data', 1));
    }

    // ---------- akun dari anggota ----------
    public function test_activating_account_creates_linked_user_with_temp_password(): void
    {
        $member = Member::factory()->create();

        $plain = null;
        $this->actingAs($this->superAdmin())->post("/members/{$member->id}/account")
            ->assertSessionHas('generated_password', function ($v) use (&$plain) {
                $plain = $v['password'];

                return true;
            });

        $user = User::where('member_id', $member->id)->firstOrFail();
        $this->assertSame($member->nomor_anggota, $user->nomor_anggota);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->hasRole('Anggota'));
        $this->assertStringNotContainsString($plain, AuditLog::all()->toJson());
    }

    public function test_inactive_member_cannot_get_an_account(): void
    {
        $member = Member::factory()->create(['status' => 'inactive']);

        $this->actingAs($this->superAdmin())->post("/members/{$member->id}/account")->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['member_id' => $member->id]);
    }

    public function test_changing_status_to_alumni_disables_account_and_ends_positions(): void
    {
        $period = $this->period();
        $position = $this->position();
        $member = Member::factory()->create();
        $user = User::factory()->create(['member_id' => $member->id, 'nomor_anggota' => $member->nomor_anggota]);
        $a = MemberPosition::create([
            'member_id' => $member->id, 'period_id' => $period->id, 'position_id' => $position->id,
            'started_at' => '2026-01-01',
        ]);

        $this->actingAs($this->superAdmin())->patch("/members/{$member->id}/status", ['status' => 'alumni'])
            ->assertSessionHas('success');

        $this->assertSame('alumni', $member->fresh()->status);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertNotNull($a->fresh()->ended_at);
    }

    // ---------- jabatan ----------
    public function test_position_max_holders_is_enforced(): void
    {
        $period = $this->period();
        $position = $this->position('organization', 1);
        [$m1, $m2] = [Member::factory()->create(), Member::factory()->create()];
        $admin = $this->superAdmin();
        $body = ['period_id' => $period->id, 'position_id' => $position->id, 'started_at' => '2026-02-01'];

        $this->actingAs($admin)->post("/members/{$m1->id}/assignments", $body)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post("/members/{$m2->id}/assignments", $body)->assertSessionHasErrors('position_id');

        $this->assertSame(1, MemberPosition::count());
    }

    public function test_division_is_required_for_division_level_positions(): void
    {
        $period = $this->period();
        $position = $this->position('division', null, 'Anggota Divisi');
        $member = Member::factory()->create();
        $admin = $this->superAdmin();
        $body = ['period_id' => $period->id, 'position_id' => $position->id, 'started_at' => '2026-02-01'];

        $this->actingAs($admin)->post("/members/{$member->id}/assignments", $body)->assertSessionHasErrors('division_id');

        $division = Division::create(['name' => 'Humas', 'is_active' => true]);
        $this->actingAs($admin)->post("/members/{$member->id}/assignments", $body + ['division_id' => $division->id])
            ->assertSessionHasNoErrors();
    }

    public function test_open_assignment_in_past_period_is_rejected(): void
    {
        $this->period();
        $old = $this->period(false);
        $position = $this->position();
        $member = Member::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post("/members/{$member->id}/assignments", ['period_id' => $old->id, 'position_id' => $position->id, 'started_at' => '2025-02-01'])
            ->assertSessionHasErrors('ended_at');
    }

    public function test_making_a_new_period_current_closes_previous_assignments(): void
    {
        $old = $this->period();
        $position = $this->position();
        $member = Member::factory()->create();
        $a = MemberPosition::create(['member_id' => $member->id, 'period_id' => $old->id, 'position_id' => $position->id, 'started_at' => '2026-01-05']);

        $this->actingAs($this->superAdmin())->post('/organisasi/periode', [
            'name' => '2027/2028', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'is_current' => true,
        ])->assertSessionHas('success');

        $this->assertFalse($old->fresh()->is_current);
        $this->assertSame('2026-12-31', $a->fresh()->ended_at->toDateString());
        $this->assertSame(1, Period::where('is_current', true)->count());
    }

    // ---------- kartu, QR, verifikasi ----------
    public function test_public_verification_page(): void
    {
        $member = Member::factory()->create();

        $this->get('/verifikasi/'.$member->qr_token)->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Public/Verify')->where('valid', true)->where('member.nomor_anggota', $member->nomor_anggota));

        $this->get('/verifikasi/'.str_repeat('x', 40))->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('valid', false)->where('member', null));
    }

    public function test_regenerating_qr_invalidates_old_card(): void
    {
        $member = Member::factory()->create();
        $old = $member->qr_token;

        $this->actingAs($this->superAdmin())->post("/members/{$member->id}/regenerate-qr");

        $this->assertNotSame($old, $member->fresh()->qr_token);
        $this->get('/verifikasi/'.$old)->assertInertia(fn (Assert $p) => $p->where('valid', false));
        $this->assertStringNotContainsString($old, AuditLog::all()->toJson());
    }

    public function test_qr_image_is_limited_to_owner_or_staff(): void
    {
        $member = Member::factory()->create();

        $this->get("/members/{$member->id}/qr")->assertRedirect('/login');

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get("/members/{$member->id}/qr")->assertForbidden();

        $owner = User::factory()->create(['member_id' => $member->id]);
        $this->actingAs($owner)->get("/members/{$member->id}/qr")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_my_card_redirects_to_own_card_or_404(): void
    {
        $member = Member::factory()->create();
        $owner = User::factory()->create(['member_id' => $member->id]);

        $this->actingAs($owner)->get('/kartu-saya')->assertRedirect("/members/{$member->id}/card");
        $this->actingAs(User::factory()->create())->get('/kartu-saya')->assertNotFound();
    }

    public function test_admin_seeder_creates_linked_member(): void
    {
        // Pada instalasi baru AdminSeeder membuat anggota + akun tertaut.
        $this->seed(\Database\Seeders\AdminSeeder::class);

        $admin = User::where('nomor_anggota', 'AWO-0001')->firstOrFail();
        $this->assertNotNull($admin->member_id);
        $this->assertSame('Administrator', $admin->member->name);
    }
}
