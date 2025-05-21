<?php

use App\Http\Controllers\Admin\StripeController;
use App\Http\Controllers\Admin\StripeProductController;
use App\Http\Controllers\Admin\StripeSubscriptionController;
use App\Http\Controllers\Admin\StripeCustomerController;
use App\Http\Controllers\Admin\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/stripe')->middleware(['auth', 'verified', 'role:super-admin'])->name('admin.stripe.')->group(function () {
    // Dashboard
    Route::get('dashboard', [StripeController::class, 'dashboard'])->name('dashboard');

    // Products
    Route::get('products', [StripeProductController::class, 'index'])->name('products');
    Route::post('products', [StripeProductController::class, 'store'])->name('products.store');
    Route::get('products/{id}', [StripeProductController::class, 'show'])->name('products.show');
    Route::put('products/{id}', [StripeProductController::class, 'update'])->name('products.update');
    Route::post('products/{id}/prices', [StripeProductController::class, 'createPrice'])->name('products.prices.store');
    Route::put('products/{productId}/prices/{priceId}', [StripeProductController::class, 'updatePrice'])->name('products.prices.update');
    Route::delete('products/{productId}/prices/{priceId}', [StripeProductController::class, 'archivePrice'])->name('products.prices.archive');

    // Subscriptions
    Route::get('subscriptions', [StripeSubscriptionController::class, 'index'])->name('subscriptions');
    Route::get('subscriptions/{id}', [StripeSubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('subscriptions/{id}/cancel', [StripeSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('subscriptions/{id}/resume', [StripeSubscriptionController::class, 'resume'])->name('subscriptions.resume');

    // Customers
    Route::get('customers', [StripeCustomerController::class, 'index'])->name('customers');
    Route::get('customers/{id}', [StripeCustomerController::class, 'show'])->name('customers.show');
    Route::post('customers', [StripeCustomerController::class, 'createCustomer'])->name('customers.store');
    Route::delete('customers/{id}', [StripeCustomerController::class, 'destroy'])->name('customers.destroy');

    // Webhooks
    Route::get('webhooks', [StripeWebhookController::class, 'index'])->name('webhooks');
    Route::post('webhooks', [StripeWebhookController::class, 'store'])->name('webhooks.store');
    Route::put('webhooks/{id}', [StripeWebhookController::class, 'update'])->name('webhooks.update');
    Route::delete('webhooks/{id}', [StripeWebhookController::class, 'destroy'])->name('webhooks.destroy');
    Route::get('webhooks/events', [StripeWebhookController::class, 'events'])->name('webhooks.events');
});
