<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputValidationTest extends TestCase
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
    public function it_rejects_malicious_script_tags_in_activity_name()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('activities.store'), [
            'name' => '<script>alert("XSS")</script>',
            'description' => 'Valid description for testing',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('activities', [
            'name' => '<script>alert("XSS")</script>'
        ]);
    }

    /** @test */
    public function it_rejects_javascript_urls_in_description()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('activities.store'), [
            'name' => 'Valid Activity Name',
            'description' => 'Click here: javascript:alert("XSS")',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('description');
    }

    /** @test */
    public function it_rejects_sql_injection_attempts()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('activities.store'), [
            'name' => "'; DROP TABLE activities; --",
            'description' => 'Valid description for testing',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function it_rejects_path_traversal_attempts()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('activities.store'), [
            'name' => '../../../etc/passwd',
            'description' => 'Valid description for testing',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function it_accepts_valid_input_with_safe_characters()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('activities.store'), [
            'name' => 'Valid Activity Name (Test)',
            'description' => 'This is a valid description with safe characters.',
            'priority' => 'medium',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'name' => 'Valid Activity Name (Test)',
            'description' => 'This is a valid description with safe characters.'
        ]);
    }

    /** @test */
    public function it_validates_activity_name_length_constraints()
    {
        $this->actingAs($this->user);

        // Test minimum length
        $response = $this->post(route('activities.store'), [
            'name' => 'AB', // Too short
            'description' => 'Valid description for testing',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('name');

        // Test maximum length
        $longName = str_repeat('A', 256); // Too long
        $response = $this->post(route('activities.store'), [
            'name' => $longName,
            'description' => 'Valid description for testing',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function it_validates_description_length_constraints()
    {
        $this->actingAs($this->user);

        // Test minimum length
        $response = $this->post(route('activities.store'), [
            'name' => 'Valid Activity Name',
            'description' => 'Short', // Too short
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('description');

        // Test maximum length
        $longDescription = str_repeat('A', 2001); // Too long
        $response = $this->post(route('activities.store'), [
            'name' => 'Valid Activity Name',
            'description' => $longDescription,
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('description');
    }

    /** @test */
    public function it_validates_status_update_remarks()
    {
        $this->actingAs($this->user);
        
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id
        ]);

        // Test malicious content in remarks
        $response = $this->post(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => '<script>alert("XSS")</script>',
        ]);

        $response->assertSessionHasErrors('remarks');

        // Test valid remarks
        $response = $this->post(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => 'Task completed successfully with all requirements met.',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_sanitizes_input_data()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('activities.store'), [
            'name' => '  Valid Activity Name  ', // Extra whitespace
            'description' => '  This is a valid description.  ', // Extra whitespace
            'priority' => 'medium',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'name' => 'Valid Activity Name', // Trimmed
            'description' => 'This is a valid description.' // Trimmed
        ]);
    }

    /** @test */
    public function it_validates_priority_values()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('activities.store'), [
            'name' => 'Valid Activity Name',
            'description' => 'Valid description for testing',
            'priority' => 'invalid_priority',
        ]);

        $response->assertSessionHasErrors('priority');
    }

    /** @test */
    public function it_validates_assigned_user_exists()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('activities.store'), [
            'name' => 'Valid Activity Name',
            'description' => 'Valid description for testing',
            'priority' => 'medium',
            'assigned_to' => 99999, // Non-existent user ID
        ]);

        $response->assertSessionHasErrors('assigned_to');
    }

    /** @test */
    public function it_validates_due_date_constraints()
    {
        $this->actingAs($this->user);

        // Test past date
        $response = $this->post(route('activities.store'), [
            'name' => 'Valid Activity Name',
            'description' => 'Valid description for testing',
            'priority' => 'medium',
            'due_date' => '2020-01-01', // Past date
        ]);

        $response->assertSessionHasErrors('due_date');

        // Test far future date
        $response = $this->post(route('activities.store'), [
            'name' => 'Valid Activity Name',
            'description' => 'Valid description for testing',
            'priority' => 'medium',
            'due_date' => '2030-01-01', // Too far in future
        ]);

        $response->assertSessionHasErrors('due_date');
    }
}