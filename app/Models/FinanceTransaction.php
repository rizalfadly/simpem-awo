<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceTransaction extends Model
{
    use SoftDeletes, Auditable;

    public const TYPES = ['income' => 'Kas masuk', 'expense' => 'Kas keluar'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'amount' => 'integer'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(FinanceReport::class, 'report_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by')->withTrashed();
    }

    public function hasProof(): bool
    {
        return (bool) $this->proof_path;
    }

    public function auditLabel(): string
    {
        return "{$this->reference} {$this->description}";
    }
}
