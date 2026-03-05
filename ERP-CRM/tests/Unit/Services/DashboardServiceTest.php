<?php

namespace Tests\Unit\Services;

use App\Models\Sale;
use App\Models\PurchaseOrder;
use App\Models\Inventory;
use App\Services\DashboardService;
use App\Services\MetricsCalculationService;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(DashboardService::class);
    }

    /** @test */
    public function it_can_determine_comparison_period_correctly()
    {
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-31');

        $comparisonPeriod = $this->dashboardService->determineComparisonPeriod($startDate, $endDate, 'month');

        $this->assertEquals('2023-12-01', $comparisonPeriod['start']->format('Y-m-d'));
        $this->assertEquals('2023-12-31', $comparisonPeriod['end']->format('Y-m-d'));
    }

    /** @test */
    public function it_can_get_dashboard_data_with_all_sections()
    {
        // Create test data
        Sale::factory()->count(5)->create([
            'date' => Carbon::now(),
            'status' => 'completed',
        ]);

        $filters = [
            'period_type' => 'today',
            'start_date' => Carbon::now()->startOfDay()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfDay()->format('Y-m-d'),
        ];

        $data = $this->dashboardService->getDashboardData($filters);

        // Assert structure
        $this->assertArrayHasKey('metrics', $data);
        $this->assertArrayHasKey('charts', $data);
        $this->assertArrayHasKey('sales_analysis', $data);
        $this->assertArrayHasKey('purchase_analysis', $data);
        $this->assertArrayHasKey('inventory_analysis', $data);
        $this->assertArrayHasKey('top_performers', $data);
        $this->assertArrayHasKey('filters', $data);

        // Assert metrics structure
        $this->assertArrayHasKey('revenue', $data['metrics']);
        $this->assertArrayHasKey('profit', $data['metrics']);
        $this->assertArrayHasKey('profit_margin', $data['metrics']);
        $this->assertArrayHasKey('purchase_cost', $data['metrics']);
        $this->assertArrayHasKey('inventory_value', $data['metrics']);
        $this->assertArrayHasKey('inventory_turnover', $data['metrics']);

        // Assert each metric has required fields
        $this->assertArrayHasKey('current', $data['metrics']['revenue']);
        $this->assertArrayHasKey('previous', $data['metrics']['revenue']);
        $this->assertArrayHasKey('growth_rate', $data['metrics']['revenue']);
        $this->assertArrayHasKey('trend', $data['metrics']['revenue']);
    }

    /** @test */
    public function it_can_get_metrics_with_growth_rates()
    {
        // Create current period sales
        Sale::factory()->count(5)->create([
            'date' => Carbon::now(),
            'status' => 'completed',
            'total' => 1000,
            'margin' => 300,
        ]);

        // Create previous period sales
        Sale::factory()->count(3)->create([
            'date' => Carbon::now()->subDays(1),
            'status' => 'completed',
            'total' => 800,
            'margin' => 200,
        ]);

        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $comparisonStart = Carbon::now()->subDays(1)->startOfDay();
        $comparisonEnd = Carbon::now()->subDays(1)->endOfDay();

        $metrics = $this->dashboardService->getMetrics($startDate, $endDate, $comparisonStart, $comparisonEnd);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('revenue', $metrics);
        $this->assertArrayHasKey('profit', $metrics);
        
        // Check revenue has growth rate
        $this->assertArrayHasKey('growth_rate', $metrics['revenue']);
        $this->assertArrayHasKey('trend', $metrics['revenue']);
    }

    /** @test */
    public function it_can_get_chart_data()
    {
        Sale::factory()->count(5)->create([
            'date' => Carbon::now(),
            'status' => 'completed',
        ]);

        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $chartData = $this->dashboardService->getChartData($startDate, $endDate);

        $this->assertArrayHasKey('revenue_profit_trend', $chartData);
        $this->assertArrayHasKey('labels', $chartData['revenue_profit_trend']);
        $this->assertArrayHasKey('revenue', $chartData['revenue_profit_trend']);
        $this->assertArrayHasKey('profit', $chartData['revenue_profit_trend']);
    }

    /** @test */
    public function it_can_get_top_performers()
    {
        Sale::factory()->count(5)->create([
            'date' => Carbon::now(),
            'status' => 'completed',
        ]);

        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $topPerformers = $this->dashboardService->getTopPerformers($startDate, $endDate);

        $this->assertArrayHasKey('top_products', $topPerformers);
        $this->assertArrayHasKey('top_customers', $topPerformers);
        $this->assertArrayHasKey('top_suppliers', $topPerformers);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        $filters = [
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ];

        // This should not throw an exception
        $this->dashboardService->clearCache($filters);

        $this->assertTrue(true);
    }
}
