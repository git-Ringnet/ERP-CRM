<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'milestone_template_id',
        'title',
        'description',
        'days_from_start',
        'order',
    ];

    /**
     * Get the milestone template that owns the template milestone.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MilestoneTemplate::class, 'milestone_template_id');
    }
}
