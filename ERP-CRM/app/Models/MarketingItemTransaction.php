<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingItemTransaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'marketing_item_id',
        'type', // import, export, adjustment
        'quantity',
        'remaining_stock',
        'opportunity_id',
        'marketing_event_id',
        'created_by',
        'reference_code',
        'note',
    ];

    protected $casts = [
        'quantity'        => 'integer',
        'remaining_stock' => 'integer',
    ];

    public const TYPES = [
        'import'     => 'Nhập kho',
        'export'     => 'Xuất quà tặng / Sự kiện',
        'adjustment' => 'Kiểm kê điều chỉnh',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function marketingItem()
    {
        return $this->belongsTo(MarketingItem::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function marketingEvent()
    {
        return $this->belongsTo(MarketingEvent::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
