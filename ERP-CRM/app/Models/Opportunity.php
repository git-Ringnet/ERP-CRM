<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        // Customer
        'customer_type', 'customer_id', 'contact_id',
        'eu_company_name', 'eu_contact_name', 'eu_phone', 'eu_email', 'eu_position',
        // Activity
        'name', 'activity_type', 'activity_type_other',
        'activity_date', 'start_time', 'end_time', 'duration_minutes',
        'description', 'notes', 'materials_required', 'giveaway',
        // Status
        'status', 'cancel_reason', 'completed_at',
        // Technical
        'needs_technical', 'technical_user_id',
        // Report
        'customer_feedback', 'meeting_result', 'pain_points',
        'next_action', 'potential_rating',
        // Tracking
        'assigned_to', 'created_by', 'project_id',
    ];

    protected $casts = [
        'activity_date'    => 'date',
        'completed_at'     => 'datetime',
        'needs_technical'  => 'boolean',
        'duration_minutes' => 'integer',
    ];

    // ===================================================================
    // Constants
    // ===================================================================
    public const CUSTOMER_TYPES = [
        'si' => 'SI (System Integrator)',
        'eu' => 'EU (End User)',
    ];

    public const ACTIVITY_TYPES = [
        'event'           => 'Gặp gỡ tại sự kiện công ty',
        'demo_online'     => 'Setup trình bày giải pháp online',
        'demo_offline'    => 'Setup trình bày giải pháp offline',
        'coffee'          => 'Cafe với khách hàng',
        'vendor_visit'    => 'Phối hợp với Hãng gặp gỡ khách hàng',
        'project_meeting' => 'Meeting liên quan đến dự án',
        'other'           => 'Hoạt động khác (cho nhập tay)',
    ];

    public const STATUSES = [
        'draft'       => 'Nháp (Draft)',
        'planned'     => 'Đã lên lịch (Planned)',
        'confirmed'   => 'Đã xác nhận (Confirmed)',
        'in_progress' => 'Đang thực hiện (In Progress)',
        'completed'   => 'Đã hoàn thành (Completed)',
        'cancelled'   => 'Đã hủy (Cancelled)',
        'postponed'   => 'Đã hoãn (Postponed)',
    ];

    public const POTENTIAL_RATINGS = [
        '25' => '25%',
        '50' => '50%',
        '75' => '75%',
        '90' => '90%',
    ];

    // ===================================================================
    // Relationships
    // ===================================================================
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function technicalUser()
    {
        return $this->belongsTo(User::class, 'technical_user_id');
    }

    public function attachments()
    {
        return $this->hasMany(OpportunityAttachment::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // ===================================================================
    // Accessors
    // ===================================================================
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? 'Không xác định';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'        => 'bg-gray-100 text-gray-800 border border-gray-200',
            'planned'      => 'bg-blue-100 text-blue-800 border border-blue-200',
            'confirmed'    => 'bg-purple-100 text-purple-800 border border-purple-200',
            'in_progress'  => 'bg-amber-100 text-amber-800 border border-amber-200',
            'completed'    => 'bg-green-100 text-green-800 border border-green-200',
            'cancelled'    => 'bg-red-100 text-red-800 border border-red-200',
            'postponed'    => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
            default        => 'bg-gray-100 text-gray-800 border border-gray-200',
        };
    }

    public function getActivityTypeLabelAttribute(): string
    {
        if ($this->activity_type === 'other' && $this->activity_type_other) {
            return $this->activity_type_other;
        }
        return self::ACTIVITY_TYPES[$this->activity_type] ?? $this->activity_type;
    }

    public function getCustomerTypeLabelAttribute(): string
    {
        return self::CUSTOMER_TYPES[$this->customer_type] ?? $this->customer_type;
    }

    public function getCustomerDisplayNameAttribute(): string
    {
        if ($this->customer_type === 'eu') {
            return $this->eu_company_name ?: 'EU không xác định';
        }
        return $this->customer?->name ?: 'Khách hàng không xác định';
    }

    public function getPotentialRatingLabelAttribute(): string
    {
        return self::POTENTIAL_RATINGS[$this->potential_rating] ?? '—';
    }

    public function getPotentialRatingColorAttribute(): string
    {
        return match ($this->potential_rating) {
            '90'  => 'bg-red-100 text-red-800 border border-red-200',
            '75' => 'bg-orange-100 text-orange-800 border border-orange-200',
            '50' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
            '25' => 'bg-blue-100 text-blue-800 border border-blue-200',
            default => 'bg-gray-100 text-gray-800 border border-gray-200',
        };
    }

    // ===================================================================
    // Scopes
    // ===================================================================
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('eu_company_name', 'like', "%{$keyword}%")
              ->orWhere('eu_contact_name', 'like', "%{$keyword}%")
              ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$keyword}%"));
        });
    }

    public function scopeFilterByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
