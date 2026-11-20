<?php

namespace App\Policies;

use App\Models\FinanceTransaction;
use App\Models\User;
use App\Support\FinanceAccess;

class FinanceTransactionPolicy
{
    public function view(User $user, FinanceTransaction $tx): bool
    {
        return $tx->report->status === 'published'
            || FinanceAccess::internal($user)
            || $tx->recorded_by === $user->id;
    }

    public function viewHistory(User $user, FinanceTransaction $tx): bool
    {
        return FinanceAccess::internal($user);
    }
}
