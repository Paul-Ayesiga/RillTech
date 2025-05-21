<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends Controller
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Display the webhook configuration page.
     */
    public function index()
    {
        try {
            // Get webhook endpoints from Stripe
            $webhooks = $this->stripe->webhookEndpoints->all();
            
            // Format webhooks for the frontend
            $formattedWebhooks = collect($webhooks->data)->map(function ($webhook) {
                return [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'status' => $webhook->status,
                    'events' => $webhook->enabled_events,
                    'created' => date('Y-m-d H:i:s', $webhook->created),
                    'api_version' => $webhook->api_version,
                    'secret' => $webhook->secret ? '••••••••' : null,
                ];
            });
            
            // Get the default webhook events from Cashier
            $defaultEvents = config('cashier.webhook.events');
            
            return inertia('admin/stripe/Webhooks', [
                'webhooks' => $formattedWebhooks,
                'defaultEvents' => $defaultEvents,
                'webhookUrl' => route('cashier.webhook'),
                'webhookSecret' => config('cashier.webhook.secret') ? '••••••••' : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching webhook endpoints: ' . $e->getMessage());
            
            return inertia('admin/stripe/Webhooks', [
                'error' => 'Unable to fetch webhook endpoints. Please check your Stripe API keys and try again.'
            ]);
        }
    }

    /**
     * Create a new webhook endpoint.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'string',
            'description' => 'nullable|string',
        ]);
        
        try {
            // Create webhook endpoint in Stripe
            $webhook = $this->stripe->webhookEndpoints->create([
                'url' => $validated['url'],
                'enabled_events' => $validated['events'],
                'description' => $validated['description'] ?? 'Created from admin dashboard',
            ]);
            
            return redirect()->route('admin.stripe.webhooks')->with('success', 'Webhook endpoint created successfully. Secret: ' . $webhook->secret);
        } catch (\Exception $e) {
            Log::error('Error creating webhook endpoint: ' . $e->getMessage());
            
            return redirect()->back()->withErrors(['error' => 'Failed to create webhook endpoint: ' . $e->getMessage()]);
        }
    }

    /**
     * Update a webhook endpoint.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'events' => 'required|array',
            'events.*' => 'string',
            'description' => 'nullable|string',
        ]);
        
        try {
            // Update webhook endpoint in Stripe
            $this->stripe->webhookEndpoints->update($id, [
                'enabled_events' => $validated['events'],
                'description' => $validated['description'] ?? 'Updated from admin dashboard',
            ]);
            
            return redirect()->route('admin.stripe.webhooks')->with('success', 'Webhook endpoint updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating webhook endpoint: ' . $e->getMessage());
            
            return redirect()->back()->withErrors(['error' => 'Failed to update webhook endpoint: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a webhook endpoint.
     */
    public function destroy($id)
    {
        try {
            // Delete webhook endpoint in Stripe
            $this->stripe->webhookEndpoints->delete($id);
            
            return redirect()->route('admin.stripe.webhooks')->with('success', 'Webhook endpoint deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting webhook endpoint: ' . $e->getMessage());
            
            return redirect()->back()->withErrors(['error' => 'Failed to delete webhook endpoint: ' . $e->getMessage()]);
        }
    }

    /**
     * Display recent webhook events.
     */
    public function events()
    {
        try {
            // Get recent events from Stripe
            $events = $this->stripe->events->all(['limit' => 20]);
            
            // Format events for the frontend
            $formattedEvents = collect($events->data)->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => $event->type,
                    'created' => date('Y-m-d H:i:s', $event->created),
                    'data' => [
                        'object' => $event->data->object,
                    ],
                    'request' => $event->request,
                    'livemode' => $event->livemode,
                ];
            });
            
            return inertia('admin/stripe/WebhookEvents', [
                'events' => $formattedEvents,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching webhook events: ' . $e->getMessage());
            
            return inertia('admin/stripe/WebhookEvents', [
                'error' => 'Unable to fetch webhook events. Please check your Stripe API keys and try again.'
            ]);
        }
    }
}
