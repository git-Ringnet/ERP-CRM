<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'type',
        'source',
        'target_user_id',
        'status',
        'approver_id',
        'note',
        'reject_reason',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'target_user_id' => 'integer',
        'approver_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function target_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TicketItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Bị từ chối',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'preload' => 'Đặt hàng preload',
            'borrow' => 'Mượn hàng',
            default => $this->type,
        };
    }

    public static function generateCode(): string
    {
        $dateStr = date('Ymd');
        $prefix = 'TK-' . $dateStr . '-';
        
        $lastTicket = self::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastTicket) {
            $parts = explode('-', $lastTicket->code);
            $lastSeq = (int) end($parts);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . sprintf('%04d', $nextSeq);
    }
}
