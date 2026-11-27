<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MarketingRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'marketing_ticket_id',
        'marketing_event_id',
        'opportunity_id',
        'code',
        'support_team',
        'pic_type',
        'assigned_to',
        'technical_ticket_id',
        'support_content',
        'support_content_other',
        'description',
        'deadline',
        'status',
        'attachment_path',
        'completed_at',
        'departure_date',
        'departure_date_note',
        'personnel_count',
        'amount',
        'amount_in_words',
        'reference_request_code',
        'funding_source',
        'supplier_debt_checked',
        'marketing_supplier_fund_id',
        'funding_allocations',
    ];

    protected $casts = [
        'deadline'              => 'datetime',
        'completed_at'          => 'datetime',
        'departure_date'        => 'date',
        'amount'                => 'decimal:2',
        'supplier_debt_checked' => 'boolean',
        'attachment_path'       => 'array',
        'marketing_supplier_fund_id' => 'integer',
        'funding_allocations'   => 'array',
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
        $prefix = 'REQ-' . $year . '-';
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

    public function ticket()
    {
        return $this->belongsTo(MarketingTicket::class, 'marketing_ticket_id');
    }

    public function event()
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    /** Ticket kỹ thuật được tạo tự động khi yêu cầu gửi team Kỹ thuật. */
    public function technicalTicket()
    {
        return $this->belongsTo(TechnicalTicket::class, 'technical_ticket_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function fund()
    {
        return $this->belongsTo(MarketingSupplierFund::class, 'marketing_supplier_fund_id');
    }

    public function comments()
    {
        return $this->hasMany(MarketingRequestComment::class, 'marketing_request_id')->latest();
    }

    public function getAllocatedItemTransactionsAttribute()
    {
        if ($this->opportunity_id && $this->opportunity) {
            return $this->opportunity->marketingItemTransactions;
        }
        if ($this->marketing_event_id && $this->event) {
            return $this->event->marketingItemTransactions;
        }
        return collect();
    }

    /**
     * Role thành viên của từng team hỗ trợ (dùng chung cho thông báo, quyền xem, quyền phân công).
     */
    public const TEAM_ROLES = [
        'technical'  => ['technical_lead', 'technical_engineer'],
        'sales'      => ['sales_manager', 'sales_staff'],
        'accounting' => ['accountant'],
        'po'         => ['purchase_manager', 'purchase_staff'],
        'purchasing' => ['purchase_manager', 'purchase_staff'],
        'warehouse'  => ['warehouse_manager', 'warehouse_staff'],
        'marketing'  => ['marketing', 'marketing_manager'],
    ];

    /**
     * Role trưởng nhóm của từng team (được xem toàn bộ yêu cầu của team và phân công PIC).
     */
    public const TEAM_LEAD_ROLES = [
        'technical'  => ['technical_lead'],
        'sales'      => ['sales_manager'],
        'accounting' => ['accountant'],
        'po'         => ['purchase_manager'],
        'purchasing' => ['purchase_manager'],
        'warehouse'  => ['warehouse_manager'],
        'marketing'  => ['marketing_manager'],
    ];

    /**
     * Các team mà user thuộc về (theo role, có fallback theo tên phòng ban).
     */
    public static function teamsForUser(User $user): array
    {
        $teams = [];
        foreach (self::TEAM_ROLES as $team => $roles) {
            if ($user->hasAnyRole($roles)) {
                $teams[] = $team;
            }
        }

        // Fallback theo phòng ban chỉ áp dụng cho nhân viên thường (không áp cho admin / BOD dù phòng ban ghi "IT")
        $dept = mb_strtolower(trim((string) $user->department));
        if ($dept !== '' && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            if ($dept === 'it' || str_contains($dept, 'kỹ thuật') || str_contains($dept, 'ky thuat') || str_contains($dept, 'tech')) {
                $teams[] = 'technical';
            } elseif (str_contains($dept, 'sale') || str_contains($dept, 'kinh doanh')) {
                $teams[] = 'sales';
            } elseif (str_contains($dept, 'kế toán') || str_contains($dept, 'finance') || str_contains($dept, 'account')) {
                $teams[] = 'accounting';
            }
        }

        return array_values(array_unique($teams));
    }

    /**
     * Các team mà user là trưởng nhóm (theo role, hoặc chức danh manager/lead trong team đó).
     */
    public static function leadTeamsForUser(User $user): array
    {
        $position = mb_strtolower((string) $user->position);
        // "Account Manager" là chức danh nhân viên kinh doanh, không phải trưởng nhóm
        $isLeadByPosition = (str_contains($position, 'manager') || str_contains($position, 'lead') || str_contains($position, 'trưởng'))
            && !str_contains($position, 'account manager')
            && !str_contains($position, 'assistant');

        $leadTeams = [];
        foreach (self::teamsForUser($user) as $team) {
            if ($isLeadByPosition || $user->hasAnyRole(self::TEAM_LEAD_ROLES[$team] ?? [])) {
                $leadTeams[] = $team;
            }
        }

        return $leadTeams;
    }

    /**
     * Các team mà user là trợ lý (VD: Sales Director's Assistant) — tiếp nhận yêu cầu có PIC = assistant.
     */
    public static function assistantTeamsForUser(User $user): array
    {
        $position = mb_strtolower((string) $user->position);
        if (!str_contains($position, 'assistant') && !str_contains($position, 'trợ lý')) {
            return [];
        }

        return self::teamsForUser($user);
    }

    /**
     * Các team mà user được quyền điều phối (xem toàn bộ, phân công) cho yêu cầu có PIC tương ứng.
     */
    public static function userCoordinatesRequest(User $user, string $team, ?string $picType): bool
    {
        $team = strtolower($team);
        if (in_array($team, self::leadTeamsForUser($user), true)) {
            return true;
        }

        return $picType === 'assistant' && in_array($team, self::assistantTeamsForUser($user), true);
    }

    /**
     * Danh sách người nhận thông báo khi Marketing tạo yêu cầu cho một team.
     * PIC = all → toàn bộ thành viên; lead → trưởng nhóm; assistant → trưởng nhóm + trợ lý.
     */
    public static function notificationRecipients(string $team, ?string $picType, ?int $excludeUserId = null)
    {
        $team = strtolower($team);

        return User::where('status', 'active')
            ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->with('roles')
            ->get()
            ->filter(function (User $u) use ($team, $picType) {
                if (!in_array($team, self::teamsForUser($u), true)) {
                    return false;
                }
                if (in_array($picType, ['lead', 'assistant'], true)) {
                    return self::userCoordinatesRequest($u, $team, $picType);
                }
                return true;
            })
            ->values();
    }

    /**
     * Hạn xử lý thực tế: nếu chỉ chọn ngày (00:00) thì tính đến cuối ngày đó.
     */
    public function getEffectiveDeadlineAttribute(): ?\Carbon\Carbon
    {
        if (!$this->deadline) {
            return null;
        }
        $d = \Carbon\Carbon::parse($this->deadline);
        return $d->format('H:i:s') === '00:00:00' ? $d->endOfDay() : $d;
    }

    public function getSupportTeamLabelAttribute(): string
    {
        return match (strtolower((string) $this->support_team)) {
            'technical'          => 'Kỹ thuật',
            'sales'              => 'Sales',
            'accounting'         => 'Kế toán',
            'po', 'purchasing'   => 'Mua hàng',
            'warehouse'          => 'Kho',
            'marketing'          => 'Marketing',
            'bod'                => 'BOD',
            default              => (string) $this->support_team,
        };
    }

    /**
     * Nhãn người phụ trách khi chưa có ai nhận.
     */
    public function getPendingPicLabelAttribute(): string
    {
        return match ($this->pic_type) {
            'lead'      => 'Chờ trưởng nhóm phân công',
            'assistant' => 'Chờ trợ lý phân công',
            default     => 'Chờ nhận việc (cả team)',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return !in_array($this->status, ['completed', 'rejected'], true)
            && $this->effective_deadline
            && now()->greaterThan($this->effective_deadline);
    }

    /**
     * User có thể tự nhận (pickup) yêu cầu này không.
     * - Yêu cầu chưa có người phụ trách và chưa xử lý xong.
     * - User thuộc team được yêu cầu.
     * - PIC = "Cả team" (all) → mọi thành viên tự nhận; PIC = Lead/Assistant → chỉ trưởng nhóm.
     */
    public function canBePickedUpBy(User $user): bool
    {
        // Yêu cầu đã có ticket kỹ thuật → nhận việc trên ticket kỹ thuật
        if ($this->technical_ticket_id || $this->assigned_to || !in_array($this->status, ['received', 'overdue'], true)) {
            return false;
        }

        $team = strtolower((string) $this->support_team);
        if (!in_array($team, self::teamsForUser($user), true)) {
            return false;
        }

        if (in_array($this->pic_type, ['lead', 'assistant'], true)) {
            return self::userCoordinatesRequest($user, $team, $this->pic_type);
        }

        return true;
    }

    public function scopeForUser(Builder $query, User $user)
    {
        // Super admin, BOD (director), Marketing team, and Finance/Accountant see everything
        if ($user->hasRole('super_admin') || $user->hasRole('director') || $user->hasRole('marketing') || $user->hasRole('accountant') || $user->hasRole('order_management')) {
            return $query;
        }

        $teams = self::teamsForUser($user);
        $leadTeams = self::leadTeamsForUser($user);
        $memberTeams = array_values(array_diff($teams, $leadTeams));
        $assistantTeams = array_values(array_diff(self::assistantTeamsForUser($user), $leadTeams));

        return $query->where(function ($q) use ($user, $leadTeams, $memberTeams, $assistantTeams) {
            // 1. Assigned to them directly
            $q->where('assigned_to', $user->id);

            // 2. Trưởng nhóm thấy toàn bộ yêu cầu của team mình
            if (!empty($leadTeams)) {
                $q->orWhereIn('support_team', $leadTeams);
            }

            // 2b. Trợ lý (VD: Sales Director's Assistant) thấy toàn bộ yêu cầu PIC = assistant của team
            if (!empty($assistantTeams)) {
                $q->orWhere(fn ($a) => $a->where('pic_type', 'assistant')->whereIn('support_team', $assistantTeams));
            }

            // 3. Thành viên thấy yêu cầu chưa gán của team, trừ khi yêu cầu chỉ định Lead/Assistant phân công
            if (!empty($memberTeams)) {
                $q->orWhere(function ($inner) use ($memberTeams) {
                    $inner->whereNull('assigned_to')
                          ->where(fn ($p) => $p->whereNull('pic_type')->orWhereNotIn('pic_type', ['lead', 'assistant']))
                          ->whereIn('support_team', $memberTeams);
                });
            }
        });
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'pending_approval') {
            if ($this->ticket && in_array($this->ticket->type, ['internal_collaboration', 'others'])) {
                return 'Chờ Marketing duyệt';
            }
            return 'Chờ duyệt';
        }

        return match ($this->status) {
            'pending_payment'  => 'Chờ thanh toán',
            'received'         => 'Mới tiếp nhận',
            'in_progress'      => 'Đang thực hiện',
            'completed'        => 'Hoàn thành',
            'overdue'          => 'Quá hạn',
            'rejected'         => 'Từ chối',
            default            => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending_approval' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
            'pending_payment'  => 'bg-blue-100 text-blue-800 border border-blue-200',
            'received'         => 'bg-purple-100 text-purple-800 border border-purple-200',
            'in_progress'      => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
            'completed'        => 'bg-green-100 text-green-800 border border-green-200',
            'overdue'          => 'bg-red-100 text-red-800 font-bold border border-red-300 animate-pulse',
            'rejected'         => 'bg-slate-100 text-slate-800 border border-slate-200',
            default            => 'bg-gray-100 text-gray-800',
        };
    }

    public function getSupportContentLabelAttribute(): string
    {
        return match ($this->support_content) {
            'speaker'           => 'Cung cấp Speaker',
            'technical_support' => 'Hỗ trợ kỹ thuật',
            'customer_list'     => 'Cung cấp danh sách khách hàng',
            'invite_customers'  => 'Mời khách hàng',
            'others'            => $this->support_content_other ?: 'Khác',
            default             => $this->support_content ?: 'Hỗ trợ',
        };
    }
}
