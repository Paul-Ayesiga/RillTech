<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, Billable, LogsActivity;
    //  , BroadcastsEvents ;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'string',
        ];
    }

    /**
     * Configure activity logging options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                return match($eventName) {
                    'created' => "New user account created: {$this->name} ({$this->email})",
                    'updated' => $this->getUpdateDescription(),
                    'deleted' => "User account deleted: {$this->name} ({$this->email})",
                    default => "User {$eventName}: {$this->name}"
                };
            })
            ->useLogName('user_management');
    }

    /**
     * Get detailed description for user updates
     */
    private function getUpdateDescription(): string
    {
        $changes = [];
        $dirty = $this->getDirty();
        $original = $this->getOriginal();

        if (isset($dirty['name'])) {
            $changes[] = "name changed from '{$original['name']}' to '{$dirty['name']}'";
        }

        if (isset($dirty['email'])) {
            $changes[] = "email changed from '{$original['email']}' to '{$dirty['email']}'";
        }

        if (isset($dirty['status'])) {
            $oldStatus = $original['status'] ?? 'unknown';
            $newStatus = $dirty['status'];
            $changes[] = match($newStatus) {
                'active' => $oldStatus === 'suspended' ? 'account reactivated' : 'account activated',
                'suspended' => 'account suspended',
                'banned' => 'account banned',
                default => "status changed from '{$oldStatus}' to '{$newStatus}'"
            };
        }

        $changeText = implode(', ', $changes);
        return "User profile updated for {$this->name}: {$changeText}";
    }

    // public function broadcastOn($event): array
    // {
    //     return [$this];
    // }
}
