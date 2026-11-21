<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLoanItem extends Model
{
    protected $guarded = ['id'];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(AssetLoan::class, 'loan_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class)->withTrashed();
    }
}
