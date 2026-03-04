<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\PurchaseOrder;
use App\Models\Inventory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MetricsCalculationService
{
    /**
     * Calculate total revenue from sales within a date range
     * Excludes cancelled transactions
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return float Total revenue
     */
    public function calculateRevenue(Carbon $start, Carbon $end): float
    {
        return Sale::whereBetween('date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->sum('total');
    }

    /**
     * Calculate total profit from sales within a date range
     * Excludes cancelled transactions
     * Profit = margin (revenue - cost of goods sold - expenses)
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return float Total profit
     */
    public function calculateProfit(Carbon $start, Carbon $end): float
    {
        return Sale::whereBetween('date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->sum('margin');
    }

    /**
     * Calculate profit margin percentage
     * Handles division by zero by returning 0
     *
     * @param float $revenue Total revenue
     * @param float $profit Total profit
     * @return float Profit margin percentage
     */
    public function calculateProfitMargin(float $revenue, float $profit): float
    {
        if ($revenue <= 0) {
            return 0.0;
        }

        return ($profit / $revenue) * 100;
    }

    /**
     * Calculate total purchase cost from purchase orders within a date range
     * Excludes cancelled orders
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return float Total purchase cost
     */
    public function calculatePurchaseCost(Carbon $start, Carbon $end): float
    {
        return PurchaseOrder::whereBetween('order_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->sum('total');
    }

    /**
     * Get sales statistics including counts by status
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array Sales statistics
     */
    public function getSalesStats(Carbon $start, Carbon $end): array
    {
        $query = Sale::whereBetween('date', [$start, $end])
            ->where('status', '!=', 'cancelled');

        return [
            'total_count' => $query->count(),
            'completed_count' => (clone $query)->where('status', 'completed')->count(),
            'pending_count' => (clone $query)->whereIn('status', ['pending', 'approved', 'shipping'])->count(),
        ];
    }

    /**
     * Get purchase order statistics including counts by status
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array Purchase order statistics
     */
    public function getPurchaseOrderStats(Carbon $start, Carbon $end): array
    {
        $query = PurchaseOrder::whereBetween('order_date', [$start, $end])
            ->where('status', '!=', 'cancelled');

        return [
            'total_count' => $query->count(),
            'pending_count' => (clone $query)->whereIn('status', ['draft', 'pending_approval', 'approved', 'sent', 'confirmed'])->count(),
            'completed_count' => (clone $query)->whereIn('status', ['received', 'partial_received'])->count(),
        ];
    }

    /**
     * Calculate total inventory value across all warehouses
     * Value = stock * avg_cost for each inventory record
     *
     * @return float Total inventory value
     */
    public function calculateInventoryValue(): float
    {
        return Inventory::query()
            ->selectRaw('SUM(stock * avg_cost) as total_value')
            ->value('total_value') ?? 0.0;
    }

    /**
     * Calculate inventory turnover ratio
     * Turnover = Cost of Goods Sold / Average Inventory Value
     * Handles division by zero by returning 0
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return float Inventory turnover ratio
     */
    public function calculateInventoryTurnover(Carbon $start, Carbon $end): float
    {
        // Calculate cost of goods sold from sale items
        $costOfGoodsSold = \DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.date', [$start, $end])
            ->where('sales.status', '!=', 'cancelled')
            ->sum('sale_items.cost_total');

        // Get current inventory value as average (simplified approach)
        // In a more sophisticated system, you'd calculate average inventory over the period
        $averageInventoryValue = $this->calculateInventoryValue();

        if ($averageInventoryValue <= 0 || $costOfGoodsSold <= 0) {
            return 0.0;
        }

        return $costOfGoodsSold / $averageInventoryValue;
    }

    /**
     * Get inventory statistics
     *
     * @return array Inventory statistics
     */
    public function getInventoryStats(): array
    {
        $inventories = Inventory::with('product', 'warehouse')->get();

        // Calculate basic stats
        $totalValue = $inventories->sum(fn($inv) => $inv->stock * $inv->avg_cost);
        $uniqueProducts = $inventories->pluck('product_id')->unique()->count();
        $totalQuantity = $inventories->sum('stock');
        $lowStockCount = $inventories->filter(fn($inv) => $inv->stock < $inv->min_stock)->count();
        $overstockCount = $inventories->filter(fn($inv) => $inv->min_stock > 0 && $inv->stock > ($inv->min_stock * 3))->count();

        // Get value by warehouse
        $valueByWarehouse = $inventories
            ->groupBy('warehouse_id')
            ->map(function ($items, $warehouseId) {
                $warehouse = $items->first()->warehouse;
                return [
                    'warehouse_id' => $warehouseId,
                    'warehouse_name' => $warehouse ? $warehouse->name : 'N/A',
                    'total_value' => $items->sum(fn($inv) => $inv->stock * $inv->avg_cost)
                ];
            })
            ->values()
            ->sortByDesc('total_value')
            ->values()
            ->toArray();

        // Get top products by quantity
        $topByQuantity = $inventories
            ->groupBy('product_id')
            ->map(function ($items) {
                $product = $items->first()->product;
                return (object)[
                    'product_id' => $items->first()->product_id,
                    'product_name' => $product ? $product->name : 'N/A',
                    'total_stock' => $items->sum('stock')
                ];
            })
            ->sortByDesc('total_stock')
            ->take(10)
            ->values();

        // Get top products by value
        $topByValue = $inventories
            ->groupBy('product_id')
            ->map(function ($items) {
                $product = $items->first()->product;
                $totalStock = $items->sum('stock');
                $totalValue = $items->sum(fn($inv) => $inv->stock * $inv->avg_cost);
                return (object)[
                    'product_id' => $items->first()->product_id,
                    'product_name' => $product ? $product->name : 'N/A',
                    'total_stock' => $totalStock,
                    'total_value' => $totalValue
                ];
            })
            ->sortByDesc('total_value')
            ->take(10)
            ->values();

        return [
            'total_value' => $totalValue,
            'unique_products' => $uniqueProducts,
            'total_quantity' => $totalQuantity,
            'low_stock_count' => $lowStockCount,
            'overstock_count' => $overstockCount,
            'turnover_ratio' => null, // Will be calculated separately with date range
            'value_by_warehouse' => $valueByWarehouse,
            'top_by_quantity' => $topByQuantity,
            'top_by_value' => $topByValue,
        ];
    }

    /**
     * Calculate growth rate percentage
     * Growth Rate = ((current - previous) / previous) * 100
     * Handles division by zero by returning null
     *
     * @param float $current Current period value
     * @param float $previous Previous period value
     * @return float|null Growth rate percentage or null if previous is zero
     */
    public function calculateGrowthRate(float $current, float $previous): ?float
    {
        if ($previous == 0) {
            return null;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Get top products by revenue within a date range
     * Returns products ordered by total revenue (descending)
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @param int $limit Number of top products to return
     * @return Collection Top products with revenue data
     */
    public function getTopProductsByRevenue(Carbon $start, Carbon $end, int $limit = 10): Collection
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.date', [$start, $end])
            ->where('sales.status', '!=', 'cancelled')
            ->select(
                'sale_items.product_id',
                'sale_items.product_name',
                DB::raw('SUM(sale_items.quantity) as quantity_sold'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top customers by revenue within a date range
     * Returns customers ordered by total revenue (descending)
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @param int $limit Number of top customers to return
     * @return Collection Top customers with revenue data
     */
    public function getTopCustomersByRevenue(Carbon $start, Carbon $end, int $limit = 10): Collection
    {
        return Sale::query()
            ->whereBetween('date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('customer_id')
            ->select(
                'customer_id',
                'customer_name',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('customer_id', 'customer_name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top suppliers by purchase cost within a date range
     * Returns suppliers ordered by total purchase cost (descending)
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @param int $limit Number of top suppliers to return
     * @return Collection Top suppliers with cost data
     */
    public function getTopSuppliersByCost(Carbon $start, Carbon $end, int $limit = 10): Collection
    {
        return PurchaseOrder::query()
            ->with('supplier')
            ->whereBetween('order_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('supplier_id')
            ->select(
                'supplier_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_cost')
            )
            ->groupBy('supplier_id')
            ->orderByDesc('total_cost')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return (object)[
                    'supplier_id' => $item->supplier_id,
                    'supplier_name' => $item->supplier->name ?? 'N/A',
                    'order_count' => $item->order_count,
                    'total_cost' => $item->total_cost,
                ];
            });
    }

    /**
     * Calculate average sale value within a date range
     * Handles division by zero by returning 0
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return float Average sale value
     */
    public function getAverageSaleValue(Carbon $start, Carbon $end): float
    {
        $sales = Sale::whereBetween('date', [$start, $end])
            ->where('status', '!=', 'cancelled');

        $count = $sales->count();
        
        if ($count == 0) {
            return 0.0;
        }

        $total = $sales->sum('total');
        
        return $total / $count;
    }

    /**
     * Calculate average purchase order value within a date range
     * Handles division by zero by returning 0
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return float Average purchase order value
     */
    public function getAveragePurchaseOrderValue(Carbon $start, Carbon $end): float
    {
        $purchaseOrders = PurchaseOrder::whereBetween('order_date', [$start, $end])
            ->where('status', '!=', 'cancelled');

        $count = $purchaseOrders->count();
        
        if ($count == 0) {
            return 0.0;
        }

        $total = $purchaseOrders->sum('total');
        
        return $total / $count;
    }

    /**
     * Get revenue trend data grouped by time period
     * Groups by hour, day, or week based on date range length
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array Trend data with labels and values
     */
    public function getRevenueTrend(Carbon $start, Carbon $end): array
    {
        $daysDiff = $start->diffInDays($end);
        
        // Determine grouping based on time period length
        if ($daysDiff <= 1) {
            // Group by hour for single day
            $groupBy = DB::raw('DATE_FORMAT(date, "%Y-%m-%d %H:00:00") as period');
            $format = 'Y-m-d H:00:00';
        } elseif ($daysDiff <= 31) {
            // Group by day for up to one month
            $groupBy = DB::raw('DATE(date) as period');
            $format = 'Y-m-d';
        } else {
            // Group by week for longer periods
            $groupBy = DB::raw('DATE_FORMAT(date, "%Y-%u") as period');
            $format = 'Y-W';
        }

        $data = Sale::whereBetween('date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->select(
                $groupBy,
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Fill missing periods with zero values
        $labels = [];
        $values = [];
        
        if ($daysDiff <= 1) {
            // Fill hours
            for ($i = 0; $i <= 23; $i++) {
                $period = $start->copy()->setHour($i)->format($format);
                $labels[] = $start->copy()->setHour($i)->format('H:00');
                $values[] = isset($data[$period]) ? (float) $data[$period]->revenue : 0;
            }
        } elseif ($daysDiff <= 31) {
            // Fill days
            $current = $start->copy();
            while ($current->lte($end)) {
                $period = $current->format($format);
                $labels[] = $current->format('d/m');
                $values[] = isset($data[$period]) ? (float) $data[$period]->revenue : 0;
                $current->addDay();
            }
        } else {
            // For weeks, use actual data without filling
            $labels = $data->pluck('period')->toArray();
            $values = $data->pluck('revenue')->map(fn($v) => (float) $v)->toArray();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Get profit trend data grouped by time period
     * Groups by hour, day, or week based on date range length
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array Trend data with labels and values
     */
    public function getProfitTrend(Carbon $start, Carbon $end): array
    {
        $daysDiff = $start->diffInDays($end);
        
        // Determine grouping based on time period length
        if ($daysDiff <= 1) {
            // Group by hour for single day
            $groupBy = DB::raw('DATE_FORMAT(date, "%Y-%m-%d %H:00:00") as period');
            $format = 'Y-m-d H:00:00';
        } elseif ($daysDiff <= 31) {
            // Group by day for up to one month
            $groupBy = DB::raw('DATE(date) as period');
            $format = 'Y-m-d';
        } else {
            // Group by week for longer periods
            $groupBy = DB::raw('DATE_FORMAT(date, "%Y-%u") as period');
            $format = 'Y-W';
        }

        $data = Sale::whereBetween('date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->select(
                $groupBy,
                DB::raw('SUM(total - cost) as profit')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Fill missing periods with zero values
        $labels = [];
        $values = [];
        
        if ($daysDiff <= 1) {
            // Fill hours
            for ($i = 0; $i <= 23; $i++) {
                $period = $start->copy()->setHour($i)->format($format);
                $labels[] = $start->copy()->setHour($i)->format('H:00');
                $values[] = isset($data[$period]) ? (float) $data[$period]->profit : 0;
            }
        } elseif ($daysDiff <= 31) {
            // Fill days
            $current = $start->copy();
            while ($current->lte($end)) {
                $period = $current->format($format);
                $labels[] = $current->format('d/m');
                $values[] = isset($data[$period]) ? (float) $data[$period]->profit : 0;
                $current->addDay();
            }
        } else {
            // For weeks, use actual data without filling
            $labels = $data->pluck('period')->toArray();
            $values = $data->pluck('profit')->map(fn($v) => (float) $v)->toArray();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Get sales distribution by payment status
     * Returns count of sales for each payment status
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array Distribution data with status labels and counts
     */
    public function getSalesPaymentStatusDistribution(Carbon $start, Carbon $end): array
    {
        $data = Sale::whereBetween('date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->select('payment_status', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_status')
            ->get();

        // Ensure all possible statuses are included and return as indexed array
        $allStatuses = ['unpaid', 'partial', 'paid'];
        $distribution = [];

        foreach ($allStatuses as $status) {
            $item = $data->firstWhere('payment_status', $status);
            $count = $item ? (int) $item->count : 0;
            
            // Only include statuses that have data
            if ($count > 0) {
                $distribution[] = [
                    'payment_status' => $status,
                    'count' => $count
                ];
            }
        }

        return $distribution;
    }

    /**
     * Get purchase orders distribution by order status
     * Returns count of purchase orders for each status
     *
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return array Distribution data with status labels and counts
     */
    public function getPurchaseOrderStatusDistribution(Carbon $start, Carbon $end): array
    {
        $data = PurchaseOrder::whereBetween('order_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // Ensure all possible statuses are included and return as indexed array
        $allStatuses = ['draft', 'pending_approval', 'approved', 'sent', 'confirmed', 'shipping', 'partial_received', 'received'];
        $distribution = [];

        foreach ($allStatuses as $status) {
            $item = $data->firstWhere('status', $status);
            $count = $item ? (int) $item->count : 0;
            
            // Only include statuses that have data
            if ($count > 0) {
                $distribution[] = [
                    'status' => $status,
                    'count' => $count
                ];
            }
        }

        return $distribution;
    }
}
