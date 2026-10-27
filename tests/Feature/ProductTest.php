<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_lists_products_paginated_and_filtered(): void
    {
        Product::factory(3)->create();
        Product::factory()->create(['name' => 'Teclado mecánico']);

        $this->getJson('/api/products?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 4);

        $this->getJson('/api/products?search=Teclado')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Teclado mecánico');
    }

    public function test_it_creates_a_product(): void
    {
        $currency = Currency::factory()->create();

        $this->postJson('/api/products', [
            'name' => 'Teclado',
            'description' => 'Teclado mecánico',
            'price' => 120.5,
            'currency_id' => $currency->id,
            'tax_cost' => 19.28,
            'manufacturing_cost' => 45,
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Product created successfully.')
            ->assertJsonPath('data.name', 'Teclado')
            ->assertJsonPath('data.price', '120.50')
            ->assertJsonPath('data.currency.id', $currency->id);

        $this->assertDatabaseHas('products', ['name' => 'Teclado', 'price' => 120.5]);
    }

    public function test_it_validates_product_input(): void
    {
        $this->postJson('/api/products', ['price' => -1, 'currency_id' => Str::uuid7(), 'tax_cost' => 1.234])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'price', 'currency_id', 'tax_cost', 'manufacturing_cost']);
    }

    public function test_it_shows_a_product_with_its_prices(): void
    {
        $product = Product::factory()->create();
        $product->prices()->create(['currency_id' => Currency::factory()->create()->id, 'price' => 10]);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonCount(1, 'data.prices');
    }

    public function test_it_returns_404_for_missing_products(): void
    {
        $this->getJson('/api/products/'.Str::uuid7())->assertNotFound()->assertExactJson(['message' => 'Resource not found.']);
        $this->getJson('/api/products/not-a-uuid')->assertNotFound();
    }

    public function test_it_updates_a_product_partially(): void
    {
        $product = Product::factory()->create(['name' => 'Viejo']);

        $this->putJson("/api/products/{$product->id}", ['name' => 'Nuevo'])
            ->assertOk()
            ->assertJsonPath('message', 'Product updated successfully.')
            ->assertJsonPath('data.name', 'Nuevo')
            ->assertJsonPath('data.price', $product->price);
    }

    public function test_it_deletes_a_product_and_its_prices(): void
    {
        $product = Product::factory()->create(['name' => 'Teclado']);
        $product->prices()->create(['currency_id' => Currency::factory()->create()->id, 'price' => 10]);

        $this->deleteJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertExactJson([
                'message' => 'Product deleted successfully.',
                'data' => ['id' => $product->id, 'name' => 'Teclado', 'deleted_prices' => 1],
            ]);

        $this->assertModelMissing($product);
        $this->assertDatabaseCount('product_prices', 0);
    }

    public function test_mass_assignment_ignores_unknown_fields(): void
    {
        $product = Product::factory()->create();

        $this->putJson("/api/products/{$product->id}", ['id' => (string) Str::uuid7(), 'name' => 'Ok'])->assertOk();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Ok']);
    }

    public function test_listing_and_showing_products_avoid_n_plus_one_queries(): void
    {
        Product::factory(10)->create()->each(
            fn (Product $product) => $product->prices()->create(['currency_id' => Currency::factory()->create()->id, 'price' => 10])
        );

        DB::enableQueryLog();
        $this->getJson('/api/products')->assertOk()->assertJsonCount(10, 'data');
        $this->assertCount(3, DB::getQueryLog());

        $id = Product::value('id');
        DB::flushQueryLog();
        $this->getJson("/api/products/{$id}")->assertOk();
        $this->assertCount(4, DB::getQueryLog());
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        Product::factory()->create(['name' => 'Descuento 50%']);
        Product::factory()->create(['name' => 'Otro']);

        $this->getJson('/api/products?search=%25')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/products?search=_')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_base_currency_cannot_switch_to_one_with_an_additional_price(): void
    {
        $product = Product::factory()->create();
        $eur = Currency::factory()->create();
        $product->prices()->create(['currency_id' => $eur->id, 'price' => 10]);

        $this->patchJson("/api/products/{$product->id}", ['currency_id' => $eur->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id');
    }

    public function test_api_is_rate_limited(): void
    {
        foreach (range(1, 60) as $_) {
            $this->getJson('/api/currencies')->assertOk();
        }

        $this->getJson('/api/currencies')->assertTooManyRequests();
    }

    public function test_currency_uuid_is_case_insensitive(): void
    {
        $currency = Currency::factory()->create();

        $this->postJson('/api/products', [
            'name' => 'Teclado',
            'description' => 'Teclado mecánico',
            'price' => 10,
            'currency_id' => strtoupper($currency->id),
            'tax_cost' => 1,
            'manufacturing_cost' => 1,
        ])
            ->assertCreated()
            ->assertJsonPath('data.currency.id', $currency->id);
    }

    public function test_product_names_are_unique(): void
    {
        $existing = Product::factory()->create(['name' => 'Silla']);
        $other = Product::factory()->create(['name' => 'Mesa']);

        $this->postJson('/api/products', ['name' => 'Silla'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'A product with this name already exists.');

        $this->patchJson("/api/products/{$other->id}", ['name' => 'Silla'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->patchJson("/api/products/{$existing->id}", ['name' => 'Silla', 'price' => 10])->assertOk();
    }
}
