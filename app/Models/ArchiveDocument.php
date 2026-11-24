<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveDocument extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const CATEGORIES = [
        'notulen'     => 'Notulen rapat',
        'surat_masuk' => 'Surat masuk',
        'surat_keluar' => 'Surat keluar',
        'sk'          => 'SK / keputusan',
        'proposal'    => 'Proposal',
        'laporan'     => 'Laporan',
        'lainnya'     => 'Lainnya',
    ];

    public const ACCESS = ['members' => 'Semua anggota', 'restricted' => 'Pengurus berizin'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['document_date' => 'date', 'size_bytes' => 'integer', 'is_public' => 'boolean'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('archive.view') || $user->can('archive.manage')) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->where('access', 'members')->orWhere('uploaded_by', $user->id));
    }
}