<?php

namespace App\Support;

/**
 * Sumber tunggal daftar permission SIMPEM.
 * Setiap versi roadmap menambah grup baru di sini (mis. V1.4 -> 'finance.*'),
 * lalu jalankan: php artisan db:seed --class=RolePermissionSeeder
 */
final class PermissionCatalog
{
    public static function groups(): array
    {
        return [
            [
                'label' => 'Akun Pengguna',
                'items' => [
                    'users.view'           => 'Melihat daftar akun',
                    'users.create'         => 'Membuat akun',
                    'users.update'         => 'Mengubah akun, peran, dan status aktif',
                    'users.reset-password' => 'Reset kata sandi akun',
                    'users.delete'         => 'Menghapus akun',
                ],
            ],
            [
                'label' => 'Role & Permission',
                'items' => [
                    'roles.view'   => 'Melihat daftar role',
                    'roles.manage' => 'Membuat, mengubah, dan menghapus role',
                ],
            ],
            [
                'label' => 'Audit Log',
                'items' => [
                    'audit.view' => 'Melihat audit log aktivitas',
                ],
            ],
        ];
    }

    /** @return string[] */
    public static function names(): array
    {
        return collect(self::groups())->flatMap(fn ($g) => array_keys($g['items']))->all();
    }

    /** Format untuk frontend: [{label, items:[{name,label}]}] */
    public static function forFrontend(): array
    {
        return collect(self::groups())->map(fn ($g) => [
            'label' => $g['label'],
            'items' => collect($g['items'])->map(fn ($label, $name) => compact('name', 'label'))->values()->all(),
        ])->all();
    }
}
