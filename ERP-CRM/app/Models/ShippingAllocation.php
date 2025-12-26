<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ShippingAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'purchase_order_id', 'warehouse_id', 'allocation_date',
        'total_shipping_cost', 'allocation_method', 'total_value', 'total_allocated',
        'status', 'note', 'created_by', 'approved_by', 'approved_at'
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'total_shipping_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'total_allocated' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShippingAllocationItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeByStatus(Builder $query, $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouse(Builder $query, $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByMethod(Builder $query, $method): Builder
    {
        return $query->where('allocation_method', $method);
    }

    public static function generateCode(): string
    {
        $lastCode = self::whereYear('created_at', now()->year)
            ->orderBy('id', 'desc')
            ->value('code');
        
        if ($lastCode) {
            $number = (int) substr($lastCode, -4) + 1;
        } else {
            $number = 1;
        }
        
        return 'SA' . now()->format('y') . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function calculateAllocation(): void
    {
        $items = $this->items;
        $totalValue = $items->sum('total_value');
        $totalQuantity = $items->sum('quantity');
        $totalWeight = $items->sum('weight');
        $totalVolume = $items->sum('volume');

        foreach ($items as $item) {
            $allocatedCost = match($this->allocation_method) {
                'value' => $totalValue > 0 ? ($item->total_value / $totalValue) * $this->total_shipping_cost : 0,
                'quantity' => $totalQuantity > 0 ? ($item->quantity / $totalQuantity) * $this->total_shipping_cost : 0,
                'weight' => $totalWeight > 0 ? ($item->weight / $totalWeight) * $this->total_shipping_cost : 0,
                'volume' => $totalVolume > 0 ? ($item->volume / $totalVolume) * $this->total_shipping_cost : 0,
                default => 0
            };
            
            $item->allocated_cost = $allocatedCost;
            $item->allocated_cost_per_unit = $item->quantity > 0 ? $allocatedCost / $item->quantity : 0;
            $item->save();
        }

        $this->total_value = $totalValue;
        $this->total_allocated = $items->sum('allocated_cost');
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Nháp',
            'approved' => 'Đã duyệt',
            'completed' => 'Hoàn thành',
            default => $this->status
        };
    }

    public function getMethodLabelAttribute(): string
    {
        return match($this->allocation_method) {
            'value' => 'Theo giá trị',
            'quantity' => 'Theo số lượng',
            'weight' => 'Theo trọng lượng',
            'volume' => 'Theo thể tích',
            default => $this->allocation_method
        };
    }
}
