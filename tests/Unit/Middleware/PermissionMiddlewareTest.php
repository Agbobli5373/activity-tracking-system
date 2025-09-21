<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new PermissionMiddleware();
        
        // Create test permissions
        Permission::create(['name' => 'manage-users']);
        Permission::create(['name' => 'manage-activities']);
        Permission::create(['name' => 'view-reports']);
        Permission::create(['name' => 'manage-system']);
        
        // Create test roles with permissions
        $adminRole = Role::create(['name' => 'Administrator']);
        $adminRole->givePermissionTo(['manage-users', 'manage-activities', 'view-reports', 'manage-system']);
        
        $supervisorRole = Role::create(['name' => 'Supervisor']);
        $supervisorRole->givePermissionTo(['manage-activities', 'view-reports']);
        
        $memberRole = Role::create(['name' => 'Team Member']);
        $memberRole->givePermissionTo(['view-reports']);
    }

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $request = Request::create('/admin/users', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->getTargetUrl());
    }

    public function test_unauthenticated_json_request_returns_401()
    {
        $request = Request::create('/api/admin/users', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Unauthenticated', $response->getContent());
    }

    public function test_inactive_user_is_logged_out_and_redirected()
    {
        $user = User::factory()->create([
            'status' => 'inactive',
            'role' => 'admin'
        ]);
        $user->assignRole('Administrator');

        Auth::login($user);
        
        $request = Request::create('/admin/users', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse(Auth::check());
    }

    public function test_locked_user_is_logged_out_and_redirected()
    {
        $user = User::factory()->create([
            'status' => 'locked',
            'role' => 'admin'
        ]);
        $user->assignRole('Administrator');

        Auth::login($user);
        
        $request = Request::create('/admin/users', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse(Auth::check());
    }

    public function test_user_with_required_spatie_permission_can_access()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'admin'
        ]);
        $user->assignRole('Administrator');

        Auth::login($user);
        
        $request = Request::create('/admin/users', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_user_with_required_legacy_permission_can_access()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'admin'  // Legacy admin role has manage-users permission
        ]);

        Auth::login($user);
        
        $request = Request::create('/admin/users', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_user_without_required_permission_gets_403()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'member'
        ]);
        $user->assignRole('Team Member');

        Auth::login($user);
        
        $request = Request::create('/admin/users', 'GET');
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');
    }

    public function test_user_without_required_permission_json_request_returns_403()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'member'
        ]);
        $user->assignRole('Team Member');

        Auth::login($user);
        
        $request = Request::create('/api/admin/users', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Insufficient permissions', $response->getContent());
        $this->assertStringContainsString('manage-users', $response->getContent());
    }

    public function test_unauthorized_permission_access_attempt_is_logged()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Unauthorized permission access attempt', \Mockery::type('array'));

        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'member'
        ]);
        $user->assignRole('Team Member');

        Auth::login($user);
        
        $request = Request::create('/admin/users', 'GET');
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');
    }

    public function test_middleware_works_with_guard_parameter()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'admin'
        ]);
        $user->assignRole('Administrator');

        Auth::login($user);
        
        $request = Request::create('/admin/users', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users', 'web');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_supervisor_can_access_manage_activities_permission()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'supervisor'
        ]);
        $user->assignRole('Supervisor');

        Auth::login($user);
        
        $request = Request::create('/admin/activities', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-activities');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_supervisor_cannot_access_manage_users_permission()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'supervisor'
        ]);
        $user->assignRole('Supervisor');

        Auth::login($user);
        
        $request = Request::create('/admin/users', 'GET');
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');
    }

    public function test_team_member_can_access_view_reports_permission()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'member'
        ]);
        $user->assignRole('Team Member');

        Auth::login($user);
        
        $request = Request::create('/reports', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'view-reports');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_inactive_user_json_request_returns_403()
    {
        $user = User::factory()->create([
            'status' => 'inactive',
            'role' => 'admin'
        ]);
        $user->assignRole('Administrator');

        Auth::login($user);
        
        $request = Request::create('/api/admin/users', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-users');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Account is inactive or locked', $response->getContent());
        $this->assertFalse(Auth::check());
    }

    public function test_user_with_direct_permission_can_access()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'member'
        ]);
        
        // Give user direct permission (not through role)
        $user->givePermissionTo('manage-system');

        Auth::login($user);
        
        $request = Request::create('/admin/settings', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-system');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_legacy_role_permissions_work_without_spatie_roles()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'supervisor'  // Legacy supervisor role
        ]);
        // Don't assign Spatie role, test legacy permission system

        Auth::login($user);
        
        $request = Request::create('/admin/activities', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'manage-activities');

        $this->assertEquals(200, $response->getStatusCode());
    }
}