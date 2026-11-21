<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Asset extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    public const STATUSES = ['active' => 'Aktif', 'retired' => 'Dipensiunkan'];

    public const CONDITIONS = [
        'good'   => 'Baik',
        'minor'  => 'Rusak ringan',
        'broken' => 'Rusak berat',
        'lost'   => 'Hilang',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date', 'retired_at' => 'datetime', 'is_loanable' => 'boolean',
            'qty_good' => 'integer', 'qty_minor' => 'integer', 'qty_broken' => 'integer', 'qty_lost' => 'integer',
            'acquisition_cost' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(AssetLoanItem::class);
    }

    public function total(): int
    {
        return $this->qty_good + $this->qty_minor + $this->qty_broken + $this->qty_lost;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canBeLoaned(): bool
    {
        return $this->isActive() && $this->is_loanable;
    }

    /** Jumlah yang sedang dibawa peminjam (sudah diserahkan, belum kembali). */
    public function lentQuantity(): int
    {
        return (int) $this->loanItems()->whereHas('loan', fn ($q) => $q->where('status', 'lent'))->sum('quantity');
    }

    /**
     * Stok yang masih bebas pada rentang tanggal tertentu.
     * Memotong: peminjaman 'approved' (dipesan) yang tanggalnya beririsan, dan 'lent' yang beririsan
     * atau sudah lewat jatuh tempo tetapi belum kembali (barangnya masih di luar).
     * Peminjaman 'requested' belum memesan stok.
     */
    public function availableFor(Carbon|string $from, Carbon|string $to, ?int $exceptLoanId = null): int
    {
        $from = Carbon::parse($from)->toDateString();
        $to = Carbon::parse($to)->toDateString();
        $today = today()->toDateString();

        $reserved = (int) AssetLoanItem::query()
            ->where('asset_id', $this->id)
            ->whereHas('loan', function ($q) use ($from, $to, $today, $exceptLoanId) {
                $q->when($exceptLoanId, fn ($q) => $q->where('id', '!=', $exceptLoanId))
                    ->where(function ($q) use ($from, $to, $today) {
                        $q->where(fn ($q) => $q->where('status', 'approved')->where('loan_date', '<=', $to)->where('due_date', '>=', $from))
                            ->orWhere(fn ($q) => $q->where('status', 'lent')->where('loan_date', '<=', $to)
                                ->where(fn ($q) => $q->where('due_date', '>=', $from)->orWhere('due_date', '<', $today)));
                    });
            })
            ->sum('quantity');

        return max(0, $this->qty_good - $reserved);
    }

    public function availableToday(): int
    {
        return $this->availableFor(today(), today());
    }

    public function auditLabel(): string
    {
        return "{$this->code} {$this->name}";
    }
}
