<?php

namespace Tests\Unit\Services;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityService $activityService;
    protected User $admin;
    protected User $supervisor;
    protected User $member;
    protected User $otherMember;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->activityService = new ActivityService();
        
        // Create test users with different roles
        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'employee_id' => 'EMP001',
            'role' => 'admin',
            'department' => 'IT'
        ]);
        
        $this->supervisor = User::factory()->create([
            'name' => 'Supervisor User',
            'employee_id' => 'EMP002',
            'role' => 'supervisor',
            'department' => 'Operations'
        ]);
        
        $this->member = User::factory()->create([
            'name' => 'Member User',
            'employee_id' => 'EMP003',
            'role' => 'member',
            'department' => 'Support'
        ]);
        
        $this->otherMember = User::factory()->create([
            'name' => 'Other Member',
            'employee_id' => 'EMP004',
            'role' => 'member',
            'department' => 'Support'
        ]);
    }

    public function test_update_status_creates_audit_trail()
    {
        $activity = Activity::factory()->create([
            'status' => 'pending',
            'created_by' => $this->member->id,
        ]);

        $request = Request::create('/test', 'POST');
        $request->setUserResolver(function () {
            return $this->member;
        });

        $this->actingAs($this->member);

        $result = $this->activityService->updateStatus(
            $activity,
            'done',
            'Task completed successfully',
            $request
        );

        $this->assertTrue($result);
        
        // Check that activity status was updated
        $activity->refresh();
        $this->assertEquals('done', $activity->status);
        
        // Check that audit trail was created
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->member->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Task completed successfully',
        ]);
    }

    public function test_get_status_history_returns_chronological_updates()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
        ]);

        // Create multiple updates
        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $this->member->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'created_at' => now()->subHours(2),
        ]);

        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $this->supervisor->id,
            'previous_status' => 'done',
            'new_status' => 'pending',
            'created_at' => now()->subHour(),
        ]);

        $history = $this->activityService->getStatusHistory($activity);

        $this->assertCount(2, $history);
        
        // Should be in chronological order (oldest first)
        $this->assertEquals($update1->id, $history->first()->id);
        $this->assertEquals($update2->id, $history->last()->id);
        
        // Check that user relationships are loaded
        $this->assertInstanceOf(User::class, $history->first()->user);
        $this->assertEquals($this->member->id, $history->first()->user->id);
    }

    public function test_can_update_status_admin_can_update_any_activity()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
            'assigned_to' => $this->otherMember->id,
        ]);

        $canUpdate = $this->activityService->canUpdateStatus($activity, $this->admin);
        
        $this->assertTrue($canUpdate);
    }

    public function test_can_update_status_supervisor_can_update_any_activity()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
            'assigned_to' => $this->otherMember->id,
        ]);

        $canUpdate = $this->activityService->canUpdateStatus($activity, $this->supervisor);
        
        $this->assertTrue($canUpdate);
    }

    public function test_can_update_status_creator_can_update_own_activity()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
            'assigned_to' => $this->otherMember->id,
        ]);

        $canUpdate = $this->activityService->canUpdateStatus($activity, $this->member);
        
        $this->assertTrue($canUpdate);
    }

    public function test_can_update_status_assignee_can_update_assigned_activity()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
            'assigned_to' => $this->otherMember->id,
        ]);

        $canUpdate = $this->activityService->canUpdateStatus($activity, $this->otherMember);
        
        $this->assertTrue($canUpdate);
    }

    public function test_can_update_status_unrelated_member_cannot_update()
    {
        $unrelatedMember = User::factory()->create([
            'role' => 'member',
            'department' => 'HR'
        ]);

        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
            'assigned_to' => $this->otherMember->id,
        ]);

        $canUpdate = $this->activityService->canUpdateStatus($activity, $unrelatedMember);
        
        $this->assertFalse($canUpdate);
    }

    public function test_can_update_status_returns_false_for_null_user()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
        ]);

        $canUpdate = $this->activityService->canUpdateStatus($activity, null);
        
        $this->assertFalse($canUpdate);
    }

    public function test_can_update_status_uses_authenticated_user_when_no_user_provided()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
        ]);

        $this->actingAs($this->member);

        $canUpdate = $this->activityService->canUpdateStatus($activity);
        
        $this->assertTrue($canUpdate);
    }

    public function test_get_available_status_transitions_for_pending_activity()
    {
        $activity = Activity::factory()->create([
            'status' => 'pending',
            'created_by' => $this->member->id,
        ]);

        $transitions = $this->activityService->getAvailableStatusTransitions($activity);
        
        $this->assertEquals(['done'], $transitions);
    }

    public function test_get_available_status_transitions_for_done_activity()
    {
        $activity = Activity::factory()->create([
            'status' => 'done',
            'created_by' => $this->member->id,
        ]);

        $transitions = $this->activityService->getAvailableStatusTransitions($activity);
        
        $this->assertEquals(['pending'], $transitions);
    }

    public function test_get_available_status_transitions_for_unknown_status()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
        ]);
        
        // Manually set an unknown status
        $activity->status = 'unknown';

        $transitions = $this->activityService->getAvailableStatusTransitions($activity);
        
        $this->assertEquals([], $transitions);
    }

    public function test_update_status_with_different_request_data()
    {
        $activity = Activity::factory()->create([
            'status' => 'pending',
            'created_by' => $this->member->id,
        ]);

        $request = Request::create('/activities/update', 'PATCH', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser'
        ]);
        $request->setUserResolver(function () {
            return $this->member;
        });

        $this->actingAs($this->member);

        $result = $this->activityService->updateStatus(
            $activity,
            'done',
            'Completed with custom IP and user agent',
            $request
        );

        $this->assertTrue($result);
        
        // Verify the audit trail includes the request information
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->member->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Completed with custom IP and user agent',
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0 Test Browser',
        ]);
    }

    public function test_update_status_handles_empty_remarks()
    {
        $activity = Activity::factory()->create([
            'status' => 'pending',
            'created_by' => $this->member->id,
        ]);

        $request = Request::create('/test', 'POST');
        $request->setUserResolver(function () {
            return $this->member;
        });

        $this->actingAs($this->member);

        $result = $this->activityService->updateStatus(
            $activity,
            'done',
            '', // Empty remarks
            $request
        );

        $this->assertTrue($result);
        
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->member->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => '',
        ]);
    }

    public function test_get_status_history_with_no_updates()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->member->id,
        ]);

        $history = $this->activityService->getStatusHistory($activity);

        $this->assertCount(0, $history);
        $this->assertTrue($history->isEmpty());
    }

    public function test_service_methods_work_with_different_activity_states()
    {
        // Test with activity that has assignee
        $activityWithAssignee = Activity::factory()->create([
            'created_by' => $this->member->id,
            'assigned_to' => $this->otherMember->id,
            'status' => 'pending'
        ]);

        // Test with activity without assignee
        $activityWithoutAssignee = Activity::factory()->create([
            'created_by' => $this->member->id,
            'assigned_to' => null,
            'status' => 'done'
        ]);

        // Test permissions for both
        $this->assertTrue($this->activityService->canUpdateStatus($activityWithAssignee, $this->member));
        $this->assertTrue($this->activityService->canUpdateStatus($activityWithAssignee, $this->otherMember));
        $this->assertTrue($this->activityService->canUpdateStatus($activityWithoutAssignee, $this->member));
        $this->assertFalse($this->activityService->canUpdateStatus($activityWithoutAssignee, $this->otherMember));

        // Test status transitions for both
        $this->assertEquals(['done'], $this->activityService->getAvailableStatusTransitions($activityWithAssignee));
        $this->assertEquals(['pending'], $this->activityService->getAvailableStatusTransitions($activityWithoutAssignee));
    }
}