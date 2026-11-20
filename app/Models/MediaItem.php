<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Foto atau video dalam album. Audit dicatat per batch di tingkat album, bukan per berkas. */
class MediaItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleting(function (MediaItem $m) {
            $disk = Storage::disk('local');
            foreach ([$m->file_path, $m->thumb_path] as $path) {
                if ($path) {
                    $disk->delete($path);
                }
            }
        });
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }
}
