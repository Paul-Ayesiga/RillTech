<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\StripeProductController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DemoRequestController;

Route::get('/', function () {
    return Inertia::render('frontend/Landing');
})->name('home');

// Chat API Routes - Excluded from CSRF in VerifyCsrfToken middleware
Route::post('/api/chat', [ChatController::class, 'sendMessage'])->name('chat.send');
Route::post('/api/chat/stream', [ChatController::class, 'streamMessage'])->name('chat.stream');

// Demo Request API Routes - Excluded from CSRF for chatbot usage
Route::post('/api/demo-requests', [DemoRequestController::class, 'store'])->name('demo-requests.store');
Route::post('/api/demo-requests/chatbot', [DemoRequestController::class, 'storeViaChatbot'])->name('demo-requests.chatbot');
Route::get('/api/demo-requests/available-slots', [DemoRequestController::class, 'getAvailableSlots'])->name('demo-requests.available-slots');

// Stripe Products API
Route::get('/api/stripe/products', [StripeProductController::class, 'getProducts'])->name('stripe.products');

// Newsletter Routes
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'showUnsubscribe'])->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe.confirm');

// Contact Routes
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Stripe Webhook (must be outside middleware groups)
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']);

// Test route for demo scheduling tool
Route::get('/test-demo-tool', function () {
    $tool = new \App\AI\Tools\ScheduleDemo();

    // // Test with no parameters (should show options)
    // $result1 = $tool();

    // // Test with missing info (should ask for missing info)
    // $result2 = $tool(name: 'John Doe');

    // Test with complete info (should attempt to schedule)
    $result3 = $tool(
        name: 'John Doe',
        email: 'john@example.com',
        demo_type: 'general',
        preferred_datetime: '2025-05-30 14:00',
        timezone: 'Africa/Kampala'
    );

    return response()->json([
        // 'no_params' => $result1,
        // 'missing_info' => $result2,
        'complete_info' => $result3
    ]);
});

require __DIR__.'/client.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/stripe.php';
