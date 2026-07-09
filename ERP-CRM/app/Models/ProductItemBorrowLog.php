<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductItemBorrowLog extends Model
{
    use HasFactory;

    protected $table = 'product_item_borrow_logs';

    protected $fillable = [
        'product_item_id',
        'from_user_id',
        'to_user_id',
        'ticket_id',
        'note',
    ];

    protected $casts = [
        'product_item_id' => 'integer',
        'from_user_id' => 'integer',
        'to_user_id' => 'integer',
        'ticket_id' => 'integer',
    ];

    public function product_item(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function from_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function to_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
