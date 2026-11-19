<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, Auditable;

    public const SUPER_ADMIN = 'Super Admin';

    protected $fillable = [
        'nomor_anggota',
        'name',
        'email',
        'password',
        'is_active',
        'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'is_active'            => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at'        => 'datetime',
        ];
    }

    /** Nomor anggota selalu disimpan huruf besar & tanpa spasi pinggir. */
    protected function nomorAnggota(): Attribute
    {
        return Attribute::set(fn ($value) => strtoupper(trim((string) $value)));
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::SUPER_ADMIN);
    }

    public function auditLabel(): string
    {
        return "{$this->name} ({$this->nomor_anggota})";
    }
}
