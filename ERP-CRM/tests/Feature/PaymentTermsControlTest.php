<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PaymentTermsControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $salesUser;
    protected User $directorUser;
    protected User $accountantUser;
    protected Customer $customer;
    protected Project $project;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard roles/users if not exists
        $this->salesUser = User::factory()->create();
        $salesRole = \App\Models\Role::firstOrCreate(['slug' => 'sales'], ['name' => 'Sales']);
        $this->salesUser->roles()->syncWithoutDetaching([$salesRole->id]);

        $this->directorUser = User::factory()->create();
        $directorRole = \App\Models\Role::firstOrCreate(['slug' => 'director'], ['name' => 'Director']);
        $this->directorUser->roles()->syncWithoutDetaching([$directorRole->id]);

        $this->accountantUser = User::factory()->create();
        $accountantRole = \App\Models\Role::firstOrCreate(['slug' => 'accountant'], ['name' => 'Accountant']);
        $this->accountantUser->roles()->syncWithoutDetaching([$accountantRole->id]);

        $this->customer = new Customer();
        $this->customer->name = 'Test Payment Cust';
        $this->customer->debt_days = 10;
        $this->customer->tax_code = '1234567890';
        $this->customer->save();

        $this->project = new Project();
        $this->project->code = 'TESTPROJ';
        $this->project->name = 'Test Project';
        $this->project->save();

        $this->product = Product::factory()->create();
    }

    /**
     * Test payment terms percentage sum validation
     */
    public function test_sale_validation_requires_100_percent_total_milestones(): void
    {
        $this->actingAs($this->salesUser);

        // Submit P&L with milestones summing to 90% -> should redirect with error
        $sale = Sale::create([
            'code' => 'TESTSALE01',
            'type' => 'project',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'date' => now(),
            'total' => 10000000,
            'pl_status' => 'pending',
            'payment_term_type' => 'milestones',
            'payment_terms' => [
                [
                    'milestone_name' => 'Đợt 1',
                    'percentage' => 30,
                    'amount' => 3000000,
                    'required_before' => 'before_order',
                    'timing' => 'after_contract',
                    'required_docs' => 'unc',
                    'due_days' => 5,
                    'status' => 'unpaid'
                ],
                [
                    'milestone_name' => 'Đợt 2',
                    'percentage' => 60,
                    'amount' => 6000000,
                    'required_before' => 'after_delivery',
                    'timing' => 'after_delivery',
                    'required_docs' => 'none',
                    'due_days' => 30,
                    'status' => 'unpaid'
                ]
            ]
        ]);

        $response = $this->post(route('sales.submitPnL', $sale->id));
        $response->assertSessionHas('error');
        $this->assertEquals('pending', $sale->fresh()->pl_status);

        // Update milestones to sum 100% -> should successfully submit
        $sale->update([
            'payment_terms' => [
                [
                    'milestone_name' => 'Đợt 1',
                    'percentage' => 30,
                    'amount' => 3000000,
                    'required_before' => 'before_order',
                    'timing' => 'after_contract',
                    'required_docs' => 'unc',
                    'due_days' => 5,
                    'status' => 'unpaid'
                ],
                [
                    'milestone_name' => 'Đợt 2',
                    'percentage' => 70,
                    'amount' => 7000000,
                    'required_before' => 'after_delivery',
                    'timing' => 'after_delivery',
                    'required_docs' => 'none',
                    'due_days' => 30,
                    'status' => 'unpaid'
                ]
            ]
        ]);

        $response = $this->post(route('sales.submitPnL', $sale->id));
        $response->assertRedirect();
        
        $plStatus = $sale->fresh()->pl_status;
        $this->assertTrue(in_array($plStatus, ['submitted', 'pending']));
    }

    /**
     * Test order blocks and BOD exception preload
     */
    public function test_ordering_control_and_preload_exception(): void
    {
        $sale = Sale::create([
            'code' => 'TESTSALE02',
            'type' => 'project',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'date' => now(),
            'total' => 10000000,
            'pl_status' => 'approved',
            'payment_term_type' => 'milestones',
            'payment_terms' => [
                [
                    'milestone_name' => 'Đợt 1 (Cọc)',
                    'percentage' => 30,
                    'amount' => 3000000,
                    'required_before' => 'before_order',
                    'timing' => 'after_contract',
                    'required_docs' => 'unc',
                    'due_days' => 5,
                    'status' => 'unpaid'
                ],
                [
                    'milestone_name' => 'Đợt 2',
                    'percentage' => 70,
                    'amount' => 7000000,
                    'required_before' => 'after_delivery',
                    'timing' => 'after_delivery',
                    'required_docs' => 'none',
                    'due_days' => 30,
                    'status' => 'unpaid'
                ]
            ]
        ]);

        // Sale is NOT eligible for order request
        $this->assertFalse($sale->getPaymentConditionStatus()['eligible_for_order']);

        // Try submitting order request -> should fail
        $this->actingAs($this->salesUser);
        $response = $this->post(route('sales.order-request.store', $sale->id), [
            'order_request_items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'note' => 'Test'
                ]
            ]
        ]);
        $response->assertSessionHas('error');

        // Approve BOD Exception preload
        Storage::fake('public');
        $this->actingAs($this->directorUser);
        $file = UploadedFile::fake()->create('bod_approval.pdf', 100);

        $response = $this->post(route('sales.approvePaymentException', $sale->id), [
            'payment_exception_file' => $file
        ]);

        $response->assertRedirect();
        $this->assertTrue($sale->fresh()->is_payment_exception);
        $this->assertTrue($sale->fresh()->getPaymentConditionStatus()['eligible_for_order']);
    }

    /**
     * Test milestone amount retention when contract total changes (e.g. due to VAT change)
     */
    public function test_milestone_amount_retention_when_contract_total_changes(): void
    {
        $sale = Sale::create([
            'code' => 'TESTSALE03',
            'type' => 'project',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'date' => now(),
            'total' => 10000000,
            'pl_status' => 'approved',
            'payment_term_type' => 'milestones',
            'payment_terms' => [
                [
                    'milestone_name' => 'Đợt 1 (Cọc)',
                    'percentage' => 30,
                    'amount' => 3000000,
                    'required_before' => 'before_order',
                    'timing' => 'after_contract',
                    'required_docs' => 'unc',
                    'due_days' => 5,
                    'status' => 'unpaid'
                ]
            ]
        ]);

        // Change total contract price (e.g., from 10,000,000 to 9,000,000 due to VAT change)
        $sale->update(['total' => 9000000]);

        $status = $sale->getPaymentConditionStatus();
        $milestones = $status['milestones'];

        // Milestone amount should remain exactly 3,000,000 (not recalculated to 30% of 9,000,000 = 2,700,000)
        $this->assertEquals(3000000, $milestones[0]['amount']);
    }

    /**
     * Test BOD delegation of exception approval
     */
    public function test_payment_exception_delegation(): void
    {
        $sale = Sale::create([
            'code' => 'TESTSALE04',
            'type' => 'project',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'date' => now(),
            'total' => 10000000,
            'pl_status' => 'approved',
            'payment_term_type' => 'milestones',
            'payment_terms' => [
                [
                    'milestone_name' => 'Đợt 1 (Cọc)',
                    'percentage' => 30,
                    'amount' => 3000000,
                    'required_before' => 'before_order',
                    'timing' => 'after_contract',
                    'required_docs' => 'unc',
                    'due_days' => 5,
                    'status' => 'unpaid'
                ]
            ]
        ]);

        Storage::fake('public');

        // 1. Another user (not BOD, not delegated) tries to approve -> fails
        $this->actingAs($this->salesUser);
        $file = UploadedFile::fake()->create('approval.pdf', 100);
        $response = $this->post(route('sales.approvePaymentException', $sale->id), [
            'payment_exception_file' => $file
        ]);
        $response->assertSessionHas('error');
        $this->assertFalse($sale->fresh()->is_payment_exception);

        // 2. BOD delegates whole sale exception approval to sales user
        $this->actingAs($this->directorUser);
        $response = $this->post(route('sales.delegatePaymentException', $sale->id), [
            'delegate_user_id' => $this->salesUser->id
        ]);
        $response->assertRedirect();
        $this->assertEquals($this->salesUser->id, $sale->fresh()->payment_exception_delegated_to);

        // 3. Sales user now approves the sale exception -> success
        $this->actingAs($this->salesUser);
        $file = UploadedFile::fake()->create('delegated_approval.pdf', 100);
        $response = $this->post(route('sales.approvePaymentException', $sale->id), [
            'payment_exception_file' => $file
        ]);
        $response->assertRedirect();
        $this->assertTrue($sale->fresh()->is_payment_exception);

        // Reset exception status and check milestone delegation
        $sale->update(['is_payment_exception' => false, 'payment_exception_delegated_to' => null]);

        // 4. BOD delegates milestone exception approval to accountant user
        $this->actingAs($this->directorUser);
        $response = $this->post(route('sales.milestones.delegateException', [$sale->id, 0]), [
            'delegate_user_id' => $this->accountantUser->id
        ]);
        $response->assertRedirect();
        $this->assertEquals($this->accountantUser->id, $sale->paymentSchedules()->first()->delegated_to_id);

        // 5. Accountant user now approves the milestone exception -> success
        $this->actingAs($this->accountantUser);
        $file = UploadedFile::fake()->create('milestone_approval.pdf', 100);
        $response = $this->post(route('sales.milestones.approveException', [$sale->id, 0]), [
            'bod_approval_file' => $file
        ]);
        $response->assertRedirect();
        $this->assertEquals('exception_approved', $sale->paymentSchedules()->first()->status);
    }

    /**
     * Test uploading UNC payment proof for a milestone
     */
    public function test_upload_milestone_proof(): void
    {
        $sale = Sale::create([
            'code' => 'TESTSALE05',
            'type' => 'project',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'date' => now(),
            'total' => 10000000,
            'pl_status' => 'approved',
            'payment_term_type' => 'milestones',
            'payment_terms' => [
                [
                    'milestone_name' => 'Đợt 1 (Cọc)',
                    'percentage' => 30,
                    'amount' => 3000000,
                    'required_before' => 'before_order',
                    'timing' => 'after_contract',
                    'required_docs' => 'unc',
                    'due_days' => 5,
                    'status' => 'unpaid'
                ]
            ]
        ]);

        Storage::fake('public');
        $this->actingAs($this->salesUser);
        $file = UploadedFile::fake()->create('unc_proof.pdf', 100);

        $response = $this->post(route('sales.milestones.submitProof', [$sale->id, 0]), [
            'proof_file' => $file
        ]);

        $response->assertRedirect();
        $this->assertEquals('pending_finance', $sale->paymentSchedules()->first()->status);
        $this->assertDatabaseHas('payment_evidences', [
            'schedule_id' => $sale->paymentSchedules()->first()->id,
            'doc_type' => 'unc'
        ]);
    }

    /**
     * Test Salesperson can confirm payment
     */
    public function test_salesperson_can_confirm_payment(): void
    {
        $sale = Sale::create([
            'code' => 'TESTSALE06',
            'type' => 'project',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'user_id' => $this->salesUser->id,
            'date' => now(),
            'total' => 10000000,
            'pl_status' => 'approved',
            'payment_term_type' => 'milestones',
            'payment_terms' => [
                [
                    'milestone_name' => 'Đợt 1',
                    'percentage' => 100,
                    'amount' => 10000000,
                    'required_before' => 'before_order',
                    'timing' => 'after_contract',
                    'required_docs' => 'unc',
                    'due_days' => 5,
                    'status' => 'pending_finance'
                ]
            ]
        ]);

        $this->actingAs($this->salesUser);
        $response = $this->post(route('sales.milestones.confirmPayment', [$sale->id, 0]));
        $response->assertRedirect();
        
        $this->assertEquals('paid', $sale->paymentSchedules()->first()->status);
    }

    /**
     * Test zero total sale milestones are not auto paid
     */
    public function test_zero_total_sale_milestones_not_auto_paid(): void
    {
        $sale = Sale::create([
            'code' => 'TESTSALE07',
            'type' => 'project',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
            'user_id' => $this->salesUser->id,
            'date' => now(),
            'total' => 0,
            'pl_status' => 'approved',
            'payment_term_type' => 'milestones',
            'payment_terms' => [
                [
                    'milestone_name' => 'Đợt 1',
                    'percentage' => 100,
                    'amount' => 0,
                    'required_before' => 'before_order',
                    'timing' => 'after_contract',
                    'required_docs' => 'unc',
                    'due_days' => 5,
                    'status' => 'unpaid'
                ]
            ]
        ]);

        $this->assertEquals('pending', $sale->paymentSchedules()->first()->status);
    }
}
