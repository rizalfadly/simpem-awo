<?php

namespace App\Policies;

use App\Models\AssetLoan;
use App\Models\User;
use App\Support\InventoryAccess;

class AssetLoanPolicy
{
    public function view(User $user, AssetLoan $loan): bool
    {
        return InventoryAccess::viewer($user)
            || ($user->member_id !== null && $loan->borrower_member_id === $user->member_id)
            || $loan->requested_by === $user->id;
    }

    /** Peminjam atau pengaju boleh membatalkan miliknya. */
    public function owns(User $user, AssetLoan $loan): bool
    {
        return ($user->member_id !== null && $loan->borrower_member_id === $user->member_id) || $loan->requested_by === $user->id;
    }
}
