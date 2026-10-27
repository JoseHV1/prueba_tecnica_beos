<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(12),
            'price' => fake()->randomFloat(2, 10, 1000),
            'currency_id' => Currency::factory(),
            'tax_cost' => fake()->randomFloat(2, 1, 100),
            'manufacturing_cost' => fake()->randomFloat(2, 5, 500),
        ];
    }
}
