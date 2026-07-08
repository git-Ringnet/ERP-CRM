<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Sale extends Model
{
    use HasFactory, LogsActivity;

    public $tempPaymentTerms;

    protected $fillable = [
        'code',
        'type',
        'project_id',
        'customer_id',
        'contact_id',
        'customer_name',
        'user_id',
        'date',
        'delivery_address',
        'subtotal',
        'discount',
        'vat',
        'vat_amount',
        'total',
        'cost',
        'margin',
        'margin_percent',
        'paid_amount',
        'debt_amount',
        'payment_status',
        'payment_due_date',
        'status',
        'note',
        'currency_id',
        'exchange_rate',
        'total_foreign',
        'paid_amount_foreign',
        'debt_amount_foreign',
        'pl_status',
        'pl_approved_at',
        'pl_approved_by',
        'invoice_date',
        'delivery_date',
        'payment_terms',
        'payment_term',
        'payment_term_type',
        'is_payment_exception',
        'payment_exception_file',
        'payment_exception_delegated_to',
    ];

    protected $casts = [
        'date' => 'date',
        'invoice_date' => 'date',
        'delivery_date' => 'date',
        'payment_due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'cost' => 'decimal:2',
        'margin' => 'decimal:2',
        'margin_percent' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'debt_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'total_foreign' => 'decimal:4',
        'paid_amount_foreign' => 'decimal:4',
        'debt_amount_foreign' => 'decimal:4',
        'pl_approved_at' => 'datetime',
        'is_payment_exception' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function ($sale) {
            if ($sale->tempPaymentTerms !== null) {
                $sale->syncPaymentSchedules($sale->tempPaymentTerms);
                $sale->tempPaymentTerms = null;
            }
            if ($sale->wasChanged('delivery_date') || $sale->wasChanged('invoice_date')) {
                $sale->updateMilestoneDueDates();
            }
        });
    }

    /**
     * Relationship with SalePaymentSchedule
     */
    public function paymentSchedules()
    {
        return $this->hasMany(SalePaymentSchedule::class, 'sale_id')->orderBy('sort_order');
    }

    /**
     * Relationship with PaymentApprovalLog
     */
    public function paymentApprovalLogs()
    {
        return $this->hasMany(PaymentApprovalLog::class, 'sale_id')->orderBy('performed_at', 'desc');
    }

    public function paymentExceptionDelegatedTo()
    {
        return $this->belongsTo(User::class, 'payment_exception_delegated_to');
    }

    /**
     * Accessor for payment_terms (backward compatibility)
     */
    public function getPaymentTermsAttribute()
    {
        // Try getting from temp if not saved or empty relation
        if ($this->tempPaymentTerms !== null) {
            return $this->tempPaymentTerms;
        }

        $schedules = $this->paymentSchedules;
        if ($schedules->isEmpty()) {
            return [];
        }

        $milestones = [];
        foreach ($schedules as $index => $ms) {
            $timing = 'after_contract';
            if ($ms->trigger_type === 'ON_GOODS_DELIVERED') {
                $timing = 'after_delivery';
            } elseif ($ms->trigger_type === 'ON_INVOICE_ISSUED') {
                $timing = 'after_invoice';
            } elseif ($ms->trigger_type === 'ON_DELIVERY_NOTICE') {
                $timing = 'after_delivery_notice';
            } elseif ($ms->trigger_type === 'BEFORE_EXPORT') {
                $timing = 'before_export';
            }

            $requiredBefore = 'after_delivery';
            if ($ms->blocking_stage === 'BLOCK_PO_SEND') {
                $requiredBefore = 'before_order';
            } elseif ($ms->blocking_stage === 'BLOCK_WAREHOUSE_EXPORT') {
                $requiredBefore = 'before_export';
            }

            $oldStatus = 'unpaid';
            if ($ms->status === 'paid') {
                $oldStatus = 'paid';
            } elseif ($ms->status === 'exception_approved') {
                if ($ms->blocking_stage === 'BLOCK_PO_SEND') {
                    $oldStatus = 'approved_preload';
                } else {
                    $oldStatus = 'approved_export_before_payment';
                }
            } elseif ($ms->status === 'overdue') {
                $oldStatus = 'overdue';
            }

            $milestones[] = [
                'milestone_name' => $ms->milestone_name,
                'percentage' => $ms->percentage,
                'amount' => $ms->amount,
                'timing' => $timing,
                'required_before' => $requiredBefore,
                'is_blocking' => $ms->blocking_stage ? 'yes' : 'no',
                'required_docs' => $ms->required_docs ?? 'none',
                'status' => $oldStatus,
                'due_days' => $ms->due_days,
                'due_date' => $ms->due_date ? $ms->due_date->format('Y-m-d') : null,
                'proof_file_path' => $ms->proof_file_path,
                'bod_approval_file_path' => $ms->bod_approval_file_path,
                'confirmed_by' => $ms->confirmed_by,
                'confirmed_at' => $ms->confirmed_at ? $ms->confirmed_at->toDateTimeString() : null,
                'delegated_to_id' => $ms->delegated_to_id,
            ];
        }

        return $milestones;
    }

    /**
     * Mutator for payment_terms (backward compatibility)
     */
    public function setPaymentTermsAttribute($value)
    {
        $this->tempPaymentTerms = is_string($value) ? json_decode($value, true) : $value;
    }

    public function syncPaymentSchedules(array $milestones)
    {
        $this->paymentSchedules()->delete();

        foreach ($milestones as $index => $ms) {
            $milestoneName = $ms['milestone_name'] ?? $ms['label'] ?? ('Đợt ' . ($index + 1));
            $percentage = (float)($ms['percentage'] ?? $ms['percent'] ?? 0);
            $amount = (float)($ms['amount'] ?? 0);
            $timing = $ms['timing'] ?? 'after_contract';
            $requiredBefore = $ms['required_before'] ?? 'after_delivery';
            $isBlocking = ($ms['is_blocking'] ?? 'yes') === 'yes';
            $requiredDocs = $ms['required_docs'] ?? 'none';
            $status = $ms['status'] ?? 'unpaid';
            $dueDays = (int)($ms['due_days'] ?? $ms['days'] ?? 0);
            $dueDate = isset($ms['due_date']) ? $ms['due_date'] : null;
            $confirmedBy = $ms['confirmed_by'] ?? null;
            $confirmedAt = isset($ms['confirmed_at']) ? $ms['confirmed_at'] : null;
            $proofFilePath = $ms['proof_file_path'] ?? null;
            $bodApprovalFilePath = $ms['bod_approval_file_path'] ?? null;

            $triggerType = 'ON_CONTRACT_SIGNED';
            $dueBase = 'contract_date';
            if ($timing === 'after_delivery') {
                $triggerType = 'ON_GOODS_DELIVERED';
                $dueBase = 'delivery_date';
            } elseif ($timing === 'after_invoice') {
                $triggerType = 'ON_INVOICE_ISSUED';
                $dueBase = 'invoice_date';
            } elseif ($timing === 'after_delivery_notice') {
                $triggerType = 'ON_DELIVERY_NOTICE';
                $dueBase = 'delivery_date';
            } elseif ($timing === 'before_export') {
                $triggerType = 'BEFORE_EXPORT';
                $dueBase = 'contract_date';
            }

            $blockingStage = null;
            if ($isBlocking) {
                if ($requiredBefore === 'before_order') {
                    $blockingStage = 'BLOCK_PO_SEND';
                } elseif ($requiredBefore === 'before_export') {
                    $blockingStage = 'BLOCK_WAREHOUSE_EXPORT';
                }
            }

            $newStatus = 'pending';
            if ($status === 'paid') {
                $newStatus = 'paid';
            } elseif ($status === 'approved_preload' || $status === 'approved_export_before_payment' || $status === 'exception_approved') {
                $newStatus = 'exception_approved';
            } elseif ($status === 'overdue') {
                $newStatus = 'overdue';
            }

            $this->paymentSchedules()->create([
                'sort_order' => $index + 1,
                'milestone_name' => $milestoneName,
                'percentage' => $percentage,
                'amount' => $amount,
                'trigger_type' => $triggerType,
                'blocking_stage' => $blockingStage,
                'due_base' => $dueBase,
                'due_days' => $dueDays,
                'required_docs' => $requiredDocs,
                'status' => $newStatus,
                'due_date' => $dueDate,
                'proof_file_path' => $proofFilePath,
                'bod_approval_file_path' => $bodApprovalFilePath,
                'confirmed_by' => $confirmedBy,
                'confirmed_at' => $confirmedAt,
            ]);
        }
    }

    public function updateMilestoneDueDates(): void
    {
        $deliveryDate = $this->delivery_date ? \Carbon\Carbon::parse($this->delivery_date) : null;
        if (!$deliveryDate) {
            $completedExport = Export::where('reference_id', $this->id)
                ->where('reference_type', 'sale')
                ->where('status', 'completed')
                ->latest()
                ->first();
            if ($completedExport) {
                $deliveryDate = $completedExport->date ? \Carbon\Carbon::parse($completedExport->date) : \Carbon\Carbon::parse($completedExport->updated_at);
            }
        }
        
        $invoiceDate = $this->invoice_date ? \Carbon\Carbon::parse($this->invoice_date) : null;
        
        foreach ($this->paymentSchedules as $schedule) {
            $dueBase = $schedule->due_base;
            $dueDays = $schedule->due_days;
            
            $newDueDate = null;
            if ($dueBase === 'delivery_date') {
                if ($deliveryDate) {
                    $newDueDate = $deliveryDate->copy()->addDays($dueDays);
                }
            } elseif ($dueBase === 'invoice_date') {
                if ($invoiceDate) {
                    $newDueDate = $invoiceDate->copy()->addDays($dueDays);
                }
            }
            
            if ($newDueDate) {
                $schedule->update([
                    'due_date' => $newDueDate,
                    'trigger_date' => $dueBase === 'delivery_date' ? $deliveryDate : ($dueBase === 'invoice_date' ? $invoiceDate : null),
                ]);
            }
        }
    }

    /**
     * Relationship with Currency
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Relationship with Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with Contact
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Relationship with User (creator/salesperson)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with User (P&L Approver)
     */
    public function plApprover()
    {
        return $this->belongsTo(User::class, 'pl_approved_by');
    }

    /**
     * Relationship with Project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relationship with SaleItem
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Relationship with SaleExpense
     */
    public function expenses()
    {
        return $this->hasMany(SaleExpense::class);
    }

    /**
     * Relationship with PurchaseOrder (linked POs)
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Relationship with Quotation
     */
    public function quotation()
    {
        return $this->hasOne(Quotation::class, 'converted_to_sale_id');
    }

    /**
     * Relationship with SaleAttachment
     */
    public function attachments()
    {
        return $this->hasMany(SaleAttachment::class);
    }

    /**
     * Relationship with PnlApprovalAttachment (file đính kèm duyệt P&L)
     */
    public function pnlAttachments()
    {
        return $this->hasMany(PnlApprovalAttachment::class);
    }

    /**
     * Relationship with SaleOrderRequest (yêu cầu đặt hàng)
     */
    public function orderRequests()
    {
        return $this->hasMany(SaleOrderRequest::class);
    }

    /**
     * Relationship with InvoiceRequest (yêu cầu xuất hóa đơn)
     */
    public function invoiceRequests()
    {
        return $this->hasMany(InvoiceRequest::class);
    }

    /**
     * Get all Purchase Orders associated with this sale
     * (Both direct link and aggregated via Order Requests)
     */
    public function getAllPurchaseOrdersAttribute()
    {
        // 1. Direct POs
        $directPos = $this->purchaseOrders;

        // 2. Aggregated POs (via Order Request Items)
        $aggregatedPos = PurchaseOrder::whereHas('items', function($q) {
            $q->whereHas('saleOrderRequestItem', function($sq) {
                $sq->whereHas('saleOrderRequest', function($sr) {
                    $sr->where('sale_id', $this->id);
                });
            });
        })->get();

        return $directPos->concat($aggregatedPos)->unique('id');
    }

    /**
     * Scope for searching sales
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('customer_name', 'like', "%{$search}%")
              ->orWhere('note', 'like', "%{$search}%")
              ->orWhereHas('quotation', function ($qq) use ($search) {
                  $qq->where('code', 'like', "%{$search}%");
              });
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

        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeFilterByType(Builder $query, ?string $type): Builder
    {
        if (empty($type)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        // Ưu tiên đồng bộ với pl_status khi trạng thái đơn chưa tiến xa
        if ($this->status === 'pending' && $this->pl_status === 'rejected') {
            return 'PNL Từ chối';
        }
        if ($this->status === 'pending' && $this->pl_status === 'need_revision') {
            return 'Yêu cầu chỉnh sửa';
        }

        return match($this->status) {
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    /**
     * Get P&L status label
     */
    public function getPlStatusLabelAttribute(): string
    {
        return match($this->pl_status) {
            'draft' => 'Nháp (P&L)',
            'pending' => 'Chờ duyệt (P&L)',
            'approved' => 'Đã duyệt (P&L)',
            'need_revision' => 'Yêu cầu chỉnh sửa (P&L)',
            'rejected' => 'Từ chối (P&L)',
            default => 'Chưa lập',
        };
    }

    /**
     * Get P&L status color class
     */
    public function getPlStatusColorAttribute(): string
    {
        return match($this->pl_status) {
            'draft' => 'bg-gray-100 text-gray-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'need_revision' => 'bg-amber-100 text-amber-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if P&L is editable
     */
    public function isPlEditable(): bool
    {
        return in_array($this->pl_status, ['draft', 'rejected', 'need_revision', null, '']);
    }

    /**
     * Check if the sale is currently in the approval process
     */
    public function isPendingApproval(): bool
    {
        return $this->pl_status === 'pending';
    }

    /**
     * Get the ID of the person who created this sale (for notifications)
     */
    public function getCreatorId(): ?int
    {
        return $this->user_id;
    }

    /**
     * Check if all items in all order requests are fully received
     */
    public function isFullyReceived(): bool
    {
        // If no order requests, we assume it's fully ready (from procurement perspective)
        if ($this->orderRequests->count() === 0) {
            return true;
        }

        foreach ($this->orderRequests as $request) {
            foreach ($request->items as $item) {
                if ($item->received_quantity_total < $item->quantity) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get status color class
     */
    public function getStatusColorAttribute(): string
    {
        // PNL rejected → hiển thị màu đỏ dù status = pending
        if ($this->status === 'pending' && $this->pl_status === 'rejected') {
            return 'bg-red-100 text-red-800';
        }
        // PNL need_revision → hiển thị màu cam
        if ($this->status === 'pending' && $this->pl_status === 'need_revision') {
            return 'bg-amber-100 text-amber-800';
        }

        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'shipping' => 'bg-purple-100 text-purple-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get dashboard status based on Sale + linked PO status
     * Chờ đặt → Đã đặt → Đang về/Hold → Đã về → Xuất hóa đơn → Hoàn thành
     */
    public function getDashboardStatusAttribute(): string
    {
        // 1. Cancelled
        if ($this->status === 'cancelled') return 'cancelled';

        // 2. PNL Rejected → luôn hiển thị "PNL Từ chối" bất kể status
        if ($this->pl_status === 'rejected') {
            return 'pnl_rejected';
        }

        // 2b. PNL Need Revision → hiển thị "Yêu cầu chỉnh sửa"
        if ($this->pl_status === 'need_revision') {
            return 'pnl_need_revision';
        }

        // 3. Pending (bao gồm cả khi PNL đang chờ duyệt)
        if ($this->status === 'pending') {
            // PNL đang chờ duyệt → hiển thị trạng thái riêng
            if ($this->pl_status === 'pending') {
                return 'pnl_pending';
            }
            return 'pending';
        }

        // 4. Completed
        if ($this->status === 'completed') return 'completed';

        // 5. Xuất hóa đơn (shipping to customer)
        if ($this->status === 'shipping') return 'invoiced';

        // 6. Check linked PO status
        $associatedPos = $this->all_purchase_orders;
        
        if ($associatedPos->isEmpty()) {
            return 'waiting_order'; // Chờ đặt
        }

        // 5. Check if any PO is on hold
        if ($associatedPos->contains('is_hold', true)) {
            return 'hold';
        }

        // 6. Determine overall procurement status
        // A sale is 'received' only if ALL items in all requests are fully received
        if ($this->isFullyReceived()) {
            $status = 'received';
            
            // 7. Check Invoice Request (Overwrites 'received' if request exists)
            $latestInvoice = $this->invoiceRequests()->latest()->first();
            if ($latestInvoice) {
                $status = 'invoicing';
            }
            return $status;
        }

        // If not fully received, determine if it's 'ordered' or 'in_transit'
        // 'in_transit' if any PO is shipping or partial_received or received (but SO is not fully received)
        $hasInTransit = $associatedPos->contains(function($po) {
            return in_array($po->status, ['shipping', 'partial_received', 'received']);
        });

        if ($hasInTransit) {
            return 'in_transit';
        }

        // If all POs are just approved/draft/pending, it's 'ordered'
        return 'ordered';
    }

    /**
     * Get dashboard status label (Vietnamese)
     */
    public function getDashboardStatusLabelAttribute(): string
    {
        return match($this->dashboard_status) {
            'pending' => 'Chờ duyệt',
            'pnl_pending' => 'Chờ duyệt PNL',
            'pnl_rejected' => 'PNL Từ chối',
            'pnl_need_revision' => 'Yêu cầu chỉnh sửa',
            'waiting_order' => 'Đã duyệt',
            'ordered' => 'Đã đặt hàng',
            'in_transit' => 'Chờ hàng về',
            'hold' => 'Hold',
            'received' => 'Hàng về',
            'invoicing' => 'Hóa đơn',
            'invoiced' => 'Giao hàng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    /**
     * Get dashboard status color class
     */
    public function getDashboardStatusColorAttribute(): string
    {
        return match($this->dashboard_status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'pnl_pending' => 'bg-orange-100 text-orange-800',
            'pnl_rejected' => 'bg-red-100 text-red-800',
            'pnl_need_revision' => 'bg-amber-100 text-amber-800',
            'waiting_order' => 'bg-blue-100 text-blue-800',
            'ordered' => 'bg-indigo-100 text-indigo-800',
            'in_transit' => 'bg-purple-100 text-purple-800',
            'hold' => 'bg-orange-100 text-orange-800',
            'received' => 'bg-emerald-100 text-emerald-800',
            'invoicing' => 'bg-cyan-100 text-cyan-800',
            'invoiced' => 'bg-amber-100 text-amber-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get dashboard step index (0 to 6)
     */
    public function getDashboardStepAttribute(): int
    {
        return match($this->dashboard_status) {
            'pending' => 0,
            'pnl_pending' => 0,
            'pnl_rejected' => 0,
            'pnl_need_revision' => 0,
            'waiting_order' => 1,
            'ordered' => 2,
            'in_transit' => 3,
            'received' => 4,
            'invoicing' => 5,
            'invoiced' => 6,
            'completed' => 7,
            'hold' => 3, // Treat hold as transit step
            'cancelled' => -1,
            default => 0,
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'retail' => 'Bán lẻ',
            'project' => 'Bán theo dự án',
            default => 'Không xác định',
        };
    }

    /**
     * Get payment status label
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        $base = match($this->payment_status) {
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Thanh toán một phần',
            'paid' => 'Đã thanh toán',
            default => 'Chưa thanh toán',
        };

        // Thêm trạng thái tới hạn / quá hạn
        if ($this->payment_status !== 'paid') {
            $dueDate = null;
            if ($this->payment_due_date) {
                $dueDate = \Carbon\Carbon::parse($this->payment_due_date)->startOfDay();
            } elseif ($this->invoice_date) {
                $debtDays = $this->customer?->debt_days ?? 30;
                $dueDate = \Carbon\Carbon::parse($this->invoice_date)->addDays($debtDays)->startOfDay();
            }

            if ($dueDate) {
                $today = now()->startOfDay();
                $daysUntilDue = $today->diffInDays($dueDate, false);

                if ($daysUntilDue < 0) {
                    return 'Quá hạn ' . abs($daysUntilDue) . ' ngày';
                } elseif ($daysUntilDue <= 3) {
                    return 'Tới hạn' . ($daysUntilDue > 0 ? " ({$daysUntilDue} ngày)" : ' (Hôm nay)');
                }
            }
        }

        return $base;
    }

    /**
     * Get payment status color
     */
    public function getPaymentStatusColorAttribute(): string
    {
        // Ưu tiên hiển thị trạng thái tới hạn / quá hạn
        if ($this->payment_status !== 'paid') {
            $dueDate = null;
            if ($this->payment_due_date) {
                $dueDate = \Carbon\Carbon::parse($this->payment_due_date)->startOfDay();
            } elseif ($this->invoice_date) {
                $debtDays = $this->customer?->debt_days ?? 30;
                $dueDate = \Carbon\Carbon::parse($this->invoice_date)->addDays($debtDays)->startOfDay();
            }

            if ($dueDate) {
                $today = now()->startOfDay();
                $daysUntilDue = $today->diffInDays($dueDate, false);

                if ($daysUntilDue < 0) {
                    return 'bg-red-600 text-white animate-pulse';
                } elseif ($daysUntilDue <= 3) {
                    return 'bg-orange-500 text-white';
                }
            }
        }

        return match($this->payment_status) {
            'unpaid' => 'bg-red-100 text-red-800',
            'partial' => 'bg-yellow-100 text-yellow-800',
            'paid' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Calculate total cost from expenses
     * Tính tổng chi phí từ P&L (sale_items) thay vì sale_expenses
     */
    public function calculateTotalCost(): void
    {
        $totalCost = 0;
        
        // 1. Chi phí từ các item (Standard OpEx)
        foreach ($this->items as $item) {
            $costBase = $item->cost_total ?: 0;
            
            // Chi phí Tài chính
            if (!is_null($item->finance_cost_percent) && $item->finance_cost_percent > 0) {
                $totalCost += ($costBase * $item->finance_cost_percent / 100);
            }
            
            // Lãi vay phát sinh (% hoặc fixed)
            if (!is_null($item->overdue_interest_percent) && $item->overdue_interest_percent > 0) {
                $totalCost += ($costBase * $item->overdue_interest_percent / 100);
            } else {
                $totalCost += ($item->overdue_interest_cost ?: 0);
            }
            
            // Chi phí Quản lí
            if (!is_null($item->management_cost_percent) && $item->management_cost_percent > 0) {
                $totalCost += ($costBase * $item->management_cost_percent / 100);
            }
            
            // 24x7 Support
            if (!is_null($item->support_247_cost_percent) && $item->support_247_cost_percent > 0) {
                $totalCost += ($costBase * $item->support_247_cost_percent / 100);
            }
            
            // Other Support
            if (!is_null($item->other_support_cost) && $item->other_support_cost > 0) {
                $totalCost += ($costBase * $item->other_support_cost / 100);
            }
            
            // POC / Triển khai / Thuế nhà thầu: VND hoặc % trên giá vốn dòng
            if (! is_null($item->technical_poc_percent) && $item->technical_poc_percent > 0) {
                $totalCost += ($costBase * (float) $item->technical_poc_percent / 100);
            } else {
                $totalCost += ($item->technical_poc_cost ?: 0);
            }

            if (! is_null($item->implementation_cost_percent) && $item->implementation_cost_percent > 0) {
                $totalCost += ($costBase * (float) $item->implementation_cost_percent / 100);
            } else {
                $totalCost += ($item->implementation_cost ?: 0);
            }

            if (! is_null($item->contractor_tax_percent) && $item->contractor_tax_percent > 0) {
                $totalCost += ($costBase * (float) $item->contractor_tax_percent / 100);
            } else {
                $totalCost += ($item->contractor_tax ?: 0);
            }
        }

        // 2. Chi phí bổ sung từ bảng sale_expenses (loại trừ các loại đã tính ở trên nếu là dạng đồng bộ)
        $standardTypes = [
            'Chi phí Tài chính',
            'Lãi vay phát sinh do nợ quá hạn',
            'Chi phí Quản lí, Back Office & kỹ thuật',
            '24x7 Support cost',
            'Other Support',
            'Technical support/POC',
            'Chi phí triển khai hợp đồng',
            'Thuế nhà thầu',
        ];

        $costBaseTotal = $this->getCostOfGoodsSold();

        foreach ($this->expenses as $expense) {
            // Nếu là loại standard và đã được sync vào item thì bỏ qua để tránh tính trùng
            // (Thường fixed expenses sẽ được sync vào technical_poc_cost hoặc implementation_cost)
            if (in_array($expense->type, $standardTypes)) continue;

            if ($expense->input_mode === 'percent') {
                $totalCost += ($costBaseTotal * ($expense->percent_value / 100));
            } else {
                $totalCost += ($expense->amount ?: 0);
            }
        }
        
        $this->cost = round($totalCost);
    }

    /**
     * Get total cost of goods sold (giá vốn hàng bán)
     */
    public function getCostOfGoodsSold(): float
    {
        return $this->items()->sum('cost_total');
    }

    /**
     * Calculate margin
     * Margin = Doanh thu thuần - Giá vốn - Tổng chi phí (khớp 100% với bảng P&L)
     */
    public function calculateMargin(): void
    {
        $this->loadMissing(['items', 'expenses']);

        // Revenue Excl VAT — khớp với P&L: sum(revenue_total) * (1 - discount%)
        $discountAmount = $this->subtotal * ($this->discount / 100);
        $netRevenue = $this->subtotal - $discountAmount;

        $totalNetProfit = 0;

        foreach ($this->items as $item) {
            $itemRevenue = ($item->total ?: 0) * (1 - ($this->discount ?? 0) / 100);
            $costTotal   = $item->cost_total ?: 0;

            // Gross profit per row
            $grossProfit = $itemRevenue - $costTotal;

            // --- Tính chi phí OpEx từng dòng (logic giống P&L JS) ---
            $financeV = 0;
            if (!is_null($item->finance_cost_percent) && floatval($item->finance_cost_percent) > 0) {
                $financeV = round($costTotal * $item->finance_cost_percent / 100);
            }

            $overdueV = 0;
            if (!is_null($item->overdue_interest_percent) && floatval($item->overdue_interest_percent) > 0) {
                $overdueV = round($costTotal * $item->overdue_interest_percent / 100);
            } else {
                $overdueV = round($item->overdue_interest_cost ?: 0);
            }

            $mgmtV = 0;
            if (!is_null($item->management_cost_percent) && floatval($item->management_cost_percent) > 0) {
                $mgmtV = round($costTotal * $item->management_cost_percent / 100);
            }

            $supportV = 0;
            if (!is_null($item->support_247_cost_percent) && floatval($item->support_247_cost_percent) > 0) {
                $supportV = round($costTotal * $item->support_247_cost_percent / 100);
            }

            $otherV = 0;
            if (!is_null($item->other_support_cost) && floatval($item->other_support_cost) > 0) {
                $otherV = round($costTotal * $item->other_support_cost / 100);
            }

            // POC
            $pocV = 0;
            if (!is_null($item->technical_poc_percent) && floatval($item->technical_poc_percent) > 0) {
                $pocV = round($costTotal * $item->technical_poc_percent / 100);
            } else {
                $pocV = round($item->technical_poc_cost ?: 0);
            }

            // Implementation
            $impV = 0;
            if (!is_null($item->implementation_cost_percent) && floatval($item->implementation_cost_percent) > 0) {
                $impV = round($costTotal * $item->implementation_cost_percent / 100);
            } else {
                $impV = round($item->implementation_cost ?: 0);
            }

            // Contractor tax
            $taxV = round($item->contractor_tax ?: 0);

            // Extra expenses per item
            $extraSum = 0;
            $itemExtraData = $item->extra_expenses_data ?? [];
            foreach ($itemExtraData as $expId => $amt) {
                $extraSum += round(floatval($amt));
            }

            // Tổng chi phí dòng
            $rowTotalCosts = $financeV + $overdueV + $mgmtV + $supportV + $otherV + $pocV + $impV + $taxV + $extraSum;

            // Net profit dòng
            $rowNetProfit = $grossProfit - $rowTotalCosts;
            $totalNetProfit += $rowNetProfit;
        }

        // Chi phí % phân bổ từ sale_expenses (loại extra, dạng percent)
        $standardTypes = [
            'Chi phí Tài chính',
            'Lãi vay phát sinh do nợ quá hạn',
            'Chi phí Quản lí, Back Office & kỹ thuật',
            '24x7 Support cost',
            'Other Support',
            'Technical support/POC',
            'Chi phí triển khai hợp đồng',
            'Thuế nhà thầu',
        ];

        $costBaseTotal = $this->getCostOfGoodsSold();

        foreach ($this->expenses as $expense) {
            if (in_array($expense->type, $standardTypes)) continue;

            if ($expense->input_mode === 'percent') {
                // Percent-based expenses: tính trên giá vốn tổng
                $totalNetProfit -= round($costBaseTotal * ($expense->percent_value / 100));
            } else {
                // Fixed-mode extra expenses: kiểm tra đã tính qua extra_expenses_data chưa
                $sumFromItems = 0;
                foreach ($this->items as $saleItem) {
                    $extraData = $saleItem->extra_expenses_data ?? [];
                    $sumFromItems += (float) ($extraData[(string) $expense->id] ?? 0);
                }
                // Nếu chưa phân bổ per-item (tổng = 0), tính trực tiếp từ sale_expenses.amount
                if ($sumFromItems <= 0) {
                    $totalNetProfit -= round((float) ($expense->amount ?? 0));
                }
                // Nếu sumFromItems > 0 thì đã tính trong vòng lặp item phía trên
            }
        }

        $this->margin = round($totalNetProfit);
        $this->cost = round($netRevenue - $this->getCostOfGoodsSold() - $totalNetProfit); // Giữ sync field cost

        if ($netRevenue > 0) {
            $this->margin_percent = ($this->margin / $netRevenue) * 100;
            $this->margin_percent = max(-999.99, min(999.99, $this->margin_percent));
        } else {
            $this->margin_percent = 0;
        }
    }

    /**
     * Get expenses by type
     */
    public function getExpensesByType(string $type): float
    {
        return $this->expenses()->where('type', $type)->sum('amount');
    }

    /**
     * Check if margin is negative (loss)
     */
    public function hasNegativeMargin(): bool
    {
        return $this->margin < 0;
    }

    /**
     * Get margin status color
     */
    public function getMarginColorAttribute(): string
    {
        if ($this->margin < 0) {
            return 'text-red-600';
        } elseif ($this->margin_percent < 10) {
            return 'text-yellow-600';
        } else {
            return 'text-green-600';
        }
    }

    /**
     * Update debt amount
     */
    public function updateDebt(): void
    {
        $this->debt_amount = $this->total - $this->paid_amount;
        
        // Update foreign debt if applicable
        if ($this->exchange_rate > 0) {
            $this->debt_amount_foreign = $this->total_foreign - $this->paid_amount_foreign;
        }

        // Auto allocate paid_amount to milestones in order
        if ($this->exists) {
            $schedules = $this->paymentSchedules()->orderBy('sort_order')->get();
            if ($schedules->isNotEmpty()) {
                $remainingPaid = (float) $this->paid_amount;
                $total = (float) $this->total;
                
                foreach ($schedules as $ms) {
                    $percent = (float) $ms->percentage;
                    $amount = (float)$ms->amount > 0 ? (float)$ms->amount : round(($total * $percent) / 100, 2);
                    
                    if ($amount > 0 && $remainingPaid >= $amount) {
                        if ($ms->status !== 'paid') {
                            $ms->status = 'paid';
                            $ms->confirmed_by = $ms->confirmed_by ?? 'System Auto';
                            $ms->confirmed_at = $ms->confirmed_at ?? now();
                            $ms->save();
                        }
                        $remainingPaid -= $amount;
                    } else {
                        if ($ms->status === 'paid' && in_array($ms->confirmed_by, ['System Auto', null, ''])) {
                            $ms->status = 'pending';
                            $ms->confirmed_by = null;
                            $ms->confirmed_at = null;
                            $ms->save();
                        }
                    }
                }
            }
        } else {
            $milestones = $this->payment_terms ?? [];
            if (!empty($milestones)) {
                $remainingPaid = (float) $this->paid_amount;
                $total = (float) $this->total;
                
                foreach ($milestones as $index => &$ms) {
                    $percent = (float) ($ms['percentage'] ?? ($ms['percent'] ?? 0));
                    $amount = isset($ms['amount']) && (float)$ms['amount'] > 0
                        ? (float)$ms['amount']
                        : round(($total * $percent) / 100, 2);
                    
                    if ($amount > 0 && $remainingPaid >= $amount) {
                        if (($ms['status'] ?? '') !== 'paid') {
                            $ms['status'] = 'paid';
                            $ms['confirmed_by'] = $ms['confirmed_by'] ?? 'System Auto';
                            $ms['confirmed_at'] = $ms['confirmed_at'] ?? now()->toDateTimeString();
                        }
                        $remainingPaid -= $amount;
                    } else {
                        if (($ms['status'] ?? '') === 'paid' && in_array($ms['confirmed_by'] ?? '', ['System Auto', null, ''])) {
                            $ms['status'] = 'unpaid';
                            unset($ms['confirmed_by']);
                            unset($ms['confirmed_at']);
                        }
                    }
                }
                $this->payment_terms = $milestones;
            }
        }

        // Update payment status
        if ($this->paid_amount <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($this->paid_amount >= $this->total) {
            $this->payment_status = 'paid';
        } else {
            $this->payment_status = 'partial';
        }
    }

    /**
     * Get polymorphic exports relationship.
     */
    public function exports()
    {
        return $this->hasMany(Export::class, 'reference_id')->where('reference_type', 'sale');
    }

    /**
     * Compute and get detailed payment conditions status
     */
    public function getPaymentConditionStatus(): array
    {
        if ($this->exists) {
            $milestones = $this->paymentSchedules()->orderBy('sort_order')->get()->map(function($s) {
                return [
                    'milestone_name' => $s->milestone_name,
                    'percentage' => $s->percentage,
                    'amount' => $s->amount,
                    'timing' => $s->trigger_type === 'ON_CONTRACT_SIGNED' ? 'after_contract' : ($s->trigger_type === 'ON_GOODS_DELIVERED' ? 'after_delivery' : ($s->trigger_type === 'ON_INVOICE_ISSUED' ? 'after_invoice' : ($s->trigger_type === 'ON_DELIVERY_NOTICE' ? 'after_delivery_notice' : ($s->trigger_type === 'BEFORE_EXPORT' ? 'before_export' : $s->trigger_type)))),
                    'required_before' => $s->blocking_stage === 'BLOCK_PO_SEND' ? 'before_order' : ($s->blocking_stage === 'BLOCK_WAREHOUSE_EXPORT' ? 'before_export' : 'after_delivery'),
                    'is_blocking' => $s->blocking_stage ? 'yes' : 'no',
                    'required_docs' => $s->required_docs,
                    'status' => $s->status,
                    'due_days' => $s->due_days,
                    'due_date' => $s->due_date ? $s->due_date->format('Y-m-d') : null,
                    'proof_file_path' => $s->proof_file_path,
                    'bod_approval_file_path' => $s->bod_approval_file_path,
                    'confirmed_by' => $s->confirmed_by,
                    'confirmed_at' => $s->confirmed_at,
                    'delegated_to_id' => $s->delegated_to_id,
                ];
            })->toArray();
        } else {
            $milestones = $this->payment_terms ?? [];
        }
        $total = (float) $this->total;
        $paid = (float) $this->paid_amount;
        
        $eligibleForOrder = true;
        $eligibleForExport = true;
        
        $pendingOrderMilestones = [];
        $pendingExportMilestones = [];
        
        // Find completed delivery date if any
        $deliveryDate = $this->delivery_date ? \Carbon\Carbon::parse($this->delivery_date) : null;
        if (!$deliveryDate) {
            $completedExport = Export::where('reference_id', $this->id)
                ->where('reference_type', 'sale')
                ->where('status', 'completed')
                ->latest()
                ->first();
            if ($completedExport) {
                $deliveryDate = $completedExport->date ? \Carbon\Carbon::parse($completedExport->date) : \Carbon\Carbon::parse($completedExport->updated_at);
            }
        }
        
        $invoiceDate = $this->invoice_date ? \Carbon\Carbon::parse($this->invoice_date) : null;
        $today = \Carbon\Carbon::today();
        
        $updatedMilestones = [];
        $allPaid = true;
        
        foreach ($milestones as $index => $ms) {
            $name = $ms['milestone_name'] ?? ($ms['label'] ?? ('Đợt ' . ($index + 1)));
            $percent = (float) ($ms['percentage'] ?? ($ms['percent'] ?? 0));
            $amount = isset($ms['amount']) && (float)$ms['amount'] > 0
                ? (float)$ms['amount']
                : round(($total * $percent) / 100, 2);
            
            $requiredBefore = $ms['required_before'] ?? 'after_delivery';
            $status = $ms['status'] ?? 'unpaid';
            $timing = $ms['timing'] ?? 'after_contract';
            $dueDays = (int) ($ms['due_days'] ?? ($ms['days'] ?? 0));
            
            $dueDate = null;
            if (isset($ms['due_date']) && $ms['due_date']) {
                $dueDate = \Carbon\Carbon::parse($ms['due_date']);
            } else {
                // Auto calculate due date
                if ($requiredBefore === 'after_delivery' || $timing === 'after_delivery') {
                    if ($deliveryDate) {
                        $dueDate = $deliveryDate->copy()->addDays($dueDays);
                    }
                } elseif ($timing === 'after_invoice') {
                    if ($invoiceDate) {
                        $dueDate = $invoiceDate->copy()->addDays($dueDays);
                    }
                }
            }
            
            $overdueDays = 0;
            // Update status based on due dates if not paid / approved / pending finance
            if (!in_array($status, ['paid', 'pending_finance', 'approved_preload', 'approved_export_before_payment', 'exception_approved'])) {
                if ($dueDate) {
                    if ($today->gt($dueDate)) {
                        $status = 'overdue';
                        $overdueDays = $today->diffInDays($dueDate);
                    } elseif ($today->equalTo($dueDate)) {
                        $status = 'due';
                    } else {
                        $status = 'not_yet_due';
                    }
                } else {
                    $status = 'unpaid';
                }
            }
            
            if ($status !== 'paid') {
                $allPaid = false;
            }
            
            // Check eligibility blocks
            $isBlocking = ($ms['is_blocking'] ?? 'yes') === 'yes';
            if ($requiredBefore === 'before_order' && $isBlocking) {
                if ($status !== 'paid' && $status !== 'approved_preload') {
                    $eligibleForOrder = false;
                    $pendingOrderMilestones[] = $name;
                }
            } elseif ($requiredBefore === 'before_export' && $isBlocking) {
                if ($status !== 'paid' && $status !== 'approved_export_before_payment' && $status !== 'approved_preload') {
                    $eligibleForExport = false;
                    $pendingExportMilestones[] = $name;
                }
            }
            
            $updatedMilestones[] = [
                'milestone_name' => $name,
                'percentage' => $percent,
                'amount' => $amount,
                'timing' => $timing,
                'required_before' => $requiredBefore,
                'is_blocking' => $isBlocking ? 'yes' : 'no',
                'required_docs' => $ms['required_docs'] ?? 'none',
                'status' => $status,
                'due_days' => $dueDays,
                'due_date' => $dueDate ? $dueDate->format('Y-m-d') : null,
                'overdue_days' => $overdueDays,
                'proof_file_path' => $ms['proof_file_path'] ?? null,
                'bod_approval_file_path' => $ms['bod_approval_file_path'] ?? null,
                'confirmed_by' => $ms['confirmed_by'] ?? null,
                'confirmed_at' => $ms['confirmed_at'] ?? null,
            ];
        }
        
        // If sale-level exception is active, bypass blocks
        if ($this->is_payment_exception) {
            $eligibleForOrder = true;
            $eligibleForExport = true;
        }
        
        return [
            'eligible_for_order' => $eligibleForOrder,
            'eligible_for_export' => $eligibleForExport,
            'pending_order_milestones' => $pendingOrderMilestones,
            'pending_export_milestones' => $pendingExportMilestones,
            'milestones' => $updatedMilestones,
            'all_paid' => $allPaid,
            'has_exception' => (bool) $this->is_payment_exception,
        ];
    }
}
