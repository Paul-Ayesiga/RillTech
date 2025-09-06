<?php

namespace App\Jobs;

use App\Mail\WelcomeEmail;
use App\Models\User;
use App\Notifications\NewUserRegistered;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class SendWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The email address to send the welcome email to.
     */
    protected string $email;

    /**
     * The name of the user.
     */
    protected string $name;

    /**
     * Create a new job instance.
     */
    public function __construct(string $email, string $name)
    {
        $this->email = $email;
        $this->name = $name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send a welcome email to the user using our template
        // Mail::to($this->email)->send(new WelcomeEmail($this->name, $this->email));

        // Find the user by email
        $user = User::where('email', $this->email)->first();

        if ($user) {
            // Get all users with admin and super-admin roles
            $superAdmins = Role::where('name', 'super-admin')->get();
            // $adminRole = Role::where('name', 'admin')->first();

            $adminUsers = User::role('super-admin')->get();

            // Send notification to all admin users
            foreach ($adminUsers as $admin) {
                $admin->notify(new NewUserRegistered($user));
            }
        }
    }
}
