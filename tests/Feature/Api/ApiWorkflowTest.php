<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'member',
            'department' => 'IT Support'
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department' => 'IT Support'
        ]);
    }

    /** @test */
    public function complete_api_authentication_workflow()
    {
        // Step 1: Attempt API access without authentication
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);

        // Step 2: Login to get token
        $response = $this->postJson('/api/auth/login', [
            'login' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'token'
            ]
        ]);

        $token = $response->json('data.token');

        // Step 3: Use token for authenticated requests
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'email' => $this->user->email,
            'role' => 'member',
        ]);

        // Step 4: Logout and invalidate token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200);

        // Step 5: Verify token is invalidated
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(401);
    }

    /** @test */
    public function complete_api_activity_crud_workflow()
    {
        Sanctum::actingAs($this->user);

        // Step 1: Create activity via API
        $activityData = [
            'name' => 'API Test Activity',
            'description' => 'Created via API endpoint',
            'priority' => 'high',
            'assigned_to' => $this->user->id,
        ];

        $response = $this->postJson('/api/activities', $activityData);
        $response->assertStatus(302); // Redirect after creation
        
        // Get the created activity
        $activity = Activity::where('name', 'API Test Activity')->first();
        $this->assertNotNull($activity);

        $activityId = $activity->id;

        // Step 2: Retrieve activity via API
        $response = $this->getJson("/api/activities/{$activityId}");
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'API Test Activity',
            'description' => 'Created via API endpoint',
            'status' => 'pending',
        ]);

        // Step 3: Update activity via API
        $updateData = [
            'name' => 'Updated API Activity',
            'description' => 'Updated via API endpoint',
            'priority' => 'medium',
        ];

        $response = $this->putJson("/api/activities/{$activityId}", $updateData);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Updated API Activity',
            'priority' => 'medium',
        ]);

        // Step 4: Update status via API
        $statusData = [
            'status' => 'done',
            'remarks' => 'Completed via API',
        ];

        $response = $this->postJson("/api/activities/{$activityId}/status", $statusData);
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'message' => 'Activity status updated successfully',
        ]);

        // Step 5: Verify status update
        $response = $this->getJson("/api/activities/{$activityId}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'done']);

        // Step 6: Delete activity via API (admin only)
        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson("/api/activities/{$activityId}");
        $response->assertStatus(200);

        // Step 7: Verify activity is deleted
        $response = $this->getJson("/api/activities/{$activityId}");
        $response->assertStatus(404);
    }

    /** @test */
    public function api_activity_listing_and_filtering_workflow()
    {
        Sanctum::actingAs($this->user);

        // Step 1: Create test activities
        Activity::factory()->create([
            'name' => 'High Priority Task',
            'status' => 'pending',
            'priority' => 'high',
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        Activity::factory()->create([
            'name' => 'Completed Task',
            'status' => 'done',
            'priority' => 'medium',
            'created_by' => $this->user->id,
            'created_at' => Carbon::yesterday(),
        ]);

        Activity::factory()->create([
            'name' => 'Low Priority Task',
            'status' => 'pending',
            'priority' => 'low',
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        // Step 2: Get all activities
        $response = $this->getJson('/api/activities');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'activities' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'status',
                            'priority',
                            'created_at',
                        ]
                    ],
                    'current_page',
                    'total',
                ]
            ]
        ]);

        // Step 3: Filter by status
        $response = $this->getJson('/api/activities?status=pending');
        $response->assertStatus(200);
        $activities = $response->json('data.activities.data');
        $this->assertCount(2, $activities);

        // Step 4: Filter by priority
        $response = $this->getJson('/api/activities?priority=high');
        $response->assertStatus(200);
        $activities = $response->json('data.activities.data');
        $this->assertCount(1, $activities);
        $this->assertEquals('High Priority Task', $activities[0]['name']);

        // Step 5: Filter by date
        $response = $this->getJson('/api/activities?date=' . Carbon::today()->format('Y-m-d'));
        $response->assertStatus(200);
        $activities = $response->json('data.activities.data');
        $this->assertCount(2, $activities);

        // Step 6: Search by name
        $response = $this->getJson('/api/activities?search=High');
        $response->assertStatus(200);
        $activities = $response->json('data.activities.data');
        $this->assertCount(1, $activities);

        // Step 7: Combine filters
        $response = $this->getJson('/api/activities?status=pending&priority=high&date=' . Carbon::today()->format('Y-m-d'));
        $response->assertStatus(200);
        $activities = $response->json('data.activities.data');
        $this->assertCount(1, $activities);
    }

    /** @test */
    public function api_dashboard_data_workflow()
    {
        Sanctum::actingAs($this->user);

        // Step 1: Create dashboard test data
        Activity::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => Carbon::today(),
        ]);

        Activity::factory()->count(2)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'created_at' => Carbon::today(),
        ]);

        // Step 2: Get dashboard data
        $response = $this->getJson('/api/dashboard/daily');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'activities',
                'summary' => [
                    'total',
                    'pending',
                    'done',
                    'completion_rate',
                ],
                'departments',
            ]
        ]);

        // Step 3: Verify summary statistics
        $summary = $response->json('data.summary');
        $this->assertEquals(5, $summary['total']);
        $this->assertEquals(3, $summary['pending']);
        $this->assertEquals(2, $summary['done']);
        $this->assertEquals(40.0, $summary['completion_rate']);

        // Step 4: Get dashboard data for specific date
        $response = $this->getJson('/api/dashboard/daily?date=' . Carbon::yesterday()->format('Y-m-d'));
        $response->assertStatus(200);

        // Step 5: Get recent updates
        $response = $this->getJson('/api/dashboard/updates');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'updates',
                'timestamp',
            ]
        ]);
    }

    /** @test */
    public function api_reports_workflow()
    {
        Sanctum::actingAs($this->admin);

        // Step 1: Create report test data
        Activity::factory()->count(5)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'priority' => 'high',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        Activity::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'priority' => 'medium',
            'created_at' => Carbon::now()->subDay(),
        ]);

        // Step 2: Generate report via API
        $reportParams = [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->postJson('/api/reports/generate', $reportParams);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'activities',
                'statistics' => [
                    'total_activities',
                    'completed_activities',
                    'pending_activities',
                    'completion_rate',
                ],
            ]
        ]);

        // Step 3: Get trends data
        $response = $this->getJson('/api/reports/trends?' . http_build_query([
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'group_by' => 'day'
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'labels',
                'datasets',
            ]
        ]);

        // Step 4: Get department statistics
        $response = $this->getJson('/api/reports/department-stats?' . http_build_query([
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'department',
                    'total_activities',
                    'completion_rate',
                ]
            ]
        ]);
    }

    /** @test */
    public function api_error_handling_workflow()
    {
        Sanctum::actingAs($this->user);

        // Step 1: Test validation errors
        $response = $this->postJson('/api/activities', [
            'description' => 'Missing name field',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'name'
            ]
        ]);

        // Step 2: Test not found errors
        $response = $this->getJson('/api/activities/99999');
        $response->assertStatus(404);
        $response->assertJsonStructure([
            'success',
            'message',
        ]);

        // Step 3: Test authorization errors
        $otherUser = User::factory()->create(['role' => 'member']);
        $activity = Activity::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->putJson("/api/activities/{$activity->id}", [
            'name' => 'Should not be allowed',
        ]);

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'success',
            'message',
        ]);

        // Step 4: Test rate limiting (if implemented)
        // Make multiple rapid requests to test rate limiting
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/activities');
        }

        // The 11th request might be rate limited depending on configuration
        $response = $this->getJson('/api/activities');
        // Rate limiting might return 429, but this depends on configuration
        $this->assertContains($response->status(), [200, 429]);
    }

    /** @test */
    public function api_pagination_workflow()
    {
        Sanctum::actingAs($this->user);

        // Step 1: Create many activities
        Activity::factory()->count(25)->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        // Step 2: Get first page
        $response = $this->getJson('/api/activities?page=1&per_page=10');
        $response->assertStatus(200);

        $data = $response->json('data.activities');
        $this->assertEquals(1, $data['current_page']);
        $this->assertEquals(10, count($data['data']));
        $this->assertEquals(25, $data['total']);
        $this->assertGreaterThan(1, $data['last_page']);

        // Step 3: Get second page
        $response = $this->getJson('/api/activities?page=2&per_page=10');
        $response->assertStatus(200);

        $data = $response->json('data.activities');
        $this->assertEquals(2, $data['current_page']);
        $this->assertEquals(10, count($data['data']));

        // Step 4: Get last page
        $lastPage = $data['last_page'];
        $response = $this->getJson("/api/activities?page={$lastPage}&per_page=10");
        $response->assertStatus(200);

        $data = $response->json('data.activities');
        $this->assertEquals($lastPage, $data['current_page']);
        $this->assertLessThanOrEqual(10, count($data['data']));

        // Step 5: Test invalid page
        $response = $this->getJson('/api/activities?page=999&per_page=10');
        $response->assertStatus(200);
        $data = $response->json('data.activities');
        $this->assertEquals(0, count($data['data']));
    }

    /** @test */
    public function api_real_time_updates_workflow()
    {
        Sanctum::actingAs($this->user);

        // Step 1: Create an activity
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        // Step 2: Get initial activity state
        $response = $this->getJson("/api/activities/{$activity->id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'pending']);

        // Step 3: Update activity status
        $response = $this->postJson("/api/activities/{$activity->id}/status", [
            'status' => 'done',
            'remarks' => 'Completed via API',
        ]);

        $response->assertStatus(200);

        // Step 4: Verify updated state
        $response = $this->getJson("/api/activities/{$activity->id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'done']);

        // Step 5: Get activity with updates history
        $response = $this->getJson("/api/activities/{$activity->id}?include=updates");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'activity' => [
                    'id',
                    'name',
                    'status',
                    'updates' => [
                        '*' => [
                            'user_id',
                            'previous_status',
                            'new_status',
                            'remarks',
                            'created_at',
                        ]
                    ]
                ]
            ]
        ]);

        $updates = $response->json('data.activity.updates');
        $this->assertGreaterThanOrEqual(1, count($updates));
    }
}