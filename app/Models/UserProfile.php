<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'avatar',
        'bio',
        'timezone',
        'date_format',
        'time_format',
        'language',
        'dashboard_settings',
        'notification_preferences'
    ];

    protected $casts = [
        'dashboard_settings' => 'json',
        'notification_preferences' => 'json'
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the default dashboard settings.
     */
    public function getDefaultDashboardSettings(): array
    {
        return [
            'default_date_range' => '30_days',
            'default_view' => 'list',
            'items_per_page' => 25,
            'show_completed' => true,
            'default_sort' => 'created_at_desc'
        ];
    }

    /**
     * Get the default notification preferences.
     */
    public function getDefaultNotificationPreferences(): array
    {
        return [
            'email_notifications' => true,
            'in_app_notifications' => true,
            'activity_assigned' => true,
            'activity_completed' => true,
            'activity_overdue' => true,
            'system_maintenance' => true
        ];
    }
}
