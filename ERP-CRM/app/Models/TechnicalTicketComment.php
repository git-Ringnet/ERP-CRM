<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicalTicketComment extends Model
{
    protected $fillable = [
        'technical_ticket_id',
        'user_id',
        'comment'
    ];

    /**
     * Get the user who posted the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the technical ticket this comment belongs to.
     */
    public function ticket()
    {
        return $this->belongsTo(TechnicalTicket::class, 'technical_ticket_id');
    }
}
