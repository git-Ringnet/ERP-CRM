<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'description',
        'type',
        'start_date',
        'due_date',
        'is_completed',
        'completed_at',
        'user_id',
        'created_by',
        'opportunity_id',
        'customer_id',
        'lead_id',
        'customer_care_stage_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function customerCareStage()
    {
        return $this->belongsTo(CustomerCareStage::class);
    }
}
