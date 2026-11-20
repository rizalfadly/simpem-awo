<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Semua foto diproses ulang di server (GD):
 *  - divalidasi sebagai gambar sungguhan (bukan sekadar ekstensi),
 *  - diputar sesuai orientasi EXIF lalu metadata (termasuk GPS) DIBUANG karena di-encode ulang,
 *  - diperkecil (maks. 2000 px) + dibuat thumbnail 480 px,
 *  - disimpan di disk privat 'local'.
 */
final class ImageProcessor
{
    private const MAX_PIXELS = 25_000_000;

    /** @return array{path:string,thumb:string,width:int,height:int,size:int,mime:string} */
    public static function store(UploadedFile $file, string $directory): array
    {
        @ini_set('memory_limit', '512M');

        $real = $file->getRealPath();
        $info = $real ? @getimagesize($real) : false;
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new InvalidArgumentException('Bukan gambar JPG, PNG, atau WEBP yang valid.');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new InvalidArgumentException('Resolusi gambar terlalu besar.');
        }

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($real),
            IMAGETYPE_PNG  => @imagecreatefrompng($real),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($real) : false,
        };
        if (! $src) {
            throw new InvalidArgumentException('Gambar tidak dapat dibaca.');
        }

        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = (int) (@exif_read_data($real)['Orientation'] ?? 1);
            $rotated = match ($orientation) {
                3 => imagerotate($src, 180, 0),
                6 => imagerotate($src, -90, 0),
                8 => imagerotate($src, 90, 0),
                default => $src,
            };
            $src = $rotated ?: $src;
        }

        $full = self::fit($src, 2000);
        $thumb = self::fit($src, 480);

        $name = Str::random(40);
        $disk = Storage::disk('local');
        $path = "{$directory}/{$name}.jpg";
        $thumbPath = "{$directory}/{$name}_t.jpg";
        $fullData = self::encode($full, 82);

        $disk->put($path, $fullData);
        $disk->put($thumbPath, self::encode($thumb, 78));

        return [
            'path'   => $path,
            'thumb'  => $thumbPath,
            'width'  => imagesx($full),
            'height' => imagesy($full),
            'size'   => strlen($fullData),
            'mime'   => 'image/jpeg',
        ];
    }

    private static function fit(\GdImage $src, int $max): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $max / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // PNG transparan -> latar putih
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    private static function encode(\GdImage $img, int $quality): string
    {
        ob_start();
        imagejpeg($img, null, $quality);

        return (string) ob_get_clean();
    }
}
