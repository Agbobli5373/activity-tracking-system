<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            [
                'key' => 'password_policy',
                'value' => [
                    'min_length' => 8,
                    'require_uppercase' => true,
                    'require_lowercase' => true,
                    'require_numbers' => true,
                    'require_symbols' => false,
                    'expiry_days' => 90,
                    'prevent_reuse' => 5
                ],
                'type' => 'json',
                'description' => 'Password policy configuration'
            ],
            [
                'key' => 'security_settings',
                'value' => [
                    'session_timeout' => 120,
                    'max_login_attempts' => 5,
                    'lockout_duration' => 15,
                    'require_2fa' => false,
                    'maintenance_mode' => false
                ],
                'type' => 'json',
                'description' => 'Security configuration settings'
            ],
            [
                'key' => 'notification_settings',
                'value' => [
                    'smtp_host' => '',
                    'smtp_port' => 587,
                    'smtp_username' => '',
                    'smtp_password' => '',
                    'smtp_encryption' => 'tls',
                    'from_email' => 'noreply@activitytracker.com',
                    'from_name' => 'Activity Tracking System'
                ],
                'type' => 'json',
                'description' => 'Email notification configuration'
            ],
            [
                'key' => 'general_settings',
                'value' => [
                    'app_name' => 'Activity Tracking System',
                    'app_description' => 'Comprehensive activity and task management system',
                    'default_timezone' => 'UTC',
                    'date_format' => 'Y-m-d',
                    'time_format' => 'H:i',
                    'items_per_page' => 25
                ],
                'type' => 'json',
                'description' => 'General application settings'
            ],
            [
                'key' => 'audit_settings',
                'value' => [
                    'log_user_actions' => true,
                    'log_system_changes' => true,
                    'retention_days' => 365,
                    'log_failed_logins' => true,
                    'log_permission_changes' => true
                ],
                'type' => 'json',
                'description' => 'Audit logging configuration'
            ]
        ];

        foreach ($settings as $setting) {
            \App\Models\SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
