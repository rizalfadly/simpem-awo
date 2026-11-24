<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Member extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const ACTIVE = 'active';

    public const STATUSES = [
        'active'   => 'Aktif',
        'inactive' => 'Nonaktif',
        'alumni'   => 'Alumni',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'birth_date'        => 'date',
            'joined_at'         => 'date',
            'status_changed_at' => 'datetime',
            'public_profile'    => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $m) {
            $m->qr_token ??= Str::random(40);
        });
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MemberPosition::class)->orderByRaw('ended_at is not null')->orderByDesc('started_at');
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(MemberPosition::class)->whereNull('ended_at');
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Butuh relasi activeAssignments.position & activeAssignments.division sudah di-load. */
    public function positionLabels(): array
    {
        return $this->activeAssignments
            ->map(fn (MemberPosition $a) => $a->position->name.($a->division ? ' - '.$a->division->name : ''))
            ->values()
            ->all();
    }

    public function auditExcluded(): array
    {
        return ['qr_token', 'created_at', 'updated_at', 'deleted_at'];
    }

    public function auditSensitive(): array
    {
        return ['qr_token'];
    }

    public function auditLabel(): string
    {
        return "{$this->name} ({$this->nomor_anggota})";
    }
}