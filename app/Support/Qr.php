<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class Qr
{
    /** QR Code sebagai SVG (tanpa ekstensi GD/Imagick). */
    public static function svg(string $text, int $size = 320): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($text);
    }
}
