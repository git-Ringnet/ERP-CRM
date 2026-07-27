<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingEvent extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title', 'description', 'event_date', 'location',
        'budget', 'actual_cost', 'status', 'current_approval_level',
        'created_by', 'rejection_reason', 'approved_at', 'approved_by',
        'code', 'scope', 'vendor_id', 'vendor_other_note', 'partner_cooperation',
        'partner_info', 'organize_type', 'organize_type_other', 'start_time', 'end_time',
        'target_audience_count', 'target_audience_note', 'budget_external_note', 'funding_source',
        'special_notes', 'attachments',
    ];

    protected $casts = [
        'event_date'  => 'date',
        'approved_at' => 'datetime',
        'budget'      => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'attachments' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $prefix = 'MKT-' . $year . '-';
        $last = self::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($last) {
            $parts = explode('-', $last->code);
            $num = (int) end($parts);
            $next = $num + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // --- ApprovalService compatibility ---
    // ApprovalService reads $document->total to check amount thresholds
    public function getTotalAttribute(): float
    {
        return (float) $this->budget;
    }

    // --- Relations ---
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function vendor()
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function tickets()
    {
        return $this->hasMany(MarketingTicket::class, 'marketing_event_id');
    }

    public function requests()
    {
        return $this->hasMany(MarketingRequest::class, 'marketing_event_id');
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'marketing_event_customers')
            ->withPivot('status', 'notes')
            ->withTimestamps();
    }

    public function approvalHistories()
    {
        return $this->hasMany(ApprovalHistory::class, 'document_id')
            ->where('document_type', 'marketing_budget')
            ->orderBy('level')
            ->orderBy('created_at');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'marketing_event_id');
    }

    // --- Helpers ---
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Nháp',
            'pending'   => 'Chờ duyệt',
            'approved'  => 'Đã duyệt',
            'rejected'  => 'Từ chối',
            'cancelled' => 'Đã hủy',
            default     => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'bg-gray-100 text-gray-700',
            'pending'   => 'bg-yellow-100 text-yellow-700',
            'approved'  => 'bg-green-100 text-green-700',
            'rejected'  => 'bg-red-100 text-red-700',
            'cancelled' => 'bg-gray-200 text-gray-500',
            default     => 'bg-gray-100 text-gray-700',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }
}
