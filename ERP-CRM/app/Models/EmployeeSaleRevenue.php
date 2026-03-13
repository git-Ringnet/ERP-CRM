<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSaleRevenue extends Model
{
    use HasFactory;

    protected $table = 'employee_sales_revenues';

    protected $fillable = [
        'user_id',
        'month',
        'year',
        'total_revenue',
        'total_profit',
        'quantity_on_target',
        'note',
        'recorded_by',
    ];

    /**
     * Get the employee (user) associated with this revenue record.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the person who recorded this revenue record.
     */
    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
