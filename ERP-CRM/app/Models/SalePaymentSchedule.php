<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SalePaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'template_id',
        'template_version',
        'sort_order',
        'milestone_name',
        'percentage',
        'amount',
        'trigger_type',
        'trigger_value',
        'blocking_stage',
        'due_base',
        'due_days',
        'required_docs',
        'status',
        'trigger_date',
        'due_date',
        'paid_amount',
        'proof_file_path',
        'bod_approval_file_path',
        'confirmed_by',
        'confirmed_at',
        'delegated_to_id',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_days' => 'integer',
        'sort_order' => 'integer',
        'template_version' => 'integer',
        'trigger_date' => 'date',
        'due_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function template()
    {
        return $this->belongsTo(PaymentTemplate::class, 'template_id');
    }

    public function evidences()
    {
        return $this->hasMany(PaymentEvidence::class, 'schedule_id');
    }

    public function logs()
    {
        return $this->hasMany(PaymentApprovalLog::class, 'schedule_id');
    }

    /**
     * Calculate and update due date based on trigger date
     */
    public function calculateDueDate(?Carbon $triggerDate = null): void
    {
        $date = $triggerDate ?? $this->trigger_date;
        if ($date) {
            $this->due_date = $date->copy()->addDays($this->due_days);
        }
    }

    /**
     * Check if this milestone blocks a specific stage
     */
    public function isBlocking(string $stage): bool
    {
        if ($this->blocking_stage !== $stage) {
            return false;
        }

        // Paid, waived, or exceptional BOD approved schedules do not block anymore
        return !in_array($this->status, ['paid', 'waived', 'exception_approved']);
    }

    public function delegatedTo()
    {
        return $this->belongsTo(User::class, 'delegated_to_id');
    }
}
