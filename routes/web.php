<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\StripeProductController;

Route::get('/', function () {
    return Inertia::render('frontend/Landing');
})->name('home');

// Stripe Products API
Route::get('/api/stripe/products', [StripeProductController::class, 'getProducts'])->name('stripe.products');


require __DIR__.'/client.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/stripe.php';
