<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'project_id',
        'customer_id',
        'customer_name',
        'date',
        'delivery_address',
        'subtotal',
        'discount',
        'vat',
        'total',
        'cost',
        'margin',
        'margin_percent',
        'paid_amount',
        'debt_amount',
        'payment_status',
        'status',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'total' => 'decimal:2',
        'cost' => 'decimal:2',
        'margin' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'debt_amount' => 'decimal:2',
    ];

    /**
     * Relationship with Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with Project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relationship with SaleItem
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Relationship with SaleExpense
     */
    public function expenses()
    {
        return $this->hasMany(SaleExpense::class);
    }

    /**
     * Scope for searching sales
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('customer_name', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering by status
     */
    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeFilterByType(Builder $query, ?string $type): Builder
    {
        if (empty($type)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'shipping' => 'bg-purple-100 text-purple-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'retail' => 'Bán lẻ',
            'project' => 'Bán theo dự án',
            default => 'Không xác định',
        };
    }

    /**
     * Get payment status label
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Thanh toán một phần',
            'paid' => 'Đã thanh toán',
            default => 'Chưa thanh toán',
        };
    }

    /**
     * Get payment status color
     */
    public function getPaymentStatusColorAttribute(): string
    {
        return match($this->payment_status) {
            'unpaid' => 'bg-red-100 text-red-800',
            'partial' => 'bg-yellow-100 text-yellow-800',
            'paid' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Calculate total cost from expenses
     */
    public function calculateTotalCost(): void
    {
        $this->cost = $this->expenses()->sum('amount');
    }

    /**
     * Get total cost of goods sold (giá vốn hàng bán)
     */
    public function getCostOfGoodsSold(): float
    {
        return $this->items()->sum('cost_total');
    }

    /**
     * Calculate margin
     * Margin = Doanh thu - Giá vốn hàng bán - Chi phí bán hàng
     */
    public function calculateMargin(): void
    {
        $this->calculateTotalCost();
        
        // Get cost of goods sold from items
        $costOfGoodsSold = $this->getCostOfGoodsSold();
        
        // Margin = Total - Cost of Goods Sold - Operating Expenses
        $this->margin = $this->total - $costOfGoodsSold - $this->cost;
        
        // Calculate margin percent, handle edge cases
        if ($this->total > 0) {
            $this->margin_percent = ($this->margin / $this->total) * 100;
            // Cap at reasonable limits to avoid database overflow
            $this->margin_percent = max(-999.99, min(999.99, $this->margin_percent));
        } else {
            $this->margin_percent = 0;
        }
    }

    /**
     * Get expenses by type
     */
    public function getExpensesByType(string $type): float
    {
        return $this->expenses()->where('type', $type)->sum('amount');
    }

    /**
     * Check if margin is negative (loss)
     */
    public function hasNegativeMargin(): bool
    {
        return $this->margin < 0;
    }

    /**
     * Get margin status color
     */
    public function getMarginColorAttribute(): string
    {
        if ($this->margin < 0) {
            return 'text-red-600';
        } elseif ($this->margin_percent < 10) {
            return 'text-yellow-600';
        } else {
            return 'text-green-600';
        }
    }

    /**
     * Update debt amount
     */
    public function updateDebt(): void
    {
        $this->debt_amount = $this->total - $this->paid_amount;
        
        // Update payment status
        if ($this->paid_amount <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($this->paid_amount >= $this->total) {
            $this->payment_status = 'paid';
        } else {
            $this->payment_status = 'partial';
        }
    }
}
