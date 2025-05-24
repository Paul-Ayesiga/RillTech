<?php

namespace App\Mail;

use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subscription;
    public $subject;
    public $emailContent;
    public $adminUser;
    public $unsubscribeToken;

    /**
     * Create a new message instance.
     */
    public function __construct(
        NewsletterSubscription $subscription, 
        string $subject, 
        string $emailContent, 
        User $adminUser,
        string $unsubscribeToken
    ) {
        $this->subscription = $subscription;
        $this->subject = $subject;
        $this->emailContent = $emailContent;
        $this->adminUser = $adminUser;
        $this->unsubscribeToken = $unsubscribeToken;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->subscription->email,
            subject: $this->subject,
            replyTo: $this->adminUser->email,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter',
            with: [
                'subscription' => $this->subscription,
                'subject' => $this->subject,
                'emailContent' => $this->emailContent,
                'adminUser' => $this->adminUser,
                'unsubscribeToken' => $this->unsubscribeToken,
                'unsubscribeUrl' => route('newsletter.unsubscribe', ['token' => $this->unsubscribeToken]),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
