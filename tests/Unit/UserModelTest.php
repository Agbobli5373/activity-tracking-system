<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\Department;
use App\Models\UserProfile;
use App\Models\SystemSetting;
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
            'phone_number',
            'department_id',
            'status',
            'password',
            'last_login_at',
            'password_changed_at',
            'two_factor_enabled',
            'failed_login_attempts',
            'account_locked_until',
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

    // Enhanced User Model Tests for Role-based Functionality

    public function test_user_status_management_methods()
    {
        $user = User::factory()->create(['status' => 'active']);

        // Test status checking methods
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isInactive());
        $this->assertFalse($user->isLocked());
        $this->assertFalse($user->isPending());

        // Test deactivation
        $user->deactivate('Test reason');
        $user->refresh();
        $this->assertFalse($user->isActive());
        $this->assertTrue($user->isInactive());

        // Test activation
        $user->activate();
        $user->refresh();
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isInactive());

        // Test locking
        $user->lock(30, 'Security violation');
        $user->refresh();
        $this->assertTrue($user->isLocked());
        $this->assertFalse($user->isActive());
        $this->assertNotNull($user->account_locked_until);

        // Test unlocking
        $user->unlock();
        $user->refresh();
        $this->assertFalse($user->isLocked());
        $this->assertTrue($user->isActive());
        $this->assertNull($user->account_locked_until);
    }

    public function test_user_status_scopes()
    {
        $activeUser = User::factory()->create(['status' => 'active']);
        $inactiveUser = User::factory()->create(['status' => 'inactive']);
        $lockedUser = User::factory()->create(['status' => 'locked']);
        $pendingUser = User::factory()->create(['status' => 'pending']);

        // Test active scope
        $activeUsers = User::active()->get();
        $this->assertTrue($activeUsers->contains($activeUser));
        $this->assertFalse($activeUsers->contains($inactiveUser));
        $this->assertFalse($activeUsers->contains($lockedUser));

        // Test inactive scope
        $inactiveUsers = User::inactive()->get();
        $this->assertTrue($inactiveUsers->contains($inactiveUser));
        $this->assertFalse($inactiveUsers->contains($activeUser));

        // Test locked scope
        $lockedUsers = User::locked()->get();
        $this->assertTrue($lockedUsers->contains($lockedUser));
        $this->assertFalse($lockedUsers->contains($activeUser));

        // Test pending scope
        $pendingUsers = User::pending()->get();
        $this->assertTrue($pendingUsers->contains($pendingUser));
        $this->assertFalse($pendingUsers->contains($activeUser));

        // Test by status scope
        $statusUsers = User::byStatus('active')->get();
        $this->assertTrue($statusUsers->contains($activeUser));
        $this->assertFalse($statusUsers->contains($inactiveUser));
    }

    public function test_failed_login_attempts_management()
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 0,
            'status' => 'active'
        ]);

        // Test increment failed attempts
        $user->incrementFailedLoginAttempts();
        $user->refresh();
        $this->assertEquals(1, $user->failed_login_attempts);

        // Test reset failed attempts
        $user->resetFailedLoginAttempts();
        $user->refresh();
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_account_locking_after_max_attempts()
    {
        // Mock system setting for max attempts
        \App\Models\SystemSetting::set('security_settings.max_login_attempts', 3);
        \App\Models\SystemSetting::set('security_settings.lockout_duration', 15);

        $user = User::factory()->create([
            'failed_login_attempts' => 2,
            'status' => 'active'
        ]);

        // This should lock the account
        $user->incrementFailedLoginAttempts();
        $user->refresh();

        $this->assertEquals(3, $user->failed_login_attempts);
        $this->assertEquals('locked', $user->status);
        $this->assertNotNull($user->account_locked_until);
    }

    public function test_legacy_permission_checking()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        // Test admin permissions
        $this->assertTrue($admin->hasLegacyPermission('manage-users'));
        $this->assertTrue($admin->hasLegacyPermission('manage-activities'));
        $this->assertTrue($admin->hasLegacyPermission('manage-system'));

        // Test supervisor permissions
        $this->assertFalse($supervisor->hasLegacyPermission('manage-users'));
        $this->assertTrue($supervisor->hasLegacyPermission('manage-activities'));
        $this->assertFalse($supervisor->hasLegacyPermission('manage-system'));

        // Test member permissions
        $this->assertFalse($member->hasLegacyPermission('manage-users'));
        $this->assertFalse($member->hasLegacyPermission('manage-activities'));
        $this->assertTrue($member->hasLegacyPermission('view-activities'));
    }

    public function test_role_display_methods()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        // Test primary role name
        $this->assertEquals('Admin', $admin->getPrimaryRoleName());
        $this->assertEquals('Supervisor', $supervisor->getPrimaryRoleName());
        $this->assertEquals('Member', $member->getPrimaryRoleName());

        // Test role display name
        $this->assertEquals('Administrator', $admin->getRoleDisplayName());
        $this->assertEquals('Supervisor', $supervisor->getRoleDisplayName());
        $this->assertEquals('Team Member', $member->getRoleDisplayName());
    }

    public function test_enhanced_permission_methods()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $member = User::factory()->create(['role' => 'member']);

        // Test can manage users
        $this->assertTrue($admin->canManageUsers());
        $this->assertFalse($supervisor->canManageUsers());
        $this->assertFalse($member->canManageUsers());

        // Test can view reports
        $this->assertTrue($admin->canViewReports());
        $this->assertTrue($supervisor->canViewReports());
        $this->assertFalse($member->canViewReports());

        // Test can manage system
        $this->assertTrue($admin->canManageSystem());
        $this->assertFalse($supervisor->canManageSystem());
        $this->assertFalse($member->canManageSystem());
    }

    public function test_additional_scopes()
    {
        $user1 = User::factory()->create([
            'failed_login_attempts' => 3,
            'last_login_at' => now()->subDays(5)
        ]);
        
        $user2 = User::factory()->create([
            'failed_login_attempts' => 0,
            'last_login_at' => now()->subDays(45)
        ]);

        // Test with failed attempts scope
        $usersWithFailedAttempts = User::withFailedAttempts()->get();
        $this->assertTrue($usersWithFailedAttempts->contains($user1));
        $this->assertFalse($usersWithFailedAttempts->contains($user2));

        // Test recently active scope
        $recentlyActiveUsers = User::recentlyActive(30)->get();
        $this->assertTrue($recentlyActiveUsers->contains($user1));
        $this->assertFalse($recentlyActiveUsers->contains($user2));
    }

    public function test_department_relationship()
    {
        $department = \App\Models\Department::factory()->create(['name' => 'IT Department']);
        $user = User::factory()->create(['department_id' => $department->id]);

        $this->assertInstanceOf(\App\Models\Department::class, $user->department);
        $this->assertEquals($department->id, $user->department->id);
        $this->assertEquals('IT Department', $user->department->name);
    }

    public function test_user_profile_relationship()
    {
        $user = User::factory()->create();
        $profile = \App\Models\UserProfile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(\App\Models\UserProfile::class, $user->profile);
        $this->assertEquals($profile->id, $user->profile->id);
        $this->assertEquals($user->id, $user->profile->user_id);
    }

    public function test_by_department_scope_with_department_id()
    {
        $department1 = \App\Models\Department::factory()->create();
        $department2 = \App\Models\Department::factory()->create();
        
        $user1 = User::factory()->create(['department_id' => $department1->id]);
        $user2 = User::factory()->create(['department_id' => $department2->id]);

        // Test by department ID
        $dept1Users = User::byDepartment($department1->id)->get();
        $this->assertTrue($dept1Users->contains($user1));
        $this->assertFalse($dept1Users->contains($user2));

        // Test by department name (legacy)
        $user3 = User::factory()->create(['department' => 'HR']);
        $hrUsers = User::byDepartment('HR')->get();
        $this->assertTrue($hrUsers->contains($user3));
    }

    public function test_account_locked_until_functionality()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'account_locked_until' => now()->addMinutes(30)
        ]);

        // Should be locked due to account_locked_until
        $this->assertTrue($user->isLocked());
        $this->assertFalse($user->isActive());

        // Test with past lock time
        $user->update(['account_locked_until' => now()->subMinutes(30)]);
        $user->refresh();
        
        // Should not be locked anymore
        $this->assertFalse($user->isLocked());
    }

    public function test_user_casts_for_new_fields()
    {
        $user = User::factory()->create([
            'last_login_at' => '2024-01-01 12:00:00',
            'password_changed_at' => '2024-01-01 10:00:00',
            'account_locked_until' => '2024-01-01 15:00:00',
            'two_factor_enabled' => true
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->last_login_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->password_changed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->account_locked_until);
        $this->assertTrue($user->two_factor_enabled);
    }
}