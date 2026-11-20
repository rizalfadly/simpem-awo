<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTimeline extends Model
{
    use Auditable;

    public const KINDS = ['preparation' => 'Persiapan', 'rundown' => 'Rundown acara'];

    public const STATUSES = ['todo' => 'Belum', 'doing' => 'Dikerjakan', 'done' => 'Selesai'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'pic_member_id')->withTrashed();
    }

    public function auditLabel(): string
    {
        return $this->title;
    }
}
