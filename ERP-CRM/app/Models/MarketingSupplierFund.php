<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingSupplierFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'name',
        'quarter',
        'year',
        'amount',
        'used_amount',
        'remaining_amount',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'used_amount'      => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(MarketingSupplierTransaction::class, 'marketing_supplier_fund_id');
    }
}
