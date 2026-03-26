<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'export_id',
        'product_id',
        'quantity',
        'requested_quantity',
        'unit',
        'serial_number',
        'comments',
        'is_liquidation',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'requested_quantity' => 'integer',
        'is_liquidation' => 'boolean',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the export that owns this item.
     */
    public function export(): BelongsTo
    {
        return $this->belongsTo(Export::class);
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get calculated total for the item (legacy support).
     */
    public function getCalculatedTotalAttribute()
    {
        if ($this->total > 0) return (float) $this->total;

        $price = $this->unit_price;
        if (!$price || $price == 0) {
            // Fallback for legacy records - try to get from inventory avg cost
            $price = \App\Models\Inventory::where('product_id', $this->product_id)
                ->where('warehouse_id', $this->warehouse_id ?? $this->export->warehouse_id)
                ->first()->avg_cost ?? 0;
        }
        return (float) ($price * $this->quantity);
    }
}
