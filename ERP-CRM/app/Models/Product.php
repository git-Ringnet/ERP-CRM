<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'price',
        'cost',
        'stock',
        'min_stock',
        'max_stock',
        'management_type',
        'auto_generate_serial',
        'serial_prefix',
        'expiry_months',
        'track_expiry',
        'description',
        'note',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
        'auto_generate_serial' => 'boolean',
        'expiry_months' => 'integer',
        'track_expiry' => 'boolean',
    ];

    /**
     * Relationship: Product has many inventories
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Scope for searching products by name, code, or category
     * Requirements: 4.11
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering products by management type
     * Requirements: 4.12
     */
    public function scopeFilterByManagementType(Builder $query, ?string $managementType): Builder
    {
        if (empty($managementType)) {
            return $query;
        }

        return $query->where('management_type', $managementType);
    }
}
