<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingAllocationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_allocation_id', 'product_id', 'quantity',
        'unit_value', 'total_value', 'weight', 'volume',
        'allocated_cost', 'allocated_cost_per_unit'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_value' => 'decimal:2',
        'total_value' => 'decimal:2',
        'weight' => 'decimal:3',
        'volume' => 'decimal:4',
        'allocated_cost' => 'decimal:2',
        'allocated_cost_per_unit' => 'decimal:2',
    ];

    public function shippingAllocation(): BelongsTo
    {
        return $this->belongsTo(ShippingAllocation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateTotalValue(): void
    {
        $this->total_value = $this->quantity * $this->unit_value;
    }
}
