<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilestoneTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'stage_type',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the user who created the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the template milestones for this template.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(TemplateMilestone::class)->orderBy('order');
    }

    /**
     * Get stage type label.
     */
    public function getStageTypeLabelAttribute(): string
    {
        return match($this->stage_type) {
            'new' => 'Khách hàng mới',
            'onboarding' => 'Đang tiếp nhận',
            'active' => 'Chăm sóc tích cực',
            'follow_up' => 'Theo dõi',
            'retention' => 'Duy trì',
            'at_risk' => 'Có nguy cơ',
            'inactive' => 'Không hoạt động',
            default => 'Không xác định',
        };
    }

    /**
     * Scope for specific stage type.
     */
    public function scopeForStage($query, string $stageType)
    {
        return $query->where('stage_type', $stageType);
    }

    /**
     * Scope for default templates.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Apply this template to a customer care stage.
     */
    public function applyTo(CustomerCareStage $careStage): void
    {
        foreach ($this->milestones as $templateMilestone) {
            $dueDate = $careStage->start_date->addDays($templateMilestone->days_from_start);
            
            CareMilestone::create([
                'customer_care_stage_id' => $careStage->id,
                'title' => $templateMilestone->title,
                'description' => $templateMilestone->description,
                'due_date' => $dueDate,
                'order' => $templateMilestone->order,
                'is_completed' => false,
            ]);
        }
    }
}
