<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\NewUserRegistered;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class TriggerUserCreatedEvent extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:trigger-user-created {user_id? : The ID of the user to use (defaults to a random user)} {--admin= : The ID of the admin to notify (defaults to all admins)}';

    /**
     * The console command description.
     */
    protected $description = 'Trigger a user created event for testing notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the user to use as the new user
        $userId = $this->argument('user_id');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
        } else {
            // Get a random user
            $user = User::inRandomOrder()->first();
            if (!$user) {
                $this->error("No users found in the database.");
                return 1;
            }
        }

        $this->info("Using user: {$user->name} ({$user->email})");

        // Get the admin(s) to notify
        $adminId = $this->option('admin');

        if ($adminId) {
            $admin = User::find($adminId);
            if (!$admin) {
                $this->error("Admin with ID {$adminId} not found.");
                return 1;
            }

            $admins = collect([$admin]);
            $this->info("Notifying admin: {$admin->name} ({$admin->email})");
        } else {
            // Get all users with admin and super-admin roles
            $superAdminRole = Role::where('name', 'super-admin')->first();
            $adminRole = Role::where('name', 'admin')->first();

            if (!$superAdminRole && !$adminRole) {
                $this->error("No admin or super-admin roles found.");
                return 1;
            }

            $roles = collect();
            if ($superAdminRole) $roles->push($superAdminRole);
            if ($adminRole) $roles->push($adminRole);

            $admins = User::role($roles)->get();

            if ($admins->isEmpty()) {
                $this->error("No admin users found.");
                return 1;
            }

            $this->info("Notifying " . $admins->count() . " admin users");
        }

        // Send the notification to each admin
        foreach ($admins as $admin) {
            $admin->notify(new NewUserRegistered($user));
            $this->info("Notification sent to {$admin->name}");
        }

        $this->info("User created event triggered successfully!");

        return 0;
    }
}
