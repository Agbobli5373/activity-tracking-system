<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Activity;
use App\Models\ActivityUpdate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 specific users with different roles and departments
        $users = [
            [
                'name' => 'John Manager',
                'email' => 'manager@example.com',
                'employee_id' => 'MGR001',
                'role' => 'admin',
                'department' => 'Management',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Sarah Supervisor',
                'email' => 'supervisor@example.com',
                'employee_id' => 'SUP001',
                'role' => 'supervisor',
                'department' => 'IT Support',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike@example.com',
                'employee_id' => 'EMP001',
                'role' => 'member',
                'department' => 'IT Support',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Lisa Chen',
                'email' => 'lisa@example.com',
                'employee_id' => 'EMP002',
                'role' => 'member',
                'department' => 'Customer Service',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david@example.com',
                'employee_id' => 'EMP003',
                'role' => 'member',
                'department' => 'Operations',
                'password' => Hash::make('password123'),
            ],
        ];

        $createdUsers = collect();
        foreach ($users as $userData) {
            $user = User::create($userData);
            $createdUsers->push($user);
        }

        // Get specific users for activity creation
        $admin = $createdUsers->where('role', 'admin')->first();
        $supervisor = $createdUsers->where('role', 'supervisor')->first();
        $members = $createdUsers->where('role', 'member')->values();

        $this->command->info("Created users: Admin: {$admin->name}, Supervisor: {$supervisor->name}, Members: {$members->count()}");

        // Create sample activities with realistic scenarios
        $this->createSampleActivities($admin, $supervisor, $members);
    }

    private function createSampleActivities($admin, $supervisor, $members): void
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $tomorrow = Carbon::tomorrow();

        // Get member IDs safely
        $member1 = $members->get(0);
        $member2 = $members->get(1) ?? $member1;
        $member3 = $members->get(2) ?? $member1;

        // Today's activities
        $todayActivities = [
            [
                'name' => 'Fix server connectivity issue',
                'description' => 'Server room connectivity problems reported by multiple users. Need to investigate network infrastructure.',
                'status' => 'pending',
                'priority' => 'high',
                'created_by' => $supervisor->id,
                'assigned_to' => $member1->id,
                'due_date' => $today->copy()->addHours(4),
                'created_at' => $today->copy()->addHours(1),
            ],
            [
                'name' => 'Update customer database records',
                'description' => 'Batch update of customer contact information based on recent data validation results.',
                'status' => 'done',
                'priority' => 'medium',
                'created_by' => $admin->id,
                'assigned_to' => $member2->id,
                'due_date' => $today->copy()->addHours(6),
                'created_at' => $today->copy()->addHours(2),
                'updated_at' => $today->copy()->addHours(5),
            ],
            [
                'name' => 'Prepare monthly operations report',
                'description' => 'Compile and analyze operational metrics for the monthly management review meeting.',
                'status' => 'pending',
                'priority' => 'medium',
                'created_by' => $admin->id,
                'assigned_to' => $member3->id,
                'due_date' => $tomorrow,
                'created_at' => $today->copy()->addHours(3),
            ],
            [
                'name' => 'Install security patches on workstations',
                'description' => 'Deploy latest security updates to all workstations in the IT department.',
                'status' => 'pending',
                'priority' => 'high',
                'created_by' => $supervisor->id,
                'assigned_to' => $member1->id,
                'due_date' => $today->copy()->addHours(8),
                'created_at' => $today->copy()->addMinutes(30),
            ],
            [
                'name' => 'Customer service training session',
                'description' => 'Conduct training session on new customer service protocols and procedures.',
                'status' => 'done',
                'priority' => 'low',
                'created_by' => $supervisor->id,
                'assigned_to' => $member2->id,
                'due_date' => $today->copy()->addHours(3),
                'created_at' => $today->copy()->addMinutes(15),
                'updated_at' => $today->copy()->addHours(2),
            ],
        ];

        // Yesterday's activities
        $yesterdayActivities = [
            [
                'name' => 'Backup system maintenance',
                'description' => 'Perform routine maintenance on backup systems and verify data integrity.',
                'status' => 'done',
                'priority' => 'medium',
                'created_by' => $admin->id,
                'assigned_to' => $member1->id,
                'due_date' => $yesterday->copy()->addHours(6),
                'created_at' => $yesterday->copy()->addHours(1),
                'updated_at' => $yesterday->copy()->addHours(5),
            ],
            [
                'name' => 'Process customer refund requests',
                'description' => 'Review and process pending customer refund requests from the support queue.',
                'status' => 'done',
                'priority' => 'high',
                'created_by' => $supervisor->id,
                'assigned_to' => $member2->id,
                'due_date' => $yesterday->copy()->addHours(4),
                'created_at' => $yesterday->copy()->addMinutes(30),
                'updated_at' => $yesterday->copy()->addHours(3),
            ],
            [
                'name' => 'Inventory audit - Office supplies',
                'description' => 'Conduct quarterly audit of office supplies and update inventory management system.',
                'status' => 'pending',
                'priority' => 'low',
                'created_by' => $admin->id,
                'assigned_to' => $member3->id,
                'due_date' => $today->copy()->addDays(2),
                'created_at' => $yesterday->copy()->addHours(2),
            ],
        ];

        // Create activities and their updates
        foreach ([$todayActivities, $yesterdayActivities] as $activityGroup) {
            foreach ($activityGroup as $activityData) {
                $activity = Activity::create($activityData);

                // Create initial activity update (creation)
                ActivityUpdate::create([
                    'activity_id' => $activity->id,
                    'user_id' => $activity->created_by,
                    'previous_status' => null,
                    'new_status' => 'pending',
                    'remarks' => 'Activity created and assigned',
                    'ip_address' => '192.168.1.' . rand(10, 100),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'created_at' => $activity->created_at,
                ]);

                // If activity is done, create completion update
                if ($activity->status === 'done') {
                    ActivityUpdate::create([
                        'activity_id' => $activity->id,
                        'user_id' => $activity->assigned_to,
                        'previous_status' => 'pending',
                        'new_status' => 'done',
                        'remarks' => 'Task completed successfully. All requirements met.',
                        'ip_address' => '192.168.1.' . rand(10, 100),
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'created_at' => $activity->updated_at,
                    ]);
                }

                // Add some progress updates for pending activities
                if ($activity->status === 'pending' && rand(1, 100) > 50) {
                    ActivityUpdate::create([
                        'activity_id' => $activity->id,
                        'user_id' => $activity->assigned_to,
                        'previous_status' => 'pending',
                        'new_status' => 'pending',
                        'remarks' => 'Work in progress. ' . collect([
                            'Initial assessment completed.',
                            'Gathering required resources.',
                            'Coordinating with team members.',
                            'Waiting for approval from supervisor.',
                            'Testing solution in development environment.',
                        ])->random(),
                        'ip_address' => '192.168.1.' . rand(10, 100),
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'created_at' => $activity->created_at->copy()->addMinutes(rand(30, 180)),
                    ]);
                }
            }
        }

        $this->command->info('Created 5 users and ' . (count($todayActivities) + count($yesterdayActivities)) . ' sample activities with updates.');
    }
}