<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Events\userSubcribedToNewsletter;

class NewsletterController extends Controller
{
    /**
     * Subscribe to newsletter
     */
    public function subscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'name' => 'nullable|string|max:255',
                'source' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please provide a valid email address.',
                        'errors' => $validator->errors()
                    ], 422);
                }

                return back()->withErrors($validator)->withInput();
            }

            $validated = $validator->validated();

            // Check if already subscribed
            $existingSubscription = NewsletterSubscription::where('email', $validated['email'])->first();

            if ($existingSubscription) {
                if ($existingSubscription->isActive()) {
                    $message = 'You are already subscribed to our newsletter!';
                } else {
                    // Reactivate subscription
                    $existingSubscription->resubscribe();
                    $message = 'Welcome back! Your newsletter subscription has been reactivated.';
                }
            } else {
                // Create new subscription
                $newsletter = NewsletterSubscription::create([
                    'email' => $validated['email'],
                    'name' => $validated['name'] ?? null,
                    'source' => $validated['source'] ?? 'website',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $message = 'Thank you for subscribing to our newsletter!';
                event(new userSubcribedToNewsletter($newsletter));
            }

            // Log subscription for analytics
            Log::info('Newsletter subscription', [
                'email' => $validated['email'],
                'source' => $validated['source'] ?? 'website',
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Newsletter subscription error: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = 'Sorry, there was an error processing your subscription. Please try again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->withErrors(['error' => $errorMessage]);
        }
    }

    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe(Request $request, $token)
    {
        try {
            $subscription = NewsletterSubscription::where('unsubscribe_token', $token)->first();

            if (!$subscription) {
                return view('newsletter.unsubscribe-error', [
                    'message' => 'Invalid unsubscribe link. Please contact support if you continue to receive emails.'
                ]);
            }

            if ($subscription->status === 'unsubscribed') {
                return view('newsletter.unsubscribe-success', [
                    'message' => 'You have already been unsubscribed from our newsletter.',
                    'email' => $subscription->email
                ]);
            }

            $subscription->unsubscribe();

            Log::info('Newsletter unsubscription', [
                'email' => $subscription->email,
                'ip' => $request->ip(),
            ]);

            return view('newsletter.unsubscribe-success', [
                'message' => 'You have been successfully unsubscribed from our newsletter.',
                'email' => $subscription->email
            ]);

        } catch (\Exception $e) {
            Log::error('Newsletter unsubscription error: ' . $e->getMessage(), [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return view('newsletter.unsubscribe-error', [
                'message' => 'Sorry, there was an error processing your unsubscription. Please contact support.'
            ]);
        }
    }

    /**
     * Show unsubscribe confirmation page
     */
    public function showUnsubscribe($token)
    {
        $subscription = NewsletterSubscription::where('unsubscribe_token', $token)->first();

        if (!$subscription) {
            return view('newsletter.unsubscribe-error', [
                'message' => 'Invalid unsubscribe link.'
            ]);
        }

        return view('newsletter.unsubscribe-confirm', [
            'subscription' => $subscription,
            'token' => $token
        ]);
    }
}
