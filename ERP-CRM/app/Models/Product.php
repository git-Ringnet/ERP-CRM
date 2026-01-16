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

    /**
     * Relationship: Product has many supplier price list items (by matching code to SKU)
     */
    public function supplierPriceListItems(): HasMany
    {
        return $this->hasMany(SupplierPriceListItem::class, 'sku', 'code');
    }

    /**
     * Get calculated selling price from the best available supplier price list
     * Strategy:
     * 1. Check all linked price list items
     * 2. Filter for valid/active price lists and effective dates
     * 3. Sort by price list effective_date (newest first)
     * 4. Calculate final selling price using the price list formula
     */
    public function getCalculatedSellingPriceAttribute(): float
    {
        // Use loaded relationship if available to avoid N+1, otherwise query
        $items = $this->relationLoaded('supplierPriceListItems')
            ? $this->supplierPriceListItems
            : $this->supplierPriceListItems()->with('priceList')->get();

        $bestPrice = 0;
        $latestDate = null;

        foreach ($items as $item) {
            $pl = $item->priceList;

            // Check if price list exists and is valid
            if (!$pl || !$pl->isValid()) {
                continue;
            }

            // We prefer the most recent price list
            // If we already have a candidate, check if this one is newer
            if ($latestDate && $pl->effective_date && $pl->effective_date->lt($latestDate)) {
                continue;
            }

            // Get base price (min of list_price, price_1yr, etc.)
            $basePrice = $item->best_price;
            if (!$basePrice)
                continue;

            // Calculate selling price
            $calculated = $pl->calculateFinalPrice($basePrice);
            $sellingPrice = $calculated['final_price_vnd'];

            // Update candidate
            $bestPrice = $sellingPrice;
            $latestDate = $pl->effective_date;
        }

        return $bestPrice;
    }

    /**
     * Get calculated cost price from the best available supplier price list
     * Cost = Base - Discount + Shipping + Other Fees (Excludes Margin)
     */
    public function getCalculatedCostAttribute(): float
    {
        // Use loaded relationship if available to avoid N+1, otherwise query
        $items = $this->relationLoaded('supplierPriceListItems')
            ? $this->supplierPriceListItems
            : $this->supplierPriceListItems()->with('priceList')->get();

        $bestCost = 0;
        $latestDate = null;

        foreach ($items as $item) {
            $pl = $item->priceList;

            // Check if price list exists and is valid
            if (!$pl || !$pl->isValid()) {
                continue;
            }

            // We prefer the most recent price list
            if ($latestDate && $pl->effective_date && $pl->effective_date->lt($latestDate)) {
                continue;
            }

            // Get base price
            $basePrice = $item->best_price;
            if (!$basePrice)
                continue;

            // Calculate details
            $calculated = $pl->calculateFinalPrice($basePrice);

            // Cost = (After Discount + Shipping + Other Fees) * Exchange Rate
            // Note: calculateFinalPrice returns components in original currency
            // We need to sum them and convert
            $costNative = $calculated['after_discount'] + $calculated['total_shipping'] + $calculated['other_fees'];
            $costVnd = $costNative * ($calculated['exchange_rate'] ?: 1);

            // Update candidate
            $bestCost = $costVnd;
            $latestDate = $pl->effective_date;
        }

        return round($bestCost, 2);
    }
}
