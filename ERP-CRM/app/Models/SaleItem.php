<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SaleItem extends Model
{
    use HasFactory;

    /**
     * Warranty status constants
     */
    public const WARRANTY_STATUS_ACTIVE = 'active';
    public const WARRANTY_STATUS_EXPIRED = 'expired';
    public const WARRANTY_STATUS_NO_WARRANTY = 'no_warranty';

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
        'warranty_start_date',
        'is_liquidation',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'total' => 'decimal:2',
        'cost_total' => 'decimal:2',
        'warranty_months' => 'integer',
        'warranty_start_date' => 'date',
        'is_liquidation' => 'boolean',
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

    /**
     * Get warranty end date
     * Requirements: 1.2, 6.1
     */
    public function getWarrantyEndDateAttribute(): ?Carbon
    {
        if (!$this->warranty_start_date || !$this->warranty_months || $this->warranty_months <= 0) {
            return null;
        }
        return $this->warranty_start_date->copy()->addMonths($this->warranty_months);
    }

    /**
     * Get warranty status
     * Requirements: 1.5, 6.2, 6.3, 6.4
     */
    public function getWarrantyStatusAttribute(): string
    {
        // No warranty if warranty_months is 0 or null
        if (!$this->warranty_months || $this->warranty_months <= 0) {
            return self::WARRANTY_STATUS_NO_WARRANTY;
        }

        // No warranty if no start date
        if (!$this->warranty_start_date) {
            return self::WARRANTY_STATUS_NO_WARRANTY;
        }

        $endDate = $this->warranty_end_date;
        if (!$endDate) {
            return self::WARRANTY_STATUS_NO_WARRANTY;
        }

        // Check if warranty is still active
        return now()->lte($endDate) ? self::WARRANTY_STATUS_ACTIVE : self::WARRANTY_STATUS_EXPIRED;
    }

    /**
     * Get days remaining in warranty
     * Requirements: 4.1
     */
    public function getWarrantyDaysRemainingAttribute(): ?int
    {
        $endDate = $this->warranty_end_date;
        if (!$endDate) {
            return null;
        }
        return (int) now()->diffInDays($endDate, false);
    }

    /**
     * Get warranty status labels
     */
    public static function getWarrantyStatusLabels(): array
    {
        return [
            self::WARRANTY_STATUS_ACTIVE => 'Đang bảo hành',
            self::WARRANTY_STATUS_EXPIRED => 'Hết hạn',
            self::WARRANTY_STATUS_NO_WARRANTY => 'Không bảo hành',
        ];
    }

    /**
     * Get warranty status colors for UI
     */
    public static function getWarrantyStatusColors(): array
    {
        return [
            self::WARRANTY_STATUS_ACTIVE => 'green',
            self::WARRANTY_STATUS_EXPIRED => 'red',
            self::WARRANTY_STATUS_NO_WARRANTY => 'gray',
        ];
    }
}
