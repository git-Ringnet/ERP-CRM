<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleOrderRequestItem extends Model
{
    protected $fillable = [
        'sale_order_request_id',
        'sale_item_id',
        'vendor_id',
        'vendor',
        'type',
        'needs_cq',
        'part_number',
        'product_id',
        'quantity',
        'unit',
        'serial_number',
        'exp_date',
        'si_name',
        'pos_id',
        'eu_name_mst',
        'address',
        'note',
        'is_cancelled',
    ];

    protected $casts = [
        'exp_date' => 'date',
        'is_cancelled' => 'boolean',
        'needs_cq' => 'boolean',
    ];

    public function saleOrderRequest(): BelongsTo
    {
        return $this->belongsTo(SaleOrderRequest::class, 'sale_order_request_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function purchaseOrderItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'sale_order_request_item_id');
    }

    /**
     * Determine if this item is classified as a Project item (Hàng dự án) vs Run-rate item (Hàng run-rate)
     */
    public function isProjectItem(): bool
    {
        $vendorName = strtolower($this->vendor ?? ($this->product?->brand ?? ''));
        $partNumber = strtolower($this->part_number ?? ($this->product?->code ?? ''));
        $isFortinet = str_contains($vendorName, 'fortinet') 
            || str_starts_with($partNumber, 'fa') 
            || str_starts_with($partNumber, 'fg') 
            || str_starts_with($partNumber, 'fw') 
            || str_starts_with($partNumber, 'fc') 
            || str_starts_with($partNumber, 'fad');

        $type = strtoupper(trim((string)($this->type ?? '')));
        $needsCq = (bool)($this->needs_cq ?? false);
        $euInfo = trim((string)($this->eu_name_mst ?? $this->eu_name ?? ''));

        if ($isFortinet) {
            // Fortinet: HW without CQ checkbox & without EU info -> Run-rate
            if ($type === 'HW' && !$needsCq && empty($euInfo)) {
                return false;
            }
            return true;
        } else {
            // Non-Fortinet: empty EU info & needs_cq false -> Run-rate
            if (empty($euInfo) && !$needsCq) {
                return false;
            }
            return true;
        }
    }

    /**
     * Tính tổng số lượng đã đặt hàng từ các PO liên kết (loại trừ các PO đã hủy)
     */
    public function getOrderedQuantityTotalAttribute(): float
    {
        return (float) $this->purchaseOrderItems()
            ->whereHas('purchaseOrder', function ($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->sum('ordered_quantity');
    }

    /**
     * Tính tổng số lượng thực tế đã nhận hàng (loại trừ các PO đã hủy)
     */
    public function getReceivedQuantityTotalAttribute(): float
    {
        return (float) $this->purchaseOrderItems()
            ->whereHas('purchaseOrder', function ($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->sum('received_quantity');
    }

    /**
     * Tính số lượng còn lại cần đặt
     */
    public function getRemainingOrderQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity - $this->ordered_quantity_total);
    }

    /**
     * Alias: Tính số lượng còn lại cần đặt (dùng trong API getPrItems)
     */
    public function getRemainingToOrderAttribute(): float
    {
        return $this->remaining_order_quantity;
    }

    /**
     * Alias relationship cho backward compatibility
     */
    public function orderRequest(): BelongsTo
    {
        return $this->saleOrderRequest();
    }
}
