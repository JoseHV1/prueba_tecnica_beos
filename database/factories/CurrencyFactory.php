<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->currencyCode(),
            'symbol' => fake()->randomElement(['$', '€', '£', '¥']),
            'exchange_rate' => fake()->randomFloat(6, 0.5, 50),
        ];
    }
}
