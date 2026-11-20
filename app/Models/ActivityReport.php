<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReport extends Model
{
    use Auditable;

    public const STATUSES = [
        'draft'     => 'Draf',
        'submitted' => 'Menunggu review',
        'revision'  => 'Perlu revisi',
        'accepted'  => 'Diterima',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'realized_participants' => 'integer'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'revision'], true);
    }

    public function auditExcluded(): array
    {
        return ['summary', 'evaluation', 'created_at', 'updated_at'];
    }

    public function auditSensitive(): array
    {
        return ['summary', 'evaluation'];
    }

    public function auditLabel(): string
    {
        return 'LPJ '.($this->activity?->code ?? '#'.$this->activity_id);
    }
}
