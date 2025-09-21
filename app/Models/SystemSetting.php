<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description'
    ];

    protected $casts = [
        'value' => 'json'
    ];

    /**
     * Get a system setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a system setting value by key.
     */
    public static function set(string $key, $value, string $type = 'string', string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description
            ]
        );

        Cache::forget("system_setting_{$key}");
    }

    /**
     * Get password policy settings.
     */
    public static function getPasswordPolicy(): array
    {
        return static::get('password_policy', [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
            'expiry_days' => 90,
            'prevent_reuse' => 5
        ]);
    }

    /**
     * Get notification settings.
     */
    public static function getNotificationSettings(): array
    {
        return static::get('notification_settings', [
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'from_email' => '',
            'from_name' => 'Activity Tracking System'
        ]);
    }

    /**
     * Get security settings.
     */
    public static function getSecuritySettings(): array
    {
        return static::get('security_settings', [
            'session_timeout' => 120, // minutes
            'max_login_attempts' => 5,
            'lockout_duration' => 15, // minutes
            'require_2fa' => false,
            'maintenance_mode' => false
        ]);
    }
}
