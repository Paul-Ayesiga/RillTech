<?php

namespace App\Jobs;

use App\Mail\ContactSubmissionReply;
use App\Models\ContactSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendContactSubmissionReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $contactSubmission;
    public $replyMessage;
    public $adminUser;

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
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(ContactSubmission $contactSubmission, string $replyMessage, User $adminUser)
    {
        $this->contactSubmission = $contactSubmission;
        $this->replyMessage = $replyMessage;
        $this->adminUser = $adminUser;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Send the email
            Mail::send(new ContactSubmissionReply(
                $this->contactSubmission,
                $this->replyMessage,
                $this->adminUser
            ));

            // Update the contact submission to mark as responded
            // Only update responded_at, don't change status as it may have been updated already
            $this->contactSubmission->update([
                'responded_at' => now(),
            ]);

            Log::info('Contact submission reply sent successfully', [
                'submission_id' => $this->contactSubmission->id,
                'admin_id' => $this->adminUser->id,
                'recipient' => $this->contactSubmission->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send contact submission reply', [
                'submission_id' => $this->contactSubmission->id,
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
        Log::error('Contact submission reply job failed permanently', [
            'submission_id' => $this->contactSubmission->id,
            'admin_id' => $this->adminUser->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
