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
    /** Izin awal per role. Hanya diterapkan saat role baru dibuat; setelahnya diatur lewat UI. */
    private const DEFAULTS = [
        User::SUPER_ADMIN => [],                       // akses penuh via Gate::before
        'Ketua'           => ['users.view', 'roles.view', 'audit.view'],
        'Wakil Ketua'     => ['users.view', 'audit.view'],
        'Sekretaris'      => ['users.view', 'users.create', 'users.update', 'users.reset-password'],
        'Bendahara'       => [],                       // izin keuangan menyusul di V1.4
        'Ketua Divisi'    => [],
        'Anggota'         => [],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::names() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (self::DEFAULTS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            if ($role->wasRecentlyCreated && $permissions) {
                $role->syncPermissions($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
