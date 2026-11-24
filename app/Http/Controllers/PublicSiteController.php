<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Album;
use App\Models\ArchiveDocument;
use App\Models\ContactMessage;
use App\Models\Division;
use App\Models\Member;
use App\Models\MemberPosition;
use App\Models\MediaItem;
use App\Models\NewsPost;
use App\Models\Partner;
use App\Models\Period;
use App\Models\SiteSetting;
use App\Support\VideoLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Website publik. Hanya konten yang DIPILIH EKSPLISIT (is_public) dan berstatus layak yang tampil.
 * Semua kueri publik ada di lima builder privat di bawah, supaya aturannya satu tempat.
 */
class PublicSiteController extends Controller
{
    // ---------- satu-satunya definisi "apa yang publik" ----------
    private function albums()
    {
        return Album::query()->where('status', 'published')->where('is_public', true);
    }

    private function news()
    {
        return NewsPost::query()->where('status', 'published')->where('is_public', true);
    }

    private function activities()
    {
        return Activity::query()->where('is_public', true)->whereNotNull('public_slug')->whereIn('status', Activity::PUBLIC_STATUSES);
    }

    private function documents()
    {
        return ArchiveDocument::query()->where('is_public', true);
    }

    private function partners()
    {
        return Partner::query()->where('is_public', true)->where('status', 'active');
    }

    private function page(string $component, array $props = [], string $description = ''): Response
    {
        return Inertia::render($component, $props + ['site' => SiteSetting::bundle(), 'meta' => ['description' => $description]]);
    }

    // ---------- halaman ----------
    public function home(): Response
    {
        $site = SiteSetting::bundle();

        return $this->page('Site/Home', [
            'stats' => [
                'members'    => Member::where('status', Member::ACTIVE)->count(),
                'activities' => $this->activities()->whereIn('status', ['completed', 'closed'])->count(),
                'partners'   => $this->partners()->count(),
            ],
            'news'       => $this->news()->orderByDesc('published_at')->limit(3)->get()->map(fn ($n) => $this->newsCard($n))->values(),
            'activities' => $this->activities()->where('ends_at', '>=', now())->orderBy('starts_at')->limit(3)->get()->map(fn ($a) => $this->activityCard($a))->values(),
            'albums'     => $this->albumsWithCover($this->albums()->orderByDesc('event_date')->orderByDesc('id')->limit(6)->get())->values(),
            'partners'   => $this->partners()->orderBy('name')->limit(8)->get()->map(fn ($p) => $this->partnerCard($p))->values(),
        ], $site['tagline'] ?: 'Website resmi '.$site['name']);
    }

    public function about(): Response
    {
        $s = SiteSetting::settings();

        return $this->page('Site/About', [
            'profile' => ['about' => $s['about'], 'vision' => $s['vision'], 'mission' => $s['mission'], 'history' => $s['history']],
        ], 'Profil, visi, dan misi '.($s['name'] ?: 'organisasi'));
    }

    public function structure(): Response
    {
        $period = Period::where('is_current', true)->first();
        if (! $period) {
            return $this->page('Site/Structure', ['period' => null, 'leadership' => [], 'divisions' => []], 'Struktur pengurus');
        }

        $rows = MemberPosition::with(['member:id,name,photo_path,status,public_profile', 'position', 'division'])
            ->where('period_id', $period->id)->whereNull('ended_at')->get()
            ->sortBy([fn ($a, $b) => $a->position->sort_order <=> $b->position->sort_order, fn ($a, $b) => strcmp($a->member->name, $b->member->name)]);

        // Nama dan foto hanya tampil jika anggota itu sendiri menyetujui (Profil > "Tampilkan di website publik").
        $entry = function (MemberPosition $a) {
            $consent = $a->member->public_profile && $a->member->status === Member::ACTIVE;

            return ['position' => $a->position->name, 'name' => $consent ? $a->member->name : null, 'photo' => $consent ? $a->member->photo_url : null];
        };

        return $this->page('Site/Structure', [
            'period'     => $period->name,
            'leadership' => $rows->whereNull('division_id')->map($entry)->values(),
            'divisions'  => Division::where('is_active', true)->orderBy('name')->get()->map(fn ($d) => [
                'name' => $d->name, 'entries' => $rows->where('division_id', $d->id)->map($entry)->values(),
            ])->filter(fn ($d) => $d['entries']->isNotEmpty())->values(),
        ], 'Struktur pengurus periode '.$period->name);
    }

    public function activitiesIndex(Request $request): Response
    {
        return $this->page('Site/Activities', [
            'upcoming' => $this->activities()->where('ends_at', '>=', now())->orderBy('starts_at')->limit(20)->get()->map(fn ($a) => $this->activityCard($a))->values(),
            'past'     => $this->activities()->where('ends_at', '<', now())->orderByDesc('starts_at')->paginate(9)->withQueryString()
                ->through(fn ($a) => $this->activityCard($a)),
        ], 'Kegiatan organisasi');
    }

    public function activity(string $slug): Response
    {
        $a = $this->activities()->with('division:id,name')->where('public_slug', $slug)->firstOrFail();

        return $this->page('Site/Activity', [
            'activity' => $this->activityCard($a) + [
                'summary' => $a->public_summary,   // sengaja BUKAN description internal
                'category' => $a->category,
            ],
            'albums' => $this->albumsWithCover($this->albums()->where('activity_id', $a->id)->get())->values(),
            'news'   => $this->news()->where('activity_id', $a->id)->orderByDesc('published_at')->get()->map(fn ($n) => $this->newsCard($n))->values(),
        ], str($a->public_summary)->limit(150)->toString());
    }

    public function newsIndex(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));

        return $this->page('Site/NewsList', [
            'posts' => $this->news()->when($q !== '', fn ($b) => $b->where('title', 'like', "%{$q}%"))
                ->orderByDesc('published_at')->paginate(9)->withQueryString()->through(fn ($n) => $this->newsCard($n)),
            'q' => $q,
        ], 'Berita dan kabar organisasi');
    }

    public function newsShow(string $slug): Response
    {
        $post = $this->news()->with('activity')->where('slug', $slug)->firstOrFail();
        $linked = $post->activity && $post->activity->is_public && $post->activity->public_slug && in_array($post->activity->status, Activity::PUBLIC_STATUSES, true)
            ? ['slug' => $post->activity->public_slug, 'title' => $post->activity->title] : null;

        return $this->page('Site/NewsDetail', [
            'post' => $this->newsCard($post) + ['body' => $post->body, 'cover_full' => $post->cover_path ? "/publik/berita/{$post->id}/sampul" : null, 'activity' => $linked],
        ], $post->excerpt ?: str($post->body ?? '')->limit(150)->toString());
    }

    public function gallery(): Response
    {
        $paginator = $this->albums()->orderByDesc('event_date')->orderByDesc('id')->paginate(12)->withQueryString();
        $covers = $this->albumsWithCover($paginator->getCollection())->values();

        return $this->page('Site/Gallery', ['albums' => $paginator->setCollection($covers)], 'Galeri foto dan video kegiatan');
    }

    public function album(int $album): Response
    {
        $a = $this->albums()->with('activity')->findOrFail($album);
        $media = $a->media()->get()->map(fn (MediaItem $m) => [
            'id' => $m->id, 'type' => $m->type, 'caption' => $m->caption,
            'src' => $m->file_path ? "/publik/media/{$m->id}" : null, 'thumb' => $m->thumb_path ? "/publik/media/{$m->id}?thumb=1" : null,
            'embed' => $m->video_provider === 'youtube' ? VideoLink::embedUrl($m->video_ref) : null,
            'href' => $m->video_provider === 'link' ? $m->video_ref : null,
        ]);

        return $this->page('Site/Album', [
            'album' => ['id' => $a->id, 'title' => $a->title, 'description' => $a->description, 'date' => $a->event_date?->format('d/m/Y')],
            'media' => $media,
        ], $a->description ?: 'Album '.$a->title);
    }

    public function documentsIndex(Request $request): Response
    {
        $category = (string) $request->query('category', '');

        return $this->page('Site/Documents', [
            'documents' => $this->documents()
                ->when(array_key_exists($category, ArchiveDocument::CATEGORIES), fn ($b) => $b->where('category', $category))
                ->orderByDesc('document_date')->orderByDesc('id')->paginate(20)->withQueryString()
                ->through(fn (ArchiveDocument $d) => [
                    'id' => $d->id, 'title' => $d->title, 'category' => ArchiveDocument::CATEGORIES[$d->category] ?? $d->category,
                    'number' => $d->document_number, 'date' => $d->document_date?->format('d/m/Y'), 'description' => $d->description,
                    'size' => $this->humanSize($d->size_bytes),
                ]),
            'categories' => ArchiveDocument::CATEGORIES, 'category' => $category,
        ], 'Dokumen publik organisasi');
    }

    public function download(int $document): StreamedResponse
    {
        $d = $this->documents()->findOrFail($document);
        abort_unless(Storage::disk('local')->exists($d->file_path), 404);

        return Storage::disk('local')->download($d->file_path, $d->original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function partnersIndex(Request $request): Response
    {
        $type = (string) $request->query('type', '');

        return $this->page('Site/Partners', [
            'partners' => $this->partners()->when(array_key_exists($type, Partner::TYPES), fn ($b) => $b->where('type', $type))
                ->orderBy('name')->paginate(12)->withQueryString()->through(fn ($p) => $this->partnerCard($p)),
            'types' => Partner::TYPES, 'type' => $type,
        ], 'Mitra dan UMKM binaan');
    }

    public function partner(string $slug): Response
    {
        $p = $this->partners()->where('slug', $slug)->firstOrFail();

        // Hanya field usaha. Kontak pribadi, catatan, kerja sama, dan alamat lengkap TIDAK dikirim.
        return $this->page('Site/Partner', [
            'partner' => $this->partnerCard($p) + [
                'description' => $p->description, 'owner_name' => $p->owner_name, 'website' => $p->website,
                'instagram' => $p->instagram ? ltrim($p->instagram, '@') : null, 'public_contact' => $p->public_contact,
                'logo_full' => $p->logo_path ? "/publik/mitra/{$p->id}/logo" : null,
            ],
            'products' => $p->products()->get()->map(fn ($x) => [
                'id' => $x->id, 'name' => $x->name, 'description' => $x->description, 'price' => $x->price, 'price_note' => $x->price_note,
                'available' => $x->is_available, 'photo' => $x->photo_thumb_path ? "/publik/mitra/produk/{$x->id}/foto?thumb=1" : null,
            ])->values(),
            'promotions' => $p->promotions()->current()->where('audience', 'public')->orderBy('ends_on')->get()->map(fn ($x) => [
                'id' => $x->id, 'title' => $x->title, 'description' => $x->description, 'terms' => $x->terms,
                'period' => $x->starts_on->format('d/m/Y').' - '.$x->ends_on->format('d/m/Y'),
            ])->values(),
        ], str($p->description ?? '')->limit(150)->toString());
    }

    public function contact(): Response
    {
        return $this->page('Site/Contact', [], 'Hubungi organisasi');
    }

    public function send(Request $request): RedirectResponse
    {
        // Honeypot: bot mengisi kolom tersembunyi. Pura-pura sukses, jangan simpan.
        if (filled($request->input('website'))) {
            return back()->with('success', 'Pesan Anda sudah kami terima. Terima kasih!');
        }

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['nullable', 'email', 'max:190', 'required_without:phone'],
            'phone'   => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]+$/', 'required_without:email'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'name.required'          => 'Nama wajib diisi.',
            'email.required_without' => 'Isi email atau nomor telepon agar kami bisa membalas.',
            'phone.required_without' => 'Isi email atau nomor telepon agar kami bisa membalas.',
            'phone.regex'            => 'Nomor telepon hanya boleh angka dan tanda + - ( ).',
            'message.required'       => 'Pesan wajib diisi.',
            'message.min'            => 'Pesan minimal 10 karakter.',
        ]);

        ContactMessage::create($data + ['ip' => $request->ip(), 'status' => 'new']);

        return back()->with('success', 'Pesan Anda sudah kami terima. Terima kasih!');
    }

    // ---------- pembentuk data ----------
    private function newsCard(NewsPost $n): array
    {
        return [
            'slug' => $n->slug, 'title' => $n->title, 'excerpt' => $n->excerpt, 'published_at' => $n->published_at?->format('d/m/Y'),
            'cover' => $n->cover_thumb_path ? "/publik/berita/{$n->id}/sampul?thumb=1" : null,
        ];
    }

    private function activityCard(Activity $a): array
    {
        $phase = $a->ends_at->isPast() ? 'done' : ($a->starts_at->isPast() ? 'running' : 'upcoming');
        $sameDay = $a->starts_at->isSameDay($a->ends_at);

        return [
            'slug' => $a->public_slug, 'title' => $a->title, 'location' => $a->location, 'division' => $a->division?->name, 'phase' => $phase,
            'when' => $a->starts_at->format('d/m/Y H:i').' - '.$a->ends_at->format($sameDay ? 'H:i' : 'd/m/Y H:i'),
        ];
    }

    private function partnerCard(Partner $p): array
    {
        return [
            'slug' => $p->slug, 'name' => $p->name, 'type' => $p->type, 'type_label' => $p->typeLabel(), 'sector' => $p->sector, 'city' => $p->city,
            'logo' => $p->logo_thumb_path ? "/publik/mitra/{$p->id}/logo?thumb=1" : null,
            'blurb' => str($p->description ?? '')->limit(110)->toString(),
        ];
    }

    /** Sampul = foto sampul yang dipilih, atau foto pertama. */
    private function albumsWithCover($albums)
    {
        $firstPhotos = MediaItem::whereIn('album_id', $albums->pluck('id'))->where('type', 'photo')->orderBy('sort_order')->orderBy('id')->get()->unique('album_id')->keyBy('album_id');

        return $albums->map(function (Album $a) use ($firstPhotos) {
            $coverId = $a->cover_media_id ?? $firstPhotos->get($a->id)?->id;

            return [
                'id' => $a->id, 'title' => $a->title, 'date' => $a->event_date?->format('d/m/Y'),
                'cover' => $coverId ? "/publik/media/{$coverId}?thumb=1" : null,
            ];
        });
    }

    private function humanSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1048576 => number_format($bytes / 1048576, 1).' MB',
            $bytes >= 1024    => number_format($bytes / 1024, 0).' KB',
            default           => $bytes.' B',
        };
    }
}
