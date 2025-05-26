<?php

namespace App\AI\Tools;

use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GetPricingInfo
{
    public function __invoke(?string $plan_type = null): string
    {
        try {
            // Try to get real pricing from Stripe
            $stripePricing = $this->getStripePricing();

            if ($stripePricing) {
                return $this->formatStripePricing($stripePricing, $plan_type);
            }

            // Fallback to static pricing if Stripe is unavailable
            return $this->getFallbackPricing($plan_type);

        } catch (\Exception $e) {
            Log::warning('Failed to fetch Stripe pricing', ['error' => $e->getMessage()]);
            return $this->getFallbackPricing($plan_type);
        }
    }

    private function getStripePricing(): ?array
    {
        // Cache pricing for 1 hour to avoid excessive API calls
        return Cache::remember('stripe_pricing', 3600, function () {
            try {
                if (!config('services.stripe.secret')) {
                    return null;
                }

                $stripe = new StripeClient(config('services.stripe.secret'));

                // Get all products with their prices
                $products = $stripe->products->all(['active' => true, 'limit' => 10]);
                $pricing = [];

                foreach ($products->data as $product) {
                    $prices = $stripe->prices->all([
                        'product' => $product->id,
                        'active' => true
                    ]);

                    if (!empty($prices->data)) {
                        $pricing[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'description' => $product->description,
                            'features' => $product->metadata->features ?? '',
                            'price' => $prices->data[0], // Get the first active price
                            'metadata' => $product->metadata
                        ];
                    }
                }

                return $pricing;

            } catch (\Exception $e) {
                Log::error('Stripe API error', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    private function formatStripePricing(array $stripePricing, ?string $plan_type): string
    {
        $result = "**RillTech Pricing Plans** (Live from Stripe):\n\n";

        foreach ($stripePricing as $product) {
            $price = $product['price'];
            $planName = strtolower($product['name']);

            // Filter by plan type if specified
            if ($plan_type && $plan_type !== 'all' && !str_contains($planName, strtolower($plan_type))) {
                continue;
            }

            // Format price
            $formattedPrice = 'Custom pricing';
            if ($price->unit_amount) {
                $amount = $price->unit_amount / 100; // Convert from cents
                $currency = strtoupper($price->currency);
                $interval = $price->recurring ? "/{$price->recurring->interval}" : '';
                $formattedPrice = "{$currency} {$amount}{$interval}";
            }

            $result .= "🚀 **{$product['name']}** - {$formattedPrice}\n";

            if ($product['description']) {
                $result .= "{$product['description']}\n";
            }

            // Add features if available in metadata
            if (!empty($product['features'])) {
                $features = explode(',', $product['features']);
                foreach ($features as $feature) {
                    $result .= "• " . trim($feature) . "\n";
                }
            }

            $result .= "\n";
        }

        if (empty($stripePricing) || ($plan_type && $plan_type !== 'all' && !str_contains($result, '🚀'))) {
            return $this->getFallbackPricing($plan_type);
        }

        $result .= "💳 **Ready to get started?** All plans include a free trial!\n";
        $result .= "Contact our sales team for custom enterprise solutions and volume discounts.";

        return $result;
    }

    private function getFallbackPricing(?string $plan_type): string
    {
        $pricingData = [
            'starter' => [
                'name' => 'Starter Plan',
                'price' => '$29/month',
                'description' => 'Perfect for individuals and small teams getting started with AI agents',
                'features' => [
                    '5 AI agents',
                    'Drag & drop builder',
                    'Basic integrations',
                    'Email support',
                    '1,000 AI interactions/month',
                    'Basic analytics',
                    'Community access'
                ],
                'best_for' => 'Small businesses, freelancers, and individuals'
            ],
            'professional' => [
                'name' => 'Professional Plan',
                'price' => '$99/month',
                'description' => 'Advanced features for growing businesses and teams',
                'features' => [
                    '25 AI agents',
                    'Advanced drag & drop builder',
                    'Premium integrations (Slack, Teams, CRM)',
                    'Priority support',
                    '10,000 AI interactions/month',
                    'Advanced analytics & reporting',
                    'Team collaboration',
                    'Custom branding',
                    'API access'
                ],
                'best_for' => 'Growing businesses and professional teams'
            ],
            'enterprise' => [
                'name' => 'Enterprise Plan',
                'price' => 'Custom pricing',
                'description' => 'Enterprise-grade solution with unlimited possibilities',
                'features' => [
                    'Unlimited AI agents',
                    'White-label solution',
                    'Custom integrations',
                    'Dedicated support manager',
                    'Unlimited AI interactions',
                    'Enterprise analytics',
                    'Advanced security & compliance',
                    'On-premise deployment option',
                    'Custom training & onboarding',
                    'SLA guarantees'
                ],
                'best_for' => 'Large enterprises and organizations'
            ]
        ];

        if ($plan_type && $plan_type !== 'all' && isset($pricingData[strtolower($plan_type)])) {
            $plan = $pricingData[strtolower($plan_type)];
            return $this->formatSinglePlan($plan);
        }

        return $this->formatAllPlans($pricingData);
    }

    private function formatSinglePlan(array $plan): string
    {
        $features = implode("\n• ", $plan['features']);

        return "**{$plan['name']} - {$plan['price']}**\n\n" .
               "{$plan['description']}\n\n" .
               "**Features included:**\n• {$features}\n\n" .
               "**Best for:** {$plan['best_for']}\n\n" .
               "Would you like to know more about any specific features or see how this compares to other plans?";
    }

    private function formatAllPlans(array $pricingData): string
    {
        $output = "Here are our current pricing plans:\n\n";

        foreach ($pricingData as $plan) {
            $output .= "**{$plan['name']} - {$plan['price']}**\n";
            $output .= "{$plan['description']}\n";
            $output .= "Best for: {$plan['best_for']}\n\n";
        }

        $output .= "All plans include:\n";
        $output .= "• 14-day free trial\n";
        $output .= "• No setup fees\n";
        $output .= "• Cancel anytime\n";
        $output .= "• Regular feature updates\n\n";

        $output .= "Would you like detailed information about any specific plan or help choosing the right one for your needs?";

        return $output;
    }
}
