<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAssetAssignment extends Model
{
    protected $fillable = [
        'employee_asset_id',
        'user_id',
        'assigned_by',
        'quantity',
        'assigned_date',
        'expected_return_date',
        'returned_date',
        'condition_at_assignment',
        'condition_at_return',
        'reason',
        'return_note',
        'status',
    ];

    protected $casts = [
        'assigned_date'        => 'date',
        'expected_return_date' => 'date',
        'returned_date'        => 'date',
        'quantity'             => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function asset(): BelongsTo
    {
        return $this->belongsTo(EmployeeAsset::class, 'employee_asset_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->whereNotNull('expected_return_date')
                     ->where('expected_return_date', '<', now()->toDateString());
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'   => 'Đang cấp phát',
            'returned' => 'Đã thu hồi',
            'overdue'  => 'Quá hạn',
            default    => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'   => 'primary',
            'returned' => 'success',
            'overdue'  => 'danger',
            default    => 'secondary',
        };
    }

    public function getConditionLabelAttribute(): string
    {
        return $this->getConditionText($this->condition_at_assignment);
    }

    public function getConditionReturnLabelAttribute(): string
    {
        return $this->getConditionText($this->condition_at_return);
    }

    private function getConditionText(?string $condition): string
    {
        return match ($condition) {
            'new'  => 'Mới',
            'good' => 'Tốt',
            'fair' => 'Khá',
            'poor' => 'Kém',
            default => '—',
        };
    }
}
