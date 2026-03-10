<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentKpiResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_kpi_id',
        'criterion_name',
        'weight',
        'target',
        'actual_value',
        'score',
        'note',
    ];

    public function departmentKpi()
    {
        return $this->belongsTo(DepartmentKpi::class);
    }
}
