<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Laravel\Cashier\Subscription;
use Illuminate\Pagination\LengthAwarePaginator;

class StripeSubscriptionController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');
            $status = $request->input('status', 'all');

            // Get subscriptions from the database
            $query = Subscription::query()
                ->with('user')
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($status !== 'all') {
                $query->where('stripe_status', $status);
            }

            // Search by user name or email
            if ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $subscriptions = $query->paginate($perPage);

            // If no subscriptions found in the database, try to fetch from Stripe directly
            if ($subscriptions->isEmpty()) {
                try {
                    // Fetch subscriptions directly from Stripe
                    $stripeParams = ['limit' => $perPage, 'expand' => ['data.customer']];
                    if ($status !== 'all') {
                        $stripeParams['status'] = $status;
                    }

                    $stripeSubscriptions = $this->stripe->subscriptions->all($stripeParams);

                    // Format Stripe subscriptions
                    $formattedSubscriptions = collect($stripeSubscriptions->data)->map(function ($subscription) {
                        // Try to find the user associated with this customer
                        $user = User::where('stripe_id', $subscription->customer->id)->first();

                        return [
                            'id' => null, // No database ID
                            'stripe_id' => $subscription->id,
                            'status' => $subscription->status,
                            'user' => $user ? [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                            ] : [
                                'id' => null,
                                'name' => $subscription->customer->name ?? 'Unknown',
                                'email' => $subscription->customer->email ?? 'Unknown',
                            ],
                            'items' => collect($subscription->items->data)->map(function ($item) {
                                // Fetch product details
                                try {
                                    $product = $this->stripe->products->retrieve($item->price->product);
                                    $productName = $product->name;
                                } catch (\Exception $e) {
                                    $productName = 'Unknown Product';
                                }

                                return [
                                    'id' => $item->id,
                                    'price' => [
                                        'id' => $item->price->id,
                                        'amount' => $item->price->unit_amount / 100,
                                        'currency' => strtoupper($item->price->currency),
                                        'interval' => $item->price->type === 'recurring' ? $item->price->recurring->interval : null,
                                    ],
                                    'product' => [
                                        'id' => $item->price->product,
                                        'name' => $productName,
                                    ],
                                    'quantity' => $item->quantity,
                                ];
                            }),
                            'current_period_start' => $subscription->current_period_start ? date('Y-m-d', $subscription->current_period_start) : null,
                            'current_period_end' => $subscription->current_period_end ? date('Y-m-d', $subscription->current_period_end) : null,
                            'created_at' => date('Y-m-d H:i:s', $subscription->created),
                            'trial_ends_at' => $subscription->trial_end ? date('Y-m-d', $subscription->trial_end) : null,
                            'ends_at' => $subscription->cancel_at ? date('Y-m-d', $subscription->cancel_at) : null,
                            'note' => 'This subscription exists in Stripe but not in your database. Consider syncing your database with Stripe.',
                        ];
                    });

                    // Create a custom pagination instance
                    $customPagination = new \Illuminate\Pagination\LengthAwarePaginator(
                        $formattedSubscriptions,
                        $stripeSubscriptions->has_more ? $perPage + 1 : $formattedSubscriptions->count(),
                        $perPage,
                        $page
                    );

                    return inertia('admin/stripe/Subscriptions', [
                        'subscriptions' => $formattedSubscriptions,
                        'pagination' => [
                            'total' => $customPagination->total(),
                            'per_page' => $customPagination->perPage(),
                            'current_page' => $customPagination->currentPage(),
                            'last_page' => $customPagination->lastPage(),
                        ],
                        'filters' => [
                            'search' => $search,
                            'status' => $status,
                            'per_page' => $perPage,
                        ],
                        'warning' => 'Some subscriptions exist in Stripe but not in your database. Consider syncing your database with Stripe.',
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error fetching subscriptions from Stripe: ' . $e->getMessage());
                    // Continue with empty database results
                }
            }

            // Format subscriptions from database
            $formattedSubscriptions = $subscriptions->map(function ($subscription) {
                try {
                    // Get additional details from Stripe
                    $stripeSubscription = $this->stripe->subscriptions->retrieve(
                        $subscription->stripe_id,
                        ['expand' => ['customer', 'items.data.price.product']]
                    );

                    $items = collect($stripeSubscription->items->data)->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'price' => [
                                'id' => $item->price->id,
                                'amount' => $item->price->unit_amount / 100,
                                'currency' => strtoupper($item->price->currency),
                                'interval' => $item->price->type === 'recurring' ? $item->price->recurring->interval : null,
                            ],
                            'product' => [
                                'id' => $item->price->product->id,
                                'name' => $item->price->product->name,
                            ],
                            'quantity' => $item->quantity,
                        ];
                    });

                    return [
                        'id' => $subscription->id,
                        'stripe_id' => $subscription->stripe_id,
                        'status' => $subscription->stripe_status,
                        'user' => [
                            'id' => $subscription->user->id,
                            'name' => $subscription->user->name,
                            'email' => $subscription->user->email,
                        ],
                        'items' => $items,
                        'current_period_start' => $stripeSubscription->current_period_start ? date('Y-m-d', $stripeSubscription->current_period_start) : null,
                        'current_period_end' => $stripeSubscription->current_period_end ? date('Y-m-d', $stripeSubscription->current_period_end) : null,
                        'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                        'trial_ends_at' => $subscription->trial_ends_at ? $subscription->trial_ends_at->format('Y-m-d') : null,
                        'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('Y-m-d') : null,
                    ];
                } catch (\Exception $e) {
                    // If we can't get details from Stripe, return basic info from the database
                    Log::warning('Error fetching Stripe subscription details: ' . $e->getMessage());

                    return [
                        'id' => $subscription->id,
                        'stripe_id' => $subscription->stripe_id,
                        'status' => $subscription->stripe_status,
                        'user' => [
                            'id' => $subscription->user->id,
                            'name' => $subscription->user->name,
                            'email' => $subscription->user->email,
                        ],
                        'items' => [],
                        'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                        'trial_ends_at' => $subscription->trial_ends_at ? $subscription->trial_ends_at->format('Y-m-d') : null,
                        'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('Y-m-d') : null,
                        'error' => 'Could not fetch detailed information from Stripe',
                    ];
                }
            });

            return inertia('admin/stripe/Subscriptions', [
                'subscriptions' => $formattedSubscriptions,
                'pagination' => [
                    'total' => $subscriptions->total(),
                    'per_page' => $subscriptions->perPage(),
                    'current_page' => $subscriptions->currentPage(),
                    'last_page' => $subscriptions->lastPage(),
                ],
                'filters' => [
                    'search' => $search,
                    'status' => $status,
                    'per_page' => $perPage,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching subscriptions: ' . $e->getMessage());

            return inertia('admin/stripe/Subscriptions', [
                'error' => 'Unable to fetch subscriptions. Please try again later.'
            ]);
        }
    }

    /**
     * Display the specified subscription.
     */
    public function show($id)
    {
        try {
            // Check if this is a database ID or a Stripe ID
            if (is_numeric($id)) {
                // Database ID
                $subscription = Subscription::with('user')->findOrFail($id);
                $stripeId = $subscription->stripe_id;
            } else {
                // Stripe ID
                $stripeId = $id;
                $subscription = Subscription::where('stripe_id', $stripeId)->with('user')->first();
            }

            // Get details from Stripe
            $stripeSubscription = $this->stripe->subscriptions->retrieve(
                $stripeId,
                ['expand' => ['customer', 'items.data.price.product', 'latest_invoice']]
            );

            // If subscription doesn't exist in database, try to find the user
            $user = null;
            if (!$subscription) {
                $user = User::where('stripe_id', $stripeSubscription->customer->id)->first();
            }

            $items = collect($stripeSubscription->items->data)->map(function ($item) {
                try {
                    return [
                        'id' => $item->id,
                        'price' => [
                            'id' => $item->price->id,
                            'amount' => $item->price->unit_amount / 100,
                            'currency' => strtoupper($item->price->currency),
                            'interval' => $item->price->type === 'recurring' ? $item->price->recurring->interval : null,
                            'interval_count' => $item->price->type === 'recurring' ? $item->price->recurring->interval_count : null,
                        ],
                        'product' => [
                            'id' => $item->price->product->id,
                            'name' => $item->price->product->name,
                            'description' => $item->price->product->description,
                        ],
                        'quantity' => $item->quantity,
                    ];
                } catch (\Exception $e) {
                    // Handle case where product might not be expanded
                    try {
                        $product = $this->stripe->products->retrieve($item->price->product);
                        $productName = $product->name;
                        $productDescription = $product->description;
                    } catch (\Exception $e2) {
                        $productName = 'Unknown Product';
                        $productDescription = '';
                    }

                    return [
                        'id' => $item->id,
                        'price' => [
                            'id' => $item->price->id,
                            'amount' => $item->price->unit_amount / 100,
                            'currency' => strtoupper($item->price->currency),
                            'interval' => $item->price->type === 'recurring' ? $item->price->recurring->interval : null,
                            'interval_count' => $item->price->type === 'recurring' ? $item->price->recurring->interval_count : null,
                        ],
                        'product' => [
                            'id' => $item->price->product,
                            'name' => $productName,
                            'description' => $productDescription,
                        ],
                        'quantity' => $item->quantity,
                    ];
                }
            });

            // Format subscription data
            if ($subscription) {
                // Subscription exists in database
                $formattedSubscription = [
                    'id' => $subscription->id,
                    'stripe_id' => $subscription->stripe_id,
                    'status' => $subscription->stripe_status,
                    'user' => [
                        'id' => $subscription->user->id,
                        'name' => $subscription->user->name,
                        'email' => $subscription->user->email,
                    ],
                    'items' => $items,
                    'current_period_start' => $stripeSubscription->current_period_start ? date('Y-m-d', $stripeSubscription->current_period_start) : null,
                    'current_period_end' => $stripeSubscription->current_period_end ? date('Y-m-d', $stripeSubscription->current_period_end) : null,
                    'created_at' => $subscription->created_at->format('Y-m-d H:i:s'),
                    'trial_ends_at' => $subscription->trial_ends_at ? $subscription->trial_ends_at->format('Y-m-d') : null,
                    'ends_at' => $subscription->ends_at ? $subscription->ends_at->format('Y-m-d') : null,
                    'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                    'latest_invoice' => $stripeSubscription->latest_invoice ? [
                        'id' => $stripeSubscription->latest_invoice->id,
                        'amount_due' => $stripeSubscription->latest_invoice->amount_due / 100,
                        'amount_paid' => $stripeSubscription->latest_invoice->amount_paid / 100,
                        'currency' => strtoupper($stripeSubscription->latest_invoice->currency),
                        'status' => $stripeSubscription->latest_invoice->status,
                        'created' => date('Y-m-d', $stripeSubscription->latest_invoice->created),
                    ] : null,
                ];
            } else {
                // Subscription only exists in Stripe
                $formattedSubscription = [
                    'id' => null,
                    'stripe_id' => $stripeSubscription->id,
                    'status' => $stripeSubscription->status,
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ] : [
                        'id' => null,
                        'name' => $stripeSubscription->customer->name ?? 'Unknown',
                        'email' => $stripeSubscription->customer->email ?? 'Unknown',
                    ],
                    'items' => $items,
                    'current_period_start' => $stripeSubscription->current_period_start ? date('Y-m-d', $stripeSubscription->current_period_start) : null,
                    'current_period_end' => $stripeSubscription->current_period_end ? date('Y-m-d', $stripeSubscription->current_period_end) : null,
                    'created_at' => date('Y-m-d H:i:s', $stripeSubscription->created),
                    'trial_ends_at' => $stripeSubscription->trial_end ? date('Y-m-d', $stripeSubscription->trial_end) : null,
                    'ends_at' => $stripeSubscription->cancel_at ? date('Y-m-d', $stripeSubscription->cancel_at) : null,
                    'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
                    'latest_invoice' => $stripeSubscription->latest_invoice ? [
                        'id' => $stripeSubscription->latest_invoice->id,
                        'amount_due' => $stripeSubscription->latest_invoice->amount_due / 100,
                        'amount_paid' => $stripeSubscription->latest_invoice->amount_paid / 100,
                        'currency' => strtoupper($stripeSubscription->latest_invoice->currency),
                        'status' => $stripeSubscription->latest_invoice->status,
                        'created' => date('Y-m-d', $stripeSubscription->latest_invoice->created),
                    ] : null,
                    'warning' => 'This subscription exists in Stripe but not in your database. Consider syncing your database with Stripe.',
                ];
            }

            return inertia('admin/stripe/SubscriptionDetail', [
                'subscription' => $formattedSubscription,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching subscription details: ' . $e->getMessage());

            return redirect()->route('admin.stripe.subscriptions')->withErrors(['error' => 'Failed to fetch subscription details: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(Request $request, $id)
    {
        try {
            $subscription = Subscription::findOrFail($id);
            $atPeriodEnd = $request->input('at_period_end', true);

            if ($atPeriodEnd) {
                // Cancel at period end
                $subscription->cancelAtPeriodEnd();
            } else {
                // Cancel immediately
                $subscription->cancelNow();
            }

            return redirect()->route('admin.stripe.subscriptions.show', $id)->with('success', 'Subscription cancelled successfully.');
        } catch (\Exception $e) {
            Log::error('Error cancelling subscription: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to cancel subscription: ' . $e->getMessage()]);
        }
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume($id)
    {
        try {
            $subscription = Subscription::findOrFail($id);

            if ($subscription->onGracePeriod()) {
                $subscription->resume();
                return redirect()->route('admin.stripe.subscriptions.show', $id)->with('success', 'Subscription resumed successfully.');
            }

            return redirect()->back()->withErrors(['error' => 'This subscription cannot be resumed.']);
        } catch (\Exception $e) {
            Log::error('Error resuming subscription: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to resume subscription: ' . $e->getMessage()]);
        }
    }
}
