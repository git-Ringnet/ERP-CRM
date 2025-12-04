<?php

namespace Tests\Unit\Models;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_be_created(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUPP001',
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
            'phone' => '0123456789',
        ]);

        $this->assertDatabaseHas('suppliers', [
            'code' => 'SUPP001',
            'name' => 'Test Supplier',
        ]);
    }

    public function test_supplier_fillable_attributes(): void
    {
        $supplier = new Supplier();
        $fillable = $supplier->getFillable();

        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('phone', $fillable);
    }
}
