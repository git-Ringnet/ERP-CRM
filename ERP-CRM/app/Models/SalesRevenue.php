<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesRevenue extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        // FK links
        'purchase_order_id',
        'purchase_order_item_id',
        'sale_id',
        'sale_item_id',
        'quotation_id',
        'project_id',
        'customer_id',
        'product_id',
        'supplier_id',
        // 23 cột template
        'cpq_number',
        'invoice_status',
        'warehouse_status',
        'license_exported',
        'po_code',
        'po_date',
        'product_name',
        'quantity',
        'serial_number',
        'quote_id',
        'list_price',
        'discount_percent',
        'unit_price',
        'total_amount',
        'expired_date',
        'customer_name',
        'selling_price',
        'end_user_partner',
        'equipment',
        'partner_name',
        'end_user',
        'industry',
        // Metadata
        'year',
        'note',
        'created_by',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expired_date' => 'date',
        'list_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity' => 'integer',
        'year' => 'integer',
    ];

    /**
     * Invoice status options (Tình trạng XHĐ)
     */
    public const INVOICE_STATUSES = [
        'not_issued' => 'Chưa xuất',
        'pending' => 'Chờ xử lý',
        'draft_issued' => 'Đã xuất nháp',
        'official_issued' => 'Đã xuất chính thức',
        'rejected' => 'Bị từ chối',
    ];

    // ─── Relationships ──────────────────────────────────────────────

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Auto-populate Methods ──────────────────────────────────────

    /**
     * Populate fields from a PurchaseOrderItem + its linked SaleOrderRequestItem
     */
    public function populateFromPurchaseOrderItem(PurchaseOrderItem $poItem): self
    {
        $po = $poItem->purchaseOrder;

        $this->purchase_order_id = $po->id;
        $this->purchase_order_item_id = $poItem->id;
        $this->po_code = $po->code;
        $this->po_date = $po->order_date;
        $this->product_name = $poItem->product_name;
        $this->product_id = $poItem->product_id;
        $this->quantity = $poItem->quantity;
        $this->unit_price = $poItem->unit_price;
        $this->total_amount = $poItem->total;
        $this->supplier_id = $po->supplier_id;

        // Warehouse status from received quantity
        if ($poItem->received_quantity >= $poItem->quantity) {
            $this->warehouse_status = 'Đã nhập đủ';
        } elseif ($poItem->received_quantity > 0) {
            $this->warehouse_status = 'Nhập một phần (' . $poItem->received_quantity . '/' . $poItem->quantity . ')';
        } else {
            $this->warehouse_status = 'Chưa nhập';
        }

        // License status
        $this->license_exported = $poItem->license_file ? 'Đã xuất' : 'Chưa xuất';

        // ─── Pull data from linked SaleOrderRequestItem ──────────
        $sorItem = $poItem->saleOrderRequestItem;
        if ($sorItem) {
            $this->populateFromSaleOrderRequestItem($sorItem);
        }

        return $this;
    }

    /**
     * Populate S/N, Exp date, SI Name (→Partner), EU Name (→EU) from SaleOrderRequestItem
     */
    public function populateFromSaleOrderRequestItem(SaleOrderRequestItem $sorItem): self
    {
        // Serial Number
        if ($sorItem->serial_number) {
            $this->serial_number = $sorItem->serial_number;
        }

        // Expired date
        if ($sorItem->exp_date) {
            $this->expired_date = $sorItem->exp_date;
        }

        // SI Name → Partner column
        if ($sorItem->si_name) {
            $this->partner_name = $sorItem->si_name;
        }

        // EU Name - MST → EU column
        if ($sorItem->eu_name_mst) {
            $this->end_user = $sorItem->eu_name_mst;
        }

        return $this;
    }

    /**
     * Populate fields from a Sale and its related Sale Item
     */
    public function populateFromSale(Sale $sale, ?SaleItem $saleItem = null): self
    {
        $this->sale_id = $sale->id;
        $this->customer_id = $sale->customer_id;
        $this->customer_name = $sale->customer_name ?? $sale->customer?->name;

        // Project info → End User/Partner (Project) column + Industry
        // This column ONLY shows data from linked Project
        $project = $sale->project;
        if ($project) {
            $this->project_id = $project->id;
            // End User/Partner (Project) - only from Project
            $euProject = $project->eu_name_vi;
            $partnerProject = $project->collaborate_company;
            $this->end_user_partner = $euProject
                ? ($partnerProject ? $euProject . ' / ' . $partnerProject : $euProject)
                : $partnerProject;
            $this->industry = $project->eu_industry;
        }

        if ($saleItem) {
            $this->sale_item_id = $saleItem->id;
            $this->selling_price = $saleItem->price;
            $this->product_name = $this->product_name ?: $saleItem->product_name;
            $this->product_id = $this->product_id ?: $saleItem->product_id;
            $this->quantity = $this->quantity ?: $saleItem->quantity;

            // ListPrice = usd_price from SaleItem
            if ($saleItem->usd_price > 0) {
                $this->list_price = $saleItem->usd_price;
            }

            // Discount = discount_rate from SaleItem
            if ($saleItem->discount_rate > 0) {
                $this->discount_percent = $saleItem->discount_rate;
            }

            // Expired date from warranty (fallback if not set from SOR)
            if (empty($this->expired_date) && $saleItem->warranty_end_date) {
                $this->expired_date = $saleItem->warranty_end_date;
            }
        }

        // Invoice status → from Sale's latest InvoiceRequest
        $latestInvoice = $sale->invoiceRequests()->latest()->first();
        if ($latestInvoice) {
            $this->invoice_status = $latestInvoice->status;
        } else {
            $this->invoice_status = 'not_issued';
        }

        // Quotation ID → find quotation that converted to this sale
        $quotation = Quotation::where('converted_to_sale_id', $sale->id)->first();
        if ($quotation) {
            $this->populateFromQuotation($quotation);
        }

        return $this;
    }

    /**
     * Populate from Quotation
     */
    public function populateFromQuotation(Quotation $quotation): self
    {
        $this->quotation_id = $quotation->id;
        $this->quote_id = $quotation->code;
        return $this;
    }

    // ─── Calculations ───────────────────────────────────────────────

    /**
     * Calculate total_amount = quantity × unit_price
     */
    public function calculateTotal(): self
    {
        $this->total_amount = round($this->quantity * $this->unit_price, 2);
        return $this;
    }

    // ─── Accessors ──────────────────────────────────────────────────

    public function getInvoiceStatusLabelAttribute(): string
    {
        return self::INVOICE_STATUSES[$this->invoice_status] ?? ($this->invoice_status ?: 'Chưa xuất');
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeByYear(Builder $query, ?int $year): Builder
    {
        if (!$year) return $query;
        return $query->where('year', $year);
    }

    public function scopeBySupplier(Builder $query, ?int $supplierId): Builder
    {
        if (!$supplierId) return $query;
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('po_code', 'like', "%{$search}%")
              ->orWhere('product_name', 'like', "%{$search}%")
              ->orWhere('customer_name', 'like', "%{$search}%")
              ->orWhere('cpq_number', 'like', "%{$search}%")
              ->orWhere('serial_number', 'like', "%{$search}%")
              ->orWhere('quote_id', 'like', "%{$search}%")
              ->orWhere('end_user', 'like', "%{$search}%")
              ->orWhere('partner_name', 'like', "%{$search}%");
        });
    }
}
