<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $currencies = Currency::pluck('id', 'name');

        $products = [
            ['name' => 'Laptop 14"', 'description' => 'Laptop de 14 pulgadas, 16 GB de RAM y 512 GB SSD', 'price' => 899.99, 'currency' => 'USD', 'tax_cost' => 143.99, 'manufacturing_cost' => 520.00],
            ['name' => 'Monitor 27"', 'description' => 'Monitor IPS 27 pulgadas QHD a 144 Hz', 'price' => 329.00, 'currency' => 'USD', 'tax_cost' => 52.64, 'manufacturing_cost' => 180.00],
            ['name' => 'Teclado mecánico', 'description' => 'Teclado mecánico inalámbrico con switches rojos', 'price' => 95.50, 'currency' => 'EUR', 'tax_cost' => 20.06, 'manufacturing_cost' => 38.00],
            ['name' => 'Mouse ergonómico', 'description' => 'Mouse vertical ergonómico recargable', 'price' => 850.00, 'currency' => 'MXN', 'tax_cost' => 136.00, 'manufacturing_cost' => 310.00],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['name' => $product['name']],
                [...collect($product)->except('currency')->all(), 'currency_id' => $currencies[$product['currency']]],
            );
        }
    }
}
