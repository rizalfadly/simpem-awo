<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        if (Position::query()->exists()) {
            return;   // jangan menimpa jabatan yang sudah diatur
        }

        // [nama, tingkat, maks. pemegang per periode (dan divisi), urutan]
        foreach ([
            ['Ketua',          'organization', 1, 10],
            ['Wakil Ketua',    'organization', 1, 20],
            ['Sekretaris',     'organization', 1, 30],
            ['Bendahara',      'organization', 1, 40],
            ['Ketua Divisi',   'division',     1, 50],
            ['Anggota Divisi', 'division',     null, 60],
        ] as [$name, $scope, $max, $order]) {
            Position::create(['name' => $name, 'scope' => $scope, 'max_holders' => $max, 'sort_order' => $order, 'is_active' => true]);
        }
    }
}
