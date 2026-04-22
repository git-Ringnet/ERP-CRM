<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierQuotation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code', 'purchase_request_id', 'supplier_id', 'quotation_date', 'valid_until',
        'subtotal', 'discount_percent', 'discount_amount', 'shipping_cost',
        'vat_percent', 'vat_amount', 'total', 'delivery_days', 'payment_terms',
        'warranty', 'status', 'note', 'created_by',
        'currency_id', 'exchange_rate', 'total_foreign',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'vat_percent' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'total_foreign' => 'decimal:4',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierQuotationItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseOrder(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Chờ xử lý',
            'selected' => 'Đã chọn',
            'rejected' => 'Từ chối',
            default => $this->status
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
        
        return 'SQ' . now()->format('y') . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
