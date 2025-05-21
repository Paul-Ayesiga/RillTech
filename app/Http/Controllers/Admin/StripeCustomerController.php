<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class StripeCustomerController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');

            // Get users with Stripe IDs from the database
            $query = User::whereNotNull('stripe_id')
                ->orderBy('created_at', 'desc');

            // Search by name or email
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('stripe_id', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate($perPage);

            // If no users have Stripe IDs, let's get some users without Stripe IDs that we can convert
            $potentialCustomers = [];
            if ($users->total() === 0) {
                $potentialCustomers = User::whereNull('stripe_id')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                        ];
                    });
            }

            // Format users for the frontend
            $formattedUsers = $users->map(function ($user) {
                try {
                    // Get additional details from Stripe
                    $customer = $this->stripe->customers->retrieve($user->stripe_id);

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'stripe_id' => $user->stripe_id,
                        'pm_type' => $user->pm_type,
                        'pm_last_four' => $user->pm_last_four,
                        'trial_ends_at' => $user->trial_ends_at ? $user->trial_ends_at->format('Y-m-d') : null,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                        'stripe_details' => [
                            'balance' => $customer->balance / 100, // Convert from cents
                            'currency' => $customer->currency,
                            'default_source' => $customer->default_source,
                            'delinquent' => $customer->delinquent,
                        ],
                    ];
                } catch (\Exception $e) {
                    // If we can't get details from Stripe, return basic info from the database
                    Log::warning('Error fetching Stripe customer details: ' . $e->getMessage());

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'stripe_id' => $user->stripe_id,
                        'pm_type' => $user->pm_type,
                        'pm_last_four' => $user->pm_last_four,
                        'trial_ends_at' => $user->trial_ends_at ? $user->trial_ends_at->format('Y-m-d') : null,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                        'error' => 'Could not fetch detailed information from Stripe',
                    ];
                }
            });

            return inertia('admin/stripe/Customers', [
                'customers' => $formattedUsers,
                'potential_customers' => $potentialCustomers,
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ],
                'filters' => [
                    'search' => $search,
                    'per_page' => $perPage,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customers: ' . $e->getMessage());

            return inertia('admin/stripe/Customers', [
                'error' => 'Unable to fetch customers. Please try again later.'
            ]);
        }
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);

            if (!$user->stripe_id) {
                return redirect()->route('admin.stripe.customers')->withErrors(['error' => 'This user is not a Stripe customer.']);
            }

            // Get customer details from Stripe
            $customer = $this->stripe->customers->retrieve(
                $user->stripe_id,
                ['expand' => ['subscriptions']]
            );

            // Get payment methods separately
            try {
                $paymentMethodsList = $this->stripe->paymentMethods->all([
                    'customer' => $user->stripe_id,
                    'type' => 'card'
                ]);
            } catch (\Exception $e) {
                // If we can't get payment methods, just use an empty array
                Log::warning('Could not retrieve payment methods: ' . $e->getMessage());
                $paymentMethodsList = (object) ['data' => []];
            }

            // Get default payment method
            $defaultPaymentMethod = null;
            if (isset($customer->invoice_settings->default_payment_method)) {
                $defaultPaymentMethod = $customer->invoice_settings->default_payment_method;
            }

            // Format payment methods
            $paymentMethods = collect($paymentMethodsList->data ?? [])->map(function ($method) use ($defaultPaymentMethod) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'brand' => $method->card->brand ?? null,
                    'last4' => $method->card->last4 ?? null,
                    'exp_month' => $method->card->exp_month ?? null,
                    'exp_year' => $method->card->exp_year ?? null,
                    'is_default' => $method->id === $defaultPaymentMethod,
                ];
            });

            // Get subscriptions
            $subscriptions = [];
            if (isset($customer->subscriptions) && isset($customer->subscriptions->data)) {
                $subscriptions = collect($customer->subscriptions->data)->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'status' => $subscription->status,
                        'current_period_end' => date('Y-m-d', $subscription->current_period_end),
                        'cancel_at_period_end' => $subscription->cancel_at_period_end,
                        'items' => collect($subscription->items->data)->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'price' => [
                                    'id' => $item->price->id,
                                    'amount' => $item->price->unit_amount / 100,
                                    'currency' => strtoupper($item->price->currency),
                                    'interval' => $item->price->type === 'recurring' ? $item->price->recurring->interval : null,
                                ],
                                'quantity' => $item->quantity,
                            ];
                        }),
                    ];
                });
            }

            $formattedCustomer = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'stripe_id' => $user->stripe_id,
                'pm_type' => $user->pm_type,
                'pm_last_four' => $user->pm_last_four,
                'trial_ends_at' => $user->trial_ends_at ? $user->trial_ends_at->format('Y-m-d') : null,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'stripe_details' => [
                    'balance' => $customer->balance / 100, // Convert from cents
                    'currency' => $customer->currency,
                    'default_source' => $customer->default_source,
                    'delinquent' => $customer->delinquent,
                ],
                'payment_methods' => $paymentMethods,
                'subscriptions' => $subscriptions,
            ];

            return inertia('admin/stripe/CustomerDetail', [
                'customer' => $formattedCustomer,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customer details: ' . $e->getMessage());

            return redirect()->route('admin.stripe.customers')->withErrors(['error' => 'Failed to fetch customer details: ' . $e->getMessage()]);
        }
    }

    /**
     * Create a Stripe customer for a user.
     */
    public function createCustomer(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);

            if ($user->stripe_id) {
                return redirect()->route('admin.stripe.customers')->withErrors(['error' => 'This user is already a Stripe customer.']);
            }

            // Create customer in Stripe
            $customer = $this->stripe->customers->create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            // Update user with Stripe ID
            $user->stripe_id = $customer->id;
            $user->save();

            return redirect()->route('admin.stripe.customers.show', $user->id)->with('success', 'Stripe customer created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating Stripe customer: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to create Stripe customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a Stripe customer.
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            if (!$user->stripe_id) {
                return redirect()->route('admin.stripe.customers')->withErrors(['error' => 'This user is not a Stripe customer.']);
            }

            // Delete customer in Stripe
            $this->stripe->customers->delete($user->stripe_id);

            // Remove Stripe ID from user
            $user->stripe_id = null;
            $user->pm_type = null;
            $user->pm_last_four = null;
            $user->save();

            return redirect()->route('admin.stripe.customers')->with('success', 'Stripe customer deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting Stripe customer: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to delete Stripe customer: ' . $e->getMessage()]);
        }
    }
}
