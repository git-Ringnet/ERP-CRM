<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'position',
        'phone',
        'email',
        'is_primary',
        'note',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
