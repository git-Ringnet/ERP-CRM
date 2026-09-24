<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Project extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'customer_id',
        'customer_name',
        'address',
        'description',
        'budget',
        'start_date',
        'end_date',
        'status',
        'manager_id',
        'note',
        'marketing_event_id',
        // Distributor
        'vendor_id',
        'distributor_am',
        'assigned_team',
        // End-User
        'eu_name_vi',
        'eu_name_en',
        'eu_name_abbr',
        'eu_tax_code',
        'eu_province',
        'eu_industry',
        // Collaboration
        'collaborate_type',
        'collaborate_customer_id',
        'collaborate_company',
        'collaborate_tax_code',
        'collaborate_pic_name',
        'collaborate_pic_title',
        'collaborate_pic_phone',
        'collaborate_pic_email',
        // Project enhancements
        'estimated_close_months',
        'bom_file',
        'bom_data',
        'net_to_tech_horizon',
        'stage',
        'deal_type',
        'special_request_type',
        'special_request_note',
        'sn_numbers',
        // SLA Intake
        'initial_sla_due_at',
        'initial_processed_at',
        'initial_processed_by',
        'intake_status',
        'intake_note',
        'duplicate_sales_info',
        'registration_status',
        // Vendor Response SLA
        'vendor_submitted_at',
        'vendor_due_at',
        'vendor_reminder_count',
        'last_vendor_reminded_at',
        'vendor_deal_id',
        'vendor_quote_file',
        'vendor_quote_note',
        'vendor_quote_valid_until',
        // Sales Update
        'forecast_stage',
        'support_request_type',
        'support_request_note',
        'last_sales_updated_at',
        'last_sales_reminded_at',
        'sales_reminder_count',
        'missed_update_count',
        // Close Deal
        'close_reason',
        'close_note',
        'po_code',
        'order_value',
        'order_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'net_to_tech_horizon' => 'decimal:2',
        'order_value' => 'decimal:2',
        'bom_file' => 'array',
        'vendor_quote_file' => 'array',
        'initial_sla_due_at' => 'datetime',
        'initial_processed_at' => 'datetime',
        'vendor_submitted_at' => 'datetime',
        'vendor_due_at' => 'datetime',
        'last_vendor_reminded_at' => 'datetime',
        'last_sales_updated_at' => 'datetime',
        'last_sales_reminded_at' => 'datetime',
        'vendor_quote_valid_until' => 'date',
        'order_date' => 'date',
    ];

    // --- Relationships ---

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function collaborateCustomer()
    {
        return $this->belongsTo(Customer::class, 'collaborate_customer_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function initialProcessedBy()
    {
        return $this->belongsTo(User::class, 'initial_processed_by');
    }

    public function marketingEvent()
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function exports()
    {
        return $this->hasMany(Export::class, 'project_id');
    }

    public function vendorQuoteVersions()
    {
        return $this->hasMany(ProjectVendorQuote::class)->orderBy('version_number', 'desc');
    }

    public function notes()
    {
        return $this->hasMany(ProjectRegistrationNote::class)->orderBy('created_at', 'asc');
    }

    public function statusUpdates()
    {
        return $this->hasMany(ProjectStatusUpdate::class)->orderBy('created_at', 'desc');
    }

    public function technicalTickets()
    {
        return $this->hasMany(TechnicalTicket::class, 'project_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get list of all chronological updates made by Sales and PM on this project.
     * Format: [H:i j.n.Y] Role: Content
     * 
     * @return array
     */
    public function getTimelineUpdates(): array
    {
        $entries = [];

        // 1. Sales monthly / periodic status updates
        $statusUpdates = $this->relationLoaded('statusUpdates') ? $this->statusUpdates : $this->statusUpdates()->get();
        foreach ($statusUpdates as $su) {
            $stageText = match ($su->forecast_stage) {
                'budget_quote' => 'Đã báo giá dự toán',
                'bidding' => 'Đang đấu thầu',
                'waiting_result' => 'Chờ kết quả thầu',
                'close_deal' => 'Chốt hợp đồng',
                default => $su->forecast_stage ?: 'Cập nhật tiến độ',
            };
            
            $details = [];
            if (!empty($stageText)) {
                $details[] = $stageText;
            }
            if (!empty($su->support_request_note)) {
                $details[] = $su->support_request_note;
            } elseif (!empty($su->support_request_type)) {
                $details[] = $su->support_request_type;
            }

            $content = implode(' - ', $details);
            $time = $su->created_at ? $su->created_at->format('H:i j.n.Y') : '';
            $entries[] = [
                'time' => $time,
                'created_at' => $su->created_at,
                'role' => 'Sales',
                'formatted' => "[{$time}] Sales: {$content}",
            ];
        }

        // 2. PM and Sales conversation notes
        $notes = $this->relationLoaded('notes') ? $this->notes : $this->notes()->get();
        foreach ($notes as $note) {
            $role = ($note->user_role === 'pm' || ($note->user && in_array($note->user->department, ['PM', 'PO', 'PM Team', 'PO Team']))) ? 'PM' : 'Sales';
            $time = $note->created_at ? $note->created_at->format('H:i j.n.Y') : '';
            $content = trim((string)$note->content);
            if (!empty($content)) {
                $entries[] = [
                    'time' => $time,
                    'created_at' => $note->created_at,
                    'role' => $role,
                    'formatted' => "[{$time}] {$role}: {$content}",
                ];
            }
        }

        // 3. PM vendor quote submissions
        $vendorQuotes = $this->relationLoaded('vendorQuoteVersions') ? $this->vendorQuoteVersions : $this->vendorQuoteVersions()->get();
        foreach ($vendorQuotes as $vq) {
            $time = $vq->created_at ? $vq->created_at->format('H:i j.n.Y') : '';
            $vqContent = "Đã nộp báo giá Hãng v" . ($vq->version ?? $vq->version_number ?? '1') . ($vq->note ? ": {$vq->note}" : '');
            $entries[] = [
                'time' => $time,
                'created_at' => $vq->created_at,
                'role' => 'PM',
                'formatted' => "[{$time}] PM: {$vqContent}",
            ];
        }

        // 4. Initial note from registration
        if (!empty($this->note)) {
            $time = $this->created_at ? $this->created_at->format('H:i j.n.Y') : '';
            $entries[] = [
                'time' => $time,
                'created_at' => $this->created_at,
                'role' => 'Sales',
                'formatted' => "[{$time}] Sales: " . trim((string)$this->note),
            ];
        }

        // Sort by created_at desc (newest first)
        usort($entries, function ($a, $b) {
            $tA = $a['created_at'] ? $a['created_at']->timestamp : 0;
            $tB = $b['created_at'] ? $b['created_at']->timestamp : 0;
            return $tB <=> $tA;
        });

        return $entries;
    }

    /**
     * Get formatted updates text for export / table display
     * 
     * @param bool $allUpdates If true, returns all updates separated by newline; if false, returns latest update.
     * @return string
     */
    public function getFormattedUpdatesSummary(bool $allUpdates = true): string
    {
        $updates = $this->getTimelineUpdates();
        if (empty($updates)) {
            $time = $this->created_at ? $this->created_at->format('H:i j.n.Y') : '';
            return "[{$time}] Sales: Đăng ký dự án ban đầu";
        }

        if (!$allUpdates) {
            return $updates[0]['formatted'];
        }

        return implode("\n", array_column($updates, 'formatted'));
    }

    /**
     * Find or auto-create Customer record in customers table from Project details
     */
    public function findOrCreateCustomerFromProject(): ?Customer
    {
        // 1. If collaborate_customer_id exists and points to a valid Customer
        if ($this->collaborate_customer_id) {
            $cust = Customer::find($this->collaborate_customer_id);
            if ($cust) return $cust;
        }

        // 2. If partner info is filled (collaborate_type === 'partner')
        if ($this->collaborate_type === 'partner' && !empty($this->collaborate_company)) {
            $customer = null;
            if (!empty($this->collaborate_tax_code)) {
                $customer = Customer::where('tax_code', trim($this->collaborate_tax_code))->first();
            }
            if (!$customer) {
                $customer = Customer::where('name', trim($this->collaborate_company))->first();
            }

            if (!$customer) {
                $customer = Customer::create([
                    'name' => trim($this->collaborate_company),
                    'tax_code' => $this->collaborate_tax_code ?: null,
                    'phone' => $this->collaborate_pic_phone ?: null,
                    'email' => $this->collaborate_pic_email ?: null,
                    'address' => $this->address ?: null,
                    'type' => 'normal',
                ]);
            }

            // Create Contact if customer has none
            if ($customer && $customer->contacts()->count() === 0) {
                $picName = $this->collaborate_pic_name ?: ($this->manager ? $this->manager->name : $customer->name);
                $customer->contacts()->create([
                    'name' => $picName,
                    'first_name' => $picName,
                    'position' => $this->collaborate_pic_title ?: 'P.I.C',
                    'phone' => $this->collaborate_pic_phone ?: ($customer->phone ?: ''),
                    'email' => $this->collaborate_pic_email ?: ($customer->email ?: ''),
                    'is_primary' => true,
                ]);
            }

            // Update project with customer ID
            $this->update([
                'collaborate_customer_id' => $customer->id,
                'customer_id' => $this->customer_id ?: $customer->id,
            ]);

            return $customer;
        }

        // 3. If customer_id exists and points to a valid Customer
        if ($this->customer_id) {
            $cust = Customer::find($this->customer_id);
            if ($cust) {
                if ($cust->contacts()->count() === 0) {
                    $picName = $this->collaborate_pic_name ?: ($this->manager ? $this->manager->name : $cust->name);
                    $cust->contacts()->create([
                        'name' => $picName,
                        'first_name' => $picName,
                        'position' => $this->collaborate_pic_title ?: 'P.I.C',
                        'phone' => $this->collaborate_pic_phone ?: ($cust->phone ?: ''),
                        'email' => $this->collaborate_pic_email ?: ($cust->email ?: ''),
                        'is_primary' => true,
                    ]);
                }
                return $cust;
            }
        }

        // 4. If end-user info is filled
        $euName = $this->eu_name_vi ?: $this->customer_name;
        if (!empty($euName)) {
            $customer = null;
            if (!empty($this->eu_tax_code)) {
                $customer = Customer::where('tax_code', trim($this->eu_tax_code))->first();
            }
            if (!$customer) {
                $customer = Customer::where('name', trim($euName))->first();
            }

            if (!$customer) {
                $customer = Customer::create([
                    'name' => trim($euName),
                    'name_en' => $this->eu_name_en ?: null,
                    'abv_name' => $this->eu_name_abbr ?: null,
                    'tax_code' => $this->eu_tax_code ?: null,
                    'address' => $this->address ?: null,
                    'type' => 'normal',
                ]);
            }

            // Create Contact if customer has none
            if ($customer && $customer->contacts()->count() === 0) {
                $picName = $this->collaborate_pic_name ?: ($this->manager ? $this->manager->name : $customer->name);
                $customer->contacts()->create([
                    'name' => $picName,
                    'first_name' => $picName,
                    'position' => $this->collaborate_pic_title ?: 'P.I.C',
                    'phone' => $this->collaborate_pic_phone ?: ($customer->phone ?: ''),
                    'email' => $this->collaborate_pic_email ?: ($customer->email ?: ''),
                    'is_primary' => true,
                ]);
            }

            // Update project reference
            $this->update([
                'collaborate_customer_id' => $customer->id,
                'customer_id' => $customer->id,
            ]);

            return $customer;
        }

        return null;
    }

    // --- Helper Methods ---

    /**
     * Helper to add working days excluding Saturday and Sunday
     */
    public static function addWorkingDays(Carbon $date, int $days): Carbon
    {
        $d = $date->copy();
        while ($days > 0) {
            $d->addDay();
            if (!$d->isWeekend()) {
                $days--;
            }
        }
        return $d;
    }

    /**
     * Is initial 4-hour processing SLA overdue?
     */
    public function getIsInitialOverdueAttribute(): bool
    {
        if ($this->intake_status === 'pending' && $this->initial_sla_due_at) {
            return now()->greaterThan($this->initial_sla_due_at);
        }
        return false;
    }

    /**
     * Initial 4h SLA status badge details
     */
    public function getInitialSlaStatusAttribute(): array
    {
        if ($this->intake_status !== 'pending') {
            return [
                'label' => 'Đã tiếp nhận',
                'color' => 'bg-green-100 text-green-800',
                'is_overdue' => false,
            ];
        }

        if ($this->is_initial_overdue) {
            $hoursOverdue = now()->diffInHours($this->initial_sla_due_at);
            return [
                'label' => "🔴 Quá hạn tiếp nhận ({$hoursOverdue}h)",
                'color' => 'bg-red-100 text-red-800 font-bold border border-red-300',
                'is_overdue' => true,
            ];
        }

        $remainingHours = ceil(now()->diffInMinutes($this->initial_sla_due_at, false) / 60);
        return [
            'label' => "⏳ Hạn tiếp nhận: Còn {$remainingHours}h",
            'color' => 'bg-amber-100 text-amber-800',
            'is_overdue' => false,
        ];
    }

    /**
     * Is Vendor Response SLA overdue?
     */
    public function getIsVendorOverdueAttribute(): bool
    {
        if (in_array($this->registration_status, ['vendor_processing', 'vendor_reminded', 'processing']) && $this->vendor_due_at) {
            return now()->greaterThan($this->vendor_due_at);
        }
        return false;
    }

    /**
     * Vendor SLA status badge details
     */
    public function getVendorSlaStatusAttribute(): array
    {
        if (in_array($this->registration_status, ['vendor_quoted', 'closed_won', 'update_status'])) {
            return [
                'label' => 'Hãng đã báo giá',
                'color' => 'bg-green-100 text-green-800',
                'is_overdue' => false,
            ];
        }

        if ($this->is_vendor_overdue) {
            $daysOverdue = now()->diffInDays($this->vendor_due_at);
            return [
                'label' => "🔴 Quá hạn Hãng phản hồi ({$daysOverdue} ngày)",
                'color' => 'bg-red-100 text-red-800 font-bold border border-red-300',
                'is_overdue' => true,
            ];
        }

        if ($this->vendor_due_at) {
            $daysRemaining = ceil(now()->diffInHours($this->vendor_due_at, false) / 24);
            return [
                'label' => "⏳ Chờ Hãng (Còn {$daysRemaining} ngày)",
                'color' => 'bg-blue-100 text-blue-800',
                'is_overdue' => false,
            ];
        }

        return [
            'label' => 'Chưa gửi Hãng',
            'color' => 'bg-gray-100 text-gray-800',
            'is_overdue' => false,
        ];
    }

    /**
     * Is Sales update overdue? (> 30 days since last update or registration)
     */
    public function getIsSalesUpdateOverdueAttribute(): bool
    {
        if (!in_array($this->registration_status, ['update_status', 'registered'])) {
            return false;
        }

        $lastUpdate = $this->last_sales_updated_at ?? $this->created_at;
        return now()->diffInDays($lastUpdate) >= 30;
    }

    /**
     * Registration workflow status label & color badge
     */
    public function getRegistrationStatusBadgeAttribute(): array
    {
        return match ($this->registration_status) {
            'submitted' => ['label' => 'Mới đăng ký (Chờ tiếp nhận)', 'color' => 'bg-purple-100 text-purple-800'],
            'processing' => ['label' => 'Đang xử lý tiếp nhận', 'color' => 'bg-blue-100 text-blue-800'],
            'vendor_processing' => ['label' => 'Đã gửi Hãng (Chờ phản hồi)', 'color' => 'bg-indigo-100 text-indigo-800'],
            'vendor_reminded' => ['label' => 'Đã nhắc Hãng (Gia hạn SLA)', 'color' => 'bg-amber-100 text-amber-800'],
            'vendor_quoted' => ['label' => 'Hãng đã báo giá/duyệt', 'color' => 'bg-emerald-100 text-emerald-800'],
            'vendor_rejected' => ['label' => 'Hãng từ chối ĐKDA', 'color' => 'bg-red-100 text-red-800'],
            'update_status' => ['label' => 'Đã đăng ký - Đang theo đuổi', 'color' => 'bg-teal-100 text-teal-800'],
            'closed_won' => ['label' => 'Closed Won (Thành công)', 'color' => 'bg-green-600 text-white font-bold'],
            'closed_lost' => ['label' => 'Closed Lost (Thất bại)', 'color' => 'bg-gray-600 text-white'],
            'cancelled' => ['label' => 'Đã hủy', 'color' => 'bg-red-600 text-white'],
            'expired' => ['label' => 'Hết hạn (Expired)', 'color' => 'bg-slate-700 text-white'],
            'duplicate' => ['label' => 'Dự án trùng', 'color' => 'bg-orange-100 text-orange-800'],
            'incomplete' => ['label' => 'Thiếu thông tin ĐKDA', 'color' => 'bg-yellow-100 text-yellow-800'],
            'on_hold' => ['label' => 'Tạm dừng (On Hold)', 'color' => 'bg-stone-200 text-stone-800'],
            default => ['label' => 'Mới tạo', 'color' => 'bg-gray-100 text-gray-800'],
        };
    }

    /**
     * Get status label for standard project status
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'planning' => 'Lên kế hoạch',
            'in_progress' => 'Đang thực hiện',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            'on_hold' => 'Tạm dừng',
            default => 'Không xác định',
        };
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'planning' => 'bg-yellow-100 text-yellow-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'on_hold' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getTotalRevenueAttribute(): float
    {
        return $this->sales()->sum('total');
    }

    public function getTotalCostAttribute(): float
    {
        $revenue = $this->total_revenue;
        $profit = $this->sales()->sum('margin');
        return $revenue - $profit;
    }

    public function getProfitAttribute(): float
    {
        return $this->sales()->sum('margin');
    }

    public function getProfitPercentAttribute(): float
    {
        if ($this->total_revenue <= 0) {
            return 0;
        }
        return ($this->profit / $this->total_revenue) * 100;
    }

    public function getTotalDebtAttribute(): float
    {
        return $this->sales()->sum('debt_amount');
    }

    public function getTotalExportValueAttribute(): float
    {
        return $this->exports()
            ->with('items')
            ->get()
            ->sum(function ($export) {
                return $export->items->sum(function ($item) {
                    return $item->quantity * $item->cost_usd * 25000;
                });
            });
    }

    /**
     * Scope for user permissions (Sales sees own, Sales Manager sees team, PM/PO/Admin sees all)
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        // Admin/BOD and PM manage the full registration portfolio.
        if ($user->hasAnyRole(['super_admin', 'admin', 'director']) ||
            in_array($user->department, ['PM', 'PM Team'], true)) {
            return $query;
        }

        // PO only receives Fortinet registrations; other vendors are handled
        // by Sales and PM and must not be exposed to the PO team.
        if ($user->hasAnyRole(['purchase_manager', 'purchase_staff']) ||
            in_array($user->department, ['PO', 'PO Team'], true)) {
            return $query->whereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', '%Fortinet%'));
        }

        // Sales Manager sees team projects
        if ($user->hasRole('sales_manager')) {
            return $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhereHas('manager', function ($m) use ($user) {
                      $m->where('department', $user->department);
                  });
            });
        }

        // Standard Sales: view own projects
        return $query->where('manager_id', $user->id);
    }

    /**
     * Scope for searching
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('eu_name_vi', 'like', "%{$search}%")
                ->orWhere('eu_name_en', 'like', "%{$search}%")
                ->orWhere('eu_tax_code', 'like', "%{$search}%")
                ->orWhere('collaborate_company', 'like', "%{$search}%")
                ->orWhere('vendor_deal_id', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering by status
     */
    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where(function ($q) use ($status) {
            $q->where('status', $status)
              ->orWhere('registration_status', $status);
        });
    }
}
