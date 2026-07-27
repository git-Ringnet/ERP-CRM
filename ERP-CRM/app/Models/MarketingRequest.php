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
        'code',
        'support_team',
        'pic_type',
        'assigned_to',
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
    ];

    protected $casts = [
        'deadline'              => 'datetime',
        'completed_at'          => 'datetime',
        'departure_date'        => 'date',
        'amount'                => 'decimal:2',
        'supplier_debt_checked' => 'boolean',
        'attachment_path'       => 'array',
        'marketing_supplier_fund_id' => 'integer',
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

    public function scopeForUser(Builder $query, User $user)
    {
        // Super admin, BOD (director), Marketing team, and Finance/Accountant see everything
        if ($user->hasRole('super_admin') || $user->hasRole('director') || $user->hasRole('marketing') || $user->hasRole('accountant') || $user->hasRole('order_management')) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            // 1. Assigned to them directly
            $q->where('assigned_to', $user->id);

            // 2. Tech Lead / Technical Manager sees all Tech Requests
            $isTechLead = str_contains(strtolower($user->position), 'manager') || str_contains(strtolower($user->position), 'lead');
            if ($isTechLead && ($user->department === 'Technical' || $user->department === 'Tech' || $user->department === 'IT')) {
                $q->orWhere('support_team', 'technical');
            }

            // 3. Sales Manager sees all Sales Requests
            if ($user->hasRole('sales_manager') && $user->department === 'Sales') {
                $q->orWhere('support_team', 'sales');
            }

            // 4. Tech/Sales members can see unassigned requests for their team ONLY if pic_type is not 'lead'/'assistant'
            $q->orWhere(function ($inner) use ($user) {
                $inner->whereNull('assigned_to')
                      ->whereNotIn('pic_type', ['lead', 'assistant']);
                
                if ($user->department === 'Technical' || $user->department === 'Tech' || $user->department === 'IT') {
                    $inner->where('support_team', 'technical');
                } elseif ($user->department === 'Sales') {
                    $inner->where('support_team', 'sales');
                } elseif ($user->department === 'Finance') {
                    $inner->where('support_team', 'accounting');
                }
            });
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
