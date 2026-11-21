<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Support\InventoryAccess;

/** Pengurus inventaris melihat semuanya; anggota biasa hanya barang aktif yang bisa dipinjam. */
class AssetPolicy
{
    public function view(User $user, Asset $asset): bool
    {
        return InventoryAccess::viewer($user) || $asset->canBeLoaned();
    }

    public function viewDetails(User $user, Asset $asset): bool
    {
        return InventoryAccess::viewer($user);
    }
}
