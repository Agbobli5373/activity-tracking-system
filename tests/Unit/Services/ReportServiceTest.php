<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportService $reportService;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reportService = new ReportService();
        
        // Create test users
        $this->user1 = User::factory()->create([
            'name' => 'John Doe',
            'department' => 'IT Support',
            'role' => 'member'
        ]);
        
        $this->user2 = User::factory()->create([
            'name' => 'Jane Smith',
            'department' => 'Network Operations',
            'role' => 'supervisor'
        ]);
    }

    public function test_generate_activity_report_with_basic_criteria()
    {
        // Create test activities
        $activity1 = Activity::factory()->create([
            'name' => 'Test Activity 1',
            'status' => 'done',
            'priority' => 'high',
            'created_by' => $this->user1->id,
            'created_at' => Carbon::now()->subDays(2)
        ]);

        $activity2 = Activity::factory()->create([
            'name' => 'Test Activity 2',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $this->user2->id,
            'created_at' => Carbon::now()->subDay()
        ]);

        $criteria = [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ];

        $report = $this->reportService->generateActivityReport($criteria);

        $this->assertArrayHasKey('activities', $report);
        $this->assertArrayHasKey('statistics', $report);
        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('filters', $report);

        $this->assertCount(2, $report['activities']);
        $this->assertEquals(2, $report['statistics']['total_activities']);
        $this->assertEquals(1, $report['statistics']['completed_activities']);
        $this->assertEquals(1, $report['statistics']['pending_activities']);
        $this->assertEquals(50.0, $report['statistics']['completion_rate']);
    }

    public function test_generate_activity_report_with_status_filter()
    {
        // Create test activities
        Activity::factory()->create([
            'status' => 'done',
            'created_by' => $this->user1->id,
            'created_at' => Carbon::now()->subDay()
        ]);

        Activity::factory()->create([
            'status' => 'pending',
            'created_by' => $this->user1->id,
            'created_at' => Carbon::now()->subDay()
        ]);

        $criteria = [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'done'
        ];

        $report = $this->reportService->generateActivityReport($criteria);

        $this->assertCount(1, $report['activities']);
        $this->assertEquals('done', $report['activities']->first()->status);
    }

    public function test_generate_activity_report_with_creator_filter()
    {
        // Create test activities
        Activity::factory()->create([
            'created_by' => $this->user1->id,
            'created_at' => Carbon::now()->subDay()
        ]);

        Activity::factory()->create([
            'created_by' => $this->user2->id,
            'created_at' => Carbon::now()->subDay()
        ]);

        $criteria = [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'creator_id' => $this->user1->id
        ];

        $report = $this->reportService->generateActivityReport($criteria);

        $this->assertCount(1, $report['activities']);
        $this->assertEquals($this->user1->id, $report['activities']->first()->created_by);
    }

    public function test_calculate_statistics_with_empty_activities()
    {
        $activities = collect([]);
        $startDate = Carbon::now()->subWeek();
        $endDate = Carbon::now();

        $statistics = $this->reportService->calculateStatistics($activities, $startDate, $endDate);

        $this->assertEquals(0, $statistics['total_activities']);
        $this->assertEquals(0, $statistics['completed_activities']);
        $this->assertEquals(0, $statistics['pending_activities']);
        $this->assertEquals(0, $statistics['completion_rate']);
        $this->assertNull($statistics['average_resolution_time']);
    }

    public function test_calculate_statistics_with_mixed_activities()
    {
        // Create activities with different statuses and priorities
        $activities = collect([
            Activity::factory()->make([
                'status' => 'done',
                'priority' => 'high',
                'created_by' => $this->user1->id,
                'created_at' => Carbon::now()->subDays(2)
            ]),
            Activity::factory()->make([
                'status' => 'pending',
                'priority' => 'medium',
                'created_by' => $this->user1->id,
                'created_at' => Carbon::now()->subDay()
            ]),
            Activity::factory()->make([
                'status' => 'done',
                'priority' => 'low',
                'created_by' => $this->user2->id,
                'created_at' => Carbon::now()->subDay()
            ]),
        ]);

        // Mock the creator relationship
        $activities->each(function ($activity) {
            $activity->setRelation('creator', $activity->created_by === $this->user1->id ? $this->user1 : $this->user2);
            $activity->setRelation('updates', collect([]));
        });

        $startDate = Carbon::now()->subWeek();
        $endDate = Carbon::now();

        $statistics = $this->reportService->calculateStatistics($activities, $startDate, $endDate);

        $this->assertEquals(3, $statistics['total_activities']);
        $this->assertEquals(2, $statistics['completed_activities']);
        $this->assertEquals(1, $statistics['pending_activities']);
        $this->assertEquals(66.67, $statistics['completion_rate']);

        // Check priority breakdown
        $this->assertEquals(1, $statistics['priority_breakdown']['high']);
        $this->assertEquals(1, $statistics['priority_breakdown']['medium']);
        $this->assertEquals(1, $statistics['priority_breakdown']['low']);

        // Check user statistics
        $this->assertCount(2, $statistics['user_statistics']);
    }

    public function test_get_department_statistics()
    {
        // Create activities for different departments
        Activity::factory()->create([
            'created_by' => $this->user1->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDay()
        ]);

        Activity::factory()->create([
            'created_by' => $this->user1->id,
            'status' => 'pending',
            'created_at' => Carbon::now()->subDay()
        ]);

        Activity::factory()->create([
            'created_by' => $this->user2->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDay()
        ]);

        $startDate = Carbon::now()->subWeek();
        $endDate = Carbon::now();

        $stats = $this->reportService->getDepartmentStatistics($startDate, $endDate);

        $this->assertCount(2, $stats);
        
        // Check IT Support department
        $itSupport = collect($stats)->firstWhere('department', 'IT Support');
        $this->assertNotNull($itSupport);
        $this->assertEquals(2, $itSupport->total_activities);
        $this->assertEquals(1, $itSupport->completed_activities);
        $this->assertEquals(1, $itSupport->pending_activities);
        $this->assertEquals(50.0, $itSupport->completion_rate);

        // Check Network Operations department
        $networkOps = collect($stats)->firstWhere('department', 'Network Operations');
        $this->assertNotNull($networkOps);
        $this->assertEquals(1, $networkOps->total_activities);
        $this->assertEquals(1, $networkOps->completed_activities);
        $this->assertEquals(0, $networkOps->pending_activities);
        $this->assertEquals(100.0, $networkOps->completion_rate);
    }

    public function test_get_activity_trends_by_day()
    {
        // Create activities on different days
        Activity::factory()->create([
            'created_by' => $this->user1->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDays(2)->startOfDay()
        ]);

        Activity::factory()->create([
            'created_by' => $this->user1->id,
            'status' => 'pending',
            'created_at' => Carbon::now()->subDay()->startOfDay()
        ]);

        Activity::factory()->create([
            'created_by' => $this->user2->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDay()->startOfDay()
        ]);

        $startDate = Carbon::now()->subWeek();
        $endDate = Carbon::now();

        $trends = $this->reportService->getActivityTrends($startDate, $endDate, 'day');

        $this->assertArrayHasKey('labels', $trends);
        $this->assertArrayHasKey('datasets', $trends);
        $this->assertCount(3, $trends['datasets']);

        // Check dataset structure
        $totalDataset = $trends['datasets'][0];
        $this->assertEquals('Total Activities', $totalDataset['label']);
        $this->assertIsArray($totalDataset['data']);

        $completedDataset = $trends['datasets'][1];
        $this->assertEquals('Completed', $completedDataset['label']);
        $this->assertIsArray($completedDataset['data']);

        $pendingDataset = $trends['datasets'][2];
        $this->assertEquals('Pending', $pendingDataset['label']);
        $this->assertIsArray($pendingDataset['data']);
    }

    public function test_get_filter_options()
    {
        $options = $this->reportService->getFilterOptions();

        $this->assertArrayHasKey('users', $options);
        $this->assertArrayHasKey('departments', $options);
        $this->assertArrayHasKey('statuses', $options);
        $this->assertArrayHasKey('priorities', $options);

        $this->assertContains($this->user1->name, $options['users']->pluck('name'));
        $this->assertContains($this->user2->name, $options['users']->pluck('name'));
        $this->assertContains('IT Support', $options['departments']);
        $this->assertContains('Network Operations', $options['departments']);
        $this->assertEquals(['pending', 'done'], $options['statuses']);
        $this->assertEquals(['low', 'medium', 'high'], $options['priorities']);
    }
}