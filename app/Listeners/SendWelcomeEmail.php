<?php

namespace App\Listeners;

use App\Events\userCreated;
use App\Jobs\SendWelcomeEmail as SendWelcomeEmailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(userCreated $event): void
    {
        // Get the user's email and name from the event
        $email = $event->user->email;
        $name = $event->user->name;

        // Dispatch the job to send the welcome email
        SendWelcomeEmailJob::dispatch($email, $name);
    }
}
