<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->creator = User::factory()->create([
            'name' => 'John Creator',
            'employee_id' => 'EMP001',
            'role' => 'member',
            'department' => 'IT'
        ]);
        
        $this->assignee = User::factory()->create([
            'name' => 'Jane Assignee',
            'employee_id' => 'EMP002',
            'role' => 'supervisor',
            'department' => 'IT'
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'employee_id' => 'EMP003',
            'role' => 'admin',
            'department' => 'Management'
        ]);
    }

    public function test_user_created_activities_relationship()
    {
        $activity1 = Activity::factory()->create(['created_by' => $this->creator->id]);
        $activity2 = Activity::factory()->create(['created_by' => $this->creator->id]);
        $activity3 = Activity::factory()->create(['created_by' => $this->assignee->id]);

        $creatorActivities = $this->creator->createdActivities;
        $assigneeActivities = $this->assignee->createdActivities;

        $this->assertInstanceOf(Collection::class, $creatorActivities);
        $this->assertCount(2, $creatorActivities);
        $this->assertCount(1, $assigneeActivities);
        
        $this->assertTrue($creatorActivities->contains($activity1));
        $this->assertTrue($creatorActivities->contains($activity2));
        $this->assertFalse($creatorActivities->contains($activity3));
    }

    public function test_user_assigned_activities_relationship()
    {
        $activity1 = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'assigned_to' => $this->assignee->id
        ]);
        
        $activity2 = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'assigned_to' => $this->assignee->id
        ]);
        
        $activity3 = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'assigned_to' => $this->creator->id
        ]);

        $assigneeActivities = $this->assignee->assignedActivities;
        $creatorActivities = $this->creator->assignedActivities;

        $this->assertInstanceOf(Collection::class, $assigneeActivities);
        $this->assertCount(2, $assigneeActivities);
        $this->assertCount(1, $creatorActivities);
        
        $this->assertTrue($assigneeActivities->contains($activity1));
        $this->assertTrue($assigneeActivities->contains($activity2));
        $this->assertTrue($creatorActivities->contains($activity3));
    }

    public function test_user_activity_updates_relationship()
    {
        $activity = Activity::factory()->create(['created_by' => $this->creator->id]);
        
        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $this->creator->id
        ]);
        
        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $this->creator->id
        ]);
        
        $update3 = ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $this->assignee->id
        ]);

        $creatorUpdates = $this->creator->activityUpdates;
        $assigneeUpdates = $this->assignee->activityUpdates;

        $this->assertInstanceOf(Collection::class, $creatorUpdates);
        $this->assertCount(2, $creatorUpdates);
        $this->assertCount(1, $assigneeUpdates);
        
        $this->assertTrue($creatorUpdates->contains($update1));
        $this->assertTrue($creatorUpdates->contains($update2));
        $this->assertTrue($assigneeUpdates->contains($update3));
    }

    public function test_activity_updates_relationship()
    {
        $activity = Activity::factory()->create(['created_by' => $this->creator->id]);
        
        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $this->creator->id
        ]);
        
        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $this->assignee->id
        ]);

        $activityUpdates = $activity->updates;

        $this->assertInstanceOf(Collection::class, $activityUpdates);
        $this->assertCount(2, $activityUpdates);
        $this->assertTrue($activityUpdates->contains($update1));
        $this->assertTrue($activityUpdates->contains($update2));
    }

    public function test_user_role_scopes()
    {
        $admins = User::admins()->get();
        $supervisors = User::supervisors()->get();
        $members = User::members()->get();

        $this->assertCount(1, $admins);
        $this->assertCount(1, $supervisors);
        $this->assertCount(1, $members);

        $this->assertTrue($admins->contains($this->admin));
        $this->assertTrue($supervisors->contains($this->assignee));
        $this->assertTrue($members->contains($this->creator));
    }

    public function test_user_by_role_scope()
    {
        $adminUsers = User::byRole('admin')->get();
        $supervisorUsers = User::byRole('supervisor')->get();
        $memberUsers = User::byRole('member')->get();

        $this->assertCount(1, $adminUsers);
        $this->assertCount(1, $supervisorUsers);
        $this->assertCount(1, $memberUsers);

        $this->assertEquals($this->admin->id, $adminUsers->first()->id);
        $this->assertEquals($this->assignee->id, $supervisorUsers->first()->id);
        $this->assertEquals($this->creator->id, $memberUsers->first()->id);
    }

    public function test_user_by_department_scope()
    {
        $itUsers = User::byDepartment('IT')->get();
        $managementUsers = User::byDepartment('Management')->get();

        $this->assertCount(2, $itUsers);
        $this->assertCount(1, $managementUsers);

        $this->assertTrue($itUsers->contains($this->creator));
        $this->assertTrue($itUsers->contains($this->assignee));
        $this->assertTrue($managementUsers->contains($this->admin));
    }

    public function test_user_role_check_methods()
    {
        $this->assertTrue($this->admin->isAdmin());
        $this->assertFalse($this->admin->isSupervisor());
        $this->assertFalse($this->admin->isMember());

        $this->assertFalse($this->assignee->isAdmin());
        $this->assertTrue($this->assignee->isSupervisor());
        $this->assertFalse($this->assignee->isMember());

        $this->assertFalse($this->creator->isAdmin());
        $this->assertFalse($this->creator->isSupervisor());
        $this->assertTrue($this->creator->isMember());
    }

    public function test_user_can_manage_activities_method()
    {
        $this->assertTrue($this->admin->canManageActivities());
        $this->assertTrue($this->assignee->canManageActivities());
        $this->assertFalse($this->creator->canManageActivities());
    }

    public function test_eager_loading_prevents_n_plus_one_queries()
    {
        // Create activities with updates
        $activity1 = Activity::factory()->create(['created_by' => $this->creator->id]);
        $activity2 = Activity::factory()->create(['created_by' => $this->creator->id]);
        
        ActivityUpdate::factory()->create([
            'activity_id' => $activity1->id,
            'user_id' => $this->creator->id
        ]);
        
        ActivityUpdate::factory()->create([
            'activity_id' => $activity2->id,
            'user_id' => $this->assignee->id
        ]);

        // Test eager loading of relationships
        $activitiesWithRelations = Activity::with(['creator', 'assignee', 'updates.user'])->get();

        $this->assertCount(2, $activitiesWithRelations);
        
        // Verify relationships are loaded
        foreach ($activitiesWithRelations as $activity) {
            $this->assertTrue($activity->relationLoaded('creator'));
            $this->assertTrue($activity->relationLoaded('updates'));
            
            foreach ($activity->updates as $update) {
                $this->assertTrue($update->relationLoaded('user'));
            }
        }
    }

    public function test_complex_relationship_queries()
    {
        // Create activities and updates
        $activity1 = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'assigned_to' => $this->assignee->id,
            'status' => 'pending'
        ]);
        
        $activity2 = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'assigned_to' => $this->assignee->id,
            'status' => 'done'
        ]);

        ActivityUpdate::factory()->create([
            'activity_id' => $activity1->id,
            'user_id' => $this->creator->id,
            'new_status' => 'pending'
        ]);
        
        ActivityUpdate::factory()->create([
            'activity_id' => $activity2->id,
            'user_id' => $this->assignee->id,
            'new_status' => 'done'
        ]);

        // Test complex queries with relationships
        $pendingActivitiesWithUpdates = Activity::with('updates')
            ->where('status', 'pending')
            ->get();

        $completedActivitiesByAssignee = Activity::with(['assignee', 'updates'])
            ->where('status', 'done')
            ->whereHas('assignee', function ($query) {
                $query->where('role', 'supervisor');
            })
            ->get();

        $this->assertCount(1, $pendingActivitiesWithUpdates);
        $this->assertCount(1, $completedActivitiesByAssignee);
        
        $this->assertEquals($activity1->id, $pendingActivitiesWithUpdates->first()->id);
        $this->assertEquals($activity2->id, $completedActivitiesByAssignee->first()->id);
    }

    public function test_activity_scopes_with_relationships()
    {
        $today = now();
        $yesterday = now()->subDay();

        // Create activities for different dates
        $todayActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'created_at' => $today,
            'status' => 'pending'
        ]);

        $yesterdayActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'created_at' => $yesterday,
            'status' => 'done'
        ]);

        // Test scopes with eager loading
        $todayPendingActivities = Activity::with('creator')
            ->byDate($today)
            ->pending()
            ->get();

        $yesterdayCompletedActivities = Activity::with('creator')
            ->byDate($yesterday)
            ->completed()
            ->get();

        $creatorPendingActivities = Activity::with('updates')
            ->byCreator($this->creator->id)
            ->pending()
            ->get();

        $this->assertCount(1, $todayPendingActivities);
        $this->assertCount(1, $yesterdayCompletedActivities);
        $this->assertCount(1, $creatorPendingActivities);

        $this->assertEquals($todayActivity->id, $todayPendingActivities->first()->id);
        $this->assertEquals($yesterdayActivity->id, $yesterdayCompletedActivities->first()->id);
        $this->assertEquals($todayActivity->id, $creatorPendingActivities->first()->id);
    }
}
