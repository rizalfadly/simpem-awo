<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partnership extends Model
{
    use SoftDeletes, Auditable;

    public const TYPES = [
        'sponsorship' => 'Sponsorship',
        'mou'         => 'MoU / nota kesepahaman',
        'barter'      => 'Barter / pertukaran manfaat',
        'event'       => 'Kerja sama kegiatan',
        'other'       => 'Lainnya',
    ];

    public const STATUSES = [
        'draft'      => 'Draf',
        'active'     => 'Berjalan',
        'completed'  => 'Selesai',
        'terminated' => 'Dihentikan',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'signed_on' => 'date', 'value' => 'integer'];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class)->withTrashed();
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'pic_member_id')->withTrashed();
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    /** Berjalan tetapi tanggal berakhirnya sudah lewat: perlu diselesaikan atau diperpanjang. */
    public function isLapsed(): bool
    {
        return $this->status === 'active' && $this->ends_on !== null && $this->ends_on->lt(today());
    }

    public function expiresSoon(int $days = 30): bool
    {
        return $this->status === 'active' && $this->ends_on !== null
            && $this->ends_on->betweenIncluded(today(), today()->addDays($days));
    }

    public function auditExcluded(): array
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    public function auditLabel(): string
    {
        return $this->title;
    }
}
