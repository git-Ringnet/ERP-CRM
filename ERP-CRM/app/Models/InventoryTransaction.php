<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'warehouse_id',
        'to_warehouse_id',
        'project_id',
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
     * Get the warehouse for this transaction.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the destination warehouse (for transfers).
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Get the employee who created this transaction.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the project for this transaction.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the items for this transaction.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class, 'transaction_id');
    }

    /**
     * Scope: Filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by warehouse.
     */
    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Generate unique transaction code.
     */
    public static function generateCode(string $type): string
    {
        $prefix = match($type) {
            'import' => 'IMP',
            'export' => 'EXP',
            'transfer' => 'TRF',
            default => 'TXN',
        };

        $lastTransaction = self::where('type', $type)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastTransaction ? ((int) substr($lastTransaction->code, 3)) + 1 : 1;
        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get type label in Vietnamese.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'import' => 'Nhập kho',
            'export' => 'Xuất kho',
            'transfer' => 'Chuyển kho',
            default => $this->type,
        };
    }

    /**
     * Get status label in Vietnamese.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Chờ xử lý',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            default => $this->status,
        };
    }

    /**
     * Get status color for badge.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get type color for badge.
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'import' => 'blue',
            'export' => 'orange',
            'transfer' => 'purple',
            default => 'gray',
        };
    }
}
