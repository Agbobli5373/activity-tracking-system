<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected RoleMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RoleMiddleware();
        
        // Create test roles
        Role::create(['name' => 'Administrator']);
        Role::create(['name' => 'Supervisor']);
        Role::create(['name' => 'Team Member']);
    }

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $request = Request::create('/admin', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->getTargetUrl());
    }

    public function test_unauthenticated_json_request_returns_401()
    {
        $request = Request::create('/api/admin', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');

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
        
        $request = Request::create('/admin', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');

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
        
        $request = Request::create('/admin', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse(Auth::check());
    }

    public function test_user_with_required_spatie_role_can_access()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'admin'
        ]);
        $user->assignRole('Administrator');

        Auth::login($user);
        
        $request = Request::create('/admin', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_user_with_required_legacy_role_can_access()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'admin'
        ]);

        Auth::login($user);
        
        $request = Request::create('/admin', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_user_with_any_of_multiple_required_roles_can_access()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'supervisor'
        ]);
        $user->assignRole('Supervisor');

        Auth::login($user);
        
        $request = Request::create('/admin', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator', 'Supervisor');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_user_without_required_role_gets_403()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'member'
        ]);
        $user->assignRole('Team Member');

        Auth::login($user);
        
        $request = Request::create('/admin', 'GET');
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');
    }

    public function test_user_without_required_role_json_request_returns_403()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'member'
        ]);
        $user->assignRole('Team Member');

        Auth::login($user);
        
        $request = Request::create('/api/admin', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Insufficient permissions', $response->getContent());
    }

    public function test_unauthorized_access_attempt_is_logged()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Unauthorized access attempt', \Mockery::type('array'));

        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'member'
        ]);
        $user->assignRole('Team Member');

        Auth::login($user);
        
        $request = Request::create('/admin', 'GET');
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');
    }

    public function test_middleware_works_with_single_role_parameter()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'admin'
        ]);
        $user->assignRole('Administrator');

        Auth::login($user);
        
        $request = Request::create('/admin', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_works_with_multiple_role_parameters()
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role' => 'supervisor'
        ]);
        $user->assignRole('Supervisor');

        Auth::login($user);
        
        $request = Request::create('/admin', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator', 'Supervisor', 'Team Member');

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
        
        $request = Request::create('/api/admin', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        }, 'Administrator');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Account is inactive or locked', $response->getContent());
        $this->assertFalse(Auth::check());
    }
}