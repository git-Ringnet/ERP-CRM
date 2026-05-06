<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'type' => fake()->randomElement(['normal', 'vip']),
            'tax_code' => 'TAX-' . fake()->unique()->numberBetween(100000, 999999),
            'website' => fake()->optional()->url(),
            'debt_limit' => fake()->randomFloat(2, 0, 100000),
            'debt_days' => fake()->numberBetween(0, 90),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
