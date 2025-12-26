<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PurchasePricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'supplier_id', 'purchase_order_id', 'quantity',
        'purchase_price', 'discount_percent', 'price_after_discount', 'vat_percent',
        'shipping_cost', 'loading_cost', 'inspection_cost', 'other_cost',
        'total_service_cost', 'service_cost_per_unit', 'warehouse_price',
        'pricing_method', 'note', 'created_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'price_after_discount' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'loading_cost' => 'decimal:2',
        'inspection_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'total_service_cost' => 'decimal:2',
        'service_cost_per_unit' => 'decimal:2',
        'warehouse_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeByProduct(Builder $query, $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeBySupplier(Builder $query, $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByMethod(Builder $query, $method): Builder
    {
        return $query->where('pricing_method', $method);
    }

    public function calculatePrices(): void
    {
        $this->price_after_discount = $this->purchase_price * (1 - $this->discount_percent / 100);
        $this->total_service_cost = $this->shipping_cost + $this->loading_cost + $this->inspection_cost + $this->other_cost;
        $this->service_cost_per_unit = $this->quantity > 0 ? $this->total_service_cost / $this->quantity : 0;
        $priceWithVat = $this->price_after_discount * (1 + $this->vat_percent / 100);
        $this->warehouse_price = $priceWithVat + $this->service_cost_per_unit;
    }

    public function getPricingMethodLabelAttribute(): string
    {
        return match($this->pricing_method) {
            'fifo' => 'FIFO (Nhập trước xuất trước)',
            'lifo' => 'LIFO (Nhập sau xuất trước)',
            'average' => 'Bình quân gia quyền',
            default => $this->pricing_method
        };
    }

    public static function getAverageWarehousePrice($productId): float
    {
        return self::where('product_id', $productId)
            ->avg('warehouse_price') ?? 0;
    }
}
