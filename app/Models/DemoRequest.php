<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class DemoRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'company',
        'phone',
        'message',
        'demo_type',
        'preferred_datetime',
        'timezone',
        'status',
        'user_id',
        'session_id',
        'source',
        'metadata',
        'confirmed_datetime',
        'admin_notes',
    ];

    protected $casts = [
        'preferred_datetime' => 'datetime',
        'confirmed_datetime' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'confirmed_datetime', 'admin_notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for confirmed requests
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope for today's demos
     */
    public function scopeToday($query)
    {
        return $query->whereDate('preferred_datetime', Carbon::today());
    }

    /**
     * Scope for upcoming demos
     */
    public function scopeUpcoming($query)
    {
        return $query->where('preferred_datetime', '>', Carbon::now());
    }

    /**
     * Get formatted preferred datetime in user's timezone
     */
    public function getFormattedPreferredDatetimeAttribute(): string
    {
        return $this->preferred_datetime
            ->setTimezone($this->timezone)
            ->format('M j, Y \a\t g:i A T');
    }

    /**
     * Get formatted confirmed datetime in user's timezone
     */
    public function getFormattedConfirmedDatetimeAttribute(): ?string
    {
        if (!$this->confirmed_datetime) {
            return null;
        }

        return $this->confirmed_datetime
            ->setTimezone($this->timezone)
            ->format('M j, Y \a\t g:i A T');
    }

    /**
     * Check if the demo is in the past
     */
    public function getIsPastAttribute(): bool
    {
        $datetime = $this->confirmed_datetime ?? $this->preferred_datetime;
        return $datetime->isPast();
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'green',
            'completed' => 'blue',
            'cancelled' => 'red',
            'rescheduled' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get demo type label
     */
    public function getDemoTypeLabelAttribute(): string
    {
        return match ($this->demo_type) {
            'general' => 'General Demo',
            'enterprise' => 'Enterprise Demo',
            'specific-feature' => 'Feature-Specific Demo',
            'custom' => 'Custom Demo',
            default => 'General Demo',
        };
    }
}
