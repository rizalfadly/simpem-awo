<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (AssetCategory::query()->exists()) {
            return;   // jangan menimpa kategori yang sudah diatur
        }

        foreach (['Elektronik & audio', 'Perlengkapan acara', 'Furnitur', 'Olahraga', 'Alat tulis & kantor', 'Dokumentasi (kamera, dll.)', 'Lain-lain'] as $name) {
            AssetCategory::create(['name' => $name, 'is_active' => true]);
        }
    }
}
