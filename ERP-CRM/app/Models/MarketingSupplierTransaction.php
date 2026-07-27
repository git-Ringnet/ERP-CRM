<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingSupplierTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'marketing_supplier_fund_id',
        'marketing_event_id',
        'marketing_request_id',
        'type', // incoming, expense, receivable, collected
        'amount',
        'status', // pending, collected
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function fund()
    {
        return $this->belongsTo(MarketingSupplierFund::class, 'marketing_supplier_fund_id');
    }

    public function event()
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }

    public function request()
    {
        return $this->belongsTo(MarketingRequest::class, 'marketing_request_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'incoming'   => 'Đầu vào hãng cấp',
            'expense'    => 'Chi phí sự kiện',
            'receivable' => 'Hãng nợ (Chưa thu)',
            'collected'  => 'Đã thu nợ',
            default      => $this->type,
        };
    }
}
