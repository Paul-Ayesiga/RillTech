<?php

namespace App\Jobs;

use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendBulkNewsletterEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subscriptionIds;
    public $subject;
    public $emailContent;
    public $adminUser;
    public $campaignId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $subscriptionIds,
        string $subject,
        string $emailContent,
        User $adminUser
    ) {
        $this->subscriptionIds = $subscriptionIds;
        $this->subject = $subject;
        $this->emailContent = $emailContent;
        $this->adminUser = $adminUser;
        $this->campaignId = Str::uuid()->toString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting bulk newsletter email campaign', [
                'campaign_id' => $this->campaignId,
                'total_recipients' => count($this->subscriptionIds),
                'admin_id' => $this->adminUser->id,
            ]);

            $subscriptions = NewsletterSubscription::whereIn('id', $this->subscriptionIds)
                ->where('status', 'active')
                ->get();

            $successCount = 0;
            $batchSize = 50; // Process in batches to avoid overwhelming the queue

            foreach ($subscriptions->chunk($batchSize) as $batch) {
                foreach ($batch as $subscription) {
                    // Dispatch individual email job with delay to avoid rate limiting
                    SendNewsletterEmail::dispatch(
                        $subscription,
                        $this->subject,
                        $this->emailContent,
                        $this->adminUser,
                        $this->campaignId
                    )->delay(now()->addSeconds($successCount * 2)); // 2-second delay between emails

                    $successCount++;
                }

                // Small delay between batches
                sleep(1);
            }

            Log::info('Bulk newsletter email campaign queued successfully', [
                'campaign_id' => $this->campaignId,
                'emails_queued' => $successCount,
                'admin_id' => $this->adminUser->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue bulk newsletter emails', [
                'campaign_id' => $this->campaignId,
                'admin_id' => $this->adminUser->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk newsletter email campaign failed', [
            'campaign_id' => $this->campaignId,
            'admin_id' => $this->adminUser->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
