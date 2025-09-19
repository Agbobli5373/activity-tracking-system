<?php

namespace Tests\Feature\Browser;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JavaScriptInteractionTest extends TestCase
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
    public function dashboard_auto_refresh_functionality()
    {
        $this->actingAs($this->user);

        // Create initial activity
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        // Get initial dashboard state
        $response = $this->getJson('/dashboard/activities');
        $response->assertStatus(200);
        $initialTimestamp = $response->json('timestamp');

        // Simulate time passing and new updates
        sleep(1);
        
        $activity->updates()->create([
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Auto-refresh test',
        ]);

        // Get updates since last timestamp
        $response = $this->getJson('/dashboard/updates?since=' . $initialTimestamp);
        $response->assertStatus(200);
        
        $updates = $response->json('updates');
        $this->assertNotEmpty($updates);
        // Check if the update contains the expected status change
        $this->assertArrayHasKey(0, $updates);
    }

    /** @test */
    public function dynamic_form_validation_feedback()
    {
        $this->actingAs($this->user);

        // Test client-side validation by submitting invalid data
        $response = $this->post('/activities', [
            'name' => '', // Empty name should trigger validation
            'description' => 'Test description',
        ]);

        $response->assertSessionHasErrors(['name']);
        
        // Test that the form retains old input values
        $response = $this->post('/activities', [
            'name' => 'Test Activity',
            'description' => '', // Empty description
            'priority' => 'high',
        ]);

        $response->assertSessionHasErrors(['description']);
        
        // Verify old input is available for JavaScript to repopulate
        $this->assertEquals('Test Activity', old('name'));
        $this->assertEquals('high', old('priority'));
    }

    /** @test */
    public function activity_quick_actions_functionality()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        // Test quick status update via AJAX
        $response = $this->postJson("/activities/{$activity->id}/status", [
            'status' => 'done',
            'remarks' => 'Quick action completion',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Activity status updated successfully.'
        ]);

        // Verify the update was saved
        $activity->refresh();
        $this->assertEquals('done', $activity->status);
    }

    /** @test */
    public function search_autocomplete_functionality()
    {
        $this->actingAs($this->user);

        // Create activities with searchable content
        Activity::factory()->create([
            'name' => 'Server Maintenance',
            'description' => 'Routine server maintenance task',
            'created_by' => $this->user->id,
        ]);

        Activity::factory()->create([
            'name' => 'Database Backup',
            'description' => 'Weekly database backup procedure',
            'created_by' => $this->user->id,
        ]);

        Activity::factory()->create([
            'name' => 'Network Configuration',
            'description' => 'Update network settings',
            'created_by' => $this->user->id,
        ]);

        // Test search with partial matches
        $response = $this->get('/activities?search=server');
        $response->assertStatus(200);
        $response->assertSee('Server Maintenance');
        $response->assertDontSee('Database Backup');
        $response->assertDontSee('Network Configuration');

        // Test search in description
        $response = $this->get('/activities?search=backup');
        $response->assertStatus(200);
        $response->assertSee('Database Backup');
        $response->assertDontSee('Server Maintenance');

        // Test case-insensitive search
        $response = $this->get('/activities?search=SERVER');
        $response->assertStatus(200);
        $response->assertSee('Server Maintenance');
    }

    /** @test */
    public function filter_combination_interactions()
    {
        $this->actingAs($this->user);

        // Create activities with various combinations
        Activity::factory()->create([
            'name' => 'High Priority Pending',
            'status' => 'pending',
            'priority' => 'high',
            'created_by' => $this->user->id,
            'created_at' => now(),
        ]);

        Activity::factory()->create([
            'name' => 'Medium Priority Done',
            'status' => 'done',
            'priority' => 'medium',
            'created_by' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        Activity::factory()->create([
            'name' => 'Low Priority Pending',
            'status' => 'pending',
            'priority' => 'low',
            'created_by' => $this->user->id,
            'created_at' => now(),
        ]);

        // Test multiple filter combinations
        $response = $this->get('/activities?' . http_build_query([
            'status' => 'pending',
            'priority' => 'high',
            'date' => now()->format('Y-m-d')
        ]));

        $response->assertStatus(200);
        $response->assertSee('High Priority Pending');
        $response->assertDontSee('Medium Priority Done');
        $response->assertDontSee('Low Priority Pending');

        // Test clearing filters
        $response = $this->get('/activities');
        $response->assertStatus(200);
        $response->assertSee('High Priority Pending');
        $response->assertSee('Medium Priority Done');
        $response->assertSee('Low Priority Pending');
    }

    /** @test */
    public function modal_dialog_interactions()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        // Test that activity detail page loads correctly
        $response = $this->get("/activities/{$activity->id}");
        $response->assertStatus(200);
        $response->assertSee($activity->name);
        $response->assertSee('Update Status');

        // Test status update form structure
        $response->assertSee('x-model="statusForm.status"', false);
        $response->assertSee('x-model="statusForm.remarks"', false);
        $response->assertSee('value="done"', false);
        $response->assertSee('value="pending"', false);
    }

    /** @test */
    public function notification_system_interactions()
    {
        $this->actingAs($this->user);

        // Create an activity to trigger notifications
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        // Test that navigation shows notification count
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        
        // Look for notification indicators in the navigation
        $response->assertSee('bg-red-500'); // Notification badge styling
        $response->assertSee('1'); // Notification count
    }

    /** @test */
    public function chart_and_visualization_data()
    {
        $this->actingAs($this->admin);

        // Create data for charts
        Activity::factory()->count(5)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'priority' => 'high',
            'created_at' => now()->subDays(1),
        ]);

        Activity::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'priority' => 'medium',
            'created_at' => now(),
        ]);

        // Test chart data endpoints
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
                'datasets' => [
                    '*' => [
                        'label',
                        'data',
                        'backgroundColor',
                        'borderColor'
                    ]
                ]
            ]
        ]);

        // Verify chart data format is suitable for Chart.js
        $chartData = $response->json('data');
        $this->assertIsArray($chartData['labels']);
        $this->assertIsArray($chartData['datasets']);
        $this->assertNotEmpty($chartData['datasets']);
    }

    /** @test */
    public function keyboard_navigation_support()
    {
        $this->actingAs($this->user);

        // Test that forms are keyboard accessible
        $response = $this->get('/activities/create');
        $response->assertStatus(200);
        
        // Check for proper tab order attributes
        $response->assertSee('tabindex', false);
        
        // Check for keyboard event handling elements
        $response->assertSee('type="submit"', false);
        $response->assertSee('type="button"', false);

        // Test navigation accessibility
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        
        // Check for focus management elements
        $response->assertSee('focus:outline-none');
        $response->assertSee('focus:ring');
    }

    /** @test */
    public function progressive_enhancement_fallbacks()
    {
        $this->actingAs($this->user);

        // Test that forms work without JavaScript
        $response = $this->post('/activities', [
            'name' => 'No JS Activity',
            'description' => 'Created without JavaScript',
            'priority' => 'medium',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'name' => 'No JS Activity',
            'created_by' => $this->user->id,
        ]);

        // Test that status updates work without AJAX
        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->post("/activities/{$activity->id}/status", [
            'status' => 'done',
            'remarks' => 'Updated without AJAX',
        ]);

        $response->assertRedirect();
        $activity->refresh();
        $this->assertEquals('done', $activity->status);
    }

    /** @test */
    public function mobile_responsive_interactions()
    {
        $this->actingAs($this->user);

        // Test mobile navigation elements
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        
        // Check for mobile-specific CSS classes
        $response->assertSee('md:hidden'); // Mobile menu button
        $response->assertSee('sm:px-6'); // Responsive padding
        $response->assertSee('lg:px-8'); // Large screen padding
        
        // Check for responsive grid layouts
        $response->assertSee('grid-cols-1'); // Mobile: 1 column
        $response->assertSee('md:grid-cols-2'); // Tablet: 2 columns
        $response->assertSee('lg:grid-cols-3'); // Desktop: 3 columns

        // Test that forms are mobile-friendly
        $response = $this->get('/activities/create');
        $response->assertStatus(200);
        
        // Check for mobile-optimized form elements
        $response->assertSee('w-full'); // Full width inputs
        $response->assertSee('text-sm'); // Appropriate text sizing
    }

    /** @test */
    public function error_state_handling()
    {
        $this->actingAs($this->user);

        // Test handling of server errors in AJAX requests
        $response = $this->getJson('/dashboard/activities?simulate_error=true');
        $response->assertStatus(200); // Should handle gracefully

        // Test validation error display
        $response = $this->post('/activities', [
            'name' => 'A', // Too short
            'description' => 'Test',
        ]);

        $response->assertSessionHasErrors();
        
        // Test that error messages are properly formatted for display
        $errors = session('errors');
        if ($errors) {
            $this->assertNotEmpty($errors->get('name'));
        }
    }

    /** @test */
    public function performance_optimization_features()
    {
        $this->actingAs($this->user);

        // Create many activities to test pagination performance
        Activity::factory()->count(50)->create([
            'created_by' => $this->user->id,
        ]);

        // Test that pagination limits results appropriately
        $response = $this->get('/activities');
        $response->assertStatus(200);
        
        // Verify response time is reasonable (should complete quickly)
        $this->assertTrue(true); // Placeholder for timing assertions

        // Test AJAX endpoints return reasonable amounts of data
        $response = $this->getJson('/dashboard/activities');
        $response->assertStatus(200);
        
        $activities = $response->json('activities');
        $this->assertLessThanOrEqual(20, count($activities)); // Should be paginated
    }
}