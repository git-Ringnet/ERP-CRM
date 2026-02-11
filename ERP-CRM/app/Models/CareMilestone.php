<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CareMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_care_stage_id',
        'title',
        'description',
        'due_date',
        'is_completed',
        'completed_at',
        'completed_by',
        'order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'order' => 'integer',
    ];

    /**
     * Relationship with CustomerCareStage
     */
    public function customerCareStage()
    {
        return $this->belongsTo(CustomerCareStage::class);
    }

    /**
     * Relationship with User (Completed By)
     */
    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Scope for pending milestones
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope for completed milestones
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope for overdue milestones
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('is_completed', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now());
    }

    /**
     * Mark milestone as completed
     */
    public function markAsCompleted(?int $userId = null): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Mark milestone as pending
     */
    public function markAsPending(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ]);
    }

    /**
     * Check if milestone is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_completed || !$this->due_date) {
            return false;
        }
        return $this->due_date->isPast();
    }
}
