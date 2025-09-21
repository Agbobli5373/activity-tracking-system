<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departments = [
            [
                'name' => 'Information Technology',
                'description' => 'Responsible for managing technology infrastructure and software development',
                'settings' => [
                    'default_role' => 'Team Member',
                    'auto_assign_activities' => true,
                    'notification_enabled' => true
                ]
            ],
            [
                'name' => 'Human Resources',
                'description' => 'Manages employee relations, recruitment, and organizational development',
                'settings' => [
                    'default_role' => 'Team Member',
                    'auto_assign_activities' => false,
                    'notification_enabled' => true
                ]
            ],
            [
                'name' => 'Finance',
                'description' => 'Handles financial planning, accounting, and budget management',
                'settings' => [
                    'default_role' => 'Team Member',
                    'auto_assign_activities' => false,
                    'notification_enabled' => true
                ]
            ],
            [
                'name' => 'Marketing',
                'description' => 'Develops marketing strategies and manages brand communications',
                'settings' => [
                    'default_role' => 'Team Member',
                    'auto_assign_activities' => true,
                    'notification_enabled' => true
                ]
            ],
            [
                'name' => 'Sales',
                'description' => 'Manages customer relationships and revenue generation',
                'settings' => [
                    'default_role' => 'Team Member',
                    'auto_assign_activities' => true,
                    'notification_enabled' => true
                ]
            ]
        ];

        foreach ($departments as $dept) {
            \App\Models\Department::create($dept);
        }

        // Create some sub-departments
        $itDept = \App\Models\Department::where('name', 'Information Technology')->first();
        if ($itDept) {
            \App\Models\Department::create([
                'name' => 'Software Development',
                'description' => 'Application development and maintenance',
                'parent_id' => $itDept->id,
                'settings' => [
                    'default_role' => 'Team Member',
                    'auto_assign_activities' => true,
                    'notification_enabled' => true
                ]
            ]);

            \App\Models\Department::create([
                'name' => 'Infrastructure',
                'description' => 'Server and network management',
                'parent_id' => $itDept->id,
                'settings' => [
                    'default_role' => 'Team Member',
                    'auto_assign_activities' => true,
                    'notification_enabled' => true
                ]
            ]);
        }
    }
}
