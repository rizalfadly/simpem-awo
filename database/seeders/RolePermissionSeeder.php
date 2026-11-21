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
        'Ketua'           => ['users.view', 'roles.view', 'audit.view', 'members.view', 'members.assign', 'organization.view', 'activities.view', 'activities.approve', 'content.publish', 'archive.view', 'finance.view', 'finance.approve', 'finance.publish', 'assets.view'],
        'Wakil Ketua'     => ['users.view', 'audit.view', 'members.view', 'organization.view', 'activities.view', 'archive.view', 'finance.view', 'finance.review', 'assets.view'],
        'Sekretaris'      => [
            'users.view', 'users.create', 'users.update', 'users.reset-password',
            'members.view', 'members.create', 'members.update', 'members.assign', 'members.activate-account',
            'organization.view', 'organization.manage',
            'activities.view', 'activities.create', 'activities.update', 'activities.delete',
            'gallery.manage', 'news.manage', 'content.publish', 'archive.view', 'archive.manage',
            'assets.view', 'assets.manage', 'assets.loans',
        ],
        'Bendahara'       => ['activities.view', 'finance.view', 'finance.record', 'assets.view'],   // izin keuangan menyusul di V1.4
        'Ketua Divisi'    => ['members.view', 'activities.view', 'activities.create'],
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
