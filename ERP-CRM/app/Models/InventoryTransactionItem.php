<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'unit',
        'description',
        'comments',
        'serial_number',
        'cost',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the transaction for this item.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'transaction_id');
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get total value of this item.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * ($this->cost ?? 0);
    }
}
