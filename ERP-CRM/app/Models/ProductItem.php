<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ProductItem extends Model
{
    use HasFactory;

    /**
     * Status constants
     * Requirements: 3.1
     */
    public const STATUS_IN_STOCK = 'in_stock';
    public const STATUS_SOLD = 'sold';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_LIQUIDATION = 'liquidation';

    /**
     * NO_SKU prefix for items without physical SKU
     * Requirements: 3.4
     */
    public const NO_SKU_PREFIX = 'NOSKU';

    /**
     * Fillable fields
     * Requirements: 3.1
     */
    protected $fillable = [
        'product_id',
        'sku',
        'description',
        'cost_usd',
        'price_tiers',
        'quantity',
        'comments',
        'warehouse_id',
        'import_id',
        'export_id',
        'status',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'cost_usd' => 'decimal:2',
        'price_tiers' => 'array',
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: ProductItem belongs to Product
     * Requirements: 3.2
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: ProductItem belongs to Warehouse
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relationship: ProductItem belongs to Import
     * Requirements: 7.1
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * Relationship: ProductItem belongs to Export
     */
    public function export(): BelongsTo
    {
        return $this->belongsTo(Export::class);
    }

    /**
     * Generate NO_SKU identifier for items without physical SKU
     * Format: NOSKU-{product_id}-{sequential_number}
     * Requirements: 3.4
     */
    public static function generateNoSku(int $productId): string
    {
        // Get the next sequential number for this product's NO_SKU items
        $lastNoSku = self::where('product_id', $productId)
            ->where('sku', 'like', self::NO_SKU_PREFIX . '-' . $productId . '-%')
            ->orderBy('sku', 'desc')
            ->value('sku');

        if ($lastNoSku) {
            // Extract the sequential number and increment
            $parts = explode('-', $lastNoSku);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Format: NOSKU-{product_id}-{sequential_number} (3-digit padded)
        return sprintf('%s-%d-%03d', self::NO_SKU_PREFIX, $productId, $nextNumber);
    }

    /**
     * Check if this item has a NO_SKU identifier
     * Requirements: 3.4
     */
    public function isNoSku(): bool
    {
        return str_starts_with($this->sku, self::NO_SKU_PREFIX . '-');
    }

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_IN_STOCK => 'Trong kho',
            self::STATUS_SOLD => 'Đã bán',
            self::STATUS_DAMAGED => 'Hư hỏng',
            self::STATUS_TRANSFERRED => 'Đã chuyển',
            self::STATUS_LIQUIDATION => 'Thanh lý',
        ];
    }
}
