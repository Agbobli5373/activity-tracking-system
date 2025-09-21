<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => $this->faker->randomElement([
                $this->faker->word,
                $this->faker->numberBetween(1, 100),
                $this->faker->boolean,
                ['option1' => $this->faker->word, 'option2' => $this->faker->word]
            ]),
            'type' => $this->faker->randomElement(['string', 'integer', 'boolean', 'json']),
            'description' => $this->faker->sentence()
        ];
    }

    /**
     * Create password policy setting.
     */
    public function passwordPolicy()
    {
        return $this->state([
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
        ]);
    }

    /**
     * Create security settings.
     */
    public function securitySettings()
    {
        return $this->state([
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
        ]);
    }
}
