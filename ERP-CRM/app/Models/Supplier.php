<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'email', 'phone', 'address', 'tax_code',
        'website', 'contact_person', 'payment_terms', 'product_type',
        'base_discount', 'volume_discount', 'volume_threshold',
        'early_payment_discount', 'early_payment_days',
        'special_discount', 'special_discount_condition', 'note',
    ];

    protected $casts = [
        'payment_terms' => 'integer',
        'base_discount' => 'decimal:2',
        'volume_discount' => 'decimal:2',
        'volume_threshold' => 'integer',
        'early_payment_discount' => 'decimal:2',
        'early_payment_days' => 'integer',
        'special_discount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchasePricings(): HasMany
    {
        return $this->hasMany(PurchasePricing::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    public function getTotalMaxDiscountAttribute(): float
    {
        return $this->base_discount + $this->volume_discount + 
               $this->early_payment_discount + $this->special_discount;
    }

    public function calculateDiscount(int $quantity, bool $earlyPayment = false, bool $specialCondition = false): float
    {
        $discount = $this->base_discount;

        if ($quantity >= $this->volume_threshold && $this->volume_threshold > 0) {
            $discount += $this->volume_discount;
        }

        if ($earlyPayment) {
            $discount += $this->early_payment_discount;
        }

        if ($specialCondition) {
            $discount += $this->special_discount;
        }

        return $discount;
    }

    public function getDiscountPolicySummary(): array
    {
        return [
            'base' => [
                'rate' => $this->base_discount,
                'description' => 'Áp dụng cho mọi đơn hàng'
            ],
            'volume' => [
                'rate' => $this->volume_discount,
                'threshold' => $this->volume_threshold,
                'description' => "Áp dụng khi đặt từ {$this->volume_threshold} sản phẩm"
            ],
            'early_payment' => [
                'rate' => $this->early_payment_discount,
                'days' => $this->early_payment_days,
                'description' => "Thanh toán trong {$this->early_payment_days} ngày"
            ],
            'special' => [
                'rate' => $this->special_discount,
                'condition' => $this->special_discount_condition,
                'description' => $this->special_discount_condition ?: 'Không có'
            ],
            'max_total' => $this->total_max_discount
        ];
    }
}
