<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code', 'supplier_id', 'supplier_quotation_id', 'order_date', 'expected_delivery',
        'actual_delivery', 'delivery_address', 'subtotal', 'discount_percent', 'discount_amount',
        'shipping_cost', 'other_cost', 'vat_percent', 'vat_amount', 'total', 'paid_amount',
        'debt_amount', 'payment_status', 'payment_terms',
        'status', 'note', 'created_by', 'approved_by', 'approved_at', 'sent_at', 'confirmed_at',
        'currency_id', 'exchange_rate', 'total_foreign',
        'paid_amount_foreign', 'debt_amount_foreign',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery' => 'date',
        'actual_delivery' => 'date',
        'subtotal' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'debt_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'total_foreign' => 'decimal:4',
        'paid_amount_foreign' => 'decimal:4',
        'debt_amount_foreign' => 'decimal:4',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierQuotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplierPaymentHistories(): HasMany
    {
        return $this->hasMany(SupplierPaymentHistory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Nháp',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'sent' => 'Đã gửi NCC',
            'confirmed' => 'NCC xác nhận',
            'shipping' => 'Đang giao',
            'partial_received' => 'Nhận một phần',
            'received' => 'Đã nhận hàng',
            'cancelled' => 'Đã hủy',
            default => $this->status
        };
    }

    public function getPaymentTermsLabelAttribute(): string
    {
        return match($this->payment_terms) {
            'immediate' => 'Thanh toán ngay',
            'cod' => 'COD - Thanh toán khi nhận hàng',
            'net15' => 'Net 15 - Thanh toán trong 15 ngày',
            'net30' => 'Net 30 - Thanh toán trong 30 ngày',
            'net45' => 'Net 45 - Thanh toán trong 45 ngày',
            'net60' => 'Net 60 - Thanh toán trong 60 ngày',
            default => $this->payment_terms
        };
    }

    public static function generateCode(): string
    {
        $lastCode = self::whereYear('created_at', now()->year)
            ->orderBy('id', 'desc')
            ->value('code');
        
        if ($lastCode) {
            $number = (int) substr($lastCode, -4) + 1;
        } else {
            $number = 1;
        }
        
        return 'PO' . now()->format('y') . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = round($this->items->sum('total'), 2);
        $this->discount_amount = round($this->subtotal * ($this->discount_percent / 100), 2);
        $afterDiscount = $this->subtotal - $this->discount_amount;
        $beforeVat = $afterDiscount + $this->shipping_cost + $this->other_cost;
        $this->vat_amount = round($beforeVat * ($this->vat_percent / 100), 2);
        $this->total = round($beforeVat + $this->vat_amount, 2);
    }

    /**
     * Update debt amount and payment status
     */
    public function updateDebt(): void
    {
        $this->debt_amount = $this->total - $this->paid_amount;
        
        // Update foreign debt if applicable
        if ($this->exchange_rate > 0) {
            $this->debt_amount_foreign = $this->total_foreign - $this->paid_amount_foreign;
        }

        if ($this->paid_amount <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($this->paid_amount >= $this->total) {
            $this->payment_status = 'paid';
        } else {
            $this->payment_status = 'partial';
        }
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
     * Get payment status color class
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
}
