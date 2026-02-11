<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_care_stage_id',
        'user_id',
        'type',
        'subject',
        'description',
        'sentiment',
        'duration_minutes',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    /**
     * Get the customer care stage that owns the communication log.
     */
    public function customerCareStage(): BelongsTo
    {
        return $this->belongsTo(CustomerCareStage::class);
    }

    /**
     * Get the user who created the communication log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'call' => 'Cuộc gọi',
            'email' => 'Email',
            'meeting' => 'Họp',
            'sms' => 'SMS',
            'whatsapp' => 'WhatsApp',
            'zalo' => 'Zalo',
            default => 'Khác',
        };
    }

    /**
     * Get sentiment label.
     */
    public function getSentimentLabelAttribute(): ?string
    {
        if (!$this->sentiment) {
            return null;
        }

        return match($this->sentiment) {
            'positive' => 'Tích cực',
            'neutral' => 'Bình thường',
            'negative' => 'Tiêu cực',
            default => null,
        };
    }

    /**
     * Get sentiment color for UI.
     */
    public function getSentimentColorAttribute(): string
    {
        return match($this->sentiment) {
            'positive' => 'green',
            'neutral' => 'gray',
            'negative' => 'red',
            default => 'gray',
        };
    }

    /**
     * Scope for specific communication type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for specific sentiment.
     */
    public function scopeWithSentiment($query, string $sentiment)
    {
        return $query->where('sentiment', $sentiment);
    }
}
