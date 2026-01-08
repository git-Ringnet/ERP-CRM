<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity',
        'unit',
        'serial_number',
        'comments',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Get the transfer that owns this item.
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    /**
     * Get the product for this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
