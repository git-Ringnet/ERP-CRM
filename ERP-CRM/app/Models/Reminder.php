<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'remindable_type',
        'remindable_id',
        'user_id',
        'remind_at',
        'message',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    /**
     * Get the parent remindable model (polymorphic).
     */
    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who will receive the reminder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for unsent reminders.
     */
    public function scopeUnsent($query)
    {
        return $query->where('is_sent', false);
    }

    /**
     * Scope for due reminders.
     */
    public function scopeDue($query)
    {
        return $query->where('remind_at', '<=', now());
    }

    /**
     * Scope for upcoming reminders.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('remind_at', '>', now());
    }

    /**
     * Mark reminder as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    /**
     * Snooze reminder for specified minutes.
     */
    public function snooze(int $minutes = 30): void
    {
        $this->update([
            'remind_at' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Check if reminder is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_sent && $this->remind_at->isPast();
    }
}
