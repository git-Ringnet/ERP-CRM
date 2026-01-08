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
        'quantity',
        'unit',
        'serial_number',
        'cost',
        'comments',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost' => 'decimal:2',
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
}
