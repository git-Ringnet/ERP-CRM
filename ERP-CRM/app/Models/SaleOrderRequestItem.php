<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleOrderRequestItem extends Model
{
    protected $fillable = [
        'sale_order_request_id',
        'vendor',
        'type',
        'part_number',
        'serial_number',
        'exp_date',
        'si_name',
        'eu_name_mst',
        'address',
    ];

    protected $casts = [
        'exp_date' => 'date',
    ];

    public function orderRequest(): BelongsTo
    {
        return $this->belongsTo(SaleOrderRequest::class, 'sale_order_request_id');
    }
}
