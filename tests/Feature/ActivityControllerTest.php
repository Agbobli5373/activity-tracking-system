<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $admin;
    protected User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'member']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->supervisor = User::factory()->create(['role' => 'supervisor']);
    }

    /** @test */
    public function unauthenticated_users_cannot_access_activities()
    {
        $response = $this->get(route('activities.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_users_can_view_activities_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('activities.index'));
        $response->assertStatus(200);
        $response->assertViewIs('activities.index');
    }

    /** @test */
    public function users_can_create_activities()
    {
        $this->actingAs($this->user);

        $activityData = [
            'name' => 'Test Activity',
            'description' => 'This is a test activity description',
            'priority' => 'medium',
            'assigned_to' => $this->user->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ];

        $response = $this->post(route('activities.store'), $activityData);

        $this->assertDatabaseHas('activities', [
            'name' => 'Test Activity',
            'description' => 'This is a test activity description',
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        // Check that audit trail was created
        $activity = Activity::where('name', 'Test Activity')->first();
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->user->id,
            'new_status' => 'pending',
            'remarks' => 'Activity created',
        ]);

        $response->assertRedirect(route('activities.show', $activity));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function activity_creation_requires_valid_data()
    {
        $this->actingAs($this->user);

        // Test missing name
        $response = $this->post(route('activities.store'), [
            'description' => 'Test description',
        ]);
        $response->assertSessionHasErrors(['name']);

        // Test missing description
        $response = $this->post(route('activities.store'), [
            'name' => 'Test Activity',
        ]);
        $response->assertSessionHasErrors(['description']);

        // Test invalid priority
        $response = $this->post(route('activities.store'), [
            'name' => 'Test Activity',
            'description' => 'Test description',
            'priority' => 'invalid',
        ]);
        $response->assertSessionHasErrors(['priority']);

        // Test invalid assigned_to
        $response = $this->post(route('activities.store'), [
            'name' => 'Test Activity',
            'description' => 'Test description',
            'assigned_to' => 999,
        ]);
        $response->assertSessionHasErrors(['assigned_to']);

        // Test past due date
        $response = $this->post(route('activities.store'), [
            'name' => 'Test Activity',
            'description' => 'Test description',
            'due_date' => now()->subDays(1)->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors(['due_date']);
    }

    /** @test */
    public function users_can_view_single_activity()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create(['created_by' => $this->user->id]);

        $response = $this->get(route('activities.show', $activity));
        $response->assertStatus(200);
        $response->assertViewIs('activities.show');
        $response->assertViewHas('activity', $activity);
    }

    /** @test */
    public function users_can_update_their_own_activities()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create(['created_by' => $this->user->id]);

        $updateData = [
            'name' => 'Updated Activity Name',
            'description' => 'Updated description',
            'priority' => 'high',
        ];

        $response = $this->put(route('activities.update', $activity), $updateData);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'name' => 'Updated Activity Name',
            'description' => 'Updated description',
            'priority' => 'high',
        ]);

        $response->assertRedirect(route('activities.show', $activity));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function users_cannot_update_others_activities_unless_authorized()
    {
        $otherUser = User::factory()->create(['role' => 'member']);
        $this->actingAs($this->user);

        $activity = Activity::factory()->create(['created_by' => $otherUser->id]);

        $updateData = [
            'name' => 'Updated Activity Name',
            'description' => 'Updated description',
        ];

        $response = $this->put(route('activities.update', $activity), $updateData);
        $response->assertStatus(403);
    }

    /** @test */
    public function admins_can_update_any_activity()
    {
        $this->actingAs($this->admin);

        $activity = Activity::factory()->create(['created_by' => $this->user->id]);

        $updateData = [
            'name' => 'Admin Updated Activity',
            'description' => 'Updated by admin',
        ];

        $response = $this->put(route('activities.update', $activity), $updateData);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'name' => 'Admin Updated Activity',
        ]);

        $response->assertRedirect(route('activities.show', $activity));
    }

    /** @test */
    public function users_can_update_activity_status()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending'
        ]);

        $statusData = [
            'status' => 'done',
            'remarks' => 'Activity completed successfully',
        ];

        $response = $this->post(route('activities.update-status', $activity), $statusData);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'done',
        ]);

        // Check audit trail
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Activity completed successfully',
        ]);

        $response->assertRedirect(route('activities.show', $activity));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function status_update_requires_valid_data()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create(['created_by' => $this->user->id]);

        // Test missing status
        $response = $this->post(route('activities.update-status', $activity), [
            'remarks' => 'Test remarks',
        ]);
        $response->assertSessionHasErrors(['status']);

        // Test invalid status
        $response = $this->post(route('activities.update-status', $activity), [
            'status' => 'invalid',
            'remarks' => 'Test remarks',
        ]);
        $response->assertSessionHasErrors(['status']);

        // Test missing remarks
        $response = $this->post(route('activities.update-status', $activity), [
            'status' => 'done',
        ]);
        $response->assertSessionHasErrors(['remarks']);

        // Test short remarks
        $response = $this->post(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => 'short',
        ]);
        $response->assertSessionHasErrors(['remarks']);
    }

    /** @test */
    public function users_can_delete_their_own_pending_activities()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->delete(route('activities.destroy', $activity));

        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
        $response->assertRedirect(route('activities.index'));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admins_can_delete_any_activity()
    {
        $this->actingAs($this->admin);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done'
        ]);

        $response = $this->delete(route('activities.destroy', $activity));

        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
        $response->assertRedirect(route('activities.index'));
    }

    /** @test */
    public function activities_index_can_be_filtered()
    {
        $this->actingAs($this->user);

        $pendingActivity = Activity::factory()->create(['status' => 'pending']);
        $doneActivity = Activity::factory()->create(['status' => 'done']);

        // Filter by status
        $response = $this->get(route('activities.index', ['status' => 'pending']));
        $response->assertStatus(200);

        // Filter by date
        $response = $this->get(route('activities.index', ['date' => now()->format('Y-m-d')]));
        $response->assertStatus(200);

        // Search
        $response = $this->get(route('activities.index', ['search' => $pendingActivity->name]));
        $response->assertStatus(200);
    }

    /** @test */
    public function api_endpoints_work_with_authentication()
    {
        $this->actingAs($this->user, 'sanctum');

        $activity = Activity::factory()->create(['created_by' => $this->user->id]);

        // Test API index
        $response = $this->getJson('/api/activities');
        $response->assertStatus(200);

        // Test API show
        $response = $this->getJson("/api/activities/{$activity->id}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => $activity->name]);

        // Test API create
        $activityData = [
            'name' => 'API Test Activity',
            'description' => 'Created via API',
        ];

        $response = $this->postJson('/api/activities', $activityData);
        $response->assertStatus(302); // Redirect after creation

        // Test API status update
        $statusData = [
            'status' => 'done',
            'remarks' => 'Completed via API',
        ];

        $response = $this->postJson("/api/activities/{$activity->id}/status", $statusData);
        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
    }

    /** @test */
    public function api_endpoints_require_authentication()
    {
        $activity = Activity::factory()->create();

        $response = $this->getJson('/api/activities');
        $response->assertStatus(401);

        $response = $this->getJson("/api/activities/{$activity->id}");
        $response->assertStatus(401);

        $response = $this->postJson('/api/activities', []);
        $response->assertStatus(401);
    }
}
