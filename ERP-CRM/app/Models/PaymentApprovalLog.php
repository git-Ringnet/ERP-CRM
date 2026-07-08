<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentApprovalLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'sale_id',
        'action',
        'old_value',
        'new_value',
        'reason',
        'attachment_path',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(SalePaymentSchedule::class, 'schedule_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
