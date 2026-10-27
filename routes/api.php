<?php

use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductPriceController;
use Illuminate\Support\Facades\Route;

Route::get('currencies', [CurrencyController::class, 'index']);

Route::apiResource('products', ProductController::class);
Route::apiResource('products.prices', ProductPriceController::class)->only(['index', 'store']);
