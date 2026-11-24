<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Album;
use App\Models\MediaItem;
use App\Models\User;
use App\Support\Audit;
use App\Support\VideoLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = ['q' => trim((string) $request->query('q', '')), 'status' => (string) $request->query('status', '')];

        $albums = Album::query()
            ->visibleTo($user)
            ->with('activity:id,code,title')
            ->withCount([
                'media as photos_count' => fn ($q) => $q->where('type', 'photo'),
                'media as videos_count' => fn ($q) => $q->where('type', 'video'),
            ])
            ->addSelect(['first_photo_id' => MediaItem::select('id')
                ->whereColumn('album_id', 'albums.id')->where('type', 'photo')
                ->orderBy('sort_order')->orderBy('id')->limit(1)])
            ->when($filters['q'] !== '', fn ($q) => $q->where('title', 'like', '%'.$filters['q'].'%'))
            ->when(array_key_exists($filters['status'], Album::STATUSES),
                fn ($q) => $q->where('status', $filters['status']),
                fn ($q) => $q->where('status', '!=', 'archived'))
            ->orderByDesc('event_date')->orderByDesc('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Album $a) => [
                'id'           => $a->id,
                'title'        => $a->title,
                'status'       => $a->status,
                'status_label' => $a->statusLabel(),
                'event_date'   => $a->event_date?->format('d/m/Y'),
                'activity'     => $a->activity?->title,
                'photos'       => $a->photos_count,
                'videos'       => $a->videos_count,
                'cover'        => ($a->cover_media_id ?? $a->first_photo_id) ? '/media-items/'.($a->cover_media_id ?? $a->first_photo_id).'/thumb' : null,
            ]);

        return Inertia::render('Albums/Index', [
            'albums'     => $albums,
            'filters'    => $filters,
            'statuses'   => Album::STATUSES,
            'can_create' => $user->can('gallery.manage'),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $activity = $request->integer('activity') ? Activity::find($request->integer('activity')) : null;
        abort_unless($this->mayCreate($user, $activity), 403);

        return Inertia::render('Albums/Form', [
            'album'      => null,
            'activities' => $this->activityOptions($user, $activity),
            'defaults'   => ['activity_id' => $activity?->id, 'title' => $activity ? 'Dokumentasi '.$activity->title : '', 'event_date' => $activity?->starts_at->format('Y-m-d')],
            'can_link'   => $user->can('gallery.manage') || $activity !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validated($request);
        $activity = ! empty($data['activity_id']) ? Activity::find($data['activity_id']) : null;
        abort_unless($this->mayCreate($user, $activity), 403);

        $data['event_date'] ??= $activity?->starts_at->format('Y-m-d');
        $album = Album::create($data + ['status' => 'draft', 'created_by' => $user->id]);

        return to_route('albums.show', $album)->with('success', 'Album dibuat sebagai draf. Unggah foto atau video.');
    }

    public function show(Request $request, Album $album): Response
    {
        $user = $request->user();
        abort_unless($user->can('view', $album), 403);

        $album->load(['activity:id,code,title', 'publisher:id,name']);
        $canManage = $user->can('manage', $album);

        $media = $album->media()->with('uploader:id,name')->get()->map(fn (MediaItem $m) => [
            'id'        => $m->id,
            'type'      => $m->type,
            'caption'   => $m->caption,
            'src'       => $m->file_path ? "/media-items/{$m->id}/file" : null,
            'thumb'     => $m->thumb_path ? "/media-items/{$m->id}/thumb" : null,
            'mime'      => $m->mime,
            'provider'  => $m->video_provider,
            'embed'     => $m->video_provider === 'youtube' ? VideoLink::embedUrl($m->video_ref) : null,
            'href'      => $m->video_provider === 'link' ? $m->video_ref : null,
            'uploader'  => $m->uploader?->name,
            'can_delete' => $canManage || $m->uploaded_by === $user->id,
        ]);

        return Inertia::render('Albums/Show', [
            'album' => [
                'id'           => $album->id,
                'title'        => $album->title,
                'description'  => $album->description,
                'event_date'   => $album->event_date?->format('d/m/Y'),
                'status'       => $album->status,
                'status_label' => $album->statusLabel(),
                'activity'     => $album->activity ? ['id' => $album->activity->id, 'code' => $album->activity->code, 'title' => $album->activity->title] : null,
                'published_at' => $album->published_at?->format('d/m/Y H:i'),
                'published_by' => $album->publisher?->name,
                'cover_media_id' => $album->cover_media_id,
            ],
            'media' => $media,
            'can'   => [
                'contribute' => $user->can('contribute', $album) && $album->status !== 'archived',
                'manage'     => $canManage,
                'publish'    => $user->can('publish', $album),
            ],
        ]);
    }

    public function edit(Request $request, Album $album): Response
    {
        $user = $request->user();
        abort_unless($user->can('manage', $album), 403);

        return Inertia::render('Albums/Form', [
            'album' => [
                'id' => $album->id, 'title' => $album->title, 'description' => $album->description,
                'event_date' => $album->event_date?->format('Y-m-d'), 'activity_id' => $album->activity_id,
            ],
            'activities' => $this->activityOptions($user, $album->activity),
            'defaults'   => [],
            'can_link'   => $user->can('gallery.manage'),
        ]);
    }

    public function update(Request $request, Album $album): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('manage', $album), 403);

        $data = $this->validated($request);
        if (! $user->can('gallery.manage')) {
            unset($data['activity_id']);   // hanya pengelola galeri yang boleh memindahkan album ke kegiatan lain
        }

        $album->update($data);

        return to_route('albums.show', $album)->with('success', 'Album diperbarui.');
    }

    public function destroy(Request $request, Album $album): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $album), 403);

        if ($album->status === 'published') {
            return back()->with('error', 'Tarik dari publikasi dulu sebelum menghapus album.');
        }

        $album->delete();

        return to_route('albums.index')->with('success', 'Album dihapus.');
    }

    public function status(Request $request, Album $album): RedirectResponse
    {
        $user = $request->user();
        $to = $request->validate(['status' => ['required', Rule::in(array_keys(Album::STATUSES))]])['status'];
        $from = $album->status;

        if (! in_array($to, Album::TRANSITIONS[$from] ?? [], true)) {
            return back()->with('error', 'Perubahan status tidak diperbolehkan.');
        }

        // Menerbitkan / menarik terbitan butuh izin content.publish; arsip & pulihkan cukup pengelola.
        $touchesPublication = $from === 'published' || $to === 'published';
        abort_unless($touchesPublication ? $user->can('publish', $album) : $user->can('manage', $album), 403);

        if ($to === 'published') {
            if (! $album->media()->exists()) {
                return back()->with('error', 'Tambahkan minimal satu foto atau video sebelum dipublikasikan.');
            }
            if ($album->activity && ! $album->activity->isPublic()) {
                return back()->with('error', 'Kegiatan terkait belum disetujui, album belum dapat dipublikasikan.');
            }
        }

        $album->update([
            'status'       => $to,
            'published_at' => $to === 'published' ? now() : $album->published_at,
            'published_by' => $to === 'published' ? $user->id : $album->published_by,
            'is_public'    => $to === 'published' ? $album->is_public : false,
        ]);

        return back()->with('success', 'Status album: '.Album::STATUSES[$to].'.');
    }

    public function cover(Request $request, Album $album): RedirectResponse
    {
        abort_unless($request->user()->can('manage', $album), 403);

        $data = $request->validate(['media_id' => ['required', 'integer']]);
        $media = $album->media()->where('id', $data['media_id'])->where('type', 'photo')->first();

        if (! $media) {
            return back()->with('error', 'Sampul harus berupa foto dari album ini.');
        }

        $album->update(['cover_media_id' => $media->id]);

        return back()->with('success', 'Sampul album diatur.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'event_date'  => ['nullable', 'date'],
            'activity_id' => ['nullable', 'integer', 'exists:activities,id'],
        ], ['title.required' => 'Judul album wajib diisi.']);
    }

    private function mayCreate(User $user, ?Activity $activity): bool
    {
        return $user->can('gallery.manage')
            || ($activity && ($user->can('manage', $activity) || $activity->hasCommitteeMember($user->member_id)));
    }

    private function activityOptions(User $user, ?Activity $include)
    {
        $list = Activity::visibleTo($user)->whereIn('status', Activity::PUBLIC_STATUSES)
            ->orderByDesc('starts_at')->limit(200)->get(['id', 'code', 'title', 'pic_member_id', 'status'])
            ->filter(fn (Activity $a) => $user->can('gallery.manage') || $user->can('manage', $a) || $a->hasCommitteeMember($user->member_id));

        if ($include && ! $list->contains('id', $include->id)) {
            $list->push($include);
        }

        return $list->map(fn ($a) => ['id' => $a->id, 'code' => $a->code, 'title' => $a->title])->values();
    }
}
