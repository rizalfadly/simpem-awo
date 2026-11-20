<?php

namespace App\Policies;

use App\Models\FinanceDispute;
use App\Models\User;
use App\Support\FinanceAccess;

class FinanceDisputePolicy
{
    public function view(User $user, FinanceDispute $dispute): bool
    {
        return $dispute->filed_by === $user->id || FinanceAccess::internal($user);
    }
}
