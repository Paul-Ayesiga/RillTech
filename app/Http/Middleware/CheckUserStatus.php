<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * User status constants for better maintainability
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_BANNED = 'banned';

    /**
     * Status messages for user feedback
     */
    private const STATUS_MESSAGES = [
        self::STATUS_SUSPENDED => 'Your account has been suspended. Please contact support for assistance.',
        self::STATUS_BANNED => 'Your account has been permanently banned. Please contact support if you believe this is an error.',
    ];

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
        $status = $user->status ?? self::STATUS_ACTIVE;

        // Allow active users to proceed
        if ($status === self::STATUS_ACTIVE) {
            return $next($request);
        }

        // Handle inactive users
        return $this->handleInactiveUser($request, $user, $status);
    }

    /**
     * Handle users with inactive status (suspended or banned)
     *
     * @param Request $request
     * @param \App\Models\User $user
     * @param string $status
     * @return Response
     */
    private function handleInactiveUser(Request $request, $user, string $status): Response
    {
        // Log the access attempt for security monitoring
        $this->logAccessAttempt($user, $status, $request);

        // Logout the user
        Auth::logout();

        // Invalidate the session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Handle different request types appropriately
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiResponse($status);
        }

        return $this->handleWebResponse($request, $status);
    }

    /**
     * Handle API responses for inactive users
     *
     * @param string $status
     * @return Response
     */
    private function handleApiResponse(string $status): Response
    {
        $message = self::STATUS_MESSAGES[$status] ?? 'Account access denied.';

        return response()->json([
            'message' => $message,
            'status' => $status,
            'error' => 'account_inactive'
        ], 403);
    }

    /**
     * Handle web responses for inactive users
     *
     * @param Request $request
     * @param string $status
     * @return Response
     */
    private function handleWebResponse(Request $request, string $status): Response
    {
        $message = self::STATUS_MESSAGES[$status] ?? 'Account access denied.';

        return redirect()->route('login')
            ->withErrors(['status' => $message])
            ->withInput($request->only('email'));
    }

    /**
     * Log access attempts from inactive users for security monitoring
     *
     * @param \App\Models\User $user
     * @param string $status
     * @param Request $request
     * @return void
     */
    private function logAccessAttempt($user, string $status, Request $request): void
    {
        Log::warning('Access attempt by inactive user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => $status,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check if a status is considered inactive
     *
     * @param string $status
     * @return bool
     */
    public static function isInactiveStatus(string $status): bool
    {
        return in_array($status, [self::STATUS_SUSPENDED, self::STATUS_BANNED]);
    }

    /**
     * Get user-friendly message for a status
     *
     * @param string $status
     * @return string
     */
    public static function getStatusMessage(string $status): string
    {
        return self::STATUS_MESSAGES[$status] ?? 'Account status unknown.';
    }
}
