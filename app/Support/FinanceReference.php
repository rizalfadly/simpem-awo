<?php

namespace App\Support;

use App\Models\FinanceTransaction;
use Illuminate\Support\Facades\DB;

/** Nomor bukti transaksi: KM-{tahun}-{urut} untuk kas masuk, KK-{tahun}-{urut} untuk kas keluar. */
final class FinanceReference
{
    public static function next(string $type, int $year): string
    {
        $prefix = $type === 'income' ? 'KM' : 'KK';

        return DB::transaction(function () use ($type, $year, $prefix) {
            DB::table('finance_sequences')->insertOrIgnore(['year' => $year, 'type' => $type, 'last_number' => 0]);

            $n = (int) DB::table('finance_sequences')->where('year', $year)->where('type', $type)->lockForUpdate()->value('last_number');

            do {
                $n++;
                $candidate = sprintf('%s-%d-%04d', $prefix, $year, $n);
            } while (FinanceTransaction::withTrashed()->where('reference', $candidate)->exists());

            DB::table('finance_sequences')->where('year', $year)->where('type', $type)->update(['last_number' => $n]);

            return $candidate;
        });
    }
}
