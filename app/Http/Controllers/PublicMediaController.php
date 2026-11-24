<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
use App\Models\NewsPost;
use App\Models\Partner;
use App\Models\PartnerProduct;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Berkas untuk website publik. Setiap permintaan memeriksa ulang bahwa induknya memang dipilih publik,
 * jadi menarik konten dari publik langsung menutup akses ke berkasnya. Selain itu selalu 404 (bukan 403)
 * agar keberadaan konten tertutup tidak bocor.
 */
class PublicMediaController extends Controller
{
    public function media(Request $request, MediaItem $media): BinaryFileResponse
    {
        $album = $media->album;
        abort_unless($album && ! $album->trashed() && $album->status === 'published' && $album->is_public, 404);

        $thumb = $request->boolean('thumb');

        return $this->serve($thumb ? $media->thumb_path : $media->file_path, $thumb ? 'image/jpeg' : $media->mime);
    }

    public function newsCover(Request $request, NewsPost $post): BinaryFileResponse
    {
        abort_unless($post->status === 'published' && $post->is_public, 404);

        return $this->serve($request->boolean('thumb') ? $post->cover_thumb_path : $post->cover_path, 'image/jpeg');
    }

    public function partnerLogo(Request $request, Partner $partner): BinaryFileResponse
    {
        abort_unless($partner->is_public && $partner->status === 'active', 404);

        return $this->serve($request->boolean('thumb') ? $partner->logo_thumb_path : $partner->logo_path, 'image/jpeg');
    }

    public function productPhoto(Request $request, PartnerProduct $product): BinaryFileResponse
    {
        $partner = $product->partner;
        abort_unless($partner && ! $partner->trashed() && $partner->is_public && $partner->status === 'active', 404);

        return $this->serve($request->boolean('thumb') ? $product->photo_thumb_path : $product->photo_path, 'image/jpeg');
    }

    public function site(string $kind): BinaryFileResponse
    {
        abort_unless(in_array($kind, ['logo', 'hero'], true), 404);

        return $this->serve(SiteSetting::settings()[$kind.'_path'] ?: null, 'image/jpeg');
    }

    private function serve(?string $path, ?string $mime): BinaryFileResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        // response()->file mendukung HTTP Range sehingga video dapat di-seek.
        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type'           => $mime ?: 'application/octet-stream',
            'Cache-Control'          => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
