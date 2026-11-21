<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetLoan;
use Illuminate\Support\Facades\DB;

/** Kode barang AST-0001 dan kode peminjaman PJM-2026-0001. Aman terhadap permintaan bersamaan (row lock). */
final class AssetCodes
{
    public static function asset(): string
    {
        return self::next('asset', fn (int $n) => sprintf('AST-%04d', $n), fn (string $c) => Asset::withTrashed()->where('code', $c)->exists());
    }

    public static function loan(int $year): string
    {
        return self::next("loan-{$year}", fn (int $n) => sprintf('PJM-%d-%04d', $year, $n), fn (string $c) => AssetLoan::where('code', $c)->exists());
    }

    private static function next(string $scope, callable $format, callable $exists): string
    {
        return DB::transaction(function () use ($scope, $format, $exists) {
            DB::table('asset_sequences')->insertOrIgnore(['scope' => $scope, 'last_number' => 0]);

            $n = (int) DB::table('asset_sequences')->where('scope', $scope)->lockForUpdate()->value('last_number');

            do {
                $n++;
                $candidate = $format($n);
            } while ($exists($candidate));

            DB::table('asset_sequences')->where('scope', $scope)->update(['last_number' => $n]);

            return $candidate;
        });
    }
}
