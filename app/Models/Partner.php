<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Partner extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const TYPES = [
        'umkm'        => 'UMKM',
        'institution' => 'Instansi / lembaga',
        'sponsor'     => 'Sponsor / donatur',
        'community'   => 'Komunitas',
        'other'       => 'Lainnya',
    ];

    public const STATUSES = ['active' => 'Aktif', 'inactive' => 'Tidak aktif'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['joined_at' => 'date', 'is_public' => 'boolean'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function products(): HasMany
    {
        return $this->hasMany(PartnerProduct::class)->orderBy('sort_order')->orderBy('id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(PartnerPromotion::class);
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(Partnership::class);
    }

    /** Pengurus kemitraan melihat semua; anggota biasa hanya mitra aktif. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return \App\Support\PartnerAccess::viewer($user) ? $query : $query->where('status', 'active');
    }

    public static function uniqueSlug(string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name) ?: 'mitra';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Data kontak pribadi dicatat sebagai "(diubah)" di audit log, tanpa nilainya. */
    public function auditExcluded(): array
    {
        return ['contact_person', 'contact_phone', 'contact_email', 'notes', 'created_at', 'updated_at', 'deleted_at'];
    }

    public function auditSensitive(): array
    {
        return ['contact_person', 'contact_phone', 'contact_email', 'notes'];
    }
}
