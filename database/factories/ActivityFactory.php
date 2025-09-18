<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'done']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'created_by' => \App\Models\User::factory(),
            'assigned_to' => $this->faker->boolean(70) ? \App\Models\User::factory() : null,
            'due_date' => $this->faker->boolean(60) ? $this->faker->dateTimeBetween('now', '+30 days') : null,
        ];
    }

    /**
     * Indicate that the activity is pending.
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    /**
     * Indicate that the activity is done.
     */
    public function done()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'done',
            ];
        });
    }

    /**
     * Indicate that the activity has high priority.
     */
    public function highPriority()
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => 'high',
            ];
        });
    }

    /**
     * Indicate that the activity is overdue.
     */
    public function overdue()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            ];
        });
    }
}
