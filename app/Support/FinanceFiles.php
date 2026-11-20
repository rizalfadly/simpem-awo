<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Penyimpanan bukti keuangan (privat). Gambar diproses ulang (metadata/GPS dibuang); PDF disimpan apa adanya. */
final class FinanceFiles
{
    /**
     * @return array{path:string, original_name:string, mime:string, size:int}
     *
     * @throws \InvalidArgumentException bila gambar tidak valid
     */
    public static function store(UploadedFile $file, string $directory): array
    {
        $name = self::safeName($file->getClientOriginalName());

        if (str_starts_with((string) $file->getMimeType(), 'image/')) {
            $r = ImageProcessor::store($file, $directory);
            Storage::disk('local')->delete($r['thumb']);   // thumbnail tidak dipakai untuk bukti

            return ['path' => $r['path'], 'original_name' => preg_replace('/\.\w+$/', '', $name).'.jpg', 'mime' => 'image/jpeg', 'size' => $r['size']];
        }

        return ['path' => $file->store($directory, 'local'), 'original_name' => $name, 'mime' => (string) $file->getMimeType(), 'size' => (int) $file->getSize()];
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    private static function safeName(string $name): string
    {
        $name = preg_replace('/[^\w\-. ()]+/u', '_', basename($name));

        return mb_substr($name ?: 'bukti', 0, 150);
    }
}
