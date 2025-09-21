<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Department;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $regularUser;
    protected Department $department;
    protected Role $adminRole;
    protected Role $memberRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'manage-users']);
        Permission::create(['name' => 'manage-activities']);
        Permission::create(['name' => 'view-reports']);

        // Create roles
        $this->adminRole = Role::create(['name' => 'Administrator']);
        $this->memberRole = Role::create(['name' => 'Team Member']);

        // Assign permissions to roles
        $this->adminRole->givePermissionTo(['manage-users', 'manage-activities', 'view-reports']);
        $this->memberRole->givePermissionTo(['view-reports']);

        // Create department
        $this->department = Department::factory()->create([
            'name' => 'Test Department'
        ]);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'status' => 'active'
        ]);
        $this->adminUser->assignRole($this->adminRole);

        // Create regular user
        $this->regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'role' => 'member',
            'status' => 'active',
            'department_id' => $this->department->id
        ]);
        $this->regularUser->assignRole($this->memberRole);
    }

    /** @test */
    public function admin_can_view_user_management_index()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
        $response->assertViewHas('users');
        $response->assertSee($this->regularUser->name);
        $response->assertSee($this->regularUser->email);
    }

    /** @test */
    public function non_admin_cannot_access_user_management()
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_search_users()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index', ['search' => 'Regular']));

        $response->assertStatus(200);
        $response->assertSee($this->regularUser->name);
        $response->assertDontSee($this->adminUser->name);
    }

    /** @test */
    public function admin_can_filter_users_by_status()
    {
        // Create inactive user
        $inactiveUser = User::factory()->create([
            'status' => 'inactive'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index', ['status' => 'inactive']));

        $response->assertStatus(200);
        $response->assertSee($inactiveUser->name);
        $response->assertDontSee($this->regularUser->name);
    }

    /** @test */
    public function admin_can_filter_users_by_role()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index', ['role' => 'Administrator']));

        $response->assertStatus(200);
        $response->assertSee($this->adminUser->name);
        $response->assertDontSee($this->regularUser->name);
    }

    /** @test */
    public function admin_can_filter_users_by_department()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index', ['department_id' => $this->department->id]));

        $response->assertStatus(200);
        $response->assertSee($this->regularUser->name);
        $response->assertDontSee($this->adminUser->name);
    }

    /** @test */
    public function admin_can_view_create_user_form()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.create');
        $response->assertViewHas('departments');
        $response->assertViewHas('roles');
    }

    /** @test */
    public function admin_can_create_new_user()
    {
        $userData = [
            'name' => 'New Test User',
            'email' => 'newuser@test.com',
            'employee_id' => 'EMP001',
            'phone_number' => '+1234567890',
            'role' => 'Team Member',
            'department_id' => $this->department->id,
            'status' => 'active',
            'send_welcome_email' => true
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.store'), $userData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'New Test User',
            'email' => 'newuser@test.com',
            'employee_id' => 'EMP001',
            'status' => 'active'
        ]);

        $user = User::where('email', 'newuser@test.com')->first();
        $this->assertTrue($user->hasRole('Team Member'));
    }

    /** @test */
    public function create_user_validates_required_fields()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'role']);
    }

    /** @test */
    public function create_user_validates_unique_email()
    {
        $userData = [
            'name' => 'Test User',
            'email' => $this->regularUser->email, // Duplicate email
            'role' => 'Team Member'
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.store'), $userData);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function create_user_validates_unique_employee_id()
    {
        $this->regularUser->update(['employee_id' => 'EMP001']);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'employee_id' => 'EMP001', // Duplicate employee ID
            'role' => 'Team Member'
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.store'), $userData);

        $response->assertSessionHasErrors(['employee_id']);
    }

    /** @test */
    public function admin_can_view_user_details()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.show', $this->regularUser));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.show');
        $response->assertViewHas('user');
        $response->assertSee($this->regularUser->name);
        $response->assertSee($this->regularUser->email);
    }

    /** @test */
    public function admin_can_view_edit_user_form()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.edit', $this->regularUser));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.edit');
        $response->assertViewHas('user');
        $response->assertViewHas('departments');
        $response->assertViewHas('roles');
    }

    /** @test */
    public function admin_can_update_user()
    {
        $updateData = [
            'name' => 'Updated User Name',
            'email' => 'updated@test.com',
            'phone_number' => '+9876543210',
            'role' => 'Administrator'
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.users.update', $this->regularUser), $updateData);

        $response->assertRedirect(route('admin.users.show', $this->regularUser));
        $response->assertSessionHas('success');

        $this->regularUser->refresh();
        $this->assertEquals('Updated User Name', $this->regularUser->name);
        $this->assertEquals('updated@test.com', $this->regularUser->email);
        $this->assertEquals('+9876543210', $this->regularUser->phone_number);
    }

    /** @test */
    public function update_user_validates_unique_email_excluding_current_user()
    {
        $otherUser = User::factory()->create(['email' => 'other@test.com']);

        $updateData = [
            'name' => $this->regularUser->name,
            'email' => $otherUser->email, // Duplicate email from another user
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.users.update', $this->regularUser), $updateData);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function admin_can_deactivate_user()
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.users.destroy', $this->regularUser));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->regularUser->refresh();
        $this->assertEquals('inactive', $this->regularUser->status);
    }

    /** @test */
    public function admin_cannot_deactivate_own_account()
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.users.destroy', $this->adminUser));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->adminUser->refresh();
        $this->assertEquals('active', $this->adminUser->status);
    }

    /** @test */
    public function admin_can_reactivate_user()
    {
        $this->regularUser->update(['status' => 'inactive']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.restore', $this->regularUser));

        $response->assertRedirect(route('admin.users.show', $this->regularUser));
        $response->assertSessionHas('success');

        $this->regularUser->refresh();
        $this->assertEquals('active', $this->regularUser->status);
    }

    /** @test */
    public function admin_can_perform_bulk_activate_action()
    {
        $user1 = User::factory()->create(['status' => 'inactive']);
        $user2 = User::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.bulk-action'), [
                'action' => 'activate',
                'user_ids' => [$user1->id, $user2->id]
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user1->refresh();
        $user2->refresh();
        $this->assertEquals('active', $user1->status);
        $this->assertEquals('active', $user2->status);
    }

    /** @test */
    public function admin_can_perform_bulk_deactivate_action()
    {
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.bulk-action'), [
                'action' => 'deactivate',
                'user_ids' => [$user1->id, $user2->id]
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user1->refresh();
        $user2->refresh();
        $this->assertEquals('inactive', $user1->status);
        $this->assertEquals('inactive', $user2->status);
    }

    /** @test */
    public function admin_cannot_bulk_deactivate_own_account()
    {
        $user1 = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.bulk-action'), [
                'action' => 'deactivate',
                'user_ids' => [$this->adminUser->id, $user1->id]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->adminUser->refresh();
        $this->assertEquals('active', $this->adminUser->status);
    }

    /** @test */
    public function admin_can_perform_bulk_role_assignment()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.bulk-action'), [
                'action' => 'assign_role',
                'user_ids' => [$user1->id, $user2->id],
                'role' => 'Administrator'
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user1->refresh();
        $user2->refresh();
        $this->assertTrue($user1->hasRole('Administrator'));
        $this->assertTrue($user2->hasRole('Administrator'));
    }

    /** @test */
    public function admin_can_perform_bulk_department_change()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.bulk-action'), [
                'action' => 'change_department',
                'user_ids' => [$user1->id, $user2->id],
                'department_id' => $this->department->id
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user1->refresh();
        $user2->refresh();
        $this->assertEquals($this->department->id, $user1->department_id);
        $this->assertEquals($this->department->id, $user2->department_id);
    }

    /** @test */
    public function bulk_action_validates_required_fields()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.bulk-action'), []);

        $response->assertSessionHasErrors(['action', 'user_ids']);
    }

    /** @test */
    public function bulk_action_validates_role_when_assigning_role()
    {
        $user1 = User::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.bulk-action'), [
                'action' => 'assign_role',
                'user_ids' => [$user1->id]
                // Missing 'role' field
            ]);

        $response->assertSessionHasErrors(['role']);
    }

    /** @test */
    public function bulk_action_validates_department_when_changing_department()
    {
        $user1 = User::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.bulk-action'), [
                'action' => 'change_department',
                'user_ids' => [$user1->id]
                // Missing 'department_id' field
            ]);

        $response->assertSessionHasErrors(['department_id']);
    }

    /** @test */
    public function admin_can_export_users()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.export'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'filename'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertStringContains('users_export_', $data['filename']);
    }

    /** @test */
    public function admin_can_get_user_statistics()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.statistics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_users',
            'active_users',
            'inactive_users',
            'locked_users',
            'pending_users',
            'recent_logins',
            'users_by_role',
            'users_by_department'
        ]);

        $data = $response->json();
        $this->assertIsInt($data['total_users']);
        $this->assertIsInt($data['active_users']);
    }

    /** @test */
    public function pagination_works_correctly()
    {
        // Create 20 users to test pagination
        User::factory()->count(20)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewHas('users');
        
        $users = $response->viewData('users');
        $this->assertEquals(15, $users->perPage()); // Default pagination
        $this->assertTrue($users->hasPages());
    }

    /** @test */
    public function sorting_works_correctly()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index', [
                'sort_by' => 'name',
                'sort_direction' => 'asc'
            ]));

        $response->assertStatus(200);
        $response->assertViewHas('users');
    }
}