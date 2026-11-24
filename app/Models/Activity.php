<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const STATUSES = [
        'draft'     => 'Draf',
        'proposed'  => 'Diajukan',
        'approved'  => 'Disetujui',
        'rejected'  => 'Ditolak',
        'completed' => 'Selesai',
        'closed'    => 'Ditutup (LPJ diterima)',
        'cancelled' => 'Dibatalkan',
    ];

    /** Status yang boleh dilihat (ringkasannya) oleh semua anggota yang login. */
    public const PUBLIC_STATUSES = ['approved', 'completed', 'closed'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at'             => 'datetime',
            'ends_at'               => 'datetime',
            'proposal_submitted_at' => 'datetime',
            'decided_at'            => 'datetime',
            'completed_at'          => 'datetime',
            'is_public'             => 'boolean',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'pic_member_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by')->withTrashed();
    }

    public function committee(): HasMany
    {
        return $this->hasMany(ActivityCommitteeMember::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(ActivityTimeline::class);
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(ActivityBudgetItem::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(ActivityReport::class);
    }

    /** Kegiatan yang boleh dilihat user: semua bila punya izin, selain itu yang publik atau yang ia terlibat. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('activities.view') || $user->can('activities.update')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->whereIn('status', self::PUBLIC_STATUSES)->orWhere('created_by', $user->id);
            if ($user->member_id) {
                $q->orWhere('pic_member_id', $user->member_id)
                    ->orWhereHas('committee', fn ($c) => $c->where('member_id', $user->member_id));
            }
        });
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isPublic(): bool
    {
        return in_array($this->status, self::PUBLIC_STATUSES, true);
    }

    /** Proposal dan rencana anggaran hanya boleh diubah sebelum diajukan (atau setelah ditolak). */
    public function canEditProposal(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    /** Panitia dan timeline masih boleh diubah selama kegiatan belum selesai/dibatalkan. */
    public function isOpen(): bool
    {
        return in_array($this->status, ['draft', 'proposed', 'approved', 'rejected'], true);
    }

    public function hasCommitteeMember(?int $memberId): bool
    {
        return $memberId && $this->committee()->where('member_id', $memberId)->exists();
    }

    public function auditExcluded(): array
    {
        return ['description', 'proposal_background', 'proposal_objectives', 'created_at', 'updated_at', 'deleted_at'];
    }

    public function auditSensitive(): array
    {
        return ['description', 'proposal_background', 'proposal_objectives'];   // dicatat "(diubah)" tanpa isi
    }

    public function auditLabel(): string
    {
        return "{$this->code} {$this->title}";
    }
}