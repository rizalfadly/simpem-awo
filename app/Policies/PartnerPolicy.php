<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;
use App\Support\PartnerAccess;

class PartnerPolicy
{
    /** Anggota hanya melihat mitra aktif; pengurus melihat semuanya. */
    public function view(User $user, Partner $partner): bool
    {
        return PartnerAccess::viewer($user) || $partner->isActive();
    }

    public function viewInternal(User $user, Partner $partner): bool
    {
        return PartnerAccess::viewer($user);
    }
}
