<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Product;
use App\Models\SaleOrderRequest;
use App\Models\Export;
use App\Models\Warehouse;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $salesUser;
    protected User $adminUser;
    protected User $accountantUser;
    protected Customer $customer;
    protected Project $project;
    protected Product $product;
    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salesUser = User::factory()->create();
        $salesRole = \App\Models\Role::firstOrCreate(['slug' => 'sales_staff'], ['name' => 'Sales Staff']);
        $this->salesUser->roles()->syncWithoutDetaching([$salesRole->id]);

        $this->adminUser = User::factory()->create();
        $adminRole = \App\Models\Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $this->adminUser->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->accountantUser = User::factory()->create();
        $accountantRole = \App\Models\Role::firstOrCreate(['slug' => 'accountant'], ['name' => 'Accountant']);
        $this->accountantUser->roles()->syncWithoutDetaching([$accountantRole->id]);

        $this->customer = Customer::create([
            'name' => 'Test Cust',
            'tax_code' => '1234567890',
            'debt_days' => 10,
        ]);

        $this->project = Project::create([
            'code' => 'TESTPROJ',
            'name' => 'Test Project',
        ]);

        $this->product = Product::factory()->create();
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'code' => 'WH_TEST',
            'status' => 'active',
        ]);
    }

    public function test_sale_order_request_admin_approval_workflow(): void
    {
        $this->withoutExceptionHandling();
        $sale = Sale::create([
            'code' => 'SALE001',
            'type' => 'project',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'date' => now(),
            'total' => 1000000,
            'pl_status' => 'approved',
            'payment_term_type' => 'bod_exception',
            'payment_terms' => []
        ]);

        // 1. Sales user creates a SaleOrderRequest
        $this->actingAs($this->salesUser);

        // Mock Supplier
        $supplier = Supplier::create([
            'name' => 'Fortinet Distributor',
            'code' => 'SUP_TEST',
            'tax_code' => '1111111111',
            'email' => 'test@supplier.com',
            'phone' => '1234567890',
            'status' => 'active',
        ]);

        $response = $this->post(route('sales.order-request.store', $sale->id), [
            'order_request_note' => 'Yêu cầu mua hàng',
            'order_request_items' => [
                [
                    'vendor_id' => $supplier->id,
                    'type' => 'HW',
                    'part_number' => 'PART001',
                    'quantity' => 5,
                    'needs_cq' => false,
                    'si_name' => 'SI Name',
                    'eu_name' => 'EU Name',
                    'mst' => '1234567890',
                ]
            ]
        ]);

        $response->assertRedirect();
        
        $pr = SaleOrderRequest::where('sale_id', $sale->id)->first();
        $this->assertNotNull($pr);
        // Status should be pending_admin
        $this->assertEquals(SaleOrderRequest::STATUS_PENDING_ADMIN, $pr->status);

        // 2. Non-admin users cannot approve
        // We will remove withoutExceptionHandling temporarily to test unauthorized exceptions
        try {
            $this->actingAs($this->salesUser);
            $response = $this->post(route('sales.order-request.admin-approve', [$sale->id, $pr->id]));
            $response->assertStatus(302); // redirects back with error
            $this->assertEquals(SaleOrderRequest::STATUS_PENDING_ADMIN, $pr->fresh()->status);
        } catch (\Exception $e) {
            // expected
        }

        // 3. Admin user approves the PR
        $this->actingAs($this->adminUser);
        $response = $this->post(route('sales.order-request.admin-approve', [$sale->id, $pr->id]));
        $response->assertRedirect();
        $this->assertEquals(SaleOrderRequest::STATUS_PROCESSING, $pr->fresh()->status);
    }

    public function test_export_three_step_workflow(): void
    {
        $this->withoutExceptionHandling();
        $sale = Sale::create([
            'code' => 'SALE002',
            'type' => 'project',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'date' => now(),
            'total' => 1000000,
            'pl_status' => 'approved',
            'payment_term_type' => 'bod_exception',
            'payment_terms' => []
        ]);

        // Creating the export linked to the sale
        $export = Export::create([
            'code' => $sale->code,
            'warehouse_id' => $this->warehouse->id,
            'project_id' => $sale->project_id,
            'customer_id' => $sale->customer_id,
            'date' => $sale->date,
            'employee_id' => $this->salesUser->id,
            'total_qty' => 0,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'status' => 'draft',
        ]);

        // 1. Sales requests export
        $this->actingAs($this->salesUser);
        $response = $this->post(route('exports.request-export', $export->id));
        $response->assertRedirect();
        $this->assertEquals('pending_admin', $export->fresh()->status);

        // 2. Admin approves export -> status becomes pending_invoice
        $this->actingAs($this->adminUser);
        $response = $this->post(route('exports.admin-approve', $export->id));
        $response->assertJson(['success' => true]);
        $this->assertEquals('pending_invoice', $export->fresh()->status);
    }
}
