<?php

namespace App\Http\Controllers;

use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class StripeWebhookController extends CashierController
{
    /**
     * Handle customer subscription created.
     */
    public function handleCustomerSubscriptionCreated(array $payload)
    {
        Log::info('Stripe webhook: customer.subscription.created', $payload);

        $subscription = $payload['data']['object'];
        $customerId = $subscription['customer'];

        // Find user by Stripe customer ID
        $user = User::where('stripe_id', $customerId)->first();

        if ($user) {
            // Create subscription record
            $user->subscriptions()->updateOrCreate(
                ['stripe_id' => $subscription['id']],
                [
                    'type' => 'default',
                    'stripe_status' => $subscription['status'],
                    'stripe_price' => $subscription['items']['data'][0]['price']['id'],
                    'quantity' => $subscription['items']['data'][0]['quantity'],
                    'trial_ends_at' => $subscription['trial_end'] ? \Carbon\Carbon::createFromTimestamp($subscription['trial_end']) : null,
                    'ends_at' => null,
                    'created_at' => \Carbon\Carbon::createFromTimestamp($subscription['created']),
                    'updated_at' => now(),
                ]
            );

            Log::info('Subscription created for user', [
                'user_id' => $user->id,
                'subscription_id' => $subscription['id'],
                'status' => $subscription['status']
            ]);
        } else {
            Log::warning('User not found for Stripe customer', ['customer_id' => $customerId]);
        }

        return parent::handleCustomerSubscriptionCreated($payload);
    }

    /**
     * Handle customer subscription updated.
     */
    public function handleCustomerSubscriptionUpdated(array $payload)
    {
        Log::info('Stripe webhook: customer.subscription.updated', $payload);

        $subscription = $payload['data']['object'];
        $customerId = $subscription['customer'];

        // Find user by Stripe customer ID
        $user = User::where('stripe_id', $customerId)->first();

        if ($user) {
            // Update subscription record
            $user->subscriptions()->where('stripe_id', $subscription['id'])->update([
                'stripe_status' => $subscription['status'],
                'stripe_price' => $subscription['items']['data'][0]['price']['id'],
                'quantity' => $subscription['items']['data'][0]['quantity'],
                'trial_ends_at' => $subscription['trial_end'] ? \Carbon\Carbon::createFromTimestamp($subscription['trial_end']) : null,
                'ends_at' => $subscription['canceled_at'] ? \Carbon\Carbon::createFromTimestamp($subscription['canceled_at']) : null,
                'updated_at' => now(),
            ]);

            Log::info('Subscription updated for user', [
                'user_id' => $user->id,
                'subscription_id' => $subscription['id'],
                'status' => $subscription['status']
            ]);
        }

        return parent::handleCustomerSubscriptionUpdated($payload);
    }

    /**
     * Handle customer subscription deleted.
     */
    public function handleCustomerSubscriptionDeleted(array $payload)
    {
        Log::info('Stripe webhook: customer.subscription.deleted', $payload);

        $subscription = $payload['data']['object'];
        $customerId = $subscription['customer'];

        // Find user by Stripe customer ID
        $user = User::where('stripe_id', $customerId)->first();

        if ($user) {
            // Mark subscription as ended
            $user->subscriptions()->where('stripe_id', $subscription['id'])->update([
                'stripe_status' => 'canceled',
                'ends_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Subscription deleted for user', [
                'user_id' => $user->id,
                'subscription_id' => $subscription['id']
            ]);
        }

        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    /**
     * Handle checkout session completed.
     */
    public function handleCheckoutSessionCompleted(array $payload)
    {
        Log::info('Stripe webhook: checkout.session.completed', $payload);

        $session = $payload['data']['object'];
        $customerId = $session['customer'];

        // Find user by Stripe customer ID
        $user = User::where('stripe_id', $customerId)->first();

        if ($user && isset($session['subscription'])) {
            Log::info('Checkout session completed for user', [
                'user_id' => $user->id,
                'session_id' => $session['id'],
                'subscription_id' => $session['subscription']
            ]);

            // The subscription will be handled by the subscription.created webhook
            // This is just for logging purposes
        }

        return response('Webhook handled', 200);
    }
}
