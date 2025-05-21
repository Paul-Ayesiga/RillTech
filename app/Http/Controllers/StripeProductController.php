<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class StripeProductController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Get all active products with prices from Stripe
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProducts()
    {
        try {
            // Fetch active products
            $products = $this->stripe->products->all([
                'active' => true,
                'expand' => ['data.default_price'],
            ]);

            if (count($products->data) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pricing plans are currently available. Please check back later or contact our sales team for custom pricing options.',
                    'products' => []
                ]);
            }

            // Group products by their base name (without "Monthly" or "Yearly" suffix)
            $productGroups = [];
            foreach ($products->data as $product) {
                // Skip products without a default price
                if (!$product->default_price) {
                    continue;
                }

                // Get price details
                $price = $product->default_price;

                // Determine if this is a recurring price
                $isRecurring = $price->type === 'recurring';
                $interval = $isRecurring ? $price->recurring->interval : null;

                // Skip products without an interval
                if (!$interval) {
                    continue;
                }

                // Get the base product name (without Monthly/Yearly suffix)
                $baseName = $product->name;
                $baseName = preg_replace('/ (Monthly|Yearly|Annual)$/i', '', $baseName);

                // Group by base name
                if (!isset($productGroups[$baseName])) {
                    $productGroups[$baseName] = [];
                }

                $productGroups[$baseName][$interval] = [
                    'product' => $product,
                    'price' => $price
                ];
            }

            $formattedProducts = [];

            // Process each product group to calculate discounts
            foreach ($productGroups as $baseName => $variants) {
                foreach ($variants as $interval => $data) {
                    $product = $data['product'];
                    $price = $data['price'];

                    // Get features from metadata
                    $features = [];
                    if (isset($product->metadata->features)) {
                        $featuresData = json_decode($product->metadata->features, true) ?? [];
                        // Handle the new feature format { "name": "feature name", "included": true/false }
                        foreach ($featuresData as $feature) {
                            if (is_array($feature) && isset($feature['name'])) {
                                $features[] = [
                                    'name' => $feature['name'],
                                    'included' => isset($feature['included']) ? (bool) $feature['included'] : true
                                ];
                            } elseif (is_string($feature)) {
                                $features[] = [
                                    'name' => $feature,
                                    'included' => true
                                ];
                            }
                        }
                    } else {
                        // If no features in metadata, check if there are individually numbered features
                        for ($i = 1; $i <= 10; $i++) {
                            $featureKey = "feature_{$i}";
                            if (isset($product->metadata->$featureKey) && !empty($product->metadata->$featureKey)) {
                                $features[] = [
                                    'name' => $product->metadata->$featureKey,
                                    'included' => true
                                ];
                            }
                        }
                    }

                    // Format the price amount
                    $amount = $price->unit_amount / 100; // Convert from cents to dollars
                    $intervalCount = $price->recurring->interval_count;

                    // Calculate discount percentage for yearly plans
                    $discountPercentage = null;
                    $monthlyEquivalent = null;

                    if ($interval === 'year' && isset($variants['month'])) {
                        $monthlyPrice = $variants['month']['price']->unit_amount / 100;
                        $yearlyPrice = $amount;

                        // Calculate what 12 months would cost
                        $annualCostIfMonthly = $monthlyPrice * 12;

                        // Calculate the discount percentage
                        if ($annualCostIfMonthly > 0) {
                            $savings = $annualCostIfMonthly - $yearlyPrice;
                            $discountPercentage = round(($savings / $annualCostIfMonthly) * 100);
                            $monthlyEquivalent = $yearlyPrice / 12;
                        }
                    }

                    // Add to formatted products array
                    $formattedProducts[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price_id' => $price->id,
                        'amount' => $amount,
                        'currency' => strtoupper($price->currency),
                        'interval' => $interval,
                        'interval_count' => $intervalCount,
                        'features' => $features,
                        'is_popular' => isset($product->metadata->is_popular) ? (bool) $product->metadata->is_popular : false,
                        'sort_order' => isset($product->metadata->sort_order) ? (int) $product->metadata->sort_order : 999,
                        'cta_text' => $product->metadata->cta_text ?? 'Get Started',
                        'cta_url' => $product->metadata->cta_url ?? '/register',
                        'discount_percentage' => $discountPercentage,
                        'monthly_equivalent' => $monthlyEquivalent,
                        'base_name' => $baseName
                    ];
                }
            }

            // Sort products by sort_order
            usort($formattedProducts, function ($a, $b) {
                return $a['sort_order'] <=> $b['sort_order'];
            });

            return response()->json([
                'success' => true,
                'products' => $formattedProducts
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching Stripe products: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'We\'re currently unable to load our pricing plans. Please check back later or contact our sales team for assistance.',
                'products' => []
            ], 500);
        }
    }
}
