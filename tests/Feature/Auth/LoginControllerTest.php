<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'employee_id' => 'EMP001',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_employee_id()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'employee_id' => 'EMP001',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'EMP001',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'employee_id' => 'EMP001',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'login' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_login_requires_login_field()
    {
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['login']);
    }

    public function test_login_requires_password_field()
    {
        $response = $this->post('/login', [
            'login' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}