<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_allows_user_with_correct_role()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $middleware = new RoleMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'admin');

        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_allows_user_with_multiple_roles()
    {
        $user = User::factory()->create(['role' => 'supervisor']);
        $this->actingAs($user);

        $middleware = new RoleMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'admin', 'supervisor');

        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_denies_user_with_incorrect_role()
    {
        $user = User::factory()->create(['role' => 'member']);
        $this->actingAs($user);

        $middleware = new RoleMiddleware();
        $request = Request::create('/test', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized. You do not have permission to access this resource.');

        $middleware->handle($request, function () {
            return new Response('Success');
        }, 'admin');
    }

    public function test_middleware_redirects_unauthenticated_user()
    {
        $middleware = new RoleMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('Success');
        }, 'admin');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://localhost/login', $response->headers->get('Location'));
    }
}