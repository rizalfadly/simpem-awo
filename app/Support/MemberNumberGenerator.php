<?php

namespace App\Support;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Nomor anggota otomatis: AWO-{tahun bergabung}-{urutan 4 digit}, mis. AWO-2026-0001.
 * Urutan per tahun, aman terhadap pendaftaran bersamaan (row lock) dan tidak memakai ulang nomor lama.
 * Ubah format di satu tempat: method format().
 */
final class MemberNumberGenerator
{
    public static function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($year) {
            DB::table('member_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

            $n = (int) DB::table('member_sequences')->where('year', $year)->lockForUpdate()->value('last_number');

            do {
                $n++;
                $candidate = self::format($year, $n);
            } while (
                Member::withTrashed()->where('nomor_anggota', $candidate)->exists()
                || User::withTrashed()->where('nomor_anggota', $candidate)->exists()
            );

            DB::table('member_sequences')->where('year', $year)->update(['last_number' => $n]);

            return $candidate;
        });
    }

    public static function format(int $year, int $sequence): string
    {
        return sprintf('AWO-%d-%04d', $year, $sequence);
    }
}
