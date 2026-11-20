<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityReport;
use App\Models\ActivityTimeline;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    /** @return array{0: User, 1: Member} */
    private function memberUser(array $permissions = []): array
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['member_id' => $member->id, 'nomor_anggota' => $member->nomor_anggota]);
        $permissions && $user->givePermissionTo($permissions);

        return [$user, $member];
    }

    private function activity(Member $pic, array $attrs = []): Activity
    {
        return Activity::factory()->create(['pic_member_id' => $pic->id] + $attrs);
    }

    private function payload(Member $pic, array $extra = []): array
    {
        $start = now()->addDays(20)->setTime(8, 0);

        return array_merge([
            'title' => 'Bakti Sosial', 'pic_member_id' => $pic->id,
            'starts_at' => $start->format('Y-m-d H:i:s'), 'ends_at' => $start->copy()->setTime(14, 0)->format('Y-m-d H:i:s'),
        ], $extra);
    }

    // ---------- pembuatan ----------
    public function test_creating_requires_permission_and_codes_are_sequential(): void
    {
        [$plain, $m] = $this->memberUser();
        $this->actingAs($plain)->post('/activities', $this->payload($m))->assertForbidden();

        [$creator, $pic] = $this->memberUser(['activities.create']);
        $this->actingAs($creator)->post('/activities', $this->payload($pic))->assertRedirect();
        $this->actingAs($creator)->post('/activities', $this->payload($pic, ['title' => 'Seminar']))->assertRedirect();

        $year = now()->addDays(20)->year;
        $this->assertDatabaseHas('activities', ['title' => 'Bakti Sosial', 'code' => sprintf('KGT-%d-001', $year), 'status' => 'draft']);
        $this->assertDatabaseHas('activities', ['title' => 'Seminar', 'code' => sprintf('KGT-%d-002', $year)]);
    }

    public function test_pic_must_be_an_active_member(): void
    {
        [$creator] = $this->memberUser(['activities.create']);
        $inactive = Member::factory()->create(['status' => 'inactive']);

        $this->actingAs($creator)->post('/activities', $this->payload($inactive))->assertSessionHasErrors('pic_member_id');
    }

    public function test_description_text_is_not_written_to_audit_log(): void
    {
        [$creator, $pic] = $this->memberUser(['activities.create']);

        $this->actingAs($creator)->post('/activities', $this->payload($pic, ['description' => 'RAHASIA-DESKRIPSI-123']));

        $this->assertStringNotContainsString('RAHASIA-DESKRIPSI-123', AuditLog::all()->toJson());
        $this->assertDatabaseHas('audit_logs', ['event' => 'created', 'auditable_type' => (new Activity)->getMorphClass()]);
    }

    // ---------- visibilitas & hak akses ----------
    public function test_members_only_see_public_activities_plus_their_own(): void
    {
        [$viewer] = $this->memberUser();
        [, $other] = $this->memberUser();
        $this->activity($other, ['status' => 'approved']);
        $this->activity($other, ['status' => 'draft']);

        $this->actingAs($viewer)->get('/activities')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Activities/Index')->has('activities.data', 1));

        [$pic, $picMember] = $this->memberUser();
        $this->activity($picMember, ['status' => 'draft']);
        $this->actingAs($pic)->get('/activities')->assertInertia(fn (Assert $p) => $p->has('activities.data', 2));
    }

    public function test_basic_viewers_do_not_receive_proposal_budget_or_report(): void
    {
        [$viewer] = $this->memberUser();
        [, $other] = $this->memberUser();
        $a = $this->activity($other, ['status' => 'approved', 'proposal_background' => 'rahasia']);
        $a->budgetItems()->create(['type' => 'expense', 'category' => 'Konsumsi', 'description' => 'Nasi', 'quantity' => 10, 'unit_price' => 15000]);

        $this->actingAs($viewer)->get("/activities/{$a->id}")->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Activities/Show')->where('can.full', false)->where('proposal', null)->where('budget', null));
    }

    public function test_draft_is_hidden_from_strangers(): void
    {
        [$stranger] = $this->memberUser();
        [, $other] = $this->memberUser();
        $a = $this->activity($other, ['status' => 'draft']);

        $this->actingAs($stranger)->get("/activities/{$a->id}")->assertForbidden();
    }

    public function test_pic_can_manage_own_activity_but_strangers_cannot(): void
    {
        [$pic, $picMember] = $this->memberUser();
        [$stranger] = $this->memberUser();
        $a = $this->activity($picMember, ['status' => 'approved']);
        $body = $this->payload($picMember, ['title' => 'Judul Baru']);

        $this->actingAs($stranger)->put("/activities/{$a->id}", $body)->assertForbidden();
        $this->actingAs($pic)->put("/activities/{$a->id}", $body)->assertRedirect();

        $this->assertSame('Judul Baru', $a->fresh()->title);
    }

    // ---------- alur proposal ----------
    public function test_proposal_submission_requires_completeness_then_locks_budget(): void
    {
        [$pic, $m] = $this->memberUser();
        $a = $this->activity($m);

        $this->actingAs($pic)->post("/activities/{$a->id}/submit")->assertSessionHas('error');
        $this->assertSame('draft', $a->fresh()->status);

        $this->actingAs($pic)->put("/activities/{$a->id}/proposal", ['proposal_background' => 'Latar', 'proposal_objectives' => 'Tujuan'])
            ->assertSessionHasNoErrors();
        $this->actingAs($pic)->post("/activities/{$a->id}/budget", [
            'type' => 'expense', 'category' => 'Konsumsi', 'description' => 'Nasi kotak', 'quantity' => 50, 'unit' => 'porsi', 'unit_price' => 20000,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1000000, $a->budgetItems()->first()->planned_amount);

        $this->actingAs($pic)->post("/activities/{$a->id}/submit")->assertSessionHas('success');
        $this->assertSame('proposed', $a->fresh()->status);

        $this->actingAs($pic)->post("/activities/{$a->id}/budget", [
            'type' => 'expense', 'category' => 'Lain', 'description' => 'Tambahan', 'quantity' => 1, 'unit_price' => 1000,
        ])->assertSessionHas('error');
        $this->assertSame(1, $a->budgetItems()->count());
    }

    public function test_only_approvers_can_decide_and_rejection_needs_a_note(): void
    {
        [, $m] = $this->memberUser();
        $a = $this->activity($m, ['status' => 'proposed']);

        [$plain] = $this->memberUser();
        $this->actingAs($plain)->post("/activities/{$a->id}/decision", ['decision' => 'approve'])->assertForbidden();

        [$approver] = $this->memberUser(['activities.approve']);
        $this->actingAs($approver)->post("/activities/{$a->id}/decision", ['decision' => 'reject'])->assertSessionHasErrors('note');
        $this->actingAs($approver)->post("/activities/{$a->id}/decision", ['decision' => 'reject', 'note' => 'Anggaran terlalu besar']);
        $this->assertSame('rejected', $a->fresh()->status);

        $a->refresh()->update(['status' => 'proposed']);
        $this->actingAs($approver)->post("/activities/{$a->id}/decision", ['decision' => 'approve']);
        $this->assertSame('approved', $a->fresh()->status);
        $this->assertSame($approver->id, $a->fresh()->decided_by);
    }

    // ---------- panitia & timeline ----------
    public function test_committee_rules(): void
    {
        [$pic, $m] = $this->memberUser();
        $a = $this->activity($m);
        [$m1, $m2] = [Member::factory()->create(), Member::factory()->create()];
        $inactive = Member::factory()->create(['status' => 'inactive']);
        $url = "/activities/{$a->id}/committee";

        $this->actingAs($pic)->post($url, ['member_id' => $m1->id, 'role' => 'leader'])->assertSessionHasNoErrors();
        $this->actingAs($pic)->post($url, ['member_id' => $m2->id, 'role' => 'leader'])->assertSessionHasErrors('role');
        $this->actingAs($pic)->post($url, ['member_id' => $m1->id, 'role' => 'member'])->assertSessionHasErrors('member_id');
        $this->actingAs($pic)->post($url, ['member_id' => $inactive->id, 'role' => 'member'])->assertSessionHasErrors('member_id');
        $this->actingAs($pic)->post($url, ['member_id' => $m2->id, 'role' => 'coordinator'])->assertSessionHasErrors('section');
        $this->actingAs($pic)->post($url, ['member_id' => $m2->id, 'role' => 'coordinator', 'section' => 'Humas'])->assertSessionHasNoErrors();

        $this->assertSame(2, $a->committee()->count());
    }

    public function test_committee_cannot_change_after_cancellation(): void
    {
        [$pic, $m] = $this->memberUser();
        $a = $this->activity($m, ['status' => 'cancelled']);

        $this->actingAs($pic)->post("/activities/{$a->id}/committee", ['member_id' => Member::factory()->create()->id, 'role' => 'member'])
            ->assertSessionHas('error');
        $this->assertSame(0, $a->committee()->count());
    }

    public function test_task_pic_can_update_status_but_others_cannot(): void
    {
        [, $m] = $this->memberUser();
        $a = $this->activity($m, ['status' => 'approved']);
        [$taskPic, $taskMember] = $this->memberUser();
        [$other] = $this->memberUser();
        $t = $a->timelines()->create(['kind' => 'preparation', 'title' => 'Survei lokasi', 'starts_at' => now()->addDay(), 'pic_member_id' => $taskMember->id, 'status' => 'todo']);

        $this->actingAs($other)->patch("/timelines/{$t->id}/status", ['status' => 'done'])->assertForbidden();
        $this->actingAs($taskPic)->patch("/timelines/{$t->id}/status", ['status' => 'done'])->assertSessionHasNoErrors();

        $this->assertSame('done', ActivityTimeline::find($t->id)->status);
    }

    // ---------- selesai & LPJ ----------
    public function test_cannot_complete_before_start_and_completion_creates_report(): void
    {
        [$pic, $m] = $this->memberUser();
        $future = $this->activity($m, ['status' => 'approved']);
        $past = $this->activity($m, ['status' => 'approved', 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);

        $this->actingAs($pic)->post("/activities/{$future->id}/complete")->assertSessionHas('error');
        $this->assertSame('approved', $future->fresh()->status);

        $this->actingAs($pic)->post("/activities/{$past->id}/complete")->assertSessionHas('success');
        $this->assertSame('completed', $past->fresh()->status);
        $this->assertDatabaseHas('activity_reports', ['activity_id' => $past->id, 'status' => 'draft']);
    }

    public function test_report_flow_requires_actuals_and_acceptance_closes_activity(): void
    {
        [$pic, $m] = $this->memberUser();
        $a = $this->activity($m, ['status' => 'completed', 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(2)]);
        ActivityReport::create(['activity_id' => $a->id, 'status' => 'draft']);
        $item = $a->budgetItems()->create(['type' => 'expense', 'category' => 'Konsumsi', 'description' => 'Nasi', 'quantity' => 10, 'unit_price' => 10000]);

        $this->actingAs($pic)->put("/activities/{$a->id}/report", ['summary' => 'Acara lancar', 'realized_participants' => 40])
            ->assertSessionHasNoErrors();
        $this->actingAs($pic)->post("/activities/{$a->id}/report/submit")->assertSessionHas('error');

        $this->actingAs($pic)->patch("/budget-items/{$item->id}/actual", ['actual_amount' => 95000])->assertSessionHasNoErrors();
        $this->actingAs($pic)->post("/activities/{$a->id}/report/submit")->assertSessionHas('success');
        $this->assertSame('submitted', $a->report()->first()->status);

        // terkunci setelah diajukan
        $this->actingAs($pic)->patch("/budget-items/{$item->id}/actual", ['actual_amount' => 1])->assertSessionHas('error');

        [$plain] = $this->memberUser();
        $this->actingAs($plain)->post("/activities/{$a->id}/report/review", ['decision' => 'accept'])->assertForbidden();

        [$approver] = $this->memberUser(['activities.approve']);
        $this->actingAs($approver)->post("/activities/{$a->id}/report/review", ['decision' => 'revise'])->assertSessionHasErrors('note');
        $this->actingAs($approver)->post("/activities/{$a->id}/report/review", ['decision' => 'accept']);

        $this->assertSame('accepted', $a->report()->first()->status);
        $this->assertSame('closed', $a->fresh()->status);
    }

    // ---------- berkas privat ----------
    public function test_proposal_file_is_private_to_authorized_users(): void
    {
        Storage::fake('local');
        [$pic, $m] = $this->memberUser();
        [$stranger] = $this->memberUser();
        $a = $this->activity($m, ['status' => 'approved']);

        $this->actingAs($pic)->put("/activities/{$a->id}/proposal", [
            'proposal_file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        // status approved: proposal terkunci untuk edit, jadi simpan lewat draft dulu
        $this->assertNull($a->fresh()->proposal_file_path);

        $a->update(['status' => 'draft']);
        $this->actingAs($pic)->put("/activities/{$a->id}/proposal", [
            'proposal_file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $path = $a->fresh()->proposal_file_path;
        Storage::disk('local')->assertExists($path);

        $a->update(['status' => 'approved']);
        $this->actingAs($pic)->get("/activities/{$a->id}/proposal/file")->assertOk();
        $this->actingAs($stranger)->get("/activities/{$a->id}/proposal/file")->assertForbidden();
    }

    public function test_proposal_file_rejects_wrong_type(): void
    {
        Storage::fake('local');
        [$pic, $m] = $this->memberUser();
        $a = $this->activity($m);

        $this->actingAs($pic)->put("/activities/{$a->id}/proposal", [
            'proposal_file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('proposal_file');
    }
}
