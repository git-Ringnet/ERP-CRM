<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class TechnicalSupportLog extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'technical_support_logs';

    protected $fillable = [
        'technical_ticket_id',
        'log_date',
        'user_id',
        'serial_number',
        'support_content',
        'status',
        'customer_info',
        'contact_info',
        'notes',
    ];

    protected $casts = [
        'log_date' => 'date',
        'technical_ticket_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TechnicalTicket::class, 'technical_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class); // Represents the Engineer
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Mới tạo (Open)',
            'assigned' => 'Đã phân công',
            'pending' => 'Tạm ngưng (Pending)',
            'escalate' => 'Cần hỗ trợ (Escalate)',
            'completed' => 'Đã hoàn thành',
            'closed' => 'Đã đóng (Closed)',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'blue',
            'assigned' => 'indigo',
            'pending' => 'yellow',
            'escalate' => 'red',
            'completed' => 'green',
            'closed' => 'gray',
            default => 'gray',
        };
    }
}
