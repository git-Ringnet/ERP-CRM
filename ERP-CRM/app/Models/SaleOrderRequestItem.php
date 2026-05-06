<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleOrderRequestItem extends Model
{
    protected $fillable = [
        'sale_order_request_id',
        'vendor_id',
        'vendor',
        'type',
        'part_number',
        'product_id',
        'quantity',
        'unit',
        'serial_number',
        'exp_date',
        'si_name',
        'eu_name_mst',
        'address',
        'note',
    ];

    protected $casts = [
        'exp_date' => 'date',
    ];

    public function saleOrderRequest(): BelongsTo
    {
        return $this->belongsTo(SaleOrderRequest::class, 'sale_order_request_id');
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
     * Tính tổng số lượng đã đặt hàng từ các PO liên kết
     */
    public function getOrderedQuantityTotalAttribute(): float
    {
        return (float) $this->purchaseOrderItems()->sum('ordered_quantity');
    }

    /**
     * Tính tổng số lượng thực tế đã nhận hàng
     */
    public function getReceivedQuantityTotalAttribute(): float
    {
        return (float) $this->purchaseOrderItems()->sum('received_quantity');
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
