<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'employee_id' => 'EMP001',
            'password' => Hash::make('password123'),
            'role' => 'member',
            'department' => 'IT Support'
        ]);
    }

    /** @test */
    public function users_can_authenticate_with_email()
    {
        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function users_can_authenticate_with_employee_id()
    {
        $response = $this->post('/login', [
            'login' => 'EMP001',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function users_cannot_authenticate_with_invalid_password()
    {
        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['login']);
    }

    /** @test */
    public function users_cannot_authenticate_with_invalid_credentials()
    {
        $response = $this->post('/login', [
            'login' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['login']);
    }

    /** @test */
    public function login_requires_valid_data()
    {
        // Test missing login
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors(['login']);

        // Test missing password
        $response = $this->post('/login', [
            'login' => 'test@example.com',
        ]);
        $response->assertSessionHasErrors(['password']);

        // Test empty login
        $response = $this->post('/login', [
            'login' => '',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors(['login']);
    }

    /** @test */
    public function authenticated_users_are_redirected_from_login()
    {
        $this->actingAs($this->user);

        $response = $this->get('/login');
        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function users_can_logout()
    {
        $this->actingAs($this->user);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    /** @test */
    public function remember_me_functionality_works()
    {
        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
        
        // Check that remember token is set
        $this->assertNotNull($this->user->fresh()->remember_token);
    }

    /** @test */
    public function session_timeout_redirects_to_login()
    {
        // Don't authenticate user - simulate unauthenticated access
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function multiple_failed_login_attempts_are_handled()
    {
        // Make multiple failed login attempts
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post('/login', [
                'login' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors(['login']);
        }

        // System should still be responsive after multiple failed attempts
        $response = $this->post('/login', [
            'login' => $this->user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /** @test */
    public function api_authentication_works_with_sanctum()
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'email' => 'test@example.com',
            'employee_id' => 'EMP001',
        ]);
    }

    /** @test */
    public function api_requires_authentication()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);

        $response = $this->getJson('/api/activities');
        $response->assertStatus(401);
    }

    /** @test */
    public function invalid_api_token_is_rejected()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/user');

        $response->assertStatus(401);
    }
}