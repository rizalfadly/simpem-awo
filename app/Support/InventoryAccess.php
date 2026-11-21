<?php

namespace App\Support;

use App\Models\User;

final class InventoryAccess
{
    /** Pengurus inventaris: melihat seluruh data barang (termasuk nilai, kondisi, riwayat). */
    public static function viewer(User $user): bool
    {
        return $user->can('assets.view') || $user->can('assets.manage') || $user->can('assets.loans');
    }

    public static function separationEnforced(): bool
    {
        return (bool) config('simpem.assets.separation_of_duties', true);
    }
}
