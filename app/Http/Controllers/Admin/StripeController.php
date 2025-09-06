<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Display the Stripe dashboard overview.
     */
    public function dashboard()
    {
        try {
            // Get basic Stripe account information
            $account = $this->stripe->accounts->retrieve();

            // Get recent products (limit to 5)
            $products = $this->stripe->products->all(['limit' => 5, 'active' => true]);

            // Get recent customers (limit to 5)
            $stripeCustomers = $this->stripe->customers->all(['limit' => 5]);

            // Get count of users with Stripe IDs in the database
            $dbCustomerCount = \App\Models\User::whereNotNull('stripe_id')->count();

            // Get recent subscriptions (limit to 5)
            $subscriptions = $this->stripe->subscriptions->all(['limit' => 5, 'status' => 'active']);

            // Get balance information
            $balance = $this->stripe->balance->retrieve();

            return inertia('admin/stripe/Dashboard', [
                'account' => [
                    'id' => $account->id,
                    'business_name' => $account->business_profile->name ?? $account->settings->dashboard->display_name ?? 'Your Business',
                    'email' => $account->email,
                    'country' => $account->country,
                    'currency' => $account->default_currency,
                ],
                'products' => [
                    'total' => $products->has_more ? '5+' : count($products->data),
                    'recent' => collect($products->data)->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'active' => $product->active,
                            'created' => date('M d, Y', $product->created),
                        ];
                    }),
                ],
                'customers' => [
                    'total' => $dbCustomerCount,
                    'recent' => collect($stripeCustomers->data)->map(function ($customer) {
                        return [
                            'id' => $customer->id,
                            'name' => $customer->name,
                            'email' => $customer->email,
                            'created' => date('M d, Y', $customer->created),
                        ];
                    }),
                ],
                'subscriptions' => [
                    'total' => $subscriptions->has_more ? '5+' : count($subscriptions->data),
                    'recent' => collect($subscriptions->data)->map(function ($subscription) {
                        return [
                            'id' => $subscription->id,
                            'customer' => $subscription->customer,
                            'status' => $subscription->status,
                            'current_period_end' => date('M d, Y', $subscription->current_period_end),
                        ];
                    }),
                ],
                'balance' => collect($balance->available)->map(function ($balance) {
                    return [
                        'amount' => $balance->amount / 100, // Convert from cents to dollars
                        'currency' => strtoupper($balance->currency),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching Stripe dashboard data: ' . $e->getMessage());

            return inertia('admin/stripe/Dashboard', [
                'error' => 'Unable to fetch Stripe dashboard data. Please check your Stripe API keys and try again.'
            ]);
        }
    }
}
