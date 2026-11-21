<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetLoan extends Model
{
    use Auditable;

    public const STATUSES = [
        'requested' => 'Diajukan',
        'approved'  => 'Disetujui (barang dipesan)',
        'lent'      => 'Sedang dipinjam',
        'returned'  => 'Dikembalikan',
        'rejected'  => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date', 'due_date' => 'date',
            'approved_at' => 'datetime', 'lent_at' => 'datetime', 'returned_at' => 'datetime',
        ];
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'borrower_member_id')->withTrashed();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function handedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_by')->withTrashed();
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetLoanItem::class, 'loan_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'lent' && $this->due_date->lt(today());
    }

    /** Hari keterlambatan: untuk yang masih dipinjam = sampai hari ini; untuk yang sudah kembali = saat dikembalikan. */
    public function lateDays(): int
    {
        $end = $this->status === 'returned' ? $this->returned_at?->copy()->startOfDay() : ($this->status === 'lent' ? today() : null);

        return $end && $end->gt($this->due_date) ? (int) $this->due_date->diffInDays($end) : 0;
    }

    public function auditExcluded(): array
    {
        return ['created_at', 'updated_at'];
    }

    public function auditLabel(): string
    {
        return $this->code;
    }
}
