<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_id',
        'rate',
        'buy_rate',
        'sell_rate',
        'effective_date',
        'source',
        'created_by',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'buy_rate' => 'decimal:6',
        'sell_rate' => 'decimal:6',
        'effective_date' => 'date',
    ];

    // ─── Relationships ───────────────────────────────────────

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Scopes ──────────────────────────────────────────────

    /**
     * Lọc theo tiền tệ
     */
    public function scopeForCurrency(Builder $query, int $currencyId): Builder
    {
        return $query->where('currency_id', $currencyId);
    }

    /**
     * Lọc theo ngày
     */
    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->where('effective_date', Carbon::parse($date)->toDateString());
    }

    /**
     * Lấy tỷ giá gần nhất <= ngày cho trước (Fallback)
     */
    public function scopeLatestBefore(Builder $query, $date): Builder
    {
        return $query->where('effective_date', '<=', Carbon::parse($date)->toDateString())
                     ->orderByDesc('effective_date');
    }

    // ─── Static Helpers ──────────────────────────────────────

    /**
     * Tìm tỷ giá cho 1 loại tiền tệ tại 1 ngày cụ thể
     * Nếu không có ngày đó, lấy tỷ giá gần nhất trước đó (Fallback)
     */
    public static function getRateForDate(int $currencyId, $date): ?self
    {
        return static::forCurrency($currencyId)
            ->latestBefore($date)
            ->first();
    }

    /**
     * Lấy tỷ giá (số) cho 1 loại tiền tệ tại 1 ngày
     * Trả về 1.0 nếu là VND hoặc không tìm thấy
     */
    public static function getRateValue(int $currencyId, $date): float
    {
        $rate = static::getRateForDate($currencyId, $date);
        return $rate ? (float) $rate->rate : 1.0;
    }

    /**
     * Lấy label nguồn tỷ giá
     */
    public function getSourceLabelAttribute(): string
    {
        return match($this->source) {
            'auto' => 'Tự động (Vietcombank)',
            'manual' => 'Nhập thủ công',
            default => $this->source,
        };
    }
}
