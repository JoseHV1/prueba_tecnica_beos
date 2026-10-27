<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductPriceTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Currency $eur;

    protected function setUp(): void
    {
        parent::setUp();

        $usd = Currency::factory()->create(['name' => 'USD', 'exchange_rate' => 1]);
        $this->eur = Currency::factory()->create(['name' => 'EUR', 'exchange_rate' => 0.92]);
        $this->product = Product::factory()->for($usd)->create(['price' => 100]);
    }

    public function test_it_lists_the_prices_of_a_product(): void
    {
        $this->product->prices()->create(['currency_id' => $this->eur->id, 'price' => 95]);

        $this->getJson("/api/products/{$this->product->id}/prices")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', '95.00')
            ->assertJsonPath('data.0.currency.name', 'EUR');
    }

    public function test_it_creates_a_price_with_an_explicit_amount(): void
    {
        $this->postJson("/api/products/{$this->product->id}/prices", ['currency_id' => $this->eur->id, 'price' => 95])
            ->assertCreated()
            ->assertJsonPath('data.price', '95.00')
            ->assertJsonPath('data.currency.id', $this->eur->id);
    }

    public function test_it_converts_the_base_price_when_no_amount_is_given(): void
    {
        $this->postJson("/api/products/{$this->product->id}/prices", ['currency_id' => $this->eur->id])
            ->assertCreated()
            ->assertJsonPath('data.price', '92.00');
    }

    public function test_it_rejects_duplicated_and_base_currencies(): void
    {
        $this->product->prices()->create(['currency_id' => $this->eur->id, 'price' => 95]);

        $this->postJson("/api/products/{$this->product->id}/prices", ['currency_id' => $this->eur->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id');

        $this->postJson("/api/products/{$this->product->id}/prices", ['currency_id' => $this->product->currency_id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id');
    }

    public function test_it_returns_404_for_a_missing_product(): void
    {
        $this->getJson('/api/products/'.Str::uuid7().'/prices')->assertNotFound();
    }

    public function test_it_rejects_a_converted_price_above_the_maximum(): void
    {
        $this->product->update(['price' => Product::MAX_AMOUNT]);
        $cop = Currency::factory()->create(['exchange_rate' => 4100]);

        $this->postJson("/api/products/{$this->product->id}/prices", ['currency_id' => $cop->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');
    }

    public function test_base_currency_cannot_be_bypassed_with_an_uppercase_uuid(): void
    {
        $this->postJson("/api/products/{$this->product->id}/prices", ['currency_id' => strtoupper($this->product->currency_id)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id');
    }
}
