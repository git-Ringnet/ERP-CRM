<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PROD' . fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['Electronics', 'Furniture', 'Office Supplies', 'Hardware']),
            'unit' => fake()->randomElement(['pcs', 'box', 'set', 'kg', 'meter']),
            'price' => fake()->randomFloat(2, 10, 1000),
            'cost' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(0, 100),
            'min_stock' => fake()->numberBetween(5, 20),
            'max_stock' => fake()->numberBetween(100, 500),
            'management_type' => fake()->randomElement(['normal', 'serial', 'lot']),
            'auto_generate_serial' => fake()->boolean(),
            'serial_prefix' => fake()->optional()->lexify('???'),
            'expiry_months' => fake()->optional()->numberBetween(6, 36),
            'track_expiry' => fake()->boolean(),
            'description' => fake()->optional()->sentence(),
            'note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the product uses serial number management.
     */
    public function withSerial(): static
    {
        return $this->state(fn (array $attributes) => [
            'management_type' => 'serial',
            'auto_generate_serial' => true,
            'serial_prefix' => 'SN',
        ]);
    }

    /**
     * Indicate that the product uses lot number management.
     */
    public function withLot(): static
    {
        return $this->state(fn (array $attributes) => [
            'management_type' => 'lot',
            'expiry_months' => 12,
            'track_expiry' => true,
        ]);
    }

    /**
     * Indicate that the product uses normal management.
     */
    public function withNormal(): static
    {
        return $this->state(fn (array $attributes) => [
            'management_type' => 'normal',
            'auto_generate_serial' => false,
            'serial_prefix' => null,
            'expiry_months' => null,
            'track_expiry' => false,
        ]);
    }
}
