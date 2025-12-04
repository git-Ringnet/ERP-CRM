<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_search_scope_works(): void
    {
        Customer::create([
            'code' => 'CUST001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0123456789',
            'type' => 'normal',
        ]);

        Customer::create([
            'code' => 'CUST002',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
            'type' => 'vip',
        ]);

        $results = Customer::search('John')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->name);
    }

    public function test_customer_filter_by_type_scope_works(): void
    {
        Customer::create([
            'code' => 'CUST001',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0123456789',
            'type' => 'normal',
        ]);

        Customer::create([
            'code' => 'CUST002',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
            'type' => 'vip',
        ]);

        $vipCustomers = Customer::filterByType('vip')->get();
        $this->assertCount(1, $vipCustomers);
        $this->assertEquals('Jane Smith', $vipCustomers->first()->name);
    }

    public function test_supplier_search_scope_works(): void
    {
        Supplier::create([
            'code' => 'SUPP001',
            'name' => 'ABC Supplier',
            'email' => 'abc@example.com',
            'phone' => '0123456789',
        ]);

        Supplier::create([
            'code' => 'SUPP002',
            'name' => 'XYZ Supplier',
            'email' => 'xyz@example.com',
            'phone' => '0987654321',
        ]);

        $results = Supplier::search('ABC')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('ABC Supplier', $results->first()->name);
    }

    public function test_product_search_scope_works(): void
    {
        Product::create([
            'code' => 'PROD001',
            'name' => 'Laptop',
            'unit' => 'pcs',
            'price' => 1000.00,
            'cost' => 800.00,
            'management_type' => 'serial',
        ]);

        Product::create([
            'code' => 'PROD002',
            'name' => 'Mouse',
            'unit' => 'pcs',
            'price' => 20.00,
            'cost' => 10.00,
            'management_type' => 'normal',
        ]);

        $results = Product::search('Laptop')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Laptop', $results->first()->name);
    }

    public function test_product_filter_by_management_type_scope_works(): void
    {
        Product::create([
            'code' => 'PROD001',
            'name' => 'Laptop',
            'unit' => 'pcs',
            'price' => 1000.00,
            'cost' => 800.00,
            'management_type' => 'serial',
        ]);

        Product::create([
            'code' => 'PROD002',
            'name' => 'Mouse',
            'unit' => 'pcs',
            'price' => 20.00,
            'cost' => 10.00,
            'management_type' => 'normal',
        ]);

        $serialProducts = Product::filterByManagementType('serial')->get();
        $this->assertCount(1, $serialProducts);
        $this->assertEquals('Laptop', $serialProducts->first()->name);
    }
}
