<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Quotation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'customer_id',
        'contact_id',
        'customer_name',
        'title',
        'date',
        'valid_until',
        'subtotal',
        'discount',
        'vat',
        'vat_amount',
        'total',
        'payment_terms',
        'delivery_time',
        'note',
        'disclaimer',
        'status',
        'current_approval_level',
        'created_by',
        'converted_to_sale_id',
        'currency_id',
        'exchange_rate',
        'total_foreign',
        'custom_columns',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'current_approval_level' => 'integer',
        'exchange_rate' => 'decimal:6',
        'total_foreign' => 'decimal:4',
        'custom_columns' => 'array',
    ];

    /**
     * Get note as array (backward compatible with legacy plain-text)
     */
    public function getNoteArrayAttribute(): array
    {
        $raw = $this->attributes['note'] ?? null;
        if (empty($raw)) return [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_values(array_filter($decoded, fn($v) => trim($v) !== ''));
        // Legacy plain-text: wrap in array
        return [trim($raw)];
    }

    /**
     * Get disclaimer as array
     */
    public function getDisclaimerArrayAttribute(): array
    {
        $raw = $this->attributes['disclaimer'] ?? null;
        if (empty($raw)) return [];
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_values(array_filter($decoded, fn($v) => trim($v) !== ''));
        return [trim($raw)];
    }

    /**
     * Default disclaimer items for new quotations
     */
    public static function defaultDisclaimer(): array
    {
        return [
            'Hỗ trợ đăng ký và kích hoạt thiết bị/phần mềm trong vòng 30 ngày kể từ ngày bàn giao.',
            'Chúng tôi được quyền từ chối cung cấp hàng nếu phát hiện tên dự án hoặc tên khách hàng không đúng với thông tin đăng ký ban đầu.',
            'Trường hợp thiết bị đã được bàn giao không đúng tên khách hàng đã đăng ký, chúng tôi sẽ không chịu trách nhiệm về bảo hành và dịch vụ của sản phẩm.',
            'Đối với khách hàng thuộc khối chính phủ phải được khai báo ngay từ đầu. Chúng tôi miễn chịu trách nhiệm đối với các trường hợp không khai báo.',
        ];
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedSale()
    {
        return $this->belongsTo(Sale::class, 'converted_to_sale_id');
    }

    public function approvalHistories()
    {
        return collect([]);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('customer_name', 'like', "%{$search}%")
              ->orWhere('title', 'like', "%{$search}%")
              ->orWhereHas('convertedSale', function ($sq) use ($search) {
                  $sq->where('code', 'like', "%{$search}%");
              });
        });
    }

    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        if (empty($status)) return $query;
        return $query->where('status', $status);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Nháp',
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'sent' => 'Đã gửi khách',
            'accepted' => 'KH chốt giá',
            'declined' => 'Khách từ chối',
            'expired' => 'Hết hạn',
            'converted' => 'KH chốt giá - Đã chuyển đơn hàng',
            default => 'Không xác định',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'rejected' => 'bg-red-100 text-red-800',
            'sent' => 'bg-purple-100 text-purple-800',
            'accepted' => 'bg-green-100 text-green-800',
            'declined' => 'bg-red-100 text-red-800',
            'expired' => 'bg-gray-100 text-gray-800',
            'converted' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function canBeApprovedBy(?User $user): bool
    {
        return false;
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function canConvertToSale(): bool
    {
        return !$this->converted_to_sale_id
            && !$this->isExpired();
    }

    public function canBeDeleted(): bool
    {
        return true;
    }
}
