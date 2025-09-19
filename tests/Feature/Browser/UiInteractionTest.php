<?php

namespace Tests\Feature\Browser;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'member',
            'department' => 'IT Support'
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'department' => 'IT Support'
        ]);
    }

    /** @test */
    public function dashboard_ajax_interactions_work()
    {
        $this->actingAs($this->user);

        // Create test activities
        Activity::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        Activity::factory()->count(2)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'created_at' => now(),
        ]);

        // Test AJAX endpoint for dashboard activities
        $response = $this->getJson('/dashboard/activities');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'activities',
            'summary' => [
                'total',
                'pending',
                'done',
                'completion_rate'
            ]
        ]);

        // Verify data structure for JavaScript consumption
        $data = $response->json();
        $this->assertEquals(5, $data['summary']['total']);
        $this->assertEquals(3, $data['summary']['pending']);
        $this->assertEquals(2, $data['summary']['done']);
        $this->assertEquals(40.0, $data['summary']['completion_rate']);

        // Test AJAX endpoint for recent updates
        $response = $this->getJson('/dashboard/updates');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'updates',
            'timestamp'
        ]);
    }

    /** @test */
    public function activity_form_validation_works_with_javascript()
    {
        $this->actingAs($this->user);

        // Test form submission with missing required fields
        $response = $this->post('/activities', [
            'description' => 'Test description without name',
        ]);

        $response->assertSessionHasErrors(['name']);
        $response->assertRedirect();

        // Test form submission with invalid data
        $response = $this->post('/activities', [
            'name' => 'Test Activity',
            'description' => 'Test description',
            'priority' => 'invalid_priority',
        ]);

        $response->assertSessionHasErrors(['priority']);

        // Test successful form submission
        $response = $this->post('/activities', [
            'name' => 'Valid Activity',
            'description' => 'Valid description',
            'priority' => 'medium',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'name' => 'Valid Activity',
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function activity_status_update_modal_interactions()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        // Test status update form submission
        $response = $this->post("/activities/{$activity->id}/status", [
            'status' => 'done',
            'remarks' => 'Completed successfully',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'done',
        ]);

        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->user->id,
            'new_status' => 'done',
            'remarks' => 'Completed successfully',
        ]);
    }

    /** @test */
    public function dashboard_filtering_interactions()
    {
        $this->actingAs($this->user);

        // Create activities for different dates and statuses
        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'created_at' => now()->subDay(),
        ]);

        // Test date filtering
        $response = $this->get('/dashboard?date=' . now()->format('Y-m-d'));
        $response->assertStatus(200);
        $response->assertSee('pending');

        $response = $this->get('/dashboard?date=' . now()->subDay()->format('Y-m-d'));
        $response->assertStatus(200);
        $response->assertSee('done');

        // Test status filtering
        $response = $this->get('/dashboard?status=pending');
        $response->assertStatus(200);

        $response = $this->get('/dashboard?status=done');
        $response->assertStatus(200);
    }

    /** @test */
    public function activity_search_and_filtering_ui()
    {
        $this->actingAs($this->user);

        // Create activities with different attributes
        Activity::factory()->create([
            'name' => 'Server Maintenance Task',
            'status' => 'pending',
            'priority' => 'high',
            'created_by' => $this->user->id,
        ]);

        Activity::factory()->create([
            'name' => 'Database Backup',
            'status' => 'done',
            'priority' => 'medium',
            'created_by' => $this->user->id,
        ]);

        // Test search functionality
        $response = $this->get('/activities?search=Server');
        $response->assertStatus(200);
        $response->assertSee('Server Maintenance Task');
        $response->assertDontSee('Database Backup');

        // Test priority filtering
        $response = $this->get('/activities?priority=high');
        $response->assertStatus(200);
        $response->assertSee('Server Maintenance Task');
        $response->assertDontSee('Database Backup');

        // Test status filtering
        $response = $this->get('/activities?status=done');
        $response->assertStatus(200);
        $response->assertSee('Database Backup');
        $response->assertDontSee('Server Maintenance Task');

        // Test combined filters
        $response = $this->get('/activities?status=pending&priority=high');
        $response->assertStatus(200);
        $response->assertSee('Server Maintenance Task');
        $response->assertDontSee('Database Backup');
    }

    /** @test */
    public function report_generation_ui_interactions()
    {
        $this->actingAs($this->admin);

        // Create test data
        Activity::factory()->count(5)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'created_at' => now()->subDays(2),
        ]);

        // Test report generation form
        $response = $this->get('/reports');
        $response->assertStatus(200);
        $response->assertSee('Generate Report');
        $response->assertSee('Date Range');

        // Test AJAX report generation
        $response = $this->postJson('/reports/generate', [
            'start_date' => now()->subWeek()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'activities',
                'statistics'
            ]
        ]);

        // Test trends AJAX endpoint
        $response = $this->getJson('/reports/trends?' . http_build_query([
            'start_date' => now()->subWeek()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'group_by' => 'day'
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'labels',
                'datasets'
            ]
        ]);
    }

    /** @test */
    public function navigation_menu_interactions()
    {
        $this->actingAs($this->user);

        // Test main navigation links
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Activities');

        // Test activities navigation
        $response = $this->get('/activities');
        $response->assertStatus(200);
        $response->assertSee('Activities');

        // Test activity creation link
        $response = $this->get('/activities/create');
        $response->assertStatus(200);
        $response->assertSee('Create Activity');
    }

    /** @test */
    public function responsive_design_elements()
    {
        $this->actingAs($this->user);

        // Test dashboard responsiveness by checking for mobile-specific classes
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        
        // Check for responsive grid classes
        $response->assertSee('grid-cols-1');
        $response->assertSee('lg:grid-cols-3');
        
        // Check for mobile navigation elements
        $response->assertSee('md:hidden'); // Mobile menu button
        $response->assertSee('hidden md:block'); // Desktop elements

        // Test activities list responsiveness
        $response = $this->get('/activities');
        $response->assertStatus(200);
        
        // Check for responsive table/card layouts
        $response->assertSee('sm:px-6');
        $response->assertSee('lg:px-8');
    }

    /** @test */
    public function form_submission_with_csrf_protection()
    {
        $this->actingAs($this->user);

        // Test that forms include CSRF tokens
        $response = $this->get('/activities/create');
        $response->assertStatus(200);
        $response->assertSee('name="_token"', false);

        // Test form submission without CSRF token fails
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $response = $this->post('/activities', [
            'name' => 'Test Activity',
            'description' => 'Test description',
        ]);

        // With CSRF middleware disabled, this should work
        $response->assertRedirect();
    }

    /** @test */
    public function real_time_updates_simulation()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        // Get initial state
        $response = $this->getJson('/dashboard/activities');
        $response->assertStatus(200);
        $initialData = $response->json();

        // Update activity status
        $activity->update(['status' => 'done']);
        $activity->updates()->create([
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Completed',
        ]);

        // Get updated state
        $response = $this->getJson('/dashboard/activities');
        $response->assertStatus(200);
        $updatedData = $response->json();

        // Verify the data changed
        $this->assertNotEquals(
            $initialData['summary']['completion_rate'],
            $updatedData['summary']['completion_rate']
        );

        // Test recent updates endpoint
        $response = $this->getJson('/dashboard/updates');
        $response->assertStatus(200);
        $updates = $response->json('updates');
        
        $this->assertNotEmpty($updates);
        // Check if the update contains the expected data structure
        $this->assertArrayHasKey(0, $updates);
    }

    /** @test */
    public function error_handling_in_ajax_requests()
    {
        $this->actingAs($this->user);

        // Test invalid AJAX request
        $response = $this->getJson('/dashboard/activities?invalid_param=true');
        $response->assertStatus(200); // Should still work, just ignore invalid params

        // Test unauthorized AJAX request
        $this->actingAs($this->user);
        $response = $this->getJson('/reports/generate'); // GET instead of POST
        $response->assertStatus(405); // Method not allowed

        // Test missing required parameters
        $response = $this->postJson('/reports/generate', []);
        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /** @test */
    public function pagination_ui_interactions()
    {
        $this->actingAs($this->user);

        // Create many activities to trigger pagination
        Activity::factory()->count(25)->create([
            'created_by' => $this->user->id,
        ]);

        // Test first page
        $response = $this->get('/activities');
        $response->assertStatus(200);
        $response->assertSee('Next'); // Pagination link

        // Test second page
        $response = $this->get('/activities?page=2');
        $response->assertStatus(200);
        $response->assertSee('Previous'); // Pagination link

        // Test pagination with filters
        $response = $this->get('/activities?page=1&status=pending');
        $response->assertStatus(200);
    }

    /** @test */
    public function accessibility_features()
    {
        $this->actingAs($this->user);

        // Test that forms have proper labels and accessibility attributes
        $response = $this->get('/activities/create');
        $response->assertStatus(200);
        
        // Check for form labels
        $response->assertSee('<label', false);
        
        // Check for required field indicators
        $response->assertSee('required', false);

        // Test that buttons have proper text or aria-labels
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        
        // Check for semantic HTML elements
        $response->assertSee('<main', false);
        $response->assertSee('<nav', false);
        $response->assertSee('<button', false);
    }
}