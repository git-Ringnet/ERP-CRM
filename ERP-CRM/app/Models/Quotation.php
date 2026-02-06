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
        'customer_name',
        'title',
        'date',
        'valid_until',
        'subtotal',
        'discount',
        'vat',
        'total',
        'payment_terms',
        'delivery_time',
        'note',
        'status',
        'current_approval_level',
        'created_by',
        'converted_to_sale_id',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'total' => 'decimal:2',
        'current_approval_level' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
        return ApprovalHistory::where('document_type', 'quotation')
            ->where('document_id', $this->id)
            ->orderBy('level')
            ->orderBy('created_at')
            ->get();
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('customer_name', 'like', "%{$search}%")
              ->orWhere('title', 'like', "%{$search}%");
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
            'accepted' => 'Khách chấp nhận',
            'declined' => 'Khách từ chối',
            'expired' => 'Hết hạn',
            'converted' => 'Đã chuyển đơn hàng',
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

    public function getApprovalWorkflow(): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::getForDocumentType('quotation');
    }

    public function getMaxApprovalLevel(): int
    {
        $workflow = $this->getApprovalWorkflow();
        return $workflow ? $workflow->max_level : 0;
    }

    public function getCurrentApprovalLevel(): ?ApprovalLevel
    {
        $workflow = $this->getApprovalWorkflow();
        if (!$workflow) return null;

        return $workflow->levels()
            ->where('level', $this->current_approval_level)
            ->first();
    }

    public function getNextApprovalLevel(): ?ApprovalLevel
    {
        $workflow = $this->getApprovalWorkflow();
        if (!$workflow) return null;

        return $workflow->levels()
            ->where('level', $this->current_approval_level + 1)
            ->first();
    }

    public function canBeApprovedBy(?User $user): bool
    {
        if (!$user || $this->status !== 'pending') return false;

        $nextLevel = $this->getNextApprovalLevel();
        if (!$nextLevel) return false;

        return $nextLevel->canApprove($user, $this->total);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    public function canConvertToSale(): bool
    {
        return in_array($this->status, ['approved', 'sent', 'accepted']) 
            && !$this->converted_to_sale_id
            && !$this->isExpired();
    }
}
