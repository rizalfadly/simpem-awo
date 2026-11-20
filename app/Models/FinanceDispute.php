<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceDispute extends Model
{
    use Auditable;

    public const STATUSES = [
        'open'      => 'Menunggu klarifikasi bendahara',
        'clarified' => 'Menunggu review',
        'reviewed'  => 'Menunggu keputusan akhir',
        'upheld'    => 'Diterima (perlu koreksi)',
        'rejected'  => 'Ditolak',
        'withdrawn' => 'Dicabut',
    ];

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => 'Terbuka',
            'in_review' => 'Sedang Ditinjau',
            'resolved' => 'Selesai',
            'rejected' => 'Ditolak',
            default => ucfirst((string) $this->status),
        };
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['clarified_at' => 'datetime', 'reviewed_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(FinanceReport::class, 'report_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'transaction_id')->withTrashed();
    }

    public function filer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by')->withTrashed();
    }

    public function clarifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clarified_by')->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by')->withTrashed();
    }

    public function files(): HasMany
    {
        return $this->hasMany(FinanceDisputeFile::class, 'dispute_id');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['upheld', 'rejected', 'withdrawn'], true);
    }

    public function auditExcluded(): array
    {
        return ['reason', 'clarification', 'review_note', 'decision_note', 'created_at', 'updated_at'];
    }

    public function auditSensitive(): array
    {
        return ['reason', 'clarification', 'review_note', 'decision_note'];
    }

    public function auditLabel(): string
    {
        return 'Sanggahan #'.($this->id ?? '?');
    }
}
