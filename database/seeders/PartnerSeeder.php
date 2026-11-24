<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\PartnerProduct;
use App\Models\PartnerPromotion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh mitra & UMKM (fiktif): mitra, produk, promosi, plus logo/foto placeholder (PHP GD).
 * Aman dijalankan berulang: mitra dicocokkan lewat nama; produk, promosi, dan gambar
 * hanya dibuat bila belum ada.
 *
 * Jalankan: php artisan db:seed --class=PartnerSeeder
 */
class PartnerSeeder extends Seeder
{
    private const COLORS = [
        [8, 145, 178], [14, 116, 144], [2, 132, 199], [22, 32, 46], [179, 32, 42], [30, 107, 60],
    ];

    private bool $hasGd;

    public function run(): void
    {
        $userId = User::query()->value('id');
        $this->hasGd = function_exists('imagecreatetruecolor') && function_exists('imagejpeg');

        if (! $this->hasGd) {
            $this->command?->warn('Ekstensi PHP GD tidak aktif: mitra dibuat tanpa logo dan foto produk.');
        }

        foreach ($this->partners() as $i => $data) {
            $products = $data['products'] ?? [];
            $promotions = $data['promotions'] ?? [];
            unset($data['products'], $data['promotions']);

            $partner = Partner::withTrashed()->firstOrNew(['name' => $data['name']]);
            if (! $partner->exists) {
                $partner->slug = Partner::uniqueSlug($data['name']);
            }
            $partner->fill($data + ['created_by' => $userId, 'deleted_at' => null])->save();

            if ($this->hasGd && ! $partner->logo_path) {
                $this->makeLogo($partner, $i);
            }

            if (! $partner->products()->exists()) {
                foreach ($products as $n => $p) {
                    $this->makeProduct($partner, $p, $n + 1, $i + $n);
                }
            }

            if (! $partner->promotions()->exists()) {
                foreach ($promotions as $promo) {
                    $this->makePromotion($partner, $promo, $userId);
                }
            }
        }
    }

    private function makeLogo(Partner $partner, int $i): void
    {
        $color = self::COLORS[$i % count(self::COLORS)];
        $initials = $this->initials($partner->name);

        $disk = Storage::disk('local');
        $disk->put("partners/{$partner->id}/logo.jpg", $this->tile(400, 400, $color, $initials, 10));
        $disk->put("partners/{$partner->id}/logo-thumb.jpg", $this->tile(150, 150, $color, $initials, 4));

        $partner->update([
            'logo_path' => "partners/{$partner->id}/logo.jpg",
            'logo_thumb_path' => "partners/{$partner->id}/logo-thumb.jpg",
        ]);
    }

    private function makeProduct(Partner $partner, array $p, int $order, int $colorIdx): void
    {
        $product = PartnerProduct::create([
            'partner_id' => $partner->id,
            'name' => $p['name'],
            'description' => $p['description'] ?? null,
            'price' => $p['price'] ?? null,
            'price_note' => $p['note'] ?? null,
            'is_available' => $p['available'] ?? true,
            'sort_order' => $order,
        ]);

        if (! $this->hasGd) {
            return;
        }

        $color = self::COLORS[($colorIdx + 2) % count(self::COLORS)];
        $initials = $this->initials($p['name']);
        $dir = "partners/{$partner->id}/products";

        $disk = Storage::disk('local');
        $disk->put("{$dir}/{$product->id}.jpg", $this->tile(600, 450, $color, $initials, 10));
        $disk->put("{$dir}/{$product->id}-thumb.jpg", $this->tile(300, 225, $color, $initials, 5));

        $product->update([
            'photo_path' => "{$dir}/{$product->id}.jpg",
            'photo_thumb_path' => "{$dir}/{$product->id}-thumb.jpg",
        ]);
    }

    private function makePromotion(Partner $partner, array $promo, ?int $userId): void
    {
        $published = $promo['status'] === 'published';

        PartnerPromotion::create([
            'partner_id' => $partner->id,
            'title' => $promo['title'],
            'description' => $promo['description'] ?? null,
            'terms' => $promo['terms'] ?? null,
            'starts_on' => now()->addDays($promo['start'])->toDateString(),
            'ends_on' => now()->addDays($promo['end'])->toDateString(),
            'audience' => $promo['audience'],
            'status' => $promo['status'],
            'published_at' => $published ? now()->subDays(1) : null,
            'published_by' => $published ? $userId : null,
            'created_by' => $userId,
        ]);
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice($words, 0, 2));

        return implode('', $letters) ?: '?';
    }

    /**
     * Gambar warna polos dengan teks besar di tengah. Teks digambar pada kanvas kecil lalu
     * diperbesar (supaya tanpa font TTF), dan latar kanvas sama dengan warna akhir sehingga mulus.
     */
    private function tile(int $w, int $h, array $rgb, string $text, int $scale): string
    {
        $sw = max(1, intdiv($w, $scale));
        $sh = max(1, intdiv($h, $scale));

        $small = imagecreatetruecolor($sw, $sh);
        $bg = imagecolorallocate($small, $rgb[0], $rgb[1], $rgb[2]);
        imagefilledrectangle($small, 0, 0, $sw, $sh, $bg);

        $white = imagecolorallocate($small, 255, 255, 255);
        $tw = imagefontwidth(5) * strlen($text);
        $th = imagefontheight(5);
        imagestring($small, 5, (int) (($sw - $tw) / 2), (int) (($sh - $th) / 2), $text, $white);

        $big = imagecreatetruecolor($w, $h);
        imagecopyresampled($big, $small, 0, 0, 0, 0, $w, $h, $sw, $sh);

        ob_start();
        imagejpeg($big, null, 88);
        $bytes = (string) ob_get_clean();

        imagedestroy($small);
        imagedestroy($big);

        return $bytes;
    }

    /** Semua nama usaha, orang, dan kontak di bawah ini fiktif. */
    private function partners(): array
    {
        return [
            [
                'name' => 'Kopi Wetan',
                'type' => 'umkm', 'sector' => 'Kuliner & minuman', 'owner_name' => 'Rendra Pratama',
                'description' => 'Kedai kopi lokal dengan biji pilihan dari petani Jawa Timur, dikelola anak muda lingkungan sekitar.',
                'address' => 'Jl. Contoh No. 12', 'city' => 'Surabaya',
                'instagram' => 'kopiwetan.sby', 'public_contact' => '@kopiwetan.sby',
                'contact_person' => 'Rendra Pratama', 'contact_phone' => '0812-0000-0001', 'contact_email' => 'kopiwetan@example.com',
                'notes' => 'Data contoh.', 'status' => 'active', 'joined_at' => now()->subMonths(8)->toDateString(), 'is_public' => true,
                'products' => [
                    ['name' => 'Es Kopi Susu Gula Aren', 'description' => 'Espresso, susu segar, dan gula aren.', 'price' => 18000, 'note' => 'per gelas'],
                    ['name' => 'Kopi Hitam Tubruk', 'description' => 'Seduhan tradisional, pekat dan harum.', 'price' => 12000, 'note' => 'per gelas'],
                    ['name' => 'Biji Kopi Arabika 250 gr', 'description' => 'Sangrai medium, cocok untuk manual brew.', 'price' => 65000],
                ],
                'promotions' => [
                    ['title' => 'Diskon 15% untuk Anggota', 'description' => 'Tunjukkan kartu anggota saat memesan.', 'terms' => 'Berlaku untuk minuman, tidak berlaku akhir pekan.', 'start' => -3, 'end' => 25, 'audience' => 'public', 'status' => 'published'],
                    ['title' => 'Promo Akhir Tahun (Draf)', 'description' => 'Masih disiapkan.', 'start' => 10, 'end' => 40, 'audience' => 'members', 'status' => 'draft'],
                ],
            ],
            [
                'name' => 'Dapur Bu Sari',
                'type' => 'umkm', 'sector' => 'Kuliner', 'owner_name' => 'Sari Wulandari',
                'description' => 'Masakan rumahan khas Jawa Timur, menerima pesanan harian dan nasi kotak untuk acara.',
                'address' => 'Jl. Contoh No. 7', 'city' => 'Surabaya',
                'instagram' => 'dapurbusari', 'public_contact' => '0812-0000-0002',
                'contact_person' => 'Sari Wulandari', 'contact_phone' => '0812-0000-0002', 'contact_email' => 'dapursari@example.com',
                'notes' => 'Data contoh.', 'status' => 'active', 'joined_at' => now()->subMonths(6)->toDateString(), 'is_public' => true,
                'products' => [
                    ['name' => 'Nasi Bakar Ayam Suwir', 'description' => 'Dibakar dengan daun pisang, sambal terasi.', 'price' => 20000, 'note' => 'per porsi'],
                    ['name' => 'Rawon Daging', 'description' => 'Kuah kluwek pekat, telur asin, tauge pendek.', 'price' => 28000, 'note' => 'per porsi'],
                    ['name' => 'Paket Nasi Kotak', 'description' => 'Nasi, lauk, sambal, dan buah.', 'price' => 25000, 'note' => 'mulai dari'],
                ],
                'promotions' => [
                    ['title' => 'Gratis Es Teh untuk Pemesanan Nasi Kotak', 'description' => 'Pemesanan minimal 20 kotak.', 'start' => -1, 'end' => 14, 'audience' => 'members', 'status' => 'published'],
                ],
            ],
            [
                'name' => 'Wetan Sablon & Merch',
                'type' => 'umkm', 'sector' => 'Fashion & merchandise', 'owner_name' => 'Dimas Aryo',
                'description' => 'Sablon kaos, totebag, dan merchandise komunitas dengan minimal order rendah.',
                'address' => 'Jl. Contoh No. 21', 'city' => 'Surabaya',
                'instagram' => 'wetan.merch', 'public_contact' => '@wetan.merch',
                'contact_person' => 'Dimas Aryo', 'contact_phone' => '0812-0000-0003', 'contact_email' => 'merch@example.com',
                'notes' => 'Data contoh.', 'status' => 'active', 'joined_at' => now()->subMonths(10)->toDateString(), 'is_public' => true,
                'products' => [
                    ['name' => 'Kaos Sablon Custom', 'description' => 'Katun combed 24s, desain sesuai permintaan.', 'price' => 85000, 'note' => 'mulai dari'],
                    ['name' => 'Totebag Kanvas', 'description' => 'Kanvas tebal, sablon satu warna.', 'price' => 35000],
                    ['name' => 'Topi Bordir', 'description' => 'Topi baseball dengan bordir logo.', 'price' => 45000],
                ],
                'promotions' => [
                    ['title' => 'Harga Khusus Pesanan Komunitas', 'description' => 'Potongan untuk pemesanan di atas 30 pcs.', 'terms' => 'Syarat dan ketentuan berlaku.', 'start' => -20, 'end' => -2, 'audience' => 'public', 'status' => 'published'],
                ],
            ],
            [
                'name' => 'Anyaman Lestari',
                'type' => 'umkm', 'sector' => 'Kerajinan', 'owner_name' => 'Lestari Handayani',
                'description' => 'Kerajinan anyaman tangan dari bahan alami: tas, keranjang, dan hiasan rumah.',
                'address' => 'Jl. Contoh No. 3', 'city' => 'Sidoarjo',
                'instagram' => 'anyaman.lestari', 'public_contact' => '@anyaman.lestari',
                'contact_person' => 'Lestari Handayani', 'contact_phone' => '0812-0000-0004', 'contact_email' => 'anyaman@example.com',
                'notes' => 'Data contoh.', 'status' => 'active', 'joined_at' => now()->subMonths(4)->toDateString(), 'is_public' => true,
                'products' => [
                    ['name' => 'Tas Anyaman Pandan', 'description' => 'Ringan, kuat, dan ramah lingkungan.', 'price' => 120000],
                    ['name' => 'Keranjang Serbaguna', 'description' => 'Untuk penyimpanan atau hampers.', 'price' => 55000, 'note' => 'per buah'],
                ],
            ],
            [
                'name' => 'Servis Elektronik Pak Budi',
                'type' => 'umkm', 'sector' => 'Jasa', 'owner_name' => 'Budi Santoso',
                'description' => 'Servis laptop dan ponsel dengan garansi pengerjaan.',
                'address' => 'Jl. Contoh No. 30', 'city' => 'Surabaya',
                'public_contact' => '0812-0000-0005',
                'contact_person' => 'Budi Santoso', 'contact_phone' => '0812-0000-0005', 'contact_email' => 'budi@example.com',
                'notes' => 'Data contoh.', 'status' => 'active', 'joined_at' => now()->subMonths(3)->toDateString(), 'is_public' => true,
                'products' => [
                    ['name' => 'Servis Laptop', 'description' => 'Pembersihan, ganti thermal paste, dan perbaikan ringan.', 'price' => 75000, 'note' => 'mulai dari'],
                    ['name' => 'Ganti Layar HP', 'description' => 'Tersedia berbagai merk, garansi 1 bulan.', 'price' => 150000, 'note' => 'mulai dari'],
                ],
            ],
            [
                'name' => 'Toko Bangunan Makmur Jaya',
                'type' => 'sponsor', 'sector' => 'Perdagangan',
                'description' => 'Donatur tetap untuk kegiatan lingkungan dan perlengkapan kerja bakti.',
                'address' => 'Jl. Contoh No. 45', 'city' => 'Surabaya',
                'public_contact' => '0812-0000-0006',
                'contact_person' => 'Pak Haryono', 'contact_phone' => '0812-0000-0006', 'contact_email' => 'makmurjaya@example.com',
                'notes' => 'Data contoh.', 'status' => 'active', 'joined_at' => now()->subYear()->toDateString(), 'is_public' => true,
            ],
            [
                'name' => 'Kantor Kelurahan Setempat',
                'type' => 'institution', 'sector' => 'Pemerintahan',
                'description' => 'Mitra koordinasi perizinan dan kegiatan warga. Hanya untuk keperluan internal.',
                'city' => 'Surabaya',
                'contact_person' => 'Staf Kelurahan', 'contact_phone' => '0812-0000-0007', 'contact_email' => 'kelurahan@example.com',
                'notes' => 'Data contoh, tidak ditampilkan di website publik.', 'status' => 'active', 'joined_at' => now()->subYear()->toDateString(), 'is_public' => false,
            ],
            [
                'name' => 'Komunitas Pecinta Alam Wetan',
                'type' => 'community', 'sector' => 'Komunitas',
                'description' => 'Komunitas yang pernah berkolaborasi pada kegiatan penanaman pohon. Saat ini tidak aktif.',
                'city' => 'Surabaya',
                'public_contact' => '@pawetan',
                'notes' => 'Data contoh: tidak aktif tetapi bertanda publik, untuk menguji bahwa mitra nonaktif tersembunyi.', 'status' => 'inactive', 'joined_at' => now()->subMonths(14)->toDateString(), 'is_public' => true,
            ],
        ];
    }
}