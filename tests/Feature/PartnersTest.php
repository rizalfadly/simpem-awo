<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Partner;
use App\Models\PartnerProduct;
use App\Models\PartnerPromotion;
use App\Models\Partnership;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PartnersTest extends TestCase
{
    use RefreshDatabase;

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
        return $this->user(['partners.view', 'partners.manage']);
    }

    private function body(array $extra = []): array
    {
        return $extra + ['name' => 'Kopi Wetan', 'type' => 'umkm'];
    }

    private function promo(Partner $partner, array $attrs = []): PartnerPromotion
    {
        return PartnerPromotion::create($attrs + [
            'partner_id' => $partner->id, 'title' => 'Diskon anggota', 'audience' => 'members', 'status' => 'published',
            'starts_on' => today()->subDay(), 'ends_on' => today()->addDays(5),
        ]);
    }

    // ---------- direktori ----------
    public function test_members_see_only_active_partners_and_no_internal_data(): void
    {
        Partner::factory()->create(['name' => 'Aktif', 'contact_phone' => '0811111111']);
        Partner::factory()->create(['name' => 'Nonaktif', 'status' => 'inactive']);
        $active = Partner::where('name', 'Aktif')->first();

        $member = $this->user();
        $this->actingAs($member)->get('/partners')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Partners/Index')->has('partners.data', 1)->where('partners.data.0.is_public', null));
        $this->actingAs($member)->get("/partners/{$active->id}")->assertOk()
            ->assertInertia(fn (Assert $p) => $p->where('partner.internal', null)->where('partner.is_public', null));

        $this->actingAs($this->manager())->get('/partners')->assertInertia(fn (Assert $p) => $p->has('partners.data', 2));
        $this->actingAs($this->manager())->get("/partners/{$active->id}")
            ->assertInertia(fn (Assert $p) => $p->where('partner.internal.contact_phone', '0811111111'));
    }

    public function test_inactive_partner_pages_are_hidden_from_members(): void
    {
        $inactive = Partner::factory()->create(['status' => 'inactive']);

        $this->actingAs($this->user())->get("/partners/{$inactive->id}")->assertForbidden();
        $this->actingAs($this->manager())->get("/partners/{$inactive->id}")->assertOk();
    }

    // ---------- pengelolaan ----------
    public function test_creating_partners_needs_permission_and_slugs_are_unique(): void
    {
        $this->actingAs($this->user())->post('/partners', $this->body())->assertForbidden();

        $m = $this->manager();
        $this->actingAs($m)->post('/partners', $this->body())->assertSessionHasNoErrors();
        $this->actingAs($m)->post('/partners', $this->body())->assertSessionHasNoErrors();

        $this->assertSame(['kopi-wetan', 'kopi-wetan-2'], Partner::orderBy('id')->pluck('slug')->all());
    }

    public function test_partner_validation(): void
    {
        $m = $this->manager();

        $this->actingAs($m)->post('/partners', $this->body(['website' => 'javascript:alert(1)']))->assertSessionHasErrors('website');
        $this->actingAs($m)->post('/partners', $this->body(['website' => 'ftp://contoh.com']))->assertSessionHasErrors('website');
        $this->actingAs($m)->post('/partners', $this->body(['instagram' => 'nama usaha!']))->assertSessionHasErrors('instagram');
        $this->actingAs($m)->post('/partners', $this->body(['contact_phone' => 'abc']))->assertSessionHasErrors('contact_phone');
        $this->actingAs($m)->post('/partners', $this->body(['type' => 'bukan-jenis']))->assertSessionHasErrors('type');
        $this->assertSame(0, Partner::count());

        $this->actingAs($m)->post('/partners', $this->body(['website' => 'https://kopiwetan.id', 'instagram' => '@kopi.wetan']))->assertSessionHasNoErrors();
    }

    public function test_contact_details_are_not_written_to_the_audit_log(): void
    {
        $this->actingAs($this->manager())->post('/partners', $this->body([
            'contact_person' => 'Bu Rahasia', 'contact_phone' => '081234567890', 'contact_email' => 'rahasia@contoh.id', 'notes' => 'catatan-internal-xyz',
        ]))->assertSessionHasNoErrors();

        $json = AuditLog::all()->toJson();
        foreach (['Bu Rahasia', '081234567890', 'rahasia@contoh.id', 'catatan-internal-xyz'] as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
        $this->assertDatabaseHas('audit_logs', ['event' => 'created', 'auditable_type' => (new Partner)->getMorphClass()]);
    }

    public function test_logo_is_processed_and_served_with_visibility(): void
    {
        $this->actingAs($this->manager())->post('/partners', $this->body(['logo' => UploadedFile::fake()->image('logo.png', 900, 900)]))
            ->assertSessionHasNoErrors();

        $partner = Partner::firstOrFail();
        Storage::disk('local')->assertExists($partner->logo_path);
        Storage::disk('local')->assertExists($partner->logo_thumb_path);

        $member = $this->user();
        $this->actingAs($member)->get("/partners/{$partner->id}/logo")->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $partner->update(['status' => 'inactive']);
        $this->actingAs($member)->get("/partners/{$partner->id}/logo")->assertForbidden();
    }

    public function test_deactivating_a_partner_withdraws_it_from_public(): void
    {
        $partner = Partner::factory()->create(['is_public' => true]);

        $this->actingAs($this->manager())->post("/partners/{$partner->id}/status", ['status' => 'inactive'])->assertSessionHas('success');

        $partner->refresh();
        $this->assertSame('inactive', $partner->status);
        $this->assertFalse($partner->is_public);

        $this->actingAs($this->manager())->post("/partners/{$partner->id}/status", ['status' => 'active']);
        $this->assertFalse($partner->fresh()->is_public);   // harus dipilih ulang secara sadar
    }

    public function test_partners_with_running_partnerships_cannot_be_deleted(): void
    {
        $partner = Partner::factory()->create();
        Partnership::create(['partner_id' => $partner->id, 'title' => 'Sponsor', 'type' => 'sponsorship', 'status' => 'active']);
        $m = $this->manager();

        $this->actingAs($m)->delete("/partners/{$partner->id}")->assertSessionHas('error');
        $this->assertNotNull($partner->fresh());

        Partnership::query()->update(['status' => 'completed']);
        $this->actingAs($m)->delete("/partners/{$partner->id}")->assertSessionHas('success');
        $this->assertNull(Partner::find($partner->id));
    }

    // ---------- produk ----------
    public function test_products_are_managed_by_managers_only(): void
    {
        $partner = Partner::factory()->create();
        $body = ['name' => 'Kopi susu', 'price' => 18000, 'price_note' => 'per gelas', 'is_available' => true];

        $this->actingAs($this->user())->post("/partners/{$partner->id}/products", $body)->assertForbidden();

        $m = $this->manager();
        $this->actingAs($m)->post("/partners/{$partner->id}/products", ['price' => -5] + $body)->assertSessionHasErrors('price');
        $this->actingAs($m)->post("/partners/{$partner->id}/products", ['price' => 'gratis'] + $body)->assertSessionHasErrors('price');
        $this->actingAs($m)->post("/partners/{$partner->id}/products", $body + ['photo' => UploadedFile::fake()->image('k.jpg', 800, 600)])->assertSessionHasNoErrors();

        $product = PartnerProduct::firstOrFail();
        $this->assertSame(18000, $product->price);
        Storage::disk('local')->assertExists($product->photo_path);

        $this->actingAs($this->user())->get("/partners/products/{$product->id}/photo?thumb=1")->assertOk();

        [$photo, $thumb] = [$product->photo_path, $product->photo_thumb_path];
        $this->actingAs($m)->delete("/partners/products/{$product->id}")->assertSessionHas('success');
        Storage::disk('local')->assertMissing($photo);
        Storage::disk('local')->assertMissing($thumb);
    }

    // ---------- promosi ----------
    public function test_promotions_follow_the_draft_publish_workflow(): void
    {
        $partner = Partner::factory()->create();
        $manager = $this->manager();
        $publisher = $this->user(['content.publish']);
        $member = $this->user();
        $body = ['title' => 'Diskon 10%', 'starts_on' => today()->toDateString(), 'ends_on' => today()->addDays(5)->toDateString(), 'audience' => 'members'];

        $this->actingAs($manager)->post("/partners/{$partner->id}/promotions", $body)->assertSessionHasNoErrors();
        $promo = PartnerPromotion::firstOrFail();
        $this->assertSame('draft', $promo->status);

        $this->actingAs($member)->get("/partners/{$partner->id}")->assertInertia(fn (Assert $p) => $p->has('promotions', 0));

        $this->actingAs($manager)->post("/partners/promotions/{$promo->id}/status", ['status' => 'published'])->assertForbidden();
        $this->actingAs($publisher)->post("/partners/promotions/{$promo->id}/status", ['status' => 'published'])->assertSessionHas('success');
        $this->assertSame($publisher->id, $promo->fresh()->published_by);

        $this->actingAs($member)->get("/partners/{$partner->id}")->assertInertia(fn (Assert $p) => $p->has('promotions', 1));

        $this->actingAs($manager)->put("/partners/promotions/{$promo->id}", $body)->assertSessionHas('error');   // terbit: tarik dulu
        $this->actingAs($manager)->delete("/partners/promotions/{$promo->id}")->assertSessionHas('error');
    }

    public function test_promotion_dates_and_partner_state_are_checked(): void
    {
        $partner = Partner::factory()->create();
        $manager = $this->manager();

        $this->actingAs($manager)->post("/partners/{$partner->id}/promotions", [
            'title' => 'X', 'starts_on' => today()->addDays(3)->toDateString(), 'ends_on' => today()->toDateString(), 'audience' => 'members',
        ])->assertSessionHasErrors('ends_on');

        $promo = $this->promo($partner, ['status' => 'draft']);
        $partner->update(['status' => 'inactive']);
        $this->actingAs($this->user(['content.publish']))->post("/partners/promotions/{$promo->id}/status", ['status' => 'published'])->assertSessionHas('error');
        $this->assertSame('draft', $promo->fresh()->status);
    }

    public function test_members_only_see_current_promotions_in_the_list_and_on_the_dashboard(): void
    {
        $partner = Partner::factory()->create();
        $this->promo($partner);                                                                                  // berlaku
        $this->promo($partner, ['title' => 'Lama', 'starts_on' => today()->subDays(10), 'ends_on' => today()->subDay()]); // berakhir
        $this->promo($partner, ['title' => 'Draf', 'status' => 'draft']);                                       // draf

        $member = $this->user();
        $this->actingAs($member)->get('/partners/promotions')->assertInertia(fn (Assert $p) => $p->has('promotions.data', 1));
        $this->actingAs($this->manager())->get('/partners/promotions')->assertInertia(fn (Assert $p) => $p->has('promotions.data', 3));
        $this->actingAs($member)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('promos', 1));
    }

    // ---------- kerja sama ----------
    public function test_partnerships_are_internal_and_need_permissions(): void
    {
        $partner = Partner::factory()->create();
        $member = $this->user();

        $this->actingAs($member)->get('/partners/partnerships')->assertForbidden();
        $this->actingAs($member)->post('/partners/partnerships', ['partner_id' => $partner->id, 'title' => 'x', 'type' => 'mou'])->assertForbidden();

        $viewer = $this->user(['partners.view']);
        $this->actingAs($viewer)->get('/partners/partnerships')->assertOk();
        $this->actingAs($viewer)->post('/partners/partnerships', ['partner_id' => $partner->id, 'title' => 'x', 'type' => 'mou'])->assertForbidden();
    }

    public function test_partnership_lifecycle_rules(): void
    {
        $partner = Partner::factory()->create();
        $m = $this->manager();

        $this->actingAs($m)->post('/partners/partnerships', [
            'partner_id' => $partner->id, 'title' => 'Sponsor Bakti Sosial', 'type' => 'sponsorship', 'value' => 5000000,
            'starts_on' => today()->toDateString(), 'ends_on' => today()->addDays(10)->toDateString(),
        ])->assertSessionHasNoErrors();
        $p = Partnership::firstOrFail();
        $this->assertSame('draft', $p->status);

        $this->actingAs($m)->post("/partners/partnerships/{$p->id}/status", ['status' => 'completed'])->assertSessionHas('error');   // tidak boleh loncat
        $this->actingAs($m)->post("/partners/partnerships/{$p->id}/status", ['status' => 'active'])->assertSessionHas('success');
        $this->actingAs($m)->post("/partners/partnerships/{$p->id}/status", ['status' => 'terminated'])->assertSessionHasErrors('note');
        $this->actingAs($m)->post("/partners/partnerships/{$p->id}/status", ['status' => 'terminated', 'note' => 'Sponsor mundur'])->assertSessionHas('success');

        $this->assertSame('terminated', $p->fresh()->status);
        $this->actingAs($m)->put("/partners/partnerships/{$p->id}", ['partner_id' => $partner->id, 'title' => 'Ubah', 'type' => 'mou'])->assertSessionHas('error');
        $this->actingAs($m)->delete("/partners/partnerships/{$p->id}")->assertSessionHas('error');   // bukan draf
    }

    public function test_partnership_dates_are_validated(): void
    {
        $partner = Partner::factory()->create();

        $this->actingAs($this->manager())->post('/partners/partnerships', [
            'partner_id' => $partner->id, 'title' => 'x', 'type' => 'mou',
            'starts_on' => today()->addDays(5)->toDateString(), 'ends_on' => today()->toDateString(),
        ])->assertSessionHasErrors('ends_on');
    }

    public function test_agreement_documents_are_private(): void
    {
        $partner = Partner::factory()->create();
        $m = $this->manager();

        $this->actingAs($m)->post('/partners/partnerships', [
            'partner_id' => $partner->id, 'title' => 'MoU', 'type' => 'mou',
            'document' => UploadedFile::fake()->create('mou.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $p = Partnership::firstOrFail();
        Storage::disk('local')->assertExists($p->document_path);

        $this->actingAs($this->user())->get("/partners/partnerships/{$p->id}/document")->assertForbidden();
        $this->actingAs($m)->get("/partners/partnerships/{$p->id}/document")->assertOk();

        $this->actingAs($m)->post('/partners/partnerships', [
            'partner_id' => $partner->id, 'title' => 'Bad', 'type' => 'mou',
            'document' => UploadedFile::fake()->create('virus.exe', 5, 'application/octet-stream'),
        ])->assertSessionHasErrors('document');
    }

    public function test_expiring_partnerships_surface_on_the_dashboard(): void
    {
        $partner = Partner::factory()->create();
        Partnership::create(['partner_id' => $partner->id, 'title' => 'Segera habis', 'type' => 'sponsorship', 'status' => 'active', 'ends_on' => today()->addDays(10)]);
        Partnership::create(['partner_id' => $partner->id, 'title' => 'Masih lama', 'type' => 'sponsorship', 'status' => 'active', 'ends_on' => today()->addDays(200)]);

        $this->actingAs($this->user(['partners.view']))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('stats', fn ($stats) => collect($stats)
            ->contains(fn ($s) => $s['label'] === 'Kerja sama perlu perhatian' && $s['value'] === 1)));

        $this->actingAs($this->user())->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('stats', fn ($stats) => collect($stats)
            ->doesntContain(fn ($s) => $s['label'] === 'Kerja sama perlu perhatian')));
    }
}
