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
        'usd_price',
        'exchange_rate',
        'discount_rate',
        'import_cost_rate',
        'estimated_cost_usd',
        'finance_cost_percent',
        'overdue_interest_cost',
        'management_cost_percent',
        'support_247_cost_percent',
        'other_support_cost',
        'technical_poc_cost',
        'implementation_cost',
        'contractor_tax',
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
        'usd_price' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'import_cost_rate' => 'decimal:2',
        'estimated_cost_usd' => 'decimal:2',
        'finance_cost_percent' => 'decimal:2',
        'overdue_interest_cost' => 'decimal:2',
        'management_cost_percent' => 'decimal:2',
        'support_247_cost_percent' => 'decimal:2',
        'other_support_cost' => 'decimal:2',
        'technical_poc_cost' => 'decimal:2',
        'implementation_cost' => 'decimal:2',
        'contractor_tax' => 'decimal:2',
    ];

    /**
     * Calculate Price VND from USD fields
     */
    public function calculateVndCost(): float
    {
        $estimatedCostUsd = $this->usd_price * (1 - ($this->discount_rate / 100)) * (1 + ($this->import_cost_rate / 100));
        return $estimatedCostUsd * $this->exchange_rate;
    }

    /**
     * Get calculated financial cost
     */
    public function getFinanceCostAttribute(): float
    {
        return $this->cost_total * ($this->finance_cost_percent / 100);
    }

    /**
     * Get calculated management cost
     */
    public function getManagementCostAttribute(): float
    {
        return $this->cost_total * ($this->management_cost_percent / 100);
    }

    /**
     * Get calculated support 24/7 cost
     */
    public function getSupport247CostAttribute(): float
    {
        return $this->cost_total * ($this->support_247_cost_percent / 100);
    }

    /**
     * Get calculated other support cost (percentage of cost_total)
     */
    public function getOtherSupportCostVndAttribute(): float
    {
        return $this->cost_total * ($this->other_support_cost / 100);
    }

    /**
     * Get total expenses for this item
     */
    public function getTotalExpensesAttribute(): float
    {
        return $this->finance_cost + 
               ($this->overdue_interest_cost ?: 0) +
               $this->management_cost + 
               $this->support_247_cost + 
               $this->other_support_cost_vnd + 
               $this->technical_poc_cost + 
               $this->implementation_cost + 
               $this->contractor_tax;
    }

    /**
     * Get net profit for this item
     */
    public function getNetProfitAttribute(): float
    {
        return $this->profit - $this->total_expenses;
    }

    /**
     * Get net profit percent
     */
    public function getNetProfitPercentAttribute(): float
    {
        if ($this->total > 0) {
            return ($this->net_profit / $this->total) * 100;
        }
        return 0;
    }

    /**
     * Get profit for this item
     */
    public function getProfitAttribute(): float
    {
        $sale = $this->sale;
        $exchangeRate = ($sale && $sale->currency && !$sale->currency->is_base) ? ($sale->exchange_rate ?: 1) : 1;
        $totalVnd = round($this->total * $exchangeRate);
        return $totalVnd - $this->cost_total;
    }

    /**
     * Get profit percent for this item
     */
    public function getProfitPercentAttribute(): float
    {
        $sale = $this->sale;
        $exchangeRate = ($sale && $sale->currency && !$sale->currency->is_base) ? ($sale->exchange_rate ?: 1) : 1;
        $totalVnd = round($this->total * $exchangeRate);
        
        if ($totalVnd > 0) {
            return (($totalVnd - $this->cost_total) / $totalVnd) * 100;
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
