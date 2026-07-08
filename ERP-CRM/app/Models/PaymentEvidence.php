<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEvidence extends Model
{
    use HasFactory;

    protected $table = 'payment_evidences';

    protected $fillable = [
        'schedule_id',
        'doc_type',
        'reference_number',
        'amount',
        'file_path',
        'uploaded_by',
        'status',
        'notes',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(SalePaymentSchedule::class, 'schedule_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
