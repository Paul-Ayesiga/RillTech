<?php

namespace App\Mail;

use App\Models\ContactSubmission;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactSubmissionReply extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $contactSubmission;
    public $replyMessage;
    public $adminUser;

    /**
     * Create a new message instance.
     */
    public function __construct(ContactSubmission $contactSubmission, string $replyMessage, User $adminUser)
    {
        $this->contactSubmission = $contactSubmission;
        $this->replyMessage = $replyMessage;
        $this->adminUser = $adminUser;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            to: $this->contactSubmission->email,
            subject: 'Re: ' . $this->contactSubmission->subject,
            replyTo: $this->adminUser->email,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-submission-reply',
            with: [
                'contactSubmission' => $this->contactSubmission,
                'replyMessage' => $this->replyMessage,
                'adminUser' => $this->adminUser,
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
