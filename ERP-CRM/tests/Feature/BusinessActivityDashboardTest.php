<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Carbon\Carbon;

class BusinessActivityDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user
        $this->admin = User::factory()->create();
        
        // Mock permission checks
        Gate::define('viewDashboard', fn() => true);
        Gate::define('exportReports', fn() => true);
    }

    /**
     * Test revenue calculation logic
     */
    public function test_revenue_calculation_logic(): void
    {
        $customer = Customer::factory()->create();

        // Valid sales: 15M
        Sale::factory()->create(['customer_id' => $customer->id, 'total' => 10000000, 'status' => 'completed', 'date' => now()]);
        Sale::factory()->create(['customer_id' => $customer->id, 'total' => 5000000, 'status' => 'pending', 'date' => now()]);
        
        // Cancelled: ignored
        Sale::factory()->create(['customer_id' => $customer->id, 'total' => 99000000, 'status' => 'cancelled', 'date' => now()]);

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->get(route('dashboard.business-activity', ['period_type' => 'today']));

        $response->assertStatus(200);
        $this->assertEquals(15000000, $response->viewData('metrics')['revenue']['current']);
    }

    /**
     * Test profit calculation with losses
     */
    public function test_profit_calculation_with_losses(): void
    {
        $customer = Customer::factory()->create();

        Sale::factory()->create(['customer_id' => $customer->id, 'total' => 1000000, 'margin' => 300000, 'status' => 'completed', 'date' => now()]);
        Sale::factory()->create(['customer_id' => $customer->id, 'total' => 1000000, 'margin' => -100000, 'status' => 'completed', 'date' => now()]);

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->get(route('dashboard.business-activity', ['period_type' => 'today']));

        $response->assertStatus(200);
        $this->assertEquals(200000, $response->viewData('metrics')['profit']['current']);
    }

    /**
     * Test Purchase Cost calculation
     */
    public function test_purchase_cost_calculation(): void
    {
        DB::table('suppliers')->insert([
            'code' => 'SUPP01',
            'name' => 'Supplier A',
            'phone' => '123',
            'email' => 'supplier@example.com',
            'tax_code' => 'TAX123',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $supplierId = DB::getPdo()->lastInsertId();

        DB::table('purchase_orders')->insert([
            ['code' => 'PO-01', 'supplier_id' => $supplierId, 'total' => 20000000, 'status' => 'received', 'order_date' => now()->format('Y-m-d'), 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'PO-02', 'supplier_id' => $supplierId, 'total' => 50000000, 'status' => 'cancelled', 'order_date' => now()->format('Y-m-d'), 'created_at' => now(), 'updated_at' => now()]
        ]);

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->get(route('dashboard.business-activity', ['period_type' => 'today']));

        $response->assertStatus(200);
        $this->assertEquals(20000000, $response->viewData('metrics')['purchase_cost']['current']);
    }

    /**
     * Test Period filtering
     */
    public function test_dashboard_period_filtering(): void
    {
        $customer = Customer::factory()->create();

        // Sale Today
        Sale::factory()->create(['customer_id' => $customer->id, 'total' => 100, 'status' => 'completed', 'date' => now()]);

        // Sale Last Month
        Sale::factory()->create(['customer_id' => $customer->id, 'total' => 9999, 'status' => 'completed', 'date' => now()->subMonths(1)]);

        // Test Today
        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->get(route('dashboard.business-activity', ['period_type' => 'today']));
        $this->assertEquals(100, $response->viewData('metrics')['revenue']['current']);

        // Test Custom Range (Today only)
        $today = now()->format('Y-m-d');
        $response = $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->get(route('dashboard.business-activity', ['start_date' => $today, 'end_date' => $today]));
        $this->assertEquals(100, $response->viewData('metrics')['revenue']['current']);
    }
}
