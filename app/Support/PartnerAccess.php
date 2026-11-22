<?php

namespace App\Support;

use App\Models\User;

final class PartnerAccess
{
    /** Pengurus kemitraan: melihat data internal (kontak, catatan, kerja sama) dan seluruh mitra. */
    public static function viewer(User $user): bool
    {
        return $user->can('partners.view') || $user->can('partners.manage');
    }
}
