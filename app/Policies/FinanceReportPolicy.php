<?php

namespace App\Policies;

use App\Models\FinanceReport;
use App\Models\User;
use App\Support\FinanceAccess;

/** Laporan terbit: semua anggota. Draf & proses persetujuan: pengurus keuangan saja. */
class FinanceReportPolicy
{
    public function view(User $user, FinanceReport $report): bool
    {
        return $report->status === 'published' || FinanceAccess::internal($user);
    }

    public function viewHistory(User $user, FinanceReport $report): bool
    {
        return FinanceAccess::internal($user);
    }
}
