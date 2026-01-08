<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Export extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'warehouse_id',
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
