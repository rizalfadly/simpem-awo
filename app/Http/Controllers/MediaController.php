<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\MediaItem;
use App\Support\Audit;
use App\Support\ImageProcessor;
use App\Support\VideoLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Berkas media dilayani lewat aplikasi (privat), bukan URL langsung, supaya draf tidak bocor. */
class MediaController extends Controller
{
    public function storePhotos(Request $request, Album $album): RedirectResponse
    {
        abort_unless($request->user()->can('contribute', $album), 403);

        if ($album->status === 'archived') {
            return back()->with('error', 'Album yang diarsipkan tidak dapat ditambah media.');
        }

        $request->validate([
            'photos'   => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'photos.required' => 'Pilih minimal satu foto.',
            'photos.max'      => 'Maksimal 20 foto sekali unggah.',
            'photos.*.image'  => 'Berkas harus berupa gambar.',
            'photos.*.mimes'  => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photos.*.max'    => 'Ukuran tiap foto maksimal 8 MB.',
        ]);

        $order = (int) $album->media()->max('sort_order');
        $ok = 0;
        $failed = [];

        foreach ($request->file('photos') as $file) {
            try {
                $r = ImageProcessor::store($file, "albums/{$album->id}");
            } catch (\Throwable) {
                $failed[] = $file->getClientOriginalName();

                continue;
            }

            $album->media()->create([
                'type' => 'photo', 'file_path' => $r['path'], 'thumb_path' => $r['thumb'], 'mime' => $r['mime'],
                'size_bytes' => $r['size'], 'width' => $r['width'], 'height' => $r['height'],
                'sort_order' => ++$order, 'uploaded_by' => $request->user()->id,
            ]);
            $ok++;
        }

        if ($ok) {
            Audit::record('created', "{$ok} foto diunggah ke album {$album->title}", $album);
        }

        if ($failed) {
            return back()
                ->withErrors(['photos' => 'Gagal memproses: '.implode(', ', $failed)])
                ->with($ok ? ['success' => "{$ok} foto diunggah."] : []);
        }

        return back()->with('success', "{$ok} foto diunggah.");
    }

    public function storeVideo(Request $request, Album $album): RedirectResponse
    {
        abort_unless($request->user()->can('contribute', $album), 403);

        if ($album->status === 'archived') {
            return back()->with('error', 'Album yang diarsipkan tidak dapat ditambah media.');
        }

        $data = $request->validate([
            'video_url'  => ['nullable', 'url:https', 'max:500', 'required_without:video_file'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm', 'mimetypes:video/mp4,video/webm', 'max:51200', 'required_without:video_url'],
            'caption'    => ['nullable', 'string', 'max:255'],
        ], [
            'video_url.url'      => 'Tautan harus diawali https://',
            'video_url.required_without' => 'Isi tautan video atau unggah berkas.',
            'video_file.required_without' => 'Isi tautan video atau unggah berkas.',
            'video_file.mimes'   => 'Video harus berformat MP4 atau WEBM.',
            'video_file.max'     => 'Ukuran video maksimal 50 MB. Untuk video besar, gunakan tautan YouTube.',
        ]);

        $order = (int) $album->media()->max('sort_order') + 1;
        $base = ['type' => 'video', 'caption' => $data['caption'] ?? null, 'sort_order' => $order, 'uploaded_by' => $request->user()->id];

        if ($request->hasFile('video_file')) {
            $file = $request->file('video_file');
            $album->media()->create($base + [
                'file_path' => $file->store("albums/{$album->id}", 'local'),
                'mime' => $file->getMimeType(), 'size_bytes' => $file->getSize(),
            ]);
        } else {
            $link = VideoLink::parse($data['video_url']);
            $album->media()->create($base + ['video_provider' => $link['provider'], 'video_ref' => $link['ref']]);
        }

        Audit::record('created', "Video ditambahkan ke album {$album->title}", $album);

        return back()->with('success', 'Video ditambahkan.');
    }

    public function update(Request $request, MediaItem $media): RedirectResponse
    {
        abort_unless($request->user()->can('contribute', $media->album), 403);

        $media->update($request->validate(['caption' => ['nullable', 'string', 'max:255']]));

        return back()->with('success', 'Keterangan disimpan.');
    }

    public function destroy(Request $request, MediaItem $media): RedirectResponse
    {
        $album = $media->album;
        $user = $request->user();
        abort_unless($user->can('manage', $album) || $media->uploaded_by === $user->id, 403);

        if ($album->cover_media_id === $media->id) {
            $album->update(['cover_media_id' => null]);
        }
        $media->delete();   // berkas fisik ikut dihapus (lihat MediaItem::booted)
        Audit::record('deleted', "Media dihapus dari album {$album->title}", $album);

        return back()->with('success', 'Media dihapus.');
    }

    public function file(Request $request, MediaItem $media): BinaryFileResponse
    {
        $this->authorizeView($request, $media);

        return $this->serve($media->file_path, $media->mime);
    }

    public function thumb(Request $request, MediaItem $media): BinaryFileResponse
    {
        $this->authorizeView($request, $media);

        return $this->serve($media->thumb_path, 'image/jpeg');
    }

    private function authorizeView(Request $request, MediaItem $media): void
    {
        $album = $media->album;
        abort_if($album->trashed(), 404);
        abort_unless($request->user()->can('view', $album), 403);
    }

    private function serve(?string $path, ?string $mime): BinaryFileResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        // response()->file mendukung HTTP Range, sehingga video bisa di-seek.
        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type'           => $mime ?: 'application/octet-stream',
            'Cache-Control'          => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
