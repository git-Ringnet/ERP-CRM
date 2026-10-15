<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'file_name',
        'created_by',
        'total_rows',
        'imported_count',
        'updated_count',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'imported_count' => 'integer',
        'updated_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'import_batch', 'batch_code');
    }

    /**
     * Get remaining active products that still exist in the database from this batch.
     */
    public function getRemainingProductsCountAttribute(): int
    {
        return $this->products()->count();
    }
}
