<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    /**
     * Relationship with Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with User (Manager)
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relationship with Sales
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Relationship with SaleItems
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Relationship with Exports
     */
    public function exports()
    {
        return $this->hasMany(Export::class, 'project_id');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
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
        return match($this->status) {
            'planning' => 'bg-yellow-100 text-yellow-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'on_hold' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Calculate total revenue from sales
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->saleItems()->sum('total');
    }

    /**
     * Calculate total cost from sale items
     */
    public function getTotalCostAttribute(): float
    {
        return $this->saleItems()->sum('cost_total');
    }

    /**
     * Calculate profit
     */
    public function getProfitAttribute(): float
    {
        return $this->total_revenue - $this->total_cost;
    }

    /**
     * Calculate profit percent
     */
    public function getProfitPercentAttribute(): float
    {
        if ($this->total_revenue <= 0) {
            return 0;
        }
        return ($this->profit / $this->total_revenue) * 100;
    }

    /**
     * Get total debt for this project
     */
    public function getTotalDebtAttribute(): float
    {
        return $this->sales()->sum('debt_amount');
    }

    /**
     * Get total export value (vật tư đã xuất)
     */
    public function getTotalExportValueAttribute(): float
    {
        return $this->exports()
            ->with('items')
            ->get()
            ->sum(function ($export) {
                return $export->items->sum(function ($item) {
                    return $item->quantity * $item->cost_usd * 25000; // Convert USD to VND
                });
            });
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
              ->orWhere('customer_name', 'like', "%{$search}%");
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
}
