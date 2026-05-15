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
        'payment_due_date',
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
        'invoice_date',
        'payment_terms',
    ];

    protected $casts = [
        'date' => 'date',
        'invoice_date' => 'date',
        'payment_due_date' => 'date',
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
        'payment_terms' => 'array',
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
     * Relationship with PurchaseOrder (linked POs)
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Relationship with SaleAttachment
     */
    public function attachments()
    {
        return $this->hasMany(SaleAttachment::class);
    }

    /**
     * Relationship with SaleOrderRequest (yêu cầu đặt hàng)
     */
    public function orderRequests()
    {
        return $this->hasMany(SaleOrderRequest::class);
    }

    /**
     * Relationship with InvoiceRequest (yêu cầu xuất hóa đơn)
     */
    public function invoiceRequests()
    {
        return $this->hasMany(InvoiceRequest::class);
    }

    /**
     * Get all Purchase Orders associated with this sale
     * (Both direct link and aggregated via Order Requests)
     */
    public function getAllPurchaseOrdersAttribute()
    {
        // 1. Direct POs
        $directPos = $this->purchaseOrders;

        // 2. Aggregated POs (via Order Request Items)
        $aggregatedPos = PurchaseOrder::whereHas('items', function($q) {
            $q->whereHas('saleOrderRequestItem', function($sq) {
                $sq->whereHas('saleOrderRequest', function($sr) {
                    $sr->where('sale_id', $this->id);
                });
            });
        })->get();

        return $directPos->concat($aggregatedPos)->unique('id');
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
              ->orWhere('customer_name', 'like', "%{$search}%")
              ->orWhere('note', 'like', "%{$search}%");
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
     * Check if all items in all order requests are fully received
     */
    public function isFullyReceived(): bool
    {
        // If no order requests, we assume it's fully ready (from procurement perspective)
        if ($this->orderRequests->count() === 0) {
            return true;
        }

        foreach ($this->orderRequests as $request) {
            foreach ($request->items as $item) {
                if ($item->received_quantity_total < $item->quantity) {
                    return false;
                }
            }
        }
        return true;
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
     * Get dashboard status based on Sale + linked PO status
     * Chờ đặt → Đã đặt → Đang về/Hold → Đã về → Xuất hóa đơn → Hoàn thành
     */
    public function getDashboardStatusAttribute(): string
    {
        // 1. Cancelled / Pending
        if ($this->status === 'cancelled') return 'cancelled';
        if ($this->status === 'pending') return 'pending';

        // 2. Completed
        if ($this->status === 'completed') return 'completed';

        // 3. Xuất hóa đơn (shipping to customer)
        if ($this->status === 'shipping') return 'invoiced';

        // 4. Check linked PO status
        $associatedPos = $this->all_purchase_orders;
        
        if ($associatedPos->isEmpty()) {
            return 'waiting_order'; // Chờ đặt
        }

        // 5. Check if any PO is on hold
        if ($associatedPos->contains('is_hold', true)) {
            return 'hold';
        }

        // 6. Determine overall procurement status
        // A sale is 'received' only if ALL items in all requests are fully received
        if ($this->isFullyReceived()) {
            $status = 'received';
            
            // 7. Check Invoice Request (Overwrites 'received' if request exists)
            $latestInvoice = $this->invoiceRequests()->latest()->first();
            if ($latestInvoice) {
                $status = 'invoicing';
            }
            return $status;
        }

        // If not fully received, determine if it's 'ordered' or 'in_transit'
        // 'in_transit' if any PO is shipping or partial_received or received (but SO is not fully received)
        $hasInTransit = $associatedPos->contains(function($po) {
            return in_array($po->status, ['shipping', 'partial_received', 'received']);
        });

        if ($hasInTransit) {
            return 'in_transit';
        }

        // If all POs are just approved/draft/pending, it's 'ordered'
        return 'ordered';
    }

    /**
     * Get dashboard status label (Vietnamese)
     */
    public function getDashboardStatusLabelAttribute(): string
    {
        return match($this->dashboard_status) {
            'pending' => 'Chờ duyệt',
            'waiting_order' => 'Đã duyệt',
            'ordered' => 'Đã đặt hàng',
            'in_transit' => 'Chờ hàng về',
            'hold' => 'Hold',
            'received' => 'Hàng về',
            'invoicing' => 'Hóa đơn',
            'invoiced' => 'Giao hàng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    /**
     * Get dashboard status color class
     */
    public function getDashboardStatusColorAttribute(): string
    {
        return match($this->dashboard_status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'waiting_order' => 'bg-blue-100 text-blue-800',
            'ordered' => 'bg-indigo-100 text-indigo-800',
            'in_transit' => 'bg-purple-100 text-purple-800',
            'hold' => 'bg-orange-100 text-orange-800',
            'received' => 'bg-emerald-100 text-emerald-800',
            'invoicing' => 'bg-cyan-100 text-cyan-800',
            'invoiced' => 'bg-amber-100 text-amber-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get dashboard step index (0 to 6)
     */
    public function getDashboardStepAttribute(): int
    {
        return match($this->dashboard_status) {
            'pending' => 0,
            'waiting_order' => 1,
            'ordered' => 2,
            'in_transit' => 3,
            'received' => 4,
            'invoicing' => 5,
            'invoiced' => 6,
            'completed' => 7,
            'hold' => 3, // Treat hold as transit step
            'cancelled' => -1,
            default => 0,
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
        $base = match($this->payment_status) {
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Thanh toán một phần',
            'paid' => 'Đã thanh toán',
            default => 'Chưa thanh toán',
        };

        // Thêm trạng thái tới hạn / quá hạn
        if ($this->payment_status !== 'paid' && $this->payment_due_date) {
            $today = now()->startOfDay();
            $dueDate = $this->payment_due_date->startOfDay();
            $daysUntilDue = $today->diffInDays($dueDate, false);

            if ($daysUntilDue < 0) {
                return 'Quá hạn ' . abs($daysUntilDue) . ' ngày';
            } elseif ($daysUntilDue <= 3) {
                return 'Tới hạn' . ($daysUntilDue > 0 ? " ({$daysUntilDue} ngày)" : ' (Hôm nay)');
            }
        }

        return $base;
    }

    /**
     * Get payment status color
     */
    public function getPaymentStatusColorAttribute(): string
    {
        // Ưu tiên hiển thị trạng thái tới hạn / quá hạn
        if ($this->payment_status !== 'paid' && $this->payment_due_date) {
            $today = now()->startOfDay();
            $dueDate = $this->payment_due_date->startOfDay();
            $daysUntilDue = $today->diffInDays($dueDate, false);

            if ($daysUntilDue < 0) {
                return 'bg-red-600 text-white animate-pulse';
            } elseif ($daysUntilDue <= 3) {
                return 'bg-orange-500 text-white';
            }
        }

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
        
        // 1. Chi phí từ các item (Standard OpEx)
        foreach ($this->items as $item) {
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
            
            // POC / Triển khai / Thuế nhà thầu: VND hoặc % trên giá vốn dòng
            if (! is_null($item->technical_poc_percent) && $item->technical_poc_percent > 0) {
                $totalCost += ($costBase * (float) $item->technical_poc_percent / 100);
            } else {
                $totalCost += ($item->technical_poc_cost ?: 0);
            }

            if (! is_null($item->implementation_cost_percent) && $item->implementation_cost_percent > 0) {
                $totalCost += ($costBase * (float) $item->implementation_cost_percent / 100);
            } else {
                $totalCost += ($item->implementation_cost ?: 0);
            }

            if (! is_null($item->contractor_tax_percent) && $item->contractor_tax_percent > 0) {
                $totalCost += ($costBase * (float) $item->contractor_tax_percent / 100);
            } else {
                $totalCost += ($item->contractor_tax ?: 0);
            }
        }

        // 2. Chi phí bổ sung từ bảng sale_expenses (loại trừ các loại đã tính ở trên nếu là dạng đồng bộ)
        $standardTypes = [
            'Chi phí Tài chính',
            'Lãi vay phát sinh do nợ quá hạn',
            'Chi phí Quản lí, Back Office & kỹ thuật',
            '24x7 Support cost',
            'Other Support',
            'Technical support/POC',
            'Chi phí triển khai hợp đồng',
            'Thuế nhà thầu',
        ];

        $costBaseTotal = $this->getCostOfGoodsSold();

        foreach ($this->expenses as $expense) {
            // Nếu là loại standard và đã được sync vào item thì bỏ qua để tránh tính trùng
            // (Thường fixed expenses sẽ được sync vào technical_poc_cost hoặc implementation_cost)
            if (in_array($expense->type, $standardTypes)) continue;

            if ($expense->input_mode === 'percent') {
                $totalCost += ($costBaseTotal * ($expense->percent_value / 100));
            } else {
                $totalCost += ($expense->amount ?: 0);
            }
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
     * Margin = Doanh thu thuần - Giá vốn - Tổng chi phí (khớp 100% với bảng P&L)
     */
    public function calculateMargin(): void
    {
        $this->loadMissing(['items', 'expenses']);

        // Revenue Excl VAT — khớp với P&L: sum(revenue_total) * (1 - discount%)
        $discountAmount = $this->subtotal * ($this->discount / 100);
        $netRevenue = $this->subtotal - $discountAmount;

        $totalNetProfit = 0;

        foreach ($this->items as $item) {
            $itemRevenue = ($item->total ?: 0) * (1 - ($this->discount ?? 0) / 100);
            $costTotal   = $item->cost_total ?: 0;

            // Gross profit per row
            $grossProfit = $itemRevenue - $costTotal;

            // --- Tính chi phí OpEx từng dòng (logic giống P&L JS) ---
            $financeV = 0;
            if (!is_null($item->finance_cost_percent) && floatval($item->finance_cost_percent) > 0) {
                $financeV = round($costTotal * $item->finance_cost_percent / 100);
            }

            $overdueV = 0;
            if (!is_null($item->overdue_interest_percent) && floatval($item->overdue_interest_percent) > 0) {
                $overdueV = round($costTotal * $item->overdue_interest_percent / 100);
            } else {
                $overdueV = round($item->overdue_interest_cost ?: 0);
            }

            $mgmtV = 0;
            if (!is_null($item->management_cost_percent) && floatval($item->management_cost_percent) > 0) {
                $mgmtV = round($costTotal * $item->management_cost_percent / 100);
            }

            $supportV = 0;
            if (!is_null($item->support_247_cost_percent) && floatval($item->support_247_cost_percent) > 0) {
                $supportV = round($costTotal * $item->support_247_cost_percent / 100);
            }

            $otherV = 0;
            if (!is_null($item->other_support_cost) && floatval($item->other_support_cost) > 0) {
                $otherV = round($costTotal * $item->other_support_cost / 100);
            }

            // POC
            $pocV = 0;
            if (!is_null($item->technical_poc_percent) && floatval($item->technical_poc_percent) > 0) {
                $pocV = round($costTotal * $item->technical_poc_percent / 100);
            } else {
                $pocV = round($item->technical_poc_cost ?: 0);
            }

            // Implementation
            $impV = 0;
            if (!is_null($item->implementation_cost_percent) && floatval($item->implementation_cost_percent) > 0) {
                $impV = round($costTotal * $item->implementation_cost_percent / 100);
            } else {
                $impV = round($item->implementation_cost ?: 0);
            }

            // Contractor tax
            $taxV = round($item->contractor_tax ?: 0);

            // Extra expenses per item
            $extraSum = 0;
            $itemExtraData = $item->extra_expenses_data ?? [];
            foreach ($itemExtraData as $expId => $amt) {
                $extraSum += round(floatval($amt));
            }

            // Tổng chi phí dòng
            $rowTotalCosts = $financeV + $overdueV + $mgmtV + $supportV + $otherV + $pocV + $impV + $taxV + $extraSum;

            // Net profit dòng
            $rowNetProfit = $grossProfit - $rowTotalCosts;
            $totalNetProfit += $rowNetProfit;
        }

        // Chi phí % phân bổ từ sale_expenses (loại extra, dạng percent)
        $standardTypes = [
            'Chi phí Tài chính',
            'Lãi vay phát sinh do nợ quá hạn',
            'Chi phí Quản lí, Back Office & kỹ thuật',
            '24x7 Support cost',
            'Other Support',
            'Technical support/POC',
            'Chi phí triển khai hợp đồng',
            'Thuế nhà thầu',
        ];

        $costBaseTotal = $this->getCostOfGoodsSold();

        foreach ($this->expenses as $expense) {
            if (in_array($expense->type, $standardTypes)) continue;

            if ($expense->input_mode === 'percent') {
                // Percent-based expenses: tính trên giá vốn tổng
                $totalNetProfit -= round($costBaseTotal * ($expense->percent_value / 100));
            }
            // Fixed-mode extra expenses: đã tính qua extra_expenses_data ở trên
        }

        $this->margin = round($totalNetProfit);
        $this->cost = round($netRevenue - $this->getCostOfGoodsSold() - $totalNetProfit); // Giữ sync field cost

        if ($netRevenue > 0) {
            $this->margin_percent = ($this->margin / $netRevenue) * 100;
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
