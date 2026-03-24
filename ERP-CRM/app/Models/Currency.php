<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'name_vi',
        'symbol',
        'decimal_places',
        'is_base',
        'is_active',
        'symbol_position',
        'sort_order',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    // ─── Scopes ──────────────────────────────────────────────

    /**
     * Chỉ lấy tiền tệ đang hoạt động
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Lấy tiền tệ cơ sở (VND)
     */
    public function scopeBase(Builder $query): Builder
    {
        return $query->where('is_base', true);
    }

    /**
     * Lấy các ngoại tệ (không phải base)
     */
    public function scopeForeign(Builder $query): Builder
    {
        return $query->where('is_base', false);
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * Kiểm tra có phải tiền tệ cơ sở (VND) không
     */
    public function isBase(): bool
    {
        return $this->is_base;
    }

    /**
     * Kiểm tra có phải ngoại tệ không
     */
    public function isForeign(): bool
    {
        return !$this->is_base;
    }

    /**
     * Format số tiền theo chuẩn tiền tệ
     * VD: $1,000.00 hoặc 25,400,000 ₫
     */
    public function format(float $amount): string
    {
        $formatted = number_format(
            $amount,
            $this->decimal_places,
            $this->decimal_places > 0 ? '.' : '',
            ','
        );

        if ($this->symbol_position === 'before') {
            return $this->symbol . $formatted;
        }

        return $formatted . ' ' . $this->symbol;
    }

    /**
     * Lấy label hiển thị: "USD - Đô la Mỹ"
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->code} - {$this->name_vi}";
    }

    /**
     * Cache: lấy VND currency
     */
    public static function getBaseCurrency(): ?self
    {
        return static::base()->first();
    }

    /**
     * Cache: lấy VND ID
     */
    public static function getBaseCurrencyId(): ?int
    {
        return static::base()->value('id');
    }
}
