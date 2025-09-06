<?php

namespace App\Events;

use App\Models\ContactSubmission;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class userContacted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ContactSubmission $contact_submission;

    /**
     * Create a new event instance.
     */
    public function __construct(ContactSubmission $contact_submission)
    {
        $this->contact_submission = $contact_submission;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user-contacted'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'email' => $this->contact_submission->email,
            'message' => $this->contact_submission->message
        ];
    }
}
