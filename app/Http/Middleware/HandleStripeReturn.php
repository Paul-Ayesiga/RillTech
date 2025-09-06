<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Services\StripeSessionRecoveryService;

class HandleStripeReturn
{
    protected StripeSessionRecoveryService $recoveryService;

    public function __construct(StripeSessionRecoveryService $recoveryService)
    {
        $this->recoveryService = $recoveryService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a return from Stripe checkout
        if ($request->query('session_id')) {
            $sessionId = $request->query('session_id');

            // Log the return for debugging
            Log::info('User returned from Stripe checkout', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'url' => $request->fullUrl(),
                'is_authenticated' => Auth::check()
            ]);

            // Handle user re-authentication if needed
            if (!Auth::check()) {
                $user = $this->recoveryService->recoverUserAuthentication($sessionId);
                if ($user) {
                    Log::info('User authentication recovered by middleware', [
                        'user_id' => $user->id,
                        'session_id' => $sessionId
                    ]);
                }
            }

            // Set a session flag to temporarily bypass subscription checks
            session(['stripe_return' => true, 'stripe_session_id' => $sessionId]);

            // Try to sync subscription data from Stripe
            if (Auth::check()) {
                $this->recoveryService->syncSubscriptionData($sessionId);
            }
        }

        return $next($request);
    }
}
