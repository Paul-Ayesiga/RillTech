<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StripeProductController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Display a listing of products and prices.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');
            $active = $request->input('active', 'all');

            $params = [
                'limit' => $perPage,
                'expand' => ['data.default_price'],
            ];

            // Add starting_after parameter for pagination
            if ($page > 1 && $request->has('last_id')) {
                $params['starting_after'] = $request->input('last_id');
            }

            // Filter by active status
            if ($active !== 'all') {
                $params['active'] = $active === 'true';
            }

            // Get products from Stripe
            $products = $this->stripe->products->all($params);

            // Format products for the frontend
            $formattedProducts = collect($products->data)->map(function ($product) {
                $price = $product->default_price;
                $priceAmount = $price ? $price->unit_amount / 100 : null;
                $priceCurrency = $price ? strtoupper($price->currency) : null;
                $priceInterval = $price && $price->type === 'recurring' ? $price->recurring->interval : null;

                // Get features from metadata
                $features = [];
                if (isset($product->metadata->features)) {
                    $featuresData = json_decode($product->metadata->features, true) ?? [];
                    foreach ($featuresData as $feature) {
                        if (is_array($feature) && isset($feature['name']) && isset($feature['included'])) {
                            $features[] = [
                                'name' => $feature['name'],
                                'included' => (bool) $feature['included']
                            ];
                        } elseif (is_string($feature)) {
                            $features[] = [
                                'name' => $feature,
                                'included' => true
                            ];
                        }
                    }
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'active' => $product->active,
                    'created' => date('Y-m-d H:i:s', $product->created),
                    'default_price' => $price ? [
                        'id' => $price->id,
                        'amount' => $priceAmount,
                        'currency' => $priceCurrency,
                        'interval' => $priceInterval,
                        'type' => $price->type,
                    ] : null,
                    'features' => $features,
                    'metadata' => $product->metadata,
                    'is_popular' => isset($product->metadata->is_popular) ? (bool) $product->metadata->is_popular : false,
                ];
            });

            return inertia('admin/stripe/Products', [
                'products' => $formattedProducts,
                'pagination' => [
                    'has_more' => $products->has_more,
                    'last_id' => count($products->data) > 0 ? $products->data[count($products->data) - 1]->id : null,
                    'current_page' => $page,
                ],
                'filters' => [
                    'search' => $search,
                    'active' => $active,
                    'per_page' => $perPage,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching Stripe products: ' . $e->getMessage());

            return inertia('admin/stripe/Products', [
                'error' => 'Unable to fetch products. Please check your Stripe API keys and try again.'
            ]);
        }
    }

    /**
     * Store a newly created product in Stripe.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'price_amount' => 'required|numeric|min:0',
            'price_currency' => 'required|string|size:3',
            'price_interval' => 'required|string|in:day,week,month,year',
            'price_interval_count' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'is_popular' => 'boolean',
        ]);

        try {
            // Create product in Stripe
            $product = $this->stripe->products->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'active' => $validated['active'] ?? true,
                'metadata' => [
                    'features' => json_encode($validated['features'] ?? []),
                    'is_popular' => $validated['is_popular'] ?? false,
                ],
            ]);

            // Create price for the product
            $price = $this->stripe->prices->create([
                'product' => $product->id,
                'unit_amount' => $validated['price_amount'] * 100, // Convert to cents
                'currency' => strtolower($validated['price_currency']),
                'recurring' => [
                    'interval' => $validated['price_interval'],
                    'interval_count' => $validated['price_interval_count'],
                ],
            ]);

            // Update product with default price
            $this->stripe->products->update($product->id, [
                'default_price' => $price->id,
            ]);

            return redirect()->route('admin.stripe.products')->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating Stripe product: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified product in Stripe.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'features' => 'nullable|array',
            'is_popular' => 'boolean',
        ]);

        try {
            // Update product in Stripe
            $product = $this->stripe->products->update($id, [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'active' => $validated['active'] ?? true,
                'metadata' => [
                    'features' => json_encode($validated['features'] ?? []),
                    'is_popular' => $validated['is_popular'] ?? false,
                ],
            ]);

            return redirect()->route('admin.stripe.products')->with('success', 'Product updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating Stripe product: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    /**
     * Create a new price for a product.
     */
    public function createPrice(Request $request, $productId)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'interval' => 'required|string|in:day,week,month,year',
            'interval_count' => 'required|integer|min:1',
            'is_default' => 'boolean',
        ]);

        try {
            // Create price for the product
            $price = $this->stripe->prices->create([
                'product' => $productId,
                'unit_amount' => $validated['amount'] * 100, // Convert to cents
                'currency' => strtolower($validated['currency']),
                'recurring' => [
                    'interval' => $validated['interval'],
                    'interval_count' => $validated['interval_count'],
                ],
            ]);

            // Update product with default price if requested
            if ($validated['is_default'] ?? false) {
                $this->stripe->products->update($productId, [
                    'default_price' => $price->id,
                ]);
            }

            return redirect()->route('admin.stripe.products.show', $productId)->with('success', 'Price created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating Stripe price: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to create price: ' . $e->getMessage()]);
        }
    }

    /**
     * Update a price's default status.
     *
     * Note: Stripe doesn't allow updating most price attributes after creation.
     * We can only update the default status and create a new price if needed.
     */
    public function updatePrice(Request $request, $productId, $priceId)
    {
        $validated = $request->validate([
            'is_default' => 'required|boolean',
            'active' => 'boolean',
            'create_new' => 'boolean',
            'amount' => 'numeric|min:0',
            'currency' => 'string|size:3',
            'interval' => 'string|in:day,week,month,year',
            'interval_count' => 'integer|min:1',
        ]);

        try {
            // If create_new is true, create a new price with the updated amount
            if (isset($validated['create_new']) && $validated['create_new'] && isset($validated['amount'])) {
                // Get the existing price to copy its attributes
                $existingPrice = $this->stripe->prices->retrieve($priceId);

                // Create a new price with the updated amount
                $newPrice = $this->stripe->prices->create([
                    'product' => $productId,
                    'unit_amount' => $validated['amount'] * 100, // Convert to cents
                    'currency' => strtolower($validated['currency'] ?? $existingPrice->currency),
                    'recurring' => [
                        'interval' => $validated['interval'] ?? $existingPrice->recurring->interval,
                        'interval_count' => $validated['interval_count'] ?? $existingPrice->recurring->interval_count,
                    ],
                ]);

                // Archive the old price
                $this->stripe->prices->update($priceId, [
                    'active' => false,
                ]);

                // Set the new price as default if requested
                if ($validated['is_default']) {
                    $this->stripe->products->update($productId, [
                        'default_price' => $newPrice->id,
                    ]);
                }

                return redirect()->route('admin.stripe.products.show', $productId)->with('success', 'New price created and old price archived successfully.');
            }

            // Update price active status if provided
            if (isset($validated['active'])) {
                $this->stripe->prices->update($priceId, [
                    'active' => $validated['active'],
                ]);
            }

            // Update product's default price if requested
            if ($validated['is_default']) {
                $this->stripe->products->update($productId, [
                    'default_price' => $priceId,
                ]);
            }

            return redirect()->route('admin.stripe.products.show', $productId)->with('success', 'Price updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating Stripe price: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to update price: ' . $e->getMessage()]);
        }
    }

    /**
     * Archive a price.
     * Note: Stripe doesn't allow deleting prices, only archiving them.
     */
    public function archivePrice(Request $request, $productId, $priceId)
    {
        try {
            // Archive the price (Stripe doesn't allow deleting prices)
            $this->stripe->prices->update($priceId, [
                'active' => false,
            ]);

            return redirect()->route('admin.stripe.products.show', $productId)->with('success', 'Price archived successfully.');
        } catch (\Exception $e) {
            Log::error('Error archiving Stripe price: ' . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to archive price: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified product with its prices.
     */
    public function show($id)
    {
        try {
            // Get product from Stripe
            $product = $this->stripe->products->retrieve($id, ['expand' => ['default_price']]);

            // Get all prices for this product
            $prices = $this->stripe->prices->all(['product' => $id]);

            // Format product for the frontend
            $formattedProduct = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'active' => $product->active,
                'created' => date('Y-m-d H:i:s', $product->created),
                'default_price' => $product->default_price ? [
                    'id' => $product->default_price->id,
                    'amount' => $product->default_price->unit_amount / 100,
                    'currency' => strtoupper($product->default_price->currency),
                    'interval' => $product->default_price->type === 'recurring' ? $product->default_price->recurring->interval : null,
                    'interval_count' => $product->default_price->type === 'recurring' ? $product->default_price->recurring->interval_count : null,
                    'type' => $product->default_price->type,
                ] : null,
                'metadata' => $product->metadata,
            ];

            // Format prices for the frontend
            $formattedPrices = collect($prices->data)->map(function ($price) use ($product) {
                return [
                    'id' => $price->id,
                    'amount' => $price->unit_amount / 100,
                    'currency' => strtoupper($price->currency),
                    'interval' => $price->type === 'recurring' ? $price->recurring->interval : null,
                    'interval_count' => $price->type === 'recurring' ? $price->recurring->interval_count : null,
                    'type' => $price->type,
                    'is_default' => $product->default_price && $product->default_price->id === $price->id,
                    'created' => date('Y-m-d H:i:s', $price->created),
                    'active' => $price->active,
                ];
            });

            return inertia('admin/stripe/ProductDetail', [
                'product' => $formattedProduct,
                'prices' => $formattedPrices,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching Stripe product: ' . $e->getMessage());

            return redirect()->route('admin.stripe.products')->withErrors(['error' => 'Failed to fetch product: ' . $e->getMessage()]);
        }
    }
}
