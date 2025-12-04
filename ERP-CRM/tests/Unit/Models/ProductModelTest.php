<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created(): void
    {
        $product = Product::create([
            'code' => 'PROD001',
            'name' => 'Test Product',
            'unit' => 'pcs',
            'price' => 100.00,
            'cost' => 50.00,
            'management_type' => 'normal',
        ]);

        $this->assertDatabaseHas('products', [
            'code' => 'PROD001',
            'name' => 'Test Product',
        ]);
    }

    public function test_product_fillable_attributes(): void
    {
        $product = new Product();
        $fillable = $product->getFillable();

        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('unit', $fillable);
        $this->assertContains('price', $fillable);
        $this->assertContains('cost', $fillable);
        $this->assertContains('management_type', $fillable);
    }
}
