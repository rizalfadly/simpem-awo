<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Support\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Profil organisasi dan kontak untuk website publik. */
class SiteSettingsController extends Controller
{
    private const TEXT = ['name', 'tagline', 'about', 'vision', 'mission', 'history', 'address', 'email', 'phone', 'whatsapp', 'instagram', 'tiktok', 'youtube', 'maps_url', 'hours'];

    public function edit(): Response
    {
        $s = SiteSetting::settings();

        return Inertia::render('SiteAdmin/Settings', [
            'settings' => collect(self::TEXT)->mapWithKeys(fn ($k) => [$k => $s[$k] ?? ''])->all(),
            'logo'     => $s['logo_path'] !== '' ? '/publik/situs/logo' : null,
            'hero'     => $s['hero_path'] !== '' ? '/publik/situs/hero' : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'tagline'     => ['nullable', 'string', 'max:200'],
            'about'       => ['nullable', 'string', 'max:5000'],
            'vision'      => ['nullable', 'string', 'max:2000'],
            'mission'     => ['nullable', 'string', 'max:3000'],
            'history'     => ['nullable', 'string', 'max:5000'],
            'address'     => ['nullable', 'string', 'max:255'],
            'email'       => ['nullable', 'email', 'max:190'],
            'phone'       => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]+$/'],
            'whatsapp'    => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]+$/'],
            'instagram'   => ['nullable', 'string', 'max:80', 'regex:/^@?[A-Za-z0-9._]{1,30}$/'],
            'tiktok'      => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/(www\.|vm\.|vt\.)?tiktok\.com\/.+|@?[A-Za-z0-9._]{1,30})$/i'],
            'youtube'     => ['nullable', 'url:http,https', 'max:255'],
            'maps_url'    => ['nullable', 'url:http,https', 'max:500'],
            'hours'       => ['nullable', 'string', 'max:200'],
            'logo'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'hero'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_hero' => ['nullable', 'boolean'],
        ], [
            'name.required'    => 'Nama organisasi wajib diisi.',
            'instagram.regex'  => 'Username Instagram hanya boleh huruf, angka, titik, dan garis bawah.',
            'tiktok.regex'     => 'Isi dengan @username TikTok atau tautan https://tiktok.com/... yang valid.',
            'youtube.url'      => 'Alamat harus diawali http:// atau https://',
            'maps_url.url'     => 'Alamat harus diawali http:// atau https://',
            'phone.regex'      => 'Nomor hanya boleh angka dan tanda + - ( ).',
            'whatsapp.regex'   => 'Nomor hanya boleh angka dan tanda + - ( ).',
        ]);

        $current = SiteSetting::settings();
        $values = collect(self::TEXT)->mapWithKeys(fn ($k) => [$k => (string) ($data[$k] ?? '')])->all();

        foreach (['logo', 'hero'] as $kind) {
            if ($request->hasFile($kind)) {
                $this->delete($current[$kind.'_path']);
                $values[$kind.'_path'] = $this->process($request, $kind);
            } elseif ($request->boolean("remove_{$kind}")) {
                $this->delete($current[$kind.'_path']);
                $values[$kind.'_path'] = '';
            }
        }

        SiteSetting::put($values);

        return back()->with('success', 'Pengaturan website disimpan.');
    }

    private function process(Request $request, string $kind): string
    {
        try {
            $r = ImageProcessor::store($request->file($kind), 'site');
        } catch (\Throwable) {
            throw ValidationException::withMessages([$kind => 'Gambar tidak dapat diproses. Pastikan berkas gambar valid.']);
        }
        Storage::disk('local')->delete($r['thumb']);   // thumbnail tidak dipakai

        return $r['path'];
    }

    private function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }
}