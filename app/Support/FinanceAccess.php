<?php

namespace App\Support;

use App\Models\User;

final class FinanceAccess
{
    public const PERMISSIONS = ['finance.view', 'finance.record', 'finance.review', 'finance.approve', 'finance.publish'];

    /** Pengurus keuangan: boleh melihat draf, buku kas, dan riwayat. Anggota biasa hanya melihat laporan terbit. */
    public static function internal(User $user): bool
    {
        foreach (self::PERMISSIONS as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function separationEnforced(): bool
    {
        return (bool) config('simpem.finance.separation_of_duties', true);
    }
}
