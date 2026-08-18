<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class TechnicalTicket extends Model
{
    use HasFactory;

    protected $table = 'technical_tickets';

    protected $fillable = [
        'code',
        'title',
        'description',
        'status',
        'work_type',
        'priority',
        'project_id',
        'opportunity_id',
        'sale_id',
        'customer_id',
        'supplier_id',
        'assigned_to',
        'created_by',
        'sla_deadline',
        'resolved_at',
        'sales_owner_id',
        'team_lead_id',
        'department',
        'project_name',
        'solution',
    ];

    protected $casts = [
        'sla_deadline' => 'datetime',
        'resolved_at' => 'datetime',
        'project_id' => 'integer',
        'opportunity_id' => 'integer',
        'sale_id' => 'integer',
        'customer_id' => 'integer',
        'supplier_id' => 'integer',
        'assigned_to' => 'integer',
        'created_by' => 'integer',
        'sales_owner_id' => 'integer',
        'team_lead_id' => 'integer',
    ];

    // ===================================================================
    // Relationships
    // ===================================================================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class); // Represents Vendor
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_owner_id');
    }

    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function supportLogs(): HasMany
    {
        return $this->hasMany(TechnicalSupportLog::class, 'technical_ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TechnicalTicketAttachment::class, 'technical_ticket_id');
    }

    // ===================================================================
    // Accessors & Mutators
    // ===================================================================

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Open',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'waiting' => 'Waiting (Customer/Partner/Vendor)',
            'completed' => 'Completed',
            'closed' => 'Closed',
            'pending' => 'Tạm ngưng (Pending)',
            'escalate' => 'Cần hỗ trợ (Escalate)',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'blue',
            'assigned' => 'indigo',
            'in_progress' => 'yellow',
            'waiting' => 'purple',
            'completed' => 'green',
            'closed' => 'gray',
            'pending' => 'orange',
            'escalate' => 'red',
            default => 'gray',
        };
    }

    public function getWorkTypeLabelAttribute(): string
    {
        return match ($this->work_type) {
            'survey' => 'Khảo sát / Tư vấn / Thiết kế',
            'BOM' => 'BOM Support',
            'documentation' => 'Technical Documents',
            'POC' => 'POC / Demo',
            'deployment' => 'Deployment',
            'after_sales' => 'After-sales support',
            'training' => 'Training / Update',
            'event' => 'Event / Speaker',
            'other' => 'Other',
            default => $this->work_type,
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Thấp',
            'medium' => 'Trung bình',
            'high' => 'Cao',
            'urgent' => 'Khẩn cấp',
            default => $this->priority,
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->sla_deadline) {
            return false;
        }
        
        if (in_array($this->status, ['completed', 'closed'])) {
            return $this->resolved_at ? $this->resolved_at->gt($this->sla_deadline) : false;
        }

        return Carbon::now()->gt($this->sla_deadline);
    }

    // ===================================================================
    // Static Helpers
    // ===================================================================

    public static function generateCode(): string
    {
        $dateStr = date('Ymd');
        $prefix = 'TECH-' . $dateStr . '-';
        
        $lastTicket = self::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastTicket) {
            $parts = explode('-', $lastTicket->code);
            $lastSeq = (int) end($parts);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . sprintf('%04d', $nextSeq);
    }
}
