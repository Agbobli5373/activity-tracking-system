<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'login_success',
                'login_failed',
                'logout',
                'activity_created',
                'activity_updated',
                'activity_status_changed',
                'report_generated',
            ]),
            'model_type' => null,
            'model_id' => null,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'url' => $this->faker->url(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            'request_data' => null,
            'session_id' => $this->faker->uuid(),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the audit log is for a login success.
     */
    public function loginSuccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'login_success',
            'method' => 'POST',
            'url' => '/login',
        ]);
    }

    /**
     * Indicate that the audit log is for a login failure.
     */
    public function loginFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'login_failed',
            'method' => 'POST',
            'url' => '/login',
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the audit log is for an activity action.
     */
    public function activityAction(string $action = 'activity_created'): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
            'model_type' => 'App\Models\Activity',
            'model_id' => $this->faker->numberBetween(1, 100),
        ]);
    }

    /**
     * Indicate that the audit log has old and new values.
     */
    public function withChanges(array $oldValues = null, array $newValues = null): static
    {
        return $this->state(fn (array $attributes) => [
            'old_values' => $oldValues ?? ['status' => 'pending'],
            'new_values' => $newValues ?? ['status' => 'done'],
        ]);
    }

    /**
     * Indicate that the audit log has request data.
     */
    public function withRequestData(array $requestData = null): static
    {
        return $this->state(fn (array $attributes) => [
            'request_data' => $requestData ?? [
                'name' => $this->faker->sentence(3),
                'description' => $this->faker->paragraph(),
            ],
        ]);
    }
}