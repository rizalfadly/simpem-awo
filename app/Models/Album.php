<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Album extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const STATUSES = ['draft' => 'Draf', 'published' => 'Dipublikasikan', 'archived' => 'Diarsipkan'];

    /** [dari => [ke...]] */
    public const TRANSITIONS = [
        'draft'     => ['published', 'archived'],
        'published' => ['draft', 'archived'],
        'archived'  => ['draft'],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'published_at' => 'datetime'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by')->withTrashed();
    }

    /** Semua album untuk pengelola/penerbit; selain itu yang terbit atau yang ia terlibat. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('gallery.manage') || $user->can('content.publish')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('status', 'published')->orWhere('created_by', $user->id);
            if ($user->member_id) {
                $q->orWhereHas('activity', fn ($a) => $a
                    ->where('pic_member_id', $user->member_id)
                    ->orWhereHas('committee', fn ($c) => $c->where('member_id', $user->member_id)));
            }
        });
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function auditLabel(): string
    {
        return $this->title;
    }
}
