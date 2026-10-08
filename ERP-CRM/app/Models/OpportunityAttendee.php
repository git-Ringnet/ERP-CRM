<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityAttendee extends Model
{
    protected $fillable = [
        'opportunity_id',
        'user_id',
        'status',
        'note',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ phản hồi',
            self::STATUS_ACCEPTED => 'Đồng ý',
            self::STATUS_DECLINED => 'Từ chối',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? 'Chờ phản hồi';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::STATUS_DECLINED => 'bg-rose-100 text-rose-800 border-rose-200',
            default => 'bg-amber-100 text-amber-800 border-amber-200',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'fas fa-check-circle text-emerald-600',
            self::STATUS_DECLINED => 'fas fa-times-circle text-rose-600',
            default => 'fas fa-clock text-amber-600',
        };
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
