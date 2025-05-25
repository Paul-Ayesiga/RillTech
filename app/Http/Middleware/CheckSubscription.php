<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check if user is not authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip check for super-admin and admin users
        if ($user->hasRole(['super-admin', 'admin'])) {
            return $next($request);
        }

        // Allow access if coming back from successful subscription
        if ($request->query('subscription_success') === 'true' || session('stripe_return')) {
            // Clear the stripe return flag after first use
            if (session('stripe_return')) {
                session()->forget(['stripe_return', 'stripe_session_id']);
            }
            return $next($request);
        }

        // Allow access if user has active subscription or is on trial
        if ($user->subscribed('default') || $user->onGenericTrial()) {
            return $next($request);
        }

        // User needs subscription - determine smart routing
        return $this->handleSubscriptionRequired($request, $user);
    }

    /**
     * Handle users who need a subscription with smart routing logic
     */
    private function handleSubscriptionRequired(Request $request, $user): Response
    {
        // Determine the appropriate redirect URL and message
        $redirectData = $this->determineRedirectStrategy($request, $user);

        // Handle JSON requests (API calls)
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $redirectData['message'],
                'redirect' => $redirectData['url']
            ], 402);
        }

        // Handle web requests
        return redirect($redirectData['url'])
            ->with('subscription_message', $redirectData['message'])
            ->with('subscription_context', $redirectData['context']);
    }

    /**
     * Determine the redirect strategy based on user state and request context
     */
    private function determineRedirectStrategy(Request $request, $user): array
    {
        // Check if user just registered
        if (session('just_registered') || $request->query('from') === 'registration') {
            session()->forget('just_registered');
            return [
                'url' => '/#pricing',
                'message' => 'Welcome! Please choose a subscription plan to get started.',
                'context' => 'new_user'
            ];
        }

        // Check if user just logged in
        if (session('just_logged_in')) {
            session()->forget('just_logged_in');
            return [
                'url' => '/#pricing',
                'message' => 'Welcome back! Please choose a subscription plan to continue.',
                'context' => 'returning_user'
            ];
        }

        // Check if user has expired subscription
        if ($this->hasExpiredSubscription($user)) {
            return [
                'url' => '/#pricing',
                'message' => 'Your subscription has expired. Please renew to continue using your dashboard.',
                'context' => 'expired'
            ];
        }

        // Check if user has cancelled subscription
        if ($this->hasCancelledSubscription($user)) {
            return [
                'url' => '/#pricing',
                'message' => 'Your subscription was cancelled. Please subscribe again to access your dashboard.',
                'context' => 'cancelled'
            ];
        }

        // Default case - user never had subscription
        return [
            'url' => '/#pricing',
            'message' => 'Please choose a subscription plan to access your dashboard.',
            'context' => 'no_subscription'
        ];
    }

    /**
     * Check if user has an expired subscription
     */
    private function hasExpiredSubscription($user): bool
    {
        $subscription = $user->subscription('default');

        if (!$subscription) {
            return false;
        }

        return $subscription->ended() && !$subscription->onGracePeriod();
    }

    /**
     * Check if user has a cancelled subscription
     */
    private function hasCancelledSubscription($user): bool
    {
        $subscription = $user->subscription('default');

        if (!$subscription) {
            return false;
        }

        return $subscription->cancelled() || $subscription->onGracePeriod();
    }
}
