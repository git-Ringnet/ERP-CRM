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
        'picked_up_at',
    ];

    protected $casts = [
        'sla_deadline' => 'datetime',
        'resolved_at' => 'datetime',
        'picked_up_at' => 'datetime',
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

    /** Cấu hình SLA mặc định (có thể thay đổi tại Cài đặt hệ thống -> SLA Ticket kỹ thuật). */
    public const SLA_SETTING_KEY = 'technical_sla_config';
    public const SLA_DEFAULTS = [
        'periods' => [['08:00', '12:00'], ['13:30', '17:30']],
        'working_days' => [1, 2, 3, 4, 5], // ISO: 1 = thứ Hai ... 7 = Chủ nhật
        'priority_hours' => ['high' => 4, 'medium' => 8],
        'holidays' => [], // Y-m-d
    ];

    private static ?array $slaConfigCache = null;

    protected static function booted(): void
    {
        // SLA dừng tại thời điểm ticket được pickup/phân công lần đầu
        static::saving(function (TechnicalTicket $ticket) {
            if (!$ticket->picked_up_at && !empty($ticket->assigned_to)) {
                $ticket->picked_up_at = Carbon::now();
            }
        });
    }

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

        // Ticket loại tự nhận chưa phân công: mọi kỹ thuật có quyền pickup đều thấy
        if ($this->isOpenForPickup() && $this->canUserPickup($user)) {
            return true;
        }

        return false;
    }

    /** Chưa có kỹ sư nào nhận/phân công. */
    public function isUnassigned(): bool
    {
        return empty($this->assigned_to) && $this->activeEngineers->isEmpty();
    }

    /** Ticket loại tự nhận, chưa phân công và còn mở. */
    public function isOpenForPickup(): bool
    {
        return $this->isSelfPickupAllowed()
            && $this->isUnassigned()
            && !in_array($this->status, ['completed', 'closed']);
    }

    /** Kỹ sư đang được phân công (active) trên ticket. */
    public function isActiveAssignee(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }
        $activeEngIds = $this->activeEngineers->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        if (empty($activeEngIds) && $this->assigned_to) {
            $activeEngIds = [(int) $this->assigned_to];
        }

        return in_array((int) $user->id, $activeEngIds, true);
    }

    /**
     * Quyền hoàn thành ticket: theo ma trận quyền, hoặc là kỹ sư được Leader phân công / Lead của ticket
     * (áp dụng cho mọi loại ticket).
     */
    public function canUserComplete(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        return $user->can('complete_technical_tickets')
            || $this->isActiveAssignee($user)
            || in_array((int) $user->id, $this->getAllLeadIds());
    }

    /**
     * Quyền sửa nội dung ticket: chỉ Lead kỹ thuật / Lead của ticket / Quản trị.
     * Sales (người tạo) không được sửa ticket đã gửi – thay đổi thì tạo ticket mới và yêu cầu đóng ticket cũ.
     */
    public function canUserEditContent(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user || !$user->can('edit_technical_tickets')) {
            return false;
        }
        if (in_array($this->status, ['in_progress', 'waiting', 'completed', 'closed'])) {
            return false;
        }
        if ($user->hasAnyRole(['super_admin', 'director', 'admin', 'technical_lead'])) {
            return true;
        }

        return in_array((int) $user->id, $this->getAllLeadIds());
    }

    /** Người tạo / Sales phụ trách được gửi yêu cầu đóng ticket. */
    public function canUserRequestClose(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user || in_array($this->status, ['completed', 'closed'])) {
            return false;
        }

        return (int) $this->created_by === (int) $user->id || (int) $this->sales_owner_id === (int) $user->id;
    }

    /**
     * Danh sách nhân sự kỹ thuật đang hoạt động: role kỹ thuật hoặc thành viên nhóm Kỹ thuật.
     */
    public static function technicalStaffQuery()
    {
        return User::where('status', 'active')
            ->where(function ($q) {
                $q->whereHas('roles', fn ($rq) => $rq->whereIn('slug', ['technical_lead', 'technical_engineer']))
                    ->orWhereHas('userGroups', function ($gq) {
                        $gq->where('status', 'active')->where(function ($sq) {
                            $sq->where('department', 'like', '%kỹ thuật%')
                                ->orWhere('department', 'like', '%technical%')
                                ->orWhere('name', 'like', '%kỹ thuật%')
                                ->orWhere('name', 'like', '%technical%');
                        });
                    });
            });
    }

    public static function isTechnicalStaff(?User $user): bool
    {
        return $user && self::technicalStaffQuery()->where('users.id', $user->id)->exists();
    }

    /** Leader kỹ thuật / Quản trị: xem toàn bộ dữ liệu tra cứu S/N - license. */
    public static function isTechnicalLeader(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->hasAnyRole(['super_admin', 'director', 'admin', 'technical_lead'])) {
            return true;
        }

        return $user->leadingGroups()->where(function ($q) {
            $q->where('department', 'like', '%kỹ thuật%')
                ->orWhere('department', 'like', '%technical%')
                ->orWhere('name', 'like', '%kỹ thuật%')
                ->orWhere('name', 'like', '%technical%');
        })->exists();
    }

    /** TECH 13: quyền mở màn hình tra cứu S/N - license đã bán. */
    public static function canUserLookupSerials(?User $user): bool
    {
        return $user && $user->can('view_technical_tickets')
            && (self::isTechnicalLeader($user) || self::isTechnicalStaff($user));
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

    /**
     * SLA = thời gian pickup/phân công: trễ khi pickup sau hạn SLA,
     * hoặc chưa pickup mà đã quá hạn.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->sla_deadline) {
            return false;
        }

        if ($this->picked_up_at) {
            return $this->picked_up_at->gt($this->sla_deadline);
        }

        if (in_array($this->status, ['completed', 'closed'])) {
            return $this->resolved_at ? $this->resolved_at->gt($this->sla_deadline) : false;
        }

        return Carbon::now()->gt($this->sla_deadline);
    }

    /** Đang chờ pickup (SLA còn đang chạy). */
    public function getIsSlaRunningAttribute(): bool
    {
        return !$this->picked_up_at && !in_array($this->status, ['completed', 'closed']);
    }

    /** Số phút làm việc từ lúc tạo đến lúc pickup (hoặc đến hiện tại nếu chưa pickup). */
    public function getSlaResponseMinutesAttribute(): int
    {
        if (!$this->created_at) {
            return 0;
        }
        $end = $this->picked_up_at ?? ($this->is_sla_running ? Carbon::now() : ($this->resolved_at ?? Carbon::now()));

        return self::workingMinutesBetween($this->created_at, $end);
    }

    public function getSlaResponseLabelAttribute(): string
    {
        $minutes = $this->sla_response_minutes;
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return ($h > 0 ? "{$h}h " : '') . "{$m}p";
    }

    /** Trễ SLA (pickup sau hạn hoặc chưa pickup mà đã quá hạn). */
    public function scopeSlaOverdue($query, ?Carbon $now = null)
    {
        $now = $now ?? Carbon::now();

        return $query->whereNotNull('sla_deadline')->where(function ($q) use ($now) {
            $q->where(function ($sq) {
                $sq->whereNotNull('picked_up_at')->whereColumn('picked_up_at', '>', 'sla_deadline');
            })->orWhere(function ($sq) use ($now) {
                $sq->whereNull('picked_up_at')->where(function ($tq) use ($now) {
                    $tq->where(function ($a) use ($now) {
                        $a->whereNotIn('status', ['completed', 'closed'])->where('sla_deadline', '<', $now);
                    })->orWhere(function ($a) {
                        $a->whereIn('status', ['completed', 'closed'])->whereColumn('resolved_at', '>', 'sla_deadline');
                    });
                });
            });
        });
    }

    /** Kịp SLA (pickup trước hạn hoặc chưa pickup và chưa tới hạn). */
    public function scopeSlaOntime($query, ?Carbon $now = null)
    {
        $now = $now ?? Carbon::now();

        return $query->where(function ($q) use ($now) {
            $q->whereNull('sla_deadline')
                ->orWhere(function ($sq) {
                    $sq->whereNotNull('picked_up_at')->whereColumn('picked_up_at', '<=', 'sla_deadline');
                })
                ->orWhere(function ($sq) use ($now) {
                    $sq->whereNull('picked_up_at')->where(function ($tq) use ($now) {
                        $tq->where(function ($a) use ($now) {
                            $a->whereNotIn('status', ['completed', 'closed'])->where('sla_deadline', '>=', $now);
                        })->orWhere(function ($a) {
                            $a->whereIn('status', ['completed', 'closed'])
                                ->where(fn ($b) => $b->whereNull('resolved_at')->orWhereColumn('resolved_at', '<=', 'sla_deadline'));
                        });
                    });
                });
        });
    }

    /** Cấu hình SLA hiện hành (giờ làm việc, ngày làm việc, số giờ theo độ ưu tiên, ngày nghỉ). */
    public static function slaConfig(): array
    {
        if (self::$slaConfigCache !== null) {
            return self::$slaConfigCache;
        }

        $config = self::SLA_DEFAULTS;
        try {
            $saved = Setting::get(self::SLA_SETTING_KEY);
            $decoded = is_array($saved) ? $saved : json_decode((string) $saved, true);
            if (is_array($decoded)) {
                $periods = collect($decoded['periods'] ?? [])
                    ->filter(fn ($p) => is_array($p) && count($p) === 2
                        && preg_match('/^\d{2}:\d{2}$/', (string) $p[0]) && preg_match('/^\d{2}:\d{2}$/', (string) $p[1])
                        && $p[0] < $p[1])
                    ->sortBy(fn ($p) => $p[0])->values()->all();
                if (!empty($periods)) {
                    $config['periods'] = $periods;
                }
                $days = array_values(array_filter(array_map('intval', (array) ($decoded['working_days'] ?? [])), fn ($d) => $d >= 1 && $d <= 7));
                if (!empty($days)) {
                    $config['working_days'] = $days;
                }
                foreach (['high', 'medium'] as $priority) {
                    $hours = (float) ($decoded['priority_hours'][$priority] ?? 0);
                    if ($hours > 0) {
                        $config['priority_hours'][$priority] = $hours;
                    }
                }
                $config['holidays'] = array_values(array_filter((array) ($decoded['holidays'] ?? []),
                    fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)));
            }
        } catch (\Throwable $e) {
            // dùng mặc định
        }

        return self::$slaConfigCache = $config;
    }

    public static function flushSlaConfig(): void
    {
        self::$slaConfigCache = null;
    }

    /** Mô tả ngắn khung giờ SLA, VD: "08:00-12:00, 13:30-17:30 (T2-T6)". */
    public static function slaWorkingHoursLabel(): string
    {
        $config = self::slaConfig();
        $names = [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'];
        $days = collect($config['working_days'])->sort()->map(fn ($d) => $names[$d])->implode(', ');

        return collect($config['periods'])->map(fn ($p) => "{$p[0]}-{$p[1]}")->implode(', ') . " ({$days})";
    }

    private static function isSlaWorkingDay(Carbon $day): bool
    {
        $config = self::slaConfig();

        return in_array($day->isoWeekday(), $config['working_days'], true)
            && !in_array($day->toDateString(), $config['holidays'], true);
    }

    /**
     * Số phút làm việc giữa 2 thời điểm theo cấu hình SLA (khung giờ, ngày làm việc, ngày nghỉ).
     * Ngoài giờ làm việc không cộng thời gian.
     */
    public static function workingMinutesBetween($from, $to): int
    {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);
        if ($to->lte($from)) {
            return 0;
        }

        $total = 0;
        $day = $from->copy()->startOfDay();
        while ($day->lte($to)) {
            if (self::isSlaWorkingDay($day)) {
                foreach (self::slaConfig()['periods'] as [$start, $end]) {
                    $pStart = $day->copy()->setTimeFromTimeString($start);
                    $pEnd = $day->copy()->setTimeFromTimeString($end);
                    $s = $from->gt($pStart) ? $from : $pStart;
                    $e = $to->lt($pEnd) ? $to : $pEnd;
                    if ($e->gt($s)) {
                        $total += $s->diffInMinutes($e);
                    }
                }
            }
            $day->addDay();
        }

        return $total;
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
            'it_support' => 'IT nội bộ (Thiết bị, phần mềm, phần cứng)',
            'other' => 'Other',
        ];
    }

    /** Loại ticket hiển thị cho toàn bộ kỹ thuật để tự pickup (TECH 3-7). */
    public const SELF_PICKUP_DEFAULT_TYPES = ['BOM', 'documentation', 'after_sales', 'it_support', 'other'];

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
            'it_support' => ['scope' => 'all', 'roles' => []],
            'other' => ['scope' => 'all', 'roles' => []],
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

        // Bắt buộc phải có quyền pickup_technical_tickets từ ma trận phân quyền
        if (!$user->can('pickup_technical_tickets')) {
            return false;
        }

        // Admins, Directors, and Technical Leads can always pickup/assign
        if ($user->hasAnyRole(['super_admin', 'director', 'technical_lead'])) {
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
            return $user->hasAnyRole(['technical_engineer', 'technical_lead', 'super_admin'])
                || self::isTechnicalStaff($user);
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

    /**
     * Hạn SLA = thời điểm bắt đầu + số giờ làm việc theo độ ưu tiên (theo cấu hình SLA).
     */
    public static function calculateSlaDeadline(string $priority, $fromTime = null): ?Carbon
    {
        $config = self::slaConfig();
        $hours = $config['priority_hours'][$priority] ?? null;
        if (!$hours) {
            return null;
        }

        $current = $fromTime ? Carbon::parse($fromTime) : Carbon::now();
        $minutesLeft = (int) round($hours * 60);
        $day = $current->copy()->startOfDay();

        // Tối đa ~3 năm để tránh lặp vô hạn nếu cấu hình bất thường
        for ($i = 0; $i < 1100; $i++) {
            if (self::isSlaWorkingDay($day)) {
                foreach ($config['periods'] as [$start, $end]) {
                    $pStart = $day->copy()->setTimeFromTimeString($start);
                    $pEnd = $day->copy()->setTimeFromTimeString($end);
                    $s = $current->gt($pStart) ? $current->copy() : $pStart;
                    if ($s->gte($pEnd)) {
                        continue;
                    }
                    $available = $s->diffInMinutes($pEnd);
                    if ($minutesLeft <= $available) {
                        return $s->addMinutes($minutesLeft);
                    }
                    $minutesLeft -= $available;
                }
            }
            $day->addDay();
        }

        return null;
    }
}
