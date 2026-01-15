<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * Available category options (single letters A-Z)
     * Requirements: 2.1
     */
    public const CATEGORIES = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

    /**
     * Simplified fillable - only basic product fields
     * Requirements: 1.1, 1.2, 1.3
     */
    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'warranty_months',
        'description',
        'note',
    ];

    /**
     * Simplified casts
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Product has many product items
     * Requirements: 6.4
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductItem::class);
    }

    /**
     * Relationship: Product has many inventories (for backward compatibility)
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get total quantity across all items
     * Requirements: 7.4
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Get in-stock quantity (only items with status 'in_stock')
     * Requirements: 7.4
     */
    public function getInStockQuantityAttribute(): int
    {
        return $this->items()
            ->where('status', ProductItem::STATUS_IN_STOCK)
            ->sum('quantity');
    }

    /**
     * Get liquidation quantity (only items with status 'liquidation')
     */
    public function getLiquidationQuantityAttribute(): int
    {
        return $this->items()
            ->where('status', ProductItem::STATUS_LIQUIDATION)
            ->sum('quantity');
    }

    /**
     * Scope for searching products by name, code, or category
     * Requirements: 4.11
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering products by category letter
     * Requirements: 2.3
     */
    public function scopeFilterByCategory(Builder $query, ?string $category): Builder
    {
        if (empty($category)) {
            return $query;
        }

        return $query->where('category', strtoupper($category));
    }
}
