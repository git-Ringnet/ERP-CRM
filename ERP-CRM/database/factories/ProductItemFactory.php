<?php

namespace Database\Factories;

use App\Models\ProductItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductItem>
 * Requirements: 3.1 - Product items with SKUs and price tiers
 */
class ProductItemFactory extends Factory
{
    protected $model = ProductItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasPriceTiers = fake()->boolean(70); // 70% chance of having price tiers
        $priceTiers = null;
        
        if ($hasPriceTiers) {
            $tierCount = fake()->numberBetween(1, 5);
            $priceTiers = [];
            for ($i = 1; $i <= $tierCount; $i++) {
                $priceTiers[] = [
                    'name' => $i . 'yr',
                    'price' => fake()->randomFloat(2, 100, 10000),
                ];
            }
        }

        return [
            'product_id' => Product::factory(),
            'sku' => 'SKU-' . fake()->unique()->numberBetween(10000, 99999),
            'description' => fake()->optional()->sentence(),
            'cost_usd' => fake()->randomFloat(2, 50, 5000),
            'price_tiers' => $priceTiers,
            'quantity' => 1,
            'comments' => fake()->optional()->sentence(),
            'warehouse_id' => null,
            'inventory_transaction_id' => null,
            'status' => ProductItem::STATUS_IN_STOCK,
        ];
    }

    /**
     * Indicate that the item has a NO_SKU identifier.
     */
    public function withNoSku(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
            'sku' => ProductItem::generateNoSku($productId),
        ]);
    }

    /**
     * Indicate that the item is sold.
     */
    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductItem::STATUS_SOLD,
        ]);
    }

    /**
     * Indicate that the item is damaged.
     */
    public function damaged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductItem::STATUS_DAMAGED,
        ]);
    }

    /**
     * Indicate that the item is transferred.
     */
    public function transferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductItem::STATUS_TRANSFERRED,
        ]);
    }
}
