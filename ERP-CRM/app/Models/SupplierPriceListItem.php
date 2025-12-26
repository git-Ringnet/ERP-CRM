<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPriceListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_price_list_id',
        'sku',
        'product_name',
        'description',
        'category',
        'unit',
        'list_price',
        'price_1yr',
        'price_2yr',
        'price_3yr',
        'price_4yr',
        'price_5yr',
        'source_sheet',
        'extra_data',
    ];

    protected $casts = [
        'list_price' => 'decimal:2',
        'price_1yr' => 'decimal:2',
        'price_2yr' => 'decimal:2',
        'price_3yr' => 'decimal:2',
        'price_4yr' => 'decimal:2',
        'price_5yr' => 'decimal:2',
        'extra_data' => 'array',
    ];

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(SupplierPriceList::class, 'supplier_price_list_id');
    }

    public function getBestPriceAttribute(): ?float
    {
        $prices = array_filter([
            $this->list_price,
            $this->price_1yr,
            $this->price_2yr,
            $this->price_3yr,
            $this->price_4yr,
            $this->price_5yr,
        ]);

        return !empty($prices) ? min($prices) : null;
    }

    public function getPriceInVndAttribute(): ?float
    {
        if (!$this->list_price) return null;
        
        $exchangeRate = $this->priceList->exchange_rate ?? 1;
        return $this->list_price * $exchangeRate;
    }
}
