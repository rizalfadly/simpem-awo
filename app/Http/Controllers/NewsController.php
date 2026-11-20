<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Album;
use App\Models\NewsPost;
use App\Support\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = ['q' => trim((string) $request->query('q', '')), 'status' => (string) $request->query('status', '')];

        $posts = NewsPost::query()
            ->visibleTo($user)
            ->with('activity:id,title')
            ->when($filters['q'] !== '', fn ($q) => $q->where('title', 'like', '%'.$filters['q'].'%'))
            ->when(array_key_exists($filters['status'], NewsPost::STATUSES),
                fn ($q) => $q->where('status', $filters['status']),
                fn ($q) => $q->where('status', '!=', 'archived'))
            ->orderByRaw('published_at is null desc')   // draf di atas bagi pengelola
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (NewsPost $p) => [
                'id'           => $p->id,
                'title'        => $p->title,
                'excerpt'      => $p->excerpt,
                'status'       => $p->status,
                'status_label' => $p->statusLabel(),
                'published_at' => $p->published_at?->format('d/m/Y'),
                'activity'     => $p->activity?->title,
                'cover'        => $p->cover_thumb_path ? "/news/{$p->id}/cover?thumb=1" : null,
            ]);

        return Inertia::render('News/Index', [
            'posts'      => $posts,
            'filters'    => $filters,
            'statuses'   => NewsPost::STATUSES,
            'can_create' => $user->can('news.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('News/Form', [
            'post'       => null,
            'activities' => $this->activityOptions($request),
            'defaults'   => ['activity_id' => $request->integer('activity') ?: null],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $cover = $this->processCover($request);

        $post = NewsPost::create($data + $cover + [
            'slug'       => NewsPost::uniqueSlug($data['title']),
            'status'     => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return to_route('news.show', $post)->with('success', 'Berita disimpan sebagai draf.');
    }

    public function show(Request $request, NewsPost $post): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $post), 403);

        $post->load(['activity:id,code,title', 'creator:id,name']);

        return Inertia::render('News/Show', [
            'post' => [
                'id'           => $post->id,
                'title'        => $post->title,
                'excerpt'      => $post->excerpt,
                'body'         => $post->body,
                'status'       => $post->status,
                'status_label' => $post->statusLabel(),
                'author'       => $post->creator?->name,
                'published_at' => $post->published_at?->format('d/m/Y H:i'),
                'cover'        => $post->cover_path ? "/news/{$post->id}/cover" : null,
                'activity'     => $post->activity ? ['id' => $post->activity->id, 'title' => $post->activity->title] : null,
            ],
            'can' => ['manage' => $user->can('manage', $post), 'publish' => $user->can('publish', $post)],
        ]);
    }

    public function edit(Request $request, NewsPost $post): Response
    {
        abort_unless($request->user()->can('manage', $post), 403);

        return Inertia::render('News/Form', [
            'post' => [
                'id' => $post->id, 'title' => $post->title, 'excerpt' => $post->excerpt, 'body' => $post->body,
                'activity_id' => $post->activity_id, 'has_cover' => (bool) $post->cover_path,
            ],
            'activities' => $this->activityOptions($request),
            'defaults'   => [],
        ]);
    }

    public function update(Request $request, NewsPost $post): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $post), 403);

        $data = $this->validated($request);
        $extra = [];

        if ($request->hasFile('cover')) {
            $this->deleteCover($post);
            $extra = $this->processCover($request);
        } elseif ($request->boolean('remove_cover')) {
            $this->deleteCover($post);
            $extra = ['cover_path' => null, 'cover_thumb_path' => null];
        }

        $post->update($data + $extra);

        return to_route('news.show', $post)->with('success', 'Berita diperbarui.');
    }

    public function destroy(Request $request, NewsPost $post): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $post), 403);

        if ($post->status === 'published') {
            return back()->with('error', 'Tarik dari publikasi dulu sebelum menghapus berita.');
        }

        $post->delete();

        return to_route('news.index')->with('success', 'Berita dihapus.');
    }

    public function status(Request $request, NewsPost $post): RedirectResponse
    {
        $user = $request->user();
        $to = $request->validate(['status' => ['required', Rule::in(array_keys(NewsPost::STATUSES))]])['status'];
        $from = $post->status;

        if (! in_array($to, Album::TRANSITIONS[$from] ?? [], true)) {
            return back()->with('error', 'Perubahan status tidak diperbolehkan.');
        }

        $touchesPublication = $from === 'published' || $to === 'published';
        abort_unless($touchesPublication ? $user->can('publish', $post) : $user->can('manage', $post), 403);

        if ($to === 'published') {
            if (blank($post->body)) {
                return back()->with('error', 'Isi berita masih kosong.');
            }
            if ($post->activity && ! $post->activity->isPublic()) {
                return back()->with('error', 'Kegiatan terkait belum disetujui, berita belum dapat dipublikasikan.');
            }
        }

        $post->update([
            'status'       => $to,
            'published_at' => $to === 'published' ? now() : $post->published_at,
            'published_by' => $to === 'published' ? $user->id : $post->published_by,
        ]);

        return back()->with('success', 'Status berita: '.NewsPost::STATUSES[$to].'.');
    }

    public function cover(Request $request, NewsPost $post): BinaryFileResponse
    {
        abort_unless($request->user()->can('view', $post), 403);

        $path = $request->boolean('thumb') ? $post->cover_thumb_path : $post->cover_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=86400', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:150'],
            'excerpt'      => ['nullable', 'string', 'max:300'],
            'body'         => ['nullable', 'string', 'max:20000'],
            'activity_id'  => ['nullable', 'integer', 'exists:activities,id'],
            'cover'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_cover' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Judul berita wajib diisi.',
            'cover.image'    => 'Sampul harus berupa gambar.',
            'cover.mimes'    => 'Sampul harus berformat JPG, PNG, atau WEBP.',
            'cover.max'      => 'Ukuran sampul maksimal 8 MB.',
        ]);

        // 'cover' (berkas) dan 'remove_cover' bukan kolom tabel; diproses terpisah.
        unset($data['cover'], $data['remove_cover']);

        return $data;
    }

    /** @return array{cover_path?:string, cover_thumb_path?:string} */
    private function processCover(Request $request): array
    {
        if (! $request->hasFile('cover')) {
            return [];
        }

        try {
            $r = ImageProcessor::store($request->file('cover'), 'news');
        } catch (\Throwable) {
            throw \Illuminate\Validation\ValidationException::withMessages(['cover' => 'Gambar sampul tidak dapat diproses.']);
        }

        return ['cover_path' => $r['path'], 'cover_thumb_path' => $r['thumb']];
    }

    private function deleteCover(NewsPost $post): void
    {
        foreach ([$post->cover_path, $post->cover_thumb_path] as $path) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
        }
    }

    private function activityOptions(Request $request)
    {
        return Activity::visibleTo($request->user())->whereIn('status', Activity::PUBLIC_STATUSES)
            ->orderByDesc('starts_at')->limit(200)->get(['id', 'code', 'title']);
    }
}
