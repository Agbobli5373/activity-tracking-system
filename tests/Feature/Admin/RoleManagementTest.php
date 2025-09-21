<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions for testing
        Permission::create(['name' => 'manage-users']);
        Permission::create(['name' => 'manage-roles']);
        Permission::create(['name' => 'view-reports']);
        Permission::create(['name' => 'manage-settings']);
        
        // Create admin user with role management permissions
        $this->adminUser = User::factory()->create(['status' => 'active']);
        $adminRole = Role::create(['name' => 'Administrator']);
        $adminRole->givePermissionTo(['manage-users', 'manage-roles', 'view-reports', 'manage-settings']);
        $this->adminUser->assignRole($adminRole);
        
        // Create regular user for testing (no roles assigned)
        $this->regularUser = User::factory()->create(['status' => 'active', 'role' => 'member']);
    }

    /** @test */
    public function admin_can_view_roles_index()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.roles.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.index');
        $response->assertViewHas(['roles', 'permissions']);
    }

    /** @test */
    public function regular_user_cannot_access_roles_index()
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.roles.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_create_role_form()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.roles.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.create');
        $response->assertViewHas('permissions');
    }

    /** @test */
    public function admin_can_create_role_with_permissions()
    {
        $roleData = [
            'name' => 'Test Role',
            'description' => 'A test role for testing',
            'permissions' => ['manage-users', 'view-reports']
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.roles.store'), $roleData);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('roles', [
            'name' => 'Test Role',
            'description' => 'A test role for testing'
        ]);

        $role = Role::where('name', 'Test Role')->first();
        $this->assertTrue($role->hasPermissionTo('manage-users'));
        $this->assertTrue($role->hasPermissionTo('view-reports'));
        $this->assertFalse($role->hasPermissionTo('manage-settings'));
    }

    /** @test */
    public function role_creation_requires_valid_data()
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.roles.store'), []);

        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function role_name_must_be_unique()
    {
        Role::create(['name' => 'Existing Role']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.roles.store'), [
                'name' => 'Existing Role',
                'description' => 'Test description'
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function admin_can_view_role_details()
    {
        $role = Role::create(['name' => 'Test Role', 'description' => 'Test description']);
        $role->givePermissionTo('manage-users');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.roles.show', $role));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.show');
        $response->assertViewHas('role');
    }

    /** @test */
    public function admin_can_view_edit_role_form()
    {
        $role = Role::create(['name' => 'Test Role', 'description' => 'Test description']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.roles.edit', $role));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.edit');
        $response->assertViewHas(['role', 'permissions', 'rolePermissions']);
    }

    /** @test */
    public function admin_can_update_role()
    {
        $role = Role::create(['name' => 'Test Role', 'description' => 'Test description']);
        $role->givePermissionTo('manage-users');

        $updateData = [
            'name' => 'Updated Role',
            'description' => 'Updated description',
            'permissions' => ['view-reports', 'manage-settings']
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.roles.update', $role), $updateData);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');

        $role->refresh();
        $this->assertEquals('Updated Role', $role->name);
        $this->assertEquals('Updated description', $role->description);
        $this->assertTrue($role->hasPermissionTo('view-reports'));
        $this->assertTrue($role->hasPermissionTo('manage-settings'));
        $this->assertFalse($role->hasPermissionTo('manage-users'));
    }

    /** @test */
    public function admin_can_delete_custom_role()
    {
        $role = Role::create(['name' => 'Custom Role', 'description' => 'Custom description']);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    /** @test */
    public function cannot_delete_system_roles()
    {
        // Use the existing Administrator role created in setUp
        $systemRole = Role::where('name', 'Administrator')->first();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.roles.destroy', $systemRole));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);

        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    /** @test */
    public function cannot_delete_role_with_assigned_users()
    {
        $role = Role::create(['name' => 'Test Role']);
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.roles.destroy', $role));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    /** @test */
    public function admin_can_view_permission_matrix()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.roles.permissions'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.permissions');
        $response->assertViewHas(['roles', 'permissions']);
    }

    /** @test */
    public function admin_can_update_permission_matrix()
    {
        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);

        $permissionData = [
            'role_permissions' => [
                $role1->id => ['manage-users', 'view-reports'],
                $role2->id => ['manage-settings']
            ]
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.roles.update-permissions'), $permissionData);

        $response->assertRedirect(route('admin.roles.permissions'));
        $response->assertSessionHas('success');

        $role1->refresh();
        $role2->refresh();

        $this->assertTrue($role1->hasPermissionTo('manage-users'));
        $this->assertTrue($role1->hasPermissionTo('view-reports'));
        $this->assertTrue($role2->hasPermissionTo('manage-settings'));
    }

    /** @test */
    public function admin_can_view_role_assignment_interface()
    {
        $role = Role::create(['name' => 'Test Role']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.roles.assign-users', $role));

        $response->assertStatus(200);
        $response->assertViewIs('admin.roles.assign-users');
        $response->assertViewHas(['role', 'users', 'usersWithRole']);
    }

    /** @test */
    public function admin_can_bulk_assign_users_to_role()
    {
        $role = Role::create(['name' => 'Test Role']);
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);
        $user3 = User::factory()->create(['status' => 'active']);

        // Initially assign user1 to the role
        $user1->assignRole($role);

        $assignmentData = [
            'user_ids' => [$user2->id, $user3->id] // Remove user1, add user2 and user3
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.roles.update-user-assignments', $role), $assignmentData);

        $response->assertRedirect(route('admin.roles.index'));
        $response->assertSessionHas('success');

        $this->assertFalse($user1->hasRole('Test Role'));
        $this->assertTrue($user2->hasRole('Test Role'));
        $this->assertTrue($user3->hasRole('Test Role'));
    }

    /** @test */
    public function admin_can_get_role_statistics()
    {
        $role1 = Role::create(['name' => 'Role 1']);
        $role2 = Role::create(['name' => 'Role 2']);
        
        $user1 = User::factory()->create(['status' => 'active']);
        $user2 = User::factory()->create(['status' => 'active']);
        $user3 = User::factory()->create(['status' => 'active']);
        
        $user1->assignRole($role1);
        $user2->assignRole($role1);
        $user3->assignRole($role2);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.roles.statistics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_roles',
            'total_permissions',
            'users_with_roles',
            'users_without_roles',
            'role_distribution'
        ]);

        $data = $response->json();
        $this->assertGreaterThan(0, $data['total_roles']);
        $this->assertGreaterThan(0, $data['total_permissions']);
        $this->assertEquals(4, $data['users_with_roles']); // 3 test users + 1 admin user from setUp
    }

    /** @test */
    public function guest_cannot_access_role_management()
    {
        $role = Role::create(['name' => 'Test Role']);

        $routes = [
            ['GET', route('admin.roles.index')],
            ['GET', route('admin.roles.create')],
            ['POST', route('admin.roles.store')],
            ['GET', route('admin.roles.show', $role)],
            ['GET', route('admin.roles.edit', $role)],
            ['PUT', route('admin.roles.update', $role)],
            ['DELETE', route('admin.roles.destroy', $role)],
            ['GET', route('admin.roles.permissions')],
            ['POST', route('admin.roles.update-permissions')],
            ['GET', route('admin.roles.assign-users', $role)],
            ['POST', route('admin.roles.update-user-assignments', $role)],
            ['GET', route('admin.roles.statistics')]
        ];

        foreach ($routes as [$method, $url]) {
            $response = $this->call($method, $url);
            $this->assertContains($response->getStatusCode(), [302, 401, 403], 
                "Route {$method} {$url} should be protected");
        }
    }
}