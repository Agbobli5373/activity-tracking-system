<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'request_data',
        'session_id',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'request_data' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that was affected.
     */
    public function model()
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }
        
        return null;
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by model type.
     */
    public function scopeByModelType($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get logs by IP address.
     */
    public function scopeByIpAddress($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Get a human-readable description of the action.
     */
    public function getActionDescriptionAttribute(): string
    {
        $descriptions = [
            'login_success' => 'Successfully logged in',
            'login_failed' => 'Failed login attempt',
            'logout' => 'Logged out',
            'activity_created' => 'Created new activity',
            'activity_updated' => 'Updated activity',
            'activity_deleted' => 'Deleted activity',
            'activity_status_changed' => 'Changed activity status',
            'report_generated' => 'Generated report',
            'user_created' => 'Created new user',
            'user_updated' => 'Updated user',
            'password_changed' => 'Changed password',
            'unauthorized_access' => 'Attempted unauthorized access',
        ];

        return $descriptions[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    /**
     * Get changes summary for display.
     */
    public function getChangesSummaryAttribute(): ?string
    {
        if (!$this->old_values || !$this->new_values) {
            return null;
        }

        $changes = [];
        $oldValues = $this->old_values;
        $newValues = $this->new_values;

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[] = "{$key}: '{$oldValue}' → '{$newValue}'";
            }
        }

        return implode(', ', $changes);
    }
}