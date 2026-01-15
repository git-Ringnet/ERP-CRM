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
        'unit',
        'serial_number',
        'comments',
        'is_liquidation',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_liquidation' => 'boolean',
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
}
