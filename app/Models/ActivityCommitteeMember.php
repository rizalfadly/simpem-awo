<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityCommitteeMember extends Model
{
    use Auditable;

    /** Urutan = urutan tampil. */
    public const ROLES = [
        'leader'      => 'Ketua Pelaksana',
        'deputy'      => 'Wakil Ketua Pelaksana',
        'secretary'   => 'Sekretaris',
        'treasurer'   => 'Bendahara',
        'coordinator' => 'Koordinator Seksi',
        'member'      => 'Anggota Pelaksana',
    ];

    protected $guarded = ['id'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public static function roleRank(string $role): int
    {
        $i = array_search($role, array_keys(self::ROLES), true);

        return $i === false ? 99 : $i;
    }

    public function auditLabel(): string
    {
        return ($this->member?->name ?? '?').' - '.(self::ROLES[$this->role] ?? $this->role);
    }
}
