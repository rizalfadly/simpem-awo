<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use Auditable;

    public const SCOPES = [
        'organization' => 'Tingkat organisasi',
        'division'     => 'Tingkat divisi',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'max_holders' => 'integer', 'sort_order' => 'integer'];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MemberPosition::class);
    }
}
