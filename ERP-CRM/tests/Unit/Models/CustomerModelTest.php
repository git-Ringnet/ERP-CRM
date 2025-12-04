<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_created(): void
    {
        $customer = Customer::create([
            'code' => 'CUST001',
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'type' => 'normal',
        ]);

        $this->assertDatabaseHas('customers', [
            'code' => 'CUST001',
            'name' => 'Test Customer',
        ]);
    }

    public function test_customer_fillable_attributes(): void
    {
        $customer = new Customer();
        $fillable = $customer->getFillable();

        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('type', $fillable);
    }
}
