<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPromotion extends Model
{
    use Auditable;

    public const AUDIENCES = ['members' => 'Khusus anggota', 'public' => 'Umum (boleh tampil di website publik)'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'published_at' => 'datetime'];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class)->withTrashed();
    }

    /** Terbit dan belum berakhir. */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereDate('ends_on', '>=', today());
    }

    public function isCurrent(): bool
    {
        return $this->status === 'published' && $this->ends_on->gte(today());
    }

    public function phase(): string
    {
        return match (true) {
            $this->status !== 'published' => 'draft',
            $this->ends_on->lt(today())   => 'ended',
            $this->starts_on->gt(today()) => 'upcoming',
            default                       => 'running',
        };
    }

    public function auditLabel(): string
    {
        return $this->title;
    }
}
