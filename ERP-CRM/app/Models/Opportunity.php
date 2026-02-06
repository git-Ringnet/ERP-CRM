<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'customer_id',
        'lead_id',
        'amount',
        'currency',
        'stage',
        'probability',
        'expected_close_date',
        'description',
        'assigned_to',
        'created_by',
        'closed_at',
        'next_action',
        'next_action_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'closed_at' => 'date',
        'next_action_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStageLabelAttribute(): string
    {
        return match ($this->stage) {
            'new' => 'Mới',
            'qualification' => 'Đánh giá',
            'proposal' => 'Báo giá',
            'negotiation' => 'Đàm phán',
            'won' => 'Thành công',
            'lost' => 'Thất bại',
            default => 'Không xác định',
        };
    }

    public function getStageColorAttribute(): string
    {
        return match ($this->stage) {
            'new' => 'bg-blue-100 text-blue-800',
            'qualification' => 'bg-indigo-100 text-indigo-800',
            'proposal' => 'bg-yellow-100 text-yellow-800',
            'negotiation' => 'bg-orange-100 text-orange-800',
            'won' => 'bg-green-100 text-green-800',
            'lost' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
