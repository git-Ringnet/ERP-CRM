<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 10000);
        $discount = fake()->randomFloat(2, 0, $subtotal * 0.1);
        $vat = ($subtotal - $discount) * 0.1;
        $total = $subtotal - $discount + $vat;
        $cost = $total * fake()->randomFloat(2, 0.5, 0.8);
        $margin = $total - $cost;
        $marginPercent = $total > 0 ? ($margin / $total) * 100 : 0;

        return [
            'code' => 'SALE' . fake()->unique()->numberBetween(10000, 99999),
            'type' => fake()->randomElement(['retail', 'project']),
            'customer_id' => Customer::factory(),
            'customer_name' => fake()->company(),
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'delivery_address' => fake()->optional()->address(),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'vat' => $vat,
            'total' => $total,
            'cost' => $cost,
            'margin' => $margin,
            'margin_percent' => $marginPercent,
            'paid_amount' => fake()->randomFloat(2, 0, $total),
            'debt_amount' => 0,
            'payment_status' => fake()->randomElement(['unpaid', 'partial', 'paid']),
            'status' => fake()->randomElement(['pending', 'approved', 'shipping', 'completed', 'cancelled']),
            'note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the sale is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the sale is cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
