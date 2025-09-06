<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripeSessionRecoveryService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Attempt to recover user authentication after Stripe checkout
     *
     * @param string $sessionId
     * @return User|null
     */
    public function recoverUserAuthentication(string $sessionId): ?User
    {
        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
            $user = $this->findUserFromSession($session);

            if ($user) {
                $this->authenticateUser($user, $sessionId);
                return $user;
            }

            Log::warning('Could not find user for Stripe session recovery', [
                'session_id' => $sessionId,
                'customer_id' => $session->customer ?? null,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to recover user authentication from Stripe session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find user from Stripe session using multiple methods
     *
     * @param \Stripe\Checkout\Session $session
     * @return User|null
     */
    protected function findUserFromSession($session): ?User
    {
        // Method 1: Try user ID from session metadata
        if (isset($session->metadata->user_id)) {
            $user = User::find($session->metadata->user_id);
            if ($user) {
                Log::info('User found via session metadata', [
                    'user_id' => $user->id,
                    'method' => 'metadata'
                ]);
                return $user;
            }
        }

        // Method 2: Try user ID from Laravel session
        if (session('checkout_user_id')) {
            $user = User::find(session('checkout_user_id'));
            if ($user) {
                Log::info('User found via Laravel session', [
                    'user_id' => $user->id,
                    'method' => 'laravel_session'
                ]);
                return $user;
            }
        }

        // Method 3: Try to find user by Stripe customer ID
        if ($session->customer) {
            $user = User::where('stripe_id', $session->customer)->first();
            if ($user) {
                Log::info('User found via Stripe customer ID', [
                    'user_id' => $user->id,
                    'stripe_customer_id' => $session->customer,
                    'method' => 'stripe_customer'
                ]);
                return $user;
            }
        }

        // Method 4: Try to find user by customer email
        if (isset($session->customer_details->email)) {
            $user = User::where('email', $session->customer_details->email)->first();
            if ($user) {
                Log::info('User found via customer email', [
                    'user_id' => $user->id,
                    'email' => $session->customer_details->email,
                    'method' => 'customer_email'
                ]);
                return $user;
            }
        }

        return null;
    }

    /**
     * Authenticate the recovered user
     *
     * @param User $user
     * @param string $sessionId
     */
    protected function authenticateUser(User $user, string $sessionId): void
    {
        // Log the user back in with "remember" functionality
        Auth::login($user, true);

        // Regenerate session ID to prevent session fixation
        session()->regenerate();

        // Clear any checkout session data as it's no longer needed
        session()->forget(['checkout_user_id', 'just_registered', 'just_logged_in']);

        // Set a flag indicating successful recovery
        session(['authentication_recovered' => true]);

        Log::info('Successfully recovered user authentication after Stripe checkout', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'session_id' => $sessionId,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Sync subscription data from Stripe session
     *
     * @param string $sessionId
     * @param User|null $user
     * @return bool
     */
    public function syncSubscriptionData(string $sessionId, ?User $user = null): bool
    {
        if (!$user && !Auth::check()) {
            Log::warning('Cannot sync subscription data: no authenticated user', [
                'session_id' => $sessionId
            ]);
            return false;
        }

        $user = $user ?: Auth::user();

        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            if (!$session->subscription) {
                Log::info('No subscription found in Stripe session', [
                    'session_id' => $sessionId,
                    'user_id' => $user->id
                ]);
                return false;
            }

            $subscription = $this->stripe->subscriptions->retrieve($session->subscription);

            // Create or update subscription in database
            $subscriptionModel = $user->subscriptions()->updateOrCreate(
                ['stripe_id' => $subscription->id],
                [
                    'type' => 'default',
                    'stripe_status' => $subscription->status,
                    'stripe_price' => $subscription->items->data[0]->price->id,
                    'quantity' => $subscription->items->data[0]->quantity,
                    'trial_ends_at' => $subscription->trial_end
                        ? \Carbon\Carbon::createFromTimestamp($subscription->trial_end)
                        : null,
                    'ends_at' => null,
                    'created_at' => \Carbon\Carbon::createFromTimestamp($subscription->created),
                    'updated_at' => now(),
                ]
            );

            // Create subscription items
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

            Log::info('Successfully synced subscription data from Stripe', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'subscription_id' => $subscription->id,
                'subscription_status' => $subscription->status,
                'price_id' => $subscription->items->data[0]->price->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to sync subscription data from Stripe', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Check if a session ID is valid and belongs to a successful checkout
     *
     * @param string $sessionId
     * @return bool
     */
    public function isValidSession(string $sessionId): bool
    {
        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
            return $session->payment_status === 'paid' || $session->status === 'complete';
        } catch (\Exception $e) {
            Log::warning('Invalid or expired Stripe session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get session details for success page
     *
     * @param string $sessionId
     * @return array|null
     */
    public function getSessionDetails(string $sessionId): ?array
    {
        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            return [
                'id' => $session->id,
                'payment_status' => $session->payment_status,
                'status' => $session->status,
                'amount_total' => $session->amount_total,
                'currency' => $session->currency,
                'customer_email' => $session->customer_details->email ?? null,
                'subscription_id' => $session->subscription ?? null,
                'created' => $session->created,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to retrieve session details', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Handle the complete checkout success flow
     *
     * @param string $sessionId
     * @return array
     */
    public function handleCheckoutSuccess(string $sessionId): array
    {
        $result = [
            'success' => false,
            'user_recovered' => false,
            'subscription_synced' => false,
            'message' => 'Unknown error occurred',
        ];

        // Validate session
        if (!$this->isValidSession($sessionId)) {
            $result['message'] = 'Invalid or expired checkout session';
            return $result;
        }

        // Recover user authentication if needed
        if (!Auth::check()) {
            $user = $this->recoverUserAuthentication($sessionId);
            if ($user) {
                $result['user_recovered'] = true;
            } else {
                $result['message'] = 'Could not recover user authentication';
                return $result;
            }
        }

        // Sync subscription data
        $syncSuccess = $this->syncSubscriptionData($sessionId);
        if ($syncSuccess) {
            $result['subscription_synced'] = true;
        }

        $result['success'] = true;
        $result['message'] = 'Subscription activated successfully!';

        return $result;
    }
}
