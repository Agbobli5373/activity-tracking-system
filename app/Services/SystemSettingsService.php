<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SystemSettingsService
{
    /**
     * Get settings for a specific section
     * 
     * @param string $section
     * @return array
     */
    public function getSettings(string $section): array
    {
        $cacheKey = "system_settings_{$section}";
        
        return Cache::remember($cacheKey, 3600, function () use ($section) {
            $settings = SystemSetting::where('key', 'like', "{$section}.%")->get();
            
            $result = [];
            foreach ($settings as $setting) {
                $key = str_replace("{$section}.", '', $setting->key);
                $result[$key] = $setting->value;
            }
            
            return $result;
        });
    }

    /**
     * Update settings for a specific section
     * 
     * @param string $section
     * @param array $settings
     * @return void
     * @throws ValidationException
     */
    public function updateSettings(string $section, array $settings): void
    {
        // Validate settings based on section
        $this->validateSectionSettings($section, $settings);

        try {
            foreach ($settings as $key => $value) {
                $settingKey = "{$section}.{$key}";
                
                SystemSetting::updateOrCreate(
                    ['key' => $settingKey],
                    [
                        'value' => $value,
                        'type' => $this->getSettingType($value),
                    ]
                );
            }

            // Clear cache for this section
            Cache::forget("system_settings_{$section}");

            Log::info('System settings updated', [
                'section' => $section,
                'settings_count' => count($settings),
                'updated_by' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update system settings', [
                'section' => $section,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate password policy settings
     * 
     * @param array $policy
     * @return bool
     * @throws ValidationException
     */
    public function validatePasswordPolicy(array $policy): bool
    {
        $validator = Validator::make($policy, [
            'min_length' => 'required|integer|min:6|max:128',
            'max_length' => 'nullable|integer|min:8|max:256',
            'require_uppercase' => 'boolean',
            'require_lowercase' => 'boolean',
            'require_numbers' => 'boolean',
            'require_symbols' => 'boolean',
            'expiry_days' => 'nullable|integer|min:30|max:365',
            'history_count' => 'nullable|integer|min:1|max:24',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Additional business logic validation
        if (isset($policy['max_length']) && $policy['max_length'] < $policy['min_length']) {
            throw ValidationException::withMessages([
                'max_length' => 'Maximum length must be greater than minimum length.'
            ]);
        }

        return true;
    }

    /**
     * Test email configuration
     * 
     * @param array $config
     * @return bool
     */
    public function testEmailConfiguration(array $config): bool
    {
        try {
            // Temporarily set mail configuration
            config([
                'mail.mailers.smtp.host' => $config['smtp_host'],
                'mail.mailers.smtp.port' => $config['smtp_port'],
                'mail.mailers.smtp.username' => $config['smtp_username'],
                'mail.mailers.smtp.password' => $config['smtp_password'],
                'mail.mailers.smtp.encryption' => $config['smtp_encryption'] ?? 'tls',
                'mail.from.address' => $config['from_address'],
                'mail.from.name' => $config['from_name'],
            ]);

            // Send test email
            Mail::raw('This is a test email from the Activity Tracking System.', function ($message) use ($config) {
                $message->to($config['test_email'] ?? $config['from_address'])
                        ->subject('Email Configuration Test');
            });

            Log::info('Email configuration test successful', [
                'smtp_host' => $config['smtp_host'],
                'tested_by' => auth()->id(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Email configuration test failed', [
                'error' => $e->getMessage(),
                'config' => array_except($config, ['smtp_password']),
            ]);
            return false;
        }
    }

    /**
     * Get audit settings
     * 
     * @return array
     */
    public function getAuditSettings(): array
    {
        return $this->getSettings('audit');
    }

    /**
     * Export all system settings
     * 
     * @return array
     */
    public function exportSettings(): array
    {
        $settings = SystemSetting::all();
        
        $export = [];
        foreach ($settings as $setting) {
            $export[$setting->key] = [
                'value' => $setting->value,
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        }

        Log::info('System settings exported', [
            'settings_count' => count($export),
            'exported_by' => auth()->id(),
        ]);

        return $export;
    }

    /**
     * Import system settings
     * 
     * @param array $settings
     * @return void
     */
    public function importSettings(array $settings): void
    {
        try {
            foreach ($settings as $key => $data) {
                SystemSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $data['value'],
                        'type' => $data['type'] ?? $this->getSettingType($data['value']),
                        'description' => $data['description'] ?? null,
                    ]
                );
            }

            // Clear all settings cache
            Cache::flush();

            Log::info('System settings imported', [
                'settings_count' => count($settings),
                'imported_by' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to import system settings', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate settings based on section
     * 
     * @param string $section
     * @param array $settings
     * @return void
     * @throws ValidationException
     */
    private function validateSectionSettings(string $section, array $settings): void
    {
        $rules = [];

        switch ($section) {
            case 'general':
                $rules = [
                    'app_name' => 'required|string|max:255',
                    'session_timeout' => 'required|integer|min:5|max:1440',
                    'maintenance_mode' => 'boolean',
                    'timezone' => 'required|string|timezone',
                ];
                break;

            case 'security':
                $rules = [
                    'max_login_attempts' => 'required|integer|min:3|max:10',
                    'lockout_duration' => 'required|integer|min:5|max:60',
                    'password_policy' => 'required|array',
                    'two_factor_enabled' => 'boolean',
                ];
                break;

            case 'notifications':
                $rules = [
                    'smtp_host' => 'required|string',
                    'smtp_port' => 'required|integer|min:1|max:65535',
                    'smtp_username' => 'required|string',
                    'smtp_password' => 'required|string',
                    'from_address' => 'required|email',
                    'from_name' => 'required|string|max:255',
                ];
                break;
        }

        if (!empty($rules)) {
            $validator = Validator::make($settings, $rules);
            
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }

        // Additional validation for password policy
        if ($section === 'security' && isset($settings['password_policy'])) {
            $this->validatePasswordPolicy($settings['password_policy']);
        }
    }

    /**
     * Determine the type of a setting value
     * 
     * @param mixed $value
     * @return string
     */
    private function getSettingType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_array($value)) {
            return 'array';
        } else {
            return 'string';
        }
    }
}