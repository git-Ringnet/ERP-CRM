<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'name',
        'company_name',
        'email',
        'phone',
        'source',
        'status',
        'notes',
        'assigned_to',
        'created_by',
        'contacted_at',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'Mới',
            'contacted' => 'Đã liên hệ',
            'qualified' => 'Đủ điều kiện',
            'lost' => 'Thất bại',
            'converted' => 'Đã chuyển đổi',
            default => 'Không xác định',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'bg-blue-100 text-blue-800',
            'contacted' => 'bg-yellow-100 text-yellow-800',
            'qualified' => 'bg-green-100 text-green-800',
            'lost' => 'bg-red-100 text-red-800',
            'converted' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
