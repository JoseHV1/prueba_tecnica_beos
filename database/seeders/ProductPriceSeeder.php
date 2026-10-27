<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductPriceSeeder extends Seeder
{
    public function run(): void
    {
        $currencies = Currency::all()->keyBy('name');
        $products = Product::with('currency')->get()->keyBy('name');

        $prices = [
            ['product' => 'Laptop 14"', 'currency' => 'EUR', 'price' => 829.00],
            ['product' => 'Laptop 14"', 'currency' => 'MXN', 'price' => null],
            ['product' => 'Monitor 27"', 'currency' => 'COP', 'price' => 1349000.00],
            ['product' => 'Teclado mecánico', 'currency' => 'USD', 'price' => null],
        ];

        foreach ($prices as $price) {
            $product = $products[$price['product']];
            $currency = $currencies[$price['currency']];

            $product->prices()->updateOrCreate(
                ['currency_id' => $currency->id],
                ['price' => $price['price'] ?? $product->priceIn($currency)],
            );
        }
    }
}
