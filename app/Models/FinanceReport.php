<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class FinanceReport extends Model
{
    use Auditable;

    public const STATUSES = [
        'draft'     => 'Draf',
        'submitted' => 'Menunggu review',
        'reviewed'  => 'Menunggu persetujuan',
        'revision'  => 'Perlu revisi',
        'approved'  => 'Disetujui',
        'published' => 'Dipublikasikan',
    ];

    public const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'integer', 'total_income' => 'integer', 'total_expense' => 'integer', 'closing_balance' => 'integer',
            'submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'approved_at' => 'datetime', 'published_at' => 'datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'report_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinanceReportEvent::class, 'report_id')->orderBy('id');
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(FinanceDispute::class, 'report_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by')->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by')->withTrashed();
    }

    /** Laporan bulan dari tanggal tersebut; dibuat (draf) bila belum ada. */
    public static function forDate(\DateTimeInterface|string $date): static
    {
        $d = Carbon::parse($date);

        return static::firstOrCreate(['year' => $d->year, 'month' => $d->month], ['status' => 'draft']);
    }

    public function label(): string
    {
        return self::MONTHS[$this->month].' '.$this->year;
    }

    public function start(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->startOfDay();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Transaksi hanya boleh diubah saat draf atau perlu revisi. */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'revision'], true);
    }

    /** Angka dari data saat ini: saldo awal = seluruh transaksi sebelum bulan ini. */
    public function liveTotals(): array
    {
        $start = $this->start()->toDateString();
        $sum = fn ($q, string $type) => (int) $q->where('type', $type)->sum('amount');

        $opening = $sum(FinanceTransaction::query()->where('transaction_date', '<', $start), 'income')
            - $sum(FinanceTransaction::query()->where('transaction_date', '<', $start), 'expense');
        $income = $sum($this->transactions(), 'income');
        $expense = $sum($this->transactions(), 'expense');

        return ['opening' => $opening, 'income' => $income, 'expense' => $expense, 'closing' => $opening + $income - $expense];
    }

    /** Draf/revisi memakai angka hidup; setelah diajukan memakai angka yang dibekukan. */
    public function totals(): array
    {
        if (! $this->isEditable() && $this->closing_balance !== null) {
            return [
                'opening' => $this->opening_balance, 'income' => $this->total_income,
                'expense' => $this->total_expense, 'closing' => $this->closing_balance,
            ];
        }

        return $this->liveTotals();
    }

    public function freezeTotals(): void
    {
        $t = $this->liveTotals();
        $this->update([
            'opening_balance' => $t['opening'], 'total_income' => $t['income'],
            'total_expense' => $t['expense'], 'closing_balance' => $t['closing'],
        ]);
    }

    public function auditLabel(): string
    {
        return 'Laporan '.$this->label();
    }
}
