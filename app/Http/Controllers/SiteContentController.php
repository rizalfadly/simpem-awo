<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Album;
use App\Models\ArchiveDocument;
use App\Models\NewsPost;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kurasi konten publik. Satu halaman untuk memilih, secara sadar, apa yang tampil di website.
 * Semua konten default tertutup. Menjadikan publik butuh izin content.publish.
 */
class SiteContentController extends Controller
{
    private const TYPES = [
        'albums'     => 'Album',
        'news'       => 'Berita',
        'activities' => 'Kegiatan',
        'documents'  => 'Dokumen',
        'partners'   => 'Mitra & UMKM',
    ];

    public function index(Request $request): Response
    {
        $type = (string) $request->query('type', 'albums');
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $items = match ($type) {
            'albums' => Album::orderByDesc('id')->paginate(20)->through(fn (Album $a) => $this->row($a->id, $a->title, $a->statusLabel(), $a->is_public, $a->status === 'published', 'Terbitkan dulu di menu Galeri.')),
            'news' => NewsPost::orderByDesc('id')->paginate(20)->through(fn (NewsPost $n) => $this->row($n->id, $n->title, $n->statusLabel(), $n->is_public, $n->status === 'published', 'Terbitkan dulu di menu Berita.')),
            'activities' => Activity::whereIn('status', Activity::PUBLIC_STATUSES)->orderByDesc('starts_at')->paginate(20)->through(fn (Activity $a) => $this->row(
                $a->id, $a->title, $a->statusLabel(), $a->is_public, true, null, ['summary' => $a->public_summary ?? '', 'is_activity' => true])),
            'documents' => ArchiveDocument::orderByDesc('id')->paginate(20)->through(fn (ArchiveDocument $d) => $this->row(
                $d->id, $d->title, ArchiveDocument::CATEGORIES[$d->category] ?? $d->category, $d->is_public, true, null)),
            'partners' => Partner::orderBy('name')->paginate(20)->through(fn (Partner $p) => $this->row($p->id, $p->name, Partner::STATUSES[$p->status] ?? $p->status, $p->is_public, $p->status === 'active', 'Mitra harus berstatus Aktif.')),
        };

        return Inertia::render('SiteAdmin/Content', ['type' => $type, 'types' => self::TYPES, 'items' => $items]);
    }

    public function toggle(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $data = $request->validate([
            'public'  => ['required', 'boolean'],
            'summary' => ['nullable', 'string', 'max:2000'],
        ]);

        $model = match ($type) {
            'albums' => Album::findOrFail($id),
            'news' => NewsPost::findOrFail($id),
            'activities' => Activity::findOrFail($id),
            'documents' => ArchiveDocument::findOrFail($id),
            'partners' => Partner::findOrFail($id),
        };

        $public = (bool) $data['public'];
        $extra = [];

        if ($public) {
            if ($error = $this->ineligible($type, $model, $data['summary'] ?? null)) {
                return back()->with('error', $error);
            }
            if ($type === 'activities') {
                $extra = ['public_summary' => trim((string) ($data['summary'] ?? $model->public_summary))]
                    + ($model->public_slug ? [] : ['public_slug' => $this->activitySlug($model)]);
            }
        }

        $model->update(['is_public' => $public] + $extra);

        return back()->with('success', $public ? 'Ditampilkan di website publik.' : 'Ditarik dari website publik.');
    }

    private function ineligible(string $type, Model $model, ?string $summary): ?string
    {
        return match ($type) {
            'albums' => $model->status !== 'published' ? 'Album belum diterbitkan.' : null,
            'news' => $model->status !== 'published' ? 'Berita belum diterbitkan.' : null,
            'partners' => $model->status !== 'active' ? 'Mitra tidak aktif.' : null,
            'activities' => ! in_array($model->status, Activity::PUBLIC_STATUSES, true)
                ? 'Kegiatan belum disetujui.'
                : (mb_strlen(trim((string) ($summary ?? $model->public_summary))) < 20 ? 'Tulis ringkasan publik minimal 20 karakter. Deskripsi internal tidak ditampilkan ke publik.' : null),
            default => null,
        };
    }

    private function activitySlug(Activity $a): string
    {
        $base = Str::slug($a->title).'-'.Str::lower($a->code);
        $slug = $base;
        $i = 2;
        while (Activity::withTrashed()->where('public_slug', $slug)->where('id', '!=', $a->id)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function row(int $id, string $title, string $status, bool $public, bool $eligible, ?string $why, array $extra = []): array
    {
        return ['id' => $id, 'title' => $title, 'status' => $status, 'is_public' => $public, 'eligible' => $eligible, 'why' => $eligible ? null : $why] + $extra;
    }
}
