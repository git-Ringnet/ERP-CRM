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
    ];

    protected $casts = [
        'item_descriptions' => 'array',
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
        return match($this->status) {
            'pending' => 'Chờ KT import hóa đơn',
            'draft_issued' => 'Đã đính kèm HĐ (Chờ Sales xác nhận)',
            'official_issued' => 'Đã xác nhận hoàn tất',
            'rejected' => 'Hóa đơn chưa chính xác',
            default => 'Không xác định',
        };
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-amber-100 text-amber-800',
            'draft_issued' => 'bg-blue-100 text-blue-800',
            'official_issued' => 'bg-emerald-100 text-emerald-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
