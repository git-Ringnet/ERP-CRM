<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'stock',
        'min_stock',
        'avg_cost',
        'expiry_date',
        'warranty_months',
    ];

    protected $casts = [
        'stock' => 'integer',
        'min_stock' => 'integer',
        'avg_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'warranty_months' => 'integer',
    ];

    /**
     * Get the product for this inventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for this inventory.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Scope: Low stock items (stock < min_stock).
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<', 'min_stock');
    }

    /**
     * Scope: Items expiring soon (within specified days).
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        $futureDate = Carbon::now()->addDays($days);
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $futureDate)
            ->where('expiry_date', '>=', Carbon::now());
    }

    /**
     * Scope: Filter by warehouse.
     */
    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope: Filter by product.
     */
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Check if stock is low.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock < $this->min_stock;
    }

    /**
     * Check if item is expiring soon (within 30 days).
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        $daysUntilExpiry = Carbon::now()->diffInDays($this->expiry_date, false);
        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get stock status label.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'Hết hàng';
        } elseif ($this->is_low_stock) {
            return 'Sắp hết';
        } else {
            return 'Còn hàng';
        }
    }

    /**
     * Get total inventory value.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->stock * $this->avg_cost;
    }

    /**
     * Update stock quantity.
     */
    public function updateStock(int $quantity, string $operation = 'add'): void
    {
        if ($operation === 'add') {
            $this->stock += $quantity;
        } elseif ($operation === 'subtract') {
            $this->stock -= $quantity;
        } else {
            $this->stock = $quantity;
        }

        $this->save();
    }

    /**
     * Check if sufficient stock exists.
     */
    public function hasSufficientStock(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }
}
