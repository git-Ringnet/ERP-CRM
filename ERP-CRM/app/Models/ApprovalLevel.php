<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'level',
        'name',
        'approver_type',
        'approver_value',
        'min_amount',
        'max_amount',
        'is_required',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function getApproverLabelAttribute(): string
    {
        if ($this->approver_type === 'role') {
            return 'Vai trò: ' . $this->approver_value;
        }
        
        $user = User::find($this->approver_value);
        return $user ? $user->name : 'Người dùng #' . $this->approver_value;
    }

    public function canApprove(?User $user, float $amount = 0): bool
    {
        if (!$user) return false;

        // Check amount conditions
        if ($this->min_amount && $amount < $this->min_amount) return false;
        if ($this->max_amount && $amount > $this->max_amount) return false;

        // Check approver
        if ($this->approver_type === 'user') {
            return $user->id == $this->approver_value;
        }

        // Check role
        return $user->role === $this->approver_value;
    }
}
