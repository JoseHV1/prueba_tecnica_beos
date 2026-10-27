<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_are_idempotent_and_use_uuids(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(4, Currency::count());
        $this->assertSame(4, Product::count());
        $this->assertSame(4, ProductPrice::count());
        $this->assertTrue(Str::isUuid(Product::value('id')));
    }
}
