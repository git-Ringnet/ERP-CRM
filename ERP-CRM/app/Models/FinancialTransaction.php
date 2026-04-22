<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'transaction_category_id',
        'type',
        'amount',
        'date',
        'payment_method',
        'reference_number',
        'note',
        'created_by',
        'currency_id',
        'exchange_rate',
        'amount_foreign',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'exchange_rate' => 'decimal:6',
        'amount_foreign' => 'decimal:4',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function getTypeLabelAttribute()
    {
        return $this->type === 'income' ? 'Thu' : 'Chi';
    }

    public function getPaymentMethodLabelAttribute()
    {
        return match($this->payment_method) {
            'cash' => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản',
            'card' => 'Thẻ',
            'other' => 'Khác',
            default => $this->payment_method,
        };
    }
}
