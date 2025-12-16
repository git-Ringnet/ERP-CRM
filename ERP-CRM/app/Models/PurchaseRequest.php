<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'title', 'deadline', 'priority', 'status',
        'requirements', 'note', 'created_by', 'sent_at'
    ];

    protected $casts = [
        'deadline' => 'date',
        'sent_at' => 'datetime',
    ];

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'purchase_request_suppliers')
            ->withPivot('sent_at')
            ->withTimestamps();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Nháp',
            'sent' => 'Đã gửi NCC',
            'received' => 'Đã nhận báo giá',
            'converted' => 'Đã chuyển PO',
            'cancelled' => 'Đã hủy',
            default => $this->status
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'normal' => 'Bình thường',
            'high' => 'Cao',
            'urgent' => 'Khẩn cấp',
            default => $this->priority
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
        
        return 'RFQ' . now()->format('y') . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
