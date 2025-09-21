<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $departments = [
            'Human Resources',
            'Information Technology',
            'Finance',
            'Marketing',
            'Sales',
            'Operations',
            'Customer Service',
            'Research & Development',
            'Quality Assurance',
            'Legal'
        ];

        return [
            'name' => $this->faker->unique()->randomElement($departments),
            'description' => $this->faker->sentence(10),
            'parent_id' => null,
            'settings' => [
                'default_role' => 'Team Member',
                'auto_assign_activities' => false,
                'notification_enabled' => true
            ]
        ];
    }

    /**
     * Create a child department.
     */
    public function child($parentId = null)
    {
        return $this->state(function (array $attributes) use ($parentId) {
            return [
                'parent_id' => $parentId,
                'name' => $this->faker->words(2, true) . ' Team'
            ];
        });
    }
}
