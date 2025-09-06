<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Stripe\StripeClient;

class SyncStripeSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync-subscriptions {--user-id= : Sync for specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync subscriptions from Stripe to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stripe = new StripeClient(config('cashier.secret'));

        $userId = $this->option('user-id');

        if ($userId) {
            $users = User::where('id', $userId)->whereNotNull('stripe_id')->get();
        } else {
            $users = User::whereNotNull('stripe_id')->get();
        }

        $this->info("Syncing subscriptions for {$users->count()} users...");

        foreach ($users as $user) {
            try {
                $this->info("Syncing subscriptions for user {$user->id} (Stripe: {$user->stripe_id})");

                // Get all subscriptions for this customer
                $subscriptions = $stripe->subscriptions->all([
                    'customer' => $user->stripe_id,
                    'limit' => 100
                ]);

                foreach ($subscriptions->data as $subscription) {
                    $this->info("  - Subscription {$subscription->id} ({$subscription->status})");

                    // Create or update subscription
                    $subscriptionModel = $user->subscriptions()->updateOrCreate(
                        ['stripe_id' => $subscription->id],
                        [
                            'type' => 'default',
                            'stripe_status' => $subscription->status,
                            'stripe_price' => $subscription->items->data[0]->price->id,
                            'quantity' => $subscription->items->data[0]->quantity,
                            'trial_ends_at' => $subscription->trial_end ? \Carbon\Carbon::createFromTimestamp($subscription->trial_end) : null,
                            'ends_at' => $subscription->canceled_at ? \Carbon\Carbon::createFromTimestamp($subscription->canceled_at) : null,
                            'created_at' => \Carbon\Carbon::createFromTimestamp($subscription->created),
                            'updated_at' => now(),
                        ]
                    );

                    // Sync subscription items
                    foreach ($subscription->items->data as $item) {
                        $subscriptionModel->items()->updateOrCreate(
                            ['stripe_id' => $item->id],
                            [
                                'stripe_product' => $item->price->product,
                                'stripe_price' => $item->price->id,
                                'quantity' => $item->quantity,
                                'created_at' => \Carbon\Carbon::createFromTimestamp($item->created),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }

                $this->info("  ✓ Synced {$subscriptions->count()} subscriptions");

            } catch (\Exception $e) {
                $this->error("  ✗ Error syncing user {$user->id}: " . $e->getMessage());
            }
        }

        $this->info('Sync completed!');
    }
}
