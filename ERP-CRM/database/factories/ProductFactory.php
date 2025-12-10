<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 * Requirements: 1.4 - Simplified product schema
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
            'category' => fake()->randomElement(Product::CATEGORIES),
            'unit' => fake()->randomElement(['Cái', 'Hộp', 'Bộ', 'Kg', 'Mét']),
            'description' => fake()->optional()->sentence(),
            'note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the product has a specific category.
     */
    public function withCategory(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => strtoupper($category),
        ]);
    }
}
