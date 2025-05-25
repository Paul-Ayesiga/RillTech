<?php

use App\Http\Controllers\Client\Settings\PasswordController;
use App\Http\Controllers\Client\Settings\ProfileController;
use App\Http\Controllers\Client\SubscriptionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Subscription routes (accessible without subscription - for checkout only)
Route::middleware(['auth', 'verified', 'user.status', 'role_or_permission:client|super-admin'])->group(function () {
    Route::post('subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
});

// Protected routes (require subscription)
Route::middleware(['auth', 'verified', 'user.status', 'stripe.return', 'subscription', 'role_or_permission:client|super-admin'])->group(function () {
    // Main Subscription Management Page
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');

    // Subscription Actions
    Route::post('subscriptions/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
    Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('subscriptions/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');
    Route::get('subscriptions/billing-portal', [SubscriptionController::class, 'billingPortal'])->name('subscriptions.billing-portal');

    // Invoice Management
    Route::get('subscriptions/invoices', [SubscriptionController::class, 'invoices'])->name('subscriptions.invoices');
    Route::get('subscriptions/invoices/{invoice}/download', [SubscriptionController::class, 'downloadInvoice'])->name('subscriptions.invoices.download');
    Route::post('subscriptions/invoices/bulk-download', [SubscriptionController::class, 'bulkDownloadInvoices'])->name('subscriptions.invoices.bulk-download');
    Route::get('subscriptions/invoices/bulk-download/{downloadId}/status', [SubscriptionController::class, 'checkBulkDownloadStatus'])->name('subscriptions.invoices.bulk-download.status');

    // Payment Method Management
    Route::get('subscriptions/payment-methods', [SubscriptionController::class, 'paymentMethods'])->name('subscriptions.payment-methods');
    Route::post('subscriptions/payment-methods', [SubscriptionController::class, 'addPaymentMethod'])->name('subscriptions.payment-methods.add');
    Route::post('subscriptions/payment-methods/setup-intent', [SubscriptionController::class, 'createSetupIntent'])->name('subscriptions.payment-methods.setup-intent');
    Route::put('subscriptions/payment-methods/{paymentMethod}', [SubscriptionController::class, 'updatePaymentMethod'])->name('subscriptions.payment-methods.update');
    Route::delete('subscriptions/payment-methods/{paymentMethod}', [SubscriptionController::class, 'deletePaymentMethod'])->name('subscriptions.payment-methods.delete');
    Route::post('subscriptions/payment-methods/{paymentMethod}/default', [SubscriptionController::class, 'setDefaultPaymentMethod'])->name('subscriptions.payment-methods.default');

    // Dashboard - Simple and Clean
    Route::get('dashboard', function () {
        return Inertia::render('client/Dashboard');
    })->name('dashboard');

    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
});
