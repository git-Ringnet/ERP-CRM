<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'amount',
        'payment_method',
        'reference_number',
        'payment_date',
        'note',
        'created_by',
        'currency_id',
        'exchange_rate',
        'amount_foreign',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'amount_foreign' => 'decimal:4',
    ];

    /**
     * Relationship with Currency
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Relationship with Sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relationship with Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
}
