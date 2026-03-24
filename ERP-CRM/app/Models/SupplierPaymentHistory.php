<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'supplier_id',
        'amount',
        'amount_foreign',
        'currency',
        'exchange_rate',
        'payment_method',
        'reference_number',
        'payment_date',
        'note',
        'created_by',
        'currency_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_foreign' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'payment_date' => 'date',
    ];

    /**
     * Relationship with Currency
     */
    public function currencyModel(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relationship with PurchaseOrder
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Relationship with Supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get payment method label
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản',
            'card' => 'Thẻ',
            'other' => 'Khác',
            default => 'Không xác định',
        };
    }

    /**
     * Get display amount (shows foreign currency if applicable)
     */
    public function getAmountDisplayAttribute(): string
    {
        if ($this->currency !== 'VND' && $this->amount_foreign) {
            return number_format($this->amount_foreign, 2, ',', '.') . ' ' . $this->currency
                . ' (≈ ' . number_format($this->amount, 0, ',', '.') . ' VND)';
        }
        return number_format($this->amount, 0, ',', '.') . ' VND';
    }
}
