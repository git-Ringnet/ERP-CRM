<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'project_id',
        'quantity',
        'price',
        'cost_price',
        'total',
        'cost_total',
        'warranty_months',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'total' => 'decimal:2',
        'cost_total' => 'decimal:2',
        'warranty_months' => 'integer',
    ];

    /**
     * Get profit for this item
     */
    public function getProfitAttribute(): float
    {
        return $this->total - $this->cost_total;
    }

    /**
     * Get profit percent for this item
     */
    public function getProfitPercentAttribute(): float
    {
        if ($this->total > 0) {
            return (($this->total - $this->cost_total) / $this->total) * 100;
        }
        return 0;
    }

    /**
     * Relationship with Sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relationship with Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship with Project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
