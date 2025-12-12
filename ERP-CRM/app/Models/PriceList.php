<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'customer_id',
        'start_date',
        'end_date',
        'discount_percent',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeValidNow(Builder $query): Builder
    {
        $today = now()->toDateString();
        return $query->where(function ($q) use ($today) {
            $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
        })->where(function ($q) use ($today) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
        });
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'standard' => 'Bảng giá chuẩn',
            'customer' => 'Giá theo khách hàng',
            'promotion' => 'Khuyến mãi',
            'wholesale' => 'Giá sỉ',
            default => 'Không xác định',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'standard' => 'bg-blue-100 text-blue-800',
            'customer' => 'bg-purple-100 text-purple-800',
            'promotion' => 'bg-orange-100 text-orange-800',
            'wholesale' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        
        $today = now();
        if ($this->start_date && $today->lt($this->start_date)) return false;
        if ($this->end_date && $today->gt($this->end_date)) return false;
        
        return true;
    }

    public static function getForCustomer(?int $customerId): ?self
    {
        // Priority: Customer specific > Promotion > Wholesale > Standard
        return static::active()
            ->validNow()
            ->where(function ($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                  ->orWhereNull('customer_id');
            })
            ->orderByRaw('CASE WHEN customer_id IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('priority', 'desc')
            ->first();
    }

    public function getPriceForProduct(int $productId, float $quantity = 1): ?float
    {
        $item = $this->items()
            ->where('product_id', $productId)
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('min_quantity', 'desc')
            ->first();

        if (!$item) return null;

        $price = $item->price;
        
        // Apply item discount
        if ($item->discount_percent > 0) {
            $price = $price * (1 - $item->discount_percent / 100);
        }
        
        // Apply list discount
        if ($this->discount_percent > 0) {
            $price = $price * (1 - $this->discount_percent / 100);
        }

        return $price;
    }
}
