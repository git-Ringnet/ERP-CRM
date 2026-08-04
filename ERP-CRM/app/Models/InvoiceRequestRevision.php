<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceRequestRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_request_id',
        'user_id',
        'version',
        'action',
        'draft_path',
        'official_path',
        'delivery_note_path',
        'note',
    ];

    public function invoiceRequest()
    {
        return $this->belongsTo(InvoiceRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedActionAttribute(): string
    {
        return match($this->action) {
            'created' => 'Sales khởi tạo yêu cầu xuất HĐ',
            'draft_uploaded' => 'Kế toán đính kèm file hóa đơn',
            'reimported' => 'Kế toán import lại file hóa đơn mới',
            'draft_rejected' => 'Sales báo file hóa đơn chưa chính xác',
            'sales_confirmed' => 'Sales đã xác nhận hóa đơn chính xác',
            'official_issued' => 'Hoàn tất xuất hóa đơn',
            default => 'Cập nhật',
        };
    }
}
