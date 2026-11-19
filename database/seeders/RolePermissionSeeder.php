<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Izin awal per role (kumulatif semua versi).
     * - Role baru: menerima semua izin di sini.
     * - Role lama: hanya menerima izin yang BARU dibuat pada eksekusi ini,
     *   sehingga perubahan yang sudah Anda atur lewat UI tidak ditimpa.
     */
    private const DEFAULTS = [
        User::SUPER_ADMIN => [],   // akses penuh via Gate::before
        'Ketua'           => ['users.view', 'roles.view', 'audit.view', 'members.view', 'members.assign', 'organization.view'],
        'Wakil Ketua'     => ['users.view', 'audit.view', 'members.view', 'organization.view'],
        'Sekretaris'      => [
            'users.view', 'users.create', 'users.update', 'users.reset-password',
            'members.view', 'members.create', 'members.update', 'members.assign', 'members.activate-account',
            'organization.view', 'organization.manage',
        ],
        'Bendahara'       => [],   // izin keuangan menyusul di V1.4
        'Ketua Divisi'    => ['members.view'],
        'Anggota'         => [],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = [];
        foreach (PermissionCatalog::names() as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            if ($permission->wasRecentlyCreated) {
                $created[] = $name;
            }
        }

        foreach (self::DEFAULTS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $grant = $role->wasRecentlyCreated ? $permissions : array_values(array_intersect($permissions, $created));
            if ($grant) {
                $role->givePermissionTo($grant);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
