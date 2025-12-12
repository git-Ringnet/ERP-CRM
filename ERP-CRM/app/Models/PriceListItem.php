<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_list_id',
        'product_id',
        'price',
        'min_quantity',
        'discount_percent',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_quantity' => 'decimal:2',
        'discount_percent' => 'decimal:2',
    ];

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getFinalPriceAttribute(): float
    {
        $price = $this->price;
        
        if ($this->discount_percent > 0) {
            $price = $price * (1 - $this->discount_percent / 100);
        }
        
        return $price;
    }
}
