<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Pengaturan situs (profil & kontak organisasi) sebagai pasangan key/value. */
class SiteSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    public const DEFAULTS = [
        'name' => 'Arek Wetan Organizer', 'tagline' => '', 'about' => '', 'vision' => '', 'mission' => '', 'history' => '',
        'address' => '', 'email' => '', 'phone' => '', 'whatsapp' => '', 'instagram' => '', 'tiktok' => '', 'youtube' => '',
        'maps_url' => '', 'hours' => '', 'logo_path' => '', 'hero_path' => '',
    ];

    /** @return array<string, string> */
    public static function settings(): array
    {
        return array_merge(self::DEFAULTS, static::query()->pluck('value', 'key')->map(fn ($v) => (string) $v)->all());
    }

    /** @param  array<string, string|null>  $data */
    public static function put(array $data): void
    {
        foreach ($data as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            static::query()->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        }
    }

    /** Bagian yang aman dikirim ke halaman publik (tanpa path berkas internal). */
    public static function bundle(): array
    {
        $s = self::settings();
        $digits = preg_replace('/\D+/', '', $s['whatsapp']);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return [
            'name'      => $s['name'] ?: self::DEFAULTS['name'],
            'tagline'   => $s['tagline'],
            'address'   => $s['address'],
            'email'     => $s['email'],
            'phone'     => $s['phone'],
            'whatsapp'  => $s['whatsapp'],
            'whatsapp_url' => strlen($digits) >= 8 && strlen($digits) <= 15 ? "https://wa.me/{$digits}" : null,
            'instagram' => $s['instagram'] !== '' ? ltrim($s['instagram'], '@') : '',
            'tiktok'    => $s['tiktok'],
            'youtube'   => $s['youtube'],
            'maps_url'  => $s['maps_url'],
            'hours'     => $s['hours'],
            'logo'      => $s['logo_path'] !== '' ? '/publik/situs/logo' : null,
            'hero'      => $s['hero_path'] !== '' ? '/publik/situs/hero' : null,
        ];
    }

    public static function slug(string $text): string
    {
        return Str::slug($text) ?: 'item';
    }
}