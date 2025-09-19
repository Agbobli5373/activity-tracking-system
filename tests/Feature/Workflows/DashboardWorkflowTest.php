<?php

namespace Tests\Feature\Workflows;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $supervisor;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'member',
            'department' => 'IT Support'
        ]);

        $this->supervisor = User::factory()->create([
            'role' => 'supervisor',
            'department' => 'IT Support'
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department' => 'IT Support'
        ]);
    }

    /** @test */
    public function daily_dashboard_workflow_for_team_member()
    {
        $this->actingAs($this->user);

        // Step 1: Create activities for today
        $myPendingActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'name' => 'My pending task',
            'created_at' => Carbon::today()->setHour(9),
        ]);

        $myCompletedActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'name' => 'My completed task',
            'created_at' => Carbon::today()->setHour(8),
        ]);

        // Step 2: User accesses dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');

        // Step 3: Verify user sees their activities
        $response->assertSee('My pending task');
        $response->assertSee('My completed task');
        $response->assertSee('pending');
        $response->assertSee('done');

        // Step 4: Verify dashboard shows summary statistics
        $dashboardData = $response->viewData('dashboardData');
        $this->assertEquals(2, $dashboardData['summary']['total']);
        $this->assertEquals(1, $dashboardData['summary']['pending']);
        $this->assertEquals(1, $dashboardData['summary']['done']);
        $this->assertEquals(50.0, $dashboardData['summary']['completion_rate']);

        // Step 5: User can quick-update status from dashboard
        $response = $this->post(route('activities.update-status', $myPendingActivity), [
            'status' => 'done',
            'remarks' => 'Completed from dashboard',
        ]);

        $response->assertRedirect(route('activities.show', $myPendingActivity));

        // Step 6: Dashboard reflects the update
        $response = $this->get('/dashboard');
        $myPendingActivity->refresh();
        $this->assertEquals('done', $myPendingActivity->status);
    }

    /** @test */
    public function supervisor_dashboard_workflow()
    {
        $this->actingAs($this->supervisor);

        // Step 1: Create activities from team members
        $teamActivity1 = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'name' => 'Team member task 1',
            'priority' => 'high',
            'created_at' => Carbon::today(),
        ]);

        $teamActivity2 = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'name' => 'Team member task 2',
            'created_at' => Carbon::today(),
        ]);

        // Create activity from other department (should not see)
        $otherDeptUser = User::factory()->create(['department' => 'HR']);
        $otherDeptActivity = Activity::factory()->create([
            'created_by' => $otherDeptUser->id,
            'status' => 'pending',
            'name' => 'HR task',
            'created_at' => Carbon::today(),
        ]);

        // Step 2: Supervisor accesses dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Step 3: Verify supervisor sees department activities only
        $response->assertSee('Team member task 1');
        $response->assertSee('Team member task 2');
        $response->assertDontSee('HR task');

        // Step 4: Verify supervisor can filter by team member
        $response = $this->get('/dashboard?user_id=' . $this->user->id);
        $response->assertStatus(200);
        $response->assertSee('Team member task 1');
        $response->assertSee('Team member task 2');

        // Step 5: Supervisor can view handover information
        $response = $this->get('/dashboard/handover');
        $response->assertStatus(200);
        $response->assertSee('Team member task 1'); // Pending activity for handover
        $response->assertSee('high'); // Priority indicator
    }

    /** @test */
    public function dashboard_date_filtering_workflow()
    {
        $this->actingAs($this->user);

        // Step 1: Create activities for different dates
        $todayActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Today activity',
            'created_at' => Carbon::today(),
        ]);

        $yesterdayActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Yesterday activity',
            'created_at' => Carbon::yesterday(),
        ]);

        $lastWeekActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Last week activity',
            'created_at' => Carbon::now()->subWeek(),
        ]);

        // Step 2: Default dashboard shows today's activities
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Today activity');
        $response->assertDontSee('Yesterday activity');
        $response->assertDontSee('Last week activity');

        // Step 3: Filter by yesterday
        $response = $this->get('/dashboard?date=' . Carbon::yesterday()->format('Y-m-d'));
        $response->assertStatus(200);
        $response->assertSee('Yesterday activity');
        $response->assertDontSee('Today activity');
        $response->assertDontSee('Last week activity');

        // Step 4: Filter by last week
        $response = $this->get('/dashboard?date=' . Carbon::now()->subWeek()->format('Y-m-d'));
        $response->assertStatus(200);
        $response->assertSee('Last week activity');
        $response->assertDontSee('Today activity');
        $response->assertDontSee('Yesterday activity');

        // Step 5: Use date picker to navigate
        $response = $this->get('/dashboard?date=' . Carbon::today()->format('Y-m-d'));
        $response->assertStatus(200);
        $response->assertSee('Today activity');
    }

    /** @test */
    public function dashboard_real_time_updates_workflow()
    {
        $this->actingAs($this->user);

        // Step 1: Create an activity
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'name' => 'Real-time test activity',
            'created_at' => Carbon::today(),
        ]);

        // Step 2: Get initial dashboard data via AJAX
        $response = $this->getJson('/dashboard/activities');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Real-time test activity']);
        $response->assertJsonFragment(['status' => 'pending']);

        // Step 3: Update activity status
        $activity->updates()->create([
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Completed via real-time update',
        ]);

        $activity->update(['status' => 'done']);

        // Step 4: Get updated dashboard data
        $response = $this->getJson('/dashboard/activities');
        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'done']);

        // Step 5: Get recent updates
        $response = $this->getJson('/dashboard/updates');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'updates' => [
                '*' => [
                    'activity_name',
                    'user_name',
                    'status',
                    'remarks',
                    'created_at'
                ]
            ],
            'timestamp'
        ]);
    }

    /** @test */
    public function handover_workflow_end_of_day()
    {
        $this->actingAs($this->supervisor);

        // Step 1: Create end-of-day scenario
        $pendingHighPriority = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'priority' => 'high',
            'name' => 'Critical server issue',
            'created_at' => Carbon::today()->setHour(17),
        ]);

        $pendingMediumPriority = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'priority' => 'medium',
            'name' => 'Software update',
            'created_at' => Carbon::today()->setHour(16),
        ]);

        $completedToday = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'name' => 'Backup completed',
            'created_at' => Carbon::today()->setHour(14),
            'updated_at' => Carbon::today()->setHour(15),
        ]);

        // Step 2: Supervisor accesses handover page
        $response = $this->get('/dashboard/handover');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard.handover');

        // Step 3: Verify handover data structure
        $handoverData = $response->viewData('handoverData');
        
        $this->assertArrayHasKey('handover_activities', $handoverData);
        $this->assertArrayHasKey('completed_activities', $handoverData);
        $this->assertArrayHasKey('critical_activities', $handoverData);
        $this->assertArrayHasKey('summary', $handoverData);

        // Step 4: Verify pending activities are shown for handover
        $response->assertSee('Critical server issue');
        $response->assertSee('Software update');
        $response->assertSee('high');
        $response->assertSee('medium');

        // Step 5: Verify completed activities are shown
        $response->assertSee('Backup completed');
        $response->assertSee('done');

        // Step 6: Verify critical activities are highlighted
        $criticalActivities = $handoverData['critical_activities'];
        $this->assertCount(2, $criticalActivities); // Both pending activities are critical for handover

        // Step 7: Verify summary statistics
        $summary = $handoverData['summary'];
        $this->assertEquals(2, $summary['handover_count']);
        $this->assertEquals(1, $summary['completed_count']);
        $this->assertEquals(2, $summary['critical_count']);
    }

    /** @test */
    public function admin_dashboard_overview_workflow()
    {
        $this->actingAs($this->admin);

        // Step 1: Create activities across departments
        $itActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => Carbon::today(),
        ]);

        $hrUser = User::factory()->create(['department' => 'HR']);
        $hrActivity = Activity::factory()->create([
            'created_by' => $hrUser->id,
            'status' => 'done',
            'created_at' => Carbon::today(),
        ]);

        // Step 2: Admin accesses dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Step 3: Admin sees all activities across departments
        $dashboardData = $response->viewData('dashboardData');
        $this->assertEquals(2, $dashboardData['summary']['total']);

        // Step 4: Admin can filter by department
        $response = $this->get('/dashboard?department=IT Support');
        $response->assertStatus(200);

        $response = $this->get('/dashboard?department=HR');
        $response->assertStatus(200);

        // Step 5: Admin can view department statistics
        $departments = $dashboardData['departments'];
        $this->assertArrayHasKey('IT Support', $departments);
        $this->assertArrayHasKey('HR', $departments);
    }

    /** @test */
    public function dashboard_performance_with_large_dataset()
    {
        $this->actingAs($this->user);

        // Step 1: Create large number of activities
        Activity::factory()->count(100)->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        // Step 2: Measure dashboard load time
        $startTime = microtime(true);
        $response = $this->get('/dashboard');
        $endTime = microtime(true);

        // Step 3: Verify dashboard loads successfully
        $response->assertStatus(200);

        // Step 4: Verify reasonable load time (under 2 seconds)
        $loadTime = $endTime - $startTime;
        $this->assertLessThan(2.0, $loadTime, 'Dashboard should load in under 2 seconds');

        // Step 5: Verify pagination is working
        $dashboardData = $response->viewData('dashboardData');
        $activities = $dashboardData['activities'];
        
        // Should be paginated, not showing all 100 at once
        $this->assertLessThanOrEqual(20, $activities->count());
    }
}