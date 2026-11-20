<?php

namespace App\Support;

/**
 * Sumber tunggal daftar permission SIMPEM.
 * Setiap versi roadmap menambah grup baru di sini, lalu jalankan:
 *   php artisan db:seed --class=RolePermissionSeeder
 */
final class PermissionCatalog
{
    public static function groups(): array
    {
        return [
            [
                'label' => 'Keanggotaan',
                'items' => [
                    'members.view'             => 'Melihat daftar dan profil anggota',
                    'members.create'           => 'Menambah anggota',
                    'members.update'           => 'Mengubah data, status, dan QR anggota',
                    'members.assign'           => 'Menetapkan jabatan dan divisi anggota',
                    'members.activate-account' => 'Mengaktifkan akun anggota',
                    'members.delete'           => 'Menghapus anggota',
                ],
            ],
            [
                'label' => 'Struktur Organisasi',
                'items' => [
                    'organization.view'   => 'Melihat data divisi, jabatan, dan periode',
                    'organization.manage' => 'Mengelola divisi, jabatan, dan periode kepengurusan',
                ],
            ],
            [
                'label' => 'Kegiatan',
                'items' => [
                    'activities.view'    => 'Melihat seluruh kegiatan beserta proposal, anggaran, dan LPJ',
                    'activities.create'  => 'Membuat kegiatan',
                    'activities.update'  => 'Mengubah kegiatan mana pun (selain PIC dan pembuatnya)',
                    'activities.approve' => 'Menyetujui/menolak proposal dan menerima/merevisi LPJ',
                    'activities.delete'  => 'Menghapus kegiatan berstatus draf atau dibatalkan',
                ],
            ],
            [
                'label' => 'Dokumentasi & Publikasi',
                'items' => [
                    'gallery.manage'  => 'Mengelola semua album, foto, dan video',
                    'news.manage'     => 'Menulis dan mengelola berita organisasi',
                    'content.publish' => 'Menerbitkan / menarik album dan berita',
                    'archive.view'    => 'Melihat dokumen arsip terbatas',
                    'archive.manage'  => 'Mengelola arsip dokumen',
                ],
            ],
            [
                'label' => 'Akun Pengguna',
                'items' => [
                    'users.view'           => 'Melihat daftar akun',
                    'users.create'         => 'Membuat akun',
                    'users.update'         => 'Mengubah peran dan status aktif akun',
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
