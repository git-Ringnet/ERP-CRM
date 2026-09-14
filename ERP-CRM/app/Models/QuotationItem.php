<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'product_id',
        'product_name',
        'description',
        'product_code',
        'quantity',
        'price',
        'total',
        'vat',
        'vat_amount',
        'note',
        'custom_fields',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'vat' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'custom_fields' => 'array',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * Product relationship - nullable for free-text products not in inventory
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get product's pricelist price in USD ($)
     */
    public function getPricelistPriceAttribute(): float
    {
        if (isset($this->custom_fields['pricelist']) && $this->custom_fields['pricelist'] !== '' && $this->custom_fields['pricelist'] !== null) {
            $val = str_replace(['$', ','], '', (string)$this->custom_fields['pricelist']);
            if (is_numeric($val)) {
                return (float)$val;
            }
        }

        $productCode = $this->product_code ?: ($this->product->code ?? null);
        if (!$productCode) {
            return 0.0;
        }

        $skuCode = trim($productCode);
        
        $priceItem = \App\Models\SupplierPriceListItem::where('sku', $skuCode)
            ->whereHas('priceList', function($q) { 
                $q->where('is_active', true); 
            })
            ->join('supplier_price_lists', 'supplier_price_list_items.supplier_price_list_id', '=', 'supplier_price_lists.id')
            ->select('supplier_price_list_items.*')
            ->orderBy('supplier_price_lists.effective_date', 'desc')
            ->orderBy('supplier_price_list_items.id', 'desc')
            ->first();

        if (!$priceItem) {
            // Fallback to fuzzy search if exact match doesn't exist
            $priceItem = \App\Models\SupplierPriceListItem::where('sku', 'like', '%' . $skuCode . '%')
                ->whereHas('priceList', function($q) { 
                    $q->where('is_active', true); 
                })
                ->join('supplier_price_lists', 'supplier_price_list_items.supplier_price_list_id', '=', 'supplier_price_lists.id')
                ->select('supplier_price_list_items.*')
                ->orderBy('supplier_price_lists.effective_date', 'desc')
                ->orderBy('supplier_price_list_items.id', 'desc')
                ->first();
        }

        if (!$priceItem) {
            return 0.0;
        }

        $pl = $priceItem->priceList;
        $rawPrice = $pl->getPrimaryPriceForItem($priceItem);
        if ($rawPrice === null) {
            return 0.0;
        }

        // If the currency of the price list is VND, convert it to USD
        $plCurrency = strtoupper(trim($pl->currency ?? 'USD'));
        if ($plCurrency === 'VND' || $plCurrency === 'Đ') {
            $exchangeRate = floatval($pl->exchange_rate ?: 24000);
            if ($exchangeRate > 0) {
                return (float)($rawPrice / $exchangeRate);
            }
        }

        return (float)$rawPrice;
    }
}
