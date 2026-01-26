<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'unit',
        'serial_number',
        'cost',
        'warehouse_price',
        'comments',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost' => 'decimal:2',
        'warehouse_price' => 'decimal:2',
    ];

    /**
     * Get the import that owns this item.
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for this item.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Calculate warehouse price including service cost allocation.
     */
    public function calculateWarehousePrice(float $serviceCostPerUnit = 0): void
    {
        // Warehouse price = cost + allocated service cost per unit
        $this->warehouse_price = $this->cost + $serviceCostPerUnit;
    }
}
