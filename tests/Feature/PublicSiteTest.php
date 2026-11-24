<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Album;
use App\Models\ArchiveDocument;
use App\Models\ContactMessage;
use App\Models\Division;
use App\Models\Member;
use App\Models\MemberPosition;
use App\Models\NewsPost;
use App\Models\Partner;
use App\Models\PartnerPromotion;
use App\Models\Period;
use App\Models\Position;
use App\Models\User;
use App\Support\ImageProcessor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSiteTest extends TestCase
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

    private function publisher(): User
    {
        return $this->user(['content.publish']);
    }

    private function album(array $attrs = []): Album
    {
        $album = Album::factory()->create($attrs + ['status' => 'published', 'event_date' => today()]);
        $r = ImageProcessor::store(UploadedFile::fake()->image('a.jpg', 800, 600), "albums/{$album->id}");
        $album->media()->create(['type' => 'photo', 'file_path' => $r['path'], 'thumb_path' => $r['thumb'], 'mime' => 'image/jpeg', 'size_bytes' => $r['size'], 'sort_order' => 1]);

        return $album;
    }

    private function activity(array $attrs = []): Activity
    {
        return Activity::factory()->create($attrs + [
            'status' => 'approved', 'public_summary' => 'Ringkasan publik kegiatan bakti sosial di kampung.',
            'public_slug' => 'bakti-sosial-kgt-9001', 'description' => 'RAHASIA-INTERNAL anggaran dan catatan pengurus',
        ]);
    }

    private function toggle(User $by, string $type, int $id, bool $public, array $extra = [])
    {
        return $this->actingAs($by)->post("/site/content/{$type}/{$id}", ['public' => $public] + $extra);
    }

    // ---------- beranda & bawaan tertutup ----------
    public function test_home_is_public_and_empty_by_default(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('Site/Home')->where('site.name', 'Arek Wetan Organizer')
            ->has('news', 0)->has('activities', 0)->has('albums', 0)->has('partners', 0));

        $this->actingAs($this->user())->get('/')->assertOk();   // anggota login juga melihat beranda publik, tanpa dialihkan
    }

    public function test_nothing_is_public_until_it_is_curated(): void
    {
        $album = $this->album();
        $news = NewsPost::factory()->create(['status' => 'published']);
        $activity = $this->activity();
        $doc = ArchiveDocument::factory()->create();
        Storage::disk('local')->put($doc->file_path, 'x');
        $partner = Partner::factory()->create();

        $this->get('/galeri')->assertInertia(fn (Assert $p) => $p->has('albums.data', 0));
        $this->get("/galeri/{$album->id}")->assertNotFound();
        $this->get("/berita/{$news->slug}")->assertNotFound();
        $this->get("/kegiatan/{$activity->public_slug}")->assertNotFound();
        $this->get("/dokumen/{$doc->id}/unduh")->assertNotFound();
        $this->get("/mitra/{$partner->slug}")->assertNotFound();
        $this->get('/publik/media/'.$album->media()->first()->id)->assertNotFound();
    }

    // ---------- kurasi ----------
    public function test_curation_needs_the_publish_permission(): void
    {
        $album = Album::factory()->create(['status' => 'published']);
        $plain = $this->user();

        $this->actingAs($plain)->get('/site/content')->assertForbidden();
        $this->toggle($plain, 'albums', $album->id, true)->assertForbidden();
        $this->actingAs($this->user(['gallery.manage', 'news.manage']))->get('/site/content')->assertForbidden();   // menulis bukan menerbitkan

        $this->actingAs($this->publisher())->get('/site/content?type=albums')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('SiteAdmin/Content')->has('items.data', 1));
        $this->actingAs($this->publisher())->get('/site/content?type=bukan-tipe')->assertNotFound();
    }

    public function test_curation_eligibility_rules(): void
    {
        $pub = $this->publisher();

        $draftAlbum = Album::factory()->create(['status' => 'draft']);
        $this->toggle($pub, 'albums', $draftAlbum->id, true)->assertSessionHas('error');
        $this->assertFalse($draftAlbum->fresh()->is_public);

        $album = Album::factory()->create(['status' => 'published']);
        $this->toggle($pub, 'albums', $album->id, true)->assertSessionHas('success');
        $this->assertTrue($album->fresh()->is_public);

        $draftNews = NewsPost::factory()->create(['status' => 'draft']);
        $this->toggle($pub, 'news', $draftNews->id, true)->assertSessionHas('error');

        $inactive = Partner::factory()->create(['status' => 'inactive']);
        $this->toggle($pub, 'partners', $inactive->id, true)->assertSessionHas('error');
        $active = Partner::factory()->create();
        $this->toggle($pub, 'partners', $active->id, true)->assertSessionHas('success');

        $unapproved = Activity::factory()->create(['status' => 'draft']);
        $this->toggle($pub, 'activities', $unapproved->id, true, ['summary' => str_repeat('ringkasan ', 5)])->assertSessionHas('error');

        $approved = Activity::factory()->create(['status' => 'approved', 'code' => 'KGT-2026-501', 'title' => 'Bakti Sosial']);
        $this->toggle($pub, 'activities', $approved->id, true)->assertSessionHas('error');                       // tanpa ringkasan
        $this->toggle($pub, 'activities', $approved->id, true, ['summary' => 'terlalu pendek'])->assertSessionHas('error');
        $this->assertFalse($approved->fresh()->is_public);

        $this->toggle($pub, 'activities', $approved->id, true, ['summary' => 'Bakti sosial membersihkan sungai bersama warga setempat.'])->assertSessionHas('success');
        $approved->refresh();
        $this->assertTrue($approved->is_public);
        $this->assertSame('bakti-sosial-kgt-2026-501', $approved->public_slug);
    }

    // ---------- tampil setelah dikurasi ----------
    public function test_curated_content_appears_on_public_pages_for_guests(): void
    {
        $pub = $this->publisher();
        $album = $this->album(['title' => 'Bakti Sosial 2026']);
        $news = NewsPost::factory()->create(['status' => 'published', 'published_at' => now(), 'title' => 'Kabar Gembira']);
        $activity = $this->activity();
        $doc = ArchiveDocument::factory()->create(['title' => 'AD ART']);
        Storage::disk('local')->put($doc->file_path, 'isi dokumen');
        $partner = Partner::factory()->create(['name' => 'Kopi Wetan', 'slug' => 'kopi-wetan']);

        foreach ([['albums', $album->id], ['news', $news->id], ['activities', $activity->id], ['documents', $doc->id], ['partners', $partner->id]] as [$type, $id]) {
            $this->toggle($pub, $type, $id, true)->assertSessionHas('success');
        }
        auth()->logout();

        $this->get('/galeri')->assertInertia(fn (Assert $p) => $p->has('albums.data', 1)->where('albums.data.0.title', 'Bakti Sosial 2026'));
        $this->get("/galeri/{$album->id}")->assertOk()->assertInertia(fn (Assert $p) => $p->component('Site/Album')->has('media', 1));
        $this->get('/berita')->assertInertia(fn (Assert $p) => $p->has('posts.data', 1));
        $this->get("/berita/{$news->slug}")->assertOk();
        $this->get('/kegiatan')->assertOk();
        $this->get('/kegiatan/bakti-sosial-kgt-9001')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Site/Activity'));
        $this->get('/dokumen')->assertInertia(fn (Assert $p) => $p->has('documents.data', 1));
        $this->get("/dokumen/{$doc->id}/unduh")->assertOk();
        $this->get('/mitra')->assertInertia(fn (Assert $p) => $p->has('partners.data', 1));
        $this->get('/mitra/kopi-wetan')->assertOk();
        $this->get('/')->assertInertia(fn (Assert $p) => $p->has('news', 1)->has('albums', 1)->has('partners', 1));
    }

    public function test_public_activity_never_exposes_the_internal_description(): void
    {
        $activity = $this->activity();
        $this->toggle($this->publisher(), 'activities', $activity->id, true)->assertSessionHas('success');
        auth()->logout();

        $response = $this->get('/kegiatan/bakti-sosial-kgt-9001')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->missing('activity.description')->where('activity.summary', 'Ringkasan publik kegiatan bakti sosial di kampung.'));
        $this->assertStringNotContainsString('RAHASIA-INTERNAL', $response->getContent());
    }

    public function test_public_partner_page_hides_internal_fields_and_non_public_promotions(): void
    {
        $partner = Partner::factory()->create([
            'slug' => 'kopi-wetan', 'public_contact' => '@kopiwetan', 'contact_person' => 'Bu Rahasia', 'contact_phone' => '0811999888',
            'contact_email' => 'rahasia@mitra.id', 'notes' => 'catatan-internal', 'address' => 'Jl. Pribadi 12',
        ]);
        $promo = fn (array $a) => PartnerPromotion::create($a + ['partner_id' => $partner->id, 'title' => 'Promo', 'status' => 'published', 'audience' => 'public', 'starts_on' => today(), 'ends_on' => today()->addDays(3)]);
        $promo(['title' => 'Promo Umum']);
        $promo(['title' => 'Promo Anggota', 'audience' => 'members']);
        $promo(['title' => 'Promo Draf', 'status' => 'draft']);
        $promo(['title' => 'Promo Lama', 'starts_on' => today()->subDays(9), 'ends_on' => today()->subDay()]);
        $this->toggle($this->publisher(), 'partners', $partner->id, true)->assertSessionHas('success');
        auth()->logout();

        $response = $this->get('/mitra/kopi-wetan')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('partner.public_contact', '@kopiwetan')
            ->missing('partner.contact_person')->missing('partner.contact_phone')->missing('partner.contact_email')->missing('partner.notes')->missing('partner.address')
            ->has('promotions', 1)->where('promotions.0.title', 'Promo Umum'));

        foreach (['Bu Rahasia', '0811999888', 'rahasia@mitra.id', 'catatan-internal', 'Jl. Pribadi 12', 'Promo Anggota', 'Promo Draf'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
    }

    // ---------- berkas publik ----------
    public function test_public_media_requires_the_album_to_be_public_and_is_cached(): void
    {
        $album = $this->album();
        $media = $album->media()->first();
        $this->get("/publik/media/{$media->id}")->assertNotFound();

        $this->toggle($this->publisher(), 'albums', $album->id, true);
        auth()->logout();

        $this->get("/publik/media/{$media->id}")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get("/publik/media/{$media->id}?thumb=1")->assertOk();
        $this->assertStringContainsString('public', $this->get("/publik/media/{$media->id}")->headers->get('Cache-Control'));

        $link = $album->media()->create(['type' => 'video', 'video_provider' => 'link', 'video_ref' => 'https://contoh.com/v', 'sort_order' => 2]);
        $this->get("/publik/media/{$link->id}")->assertNotFound();   // video tautan tidak punya berkas
    }

    public function test_leaving_the_published_state_withdraws_content_from_public(): void
    {
        $pub = $this->user(['content.publish']);
        $album = $this->album(['is_public' => true]);
        $news = NewsPost::factory()->create(['status' => 'published', 'is_public' => true, 'published_at' => now()]);
        $media = $album->media()->first();

        $this->actingAs($pub)->post("/albums/{$album->id}/status", ['status' => 'draft'])->assertSessionHas('success');
        $this->actingAs($pub)->post("/news/{$news->id}/status", ['status' => 'draft'])->assertSessionHas('success');

        $this->assertFalse($album->fresh()->is_public);
        $this->assertFalse($news->fresh()->is_public);
        auth()->logout();
        $this->get("/galeri/{$album->id}")->assertNotFound();
        $this->get("/berita/{$news->slug}")->assertNotFound();
        $this->get("/publik/media/{$media->id}")->assertNotFound();

        // diterbitkan ulang TIDAK otomatis publik lagi
        $this->actingAs($pub)->post("/albums/{$album->id}/status", ['status' => 'published']);
        $this->assertFalse($album->fresh()->is_public);
    }

    public function test_cancelling_an_activity_withdraws_it_and_deactivating_a_partner_hides_it(): void
    {
        $activity = $this->activity(['is_public' => true]);
        $manager = $this->user(['activities.update']);
        $this->actingAs($manager)->post("/activities/{$activity->id}/cancel", ['reason' => 'Hujan deras'])->assertSessionHas('success');
        $this->assertFalse($activity->fresh()->is_public);
        auth()->logout();
        $this->get("/kegiatan/{$activity->public_slug}")->assertNotFound();

        $partner = Partner::factory()->create(['is_public' => true, 'slug' => 'mitra-x']);
        $this->get('/mitra/mitra-x')->assertOk();
        $this->actingAs($this->user(['partners.manage']))->post("/partners/{$partner->id}/status", ['status' => 'inactive']);
        auth()->logout();
        $this->get('/mitra/mitra-x')->assertNotFound();
    }

    // ---------- struktur & persetujuan anggota ----------
    public function test_structure_only_names_members_who_consented(): void
    {
        $period = Period::create(['name' => '2026/2027', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_current' => true]);
        $ketua = Position::create(['name' => 'Ketua', 'scope' => 'organization', 'sort_order' => 1, 'is_active' => true]);
        $sekre = Position::create(['name' => 'Sekretaris', 'scope' => 'organization', 'sort_order' => 2, 'is_active' => true]);
        $head = Position::create(['name' => 'Ketua Divisi', 'scope' => 'division', 'sort_order' => 3, 'is_active' => true]);
        $division = Division::create(['name' => 'Humas', 'is_active' => true]);

        $agree = Member::factory()->create(['name' => 'Siti Bersedia', 'public_profile' => true]);
        $refuse = Member::factory()->create(['name' => 'Budi Tertutup', 'public_profile' => false]);
        $inactive = Member::factory()->create(['name' => 'Eko Nonaktif', 'public_profile' => true, 'status' => 'inactive']);
        foreach ([[$agree, $ketua, null], [$refuse, $sekre, null], [$inactive, $head, $division->id]] as [$m, $pos, $div]) {
            MemberPosition::create(['member_id' => $m->id, 'period_id' => $period->id, 'position_id' => $pos->id, 'division_id' => $div, 'started_at' => '2026-01-01']);
        }

        $response = $this->get('/struktur-pengurus')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('period', '2026/2027')
            ->where('leadership.0.name', 'Siti Bersedia')
            ->where('leadership.1.position', 'Sekretaris')->where('leadership.1.name', null)
            ->where('divisions.0.entries.0.name', null));

        $this->assertStringNotContainsString('Budi Tertutup', $response->getContent());
        $this->assertStringNotContainsString('Eko Nonaktif', $response->getContent());
    }

    public function test_members_control_their_own_public_consent(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/profile')->assertInertia(fn (Assert $p) => $p->where('public_profile', false));
        $this->actingAs($user)->put('/profile/public', ['public_profile' => true])->assertSessionHas('success');
        $this->assertTrue($user->member->fresh()->public_profile);
        $this->actingAs($user)->put('/profile/public', ['public_profile' => false]);
        $this->assertFalse($user->member->fresh()->public_profile);

        $loner = User::factory()->create(['member_id' => null]);
        $this->actingAs($loner)->put('/profile/public', ['public_profile' => true])->assertForbidden();
    }

    // ---------- dokumen ----------
    public function test_only_public_documents_can_be_downloaded(): void
    {
        $open = ArchiveDocument::factory()->create(['is_public' => true, 'title' => 'Terbuka']);
        $closed = ArchiveDocument::factory()->create(['is_public' => false, 'access' => 'members']);
        $open->update(['file_path' => 'archive/open.pdf']);
        Storage::disk('local')->put('archive/open.pdf', 'isi');

        $this->get("/dokumen/{$open->id}/unduh")->assertOk();
        $this->get("/dokumen/{$closed->id}/unduh")->assertNotFound();
        $this->get('/dokumen')->assertInertia(fn (Assert $p) => $p->has('documents.data', 1)->where('documents.data.0.title', 'Terbuka'));
    }

    // ---------- kontak ----------
    public function test_contact_form_stores_messages_and_validates(): void
    {
        $this->post('/kontak', ['name' => 'Rina', 'email' => 'rina@contoh.id', 'message' => 'Halo, saya ingin bergabung sebagai relawan.'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contact_messages', ['name' => 'Rina', 'status' => 'new']);

        $this->post('/kontak', ['name' => 'Tanpa Kontak', 'message' => 'Pesan tanpa email maupun telepon.'])->assertSessionHasErrors(['email', 'phone']);
        $this->post('/kontak', ['name' => 'Pendek', 'email' => 'a@b.id', 'message' => 'pendek'])->assertSessionHasErrors('message');
        $this->post('/kontak', ['name' => 'X', 'phone' => 'abc', 'message' => 'Pesan yang cukup panjang untuk lolos.'])->assertSessionHasErrors('phone');
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_contact_honeypot_discards_bot_submissions_silently(): void
    {
        $this->post('/kontak', ['name' => 'Bot', 'email' => 'bot@spam.id', 'message' => 'Beli obat murah sekarang juga ya.', 'website' => 'http://spam.id'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_contact_form_is_rate_limited(): void
    {
        $body = ['name' => 'Rina', 'email' => 'rina@contoh.id', 'message' => 'Pesan percobaan yang cukup panjang.'];
        for ($i = 0; $i < 5; $i++) {
            $this->post('/kontak', $body)->assertSessionHasNoErrors();
        }

        $this->post('/kontak', $body)->assertStatus(429);
        $this->assertSame(5, ContactMessage::count());
    }

    // ---------- pengaturan & pesan masuk ----------
    public function test_site_settings_need_permission_and_validate_links(): void
    {
        $this->actingAs($this->user())->get('/site/settings')->assertForbidden();

        $admin = $this->user(['site.manage']);
        $base = ['name' => 'AWO Surabaya', 'tagline' => 'Pemuda Wetan Bergerak'];

        $this->actingAs($admin)->put('/site/settings', $base + ['facebook' => 'javascript:alert(1)'])->assertSessionHasErrors('facebook');
        $this->actingAs($admin)->put('/site/settings', $base + ['instagram' => 'nama salah!'])->assertSessionHasErrors('instagram');
        $this->actingAs($admin)->put('/site/settings', array_merge($base, ['name' => '']))->assertSessionHasErrors('name');

        $this->actingAs($admin)->put('/site/settings', $base + ['whatsapp' => '0812-3456-7890', 'instagram' => '@awo.sby', 'email' => 'halo@awo.id'])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get('/kontak')->assertInertia(fn (Assert $p) => $p
            ->where('site.name', 'AWO Surabaya')->where('site.whatsapp_url', 'https://wa.me/6281234567890')->where('site.instagram', 'awo.sby'));
    }

    public function test_logo_is_processed_and_served_publicly(): void
    {
        $admin = $this->user(['site.manage']);
        $this->get('/publik/situs/logo')->assertNotFound();

        $this->actingAs($admin)->put('/site/settings', ['name' => 'AWO', 'logo' => UploadedFile::fake()->image('logo.png', 600, 600)])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/publik/situs/logo')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get('/publik/situs/rahasia')->assertNotFound();

        $this->actingAs($admin)->put('/site/settings', ['name' => 'AWO', 'remove_logo' => true]);
        auth()->logout();
        $this->get('/publik/situs/logo')->assertNotFound();
    }

    public function test_message_inbox_is_restricted_and_manageable(): void
    {
        $m = ContactMessage::create(['name' => 'Rina', 'email' => 'r@x.id', 'message' => 'Halo semuanya, apa kabar?', 'status' => 'new']);

        $this->actingAs($this->user())->get('/site/messages')->assertForbidden();

        $admin = $this->user(['site.manage']);
        $this->actingAs($admin)->get('/site/messages')->assertInertia(fn (Assert $p) => $p->has('items.data', 1));
        $this->actingAs($admin)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('stats', fn ($s) => collect($s)
            ->contains(fn ($x) => $x['label'] === 'Pesan website belum ditangani' && $x['value'] === 1)));

        $this->actingAs($admin)->post("/site/messages/{$m->id}/handle")->assertSessionHas('success');
        $this->assertSame('handled', $m->fresh()->status);
        $this->assertSame($admin->id, $m->fresh()->handled_by);

        $this->actingAs($admin)->delete("/site/messages/{$m->id}")->assertSessionHas('success');
        $this->assertSame(0, ContactMessage::count());
    }
}
