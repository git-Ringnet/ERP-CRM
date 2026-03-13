<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'month',
        'year',
        'standard_working_days',
        'status',
        'created_by',
    ];

    protected $casts = [
        'standard_working_days' => 'decimal:1',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }
}
