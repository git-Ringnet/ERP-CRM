<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test dashboard page loads successfully
     * Requirements: 6.1, 6.5
     */
    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
    }

    /**
     * Test dashboard displays summary counts
     * Requirements: 6.1
     */
    public function test_dashboard_displays_summary_counts(): void
    {
        // Create test data
        DB::table('customers')->insert([
            'code' => 'CUST001',
            'name' => 'Test Customer',
            'email' => 'test@customer.com',
            'phone' => '0123456789',
            'type' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('suppliers')->insert([
            'code' => 'SUPP001',
            'name' => 'Test Supplier',
            'email' => 'test@supplier.com',
            'phone' => '0123456789',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'code' => 'PROD001',
            'name' => 'Test Product',
            'unit' => 'pcs',
            'price' => 100,
            'cost' => 50,
            'management_type' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('totalCustomers', 1);
        $response->assertViewHas('totalSuppliers', 1);
        $response->assertViewHas('totalProducts', 1);
    }

    /**
     * Test dashboard root route redirects to dashboard
     * Requirements: 6.7
     */
    public function test_root_route_shows_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
    }
}
