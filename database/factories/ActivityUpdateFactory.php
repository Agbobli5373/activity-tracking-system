<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityUpdate>
 */
class ActivityUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $previousStatus = $this->faker->randomElement(['pending', 'done', null]);
        $newStatus = $this->faker->randomElement(['pending', 'done']);

        return [
            'activity_id' => \App\Models\Activity::factory(),
            'user_id' => \App\Models\User::factory(),
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'remarks' => $this->faker->paragraph(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Indicate that this is an initial activity creation update.
     */
    public function creation()
    {
        return $this->state(function (array $attributes) {
            return [
                'previous_status' => null,
                'new_status' => 'pending',
                'remarks' => 'Activity created',
            ];
        });
    }

    /**
     * Indicate that this is a completion update.
     */
    public function completion()
    {
        return $this->state(function (array $attributes) {
            return [
                'previous_status' => 'pending',
                'new_status' => 'done',
                'remarks' => 'Activity completed',
            ];
        });
    }

    /**
     * Indicate that this is a reopening update.
     */
    public function reopening()
    {
        return $this->state(function (array $attributes) {
            return [
                'previous_status' => 'done',
                'new_status' => 'pending',
                'remarks' => 'Activity reopened for additional work',
            ];
        });
    }
}
