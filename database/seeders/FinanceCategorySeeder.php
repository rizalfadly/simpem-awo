<?php

namespace Database\Seeders;

use App\Models\FinanceCategory;
use Illuminate\Database\Seeder;

class FinanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (FinanceCategory::query()->exists()) {
            return;   // jangan menimpa kategori yang sudah diatur
        }

        $income = ['Saldo awal', 'Iuran anggota', 'Donasi / sponsor', 'Dana kegiatan', 'Hasil usaha', 'Lain-lain'];
        $expense = ['Konsumsi', 'Perlengkapan', 'Transportasi', 'Publikasi & dokumentasi', 'Sewa tempat', 'Honor / apresiasi', 'Operasional sekretariat', 'Lain-lain'];

        foreach ($income as $i => $name) {
            FinanceCategory::create(['name' => $name, 'type' => 'income', 'sort_order' => ($i + 1) * 10, 'is_active' => true]);
        }
        foreach ($expense as $i => $name) {
            FinanceCategory::create(['name' => $name, 'type' => 'expense', 'sort_order' => ($i + 1) * 10, 'is_active' => true]);
        }
    }
}
