<?php

namespace Tests\Feature\Workflows;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department' => 'IT Support'
        ]);

        $this->supervisor = User::factory()->create([
            'role' => 'supervisor',
            'department' => 'IT Support'
        ]);

        $this->member = User::factory()->create([
            'role' => 'member',
            'department' => 'IT Support'
        ]);
    }

    /** @test */
    public function complete_report_generation_workflow()
    {
        $this->actingAs($this->admin);

        // Step 1: Create test data for reporting
        $this->createTestActivities();

        // Step 2: Admin navigates to reports page
        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        $response->assertSee('Generate Report');
        $response->assertSee('Date Range');

        // Step 3: Admin selects date range and filters
        $reportParams = [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'department' => 'IT Support',
            'status' => 'all',
            'priority' => 'all',
        ];

        // Step 4: Generate report
        $response = $this->postJson(route('reports.generate'), $reportParams);
        $response->assertStatus(200);

        // Step 5: Verify report data structure
        $response->assertJsonStructure([
            'success',
            'data' => [
                'activities',
                'statistics' => [
                    'total_activities',
                    'completed_activities',
                    'pending_activities',
                    'completion_rate',
                    'priority_breakdown',
                    'user_statistics',
                    'daily_statistics'
                ],
                'period',
                'filters'
            ]
        ]);

        // Step 6: Verify statistics are calculated correctly
        $data = $response->json('data');
        $this->assertIsNumeric($data['statistics']['total_activities']);
        $this->assertIsNumeric($data['statistics']['completion_rate']);
        $this->assertIsArray($data['statistics']['priority_breakdown']);
        $this->assertIsArray($data['statistics']['user_statistics']);

        // Step 7: Admin views detailed report results
        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function report_filtering_workflow()
    {
        $this->actingAs($this->supervisor);

        // Step 1: Create activities with different attributes
        $highPriorityActivity = Activity::factory()->create([
            'created_by' => $this->member->id,
            'status' => 'done',
            'priority' => 'high',
            'name' => 'High priority task',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $mediumPriorityActivity = Activity::factory()->create([
            'created_by' => $this->member->id,
            'status' => 'pending',
            'priority' => 'medium',
            'name' => 'Medium priority task',
            'created_at' => Carbon::now()->subDay(),
        ]);

        // Step 2: Filter by status
        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'done',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['statistics']['total_activities']);
        $this->assertEquals(1, $data['statistics']['completed_activities']);
        $this->assertEquals(0, $data['statistics']['pending_activities']);

        // Step 3: Filter by priority
        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'priority' => 'high',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['statistics']['total_activities']);

        // Step 4: Filter by user
        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'user_id' => $this->member->id,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(2, $data['statistics']['total_activities']);

        // Step 5: Combine multiple filters
        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'pending',
            'priority' => 'medium',
            'user_id' => $this->member->id,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['statistics']['total_activities']);
    }

    /** @test */
    public function report_export_workflow()
    {
        $this->actingAs($this->admin);

        // Step 1: Create test data
        Activity::factory()->count(5)->create([
            'created_by' => $this->member->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $exportParams = [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'format' => 'csv',
        ];

        // Step 2: Export as CSV
        $response = $this->post(route('reports.export'), $exportParams);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        // Step 3: Export as PDF
        $exportParams['format'] = 'pdf';
        $response = $this->post(route('reports.export'), $exportParams);
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));

        // Step 4: Export as Excel
        $exportParams['format'] = 'excel';
        $response = $this->post(route('reports.export'), $exportParams);
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('Content-Type'));

        // Step 5: Verify export includes correct data
        $exportParams['format'] = 'csv';
        $response = $this->post(route('reports.export'), $exportParams);
        $content = $response->getContent();
        $this->assertStringContainsString('Activity Name', $content);
        $this->assertStringContainsString('Status', $content);
        $this->assertStringContainsString('Priority', $content);
    }

    /** @test */
    public function trends_and_analytics_workflow()
    {
        $this->actingAs($this->supervisor);

        // Step 1: Create activities over multiple days
        for ($i = 7; $i >= 1; $i--) {
            Activity::factory()->count(rand(1, 5))->create([
                'created_by' => $this->member->id,
                'status' => rand(0, 1) ? 'done' : 'pending',
                'created_at' => Carbon::now()->subDays($i),
            ]);
        }

        // Step 2: Get daily trends
        $response = $this->getJson(route('reports.trends') . '?' . http_build_query([
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'group_by' => 'day'
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'labels',
                'datasets' => [
                    '*' => [
                        'label',
                        'data',
                        'backgroundColor',
                        'borderColor'
                    ]
                ]
            ]
        ]);

        // Step 3: Get weekly trends
        $response = $this->getJson(route('reports.trends') . '?' . http_build_query([
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'group_by' => 'week'
        ]));

        $response->assertStatus(200);

        // Step 4: Get department statistics
        $response = $this->getJson(route('reports.department-stats') . '?' . http_build_query([
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
                    'completed_activities',
                    'pending_activities',
                    'completion_rate'
                ]
            ]
        ]);
    }

    /** @test */
    public function performance_report_workflow()
    {
        $this->actingAs($this->admin);

        // Step 1: Create activities with updates for performance analysis
        $activity1 = Activity::factory()->create([
            'created_by' => $this->member->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        // Add updates to track resolution time
        $activity1->updates()->create([
            'user_id' => $this->member->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Completed',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $activity2 = Activity::factory()->create([
            'created_by' => $this->member->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $activity2->updates()->create([
            'user_id' => $this->member->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Completed',
            'created_at' => Carbon::now()->subDay(),
        ]);

        // Step 2: Generate performance report
        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'include_performance' => true,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        // Step 3: Verify performance metrics are included
        $this->assertArrayHasKey('user_statistics', $data['statistics']);
        $userStats = $data['statistics']['user_statistics'];
        
        $memberStats = collect($userStats)->firstWhere('user_name', $this->member->name);
        $this->assertNotNull($memberStats);
        $this->assertArrayHasKey('total_activities', $memberStats);
        $this->assertArrayHasKey('completed_activities', $memberStats);
        $this->assertArrayHasKey('completion_rate', $memberStats);
    }

    /** @test */
    public function scheduled_report_workflow()
    {
        $this->actingAs($this->admin);

        // Step 1: Create test data
        $this->createTestActivities();

        // Step 2: Get summary for different periods
        $response = $this->getJson(route('reports.summary') . '?period=today');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'statistics',
                'period'
            ]
        ]);

        $response = $this->getJson(route('reports.summary') . '?period=week');
        $response->assertStatus(200);

        $response = $this->getJson(route('reports.summary') . '?period=month');
        $response->assertStatus(200);

        // Step 3: Verify summary includes key metrics
        $data = $response->json('data');
        $this->assertArrayHasKey('statistics', $data);
        $this->assertArrayHasKey('period', $data);
        
        $stats = $data['statistics'];
        $this->assertArrayHasKey('total_activities', $stats);
        $this->assertArrayHasKey('completion_rate', $stats);
        $this->assertArrayHasKey('priority_breakdown', $stats);
    }

    /** @test */
    public function report_validation_workflow()
    {
        $this->actingAs($this->supervisor);

        // Step 1: Test invalid date range
        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'), // End before start
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);

        // Step 2: Test future dates
        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->addDay()->format('Y-m-d'),
            'end_date' => Carbon::now()->addWeek()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);

        // Step 3: Test missing required fields
        $response = $this->postJson(route('reports.generate'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date', 'end_date']);

        // Step 4: Test invalid format values
        $response = $this->post(route('reports.export'), [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'format' => 'invalid_format',
        ]);

        $response->assertStatus(422);
    }

    private function createTestActivities()
    {
        // Create activities with various statuses and priorities
        Activity::factory()->count(3)->create([
            'created_by' => $this->member->id,
            'status' => 'done',
            'priority' => 'high',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        Activity::factory()->count(2)->create([
            'created_by' => $this->member->id,
            'status' => 'pending',
            'priority' => 'medium',
            'created_at' => Carbon::now()->subDay(),
        ]);

        Activity::factory()->create([
            'created_by' => $this->member->id,
            'status' => 'done',
            'priority' => 'low',
            'created_at' => Carbon::now()->subDays(3),
        ]);
    }
}