<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin'
        ]);
    }

    /** @test */
    public function it_requires_csrf_token_for_activity_creation()
    {
        $this->actingAs($this->user);

        // Disable CSRF middleware for this test to simulate missing token
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->post(route('activities.store'), [
            'name' => 'Test Activity',
            'description' => 'Test description for activity',
            'priority' => 'medium',
        ]);

        // With CSRF middleware disabled, this should work
        $response->assertRedirect();

        // Now test with CSRF middleware enabled (default)
        $this->refreshApplication();
        
        $response = $this->post(route('activities.store'), [
            'name' => 'Test Activity 2',
            'description' => 'Test description for activity 2',
            'priority' => 'medium',
        ]);

        // Without CSRF token, this should fail
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function it_requires_csrf_token_for_activity_updates()
    {
        $this->actingAs($this->user);
        
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id
        ]);

        $response = $this->put(route('activities.update', $activity), [
            'name' => 'Updated Activity Name',
            'description' => 'Updated description for activity',
            'priority' => 'high',
        ]);

        // Without CSRF token, this should fail
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function it_requires_csrf_token_for_status_updates()
    {
        $this->actingAs($this->user);
        
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id
        ]);

        $response = $this->post(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => 'Task completed successfully',
        ]);

        // Without CSRF token, this should fail
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function it_accepts_requests_with_valid_csrf_token()
    {
        $this->actingAs($this->user);

        // Get CSRF token
        $response = $this->get(route('activities.create'));
        $response->assertStatus(200);

        // Extract CSRF token from the response
        $token = $this->app['session']->token();

        $response = $this->post(route('activities.store'), [
            '_token' => $token,
            'name' => 'Test Activity with CSRF',
            'description' => 'Test description with valid CSRF token',
            'priority' => 'medium',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'name' => 'Test Activity with CSRF'
        ]);
    }

    /** @test */
    public function it_protects_login_form_with_csrf()
    {
        $response = $this->post(route('login'), [
            'login' => 'test@example.com',
            'password' => 'password',
        ]);

        // Without CSRF token, login should fail
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function it_includes_csrf_token_in_forms()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('activities.create'));
        
        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);
        $response->assertSee('csrf_token()', false);
    }

    /** @test */
    public function it_regenerates_csrf_token_on_login()
    {
        // Get initial CSRF token
        $response = $this->get(route('login'));
        $initialToken = $this->app['session']->token();

        // Perform login
        $response = $this->post(route('login'), [
            '_token' => $initialToken,
            'login' => $this->user->email,
            'password' => 'password',
        ]);

        // Token should be regenerated after login
        $newToken = $this->app['session']->token();
        $this->assertNotEquals($initialToken, $newToken);
    }
}