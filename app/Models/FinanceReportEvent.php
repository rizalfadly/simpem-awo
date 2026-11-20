<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/** Jejak alur kerja laporan (pencatat, reviewer, approver, penerbit). Hanya-tambah. */
class FinanceReportEvent extends Model
{
    public $timestamps = false;

    public const ACTIONS = [
        'submitted'            => 'Diajukan oleh bendahara',
        'reviewed'             => 'Direview, diteruskan ke Ketua',
        'returned_by_reviewer' => 'Dikembalikan oleh reviewer',
        'approved'             => 'Disetujui',
        'returned_by_approver' => 'Dikembalikan oleh penyetuju',
        'published'            => 'Dipublikasikan',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public static function log(FinanceReport $report, string $action, ?string $note = null): self
    {
        $user = Auth::user();

        return static::create([
            'report_id'  => $report->id,
            'user_id'    => $user?->id,
            'user_label' => $user ? "{$user->name} ({$user->nomor_anggota})" : null,
            'action'     => $action,
            'note'       => $note,
        ]);
    }
}
