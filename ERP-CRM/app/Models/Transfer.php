<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'from_warehouse_id',
        'to_warehouse_id',
        'date',
        'employee_id',
        'total_qty',
        'note',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'total_qty' => 'integer',
    ];

    /**
     * Get the source warehouse for this transfer.
     */
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Get the destination warehouse for this transfer.
     */
    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Alias for fromWarehouse (backward compatibility).
     */
    public function warehouse(): BelongsTo
    {
        return $this->fromWarehouse();
    }

    /**
     * Get the employee who created this transfer.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the items for this transfer.
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
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
     * Scope: Filter by warehouse (source or destination).
     */
    public function scopeByWarehouse($query, int $warehouseId)
    {
        return $query->where(function ($q) use ($warehouseId) {
            $q->where('from_warehouse_id', $warehouseId)
              ->orWhere('to_warehouse_id', $warehouseId);
        });
    }

    /**
     * Generate unique transfer code.
     */
    public static function generateCode(): string
    {
        $lastTransfer = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastTransfer ? ((int) substr($lastTransfer->code, 3)) + 1 : 1;
        return 'TRF' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
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
            'rejected' => 'Từ chối',
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
            'cancelled' => 'gray',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
