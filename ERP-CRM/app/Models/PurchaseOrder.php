<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'supplier_id', 'supplier_quotation_id', 'order_date', 'expected_delivery',
        'actual_delivery', 'delivery_address', 'subtotal', 'discount_percent', 'discount_amount',
        'shipping_cost', 'other_cost', 'vat_percent', 'vat_amount', 'total', 'payment_terms',
        'status', 'note', 'created_by', 'approved_by', 'approved_at', 'sent_at', 'confirmed_at'
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
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

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
        $this->subtotal = $this->items->sum('total');
        $this->discount_amount = $this->subtotal * ($this->discount_percent / 100);
        $afterDiscount = $this->subtotal - $this->discount_amount;
        $beforeVat = $afterDiscount + $this->shipping_cost + $this->other_cost;
        $this->vat_amount = $beforeVat * ($this->vat_percent / 100);
        $this->total = $beforeVat + $this->vat_amount;
    }
}
