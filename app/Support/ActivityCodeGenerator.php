<?php

namespace App\Support;

use App\Models\Activity;
use Illuminate\Support\Facades\DB;

/** Kode kegiatan: KGT-{tahun pelaksanaan}-{urut 3 digit}, mis. KGT-2026-001. */
final class ActivityCodeGenerator
{
    public static function next(int $year): string
    {
        return DB::transaction(function () use ($year) {
            DB::table('activity_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

            $n = (int) DB::table('activity_sequences')->where('year', $year)->lockForUpdate()->value('last_number');

            do {
                $n++;
                $candidate = sprintf('KGT-%d-%03d', $year, $n);
            } while (Activity::withTrashed()->where('code', $candidate)->exists());

            DB::table('activity_sequences')->where('year', $year)->update(['last_number' => $n]);

            return $candidate;
        });
    }
}
