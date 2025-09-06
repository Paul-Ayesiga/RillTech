<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class NewsletterSubscription extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'email',
        'name',
        'status',
        'subscribed_at',
        'unsubscribed_at',
        'unsubscribe_token',
        'ip_address',
        'user_agent',
        'source',
        'last_email_sent_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'last_email_sent_at' => 'datetime',
    ];

    /**
     * Boot the model and generate unsubscribe token
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (empty($subscription->unsubscribe_token)) {
                $subscription->unsubscribe_token = Str::random(64);
            }
        });
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for unsubscribed
     */
    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Unsubscribe the user
     */
    public function unsubscribe(): void
    {
        $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }

    /**
     * Resubscribe the user
     */
    public function resubscribe(): void
    {
        $this->update([
            'status' => 'active',
            'unsubscribed_at' => null,
            'subscribed_at' => now(),
        ]);
    }

    /**
     * Get unsubscribe URL
     */
    public function getUnsubscribeUrlAttribute(): string
    {
        return route('newsletter.unsubscribe', $this->unsubscribe_token);
    }

    /**
     * Configure activity logging options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['email', 'name', 'status', 'source'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                return match($eventName) {
                    'created' => $this->getCreatedDescription(),
                    'updated' => $this->getUpdatedDescription(),
                    'deleted' => "Newsletter subscription deleted: {$this->email}" . ($this->name ? " ({$this->name})" : ""),
                    default => "Newsletter subscription {$eventName}: {$this->email}"
                };
            })
            ->useLogName('newsletter_management');
    }

    /**
     * Get detailed description for newsletter subscription creation
     */
    private function getCreatedDescription(): string
    {
        $source = $this->source ? " via {$this->source}" : "";
        $name = $this->name ? " ({$this->name})" : "";
        return "New newsletter subscription: {$this->email}{$name}{$source}";
    }

    /**
     * Get detailed description for newsletter subscription updates
     */
    private function getUpdatedDescription(): string
    {
        $changes = [];
        $dirty = $this->getDirty();
        $original = $this->getOriginal();

        if (isset($dirty['email'])) {
            $changes[] = "email changed from '{$original['email']}' to '{$dirty['email']}'";
        }

        if (isset($dirty['name'])) {
            $oldName = $original['name'] ?? 'no name';
            $newName = $dirty['name'] ?? 'no name';
            $changes[] = "name changed from '{$oldName}' to '{$newName}'";
        }

        if (isset($dirty['status'])) {
            $oldStatus = $original['status'] ?? 'unknown';
            $newStatus = $dirty['status'];
            $changes[] = match($newStatus) {
                'active' => $oldStatus === 'unsubscribed' ? 'resubscribed to newsletter' : 'subscription activated',
                'unsubscribed' => 'unsubscribed from newsletter',
                default => "status changed from '{$oldStatus}' to '{$newStatus}'"
            };
        }

        $changeText = implode(', ', $changes);
        $identifier = $this->name ? "{$this->name} ({$this->email})" : $this->email;
        return "Newsletter subscription updated for {$identifier}: {$changeText}";
    }
}
