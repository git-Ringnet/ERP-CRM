<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'amount_type',
        'default_amount',
        'is_taxable',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_taxable' => 'boolean',
    ];

    public function employeeComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }
}
