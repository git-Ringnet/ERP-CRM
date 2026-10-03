<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;
use Carbon\Carbon;

class TechnicalTicket extends Model
{
    use HasFactory, LogsActivity;

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
        'user_group_id',
        'co_lead_ids',
        'department',
        'project_name',
        'solution',
        'ticket_details',
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
        'user_group_id' => 'integer',
        'co_lead_ids' => 'array',
        'ticket_details' => 'array',
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

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    /**
     * Get all lead user IDs (primary lead + co-leads)
     */
    public function getAllLeadIds(): array
    {
        $leads = [];
        if ($this->team_lead_id) {
            $leads[] = (int)$this->team_lead_id;
        }
        if (!empty($this->co_lead_ids) && is_array($this->co_lead_ids)) {
            foreach ($this->co_lead_ids as $id) {
                $leads[] = (int)$id;
            }
        }
        return array_unique(array_filter($leads));
    }

    public function supportLogs(): HasMany
    {
        return $this->hasMany(TechnicalSupportLog::class, 'technical_ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TechnicalTicketAttachment::class, 'technical_ticket_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TechnicalTicketComment::class, 'technical_ticket_id')->latest();
    }

    public function assignedEngineers()
    {
        return $this->belongsToMany(User::class, 'technical_ticket_engineers', 'technical_ticket_id', 'user_id')
            ->withPivot('is_active', 'handed_over_at', 'handed_over_by', 'handover_note')
            ->withTimestamps();
    }

    public function activeEngineers()
    {
        return $this->belongsToMany(User::class, 'technical_ticket_engineers', 'technical_ticket_id', 'user_id')
            ->wherePivot('is_active', true)
            ->withPivot('is_active', 'handed_over_at', 'handed_over_by', 'handover_note')
            ->withTimestamps();
    }

    public function formerEngineers()
    {
        return $this->belongsToMany(User::class, 'technical_ticket_engineers', 'technical_ticket_id', 'user_id')
            ->wherePivot('is_active', false)
            ->withPivot('is_active', 'handed_over_at', 'handed_over_by', 'handover_note')
            ->withTimestamps();
    }

    /**
     * Check if ticket has at least one active assigned engineer who is NOT a Lead of this ticket.
     * If true, ticket is public/visible to all technical staff.
     */
    public function hasNonLeadAssignedEngineer(): bool
    {
        $leadIds = $this->getAllLeadIds();
        $activeEngIds = $this->activeEngineers->pluck('id')->toArray();
        if (empty($activeEngIds) && $this->assigned_to) {
            $activeEngIds = [(int)$this->assigned_to];
        }
        
        $nonLeadEngineers = array_diff($activeEngIds, $leadIds);
        return !empty($nonLeadEngineers);
    }

    /**
     * Check if a given user can view this ticket.
     */
    public function canUserView(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'director', 'sales_manager'])) {
            return true;
        }

        $userId = (int)$user->id;
        $leadIds = $this->getAllLeadIds();

        // Creator, Sales Owner, Lead, Co-Leads
        if ($this->created_by == $userId || $this->sales_owner_id == $userId || in_array($userId, $leadIds)) {
            return true;
        }

        // Active assignees
        $activeEngIds = $this->activeEngineers->pluck('id')->toArray();
        if (empty($activeEngIds) && $this->assigned_to) {
            $activeEngIds = [(int)$this->assigned_to];
        }
        if (in_array($userId, $activeEngIds)) {
            return true;
        }

        // Former assignees (handed over)
        $formerEngIds = $this->formerEngineers->pluck('id')->toArray();
        if (in_array($userId, $formerEngIds)) {
            return true;
        }

        // If assigned to non-lead engineer, visible to all technical staff
        if ($user->hasAnyRole(['technical_engineer', 'technical_lead'])) {
            if ($this->hasNonLeadAssignedEngineer()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a given user can comment/discuss on this ticket.
     */
    public function canUserComment(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'director', 'sales_manager'])) {
            return true;
        }

        $userId = (int)$user->id;
        $leadIds = $this->getAllLeadIds();

        if ($this->created_by == $userId || $this->sales_owner_id == $userId || in_array($userId, $leadIds)) {
            return true;
        }

        $activeEngIds = $this->activeEngineers->pluck('id')->toArray();
        if (empty($activeEngIds) && $this->assigned_to) {
            $activeEngIds = [(int)$this->assigned_to];
        }
        if (in_array($userId, $activeEngIds)) {
            return true;
        }

        // Handed over assignees can still comment and view
        $formerEngIds = $this->formerEngineers->pluck('id')->toArray();
        if (in_array($userId, $formerEngIds)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can update progress, technical solution, or create support logs.
     */
    public function canUserUpdateProgress(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'director'])) {
            return true;
        }

        $userId = (int)$user->id;
        $leadIds = $this->getAllLeadIds();

        // Leads can update progress
        if (in_array($userId, $leadIds)) {
            return true;
        }

        // Only active assignees can update progress (former assignees CANNOT)
        $activeEngIds = $this->activeEngineers->pluck('id')->toArray();
        if (empty($activeEngIds) && $this->assigned_to) {
            $activeEngIds = [(int)$this->assigned_to];
        }
        if (in_array($userId, $activeEngIds)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can upload attachments.
     */
    public function canUserAttachFile(?User $user = null): bool
    {
        return $this->canUserComment($user);
    }

    /**
     * Check if user can handover the ticket.
     */
    public function canUserHandover(?User $user = null): bool
    {
        return $this->canUserUpdateProgress($user);
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
            default => ucfirst($this->status),
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
            'it_support' => 'IT nội bộ (Thiết bị, phần mềm, phần cứng)',
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
    // Work Type Permissions & Helpers
    // ===================================================================

    public static function getAllWorkTypes(): array
    {
        return [
            'survey' => 'Khảo sát / Tư vấn / Thiết kế',
            'BOM' => 'BOM Support',
            'documentation' => 'Technical Documents',
            'POC' => 'POC / Demo',
            'deployment' => 'Deployment',
            'after_sales' => 'After-sales support',
            'training' => 'Training / Update',
            'event' => 'Event / Speaker',
            'other' => 'Other',
        ];
    }

    public static function getWorkTypePermissions(): array
    {
        $defaults = [
            'survey' => ['scope' => 'leader', 'roles' => []],
            'BOM' => ['scope' => 'all', 'roles' => []],
            'documentation' => ['scope' => 'all', 'roles' => []],
            'POC' => ['scope' => 'leader', 'roles' => []],
            'deployment' => ['scope' => 'leader', 'roles' => []],
            'after_sales' => ['scope' => 'all', 'roles' => []],
            'training' => ['scope' => 'leader', 'roles' => []],
            'event' => ['scope' => 'leader', 'roles' => []],
            'other' => ['scope' => 'leader', 'roles' => []],
        ];

        try {
            $saved = Setting::get('technical_ticket_work_type_permissions');
            if ($saved) {
                $decoded = is_array($saved) ? $saved : json_decode($saved, true);
                if (is_array($decoded)) {
                    $normalized = [];
                    foreach ($decoded as $k => $v) {
                        if (is_string($v)) {
                            $normalized[$k] = ['scope' => $v, 'roles' => []];
                        } elseif (is_array($v)) {
                            $normalized[$k] = [
                                'scope' => $v['scope'] ?? 'leader',
                                'roles' => $v['roles'] ?? [],
                            ];
                        }
                    }
                    return array_merge($defaults, $normalized);
                }
            }
        } catch (\Throwable $e) {
            // fallback to defaults
        }

        return $defaults;
    }

    public static function getSelfPickupWorkTypes(): array
    {
        $perms = self::getWorkTypePermissions();
        $types = [];
        foreach ($perms as $type => $config) {
            $scope = is_array($config) ? ($config['scope'] ?? 'leader') : $config;
            if (in_array($scope, ['all', 'tech_all', 'everyone', 'custom'])) {
                $types[] = $type;
            }
        }
        return $types;
    }

    public static function getLeaderOnlyWorkTypes(): array
    {
        $perms = self::getWorkTypePermissions();
        $types = [];
        foreach ($perms as $type => $config) {
            $scope = is_array($config) ? ($config['scope'] ?? 'leader') : $config;
            if ($scope === 'leader') {
                $types[] = $type;
            }
        }
        return $types;
    }

    public function canUserPickup(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        // Admins, Directors, Sales Managers, and Technical Leads can always pickup/assign
        if ($user->hasAnyRole(['super_admin', 'director', 'sales_manager', 'technical_lead'])) {
            return true;
        }

        $perms = self::getWorkTypePermissions();
        $config = $perms[$this->work_type] ?? ['scope' => 'leader', 'roles' => []];
        $scope = is_array($config) ? ($config['scope'] ?? 'leader') : $config;
        $customRoles = is_array($config) ? ($config['roles'] ?? []) : [];

        if ($scope === 'everyone') {
            return true;
        }

        if ($scope === 'all' || $scope === 'tech_all') {
            return $user->hasAnyRole(['technical_engineer', 'technical_lead', 'super_admin']);
        }

        if ($scope === 'custom' && !empty($customRoles)) {
            $userRoleIds = $user->roles->pluck('id')->toArray();
            $userRoleSlugs = $user->roles->pluck('slug')->toArray();
            return !empty(array_intersect($userRoleIds, (array)$customRoles)) 
                || !empty(array_intersect($userRoleSlugs, (array)$customRoles));
        }

        return false;
    }

    public function isSelfPickupAllowed(): bool
    {
        $perms = self::getWorkTypePermissions();
        $config = $perms[$this->work_type] ?? ['scope' => 'leader'];
        $scope = is_array($config) ? ($config['scope'] ?? 'leader') : $config;
        return in_array($scope, ['all', 'tech_all', 'everyone', 'custom']);
    }

    public function isLeaderOnly(): bool
    {
        $perms = self::getWorkTypePermissions();
        $config = $perms[$this->work_type] ?? ['scope' => 'leader'];
        $scope = is_array($config) ? ($config['scope'] ?? 'leader') : $config;
        return $scope === 'leader';
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

    public static function calculateSlaDeadline(string $priority, $fromTime = null): ?Carbon
    {
        if (!$fromTime) {
            $fromTime = Carbon::now();
        } else {
            $fromTime = Carbon::parse($fromTime);
        }

        $hoursToAdd = 0;
        if ($priority === 'high') {
            $hoursToAdd = 4;
        } elseif ($priority === 'medium') {
            $hoursToAdd = 8;
        } else {
            return null;
        }

        $current = $fromTime->copy();
        
        while ($hoursToAdd > 0) {
            if ($current->isWeekend()) {
                $current->next(Carbon::MONDAY)->setTime(8, 0, 0);
            }
            
            $morningStart = $current->copy()->setTime(8, 0, 0);
            $morningEnd = $current->copy()->setTime(12, 0, 0);
            $afternoonStart = $current->copy()->setTime(13, 30, 0);
            $afternoonEnd = $current->copy()->setTime(17, 30, 0);
            
            if ($current->greaterThanOrEqualTo($afternoonEnd)) {
                $current->addDay()->setTime(8, 0, 0);
                continue;
            }
            
            if ($current->lessThan($morningStart)) {
                $current->setTime(8, 0, 0);
            }
            
            if ($current->greaterThanOrEqualTo($morningStart) && $current->lessThan($morningEnd)) {
                $availableHours = $current->diffInMinutes($morningEnd) / 60;
                if ($hoursToAdd <= $availableHours) {
                    $current->addMinutes($hoursToAdd * 60);
                    $hoursToAdd = 0;
                } else {
                    $hoursToAdd -= $availableHours;
                    $current = $afternoonStart->copy();
                }
            } elseif ($current->greaterThanOrEqualTo($morningEnd) && $current->lessThan($afternoonStart)) {
                $current = $afternoonStart->copy();
            } elseif ($current->greaterThanOrEqualTo($afternoonStart) && $current->lessThan($afternoonEnd)) {
                $availableHours = $current->diffInMinutes($afternoonEnd) / 60;
                if ($hoursToAdd <= $availableHours) {
                    $current->addMinutes($hoursToAdd * 60);
                    $hoursToAdd = 0;
                } else {
                    $hoursToAdd -= $availableHours;
                    $current->addDay()->setTime(8, 0, 0);
                }
            }
        }
        
        return $current;
    }
}
