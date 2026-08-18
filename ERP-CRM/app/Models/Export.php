<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Export extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'warehouse_id',
        'project_id',
        'customer_id',
        'contact_id',
        'date',
        'employee_id',
        'total_qty',
        'reference_type',
        'reference_id',
        'note',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'total_qty' => 'integer',
        'reference_id' => 'integer',
    ];

    /**
     * Get the warehouse for this export.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the project for this export.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the customer for this export.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the associated sale order if reference_type is sale.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'reference_id');
    }

    /**
     * Get the contact person for this export.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**
     * Get the employee who created this export.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the items for this export.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ExportItem::class);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Scope: Filter by warehouse.
     */
    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope: Filter exports based on user roles/departments.
     */
    public function scopeForUser($query, User $user)
    {
        // Admin, BOD, PM, PO, Warehouse, Accountant, Legal Team, Order Management see all
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'warehouse_manager', 'warehouse_staff', 'purchase_manager', 'purchase_staff', 'accountant', 'legal_team', 'order_management']) ||
            $user->department === 'PM' ||
            $user->department === 'PO' ||
            $user->department === 'Warehouse') {
            return $query;
        }

        // Sales Manager sees team exports (associated with team projects or team sales)
        if ($user->hasRole('sales_manager')) {
            return $query->where(function ($q) use ($user) {
                $q->whereHas('project', function ($pq) use ($user) {
                    $pq->where('manager_id', $user->id)
                      ->orWhereHas('manager', function ($m) use ($user) {
                          $m->where('department', $user->department);
                      });
                })
                ->orWhereHas('sale', function ($sq) use ($user) {
                    $sq->where('user_id', $user->id)
                      ->orWhereHas('user', function ($m) use ($user) {
                          $m->where('department', $user->department);
                      });
                })
                ->orWhere('employee_id', $user->id);
            });
        }

        // Standard Sales staff: only see own exports (linked to own project, own sale, or created by self)
        return $query->where(function ($q) use ($user) {
            $q->whereHas('project', function ($pq) use ($user) {
                $pq->where('manager_id', $user->id);
            })
            ->orWhereHas('sale', function ($sq) use ($user) {
                $sq->where('user_id', $user->id);
            })
            ->orWhere('employee_id', $user->id);
        });
    }

    /**
     * Generate unique export code.
     */
    public static function generateCode(): string
    {
        $lastExport = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastExport ? ((int) substr($lastExport->code, 3)) + 1 : 1;
        return 'EXP' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get status label in Vietnamese.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Bản nháp',
            'pending_admin' => 'Chờ Admin duyệt xuất',
            'pending_invoice' => 'Chờ KT xuất hóa đơn',
            'pending' => 'Chờ xử lý',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            'rejected' => 'Từ chối',
            default => $this->status,
        };
    }

    /**
     * Get total amount of the export slip.
     */
    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum(function($item) {
            return $item->calculated_total;
        });
    }

    /**
     * Get status color for badge.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'pending_admin' => 'yellow',
            'pending_invoice' => 'orange',
            'pending' => 'blue',
            'completed' => 'green',
            'cancelled' => 'gray',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
