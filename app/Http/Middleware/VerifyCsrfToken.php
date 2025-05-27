<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/stripe/webhook', // Stripe webhooks don't include CSRF tokens
        '/api/chat', // Chat API endpoints for widget
        '/api/chat/stream', // Chat streaming API endpoints
    ];
}
