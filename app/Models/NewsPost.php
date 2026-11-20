<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NewsPost extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const STATUSES = Album::STATUSES;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('news.manage') || $user->can('content.publish')) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->where('status', 'published')->orWhere('created_by', $user->id));
    }

    public static function uniqueSlug(string $title, ?int $exceptId = null): string
    {
        $base = Str::slug($title) ?: 'berita';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function auditExcluded(): array
    {
        return ['body', 'created_at', 'updated_at', 'deleted_at'];
    }

    public function auditSensitive(): array
    {
        return ['body'];
    }

    public function auditLabel(): string
    {
        return $this->title;
    }
}
