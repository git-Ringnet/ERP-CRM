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
     * Loại dòng bán bắt buộc xuất đúng thiết bị / S/N theo PO đã đặt cho đơn.
     */
    public const PO_LOCKED_DEAL_ITEM_TYPES = ['runrate_with_cq', 'project'];

    /**
     * Hàng runrate yêu cầu cấp CQ và hàng dự án: thiết bị nhập từ PO đặt cho dòng bán thuộc
     * PO_LOCKED_DEAL_ITEM_TYPES bị khoá cho đúng đơn bán đó (bắt buộc xuất đúng S/N theo PO đã đặt),
     * không được giữ / xuất chéo.
     *
     * @param  int|null  $saleId  null: khoá cho bất kỳ đơn nào
     * @param  bool  $otherSalesOnly  true: chỉ khoá cho đơn KHÁC $saleId
     */
    private static function cqLockSubquery($q, ?int $saleId, bool $otherSalesOnly): void
    {
        $q->select(DB::raw(1))
            ->from('imports as cq_i')
            ->join('purchase_order_items as cq_poi', function ($join) {
                $join->on('cq_poi.purchase_order_id', '=', 'cq_i.reference_id')
                    ->on('cq_poi.product_id', '=', 'product_items.product_id');
            })
            ->join('sale_order_request_items as cq_sori', 'cq_sori.id', '=', 'cq_poi.sale_order_request_item_id')
            ->join('sale_items as cq_si', 'cq_si.id', '=', 'cq_sori.sale_item_id')
            ->join('sale_order_requests as cq_sor', 'cq_sor.id', '=', 'cq_sori.sale_order_request_id')
            ->whereColumn('cq_i.id', 'product_items.import_id')
            ->where('cq_i.reference_type', 'purchase_order')
            ->whereIn('cq_si.deal_item_type', self::PO_LOCKED_DEAL_ITEM_TYPES)
            ->when($saleId !== null, fn ($w) => $w->where('cq_sor.sale_id', $otherSalesOnly ? '<>' : '=', $saleId));
    }

    /**
     * Thiết bị khoá CQ (cho bất kỳ đơn nào, hoặc cho đúng $saleId).
     */
    public function scopeCqLocked($query, ?int $saleId = null)
    {
        return $query->whereExists(fn ($q) => self::cqLockSubquery($q, $saleId, false));
    }

    /**
     * Loại thiết bị khoá CQ cho đơn khác (null: loại mọi thiết bị khoá CQ).
     */
    public function scopeNotCqLockedForOthers($query, ?int $saleId = null)
    {
        return $query->whereNotExists(fn ($q) => self::cqLockSubquery($q, $saleId, $saleId !== null));
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
        // Ưu tiên Số PO trên phiếu nhập (quản trị viên có thể sửa lại cho khớp PO khác),
        // sau đó mới tới PO liên kết khi nhận hàng.
        return trim((string) $this->import?->po_code) ?: ($this->purchase_order?->code ?: null);
    }

    /**
     * File license PO Team đã upload trên dòng PO của sản phẩm này (để Kho license xem/tải/in).
     *
     * @return array<int, array{item_id:int, index:int, name:string}>
     */
    public function getLicenseFilesAttribute(): array
    {
        $po = $this->purchase_order;
        if (!$po) {
            return [];
        }

        $files = [];
        foreach ($po->items->where('product_id', $this->product_id) as $poItem) {
            if (!$poItem->license_file) {
                continue;
            }
            $decoded = json_decode($poItem->license_file, true);
            $paths = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [$poItem->license_file];
            foreach (array_values($paths) as $index => $path) {
                $files[] = ['item_id' => $poItem->id, 'index' => $index, 'name' => basename($path)];
            }
        }

        return $files;
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

    /**
     * Determine if this ProductItem is classified as a Project item (Hàng dự án) vs Run-rate item (Hàng run-rate)
     */
    public function isProjectItem(): bool
    {
        if ($this->warehouse && $this->warehouse->code === 'WH_PROJECT') {
            return true;
        }
        if ($this->warehouse && $this->warehouse->code === 'WH_RUNRATE') {
            return false;
        }

        if ($this->sale_order_request_item) {
            return $this->sale_order_request_item->isProjectItem();
        }

        if ($this->import && $this->import->purchaseOrder) {
            foreach ($this->import->purchaseOrder->items as $poItem) {
                if ($poItem->saleOrderRequestItem) {
                    return $poItem->saleOrderRequestItem->isProjectItem();
                }
            }
        }

        return false;
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


    /**
     * Get unified trace info (PO, supplier, SO, sales, project, comments)
     */
    public function getTraceInfoAttribute(): array
    {
        $po = $this->import?->purchaseOrder ?: $this->purchase_order;
        $poItem = $this->po_item;
        $sorItem = $poItem?->saleOrderRequestItem;
        $sor = $sorItem?->saleOrderRequest;
        $sale = $po?->sale ?: ($this->export?->sale ?: ($sor?->sale ?: null));

        $salesName = $sale?->user?->name 
            ?: ($sor?->creator?->name 
            ?: ($this->order_creator_name 
            ?: ($po?->creator?->name ?: null)));

        $project = $sale?->project ?: ($this->export?->project ?: null);
        $projectName = $project ? ($project->code ? "{$project->code} - {$project->name}" : $project->name) : $this->project_name;

        return [
            'po_code' => $po?->code ?: '-',
            'supplier_name' => $po?->supplier?->name ?: '-',
            'sale_code' => $sale?->code ?: '-',
            'sales_name' => $salesName ?: '-',
            'project_name' => $projectName ?: '-',
            'comments' => $this->comments ?: '-',
        ];
    }
}
