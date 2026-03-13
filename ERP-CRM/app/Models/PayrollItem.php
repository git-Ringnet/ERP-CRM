<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'user_id',
        'basic_salary',
        'actual_working_days',
        'total_allowance',
        'total_deduction',
        'commission_bonus',
        'net_salary',
        'note',
        'details_json',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'actual_working_days' => 'decimal:1',
        'total_allowance' => 'decimal:2',
        'total_deduction' => 'decimal:2',
        'commission_bonus' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'details_json' => 'array',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
