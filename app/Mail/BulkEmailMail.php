<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BulkEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $emailSubject;
    public $emailMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $subject, string $message)
    {
        $this->user = $user;
        $this->emailSubject = $subject;
        $this->emailMessage = $message;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bulk-email',
            with: [
                'user' => $this->user,
                'emailMessage' => $this->emailMessage,
                'emailSubject' => $this->emailSubject,
            ]
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
