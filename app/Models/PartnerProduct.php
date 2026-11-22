<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PartnerProduct extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'is_available' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleting(function (PartnerProduct $p) {
            foreach ([$p->photo_path, $p->photo_thumb_path] as $path) {
                $path && Storage::disk('local')->delete($path);
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class)->withTrashed();
    }
}
