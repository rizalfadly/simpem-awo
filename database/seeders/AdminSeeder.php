<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $nomor = strtoupper(env('SIMPEM_ADMIN_NOMOR', 'AWO-0001'));

        $member = Member::withTrashed()->firstOrCreate(
            ['nomor_anggota' => $nomor],
            ['name' => 'Administrator', 'joined_at' => today(), 'status' => Member::ACTIVE, 'status_changed_at' => now()],
        );

        $admin = User::withTrashed()->firstOrCreate(
            ['nomor_anggota' => $nomor],
            [
                'member_id'            => $member->id,
                'name'                 => 'Administrator',
                'password'             => env('SIMPEM_ADMIN_PASSWORD', 'GantiSandi2026'),
                'is_active'            => true,
                'must_change_password' => true,
            ],
        );

        if (! $admin->member_id) {
            $admin->update(['member_id' => $member->id]);
        }

        $admin->assignRole(User::SUPER_ADMIN);
    }
}
