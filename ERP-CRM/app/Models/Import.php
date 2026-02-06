<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'warehouse_id',
        'supplier_id',
        'date',
        'employee_id',
        'total_qty',
        'shipping_cost',
        'loading_cost',
        'inspection_cost',
        'other_cost',
        'total_service_cost',
        'discount_percent',
        'vat_percent',
        'reference_type',
        'reference_id',
        'shipping_allocation_id',
        'note',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'total_qty' => 'integer',
        'shipping_cost' => 'decimal:2',
        'loading_cost' => 'decimal:2',
        'inspection_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'total_service_cost' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'reference_id' => 'integer',
    ];

    /**
     * Get the warehouse for this import.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the supplier for this import.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the employee who created this import.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the items for this import.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ImportItem::class);
    }

    /**
     * Get the shipping allocation for this import.
     */
    public function shippingAllocation(): BelongsTo
    {
        return $this->belongsTo(ShippingAllocation::class);
    }

    /**
     * Get the purchase order for this import.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'reference_id')
            ->where('reference_type', 'purchase_order');
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Scope: Filter by warehouse.
     */
    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Generate unique import code.
     */
    public static function generateCode(): string
    {
        $lastImport = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastImport ? ((int) substr($lastImport->code, 3)) + 1 : 1;
        return 'IMP' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get status label in Vietnamese.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Chờ xử lý',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            'rejected' => 'Từ chối',
            default => $this->status,
        };
    }

    /**
     * Get status color for badge.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'completed' => 'green',
            'cancelled' => 'gray',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Calculate total service cost.
     */
    public function calculateServiceCost(): void
    {
        $this->total_service_cost = $this->shipping_cost + $this->loading_cost + $this->inspection_cost + $this->other_cost;
    }

    /**
     * Calculate warehouse price for each item.
     * Returns service cost per unit to be added to item cost.
     */
    public function getServiceCostPerUnit(): float
    {
        if ($this->total_qty <= 0) {
            return 0;
        }
        return $this->total_service_cost / $this->total_qty;
    }

    /**
     * Check if this import uses shipping allocation.
     */
    public function usesShippingAllocation(): bool
    {
        return !is_null($this->shipping_allocation_id) && $this->shippingAllocation()->exists();
    }

    /**
     * Get allocated cost per unit for a specific product from shipping allocation.
     */
    public function getAllocatedCostForProduct(int $productId): float
    {
        if (!$this->usesShippingAllocation()) {
            return 0;
        }

        $allocationItem = $this->shippingAllocation->items()
            ->where('product_id', $productId)
            ->first();

        return $allocationItem ? (float) $allocationItem->allocated_cost_per_unit : 0;
    }
}
