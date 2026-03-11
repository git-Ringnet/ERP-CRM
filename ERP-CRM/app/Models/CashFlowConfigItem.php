<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashFlowConfigItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'sort_order',
    ];
}
