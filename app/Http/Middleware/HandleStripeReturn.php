<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HandleStripeReturn
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a return from Stripe checkout
        if ($request->query('subscription_success') === 'true' && $request->query('session_id')) {
            $sessionId = $request->query('session_id');

            // Log the return for debugging
            Log::info('User returned from Stripe checkout', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'url' => $request->fullUrl()
            ]);

            // Set a session flag to temporarily bypass subscription checks
            session(['stripe_return' => true, 'stripe_session_id' => $sessionId]);

            // Try to sync subscription data from Stripe
            if (Auth::check()) {
                $user = Auth::user();
                try {
                    // Retrieve the checkout session from Stripe to get subscription info
                    $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                    $session = $stripe->checkout->sessions->retrieve($sessionId);

                    if ($session->subscription) {
                        // Sync the specific subscription from Stripe
                        $subscription = $stripe->subscriptions->retrieve($session->subscription);

                        // Create or update the subscription in our database
                        $subscriptionModel = $user->subscriptions()->updateOrCreate(
                            ['stripe_id' => $subscription->id],
                            [
                                'type' => 'default',
                                'stripe_status' => $subscription->status,
                                'stripe_price' => $subscription->items->data[0]->price->id,
                                'quantity' => $subscription->items->data[0]->quantity,
                                'trial_ends_at' => $subscription->trial_end ? \Carbon\Carbon::createFromTimestamp($subscription->trial_end) : null,
                                'ends_at' => null,
                                'created_at' => \Carbon\Carbon::createFromTimestamp($subscription->created),
                                'updated_at' => now(),
                            ]
                        );

                        // Also create subscription items if they don't exist
                        foreach ($subscription->items->data as $item) {
                            $subscriptionModel->items()->updateOrCreate(
                                ['stripe_id' => $item->id],
                                [
                                    'stripe_product' => $item->price->product,
                                    'stripe_price' => $item->price->id,
                                    'quantity' => $item->quantity,
                                    'created_at' => \Carbon\Carbon::createFromTimestamp($item->created),
                                    'updated_at' => now(),
                                ]
                            );
                        }

                        Log::info('Successfully synced subscription from Stripe', [
                            'user_id' => $user->id,
                            'session_id' => $sessionId,
                            'subscription_id' => $subscription->id,
                            'status' => $subscription->status
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not sync subscription data from Stripe', [
                        'user_id' => $user->id,
                        'session_id' => $sessionId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $next($request);
    }
}
