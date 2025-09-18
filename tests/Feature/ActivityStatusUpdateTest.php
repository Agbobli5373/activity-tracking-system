<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function creator_can_update_activity_status()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'created_by' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)->postJson(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => 'Task completed successfully'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Activity status updated successfully.'
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'done'
        ]);

        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Task completed successfully'
        ]);
    }

    /** @test */
    public function assignee_can_update_activity_status()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        
        $activity = Activity::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($assignee)->postJson(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => 'Work completed as requested'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'done'
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_update_activity_status()
    {
        $creator = User::factory()->create(['role' => 'member']);
        $unauthorizedUser = User::factory()->create(['role' => 'member']);
        
        $activity = Activity::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => null, // Not assigned to unauthorized user
            'status' => 'pending'
        ]);

        $response = $this->actingAs($unauthorizedUser)->postJson(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => 'Unauthorized update attempt'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function status_update_requires_valid_status()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'created_by' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)->postJson(route('activities.update-status', $activity), [
            'status' => 'invalid_status',
            'remarks' => 'This should fail'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function status_update_requires_remarks()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'created_by' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)->postJson(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => ''
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['remarks']);
    }

    /** @test */
    public function status_update_requires_minimum_remarks_length()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'created_by' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($user)->postJson(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => 'short'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['remarks']);
    }

    /** @test */
    public function activity_service_updates_status_with_audit_trail()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'created_by' => $user->id,
            'status' => 'pending'
        ]);

        $this->actingAs($user);
        
        $service = new ActivityService();
        $request = request();

        $result = $service->updateStatus($activity, 'done', 'Completed via service', $request);

        $this->assertTrue($result);
        $this->assertEquals('done', $activity->fresh()->status);
        
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Completed via service'
        ]);
    }

    /** @test */
    public function activity_service_can_check_update_permissions()
    {
        $creator = User::factory()->create(['role' => 'member']);
        $assignee = User::factory()->create(['role' => 'member']);
        $unauthorizedUser = User::factory()->create(['role' => 'member']);
        
        $activity = Activity::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id
        ]);

        $service = new ActivityService();

        $this->assertTrue($service->canUpdateStatus($activity, $creator));
        $this->assertTrue($service->canUpdateStatus($activity, $assignee));
        $this->assertFalse($service->canUpdateStatus($activity, $unauthorizedUser));
    }

    /** @test */
    public function activity_service_returns_available_status_transitions()
    {
        $activity = Activity::factory()->create(['status' => 'pending']);
        $service = new ActivityService();

        $transitions = $service->getAvailableStatusTransitions($activity);
        $this->assertEquals(['done'], $transitions);

        $activity->update(['status' => 'done']);
        $transitions = $service->getAvailableStatusTransitions($activity);
        $this->assertEquals(['pending'], $transitions);
    }
}