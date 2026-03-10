<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentKpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'department',
        'evaluation_period',
        'status',
        'total_score',
        'evaluator_id',
        'creator_id',
        'note',
    ];

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function results()
    {
        return $this->hasMany(DepartmentKpiResult::class);
    }
}
