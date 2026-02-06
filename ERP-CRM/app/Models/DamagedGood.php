<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamagedGood extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'type',
        'product_id',
        'warehouse_id',
        'product_item_id',
        'quantity',
        'original_value',
        'recovery_value',
        'reason',
        'status',
        'discovery_date',
        'discovered_by',
        'solution',
        'note',
    ];

    protected $casts = [
        'discovery_date' => 'date',
        'quantity' => 'decimal:2',
        'original_value' => 'decimal:2',
        'recovery_value' => 'decimal:2',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function discoveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discovered_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ProductItem::class, 'damaged_good_items');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('discovery_date', [$startDate, $endDate]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Helper methods
    public function generateCode(): string
    {
        $prefix = $this->type === 'damaged' ? 'DMG' : 'LIQ';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now()->toDateString())->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }

    public function getLossAmount(): float
    {
        return $this->original_value - $this->recovery_value;
    }

    public function getRecoveryRate(): float
    {
        if ($this->original_value == 0) {
            return 0;
        }
        return ($this->recovery_value / $this->original_value) * 100;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'damaged' => 'Hàng hư hỏng',
            'liquidation' => 'Thanh lý',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'processed' => 'Đã xử lý',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'processed' => 'info',
            default => 'secondary',
        };
    }
}
