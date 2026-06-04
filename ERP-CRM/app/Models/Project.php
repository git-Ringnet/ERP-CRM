<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Project extends Model
{
    use HasFactory, LogsActivity;
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
        'marketing_event_id',
        // Distributor
        'vendor_id',
        'distributor_am',
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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'net_to_tech_horizon' => 'decimal:2',
        'bom_file' => 'array',
    ];

    /**
     * Relationship with Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with Supplier (Vendor)
     */
    public function vendor()
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    /**
     * Relationship with Customer (Collaboration Partner)
     */
    public function collaborateCustomer()
    {
        return $this->belongsTo(Customer::class, 'collaborate_customer_id');
    }

    /**
     * Relationship with User (Manager)
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relationship with MarketingEvent
     */
    public function marketingEvent()
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }

    /**
     * Relationship with Sales
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Relationship with Opportunities
     */
    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
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

    /**
     * Calculate total revenue from sales
     * Uses sales total to account for discounts and tax
     */
    public function getTotalRevenueAttribute(): float
    {
        return $this->sales()->sum('total');
    }

    /**
     * Calculate total cost
     * Cost = Revenue - Profit (where Profit = Sum of Sales Margin)
     * This ensures consistency: Profit = Revenue - Cost
     */
    public function getTotalCostAttribute(): float
    {
        $revenue = $this->total_revenue;
        $profit = $this->sales()->sum('margin');

        return $revenue - $profit;
    }

    /**
     * Calculate profit
     */
    public function getProfitAttribute(): float
    {
        return $this->sales()->sum('margin');
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
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('eu_name_vi', 'like', "%{$search}%")
                ->orWhere('eu_name_en', 'like', "%{$search}%")
                ->orWhere('eu_tax_code', 'like', "%{$search}%")
                ->orWhere('collaborate_company', 'like', "%{$search}%");
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
