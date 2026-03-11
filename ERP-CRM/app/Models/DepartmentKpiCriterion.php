<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentKpiCriterion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department',
        'description',
        'weight',
        'target',
        'creator_id',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
