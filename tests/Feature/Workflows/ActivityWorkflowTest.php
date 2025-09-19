<?php

namespace Tests\Feature\Workflows;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $supervisor;

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
    }

    /** @test */
    public function complete_activity_creation_workflow()
    {
        $this->actingAs($this->user);

        // Step 1: User navigates to create activity page
        $response = $this->get(route('activities.create'));
        $response->assertStatus(200);
        $response->assertViewIs('activities.create');

        // Step 2: User fills out and submits activity form
        $activityData = [
            'name' => 'Fix server connectivity issue',
            'description' => 'Server room connectivity needs troubleshooting',
            'priority' => 'high',
            'assigned_to' => $this->user->id,
            'due_date' => Carbon::tomorrow()->format('Y-m-d'),
        ];

        $response = $this->post(route('activities.store'), $activityData);

        // Step 3: Verify activity was created successfully
        $activity = Activity::where('name', 'Fix server connectivity issue')->first();
        $this->assertNotNull($activity);
        $this->assertEquals('pending', $activity->status);
        $this->assertEquals($this->user->id, $activity->created_by);

        // Step 4: Verify initial audit trail was created
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->user->id,
            'new_status' => 'pending',
            'remarks' => 'Activity created',
        ]);

        // Step 5: Verify redirect to activity detail page
        $response->assertRedirect(route('activities.show', $activity));

        // Step 6: User views the created activity
        $response = $this->get(route('activities.show', $activity));
        $response->assertStatus(200);
        $response->assertSee('Fix server connectivity issue');
        $response->assertSee('Server room connectivity needs troubleshooting');
        $response->assertSee('pending');
    }

    /** @test */
    public function complete_activity_status_update_workflow()
    {
        $this->actingAs($this->user);

        // Step 1: Create an activity
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'name' => 'Database backup task'
        ]);

        // Step 2: User views activity and sees status update form
        $response = $this->get(route('activities.show', $activity));
        $response->assertStatus(200);
        $response->assertSee('Update Status');

        // Step 3: User updates status to 'done'
        $statusUpdateData = [
            'status' => 'done',
            'remarks' => 'Database backup completed successfully. All tables backed up to remote server.',
        ];

        $response = $this->post(route('activities.update-status', $activity), $statusUpdateData);

        // Step 4: Verify status was updated
        $activity->refresh();
        $this->assertEquals('done', $activity->status);

        // Step 5: Verify audit trail was created
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Database backup completed successfully. All tables backed up to remote server.',
        ]);

        // Step 6: Verify redirect back to activity
        $response->assertRedirect(route('activities.show', $activity));

        // Step 7: User views updated activity and sees history
        $response = $this->get(route('activities.show', $activity));
        $response->assertStatus(200);
        $response->assertSee('done');
        $response->assertSee('Database backup completed successfully');
        $response->assertSee('Activity History');
    }

    /** @test */
    public function activity_handover_workflow()
    {
        $this->actingAs($this->user);

        // Step 1: Create activities at end of day
        $pendingActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'name' => 'Network maintenance',
            'priority' => 'high',
            'created_at' => Carbon::today()->setHour(17), // 5 PM
        ]);

        $completedActivity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'name' => 'Software update',
            'updated_at' => Carbon::today()->setHour(16), // 4 PM
        ]);

        // Step 2: Supervisor views handover page
        $this->actingAs($this->supervisor);
        $response = $this->get(route('dashboard.handover'));
        $response->assertStatus(200);

        // Step 3: Verify handover data shows pending activities
        $response->assertSee('Network maintenance');
        $response->assertSee('pending');
        $response->assertSee('High Priority');

        // Step 4: Verify completed activities are also shown
        $response->assertSee('Software update');
        $response->assertSee('done');

        // Step 5: Supervisor can view detailed activity information
        $response = $this->get(route('activities.show', $pendingActivity));
        $response->assertStatus(200);
        $response->assertSee('Network maintenance');
        $response->assertSee('pending');
    }

    /** @test */
    public function activity_assignment_and_collaboration_workflow()
    {
        $assignee = User::factory()->create([
            'role' => 'member',
            'department' => 'IT Support'
        ]);

        $this->actingAs($this->user);

        // Step 1: User creates activity and assigns to colleague
        $activityData = [
            'name' => 'Update user permissions',
            'description' => 'Review and update user access permissions',
            'priority' => 'medium',
            'assigned_to' => $assignee->id,
        ];

        $response = $this->post(route('activities.store'), $activityData);
        $activity = Activity::where('name', 'Update user permissions')->first();

        // Step 2: Assignee logs in and sees assigned activity
        $this->actingAs($assignee);
        $response = $this->get(route('activities.index', ['assigned_to_me' => true]));
        $response->assertStatus(200);
        $response->assertSee('Update user permissions');

        // Step 3: Assignee views activity details
        $response = $this->get(route('activities.show', $activity));
        $response->assertStatus(200);
        $response->assertSee('Update user permissions');
        $response->assertSee('Review and update user access permissions');

        // Step 4: Assignee updates status with progress
        $statusUpdateData = [
            'status' => 'pending',
            'remarks' => 'Started reviewing user permissions. Found 5 users with outdated access.',
        ];

        $response = $this->post(route('activities.update-status', $activity), $statusUpdateData);

        // Step 5: Original creator can see the update
        $this->actingAs($this->user);
        $response = $this->get(route('activities.show', $activity));
        $response->assertStatus(200);
        $response->assertSee('Started reviewing user permissions');

        // Step 6: Assignee completes the task
        $this->actingAs($assignee);
        $completionData = [
            'status' => 'done',
            'remarks' => 'All user permissions reviewed and updated. Removed access for 3 inactive users.',
        ];

        $response = $this->post(route('activities.update-status', $activity), $completionData);

        // Step 7: Verify complete audit trail
        $updates = ActivityUpdate::where('activity_id', $activity->id)->orderBy('created_at')->get();
        $this->assertCount(3, $updates); // Creation, progress update, completion

        $this->assertEquals('Activity created', $updates[0]->remarks);
        $this->assertEquals('Started reviewing user permissions', $updates[1]->remarks);
        $this->assertEquals('All user permissions reviewed and updated', $updates[2]->remarks);
    }

    /** @test */
    public function activity_filtering_and_search_workflow()
    {
        $this->actingAs($this->user);

        // Step 1: Create various activities
        Activity::factory()->create([
            'name' => 'Server maintenance',
            'status' => 'pending',
            'priority' => 'high',
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        Activity::factory()->create([
            'name' => 'Database backup',
            'status' => 'done',
            'priority' => 'medium',
            'created_by' => $this->user->id,
            'created_at' => Carbon::yesterday(),
        ]);

        Activity::factory()->create([
            'name' => 'Network troubleshooting',
            'status' => 'pending',
            'priority' => 'low',
            'created_by' => $this->user->id,
            'created_at' => Carbon::today(),
        ]);

        // Step 2: User filters by status
        $response = $this->get(route('activities.index', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee('Server maintenance');
        $response->assertSee('Network troubleshooting');
        $response->assertDontSee('Database backup');

        // Step 3: User filters by priority
        $response = $this->get(route('activities.index', ['priority' => 'high']));
        $response->assertStatus(200);
        $response->assertSee('Server maintenance');
        $response->assertDontSee('Database backup');
        $response->assertDontSee('Network troubleshooting');

        // Step 4: User searches by name
        $response = $this->get(route('activities.index', ['search' => 'server']));
        $response->assertStatus(200);
        $response->assertSee('Server maintenance');
        $response->assertDontSee('Database backup');
        $response->assertDontSee('Network troubleshooting');

        // Step 5: User filters by date
        $response = $this->get(route('activities.index', ['date' => Carbon::today()->format('Y-m-d')]));
        $response->assertStatus(200);
        $response->assertSee('Server maintenance');
        $response->assertSee('Network troubleshooting');
        $response->assertDontSee('Database backup');

        // Step 6: User combines multiple filters
        $response = $this->get(route('activities.index', [
            'status' => 'pending',
            'priority' => 'high',
            'date' => Carbon::today()->format('Y-m-d')
        ]));
        $response->assertStatus(200);
        $response->assertSee('Server maintenance');
        $response->assertDontSee('Database backup');
        $response->assertDontSee('Network troubleshooting');
    }

    /** @test */
    public function activity_editing_workflow()
    {
        $this->actingAs($this->user);

        // Step 1: Create an activity
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Original task name',
            'description' => 'Original description',
            'priority' => 'low',
        ]);

        // Step 2: User navigates to edit page
        $response = $this->get(route('activities.edit', $activity));
        $response->assertStatus(200);
        $response->assertViewIs('activities.edit');
        $response->assertSee('Original task name');
        $response->assertSee('Original description');

        // Step 3: User updates activity details
        $updateData = [
            'name' => 'Updated task name',
            'description' => 'Updated description with more details',
            'priority' => 'high',
        ];

        $response = $this->put(route('activities.update', $activity), $updateData);

        // Step 4: Verify activity was updated
        $activity->refresh();
        $this->assertEquals('Updated task name', $activity->name);
        $this->assertEquals('Updated description with more details', $activity->description);
        $this->assertEquals('high', $activity->priority);

        // Step 5: Verify redirect to activity detail page
        $response->assertRedirect(route('activities.show', $activity));

        // Step 6: User views updated activity
        $response = $this->get(route('activities.show', $activity));
        $response->assertStatus(200);
        $response->assertSee('Updated task name');
        $response->assertSee('Updated description with more details');
        $response->assertSee('high');
    }

    /** @test */
    public function activity_deletion_workflow()
    {
        $this->actingAs($this->user);

        // Step 1: Create a pending activity
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'name' => 'Task to be deleted',
        ]);

        // Step 2: User views activity and sees delete option
        $response = $this->get(route('activities.show', $activity));
        $response->assertStatus(200);
        $response->assertSee('Delete Activity');

        // Step 3: User deletes the activity
        $response = $this->delete(route('activities.destroy', $activity));

        // Step 4: Verify activity was deleted
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);

        // Step 5: Verify redirect to activities index
        $response->assertRedirect(route('activities.index'));

        // Step 6: Verify success message
        $response->assertSessionHas('success');

        // Step 7: User views activities list and doesn't see deleted activity
        $response = $this->get(route('activities.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Task to be deleted');
    }
}