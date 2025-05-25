<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessBulkInvoiceDownload;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }



    /**
     * Create checkout session
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'price_id' => 'required|string'
        ]);

        $user = Auth::user();
        $priceId = $request->input('price_id');

        try {
            // Create or get Stripe customer
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer();
            }

            // Create checkout session with proper URLs
            $successUrl = config('app.url') . route('dashboard', [], false) . '?subscription_success=true&session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = config('app.url') . '/#pricing';

            Log::info('Creating checkout session', [
                'user_id' => $user->id,
                'price_id' => $priceId,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl
            ]);

            $checkoutSession = $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ]);

            return response()->json([
                'checkout_url' => $checkoutSession->url
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating checkout session: ' . $e->getMessage());

            return response()->json([
                'error' => 'Unable to create checkout session. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle successful subscription
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('subscription.plans')
                ->with('error', 'Invalid session.');
        }

        try {
            // Retrieve the session from Stripe
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            return Inertia::render('client/subscription/Success', [
                'session' => $session
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving checkout session: ' . $e->getMessage());

            return redirect()->route('dashboard')
                ->with('success', 'Subscription activated successfully!');
        }
    }

    /**
     * Access billing portal - Laravel Cashier Method
     */
    public function billingPortal()
    {
        $user = Auth::user();

        try {
            // Ensure user has a Stripe customer ID
            if (!$user->hasStripeId()) {
                // Create Stripe customer if doesn't exist
                $user->createAsStripeCustomer();
            }

            // Create customer-specific billing portal session using Laravel Cashier
            // This provides direct access without email authentication
            return $user->redirectToBillingPortal(route('dashboard'));

        } catch (\Exception $e) {
            Log::error('Error accessing billing portal: ' . $e->getMessage());

            return redirect()->route('subscriptions.index')
                ->with('error', 'Unable to access billing portal. Please try again.');
        }
    }



    /**
     * Cancel subscription with different options
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'cancel_type' => 'required|in:end_of_period,immediately,immediately_and_invoice,at_date',
            'cancel_at' => 'nullable|date|after:now', // Required if cancel_type is 'at_date'
        ]);

        $user = Auth::user();
        $subscription = $user->subscription('default');

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription found.'], 404);
        }

        try {
            $cancelType = $request->cancel_type;
            $message = '';

            switch ($cancelType) {
                case 'end_of_period':
                    // Cancel at end of billing period (grace period)
                    $subscription->cancel();
                    $message = 'Subscription canceled successfully. You can continue using the service until the end of your billing period.';
                    break;

                case 'immediately':
                    // Cancel immediately
                    $subscription->cancelNow();
                    $message = 'Subscription canceled immediately. Access has been revoked.';
                    break;

                case 'immediately_and_invoice':
                    // Cancel immediately and invoice any remaining usage
                    $subscription->cancelNowAndInvoice();
                    $message = 'Subscription canceled immediately with final invoice generated.';
                    break;

                case 'at_date':
                    // Cancel at specific date
                    if (!$request->cancel_at) {
                        return response()->json(['error' => 'Cancel date is required for scheduled cancellation.'], 422);
                    }
                    $subscription->cancelAt(\Carbon\Carbon::parse($request->cancel_at));
                    $message = 'Subscription scheduled for cancellation on ' . \Carbon\Carbon::parse($request->cancel_at)->format('M j, Y');
                    break;
            }

            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            Log::error('Error canceling subscription: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to cancel subscription.'], 500);
        }
    }

    /**
     * Resume subscription with grace period validation
     */
    public function resume()
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');

        if (!$subscription) {
            return response()->json(['error' => 'No subscription found.'], 404);
        }

        if (!$subscription->canceled()) {
            return response()->json(['error' => 'Subscription is not canceled.'], 422);
        }

        // Check if subscription is still within grace period
        if (!$subscription->onGracePeriod()) {
            return response()->json([
                'error' => 'Subscription cannot be resumed as the grace period has expired. Please create a new subscription.'
            ], 422);
        }

        try {
            $subscription->resume();

            return response()->json([
                'message' => 'Subscription resumed successfully. You will be billed on your original billing cycle.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error resuming subscription: ' . $e->getMessage());

            return response()->json([
                'error' => 'Unable to resume subscription. Please try again.'
            ], 500);
        }
    }

    /**
     * Subscription management dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $subscription = $user->subscription('default');
        $subscriptionData = null;
        $availablePlans = [];

        if ($subscription) {
            try {
                // Get subscription details from Stripe
                $stripeSubscription = $this->stripe->subscriptions->retrieve(
                    $subscription->stripe_id,
                    ['expand' => ['items.data.price.product']]
                );

                $subscriptionData = [
                    'id' => $subscription->id,
                    'stripe_id' => $subscription->stripe_id,
                    'status' => $subscription->stripe_status,
                    'current_period_start' => date('Y-m-d H:i:s', $stripeSubscription->current_period_start),
                    'current_period_end' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                    'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end ?? false,
                    'product_name' => $stripeSubscription->items->data[0]->price->product->name ?? 'Unknown',
                    'amount' => $stripeSubscription->items->data[0]->price->unit_amount ?? 0,
                    'currency' => $stripeSubscription->items->data[0]->price->currency ?? 'usd',
                    'interval' => $stripeSubscription->items->data[0]->price->recurring->interval ?? 'month',
                    'current_price_id' => $stripeSubscription->items->data[0]->price->id,
                    // Add subscription status information
                    'is_cancelled' => $subscription->canceled(),
                    'on_grace_period' => $subscription->onGracePeriod(),
                    'ends_at' => $subscription->ends_at ? $subscription->ends_at->toDateTimeString() : null,
                ];

                // Get available plans for plan changes
                $products = $this->stripe->products->all(['active' => true]);
                foreach ($products->data as $product) {
                    $prices = $this->stripe->prices->all([
                        'product' => $product->id,
                        'active' => true
                    ]);

                    foreach ($prices->data as $price) {
                        $availablePlans[] = [
                            'price_id' => $price->id,
                            'product_name' => $product->name,
                            'amount' => $price->unit_amount,
                            'currency' => $price->currency,
                            'interval' => $price->recurring->interval ?? null,
                            'is_current' => $price->id === ($subscriptionData['current_price_id'] ?? ''),
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error fetching subscription details: ' . $e->getMessage());

                // Provide fallback subscription data from database
                $subscriptionData = [
                    'id' => $subscription->id,
                    'stripe_id' => $subscription->stripe_id,
                    'status' => $subscription->stripe_status,
                    'current_period_start' => $subscription->created_at->toDateTimeString(),
                    'current_period_end' => $subscription->ends_at ? $subscription->ends_at->toDateTimeString() : now()->addMonth()->toDateTimeString(),
                    'cancel_at_period_end' => $subscription->canceled(),
                    'product_name' => 'Subscription Plan',
                    'amount' => 0,
                    'currency' => 'usd',
                    'interval' => 'month',
                    'current_price_id' => '',
                    // Add subscription status information
                    'is_cancelled' => $subscription->canceled(),
                    'on_grace_period' => $subscription->onGracePeriod(),
                    'ends_at' => $subscription->ends_at ? $subscription->ends_at->toDateTimeString() : null,
                ];
            }
        }

        // Debug information
        Log::info('Subscription Debug Info', [
            'user_id' => $user->id,
            'subscription_exists' => $subscription ? true : false,
            'subscription_status' => $subscription ? $subscription->stripe_status : 'none',
            'subscription_data_exists' => $subscriptionData ? true : false,
            'has_active_subscription' => $subscription && in_array($subscription->stripe_status, ['active', 'trialing', 'past_due'])
        ]);

        return Inertia::render('client/Subscriptions', [
            'subscription' => $subscriptionData,
            'availablePlans' => $availablePlans,
            'user' => $user,
            'hasActiveSubscription' => $subscription && in_array($subscription->stripe_status, ['active', 'trialing', 'past_due'])
        ]);
    }

    /**
     * Change subscription plan
     */
    public function changePlan(Request $request)
    {
        $request->validate([
            'price_id' => 'required|string'
        ]);

        $user = Auth::user();
        $subscription = $user->subscription('default');

        if (!$subscription) {
            return response()->json(['error' => 'No active subscription found.'], 404);
        }

        try {
            $subscription->swap($request->price_id);

            return response()->json(['message' => 'Plan changed successfully.']);
        } catch (\Exception $e) {
            Log::error('Error changing subscription plan: ' . $e->getMessage());

            return response()->json([
                'error' => 'Unable to change plan. Please try again.'
            ], 500);
        }
    }

    /**
     * Get invoices using Laravel Cashier
     */
    public function invoices()
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            return response()->json(['invoices' => []]);
        }

        try {
            // Fetch all types of invoices
            $allInvoices = collect();
            $upcomingInvoiceIds = collect();

            // 1. Get all past and pending invoices (these are NOT upcoming)
            $pastAndPendingInvoices = $user->invoicesIncludingPending();
            $allInvoices = $allInvoices->merge($pastAndPendingInvoices);

            // 2. Get upcoming invoice if it exists and track its ID
            try {
                $upcomingInvoice = $user->upcomingInvoice();
                if ($upcomingInvoice) {
                    $upcomingInvoiceIds->push($upcomingInvoice->id);
                    $allInvoices->push($upcomingInvoice);
                }
            } catch (\Exception $e) {
                // Upcoming invoice might not exist, which is fine
                Log::info('No upcoming invoice found for user: ' . $user->id);
            }

            $formattedInvoices = $allInvoices->map(function ($invoice) use ($upcomingInvoiceIds) {
                // An invoice is upcoming if:
                // 1. It's in our upcoming invoice IDs collection, OR
                // 2. It has status 'draft' and no invoice number
                $isUpcoming = $upcomingInvoiceIds->contains($invoice->id) ||
                             ($invoice->status === 'draft' && !$invoice->number);

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number ?? 'Upcoming Invoice',
                    'amount_paid' => $invoice->total(), // Use Cashier's total() method
                    'amount_due' => $invoice->amountDue(),
                    'currency' => strtoupper($invoice->currency),
                    'status' => $invoice->status,
                    'date' => $invoice->date()->toFormattedDateString(), // Use Cashier's date() method
                    'created' => $invoice->date()->timestamp,
                    'due_date' => $invoice->dueDate() ? $invoice->dueDate()->timestamp : null,
                    'hosted_invoice_url' => $invoice->hostedInvoiceUrl ?? null,
                    'invoice_pdf' => $invoice->invoice_pdf ?? null,
                    'is_upcoming' => $isUpcoming,
                ];
            })->sortByDesc('created'); // Sort by creation date, newest first

            return response()->json(['invoices' => $formattedInvoices->values()->toArray()]);
        } catch (\Exception $e) {
            Log::error('Error fetching invoices: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch invoices.'], 500);
        }
    }

    /**
     * Download single invoice using Laravel Cashier
     */
    public function downloadInvoice($invoiceId)
    {
        $user = Auth::user();

        try {
            // Use Laravel Cashier to find the invoice
            $invoice = $user->findInvoice($invoiceId);

            if (!$invoice) {
                abort(404, 'Invoice not found.');
            }

            // Use Cashier's download method
            return $invoice->download();
        } catch (\Exception $e) {
            Log::error('Error downloading invoice: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to download invoice.');
        }
    }

    /**
     * Process bulk download invoices with chunked processing
     */
    public function bulkDownloadInvoices(Request $request)
    {
        $request->validate([
            'invoice_ids' => 'required|array|min:1|max:50', // Limit to 50 invoices
            'invoice_ids.*' => 'required|string',
            'chunk_size' => 'sometimes|integer|min:1|max:10' // Optional chunk size
        ]);

        $user = Auth::user();
        $invoiceIds = $request->input('invoice_ids');
        $chunkSize = $request->input('chunk_size', 5); // Default to 5 invoices per chunk

        if (!$user->hasStripeId()) {
            return response()->json(['error' => 'No Stripe customer found.'], 404);
        }

        // For small batches (≤10), process synchronously
        if (count($invoiceIds) <= 10) {
            return $this->processBulkDownloadSync($user, $invoiceIds);
        }

        // For large batches (>10), use background job
        try {
            // Generate unique download ID
            $downloadId = Str::uuid()->toString();

            // Dispatch the background job
            ProcessBulkInvoiceDownload::dispatch($user, $invoiceIds, $downloadId);

            Log::info('Large bulk download job dispatched', [
                'user_id' => $user->id,
                'download_id' => $downloadId,
                'invoice_count' => count($invoiceIds)
            ]);

            return response()->json([
                'message' => 'Large bulk download started. Processing in background...',
                'download_id' => $downloadId,
                'status' => 'processing',
                'invoice_count' => count($invoiceIds),
                'use_polling' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error initiating large bulk download: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'invoice_ids' => $invoiceIds
            ]);
            return response()->json(['error' => 'Unable to start bulk download.'], 500);
        }
    }

    /**
     * Process small bulk downloads synchronously
     */
    private function processBulkDownloadSync($user, $invoiceIds)
    {
        try {
            Log::info('Processing small bulk download synchronously', [
                'user_id' => $user->id,
                'invoice_count' => count($invoiceIds)
            ]);

            $downloadUrls = [];
            $successCount = 0;
            $failureCount = 0;

            // Process each invoice
            foreach ($invoiceIds as $invoiceId) {
                try {
                    $invoice = $user->findInvoice($invoiceId);

                    if (!$invoice) {
                        Log::warning("Invoice not found during bulk download", [
                            'user_id' => $user->id,
                            'invoice_id' => $invoiceId
                        ]);
                        $failureCount++;
                        continue;
                    }

                    if ($invoice->invoice_pdf) {
                        // Ensure amount is numeric before division
                        $totalAmount = is_numeric($invoice->total()) ? (float)$invoice->total() : 0;

                        $downloadUrls[] = [
                            'id' => $invoice->id,
                            'number' => $invoice->number ?? "Invoice {$invoice->id}",
                            'url' => $invoice->invoice_pdf,
                            'date' => $invoice->date()->toFormattedDateString(),
                            'amount' => $totalAmount / 100,
                            'currency' => strtoupper($invoice->currency)
                        ];

                        $successCount++;

                        Log::info("Invoice processed for bulk download", [
                            'invoice_id' => $invoiceId,
                            'invoice_number' => $invoice->number
                        ]);
                    } else {
                        Log::warning("Invoice has no PDF available", [
                            'invoice_id' => $invoiceId,
                            'invoice_number' => $invoice->number ?? 'No number'
                        ]);
                        $failureCount++;
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing invoice in bulk download", [
                        'invoice_id' => $invoiceId,
                        'error' => $e->getMessage()
                    ]);
                    $failureCount++;
                    continue;
                }
            }

            if (empty($downloadUrls)) {
                return response()->json([
                    'error' => 'No downloadable invoices found. Some invoices may not have PDFs available yet.'
                ], 404);
            }

            Log::info('Small bulk download completed', [
                'user_id' => $user->id,
                'success_count' => $successCount,
                'failure_count' => $failureCount
            ]);

            return response()->json([
                'download_urls' => $downloadUrls,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total_requested' => count($invoiceIds),
                'message' => 'Bulk download completed successfully.',
                'use_polling' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Error in small bulk download: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'invoice_ids' => $invoiceIds
            ]);
            return response()->json(['error' => 'Unable to process bulk download.'], 500);
        }
    }

    /**
     * Check the status of a bulk download job
     */
    public function checkBulkDownloadStatus($downloadId)
    {
        $user = Auth::user();

        try {
            // Retrieve the download result from cache
            $result = cache()->get("bulk_download_{$downloadId}");

            if (!$result) {
                return response()->json(['error' => 'Download not found or expired.'], 404);
            }

            // Verify the download belongs to the current user
            if ($result['user_id'] !== $user->id) {
                return response()->json(['error' => 'Unauthorized access to download.'], 403);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error checking bulk download status: ' . $e->getMessage(), [
                'download_id' => $downloadId,
                'user_id' => $user->id
            ]);
            return response()->json(['error' => 'Unable to check download status.'], 500);
        }
    }

    /**
     * Get payment methods using proper Laravel Cashier methods
     */
    public function paymentMethods()
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            return response()->json(['payment_methods' => []]);
        }

        try {
            // Use Laravel Cashier's paymentMethods() method - this returns a Collection
            $paymentMethods = $user->paymentMethods();

            // Use Laravel Cashier's defaultPaymentMethod() method
            $defaultPaymentMethod = $user->defaultPaymentMethod();

            $formattedMethods = $paymentMethods->map(function ($method) use ($defaultPaymentMethod) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'card' => [
                        'brand' => $method->card->brand,
                        'last4' => $method->card->last4,
                        'exp_month' => $method->card->exp_month,
                        'exp_year' => $method->card->exp_year,
                    ],
                    'is_default' => $defaultPaymentMethod && $method->id === $defaultPaymentMethod->id,
                ];
            });

            return response()->json(['payment_methods' => $formattedMethods->toArray()]);
        } catch (\Exception $e) {
            Log::error('❌ Error fetching payment methods: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Unable to fetch payment methods.'], 500);
        }
    }

    /**
     * Add payment method using Laravel Cashier
     */
    public function addPaymentMethod(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string'
        ]);

        $user = Auth::user();
        $paymentMethodId = $request->payment_method_id;

        Log::info('Adding payment method', [
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethodId,
            'has_stripe_id' => $user->hasStripeId()
        ]);

        try {
            // Ensure user has a Stripe customer ID
            if (!$user->hasStripeId()) {
                Log::info('Creating Stripe customer for user: ' . $user->id);
                $user->createAsStripeCustomer();
            }

            // Verify the payment method exists in Stripe before attaching
            $stripePaymentMethod = $this->stripe->paymentMethods->retrieve($paymentMethodId);

            if (!$stripePaymentMethod) {
                Log::error('Payment method not found in Stripe: ' . $paymentMethodId);
                return response()->json(['error' => 'Payment method not found.'], 404);
            }

            // Use Laravel Cashier's addPaymentMethod() method
            $user->addPaymentMethod($paymentMethodId);
            Log::info('Payment method attached successfully', ['payment_method_id' => $paymentMethodId]);

            // Try to get the payment method details using Cashier
            try {
                $paymentMethod = $user->findPaymentMethod($paymentMethodId);

                if ($paymentMethod) {
                    Log::info('Payment method details retrieved successfully', [
                        'payment_method_id' => $paymentMethodId,
                        'brand' => $paymentMethod->card->brand ?? 'unknown',
                        'last4' => $paymentMethod->card->last4 ?? 'unknown'
                    ]);

                    return response()->json([
                        'message' => 'Payment method added successfully.',
                        'payment_method' => [
                            'id' => $paymentMethod->id,
                            'type' => $paymentMethod->type,
                            'card' => [
                                'brand' => $paymentMethod->card->brand ?? 'unknown',
                                'last4' => $paymentMethod->card->last4 ?? 'unknown',
                                'exp_month' => $paymentMethod->card->exp_month ?? 0,
                                'exp_year' => $paymentMethod->card->exp_year ?? 0,
                            ],
                            'is_default' => false,
                        ]
                    ]);
                } else {
                    // Payment method was attached but we can't retrieve details
                    // This is still a success case
                    Log::warning('Payment method attached but details not retrievable: ' . $paymentMethodId);

                    return response()->json([
                        'message' => 'Payment method added successfully.',
                        'payment_method' => [
                            'id' => $paymentMethodId,
                            'type' => 'card',
                            'card' => [
                                'brand' => 'unknown',
                                'last4' => 'unknown',
                                'exp_month' => 0,
                                'exp_year' => 0,
                            ],
                            'is_default' => false,
                        ]
                    ]);
                }
            } catch (\Exception $detailsError) {
                // Payment method was attached successfully, but we can't get details
                // This is still a success case
                Log::warning('Payment method attached but error retrieving details: ' . $detailsError->getMessage(), [
                    'payment_method_id' => $paymentMethodId
                ]);

                return response()->json([
                    'message' => 'Payment method added successfully.',
                    'payment_method' => [
                        'id' => $paymentMethodId,
                        'type' => 'card',
                        'card' => [
                            'brand' => 'unknown',
                            'last4' => 'unknown',
                            'exp_month' => 0,
                            'exp_year' => 0,
                        ],
                        'is_default' => false,
                    ]
                ]);
            }
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Stripe invalid request error: ' . $e->getMessage(), [
                'payment_method_id' => $paymentMethodId,
                'user_id' => $user->id
            ]);
            return response()->json(['error' => 'Invalid payment method. Please try again.'], 422);
        } catch (\Exception $e) {
            Log::error('Error adding payment method: ' . $e->getMessage(), [
                'payment_method_id' => $paymentMethodId,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Unable to add payment method. Please try again.'], 500);
        }
    }

    /**
     * Create setup intent for adding payment method using Laravel Cashier
     */
    public function createSetupIntent()
    {
        $user = Auth::user();

        Log::info('Creating setup intent for user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'has_stripe_id' => $user->hasStripeId(),
            'stripe_id' => $user->stripe_id ?? 'none'
        ]);

        try {
            // Ensure user has a Stripe customer ID
            if (!$user->hasStripeId()) {
                Log::info('Creating Stripe customer for user: ' . $user->id);
                $user->createAsStripeCustomer();
                Log::info('Stripe customer created', ['stripe_id' => $user->stripe_id]);
            }

            // Use Laravel Cashier's createSetupIntent() method
            Log::info('Creating setup intent for customer: ' . $user->stripe_id);
            $setupIntent = $user->createSetupIntent();

            Log::info('Setup intent created successfully', [
                'setup_intent_id' => $setupIntent->id,
                'client_secret' => substr($setupIntent->client_secret, 0, 20) . '...'
            ]);

            return response()->json([
                'client_secret' => $setupIntent->client_secret
            ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            Log::error('Stripe invalid request error in setup intent: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id ?? 'none',
                'error_code' => $e->getStripeCode(),
                'error_type' => $e->getError()->type ?? 'unknown'
            ]);
            return response()->json(['error' => 'Invalid request to Stripe. Please try again.'], 422);
        } catch (\Exception $e) {
            Log::error('Error creating setup intent: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id ?? 'none',
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Unable to create setup intent. Please try again.'], 500);
        }
    }

    /**
     * Set default payment method using Laravel Cashier
     */
    public function setDefaultPaymentMethod($paymentMethodId)
    {
        $user = Auth::user();

        try {
            // Use Laravel Cashier's updateDefaultPaymentMethod() method
            $user->updateDefaultPaymentMethod($paymentMethodId);

            return response()->json(['message' => 'Default payment method updated successfully.']);
        } catch (\Exception $e) {
            Log::error('Error setting default payment method: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to set default payment method.'], 500);
        }
    }

    /**
     * Delete payment method using Laravel Cashier
     */
    public function deletePaymentMethod($paymentMethodId)
    {
        $user = Auth::user();

        try {
            // Check if user has an active subscription
            $activeSubscription = $user->subscriptions()
                ->where('stripe_status', 'active')
                ->orWhere('stripe_status', 'trialing')
                ->orWhere('stripe_status', 'past_due')
                ->first();

            // If user has active subscription, check if this is the default payment method
            if ($activeSubscription) {
                $defaultPaymentMethod = $user->defaultPaymentMethod();

                if ($defaultPaymentMethod && $defaultPaymentMethod->id === $paymentMethodId) {
                    return response()->json([
                        'error' => 'Cannot delete your default payment method while you have an active subscription. Please set another payment method as default first, or cancel your subscription.'
                    ], 422);
                }
            }

            // Use Laravel Cashier's deletePaymentMethod() method
            $user->deletePaymentMethod($paymentMethodId);

            return response()->json(['message' => 'Payment method removed successfully.']);
        } catch (\Exception $e) {
            Log::error('Error deleting payment method: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to remove payment method.'], 500);
        }
    }
}
