<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'stock_quantity',
        'min_stock_alert',
        'unit_cost',
        'total_estimated_cost',
        'funding_source',
        'marketing_supplier_fund_id',
        'image',
        'description',
        'purpose',
        'marketing_event_id',
        'marketing_request_id',
        'status',
        'approval_status',
        'submitted_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'stock_quantity'       => 'integer',
        'min_stock_alert'      => 'integer',
        'unit_cost'            => 'decimal:2',
        'total_estimated_cost' => 'decimal:2',
        'approved_at'          => 'datetime',
    ];

    protected $appends = [
        'category_label',
        'approval_status_label',
        'funding_source_label',
        'is_low_stock',
    ];

    public const CATEGORIES = [
        'gift'        => 'Quà tặng doanh nghiệp',
        'publication' => 'Ấn phẩm / Brochure / Catalogue',
        'equipment'   => 'Vật tư / Standee / Thiết bị sự kiện',
        'clothing'    => 'Đồng phục / Áo thun sự kiện',
        'other'       => 'Vật phẩm khác',
    ];

    public const FUNDING_SOURCES = [
        'fund_supplier'   => 'Quỹ Hãng (Khai báo)',
        'union'           => 'Quỹ Công đoàn',
        'company_support' => 'Đề xuất Công ty hỗ trợ',
        'other'           => 'Khác',
    ];

    public static function generateCode(): string
    {
        $prefix = 'MKT-';
        $maxId = self::max('id') ?? 0;
        return $prefix . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Khác';
    }

    public function getFundingSourceLabelAttribute(): string
    {
        return self::FUNDING_SOURCES[$this->funding_source] ?? ($this->funding_source ?: 'Chưa xác định');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->min_stock_alert;
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return match ($this->approval_status ?? 'approved') {
            'pending'  => 'Chờ BOD duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'BOD từ chối',
            default    => 'Đã duyệt',
        };
    }

    public function getApprovalStatusColorAttribute(): string
    {
        return match ($this->approval_status ?? 'approved') {
            'pending'  => 'bg-amber-100 text-amber-800 border border-amber-300',
            'approved' => 'bg-emerald-100 text-emerald-800 border border-emerald-300',
            'rejected' => 'bg-rose-100 text-rose-800 border border-rose-300',
            default    => 'bg-emerald-100 text-emerald-800',
        };
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function fund()
    {
        return $this->belongsTo(MarketingSupplierFund::class, 'marketing_supplier_fund_id');
    }

    public function event()
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }

    public function ticketRequest()
    {
        return $this->belongsTo(MarketingRequest::class, 'marketing_request_id');
    }

    public function transactions()
    {
        return $this->hasMany(MarketingItemTransaction::class)->orderBy('created_at', 'desc');
    }
}

