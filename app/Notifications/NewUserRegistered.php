<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The new user instance.
     */
    protected User $newUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $newUser)
    {
        $this->newUser = $newUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'id' => $this->newUser->id,
            'name' => $this->newUser->name,
            'email' => $this->newUser->email,
            'created_at' => $this->newUser->created_at,
            'message' => 'New user registered: ' . $this->newUser->name,
            'type' => 'user_registered'
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->newUser->id,
                'name' => $this->newUser->name,
                'email' => $this->newUser->email,
                'created_at' => $this->newUser->created_at,
            ],
            'message' => 'New user registered: ' . $this->newUser->name,
            'type' => 'user_registered',
            'created_at' => now()->toIso8601String(),
        ];
    }
}
