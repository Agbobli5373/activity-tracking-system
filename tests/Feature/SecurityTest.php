<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\NoMaliciousContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'member',
            'employee_id' => 'EMP001',
        ]);
    }

    /** @test */
    public function it_blocks_xss_attempts_in_activity_creation()
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'name' => '<script>alert("XSS")</script>Test Activity',
            'description' => 'This is a test <script>alert("XSS")</script> description',
            'priority' => 'medium',
        ];

        $response = $this->post(route('activities.store'), $maliciousData);

        $response->assertSessionHasErrors(['name', 'description']);
    }

    /** @test */
    public function it_blocks_sql_injection_attempts()
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'name' => "Test'; DROP TABLE activities; --",
            'description' => "Test description' OR '1'='1",
            'priority' => 'medium',
        ];

        $response = $this->post(route('activities.store'), $maliciousData);

        $response->assertSessionHasErrors(['name', 'description']);
    }

    /** @test */
    public function it_applies_rate_limiting_to_login_attempts()
    {
        // Make 6 failed login attempts (limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post(route('login'), [
                'login' => 'invalid@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // The 6th attempt should be rate limited
        $response->assertStatus(429);
    }

    /** @test */
    public function it_includes_csrf_protection_on_forms()
    {
        $this->actingAs($this->user);

        // Test activity creation without CSRF token
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('activities.store'), [
                'name' => 'Test Activity',
                'description' => 'Test description',
            ]);

        // With CSRF middleware disabled, it should work
        $response->assertRedirect();

        // Test with CSRF middleware enabled (default)
        $response = $this->post(route('activities.store'), [
            'name' => 'Test Activity',
            'description' => 'Test description',
        ]);

        // Without CSRF token, it should fail
        $response->assertStatus(419);
    }

    /** @test */
    public function it_sanitizes_input_data()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('activities.store'), [
            'name' => "  Test Activity  \n\r",
            'description' => "  Test description with extra spaces  \t",
            'priority' => 'medium',
        ]);

        $response->assertRedirect();
        
        $activity = $this->user->activities()->first();
        $this->assertEquals('Test Activity', $activity->name);
        $this->assertEquals('Test description with extra spaces', $activity->description);
    }

    /** @test */
    public function it_validates_input_length_limits()
    {
        $this->actingAs($this->user);

        $longName = str_repeat('a', 256); // Exceeds 255 character limit
        $shortDescription = 'short'; // Below 10 character minimum

        $response = $this->post(route('activities.store'), [
            'name' => $longName,
            'description' => $shortDescription,
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors(['name', 'description']);
    }

    /** @test */
    public function it_includes_security_headers()
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeaderMissing('Server'); // Should not expose server info
    }

    /** @test */
    public function malicious_content_rule_detects_xss()
    {
        $rule = new NoMaliciousContent();
        
        $validator = Validator::make(
            ['content' => '<script>alert("xss")</script>'],
            ['content' => $rule]
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function malicious_content_rule_detects_sql_injection()
    {
        $rule = new NoMaliciousContent();
        
        $validator = Validator::make(
            ['content' => "'; DROP TABLE users; --"],
            ['content' => $rule]
        );

        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function malicious_content_rule_allows_safe_content()
    {
        $rule = new NoMaliciousContent();
        
        $validator = Validator::make(
            ['content' => 'This is a safe string with normal punctuation!'],
            ['content' => $rule]
        );

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_logs_security_violations()
    {
        $this->actingAs($this->user);

        // Attempt XSS
        $this->post(route('activities.store'), [
            'name' => '<script>alert("XSS")</script>',
            'description' => 'Test description',
        ]);

        // Check that security violation was logged
        $this->assertDatabaseHas('activity_updates', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_validates_file_extensions_if_file_upload_exists()
    {
        // This test would be relevant if file upload functionality exists
        // For now, we'll test the security helper validation
        
        $fakeFile = [
            'name' => 'test.exe',
            'size' => 1024,
            'tmp_name' => '/tmp/test',
        ];

        $errors = \App\Helpers\SecurityHelper::validateFileUpload($fakeFile);
        
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('extension', $errors[0]);
    }
}