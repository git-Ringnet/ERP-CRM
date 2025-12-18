<?php

namespace App\Services;

use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarrantyService
{
    /**
     * Calculate warranty end date
     * Requirements: 6.1
     */
    public function calculateWarrantyEndDate(?Carbon $startDate, ?int $months): ?Carbon
    {
        if (!$startDate || !$months || $months <= 0) {
            return null;
        }
        return $startDate->copy()->addMonths($months);
    }

    /**
     * Get warranty status
     * Requirements: 6.2, 6.3, 6.4
     */
    public function getWarrantyStatus(?Carbon $startDate, ?int $months): string
    {
        if (!$months || $months <= 0) {
            return SaleItem::WARRANTY_STATUS_NO_WARRANTY;
        }

        if (!$startDate) {
            return SaleItem::WARRANTY_STATUS_NO_WARRANTY;
        }

        $endDate = $this->calculateWarrantyEndDate($startDate, $months);
        if (!$endDate) {
            return SaleItem::WARRANTY_STATUS_NO_WARRANTY;
        }

        return now()->lte($endDate) 
            ? SaleItem::WARRANTY_STATUS_ACTIVE 
            : SaleItem::WARRANTY_STATUS_EXPIRED;
    }

    /**
     * Get days remaining in warranty
     */
    public function getDaysRemaining(?Carbon $startDate, ?int $months): ?int
    {
        $endDate = $this->calculateWarrantyEndDate($startDate, $months);
        if (!$endDate) {
            return null;
        }
        return (int) now()->diffInDays($endDate, false);
    }


    /**
     * Get warranty list with filters
     * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5
     */
    public function getWarrantyList(array $filters = []): LengthAwarePaginator
    {
        $query = SaleItem::query()
            ->select([
                'sale_items.*',
                'products.code as product_code',
                'sales.code as sale_code',
                'sales.date as sale_date',
                'sales.customer_name',
                'customers.phone as customer_phone',
            ])
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->whereNotNull('sale_items.warranty_months')
            ->where('sale_items.warranty_months', '>', 0);

        // Filter by warranty status
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            $now = now()->toDateString();
            
            if ($status === SaleItem::WARRANTY_STATUS_ACTIVE) {
                $query->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) >= ?', [$now]);
            } elseif ($status === SaleItem::WARRANTY_STATUS_EXPIRED) {
                $query->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) < ?', [$now]);
            }
        }

        // Filter by date range (warranty end date)
        if (!empty($filters['date_from'])) {
            $query->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) >= ?', [$filters['date_from']]);
        }
        if (!empty($filters['date_to'])) {
            $query->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) <= ?', [$filters['date_to']]);
        }

        // Search by serial or product code
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('products.code', 'like', "%{$search}%")
                  ->orWhere('sale_items.product_name', 'like', "%{$search}%")
                  ->orWhere('sales.code', 'like', "%{$search}%")
                  ->orWhere('sales.customer_name', 'like', "%{$search}%");
            });
        }

        // Filter by customer
        if (!empty($filters['customer_id'])) {
            $query->where('sales.customer_id', $filters['customer_id']);
        }

        // Filter by product
        if (!empty($filters['product_id'])) {
            $query->where('sale_items.product_id', $filters['product_id']);
        }

        return $query->orderBy('sale_items.warranty_start_date', 'desc')
                     ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get expiring warranties
     * Requirements: 4.1, 4.2
     */
    public function getExpiringWarranties(int $days = 30): Collection
    {
        $now = now()->toDateString();
        $futureDate = now()->addDays($days)->toDateString();

        return SaleItem::query()
            ->select([
                'sale_items.*',
                'products.code as product_code',
                'sales.code as sale_code',
                'sales.date as sale_date',
                'sales.customer_name',
                'customers.phone as customer_phone',
                DB::raw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) as warranty_end_date_calc'),
            ])
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->whereNotNull('sale_items.warranty_months')
            ->where('sale_items.warranty_months', '>', 0)
            ->whereNotNull('sale_items.warranty_start_date')
            ->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) >= ?', [$now])
            ->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) <= ?', [$futureDate])
            ->orderByRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) ASC')
            ->get();
    }
}
