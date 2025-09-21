<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $timezones = [
            'UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 
            'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Tokyo'
        ];

        $languages = ['en', 'es', 'fr', 'de', 'it', 'pt', 'ja', 'zh'];

        return [
            'avatar' => null,
            'bio' => $this->faker->optional(0.7)->paragraph(2),
            'timezone' => $this->faker->randomElement($timezones),
            'date_format' => $this->faker->randomElement(['Y-m-d', 'm/d/Y', 'd/m/Y', 'd-m-Y']),
            'time_format' => $this->faker->randomElement(['H:i', 'h:i A', 'H:i:s']),
            'language' => $this->faker->randomElement($languages),
            'dashboard_settings' => [
                'default_date_range' => $this->faker->randomElement(['7_days', '30_days', '90_days']),
                'default_view' => $this->faker->randomElement(['list', 'grid', 'calendar']),
                'items_per_page' => $this->faker->randomElement([10, 25, 50, 100]),
                'show_completed' => $this->faker->boolean(80),
                'default_sort' => $this->faker->randomElement(['created_at_desc', 'due_date_asc', 'priority_desc'])
            ],
            'notification_preferences' => [
                'email_notifications' => $this->faker->boolean(85),
                'in_app_notifications' => $this->faker->boolean(95),
                'activity_assigned' => $this->faker->boolean(90),
                'activity_completed' => $this->faker->boolean(70),
                'activity_overdue' => $this->faker->boolean(95),
                'system_maintenance' => $this->faker->boolean(60)
            ]
        ];
    }
}
