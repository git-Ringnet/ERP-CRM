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
    public const NO_SKU_PREFIX = 'NOSERIAL';
    public const OLD_NO_SKU_PREFIX = 'NOSKU';

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
        'warranty_months',
        'expiry_date',
        'borrower',
        'custom_fields',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'cost_usd' => 'decimal:2',
        'price_tiers' => 'array',
        'quantity' => 'integer',
        'expiry_date' => 'date',
        'warranty_months' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    /**
     * Appends
     */
    protected $appends = [
        'purchase_order_code',
        'project_name',
        'order_creator_name',
        'r_model_orderer_info'
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
     * Format: NOSERIAL-{product_id}-{sequential_number}
     * Requirements: 3.4
     */
    public static function generateNoSku(int $productId): string
    {
        // Get the next sequential number for this product's NO_SKU items (checking both prefixes)
        $lastNoSku = self::where('product_id', $productId)
            ->where(function($query) use ($productId) {
                $query->where('sku', 'like', self::NO_SKU_PREFIX . '-' . $productId . '-%')
                      ->orWhere('sku', 'like', self::OLD_NO_SKU_PREFIX . '-' . $productId . '-%');
            })
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

        // Format: NOSERIAL-{product_id}-{sequential_number} (3-digit padded)
        return sprintf('%s-%d-%03d', self::NO_SKU_PREFIX, $productId, $nextNumber);
    }

    /**
     * Check if this item has a NO_SKU identifier
     * Requirements: 3.4
     */
    public function isNoSku(): bool
    {
        return str_starts_with($this->sku, self::NO_SKU_PREFIX) || str_starts_with($this->sku, self::OLD_NO_SKU_PREFIX);
    }

    /**
     * Scope for items without serial
     */
    public function scopeNoSerial($query)
    {
        return $query->where(function ($q) {
            $q->where('sku', 'like', self::NO_SKU_PREFIX . '%')
                ->orWhere('sku', 'like', self::OLD_NO_SKU_PREFIX . '%');
        });
    }

    /**
     * Scope for items with serial
     */
    public function scopeHasSerial($query)
    {
        return $query->where('sku', 'not like', self::NO_SKU_PREFIX . '%')
            ->where('sku', 'not like', self::OLD_NO_SKU_PREFIX . '%');
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

    /**
     * Get the Purchase Order for this item.
     */
    public function getPurchaseOrderAttribute()
    {
        return $this->import && $this->import->reference_type === 'purchase_order' 
            ? $this->import->purchaseOrder 
            : null;
    }

    /**
     * Get PO code.
     */
    public function getPurchaseOrderCodeAttribute(): ?string
    {
        return $this->purchase_order ? $this->purchase_order->code : null;
    }

    /**
     * Get the related PO Item.
     */
    public function getPoItemAttribute()
    {
        if (!$this->purchase_order) {
            return null;
        }
        if ($this->purchase_order->relationLoaded('items')) {
            return $this->purchase_order->items->firstWhere('product_id', $this->product_id);
        }
        return $this->purchase_order->items()
            ->where('product_id', $this->product_id)
            ->first();
    }

    /**
     * Get the related Sale Order Request Item.
     */
    public function getSaleOrderRequestItemAttribute()
    {
        return $this->po_item ? $this->po_item->saleOrderRequestItem : null;
    }

    public function getProjectNameAttribute(): ?string
    {
        // Try to get sale from sale order request item or purchase order
        $sale = null;
        if ($this->sale_order_request_item && $this->sale_order_request_item->saleOrderRequest) {
            $sale = $this->sale_order_request_item->saleOrderRequest->sale;
        }
        if (!$sale && $this->purchase_order) {
            $sale = $this->purchase_order->sale;
        }

        // If there is a linked project, return its Code - Name
        if ($sale && $sale->project) {
            $project = $sale->project;
            return $project->code ? "{$project->code} - {$project->name}" : $project->name;
        }

        // Fallback to eu_name_mst or sale's customer_name
        return $this->sale_order_request_item?->eu_name_mst ?: ($sale?->customer_name ?: null);
    }

    /**
     * Get Orderer (Người đặt hàng).
     */
    public function getOrderCreatorNameAttribute(): ?string
    {
        if ($this->sale_order_request_item) {
            $sor = $this->sale_order_request_item->saleOrderRequest;
            if ($sor && $sor->creator) {
                return $sor->creator->name;
            }
        }

        if ($this->purchase_order) {
            if ($this->purchase_order->sale && $this->purchase_order->sale->user) {
                return $this->purchase_order->sale->user->name;
            }
            return $this->purchase_order->creator ? $this->purchase_order->creator->name : null;
        }

        return null;
    }

    public function getRModelOrdererInfoAttribute(): ?string
    {
        if ($this->purchase_order) {
            $partner = $this->purchase_order->linked_partner_names;
            $salesperson = $this->purchase_order->linked_salesperson_names;
            $poCode = $this->purchase_order->code;
            $creator = $this->order_creator_name;

            $parts = [];
            if ($partner && $partner !== 'N/A') {
                $parts[] = $partner;
            }
            if ($salesperson && $salesperson !== 'N/A') {
                $parts[] = '(' . $salesperson . ')';
            }
            $partnerSalesperson = implode(' ', $parts);

            $creatorPo = [];
            if ($creator) {
                $creatorPo[] = $creator;
            }
            if ($poCode) {
                $creatorPo[] = 'P.O ' . $poCode;
            }
            $creatorPoStr = implode(' - ', $creatorPo);

            if ($partnerSalesperson && $creatorPoStr) {
                return $partnerSalesperson . ', ' . $creatorPoStr;
            } elseif ($partnerSalesperson) {
                return $partnerSalesperson;
            } else {
                return $creatorPoStr ?: 'N/A';
            }
        }
        return $this->order_creator_name ?: 'N/A';
    }
}
