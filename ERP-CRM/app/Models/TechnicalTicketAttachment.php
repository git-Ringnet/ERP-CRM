<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalTicketAttachment extends Model
{
    use HasFactory;

    protected $table = 'technical_ticket_attachments';

    protected $fillable = [
        'technical_ticket_id',
        'file_path',
        'file_name',
        'file_size',
        'document_type',
        'uploaded_by',
    ];

    protected $casts = [
        'technical_ticket_id' => 'integer',
        'uploaded_by' => 'integer',
        'file_size' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TechnicalTicket::class, 'technical_ticket_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
