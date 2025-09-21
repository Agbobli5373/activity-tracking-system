<?php

namespace Tests\Unit\UserManagement;

use Tests\TestCase;
use App\Services\SystemSettingsService;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SystemSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SystemSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SystemSettingsService();
    }

    public function test_get_settings_returns_section_settings()
    {
        SystemSetting::create([
            'key' => 'general.app_name',
            'value' => 'Activity Tracker',
        ]);

        SystemSetting::create([
            'key' => 'general.session_timeout',
            'value' => 30,
        ]);

        SystemSetting::create([
            'key' => 'security.max_login_attempts',
            'value' => 5,
        ]);

        $generalSettings = $this->service->getSettings('general');

        $this->assertEquals([
            'app_name' => 'Activity Tracker',
            'session_timeout' => 30,
        ], $generalSettings);
    }

    public function test_update_settings_creates_and_updates_settings()
    {
        $settings = [
            'app_name' => 'New App Name',
            'session_timeout' => 60,
            'maintenance_mode' => true,
            'timezone' => 'America/New_York',
        ];

        $this->service->updateSettings('general', $settings);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'general.app_name',
            'value' => json_encode('New App Name'),
        ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'general.session_timeout',
            'value' => json_encode(60),
        ]);
    }

    public function test_update_settings_validates_general_section()
    {
        $invalidSettings = [
            'app_name' => '', // Required field
            'session_timeout' => 2, // Below minimum
        ];

        $this->expectException(ValidationException::class);

        $this->service->updateSettings('general', $invalidSettings);
    }

    public function test_update_settings_validates_security_section()
    {
        $invalidSettings = [
            'max_login_attempts' => 15, // Above maximum
            'lockout_duration' => 2, // Below minimum
        ];

        $this->expectException(ValidationException::class);

        $this->service->updateSettings('security', $invalidSettings);
    }

    public function test_validate_password_policy_with_valid_policy()
    {
        $validPolicy = [
            'min_length' => 8,
            'max_length' => 128,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
            'expiry_days' => 90,
            'history_count' => 5,
        ];

        $result = $this->service->validatePasswordPolicy($validPolicy);

        $this->assertTrue($result);
    }

    public function test_validate_password_policy_throws_exception_for_invalid_policy()
    {
        $invalidPolicy = [
            'min_length' => 4, // Below minimum
            'max_length' => 6, // Less than min_length
        ];

        $this->expectException(ValidationException::class);

        $this->service->validatePasswordPolicy($invalidPolicy);
    }

    public function test_validate_password_policy_throws_exception_when_max_less_than_min()
    {
        $invalidPolicy = [
            'min_length' => 10,
            'max_length' => 8, // Less than min_length
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
        ];

        $this->expectException(ValidationException::class);

        $this->service->validatePasswordPolicy($invalidPolicy);
    }

    public function test_test_email_configuration_returns_true_for_valid_config()
    {
        Mail::fake();

        $config = [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'user@example.com',
            'smtp_password' => 'password',
            'smtp_encryption' => 'tls',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Activity Tracker',
            'test_email' => 'test@example.com',
        ];

        $result = $this->service->testEmailConfiguration($config);

        $this->assertTrue($result);
    }

    public function test_export_settings_returns_all_settings()
    {
        SystemSetting::create([
            'key' => 'general.app_name',
            'value' => 'Activity Tracker',
            'type' => 'string',
            'description' => 'Application name',
        ]);

        SystemSetting::create([
            'key' => 'security.max_login_attempts',
            'value' => 5,
            'type' => 'integer',
            'description' => 'Maximum login attempts',
        ]);

        $exported = $this->service->exportSettings();

        $this->assertArrayHasKey('general.app_name', $exported);
        $this->assertArrayHasKey('security.max_login_attempts', $exported);
        $this->assertEquals('Activity Tracker', $exported['general.app_name']['value']);
        $this->assertEquals(5, $exported['security.max_login_attempts']['value']);
    }

    public function test_import_settings_creates_settings()
    {
        $settings = [
            'general.app_name' => [
                'value' => 'Imported App',
                'type' => 'string',
                'description' => 'Imported application name',
            ],
            'security.max_login_attempts' => [
                'value' => 3,
                'type' => 'integer',
                'description' => 'Imported max attempts',
            ],
        ];

        $this->service->importSettings($settings);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'general.app_name',
            'value' => json_encode('Imported App'),
        ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'security.max_login_attempts',
            'value' => json_encode(3),
        ]);
    }

    public function test_settings_are_cached()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('system_settings_general', 3600, \Closure::class)
            ->andReturn(['app_name' => 'Cached App']);

        $settings = $this->service->getSettings('general');

        $this->assertEquals(['app_name' => 'Cached App'], $settings);
    }

    public function test_cache_is_cleared_when_settings_updated()
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('system_settings_general');

        $settings = [
            'app_name' => 'New App Name',
            'session_timeout' => 60,
            'maintenance_mode' => false,
            'timezone' => 'UTC',
        ];

        $this->service->updateSettings('general', $settings);
    }
}