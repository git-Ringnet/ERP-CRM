<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Comprehensive CRUD operations test
 * Validates all Create, Read, Update, Delete operations for all entities
 */
class CrudOperationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Customer CRUD operations
     * Requirements: 1.3, 1.5, 1.6, 1.8
     */
    public function test_customer_crud_operations(): void
    {
        // CREATE
        $createData = [
            'code' => 'CUST001',
            'name' => 'Test Customer',
            'email' => 'test@customer.com',
            'phone' => '0123456789',
            'type' => 'normal',
            'address' => '123 Test St',
            'debt_limit' => 10000,
            'debt_days' => 30,
        ];

        $response = $this->post(route('customers.store'), $createData);
        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['code' => 'CUST001']);

        // READ
        $customer = DB::table('customers')->where('code', 'CUST001')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Test Customer', $customer->name);
        $this->assertEquals('test@customer.com', $customer->email);

        // UPDATE
        $updateData = [
            'code' => 'CUST001',
            'name' => 'Updated Customer',
            'email' => 'updated@customer.com',
            'phone' => '0987654321',
            'type' => 'vip',
        ];

        $response = $this->put(route('customers.update', $customer->id), $updateData);
        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['name' => 'Updated Customer', 'type' => 'vip']);

        // DELETE
        $response = $this->delete(route('customers.destroy', $customer->id));
        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseMissing('customers', ['code' => 'CUST001']);
    }

    /**
     * Test Supplier CRUD operations
     * Requirements: 2.3, 2.5, 2.6, 2.8
     */
    public function test_supplier_crud_operations(): void
    {
        // CREATE
        $createData = [
            'code' => 'SUPP001',
            'name' => 'Test Supplier',
            'email' => 'test@supplier.com',
            'phone' => '0123456789',
            'address' => '456 Supplier Ave',
            'payment_terms' => 45,
        ];

        $response = $this->post(route('suppliers.store'), $createData);
        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['code' => 'SUPP001']);

        // READ
        $supplier = DB::table('suppliers')->where('code', 'SUPP001')->first();
        $this->assertNotNull($supplier);
        $this->assertEquals('Test Supplier', $supplier->name);
        $this->assertEquals('test@supplier.com', $supplier->email);

        // UPDATE
        $updateData = [
            'code' => 'SUPP001',
            'name' => 'Updated Supplier',
            'email' => 'updated@supplier.com',
            'phone' => '0987654321',
            'payment_terms' => 60,
        ];

        $response = $this->put(route('suppliers.update', $supplier->id), $updateData);
        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['name' => 'Updated Supplier']);

        // DELETE
        $response = $this->delete(route('suppliers.destroy', $supplier->id));
        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['code' => 'SUPP001']);
    }

    /**
     * Test Employee CRUD operations
     * Requirements: 3.3, 3.5, 3.6, 3.8
     */
    public function test_employee_crud_operations(): void
    {
        // CREATE
        $createData = [
            'employee_code' => 'EMP001',
            'name' => 'Test Employee',
            'email' => 'test@employee.com',
            'phone' => '0123456789',
            'department' => 'IT',
            'position' => 'Developer',
            'status' => 'active',
            'salary' => 50000,
        ];

        $response = $this->post(route('employees.store'), $createData);
        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', ['employee_code' => 'EMP001']);

        // READ
        $employee = DB::table('users')->where('employee_code', 'EMP001')->first();
        $this->assertNotNull($employee);
        $this->assertEquals('Test Employee', $employee->name);
        $this->assertEquals('IT', $employee->department);

        // UPDATE
        $updateData = [
            'employee_code' => 'EMP001',
            'name' => 'Updated Employee',
            'email' => 'updated@employee.com',
            'phone' => '0987654321',
            'department' => 'HR',
            'position' => 'Manager',
            'status' => 'active',
        ];

        $response = $this->put(route('employees.update', $employee->id), $updateData);
        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('users', ['name' => 'Updated Employee', 'department' => 'HR']);

        // DELETE
        $response = $this->delete(route('employees.destroy', $employee->id));
        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseMissing('users', ['employee_code' => 'EMP001']);
    }

    /**
     * Test Product CRUD operations
     * Requirements: 4.5, 4.7, 4.8, 4.10
     */
    public function test_product_crud_operations(): void
    {
        // CREATE
        $createData = [
            'code' => 'PROD001',
            'name' => 'Test Product',
            'unit' => 'pcs',
            'price' => 100.00,
            'cost' => 50.00,
            'management_type' => 'normal',
            'stock' => 100,
            'min_stock' => 10,
            'max_stock' => 500,
        ];

        $response = $this->post(route('products.store'), $createData);
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['code' => 'PROD001']);

        // READ
        $product = DB::table('products')->where('code', 'PROD001')->first();
        $this->assertNotNull($product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(100.00, $product->price);

        // UPDATE
        $updateData = [
            'code' => 'PROD001',
            'name' => 'Updated Product',
            'unit' => 'box',
            'price' => 150.00,
            'cost' => 75.00,
            'management_type' => 'serial',
        ];

        $response = $this->put(route('products.update', $product->id), $updateData);
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['name' => 'Updated Product', 'management_type' => 'serial']);

        // DELETE
        $response = $this->delete(route('products.destroy', $product->id));
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['code' => 'PROD001']);
    }
}
