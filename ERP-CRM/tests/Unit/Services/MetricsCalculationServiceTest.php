<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MetricsCalculationService;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetricsCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MetricsCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetricsCalculationService();
    }

    /**
     * Test revenue calculation with valid sales
     * Requirements: 1.1
     */
    public function test_calculate_revenue_returns_sum_of_sales_total(): void
    {
        $start = Carbon::parse('2024-01-01');
        $end = Carbon::parse('2024-01-31');

        // Create sales within date range
        Sale::factory()->create([
            'date' => '2024-01-15',
            'total' => 1000.00,
            'status' => 'completed'
        ]);
        Sale::factory()->create([
            'date' => '2024-01-20',
            'total' => 2000.00,
            'status' => 'completed'
        ]);

        $revenue = $this->service->calculateRevenue($start, $end);

        $this->assertEquals(3000.00, $revenue);
    }

    /**
     * Test revenue calculation excludes cancelled sales
     * Requirements: 1.1, 12.5
     */
    public function test_calculate_revenue_excludes_cancelled_sales(): void
    {
        $start = Carbon::parse('2024-01-01');
        $end = Carbon::parse('2024-01-31');

        Sale::factory()->create([
            'date' => '2024-01-15',
            'total' => 1000.00,
            'status' => 'completed'
        ]);
        Sale::factory()->create([
            'date' => '2024-01-20',
            'total' => 2000.00,
            'status' => 'cancelled'
        ]);

        $revenue = $this->service->calculateRevenue($start, $end);

        $this->assertEquals(1000.00, $revenue);
    }

    /**
     * Test revenue calculation with no sales
     * Requirements: 1.1, 12.2
     */
    public function test_calculate_revenue_returns_zero_when_no_sales(): void
    {
        $start = Carbon::parse('2024-01-01');
        $end = Carbon::parse('2024-01-31');

        $revenue = $this->service->calculateRevenue($start, $end);

        $this->assertEquals(0.0, $revenue);
    }

    /**
     * Test profit calculation with valid sales
     * Requirements: 1.2
     */
    public function test_calculate_profit_returns_sum_of_margins(): void
    {
        $start = Carbon::parse('2024-01-01');
        $end = Carbon::parse('2024-01-31');

        Sale::factory()->create([
            'date' => '2024-01-15',
            'total' => 1000.00,
            'margin' => 300.00,
            'status' => 'completed'
        ]);
        Sale::factory()->create([
            'date' => '2024-01-20',
            'total' => 2000.00,
            'margin' => 600.00,
            'status' => 'completed'
        ]);

        $profit = $this->service->calculateProfit($start, $end);

        $this->assertEquals(900.00, $profit);
    }

    /**
     * Test profit calculation excludes cancelled sales
     * Requirements: 1.2, 12.5
     */
    public function test_calculate_profit_excludes_cancelled_sales(): void
    {
        $start = Carbon::parse('2024-01-01');
        $end = Carbon::parse('2024-01-31');

        Sale::factory()->create([
            'date' => '2024-01-15',
            'margin' => 300.00,
            'status' => 'completed'
        ]);
        Sale::factory()->create([
            'date' => '2024-01-20',
            'margin' => 600.00,
            'status' => 'cancelled'
        ]);

        $profit = $this->service->calculateProfit($start, $end);

        $this->assertEquals(300.00, $profit);
    }

    /**
     * Test profit margin calculation with positive values
     * Requirements: 1.3
     */
    public function test_calculate_profit_margin_returns_correct_percentage(): void
    {
        $revenue = 1000.00;
        $profit = 300.00;

        $margin = $this->service->calculateProfitMargin($revenue, $profit);

        $this->assertEquals(30.0, $margin);
    }

    /**
     * Test profit margin calculation handles zero revenue
     * Requirements: 1.3, 12.5
     */
    public function test_calculate_profit_margin_returns_zero_when_revenue_is_zero(): void
    {
        $revenue = 0.0;
        $profit = 300.00;

        $margin = $this->service->calculateProfitMargin($revenue, $profit);

        $this->assertEquals(0.0, $margin);
    }

    /**
     * Test profit margin calculation handles negative revenue
     * Requirements: 1.3, 12.5
     */
    public function test_calculate_profit_margin_returns_zero_when_revenue_is_negative(): void
    {
        $revenue = -1000.00;
        $profit = 300.00;

        $margin = $this->service->calculateProfitMargin($revenue, $profit);

        $this->assertEquals(0.0, $margin);
    }

    /**
     * Test profit margin calculation with negative profit
     * Requirements: 1.3
     */
    public function test_calculate_profit_margin_handles_negative_profit(): void
    {
        $revenue = 1000.00;
        $profit = -200.00;

        $margin = $this->service->calculateProfitMargin($revenue, $profit);

        $this->assertEquals(-20.0, $margin);
    }

    /**
     * Test revenue calculation respects date boundaries
     * Requirements: 1.1
     */
    public function test_calculate_revenue_respects_date_boundaries(): void
    {
        $start = Carbon::parse('2024-01-10');
        $end = Carbon::parse('2024-01-20');

        // Before range
        Sale::factory()->create([
            'date' => '2024-01-05',
            'total' => 1000.00,
            'status' => 'completed'
        ]);
        
        // Within range
        Sale::factory()->create([
            'date' => '2024-01-15',
            'total' => 2000.00,
            'status' => 'completed'
        ]);
        
        // After range
        Sale::factory()->create([
            'date' => '2024-01-25',
            'total' => 3000.00,
            'status' => 'completed'
        ]);

        $revenue = $this->service->calculateRevenue($start, $end);

        $this->assertEquals(2000.00, $revenue);
    }
}
