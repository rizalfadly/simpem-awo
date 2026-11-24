<?php

namespace Database\Seeders;

use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh berita untuk website publik.
 * Aman dijalankan berulang: berita dicocokkan lewat slug, jadi tidak menggandakan data.
 *
 * Jalankan: php artisan db:seed --class=NewsPostSeeder
 */
class NewsPostSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()->value('id'); // pembuat/penerbit: user pertama (boleh null)

        $posts = [
            [
                'slug' => 'kerja-bakti-bersih-lingkungan-bersama-warga-rt-21',
                'title' => 'Kerja Bakti Bersih Lingkungan Bersama Warga RT 21',
                'excerpt' => 'Puluhan anggota dan warga bergotong royong membersihkan saluran air dan fasilitas umum di lingkungan RT 21.',
                'body' => "Minggu pagi, puluhan anggota Arek Wetan Organizer bersama warga RT 21 menggelar kerja bakti membersihkan lingkungan.\n\nKegiatan difokuskan pada pembersihan saluran air, pemangkasan tanaman liar, dan perapian area bermain anak. Warga membawa peralatan masing-masing, sementara konsumsi disiapkan secara swadaya.\n\nKegiatan ini rencananya menjadi agenda rutin setiap bulan agar lingkungan tetap bersih dan nyaman.",
                'status' => 'published',
                'is_public' => true,
                'days_ago' => 2,
            ],
            [
                'slug' => 'lomba-agustusan-meriah-ratusan-warga-ikut-serta',
                'title' => 'Lomba Agustusan Meriah, Ratusan Warga Ikut Serta',
                'excerpt' => 'Berbagai lomba tradisional memeriahkan peringatan hari kemerdekaan bersama warga dari segala usia.',
                'body' => "Peringatan hari kemerdekaan tahun ini diisi dengan beragam lomba tradisional, mulai dari balap karung, tarik tambang, hingga makan kerupuk.\n\nPanitia dari Arek Wetan Organizer menyiapkan hadiah dan doorprize untuk para pemenang. Antusiasme warga terlihat sejak pagi, dari anak-anak sampai orang tua.\n\nTerima kasih kepada seluruh panitia, relawan, dan warga yang telah menyukseskan acara ini.",
                'status' => 'published',
                'is_public' => true,
                'days_ago' => 9,
            ],
            [
                'slug' => 'pelatihan-konten-kreatif-untuk-pelaku-umkm-lokal',
                'title' => 'Pelatihan Konten Kreatif untuk Pelaku UMKM Lokal',
                'excerpt' => 'Divisi kreatif berbagi tips membuat foto produk dan konten media sosial untuk membantu UMKM di sekitar.',
                'body' => "Divisi Kreatif mengadakan pelatihan singkat tentang pembuatan foto produk dan konten media sosial bagi pelaku UMKM lokal.\n\nPeserta belajar memanfaatkan ponsel untuk memotret produk, menyusun caption yang menarik, dan menjadwalkan unggahan secara konsisten.\n\nProgram ini bagian dari komitmen kolaborasi dengan mitra dan UMKM di lingkungan sekitar.",
                'status' => 'published',
                'is_public' => true,
                'days_ago' => 16,
            ],
            [
                'slug' => 'bakti-sosial-berbagi-takjil-dan-sembako',
                'title' => 'Bakti Sosial Berbagi Takjil dan Sembako',
                'excerpt' => 'Anggota menyalurkan paket sembako dan takjil kepada warga yang membutuhkan di sekitar lingkungan.',
                'body' => "Sebagai wujud kepedulian sosial, anggota Arek Wetan Organizer mengumpulkan donasi dan menyalurkannya dalam bentuk paket sembako serta takjil.\n\nPenyaluran dilakukan langsung ke rumah-rumah warga agar tepat sasaran. Seluruh dana dan penyaluran dicatat transparan oleh bendahara.",
                'status' => 'published',
                'is_public' => true,
                'days_ago' => 24,
            ],
            [
                'slug' => 'rapat-kerja-pengurus-menyusun-program-periode-babat-alas',
                'title' => 'Rapat Kerja Pengurus: Menyusun Program Periode Babat Alas',
                'excerpt' => 'Pengurus membahas rencana program kerja, pembagian tugas divisi, dan target kegiatan selama satu periode.',
                'body' => "Pengurus periode Babat Alas menggelar rapat kerja untuk menyusun program selama satu periode kepengurusan.\n\nPembahasan mencakup agenda kegiatan, pembagian tugas antardivisi, serta rencana anggaran. Hasil rapat akan dituangkan dalam dokumen program kerja yang dapat diakses anggota.",
                'status' => 'published',
                'is_public' => false, // terbit untuk anggota, belum dikurasi ke website publik
                'days_ago' => 30,
            ],
            [
                'slug' => 'rencana-turnamen-futsal-antar-rt',
                'title' => 'Rencana Turnamen Futsal Antar RT',
                'excerpt' => 'Draf rencana turnamen futsal persahabatan antar RT untuk mempererat kebersamaan warga.',
                'body' => "Draf: pengurus sedang menyiapkan rencana turnamen futsal persahabatan antar RT.\n\nDetail jadwal, lokasi, dan sistem pertandingan masih dalam pembahasan.",
                'status' => 'draft',
                'is_public' => false,
                'days_ago' => null,
            ],
        ];

        foreach ($posts as $p) {
            $published = $p['status'] === 'published';

            NewsPost::withTrashed()->updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'title' => $p['title'],
                    'excerpt' => $p['excerpt'],
                    'body' => $p['body'],
                    'status' => $p['status'],
                    'is_public' => $p['is_public'],
                    'published_at' => $published ? now()->subDays($p['days_ago']) : null,
                    'published_by' => $published ? $userId : null,
                    'created_by' => $userId,
                    'deleted_at' => null,
                ],
            );
        }
    }
}