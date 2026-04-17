<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Sale extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'type',
        'project_id',
        'customer_id',
        'customer_name',
        'user_id',
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
        'currency_id',
        'exchange_rate',
        'total_foreign',
        'paid_amount_foreign',
        'debt_amount_foreign',
        'pl_status',
        'pl_approved_at',
        'pl_approved_by',
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
        'exchange_rate' => 'decimal:6',
        'total_foreign' => 'decimal:4',
        'paid_amount_foreign' => 'decimal:4',
        'debt_amount_foreign' => 'decimal:4',
        'pl_approved_at' => 'datetime',
    ];

    /**
     * Relationship with Currency
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Relationship with Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with User (creator/salesperson)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with User (P&L Approver)
     */
    public function plApprover()
    {
        return $this->belongsTo(User::class, 'pl_approved_by');
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
     * Get P&L status label
     */
    public function getPlStatusLabelAttribute(): string
    {
        return match($this->pl_status) {
            'draft' => 'Nháp (P&L)',
            'pending' => 'Chờ duyệt (P&L)',
            'approved' => 'Đã duyệt (P&L)',
            'rejected' => 'Từ chối (P&L)',
            default => 'Chưa lập',
        };
    }

    /**
     * Get P&L status color class
     */
    public function getPlStatusColorAttribute(): string
    {
        return match($this->pl_status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if P&L is editable
     */
    public function isPlEditable(): bool
    {
        return in_array($this->pl_status, ['draft', 'rejected', null, '']);
    }

    /**
     * Check if the sale is currently in the approval process
     */
    public function isPendingApproval(): bool
    {
        return $this->pl_status === 'pending';
    }

    /**
     * Get the ID of the person who created this sale (for notifications)
     */
    public function getCreatorId(): ?int
    {
        return $this->user_id;
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
     * Tính tổng chi phí từ P&L (sale_items) thay vì sale_expenses
     */
    public function calculateTotalCost(): void
    {
        $totalCost = 0;
        
        foreach ($this->items as $item) {
            // Tính chi phí % dựa trên cost_total
            $costBase = $item->cost_total ?: 0;
            
            // Chi phí Tài chính
            if (!is_null($item->finance_cost_percent) && $item->finance_cost_percent > 0) {
                $totalCost += ($costBase * $item->finance_cost_percent / 100);
            }
            
            // Lãi vay phát sinh (% hoặc fixed)
            if (!is_null($item->overdue_interest_percent) && $item->overdue_interest_percent > 0) {
                $totalCost += ($costBase * $item->overdue_interest_percent / 100);
            } else {
                $totalCost += ($item->overdue_interest_cost ?: 0);
            }
            
            // Chi phí Quản lí
            if (!is_null($item->management_cost_percent) && $item->management_cost_percent > 0) {
                $totalCost += ($costBase * $item->management_cost_percent / 100);
            }
            
            // 24x7 Support
            if (!is_null($item->support_247_cost_percent) && $item->support_247_cost_percent > 0) {
                $totalCost += ($costBase * $item->support_247_cost_percent / 100);
            }
            
            // Other Support
            if (!is_null($item->other_support_cost) && $item->other_support_cost > 0) {
                $totalCost += ($costBase * $item->other_support_cost / 100);
            }
            
            // Chi phí cố định
            $totalCost += ($item->technical_poc_cost ?: 0);
            $totalCost += ($item->implementation_cost ?: 0);
            $totalCost += ($item->contractor_tax ?: 0);
        }
        
        $this->cost = round($totalCost);
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
        
        // Update foreign debt if applicable
        if ($this->exchange_rate > 0) {
            $this->debt_amount_foreign = $this->total_foreign - $this->paid_amount_foreign;
        }

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
