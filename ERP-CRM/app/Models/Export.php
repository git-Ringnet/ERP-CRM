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
        // 1. Quyền xem tất cả phiếu xuất kho hoặc các vai trò quản trị, kho, kế toán
        if ($user->can('view_all_exports') ||
            $user->hasAnyRole(['super_admin', 'admin', 'director', 'warehouse_manager', 'warehouse_staff', 'purchase_manager', 'purchase_staff', 'accountant', 'legal_team', 'order_management']) ||
            $user->department === 'PM' ||
            $user->department === 'PO' ||
            $user->department === 'Warehouse') {
            return $query;
        }

        // 2. Sales Manager hoặc Trưởng nhóm: xem các phiếu của team mình (thuộc nhóm quản lý hoặc cùng phòng ban)
        if ($user->hasRole('sales_manager') || $user->leadingGroups()->where('status', 'active')->exists()) {
            $managedIds = $user->getLeadGroupMemberIds();
            return $query->where(function ($q) use ($user, $managedIds) {
                $q->whereHas('project', function ($pq) use ($user, $managedIds) {
                    $pq->whereIn('manager_id', $managedIds)
                      ->orWhereHas('manager', function ($m) use ($user) {
                          $m->where('department', $user->department);
                      });
                })
                ->orWhereHas('sale', function ($sq) use ($user, $managedIds) {
                    $sq->whereIn('user_id', $managedIds)
                      ->orWhereHas('user', function ($m) use ($user) {
                          $m->where('department', $user->department);
                      });
                })
                ->orWhereIn('employee_id', $managedIds);
            });
        }

        // 3. Nhân viên Sales / User chỉ xem bản thân (view_own_exports):
        // Chỉ xem phiếu xuất của chính mình (nhân viên phụ trách là mình hoặc phiếu xuất cho đơn hàng của mình)
        return $query->where(function ($q) use ($user) {
            $q->where('employee_id', $user->id)
              ->orWhereHas('sale', function ($sq) use ($user) {
                  $sq->where('user_id', $user->id);
              });
        });
    }

    /**
     * Generate unique export code.
     */
    public static function generateCode(): string
    {
        $maxNum = 0;
        $codes = self::where('code', 'LIKE', 'EXP%')->pluck('code');
        foreach ($codes as $c) {
            if (preg_match('/^EXP(\d+)$/i', $c, $m)) {
                $num = (int) $m[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        $nextNumber = $maxNum + 1;

        do {
            $code = 'EXP' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            if (!self::where('code', $code)->exists()) {
                return $code;
            }
            $nextNumber++;
        } while (true);
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
