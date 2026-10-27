<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'symbol', 'exchange_rate'];

    protected function casts(): array
    {
        return ['exchange_rate' => 'decimal:6'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
