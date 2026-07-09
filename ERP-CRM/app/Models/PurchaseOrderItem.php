<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'product_id', 'product_name',
        'sale_order_request_item_id', 'ordered_quantity', 'serial_number',
        'quantity', 'received_quantity', 'unit', 'unit_price', 'warehouse_unit_price', 'discount_percent', 'total', 'note',
        'vat_percent', 'vat_amount', 'status', 'license_file'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'total' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'vat_amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function saleOrderRequestItem(): BelongsTo
    {
        return $this->belongsTo(SaleOrderRequestItem::class, 'sale_order_request_item_id');
    }

    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity - $this->received_quantity;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'ordered' => 'Chờ hàng',
            'shipping' => 'Đang về',
            'received' => 'Đã về',
            'cancelled' => 'Hủy',
            default => 'Chờ hàng'
        };
    }
}
