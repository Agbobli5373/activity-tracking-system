<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityUpdate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'activity_id',
        'user_id',
        'previous_status',
        'new_status',
        'remarks',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['user:id,name,role'];

    /**
     * Validation rules for the ActivityUpdate model.
     *
     * @return array<string, string>
     */
    public static function validationRules(): array
    {
        return [
            'activity_id' => 'required|exists:activities,id',
            'user_id' => 'required|exists:users,id',
            'previous_status' => 'nullable|in:pending,done',
            'new_status' => 'required|in:pending,done',
            'remarks' => 'required|string|min:10',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string',
        ];
    }

    /**
     * Get the activity that this update belongs to.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the user who made this update.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include updates for a specific activity.
     */
    public function scopeForActivity($query, $activityId)
    {
        return $query->where('activity_id', $activityId);
    }

    /**
     * Scope a query to only include updates by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include updates with a specific status change.
     */
    public function scopeStatusChange($query, $fromStatus, $toStatus)
    {
        return $query->where('previous_status', $fromStatus)
                    ->where('new_status', $toStatus);
    }

    /**
     * Scope a query to order updates chronologically.
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope a query to order updates in reverse chronological order.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Check if this update represents a status change to 'done'.
     */
    public function isCompletion(): bool
    {
        return $this->new_status === 'done';
    }

    /**
     * Check if this update represents a status change to 'pending'.
     */
    public function isReopening(): bool
    {
        return $this->new_status === 'pending' && $this->previous_status === 'done';
    }

    /**
     * Get a human-readable description of the status change.
     */
    public function getStatusChangeDescription(): string
    {
        if ($this->previous_status === null) {
            return "Activity created with status '{$this->new_status}'";
        }

        if ($this->previous_status === $this->new_status) {
            return "Status updated (no change from '{$this->new_status}')";
        }

        return "Status changed from '{$this->previous_status}' to '{$this->new_status}'";
    }

    /**
     * Create an audit trail entry for an activity status update.
     */
    public static function createAuditEntry(
        int $activityId,
        int $userId,
        ?string $previousStatus,
        string $newStatus,
        string $remarks,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'activity_id' => $activityId,
            'user_id' => $userId,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'remarks' => $remarks,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
