<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('customers'));
    }

    public function test_customers_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('customers', [
            'id', 'code', 'name', 'email', 'phone', 'address', 'type',
            'tax_code', 'website', 'contact_person', 'debt_limit', 'debt_days', 'note'
        ]));
    }

    public function test_suppliers_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('suppliers'));
    }

    public function test_suppliers_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('suppliers', [
            'id', 'code', 'name', 'email', 'phone', 'address',
            'tax_code', 'website', 'contact_person', 'payment_terms', 'product_type', 'note'
        ]));
    }

    public function test_users_table_has_employee_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'employee_code', 'birth_date', 'phone', 'address', 'id_card',
            'department', 'position', 'join_date', 'salary', 'bank_account', 'bank_name', 'status', 'note'
        ]));
    }

    public function test_products_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('products'));
    }

    public function test_products_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('products', [
            'id', 'code', 'name', 'category', 'unit', 'price', 'cost', 'stock',
            'min_stock', 'max_stock', 'management_type', 'auto_generate_serial',
            'serial_prefix', 'expiry_months', 'track_expiry', 'description', 'note'
        ]));
    }
}
