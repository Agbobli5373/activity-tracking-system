<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'employee_id' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'role' => fake()->randomElement(['admin', 'supervisor', 'member']),
            'department' => fake()->randomElement(['IT Support', 'Customer Service', 'Operations', 'Management']),
            'phone_number' => fake()->phoneNumber(),
            'department_id' => null, // Will be set by relationship or specific factory state
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'last_login_at' => fake()->optional(0.8)->dateTimeBetween('-30 days', 'now'),
            'password_changed_at' => fake()->optional(0.6)->dateTimeBetween('-90 days', 'now'),
            'two_factor_enabled' => fake()->boolean(20), // 20% chance of having 2FA enabled
            'failed_login_attempts' => fake()->numberBetween(0, 2),
            'account_locked_until' => null,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }



    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     *
     * @return static
     */
    public function admin()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'status' => 'active',
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Create a supervisor user.
     *
     * @return static
     */
    public function supervisor()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'supervisor',
            'status' => 'active',
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Create a member user.
     *
     * @return static
     */
    public function member()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'member',
            'status' => 'active',
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Create an inactive user.
     *
     * @return static
     */
    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'last_login_at' => fake()->dateTimeBetween('-6 months', '-1 month'),
        ]);
    }

    /**
     * Create a locked user.
     *
     * @return static
     */
    public function locked()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'locked',
            'failed_login_attempts' => fake()->numberBetween(5, 10),
            'account_locked_until' => fake()->dateTimeBetween('now', '+1 hour'),
        ]);
    }

    /**
     * Create a pending user.
     *
     * @return static
     */
    public function pending()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'email_verified_at' => null,
            'last_login_at' => null,
        ]);
    }

    /**
     * Create a user with two-factor authentication enabled.
     *
     * @return static
     */
    public function withTwoFactor()
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_enabled' => true,
            'two_factor_secret' => fake()->regexify('[A-Z0-9]{32}'),
        ]);
    }
}
