<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\StripeProductController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ContactController;

Route::get('/', function () {
    return Inertia::render('frontend/Landing');
})->name('home');

// Stripe Products API
Route::get('/api/stripe/products', [StripeProductController::class, 'getProducts'])->name('stripe.products');

// Newsletter Routes
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'showUnsubscribe'])->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe.confirm');

// Contact Routes
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

require __DIR__.'/client.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/stripe.php';
