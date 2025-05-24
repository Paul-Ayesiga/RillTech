<?php

namespace App\Traits;

use Spatie\Activitylog\Facades\Activity;

trait LogsAdminActivity
{
    /**
     * Log user management activities
     */
    public function logUserActivity(string $action, $user, array $details = []): void
    {
        $description = match($action) {
            'created' => "Admin created new user: {$user->name} ({$user->email})",
            'updated' => "Admin updated user profile: {$user->name}" . ($details['changes'] ?? ''),
            'deleted' => "Admin deleted user: {$user->name} ({$user->email})",
            'suspended' => "Admin suspended user account: {$user->name} ({$user->email})",
            'activated' => "Admin activated user account: {$user->name} ({$user->email})",
            'banned' => "Admin banned user account: {$user->name} ({$user->email})",
            'role_assigned' => "Admin assigned role '{$details['role']}' to user: {$user->name}",
            'role_removed' => "Admin removed role '{$details['role']}' from user: {$user->name}",
            'bulk_deleted' => "Admin bulk deleted " . count($details['users']) . " users",
            'bulk_emailed' => "Admin sent bulk email to " . count($details['users']) . " users: '{$details['subject']}'",
            'exported' => "Admin exported users data to {$details['format']}",
            default => "Admin performed {$action} on user: {$user->name}"
        };

        Activity::causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties($details)
            ->log($description);
    }

    /**
     * Log newsletter management activities
     */
    public function logNewsletterActivity(string $action, $subscription = null, array $details = []): void
    {
        $description = match($action) {
            'bulk_deleted' => "Admin bulk deleted " . count($details['subscriptions']) . " newsletter subscriptions",
            'bulk_emailed' => "Admin sent newsletter to " . count($details['subscriptions']) . " subscribers: '{$details['subject']}'",
            'exported' => "Admin exported newsletter subscriptions to {$details['format']}",
            'deleted' => $subscription ? "Admin deleted newsletter subscription: {$subscription->email}" : "Admin deleted newsletter subscription",
            default => "Admin performed {$action} on newsletter subscriptions"
        };

        Activity::causedBy(auth()->user())
            ->when($subscription, fn($activity) => $activity->performedOn($subscription))
            ->withProperties($details)
            ->log($description);
    }

    /**
     * Log contact management activities
     */
    public function logContactActivity(string $action, $contact = null, array $details = []): void
    {
        $description = match($action) {
            'status_updated' => "Admin updated contact status to '{$details['status']}': '{$contact->subject}' from {$contact->name}",
            'priority_updated' => "Admin updated contact priority to '{$details['priority']}': '{$contact->subject}' from {$contact->name}",
            'assigned' => "Admin assigned contact to {$details['assignee']}: '{$contact->subject}' from {$contact->name}",
            'unassigned' => "Admin unassigned contact: '{$contact->subject}' from {$contact->name}",
            'replied' => "Admin replied to contact: '{$contact->subject}' from {$contact->name}",
            'bulk_deleted' => "Admin bulk deleted " . count($details['contacts']) . " contact submissions",
            'exported' => "Admin exported contact submissions to {$details['format']}",
            'deleted' => $contact ? "Admin deleted contact submission: '{$contact->subject}' from {$contact->name}" : "Admin deleted contact submission",
            default => "Admin performed {$action} on contact submissions"
        };

        Activity::causedBy(auth()->user())
            ->when($contact, fn($activity) => $activity->performedOn($contact))
            ->withProperties($details)
            ->log($description);
    }

    /**
     * Log role and permission activities
     */
    public function logRoleActivity(string $action, $role = null, array $details = []): void
    {
        $description = match($action) {
            'role_created' => "Admin created new role: '{$role->name}'",
            'role_updated' => "Admin updated role: '{$role->name}'" . ($details['changes'] ?? ''),
            'role_deleted' => "Admin deleted role: '{$details['role_name'] ?? $role->name}'",
            'permission_created' => "Admin created new permission: '{$details['permission_name']}'",
            'permission_updated' => "Admin updated permission: '{$details['permission_name']}'",
            'permission_deleted' => "Admin deleted permission: '{$details['permission_name']}'",
            'permission_group_created' => "Admin created permission group: '{$details['group_name']}'",
            'permission_group_updated' => "Admin updated permission group: '{$details['group_name']}'",
            'permission_group_deleted' => "Admin deleted permission group: '{$details['group_name']}'",
            default => "Admin performed {$action} on roles/permissions"
        };

        Activity::causedBy(auth()->user())
            ->when($role, fn($activity) => $activity->performedOn($role))
            ->withProperties($details)
            ->log($description);
    }

    /**
     * Log system activities
     */
    public function logSystemActivity(string $action, array $details = []): void
    {
        $description = match($action) {
            'login' => "Admin logged into the system",
            'logout' => "Admin logged out of the system",
            'profile_updated' => "Admin updated their profile",
            'password_changed' => "Admin changed their password",
            'settings_updated' => "Admin updated system settings: " . ($details['setting'] ?? 'general'),
            'export_generated' => "Admin generated {$details['type']} export with {$details['count']} records",
            'bulk_operation' => "Admin performed bulk {$details['operation']} on {$details['count']} items",
            default => "Admin performed system action: {$action}"
        };

        Activity::causedBy(auth()->user())
            ->withProperties($details)
            ->log($description);
    }

    /**
     * Log authentication activities
     */
    public function logAuthActivity(string $action, $user = null, array $details = []): void
    {
        $description = match($action) {
            'login_success' => ($user ? "User logged in: {$user->name} ({$user->email})" : "User logged in"),
            'login_failed' => "Failed login attempt for: " . ($details['email'] ?? 'unknown email'),
            'logout' => ($user ? "User logged out: {$user->name}" : "User logged out"),
            'registration' => ($user ? "New user registered: {$user->name} ({$user->email})" : "New user registered"),
            'password_reset_requested' => "Password reset requested for: " . ($details['email'] ?? 'unknown email'),
            'password_reset_completed' => ($user ? "Password reset completed for: {$user->name}" : "Password reset completed"),
            'email_verified' => ($user ? "Email verified for: {$user->name}" : "Email verified"),
            default => "Authentication action: {$action}"
        };

        $activity = Activity::withProperties($details);
        
        if ($user) {
            $activity->causedBy($user)->performedOn($user);
        }

        $activity->log($description);
    }
}
