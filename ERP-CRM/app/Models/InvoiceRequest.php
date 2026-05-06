<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
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
    ];

    /**
     * Relationship with Sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
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
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Chờ xử lý',
            'draft_issued' => 'Đã xuất nháp',
            'official_issued' => 'Đã xuất chính thức',
            'rejected' => 'Bị từ chối',
            default => 'Không xác định',
        };
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'draft_issued' => 'bg-blue-100 text-blue-800',
            'official_issued' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
