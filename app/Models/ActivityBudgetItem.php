<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityBudgetItem extends Model
{
    use Auditable;

    public const TYPES = ['income' => 'Sumber dana', 'expense' => 'Pengeluaran'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity'       => 'float',
            'unit_price'     => 'integer',
            'planned_amount' => 'integer',
            'actual_amount'  => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Total rencana selalu dihitung server, tidak dipercaya dari klien.
        static::saving(function (ActivityBudgetItem $item) {
            $item->planned_amount = (int) round(((float) $item->quantity) * ((int) $item->unit_price));
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function auditLabel(): string
    {
        return "{$this->category}: {$this->description}";
    }
}
