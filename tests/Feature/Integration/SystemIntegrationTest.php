<?php

namespace Tests\Feature\Integration;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\AuditService;
use App\Services\CacheService;
use App\Services\DashboardService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected ActivityService $activityService;
    protected DashboardService $dashboardService;
    protected ReportService $reportService;
    protected AuditService $auditService;
    protected CacheService $cacheService;

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

        $this->activityService = app(ActivityService::class);
        $this->dashboardService = app(DashboardService::class);
        $this->reportService = app(ReportService::class);
        $this->auditService = app(AuditService::class);
        $this->cacheService = app(CacheService::class);
    }

    /** @test */
    public function complete_activity_lifecycle_integration()
    {
        $this->actingAs($this->user);

        // Step 1: Create activity through service
        $activityData = [
            'name' => 'Integration Test Activity',
            'description' => 'Testing complete lifecycle',
            'priority' => 'high',
            'assigned_to' => $this->user->id,
        ];

        $activity = $this->activityService->createActivity($activityData, $this->user);

        // Step 2: Verify activity creation
        $this->assertDatabaseHas('activities', [
            'name' => 'Integration Test Activity',
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        // Step 3: Verify audit trail creation
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->user->id,
            'new_status' => 'pending',
            'remarks' => 'Activity created',
        ]);

        // Step 4: Verify audit log creation
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'activity.created',
            'auditable_type' => Activity::class,
            'auditable_id' => $activity->id,
        ]);

        // Step 5: Update activity status through service
        $statusUpdate = $this->activityService->updateActivityStatus(
            $activity,
            'done',
            'Completed integration test',
            $this->user
        );

        // Step 6: Verify status update
        $activity->refresh();
        $this->assertEquals('done', $activity->status);

        // Step 7: Verify audit trail update
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Completed integration test',
        ]);

        // Step 8: Verify cache invalidation
        $cacheKey = "dashboard_data_{$this->user->id}_" . Carbon::today()->format('Y-m-d');
        $this->assertNull(Cache::get($cacheKey));

        // Step 9: Verify dashboard service reflects changes
        $dashboardData = $this->dashboardService->getDailyDashboardData(Carbon::today()->format('Y-m-d'));
        $this->assertEquals(1, $dashboardData['summary']['total']);
        $this->assertEquals(0, $dashboardData['summary']['pending']);
        $this->assertEquals(1, $dashboardData['summary']['done']);
        $this->assertEquals(100.0, $dashboardData['summary']['completion_rate']);
    }

    /** @test */
    public function database_transaction_integrity_test()
    {
        $this->actingAs($this->user);

        // Step 1: Start a transaction that should fail
        try {
            DB::transaction(function () {
                // Create activity
                $activity = Activity::factory()->create([
                    'created_by' => $this->user->id,
                    'name' => 'Transaction Test',
                ]);

                // Create audit trail
                ActivityUpdate::create([
                    'activity_id' => $activity->id,
                    'user_id' => $this->user->id,
                    'new_status' => 'pending',
                    'remarks' => 'Activity created',
                ]);

                // Force an exception to test rollback
                throw new \Exception('Forced transaction failure');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Step 2: Verify rollback occurred
        $this->assertDatabaseMissing('activities', [
            'name' => 'Transaction Test',
        ]);

        $this->assertDatabaseMissing('activity_updates', [
            'remarks' => 'Activity created',
        ]);

        // Step 3: Test successful transaction
        DB::transaction(function () {
            $activity = Activity::factory()->create([
                'created_by' => $this->user->id,
                'name' => 'Successful Transaction',
            ]);

            ActivityUpdate::create([
                'activity_id' => $activity->id,
                'user_id' => $this->user->id,
                'new_status' => 'pending',
                'remarks' => 'Activity created successfully',
            ]);
        });

        // Step 4: Verify successful transaction
        $this->assertDatabaseHas('activities', [
            'name' => 'Successful Transaction',
        ]);

        $this->assertDatabaseHas('activity_updates', [
            'remarks' => 'Activity created successfully',
        ]);
    }

    /** @test */
    public function caching_system_integration_test()
    {
        $this->actingAs($this->user);

        // Step 1: Create test data
        Activity::factory()->count(5)->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        // Step 2: First dashboard load should cache data
        $startTime = microtime(true);
        $dashboardData1 = $this->dashboardService->getDailyDashboardData(Carbon::today()->format('Y-m-d'));
        $firstLoadTime = microtime(true) - $startTime;

        // Step 3: Second load should be faster (from cache)
        $startTime = microtime(true);
        $dashboardData2 = $this->dashboardService->getDailyDashboardData(Carbon::today()->format('Y-m-d'));
        $secondLoadTime = microtime(true) - $startTime;

        // Step 4: Verify data is identical
        $this->assertEquals($dashboardData1, $dashboardData2);

        // Step 5: Second load should be significantly faster
        $this->assertLessThan($firstLoadTime * 0.5, $secondLoadTime);

        // Step 6: Create new activity to invalidate cache
        $newActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        // Step 7: Cache should be invalidated and data should be different
        $dashboardData3 = $this->dashboardService->getDailyDashboardData(Carbon::today()->format('Y-m-d'));
        $this->assertNotEquals($dashboardData1['summary']['total'], $dashboardData3['summary']['total']);
        $this->assertEquals(6, $dashboardData3['summary']['total']);
    }

    /** @test */
    public function audit_system_integration_test()
    {
        $this->actingAs($this->user);

        // Step 1: Perform various actions that should be audited
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Audit Test Activity',
        ]);

        // Step 2: Update activity
        $activity->update([
            'name' => 'Updated Audit Test Activity',
            'priority' => 'high',
        ]);

        // Step 3: Update status
        $this->activityService->updateActivityStatus(
            $activity,
            'done',
            'Completed for audit test',
            $this->user
        );

        // Step 4: Verify all actions were audited
        $auditLogs = AuditLog::where('user_id', $this->user->id)
            ->where('auditable_id', $activity->id)
            ->orderBy('created_at')
            ->get();

        $this->assertGreaterThanOrEqual(3, $auditLogs->count());

        // Step 5: Verify audit log details
        $creationLog = $auditLogs->where('action', 'activity.created')->first();
        $this->assertNotNull($creationLog);
        $this->assertEquals($this->user->id, $creationLog->user_id);
        $this->assertNotNull($creationLog->ip_address);
        $this->assertNotNull($creationLog->user_agent);

        // Step 6: Test audit service methods
        $userAuditTrail = $this->auditService->getUserAuditTrail($this->user->id, 10);
        $this->assertGreaterThanOrEqual(3, $userAuditTrail->count());

        $activityAuditTrail = $this->auditService->getActivityAuditTrail($activity->id);
        $this->assertGreaterThanOrEqual(3, $activityAuditTrail->count());
    }

    /** @test */
    public function report_generation_integration_test()
    {
        $this->actingAs($this->admin);

        // Step 1: Create comprehensive test data
        $this->createComprehensiveTestData();

        // Step 2: Generate report through service
        $reportData = $this->reportService->generateActivityReport([
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        // Step 3: Verify report structure
        $this->assertArrayHasKey('activities', $reportData);
        $this->assertArrayHasKey('statistics', $reportData);
        $this->assertArrayHasKey('period', $reportData);

        // Step 4: Verify statistics calculations
        $stats = $reportData['statistics'];
        $this->assertArrayHasKey('total_activities', $stats);
        $this->assertArrayHasKey('completion_rate', $stats);
        $this->assertArrayHasKey('priority_breakdown', $stats);
        $this->assertArrayHasKey('user_statistics', $stats);

        // Step 5: Test report export functionality
        $csvExport = $this->reportService->exportReport($reportData['activities'], 'csv');
        $this->assertNotNull($csvExport);

        // Step 6: Test trends calculation
        $trendsData = $this->reportService->getActivityTrends([
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'group_by' => 'day',
        ]);

        $this->assertArrayHasKey('labels', $trendsData);
        $this->assertArrayHasKey('datasets', $trendsData);
    }

    /** @test */
    public function concurrent_user_activity_test()
    {
        // Step 1: Create multiple users
        $users = User::factory()->count(3)->create(['department' => 'IT Support']);

        // Step 2: Simulate concurrent activity creation
        $activities = [];
        foreach ($users as $user) {
            $this->actingAs($user);
            
            for ($i = 0; $i < 5; $i++) {
                $activities[] = Activity::factory()->create([
                    'created_by' => $user->id,
                    'name' => "User {$user->id} Activity {$i}",
                    'created_at' => Carbon::now()->subMinutes(rand(1, 60)),
                ]);
            }
        }

        // Step 3: Simulate concurrent status updates
        foreach ($activities as $activity) {
            $this->actingAs($activity->creator);
            
            $this->activityService->updateActivityStatus(
                $activity,
                'done',
                'Completed in concurrent test',
                $activity->creator
            );
        }

        // Step 4: Verify data integrity
        $this->assertEquals(15, Activity::count());
        $this->assertEquals(30, ActivityUpdate::count()); // 15 creation + 15 status updates
        $this->assertEquals(15, Activity::where('status', 'done')->count());

        // Step 5: Verify audit logs
        $this->assertGreaterThanOrEqual(30, AuditLog::count());

        // Step 6: Test dashboard performance with concurrent data
        foreach ($users as $user) {
            $this->actingAs($user);
            $dashboardData = $this->dashboardService->getDailyDashboardData(Carbon::today()->format('Y-m-d'));
            
            $this->assertArrayHasKey('summary', $dashboardData);
            $this->assertArrayHasKey('activities', $dashboardData);
        }
    }

    /** @test */
    public function system_performance_under_load_test()
    {
        $this->actingAs($this->user);

        // Step 1: Create large dataset
        $startTime = microtime(true);
        Activity::factory()->count(1000)->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);
        $creationTime = microtime(true) - $startTime;

        // Step 2: Test dashboard performance
        $startTime = microtime(true);
        $dashboardData = $this->dashboardService->getDailyDashboardData(Carbon::today()->format('Y-m-d'));
        $dashboardTime = microtime(true) - $startTime;

        // Step 3: Verify performance benchmarks
        $this->assertLessThan(5.0, $creationTime, 'Activity creation should complete in under 5 seconds');
        $this->assertLessThan(2.0, $dashboardTime, 'Dashboard should load in under 2 seconds');

        // Step 4: Test pagination performance
        $startTime = microtime(true);
        $activities = Activity::with(['creator', 'assignee'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        $paginationTime = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $paginationTime, 'Pagination should complete in under 1 second');

        // Step 5: Test search performance
        $startTime = microtime(true);
        $searchResults = Activity::where('name', 'like', '%test%')
            ->orWhere('description', 'like', '%test%')
            ->limit(50)
            ->get();
        $searchTime = microtime(true) - $startTime;

        $this->assertLessThan(0.5, $searchTime, 'Search should complete in under 0.5 seconds');
    }

    /** @test */
    public function data_consistency_across_services_test()
    {
        $this->actingAs($this->user);

        // Step 1: Create activity through different entry points
        $webActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Web Created Activity',
        ]);

        $serviceActivity = $this->activityService->createActivity([
            'name' => 'Service Created Activity',
            'description' => 'Created through service',
        ], $this->user);

        // Step 2: Verify both activities have consistent audit trails
        $webAuditCount = ActivityUpdate::where('activity_id', $webActivity->id)->count();
        $serviceAuditCount = ActivityUpdate::where('activity_id', $serviceActivity->id)->count();

        $this->assertEquals(1, $serviceAuditCount); // Service should create audit trail
        
        // Step 3: Update both activities and verify consistency
        $this->activityService->updateActivityStatus($webActivity, 'done', 'Web updated', $this->user);
        $this->activityService->updateActivityStatus($serviceActivity, 'done', 'Service updated', $this->user);

        // Step 4: Verify dashboard shows consistent data
        $dashboardData = $this->dashboardService->getDailyDashboardData(Carbon::today()->format('Y-m-d'));
        
        $this->assertEquals(2, $dashboardData['summary']['total']);
        $this->assertEquals(2, $dashboardData['summary']['done']);
        $this->assertEquals(100.0, $dashboardData['summary']['completion_rate']);

        // Step 5: Verify report service shows consistent data
        $reportData = $this->reportService->generateActivityReport([
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $this->assertEquals(2, $reportData['statistics']['total_activities']);
        $this->assertEquals(2, $reportData['statistics']['completed_activities']);
    }

    private function createComprehensiveTestData()
    {
        // Create activities with various statuses and priorities
        Activity::factory()->count(10)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'priority' => 'high',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        Activity::factory()->count(5)->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'priority' => 'medium',
            'created_at' => Carbon::now()->subDay(),
        ]);

        Activity::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'priority' => 'low',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        // Create activities for different users
        $otherUser = User::factory()->create(['department' => 'HR']);
        Activity::factory()->count(7)->create([
            'created_by' => $otherUser->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDay(),
        ]);
    }
}