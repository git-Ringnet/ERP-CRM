<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostFormula extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'calculation_type',
        'fixed_amount',
        'percentage',
        'formula',
        'apply_to',
        'apply_conditions',
        'is_active',
        'description',
    ];

    protected $casts = [
        'fixed_amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'apply_conditions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Calculate cost based on formula
     */
    public function calculateCost(float $revenue, array $context = []): float
    {
        switch ($this->calculation_type) {
            case 'fixed':
                return $this->fixed_amount ?? 0;
                
            case 'percentage':
                return $revenue * ($this->percentage ?? 0) / 100;
                
            case 'formula':
                return $this->evaluateFormula($revenue, $context);
                
            default:
                return 0;
        }
    }

    /**
     * Evaluate custom formula
     */
    private function evaluateFormula(float $revenue, array $context): float
    {
        if (empty($this->formula)) {
            return 0;
        }

        // Replace variables in formula
        $formula = $this->formula;
        $formula = str_replace('revenue', $revenue, $formula);
        $formula = str_replace('quantity', $context['quantity'] ?? 0, $formula);
        $formula = str_replace('distance', $context['distance'] ?? 0, $formula);
        $formula = str_replace('weight', $context['weight'] ?? 0, $formula);

        try {
            // Simple eval (in production, use a proper expression evaluator library)
            $result = eval("return {$formula};");
            return is_numeric($result) ? (float) $result : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if formula applies to given conditions
     */
    public function appliesTo(array $conditions): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->apply_to === 'all') {
            return true;
        }

        $applyConditions = $this->apply_conditions ?? [];

        switch ($this->apply_to) {
            case 'product':
                $productIds = $applyConditions['product_ids'] ?? [];
                return in_array($conditions['product_id'] ?? null, $productIds);
                
            case 'category':
                $categoryIds = $applyConditions['category_ids'] ?? [];
                return in_array($conditions['category_id'] ?? null, $categoryIds);
                
            case 'customer':
                $customerIds = $applyConditions['customer_ids'] ?? [];
                return in_array($conditions['customer_id'] ?? null, $customerIds);
                
            default:
                return false;
        }
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'shipping' => 'Vận chuyển',
            'marketing' => 'Marketing',
            'commission' => 'Hoa hồng',
            'other' => 'Khác',
            default => 'Không xác định',
        };
    }

    /**
     * Get calculation type label
     */
    public function getCalculationTypeLabelAttribute(): string
    {
        return match($this->calculation_type) {
            'fixed' => 'Cố định',
            'percentage' => 'Phần trăm',
            'formula' => 'Công thức',
            default => 'Không xác định',
        };
    }
}
