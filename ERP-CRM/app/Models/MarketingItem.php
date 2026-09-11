<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'stock_quantity',
        'min_stock_alert',
        'unit_cost',
        'image',
        'description',
        'status',
    ];

    protected $casts = [
        'stock_quantity'  => 'integer',
        'min_stock_alert' => 'integer',
        'unit_cost'       => 'decimal:2',
    ];

    public const CATEGORIES = [
        'gift'        => 'Quà tặng doanh nghiệp',
        'publication' => 'Ấn phẩm / Brochure / Catalogue',
        'equipment'   => 'Vật tư / Standee / Thiết bị sự kiện',
        'clothing'    => 'Đồng phục / Áo thun sự kiện',
        'other'       => 'Vật phẩm khác',
    ];

    public static function generateCode(): string
    {
        $prefix = 'MKT-';
        $maxId = self::max('id') ?? 0;
        return $prefix . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Khác';
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->min_stock_alert;
    }

    public function transactions()
    {
        return $this->hasMany(MarketingItemTransaction::class)->orderBy('created_at', 'desc');
    }
}
