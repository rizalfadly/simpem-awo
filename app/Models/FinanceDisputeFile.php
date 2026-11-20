<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FinanceDisputeFile extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::deleting(fn (FinanceDisputeFile $f) => Storage::disk('local')->delete($f->path));
    }

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(FinanceDispute::class, 'dispute_id');
    }
}
