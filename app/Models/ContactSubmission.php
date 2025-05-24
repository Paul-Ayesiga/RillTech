<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ContactSubmission extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'subject',
        'message',
        'status',
        'priority',
        'ip_address',
        'user_agent',
        'source',
        'responded_at',
        'assigned_to',
        'admin_notes',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    /**
     * Get the assigned user
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope for new submissions
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for in progress submissions
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for resolved submissions
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for high priority submissions
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Scope for urgent submissions
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    /**
     * Check if submission is new
     */
    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    /**
     * Check if submission is urgent
     */
    public function isUrgent(): bool
    {
        return $this->priority === 'urgent';
    }

    /**
     * Mark as responded
     */
    public function markAsResponded(): void
    {
        $this->update([
            'responded_at' => now(),
            'status' => 'in_progress',
        ]);
    }

    /**
     * Assign to user
     */
    public function assignTo(User $user): void
    {
        $this->update([
            'assigned_to' => $user->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'new' => 'blue',
            'in_progress' => 'yellow',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get priority badge color
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray',
        };
    }

    /**
     * Configure activity logging options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'subject', 'status', 'priority', 'assigned_to'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                return match($eventName) {
                    'created' => $this->getCreatedDescription(),
                    'updated' => $this->getUpdatedDescription(),
                    'deleted' => "Contact submission deleted: {$this->subject} from {$this->name} ({$this->email})",
                    default => "Contact submission {$eventName}: {$this->subject}"
                };
            })
            ->useLogName('contact_management');
    }

    /**
     * Get detailed description for contact submission creation
     */
    private function getCreatedDescription(): string
    {
        $priority = $this->priority ? " [{$this->priority} priority]" : "";
        return "New contact submission received: '{$this->subject}' from {$this->name} ({$this->email}){$priority}";
    }

    /**
     * Get detailed description for contact submission updates
     */
    private function getUpdatedDescription(): string
    {
        $changes = [];
        $dirty = $this->getDirty();
        $original = $this->getOriginal();

        if (isset($dirty['status'])) {
            $oldStatus = $original['status'] ?? 'unknown';
            $newStatus = $dirty['status'];
            $changes[] = match($newStatus) {
                'new' => 'marked as new',
                'in_progress' => 'marked as in progress',
                'resolved' => 'marked as resolved',
                'closed' => 'closed',
                default => "status changed from '{$oldStatus}' to '{$newStatus}'"
            };
        }

        if (isset($dirty['priority'])) {
            $oldPriority = $original['priority'] ?? 'unknown';
            $newPriority = $dirty['priority'];
            $changes[] = "priority changed from '{$oldPriority}' to '{$newPriority}'";
        }

        if (isset($dirty['assigned_to'])) {
            if ($dirty['assigned_to']) {
                $assignedUser = \App\Models\User::find($dirty['assigned_to']);
                $newAssignee = $assignedUser ? $assignedUser->name : "user ID {$dirty['assigned_to']}";
                $changes[] = $original['assigned_to'] ? "reassigned to {$newAssignee}" : "assigned to {$newAssignee}";
            } else {
                $changes[] = "unassigned";
            }
        }

        $changeText = implode(', ', $changes);
        $identifier = "'{$this->subject}' from {$this->name}";
        return "Contact submission updated for {$identifier}: {$changeText}";
    }
}
