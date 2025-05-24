<?php

namespace App\Jobs;

use App\Mail\NewsletterEmail;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendNewsletterEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subscription;
    public $subject;
    public $emailContent;
    public $adminUser;
    public $campaignId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        NewsletterSubscription $subscription,
        string $subject,
        string $emailContent,
        User $adminUser,
        string $campaignId
    ) {
        $this->subscription = $subscription;
        $this->subject = $subject;
        $this->emailContent = $emailContent;
        $this->adminUser = $adminUser;
        $this->campaignId = $campaignId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Generate unique unsubscribe token for this email
            $unsubscribeToken = Str::random(64);
            
            // Store the unsubscribe token (you might want to create a separate table for this)
            $this->subscription->update([
                'unsubscribe_token' => $unsubscribeToken,
                'last_email_sent_at' => now(),
            ]);

            // Send the email
            Mail::send(new NewsletterEmail(
                $this->subscription,
                $this->subject,
                $this->emailContent,
                $this->adminUser,
                $unsubscribeToken
            ));

            Log::info('Newsletter email sent successfully', [
                'campaign_id' => $this->campaignId,
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
                'admin_id' => $this->adminUser->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send newsletter email', [
                'campaign_id' => $this->campaignId,
                'subscription_id' => $this->subscription->id,
                'email' => $this->subscription->email,
                'admin_id' => $this->adminUser->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Newsletter email job failed permanently', [
            'campaign_id' => $this->campaignId,
            'subscription_id' => $this->subscription->id,
            'email' => $this->subscription->email,
            'admin_id' => $this->adminUser->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
