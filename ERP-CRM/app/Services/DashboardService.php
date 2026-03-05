<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    /**
     * Cache TTL constants (in seconds)
     */
    private const METRICS_CACHE_TTL = 900; // 15 minutes
    private const TOP_PERFORMERS_CACHE_TTL = 1800; // 30 minutes
    private const CHART_DATA_CACHE_TTL = 900; // 15 minutes

    /**
     * Create a new DashboardService instance.
     *
     * @param MetricsCalculationService $metricsService
     * @param CacheService $cacheService
     */
    public function __construct(
        private MetricsCalculationService $metricsService,
        private CacheService $cacheService
    ) {}

    /**
     * Get complete dashboard data with caching
     *
     * @param array $filters Filter parameters (period_type, start_date, end_date)
     * @return array Complete dashboard data
     */
    public function getDashboardData(array $filters): array
    {
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);
        $periodType = $filters['period_type'] ?? 'custom';

        // Determine comparison period
        $comparisonDates = $this->determineComparisonPeriod($startDate, $endDate, $periodType);

        // Get metrics with growth rates
        $metrics = $this->getMetrics($startDate, $endDate, $comparisonDates['start'], $comparisonDates['end']);

        // Get chart data
        $chartData = $this->getChartData($startDate, $endDate);

        // Get top performers
        $topPerformers = $this->getTopPerformers($startDate, $endDate);

        // Get sales analysis
        $salesAnalysis = $this->getSalesAnalysis($startDate, $endDate);

        // Get purchase analysis
        $purchaseAnalysis = $this->getPurchaseAnalysis($startDate, $endDate);

        // Get inventory analysis
        $inventoryAnalysis = $this->getInventoryAnalysis($startDate, $endDate);

        return [
            'metrics' => $metrics,
            'charts' => $chartData,
            'sales_analysis' => $salesAnalysis,
            'purchase_analysis' => $purchaseAnalysis,
            'inventory_analysis' => $inventoryAnalysis,
            'top_performers' => $topPerformers,
            'filters' => [
                'period_type' => $periodType,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'comparison_start' => $comparisonDates['start']->format('Y-m-d'),
                'comparison_end' => $comparisonDates['end']->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Determine comparison period based on selected period
     *
     * @param Carbon $startDate Current period start date
     * @param Carbon $endDate Current period end date
     * @param string $periodType Period type (today, week, month, quarter, year, custom)
     * @return array Comparison period dates ['start' => Carbon, 'end' => Carbon]
     */
    public function determineComparisonPeriod(Carbon $startDate, Carbon $endDate, string $periodType): array
    {
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        return [
            'start' => $startDate->copy()->subDays($daysDiff),
            'end' => $endDate->copy()->subDays($daysDiff),
        ];
    }

    /**
     * Get metrics with growth rates
     *
     * @param Carbon $startDate Current period start date
     * @param Carbon $endDate Current period end date
     * @param Carbon $comparisonStart Comparison period start date
     * @param Carbon $comparisonEnd Comparison period end date
     * @return array Metrics with growth rates
     */
    public function getMetrics(Carbon $startDate, Carbon $endDate, Carbon $comparisonStart, Carbon $comparisonEnd): array
    {
        $cacheKey = $this->generateCacheKey('metrics', $startDate, $endDate);

        return $this->cacheService->remember($cacheKey, self::METRICS_CACHE_TTL, function () use ($startDate, $endDate, $comparisonStart, $comparisonEnd) {
            // Current period metrics
            $currentRevenue = $this->metricsService->calculateRevenue($startDate, $endDate);
            $currentProfit = $this->metricsService->calculateProfit($startDate, $endDate);
            $currentPurchaseCost = $this->metricsService->calculatePurchaseCost($startDate, $endDate);
            $currentSalesStats = $this->metricsService->getSalesStats($startDate, $endDate);
            $currentPurchaseStats = $this->metricsService->getPurchaseOrderStats($startDate, $endDate);

            // Comparison period metrics
            $previousRevenue = $this->metricsService->calculateRevenue($comparisonStart, $comparisonEnd);
            $previousProfit = $this->metricsService->calculateProfit($comparisonStart, $comparisonEnd);
            $previousPurchaseCost = $this->metricsService->calculatePurchaseCost($comparisonStart, $comparisonEnd);
            $previousSalesStats = $this->metricsService->getSalesStats($comparisonStart, $comparisonEnd);

            // Calculate growth rates
            $revenueGrowth = $this->metricsService->calculateGrowthRate($currentRevenue, $previousRevenue);
            $profitGrowth = $this->metricsService->calculateGrowthRate($currentProfit, $previousProfit);
            $purchaseCostGrowth = $this->metricsService->calculateGrowthRate($currentPurchaseCost, $previousPurchaseCost);
            $salesCountGrowth = $this->metricsService->calculateGrowthRate(
                $currentSalesStats['total_count'],
                $previousSalesStats['total_count']
            );
            $purchaseCountGrowth = $this->metricsService->calculateGrowthRate(
                $currentPurchaseStats['total_count'],
                $previousSalesStats['total_count']
            );

            // Calculate profit margin
            $profitMargin = $this->metricsService->calculateProfitMargin($currentRevenue, $currentProfit);

            // Get inventory metrics
            $inventoryValue = $this->metricsService->calculateInventoryValue();
            $inventoryTurnover = $this->metricsService->calculateInventoryTurnover($startDate, $endDate);

            return [
                'revenue' => [
                    'current' => $currentRevenue,
                    'previous' => $previousRevenue,
                    'growth_rate' => $revenueGrowth,
                    'trend' => $this->determineTrend($revenueGrowth),
                ],
                'profit' => [
                    'current' => $currentProfit,
                    'previous' => $previousProfit,
                    'growth_rate' => $profitGrowth,
                    'trend' => $this->determineTrend($profitGrowth),
                ],
                'profit_margin' => $profitMargin,
                'purchase_cost' => [
                    'current' => $currentPurchaseCost,
                    'previous' => $previousPurchaseCost,
                    'growth_rate' => $purchaseCostGrowth,
                    'trend' => $this->determineTrend($purchaseCostGrowth),
                ],
                'inventory_value' => $inventoryValue,
                'inventory_turnover' => $inventoryTurnover,
                'sales_count' => [
                    'current' => $currentSalesStats['total_count'],
                    'previous' => $previousSalesStats['total_count'],
                    'growth_rate' => $salesCountGrowth,
                ],
                'purchase_orders_count' => [
                    'current' => $currentPurchaseStats['total_count'],
                    'previous' => $previousSalesStats['total_count'],
                    'growth_rate' => $purchaseCountGrowth,
                ],
            ];
        });
    }

    /**
     * Get chart data formatted for Chart.js
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Chart data
     */
    public function getChartData(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = $this->generateCacheKey('chart_data', $startDate, $endDate);

        return $this->cacheService->remember($cacheKey, self::CHART_DATA_CACHE_TTL, function () use ($startDate, $endDate) {
            $revenueTrend = $this->metricsService->getRevenueTrend($startDate, $endDate);
            $profitTrend = $this->metricsService->getProfitTrend($startDate, $endDate);

            return [
                'revenue_profit_trend' => [
                    'labels' => $revenueTrend['labels'],
                    'revenue' => $revenueTrend['values'],
                    'profit' => $profitTrend['values'],
                ],
            ];
        });
    }

    /**
     * Get top performers data
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Top performers data
     */
    public function getTopPerformers(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = $this->generateCacheKey('top_performers', $startDate, $endDate);

        return $this->cacheService->remember($cacheKey, self::TOP_PERFORMERS_CACHE_TTL, function () use ($startDate, $endDate) {
            return [
                'top_products' => $this->metricsService->getTopProductsByRevenue($startDate, $endDate, 10),
                'top_customers' => $this->metricsService->getTopCustomersByRevenue($startDate, $endDate, 10),
                'top_suppliers' => $this->metricsService->getTopSuppliersByCost($startDate, $endDate, 10),
            ];
        });
    }

    /**
     * Get sales analysis data
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Sales analysis data
     */
    private function getSalesAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $stats = $this->metricsService->getSalesStats($startDate, $endDate);
        $averageValue = $this->metricsService->getAverageSaleValue($startDate, $endDate);
        $paymentStatusDistribution = $this->metricsService->getSalesPaymentStatusDistribution($startDate, $endDate);

        return [
            'completed_count' => $stats['completed_count'],
            'pending_count' => $stats['pending_count'],
            'average_value' => $averageValue,
            'payment_status_distribution' => $paymentStatusDistribution,
        ];
    }

    /**
     * Get purchase analysis data
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Purchase analysis data
     */
    private function getPurchaseAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $stats = $this->metricsService->getPurchaseOrderStats($startDate, $endDate);
        $averageValue = $this->metricsService->getAveragePurchaseOrderValue($startDate, $endDate);
        $statusDistribution = $this->metricsService->getPurchaseOrderStatusDistribution($startDate, $endDate);

        return [
            'total_count' => $stats['total_count'],
            'pending_count' => $stats['pending_count'],
            'completed_count' => $stats['completed_count'],
            'average_value' => $averageValue,
            'status_distribution' => $statusDistribution,
        ];
    }

    /**
     * Get inventory analysis data
     *
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Inventory analysis data
     */
    private function getInventoryAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = 'dashboard:inventory_analysis';

        return $this->cacheService->remember($cacheKey, self::METRICS_CACHE_TTL, function () use ($startDate, $endDate) {
            $stats = $this->metricsService->getInventoryStats();
            $turnoverRatio = $this->metricsService->calculateInventoryTurnover($startDate, $endDate);
            
            // Add turnover ratio to stats
            $stats['turnover_ratio'] = $turnoverRatio;
            
            return $stats;
        });
    }

    /**
     * Clear all dashboard cache
     *
     * @param array $filters Filter parameters
     * @return void
     */
    public function clearCache(array $filters): void
    {
        try {
            $startDate = Carbon::parse($filters['start_date']);
            $endDate = Carbon::parse($filters['end_date']);

            $keys = [
                $this->generateCacheKey('metrics', $startDate, $endDate),
                $this->generateCacheKey('chart_data', $startDate, $endDate),
                $this->generateCacheKey('top_performers', $startDate, $endDate),
                'dashboard:inventory_analysis',
            ];

            $this->cacheService->forgetMany($keys);

            Log::info('Dashboard cache cleared', ['filters' => $filters]);
        } catch (\Exception $e) {
            Log::warning('Failed to clear dashboard cache', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);
        }
    }

    /**
     * Generate cache key for dashboard data
     *
     * @param string $type Cache type (metrics, chart_data, top_performers)
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return string Cache key
     */
    private function generateCacheKey(string $type, Carbon $startDate, Carbon $endDate): string
    {
        return sprintf(
            'dashboard:%s:%s:%s',
            $type,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    /**
     * Determine trend direction from growth rate
     *
     * @param float|null $growthRate Growth rate percentage
     * @return string Trend direction (up, down, neutral)
     */
    private function determineTrend(?float $growthRate): string
    {
        if ($growthRate === null) {
            return 'neutral';
        }

        if ($growthRate > 0) {
            return 'up';
        } elseif ($growthRate < 0) {
            return 'down';
        }

        return 'neutral';
    }
}
