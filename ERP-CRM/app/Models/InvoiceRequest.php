<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'export_id',
        'requester_id',
        'secondary_user_id',
        'margin_beneficiary_id',
        'primary_margin_percent',
        'secondary_margin_percent',
        'admin_id',
        'finance_id',
        'status',
        'tax_name',
        'tax_address',
        'tax_code',
        'billing_email',
        'draft_path',
        'official_path',
        'delivery_note_path',
        'note',
        'rejection_reason',
        'seller_name',
        'seller_company',
        'invoice_content_note',
        'customer_email',
        'delivery_address',
        'delivery_contact',
        'delivery_phone',
        'payment_terms_note',
        'item_descriptions',
        'requested_items',
        'needs_draft',
        'is_invoiced',
        'invoice_number',
        'invoiced_at',
    ];

    protected $casts = [
        'item_descriptions' => 'array',
        'requested_items' => 'array',
        'needs_draft' => 'boolean',
        'is_invoiced' => 'boolean',
        'invoiced_at' => 'datetime',
        'primary_margin_percent' => 'decimal:2',
        'secondary_margin_percent' => 'decimal:2',
    ];

    /**
     * Relationship with Sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relationship with Export
     */
    public function export()
    {
        return $this->belongsTo(Export::class);
    }

    /**
     * Relationship with Requester (Sales User)
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function secondaryUser()
    {
        return $this->belongsTo(User::class, 'secondary_user_id');
    }

    public function marginBeneficiary()
    {
        return $this->belongsTo(User::class, 'margin_beneficiary_id');
    }

    /**
     * Relationship with Admin (Sales Admin)
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Relationship with Finance (Finance Admin)
     */
    public function finance()
    {
        return $this->belongsTo(User::class, 'finance_id');
    }

    /**
     * Relationship with Revisions (Lịch sử các phiên bản HĐ nháp/chính thức)
     */
    public function revisions()
    {
        return $this->hasMany(InvoiceRequestRevision::class)->orderBy('version', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->needs_draft) {
            return match($this->status) {
                'pending' => 'Chờ KT import HĐ nháp',
                'draft_issued' => 'Đã có HĐ nháp (Chờ Sales duyệt)',
                'sales_confirmed' => 'Đã duyệt HĐ nháp',
                'official_issued' => 'Đã hoàn tất xuất HĐ',
                'rejected' => 'HĐ nháp chưa chính xác',
                default => 'Chờ xử lý',
            };
        }

        return match($this->status) {
            'pending' => 'Xuất trực tiếp (Chờ xuất hàng)',
            'draft_issued' => 'Đã đính kèm HĐ',
            'sales_confirmed', 'official_issued' => 'Đã hoàn tất xuất HĐ',
            'rejected' => 'Hóa đơn bị từ chối',
            default => 'Chờ xử lý',
        };
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => $this->needs_draft ? 'bg-amber-100 text-amber-800' : 'bg-cyan-100 text-cyan-800',
            'draft_issued' => 'bg-blue-100 text-blue-800',
            'sales_confirmed' => 'bg-emerald-100 text-emerald-800',
            'official_issued' => 'bg-emerald-100 text-emerald-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get items list for this invoice request (supports partial invoicing)
     */
    public function getEffectiveItemsAttribute(): \Illuminate\Support\Collection
    {
        if (!empty($this->requested_items) && is_array($this->requested_items)) {
            return collect($this->requested_items);
        }

        if ($this->export && $this->export->items->isNotEmpty()) {
            return $this->export->items;
        }

        return $this->sale ? $this->sale->items : collect();
    }
}

