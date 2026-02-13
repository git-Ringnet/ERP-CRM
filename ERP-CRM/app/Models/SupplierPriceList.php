<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class SupplierPriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'supplier_id',
        'file_name',
        'effective_date',
        'expiry_date',
        'currency',
        'exchange_rate',
        'price_type',
        // Pricing configuration
        'supplier_discount_percent',
        'shipping_percent',
        'shipping_fixed',
        'margin_percent',
        'other_fees',
        'pricing_formula',
        'notes',
        'import_log',
        'custom_columns',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'supplier_discount_percent' => 'decimal:2',
        'shipping_percent' => 'decimal:2',
        'shipping_fixed' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'other_fees' => 'decimal:2',
        'pricing_formula' => 'array',
        'import_log' => 'array',
        'is_active' => 'boolean',
        'custom_columns' => 'array',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierPriceListItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForSupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function getPriceTypeLabelAttribute(): string
    {
        return match ($this->price_type) {
            'list' => 'Giá niêm yết',
            'partner' => 'Giá đối tác',
            'cost' => 'Giá gốc',
            default => 'Không xác định',
        };
    }

    public function isValid(): bool
    {
        if (!$this->is_active)
            return false;

        $today = now();
        if ($this->effective_date && $today->lt($this->effective_date))
            return false;
        if ($this->expiry_date && $today->gt($this->expiry_date))
            return false;

        return true;
    }

    /**
     * Calculate final price from base price using pricing configuration
     * Formula: Final = (BasePrice × (1 - SupplierDiscount%)) × (1 + Margin%) + ShipFixed + (BasePrice × Ship%) + OtherFees
     * 
     * @param float $basePrice Base price in original currency (usually USD)
     * @param bool $convertToVnd Whether to convert to VND using exchange rate
     * @return array ['base_price', 'after_discount', 'shipping', 'margin', 'other_fees', 'final_price', 'final_price_vnd']
     */
    public function calculateFinalPrice(float $basePrice, bool $convertToVnd = true): array
    {
        // Step 1: Apply supplier discount
        $discountAmount = $basePrice * ($this->supplier_discount_percent / 100);
        $afterDiscount = $basePrice - $discountAmount;

        // Step 2: Calculate shipping
        $shippingFromPercent = $afterDiscount * ($this->shipping_percent / 100);
        $totalShipping = $shippingFromPercent + ($this->shipping_fixed ?? 0);

        // Step 3: Apply margin/markup
        $marginAmount = $afterDiscount * ($this->margin_percent / 100);

        // Step 4: Add other fees
        $otherFees = $this->other_fees ?? 0;

        // Final price in original currency
        $finalPrice = $afterDiscount + $totalShipping + $marginAmount + $otherFees;

        // Convert to VND if needed
        $exchangeRate = $this->exchange_rate ?: 1;
        $finalPriceVnd = $finalPrice * $exchangeRate;

        return [
            'base_price' => $basePrice,
            'discount_percent' => $this->supplier_discount_percent,
            'discount_amount' => $discountAmount,
            'after_discount' => $afterDiscount,
            'shipping_percent' => $this->shipping_percent,
            'shipping_fixed' => $this->shipping_fixed,
            'total_shipping' => $totalShipping,
            'margin_percent' => $this->margin_percent,
            'margin_amount' => $marginAmount,
            'other_fees' => $otherFees,
            'final_price' => round($finalPrice, 2),
            'exchange_rate' => $exchangeRate,
            'final_price_vnd' => round($finalPriceVnd, 0),
            'currency' => $this->currency,
        ];
    }

    /**
     * Get pricing formula description
     */
    public function getPricingFormulaDescriptionAttribute(): string
    {
        $parts = [];

        $parts[] = 'Giá gốc';

        if ($this->supplier_discount_percent > 0) {
            $parts[] = "- CK NCC {$this->supplier_discount_percent}%";
        }

        if ($this->margin_percent > 0) {
            $parts[] = "+ Margin {$this->margin_percent}%";
        }

        if ($this->shipping_percent > 0 || $this->shipping_fixed > 0) {
            $ship = [];
            if ($this->shipping_percent > 0)
                $ship[] = "{$this->shipping_percent}%";
            if ($this->shipping_fixed > 0)
                $ship[] = "\${$this->shipping_fixed}";
            $parts[] = "+ Ship (" . implode(' + ', $ship) . ")";
        }

        if ($this->other_fees > 0) {
            $parts[] = "+ Phí khác \${$this->other_fees}";
        }

        return implode(' ', $parts) . ' = Giá bán';
    }

    public static function generateCode(int $supplierId): string
    {
        $supplier = Supplier::find($supplierId);
        $prefix = $supplier ? strtoupper(substr($supplier->code ?? $supplier->name, 0, 3)) : 'SPL';
        $date = date('Ymd');

        $last = static::where('code', 'like', "{$prefix}-{$date}-%")
            ->orderBy('code', 'desc')
            ->first();

        $number = $last ? intval(substr($last->code, -3)) + 1 : 1;

        return "{$prefix}-{$date}-" . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}
