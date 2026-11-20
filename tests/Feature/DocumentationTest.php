<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Album;
use App\Models\ArchiveDocument;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MediaItem;
use App\Models\NewsPost;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
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

    private function photo(string $name = 'a.jpg', int $w = 1200, int $h = 800): UploadedFile
    {
        return UploadedFile::fake()->image($name, $w, $h);
    }

    private function albumWithPhoto(User $creator, string $status = 'draft'): Album
    {
        $album = Album::factory()->create(['created_by' => $creator->id, 'status' => $status]);
        $this->actingAs($creator)->post("/albums/{$album->id}/photos", ['photos' => [$this->photo()]])->assertSessionHasNoErrors();

        return $album->fresh();
    }

    // ---------- album ----------
    public function test_standalone_albums_need_gallery_permission_but_activity_teams_can_create_their_own(): void
    {
        [$plain] = $this->memberUser();
        $this->actingAs($plain)->post('/albums', ['title' => 'Umum'])->assertForbidden();

        [$manager] = $this->memberUser(['gallery.manage']);
        $this->actingAs($manager)->post('/albums', ['title' => 'Umum'])->assertRedirect();
        $this->assertDatabaseHas('albums', ['title' => 'Umum', 'status' => 'draft']);

        [$pic, $picMember] = $this->memberUser();
        [$stranger] = $this->memberUser();
        $activity = Activity::factory()->create(['pic_member_id' => $picMember->id, 'status' => 'approved']);

        $this->actingAs($stranger)->post('/albums', ['title' => 'X', 'activity_id' => $activity->id])->assertForbidden();
        $this->actingAs($pic)->post('/albums', ['title' => 'Dokumentasi', 'activity_id' => $activity->id])->assertRedirect();
        $this->assertDatabaseHas('albums', ['title' => 'Dokumentasi', 'activity_id' => $activity->id]);
    }

    public function test_photos_are_reprocessed_resized_and_stored_privately(): void
    {
        [$user] = $this->memberUser(['gallery.manage']);
        $album = Album::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->post("/albums/{$album->id}/photos", ['photos' => [$this->photo('big.jpg', 3000, 2000)]])
            ->assertSessionHasNoErrors();

        $media = MediaItem::firstOrFail();
        $this->assertSame('photo', $media->type);
        $this->assertLessThanOrEqual(2000, $media->width);
        Storage::disk('local')->assertExists($media->file_path);
        Storage::disk('local')->assertExists($media->thumb_path);
        $this->assertStringStartsWith('albums/'.$album->id.'/', $media->file_path);
    }

    public function test_a_bad_file_is_reported_while_good_ones_are_kept(): void
    {
        [$user] = $this->memberUser(['gallery.manage']);
        $album = Album::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->post("/albums/{$album->id}/photos", [
            'photos' => [$this->photo(), UploadedFile::fake()->create('rusak.jpg', 5, 'image/jpeg')],
        ])->assertSessionHasErrors('photos');

        $this->assertSame(1, $album->media()->count());
    }

    public function test_batch_upload_writes_one_audit_entry(): void
    {
        [$user] = $this->memberUser(['gallery.manage']);
        $album = Album::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->post("/albums/{$album->id}/photos", ['photos' => [$this->photo('1.jpg'), $this->photo('2.jpg')]]);

        $this->assertSame(1, AuditLog::where('description', 'like', '2 foto diunggah%')->count());
    }

    // ---------- berkas privat & visibilitas ----------
    public function test_media_files_are_not_served_for_drafts_to_outsiders(): void
    {
        [$owner] = $this->memberUser(['gallery.manage']);
        [$stranger] = $this->memberUser();
        $album = $this->albumWithPhoto($owner);
        $media = $album->media()->first();

        $this->get("/media-items/{$media->id}/file")->assertRedirect('/login');
        $this->actingAs($stranger)->get("/media-items/{$media->id}/file")->assertForbidden();
        $this->actingAs($stranger)->get("/media-items/{$media->id}/thumb")->assertForbidden();
        $this->actingAs($owner)->get("/media-items/{$media->id}/file")->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $album->update(['status' => 'published']);
        $this->actingAs($stranger)->get("/media-items/{$media->id}/thumb")->assertOk();
    }

    public function test_members_do_not_see_draft_albums_in_the_gallery(): void
    {
        [$viewer] = $this->memberUser();
        Album::factory()->create(['status' => 'published']);
        Album::factory()->create(['status' => 'draft']);

        $this->actingAs($viewer)->get('/albums')->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Albums/Index')->has('albums.data', 1));
    }

    // ---------- publikasi ----------
    public function test_publishing_needs_permission_media_and_an_approved_activity(): void
    {
        [$creator] = $this->memberUser(['gallery.manage']);
        $album = $this->albumWithPhoto($creator);

        $this->actingAs($creator)->post("/albums/{$album->id}/status", ['status' => 'published'])->assertForbidden();

        [$publisher] = $this->memberUser(['content.publish']);
        $empty = Album::factory()->create(['created_by' => $creator->id]);
        $this->actingAs($publisher)->post("/albums/{$empty->id}/status", ['status' => 'published'])->assertSessionHas('error');

        $draftActivity = Activity::factory()->create(['status' => 'draft']);
        $album->update(['activity_id' => $draftActivity->id]);
        $this->actingAs($publisher)->post("/albums/{$album->id}/status", ['status' => 'published'])->assertSessionHas('error');
        $this->assertSame('draft', $album->fresh()->status);

        $draftActivity->update(['status' => 'approved']);
        $this->actingAs($publisher)->post("/albums/{$album->id}/status", ['status' => 'published'])->assertSessionHas('success');
        $this->assertSame('published', $album->fresh()->status);
        $this->assertSame($publisher->id, $album->fresh()->published_by);
    }

    public function test_unpublishing_also_requires_publish_permission(): void
    {
        [$creator] = $this->memberUser(['gallery.manage']);
        $album = $this->albumWithPhoto($creator, 'published');

        $this->actingAs($creator)->post("/albums/{$album->id}/status", ['status' => 'draft'])->assertForbidden();
    }

    // ---------- video ----------
    public function test_video_links_are_validated_and_youtube_is_recognised(): void
    {
        [$user] = $this->memberUser(['gallery.manage']);
        $album = Album::factory()->create(['created_by' => $user->id]);
        $url = "/albums/{$album->id}/videos";

        $this->actingAs($user)->post($url, ['video_url' => 'javascript:alert(1)'])->assertSessionHasErrors('video_url');
        $this->actingAs($user)->post($url, ['video_url' => 'http://contoh.com/v.mp4'])->assertSessionHasErrors('video_url');
        $this->actingAs($user)->post($url, [])->assertSessionHasErrors();

        $this->actingAs($user)->post($url, ['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post($url, ['video_url' => 'https://contoh.com/video'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('media_items', ['type' => 'video', 'video_provider' => 'youtube', 'video_ref' => 'dQw4w9WgXcQ']);
        $this->assertDatabaseHas('media_items', ['type' => 'video', 'video_provider' => 'link', 'video_ref' => 'https://contoh.com/video']);
    }

    public function test_deleting_media_removes_files_and_clears_cover(): void
    {
        [$user] = $this->memberUser(['gallery.manage']);
        $album = $this->albumWithPhoto($user);
        $media = $album->media()->first();
        $album->update(['cover_media_id' => $media->id]);
        [$file, $thumb] = [$media->file_path, $media->thumb_path];

        $this->actingAs($user)->delete("/media-items/{$media->id}")->assertSessionHas('success');

        Storage::disk('local')->assertMissing($file);
        Storage::disk('local')->assertMissing($thumb);
        $this->assertNull($album->fresh()->cover_media_id);
    }

    // ---------- berita ----------
    public function test_news_lifecycle_and_slugs(): void
    {
        [$plain] = $this->memberUser();
        $this->actingAs($plain)->post('/news', ['title' => 'Halo'])->assertForbidden();

        [$writer] = $this->memberUser(['news.manage']);
        $this->actingAs($writer)->post('/news', ['title' => 'Bakti Sosial', 'body' => 'Isi berita RAHASIA-AUDIT'])->assertRedirect();
        $this->actingAs($writer)->post('/news', ['title' => 'Bakti Sosial', 'body' => 'Lain'])->assertRedirect();

        $this->assertSame(['bakti-sosial', 'bakti-sosial-2'], NewsPost::orderBy('id')->pluck('slug')->all());
        $this->assertStringNotContainsString('RAHASIA-AUDIT', AuditLog::all()->toJson());

        $post = NewsPost::orderBy('id')->first();
        [$viewer] = $this->memberUser();
        $this->actingAs($viewer)->get("/news/{$post->id}")->assertForbidden();

        $this->actingAs($writer)->post("/news/{$post->id}/status", ['status' => 'published'])->assertForbidden();

        [$publisher] = $this->memberUser(['content.publish']);
        $this->actingAs($publisher)->post("/news/{$post->id}/status", ['status' => 'published'])->assertSessionHas('success');
        $this->actingAs($viewer)->get("/news/{$post->id}")->assertOk();
    }

    public function test_news_cannot_be_published_without_body(): void
    {
        [$publisher] = $this->memberUser(['content.publish']);
        $post = NewsPost::factory()->create(['body' => '']);

        $this->actingAs($publisher)->post("/news/{$post->id}/status", ['status' => 'published'])->assertSessionHas('error');
        $this->assertSame('draft', $post->fresh()->status);
    }

    public function test_news_cover_is_processed_and_published_news_shows_on_dashboard(): void
    {
        [$writer] = $this->memberUser(['news.manage']);
        $this->actingAs($writer)->post('/news', ['title' => 'Dengan sampul', 'body' => 'Isi', 'cover' => $this->photo('c.jpg')])
            ->assertSessionHasNoErrors();

        $post = NewsPost::firstOrFail();
        Storage::disk('local')->assertExists($post->cover_path);
        Storage::disk('local')->assertExists($post->cover_thumb_path);

        $post->update(['status' => 'published', 'published_at' => now()]);
        [$viewer] = $this->memberUser();
        $this->actingAs($viewer)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('news', 1));
    }

    // ---------- arsip ----------
    public function test_archive_upload_requires_permission_and_safe_file_types(): void
    {
        [$plain] = $this->memberUser();
        $this->actingAs($plain)->get('/archive/create')->assertForbidden();

        [$clerk] = $this->memberUser(['archive.manage']);
        $base = ['title' => 'Notulen Januari', 'category' => 'notulen', 'access' => 'members'];

        $this->actingAs($clerk)->post('/archive', $base + ['file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream')])
            ->assertSessionHasErrors('file');
        $this->actingAs($clerk)->post('/archive', $base + ['file' => UploadedFile::fake()->create('notulen.pdf', 100, 'application/pdf')])
            ->assertSessionHasNoErrors();

        $doc = ArchiveDocument::firstOrFail();
        Storage::disk('local')->assertExists($doc->file_path);
        $this->assertSame('notulen.pdf', $doc->original_name);
    }

    public function test_restricted_documents_are_limited_to_authorized_users(): void
    {
        [$clerk] = $this->memberUser(['archive.manage']);
        $file = fn () => UploadedFile::fake()->create('rahasia.pdf', 50, 'application/pdf');

        $this->actingAs($clerk)->post('/archive', ['title' => 'Terbatas', 'category' => 'sk', 'access' => 'restricted', 'file' => $file()]);
        $this->actingAs($clerk)->post('/archive', ['title' => 'Terbuka', 'category' => 'sk', 'access' => 'members', 'file' => $file()]);
        $restricted = ArchiveDocument::where('title', 'Terbatas')->firstOrFail();
        $open = ArchiveDocument::where('title', 'Terbuka')->firstOrFail();

        [$plain] = $this->memberUser();
        $this->actingAs($plain)->get("/archive/{$restricted->id}/download")->assertForbidden();
        $this->actingAs($plain)->get("/archive/{$open->id}/download")->assertOk();
        $this->actingAs($plain)->get('/archive')->assertInertia(fn (Assert $p) => $p->has('documents.data', 1));

        [$viewer] = $this->memberUser(['archive.view']);
        $this->actingAs($viewer)->get("/archive/{$restricted->id}/download")->assertOk();
    }
}
