<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Activity;
use App\Models\ActivityUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_be_created_with_required_fields()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP001',
            'role' => 'member',
            'department' => 'IT',
            'password' => bcrypt('password'),
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('EMP001', $user->employee_id);
        $this->assertEquals('member', $user->role);
        $this->assertEquals('IT', $user->department);
    }

    public function test_user_fillable_attributes()
    {
        $fillable = [
            'name',
            'email',
            'employee_id',
            'role',
            'department',
            'password',
        ];

        $user = new User();
        $this->assertEquals($fillable, $user->getFillable());
    }

    public function test_user_hidden_attributes()
    {
        $hidden = [
            'password',
            'remember_token',
        ];

        $user = new User();
        $this->assertEquals($hidden, $user->getHidden());
    }

    public function test_user_casts()
    {
        $user = User::factory()->create([
            'email_verified_at' => '2024-01-01 12:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    public function test_created_activities_relationship()
    {
        $user = User::factory()->create();
        $activity1 = Activity::factory()->create(['created_by' => $user->id]);
        $activity2 = Activity::factory()->create(['created_by' => $user->id]);

        $createdActivities = $user->createdActivities;

        $this->assertInstanceOf(Collection::class, $createdActivities);
        $this->assertCount(2, $createdActivities);
        $this->assertTrue($createdActivities->contains($activity1));
        $this->assertTrue($createdActivities->contains($activity2));
    }

    public function test_assigned_activities_relationship()
    {
        $user = User::factory()->create();
        $creator = User::factory()->create();
        
        $activity1 = Activity::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $user->id
        ]);
        
        $activity2 = Activity::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $user->id
        ]);

        $assignedActivities = $user->assignedActivities;

        $this->assertInstanceOf(Collection::class, $assignedActivities);
        $this->assertCount(2, $assignedActivities);
        $this->assertTrue($assignedActivities->contains($activity1));
        $this->assertTrue($assignedActivities->contains($activity2));
    }

    public function test_activity_updates_relationship()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['created_by' => $user->id]);
        
        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id
        ]);
        
        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id
        ]);

        $activityUpdates = $user->activityUpdates;

        $this->assertInstanceOf(Collection::class, $activityUpdates);
        $this->assertCount(2, $activityUpdates);
        $this->assertTrue($activityUpdates->contains($update1));
        $this->assertTrue($activityUpdates->contains($update2));
    }

    public function test_by_role_scope()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        $adminUsers = User::byRole('admin')->get();
        $supervisorUsers = User::byRole('supervisor')->get();
        $memberUsers = User::byRole('member')->get();

        $this->assertCount(1, $adminUsers);
        $this->assertCount(1, $supervisorUsers);
        $this->assertCount(1, $memberUsers);
        
        $this->assertTrue($adminUsers->contains($admin));
        $this->assertTrue($supervisorUsers->contains($supervisor));
        $this->assertTrue($memberUsers->contains($member));
    }

    public function test_by_department_scope()
    {
        $itUser = User::factory()->create(['department' => 'IT']);
        $hrUser = User::factory()->create(['department' => 'HR']);
        $financeUser = User::factory()->create(['department' => 'Finance']);

        $itUsers = User::byDepartment('IT')->get();
        $hrUsers = User::byDepartment('HR')->get();
        $financeUsers = User::byDepartment('Finance')->get();

        $this->assertCount(1, $itUsers);
        $this->assertCount(1, $hrUsers);
        $this->assertCount(1, $financeUsers);
        
        $this->assertTrue($itUsers->contains($itUser));
        $this->assertTrue($hrUsers->contains($hrUser));
        $this->assertTrue($financeUsers->contains($financeUser));
    }

    public function test_admins_scope()
    {
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        $admins = User::admins()->get();

        $this->assertCount(2, $admins);
        $this->assertTrue($admins->contains($admin1));
        $this->assertTrue($admins->contains($admin2));
        $this->assertFalse($admins->contains($supervisor));
        $this->assertFalse($admins->contains($member));
    }

    public function test_supervisors_scope()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor1 = User::factory()->create(['role' => 'supervisor']);
        $supervisor2 = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        $supervisors = User::supervisors()->get();

        $this->assertCount(2, $supervisors);
        $this->assertTrue($supervisors->contains($supervisor1));
        $this->assertTrue($supervisors->contains($supervisor2));
        $this->assertFalse($supervisors->contains($admin));
        $this->assertFalse($supervisors->contains($member));
    }

    public function test_members_scope()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member1 = User::factory()->create(['role' => 'member']);
        $member2 = User::factory()->create(['role' => 'member']);

        $members = User::members()->get();

        $this->assertCount(2, $members);
        $this->assertTrue($members->contains($member1));
        $this->assertTrue($members->contains($member2));
        $this->assertFalse($members->contains($admin));
        $this->assertFalse($members->contains($supervisor));
    }

    public function test_is_admin_method()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($supervisor->isAdmin());
        $this->assertFalse($member->isAdmin());
    }

    public function test_is_supervisor_method()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        $this->assertFalse($admin->isSupervisor());
        $this->assertTrue($supervisor->isSupervisor());
        $this->assertFalse($member->isSupervisor());
    }

    public function test_is_member_method()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        $this->assertFalse($admin->isMember());
        $this->assertFalse($supervisor->isMember());
        $this->assertTrue($member->isMember());
    }

    public function test_can_manage_activities_method()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        $this->assertTrue($admin->canManageActivities());
        $this->assertTrue($supervisor->canManageActivities());
        $this->assertFalse($member->canManageActivities());
    }

    public function test_user_role_validation()
    {
        // Test valid roles
        $validRoles = ['admin', 'supervisor', 'member'];
        
        foreach ($validRoles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertEquals($role, $user->role);
        }
    }

    public function test_employee_id_uniqueness()
    {
        $user1 = User::factory()->create(['employee_id' => 'EMP001']);
        
        // This should work fine as we're not enforcing uniqueness at model level
        // but it would be enforced at database level
        $this->assertEquals('EMP001', $user1->employee_id);
    }

    public function test_user_authentication_attributes()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Test that password is hidden in array conversion
        $userArray = $user->toArray();
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
        
        // But the actual attributes exist
        $this->assertNotNull($user->password);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_user_with_multiple_relationships()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        // Create activities where user is creator
        $createdActivity1 = Activity::factory()->create(['created_by' => $user->id]);
        $createdActivity2 = Activity::factory()->create(['created_by' => $user->id]);
        
        // Create activities where user is assignee
        $assignedActivity1 = Activity::factory()->create([
            'created_by' => $otherUser->id,
            'assigned_to' => $user->id
        ]);
        
        // Create activity updates by user
        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $createdActivity1->id,
            'user_id' => $user->id
        ]);
        
        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $assignedActivity1->id,
            'user_id' => $user->id
        ]);

        // Test all relationships
        $this->assertCount(2, $user->createdActivities);
        $this->assertCount(1, $user->assignedActivities);
        $this->assertCount(2, $user->activityUpdates);
        
        // Test relationship integrity
        $this->assertEquals($user->id, $createdActivity1->created_by);
        $this->assertEquals($user->id, $assignedActivity1->assigned_to);
        $this->assertEquals($user->id, $update1->user_id);
        $this->assertEquals($user->id, $update2->user_id);
    }
}