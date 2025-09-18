<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin',
            'department' => 'IT'
        ]);
        
        $this->dashboardService = new DashboardService();
    }

    public function test_dashboard_index_displays_correctly()
    {
        // Create test activities
        Activity::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
        $response->assertViewHas(['dashboardData', 'date']);
    }

    public function test_dashboard_filters_by_date()
    {
        $yesterday = Carbon::yesterday();
        $today = Carbon::today();

        // Create activities for different dates
        Activity::factory()->create([
            'created_by' => $this->user->id,
            'created_at' => $yesterday,
            'name' => 'Yesterday Activity'
        ]);

        Activity::factory()->create([
            'created_by' => $this->user->id,
            'created_at' => $today,
            'name' => 'Today Activity'
        ]);

        $response = $this->actingAs($this->user)
            ->get('/dashboard?date=' . $yesterday->format('Y-m-d'));

        $response->assertStatus(200);
        // Should only show yesterday's activity
        $this->assertEquals(1, $response->viewData('dashboardData')['activities']->count());
    }

    public function test_dashboard_filters_by_status()
    {
        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => Carbon::today(),
        ]);

        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'created_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/dashboard?status=pending');

        $response->assertStatus(200);
        $activities = $response->viewData('dashboardData')['activities'];
        $this->assertEquals(1, $activities->count());
        $this->assertEquals('pending', $activities->first()->status);
    }

    public function test_dashboard_filters_by_department()
    {
        $itUser = User::factory()->create(['department' => 'IT']);
        $hrUser = User::factory()->create(['department' => 'HR']);

        Activity::factory()->create([
            'created_by' => $itUser->id,
            'created_at' => Carbon::today(),
        ]);

        Activity::factory()->create([
            'created_by' => $hrUser->id,
            'created_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/dashboard?department=IT');

        $response->assertStatus(200);
        $activities = $response->viewData('dashboardData')['activities'];
        $this->assertEquals(1, $activities->count());
        $this->assertEquals('IT', $activities->first()->creator->department);
    }

    public function test_get_activities_ajax_endpoint()
    {
        Activity::factory()->count(2)->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/dashboard/activities');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'activities',
            'summary' => [
                'total',
                'pending',
                'done',
                'completion_rate'
            ]
        ]);
    }

    public function test_handover_page_displays_correctly()
    {
        // Create activities that need handover
        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => Carbon::today(),
        ]);

        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'updated_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard/handover');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.handover');
        $response->assertViewHas(['handoverData', 'date']);
    }

    public function test_get_updates_ajax_endpoint()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        // Create an activity update
        $activity->updates()->create([
            'user_id' => $this->user->id,
            'status' => 'done',
            'remarks' => 'Test update',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/dashboard/updates');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'updates',
            'timestamp'
        ]);
    }

    public function test_dashboard_service_get_daily_dashboard_data()
    {
        $today = Carbon::today()->format('Y-m-d');
        
        Activity::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
            'status' => 'pending'
        ]);

        Activity::factory()->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
            'status' => 'done'
        ]);

        $data = $this->dashboardService->getDailyDashboardData($today);

        $this->assertArrayHasKey('activities', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('departments', $data);
        $this->assertEquals(4, $data['summary']['total']);
        $this->assertEquals(3, $data['summary']['pending']);
        $this->assertEquals(1, $data['summary']['done']);
        $this->assertEquals(25.0, $data['summary']['completion_rate']);
    }

    public function test_dashboard_service_get_handover_data()
    {
        $today = Carbon::today()->format('Y-m-d');

        // Create activities that need handover
        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => Carbon::today(),
        ]);

        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'updated_at' => Carbon::today(),
        ]);

        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'priority' => 'high',
            'created_at' => Carbon::today(),
        ]);

        $handoverData = $this->dashboardService->getHandoverData($today);

        $this->assertArrayHasKey('handover_activities', $handoverData);
        $this->assertArrayHasKey('completed_activities', $handoverData);
        $this->assertArrayHasKey('critical_activities', $handoverData);
        $this->assertArrayHasKey('summary', $handoverData);

        $this->assertEquals(2, $handoverData['summary']['handover_count']);
        $this->assertEquals(1, $handoverData['summary']['completed_count']);
        $this->assertEquals(2, $handoverData['summary']['critical_count']);
    }

    public function test_dashboard_requires_authentication()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_dashboard_ajax_endpoints_require_authentication()
    {
        $response = $this->getJson('/dashboard/activities');
        $response->assertStatus(401);

        $response = $this->getJson('/dashboard/updates');
        $response->assertStatus(401);
    }
}