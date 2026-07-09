<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'product_id',
        'quantity',
        'allocated_item_ids',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'allocated_item_ids' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
