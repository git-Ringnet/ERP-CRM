<?php

namespace App\Services;

use App\Models\SaleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarrantyReportService
{
    /**
     * Get warranty summary report
     * Requirements: 5.1
     */
    public function getSummaryReport(array $filters = []): array
    {
        $now = now()->toDateString();
        $expiringDays = $filters['expiring_days'] ?? 30;
        $expiringDate = now()->addDays($expiringDays)->toDateString();

        $baseQuery = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id');

        // Apply date filters if provided
        if (!empty($filters['date_from'])) {
            $baseQuery->where('sales.date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $baseQuery->where('sales.date', '<=', $filters['date_to']);
        }

        // Total sold items
        $totalSold = (clone $baseQuery)->count();

        // Items with warranty
        $withWarranty = (clone $baseQuery)
            ->whereNotNull('sale_items.warranty_months')
            ->where('sale_items.warranty_months', '>', 0)
            ->count();

        // Active warranties
        $activeWarranties = (clone $baseQuery)
            ->whereNotNull('sale_items.warranty_months')
            ->where('sale_items.warranty_months', '>', 0)
            ->whereNotNull('sale_items.warranty_start_date')
            ->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) >= ?', [$now])
            ->count();

        // Expired warranties
        $expiredWarranties = (clone $baseQuery)
            ->whereNotNull('sale_items.warranty_months')
            ->where('sale_items.warranty_months', '>', 0)
            ->whereNotNull('sale_items.warranty_start_date')
            ->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) < ?', [$now])
            ->count();

        // Expiring soon
        $expiringSoon = (clone $baseQuery)
            ->whereNotNull('sale_items.warranty_months')
            ->where('sale_items.warranty_months', '>', 0)
            ->whereNotNull('sale_items.warranty_start_date')
            ->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) >= ?', [$now])
            ->whereRaw('DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) <= ?', [$expiringDate])
            ->count();

        // No warranty
        $noWarranty = $totalSold - $withWarranty;

        return [
            'total_sold' => $totalSold,
            'with_warranty' => $withWarranty,
            'no_warranty' => $noWarranty,
            'active' => $activeWarranties,
            'expired' => $expiredWarranties,
            'expiring_soon' => $expiringSoon,
            'expiring_days' => $expiringDays,
        ];
    }


    /**
     * Get warranty report grouped by customer
     * Requirements: 5.2
     */
    public function getReportByCustomer(array $filters = []): Collection
    {
        $now = now()->toDateString();

        $query = DB::table('sale_items')
            ->select([
                'customers.id as customer_id',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                DB::raw('COUNT(sale_items.id) as total_items'),
                DB::raw("SUM(CASE WHEN sale_items.warranty_months > 0 AND sale_items.warranty_start_date IS NOT NULL AND DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) >= '{$now}' THEN 1 ELSE 0 END) as active_count"),
                DB::raw("SUM(CASE WHEN sale_items.warranty_months > 0 AND sale_items.warranty_start_date IS NOT NULL AND DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) < '{$now}' THEN 1 ELSE 0 END) as expired_count"),
                DB::raw("SUM(CASE WHEN sale_items.warranty_months IS NULL OR sale_items.warranty_months = 0 THEN 1 ELSE 0 END) as no_warranty_count"),
            ])
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->groupBy('customers.id', 'customers.name', 'customers.phone');

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->where('sales.date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('sales.date', '<=', $filters['date_to']);
        }

        return $query->orderBy('customer_name')->get();
    }

    /**
     * Get warranty report grouped by product
     * Requirements: 5.3
     */
    public function getReportByProduct(array $filters = []): Collection
    {
        $now = now()->toDateString();

        $query = DB::table('sale_items')
            ->select([
                'products.id as product_id',
                'products.code as product_code',
                'products.name as product_name',
                'products.warranty_months as default_warranty',
                DB::raw('COUNT(sale_items.id) as total_sold'),
                DB::raw("SUM(CASE WHEN sale_items.warranty_months > 0 AND sale_items.warranty_start_date IS NOT NULL AND DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) >= '{$now}' THEN 1 ELSE 0 END) as active_count"),
                DB::raw("SUM(CASE WHEN sale_items.warranty_months > 0 AND sale_items.warranty_start_date IS NOT NULL AND DATE_ADD(sale_items.warranty_start_date, INTERVAL sale_items.warranty_months MONTH) < '{$now}' THEN 1 ELSE 0 END) as expired_count"),
                DB::raw("SUM(CASE WHEN sale_items.warranty_months IS NULL OR sale_items.warranty_months = 0 THEN 1 ELSE 0 END) as no_warranty_count"),
            ])
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->groupBy('products.id', 'products.code', 'products.name', 'products.warranty_months');

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->where('sales.date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('sales.date', '<=', $filters['date_to']);
        }

        return $query->orderBy('product_code')->get();
    }
}
