<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $nomor = strtoupper(env('SIMPEM_ADMIN_NOMOR', 'AWO-0001'));

        $admin = User::withTrashed()->firstOrCreate(
            ['nomor_anggota' => $nomor],
            [
                'name'                 => 'Administrator',
                'password'             => env('SIMPEM_ADMIN_PASSWORD', 'GantiSandi2026'),
                'is_active'            => true,
                'must_change_password' => true,
            ],
        );

        $admin->assignRole(User::SUPER_ADMIN);
    }
}
