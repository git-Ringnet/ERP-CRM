<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CustomerCareStage extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_id',
        'stage',
        'status',
        'priority',
        'assigned_to',
        'start_date',
        'target_completion_date',
        'actual_completion_date',
        'completion_percentage',
        'notes',
        'next_action',
        'next_action_due_at',
        'next_action_completed',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'completion_percentage' => 'integer',
        'next_action_due_at' => 'datetime',
        'next_action_completed' => 'boolean',
    ];

    /**
     * Relationship with Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with User (Assigned To)
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relationship with User (Created By)
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship with Care Milestones
     */
    public function milestones()
    {
        return $this->hasMany(CareMilestone::class)->orderBy('order');
    }

    /**
     * Relationship with Activities
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Relationship with Communication Logs
     */
    public function communicationLogs()
    {
        return $this->hasMany(CommunicationLog::class)->orderBy('occurred_at', 'desc');
    }

    /**
     * Relationship with Reminders (polymorphic)
     */
    public function reminders()
    {
        return $this->morphMany(Reminder::class, 'remindable');
    }

    /**
     * Scope for filtering by stage
     */
    public function scopeByStage(Builder $query, ?string $stage): Builder
    {
        if (empty($stage)) {
            return $query;
        }
        return $query->where('stage', $stage);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        if (empty($status)) {
            return $query;
        }
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by priority
     */
    public function scopeByPriority(Builder $query, ?string $priority): Builder
    {
        if (empty($priority)) {
            return $query;
        }
        return $query->where('priority', $priority);
    }

    /**
     * Scope for overdue care stages
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed')
            ->whereNotNull('target_completion_date')
            ->whereDate('target_completion_date', '<', now());
    }

    /**
     * Scope for filtering by assigned user
     */
    public function scopeAssignedTo(Builder $query, ?int $userId): Builder
    {
        if (empty($userId)) {
            return $query;
        }
        return $query->where('assigned_to', $userId);
    }

    /**
     * Check if care stage is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'completed' || !$this->target_completion_date) {
            return false;
        }
        return $this->target_completion_date->isPast();
    }

    /**
     * Get progress percentage based on completed milestones
     */
    public function getCalculatedProgressAttribute(): int
    {
        $totalMilestones = $this->milestones()->count();
        if ($totalMilestones === 0) {
            return $this->completion_percentage;
        }
        
        $completedMilestones = $this->milestones()->where('is_completed', true)->count();
        return (int) round(($completedMilestones / $totalMilestones) * 100);
    }

    /**
     * Get stage label in Vietnamese
     */
    public function getStageLabelAttribute(): string
    {
        return match($this->stage) {
            'new' => 'Khách hàng mới',
            'onboarding' => 'Đang tiếp nhận',
            'active' => 'Chăm sóc tích cực',
            'follow_up' => 'Theo dõi',
            'retention' => 'Duy trì',
            'at_risk' => 'Có nguy cơ',
            'inactive' => 'Không hoạt động',
            default => $this->stage,
        };
    }

    /**
     * Get status label in Vietnamese
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'not_started' => 'Chưa bắt đầu',
            'in_progress' => 'Đang thực hiện',
            'completed' => 'Hoàn thành',
            'on_hold' => 'Tạm dừng',
            default => $this->status,
        };
    }

    /**
     * Get priority label in Vietnamese
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Thấp',
            'medium' => 'Trung bình',
            'high' => 'Cao',
            'urgent' => 'Khẩn cấp',
            default => $this->priority,
        };
    }
}
