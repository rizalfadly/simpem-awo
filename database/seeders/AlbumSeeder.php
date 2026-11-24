<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh galeri (album + foto placeholder yang dibuat otomatis dengan PHP GD).
 * Aman dijalankan berulang: album dicocokkan lewat judul, foto hanya dibuat jika album belum punya media.
 *
 * Jalankan: php artisan db:seed --class=AlbumSeeder
 */
class AlbumSeeder extends Seeder
{
    /** Warna gradasi [atas, bawah] per foto, nuansa tema cyan/biru situs. */
    private const PALETTES = [
        [[8, 145, 178], [8, 9, 11]],
        [[54, 216, 232], [2, 132, 199]],
        [[14, 116, 144], [22, 32, 46]],
        [[103, 232, 249], [8, 145, 178]],
        [[2, 132, 199], [14, 17, 22]],
    ];

    public function run(): void
    {
        $userId = User::query()->value('id');
        $hasGd = function_exists('imagecreatetruecolor') && function_exists('imagejpeg');

        if (! $hasGd) {
            $this->command?->warn('Ekstensi PHP GD tidak aktif: album dibuat tanpa foto.');
        }

        $albums = [
            [
                'title' => 'Jalan Sehat Bersama Warga RT 21',
                'description' => 'Dokumentasi jalan sehat bersama warga dalam rangka memperingati hari kemerdekaan.',
                'photos' => 5, 'status' => 'published', 'is_public' => true, 'days_ago' => 5,
            ],
            [
                'title' => 'Kerja Bakti Bersih Lingkungan',
                'description' => 'Gotong royong anggota dan warga membersihkan saluran air serta fasilitas umum.',
                'photos' => 4, 'status' => 'published', 'is_public' => true, 'days_ago' => 12,
            ],
            [
                'title' => 'Lomba Agustusan',
                'description' => 'Keseruan lomba tradisional: balap karung, tarik tambang, dan makan kerupuk.',
                'photos' => 4, 'status' => 'published', 'is_public' => true, 'days_ago' => 40,
            ],
            [
                'title' => 'Pelatihan Konten Kreatif UMKM',
                'description' => 'Sesi berbagi foto produk dan konten media sosial untuk pelaku UMKM lokal.',
                'photos' => 3, 'status' => 'published', 'is_public' => true, 'days_ago' => 20,
            ],
            [
                'title' => 'Rapat Kerja Pengurus (Draf)',
                'description' => 'Album internal yang belum dipublikasikan.',
                'photos' => 2, 'status' => 'draft', 'is_public' => false, 'days_ago' => 2,
            ],
        ];

        foreach ($albums as $a) {
            $published = $a['status'] === 'published';

            $album = Album::withTrashed()->updateOrCreate(
                ['title' => $a['title']],
                [
                    'description' => $a['description'],
                    'event_date' => now()->subDays($a['days_ago'])->toDateString(),
                    'status' => $a['status'],
                    'is_public' => $a['is_public'],
                    'published_at' => $published ? now()->subDays($a['days_ago'] - 1 > 0 ? $a['days_ago'] - 1 : 0) : null,
                    'published_by' => $published ? $userId : null,
                    'created_by' => $userId,
                    'deleted_at' => null,
                ],
            );

            if (! $hasGd || $album->media()->exists()) {
                continue;
            }

            $first = null;
            for ($i = 1; $i <= $a['photos']; $i++) {
                $item = $this->makePhoto($album, $i, $a['title'], $userId);
                $first ??= $item;
            }

            if ($first) {
                $album->update(['cover_media_id' => $first->id]);
            }
        }
    }

    private function makePhoto(Album $album, int $n, string $label, ?int $userId): MediaItem
    {
        [$top, $bottom] = self::PALETTES[($n - 1) % count(self::PALETTES)];
        [$w, $h] = [1200, 800];

        $full = $this->render($w, $h, $top, $bottom, "{$label} - Foto contoh {$n}");
        $thumb = $this->render(400, 267, $top, $bottom, "Foto contoh {$n}");

        $dir = "albums/{$album->id}";
        $file = "{$dir}/seed-{$n}.jpg";
        $thumbPath = "{$dir}/thumbs/seed-{$n}.jpg";

        $disk = Storage::disk('local');
        $disk->put($file, $full);
        $disk->put($thumbPath, $thumb);

        return MediaItem::create([
            'album_id' => $album->id,
            'type' => 'photo',
            'caption' => "Foto contoh {$n}",
            'file_path' => $file,
            'thumb_path' => $thumbPath,
            'mime' => 'image/jpeg',
            'size_bytes' => strlen($full),
            'width' => $w,
            'height' => $h,
            'sort_order' => $n,
            'uploaded_by' => $userId,
        ]);
    }

    /** Gambar gradasi vertikal + label teks; mengembalikan byte JPEG. */
    private function render(int $w, int $h, array $top, array $bottom, string $text): string
    {
        $img = imagecreatetruecolor($w, $h);

        for ($y = 0; $y < $h; $y++) {
            $t = $y / max(1, $h - 1);
            $c = imagecolorallocate(
                $img,
                (int) ($top[0] + ($bottom[0] - $top[0]) * $t),
                (int) ($top[1] + ($bottom[1] - $top[1]) * $t),
                (int) ($top[2] + ($bottom[2] - $top[2]) * $t),
            );
            imageline($img, 0, $y, $w, $y, $c);
        }

        // Pita label di bawah
        $band = imagecolorallocatealpha($img, 0, 0, 0, 80);
        imagefilledrectangle($img, 0, $h - 34, $w, $h, $band);
        imagestring($img, 5, 12, $h - 26, $text, imagecolorallocate($img, 255, 255, 255));

        ob_start();
        imagejpeg($img, null, 85);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }
}