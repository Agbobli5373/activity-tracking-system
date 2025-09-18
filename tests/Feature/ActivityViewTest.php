<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function authenticated_user_can_view_activities_index()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('activities.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('activities.index');
        $response->assertSee('Activities');
        $response->assertSee('New Activity');
    }

    /** @test */
    public function authenticated_user_can_view_create_activity_form()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('activities.create'));
        
        $response->assertStatus(200);
        $response->assertViewIs('activities.create');
        $response->assertSee('Create New Activity');
        $response->assertSee('Activity Name');
        $response->assertSee('Description');
        $response->assertSee('Priority');
    }

    /** @test */
    public function activities_index_displays_existing_activities()
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'name' => 'Test Activity',
            'description' => 'Test Description',
            'created_by' => $user->id,
        ]);
        
        $response = $this->actingAs($user)->get(route('activities.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Test Activity');
        $response->assertSee('Test Description');
    }

    /** @test */
    public function activities_index_shows_filters()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('activities.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Search');
        $response->assertSee('Status');
        $response->assertSee('Date');
        $response->assertSee('Creator');
    }

    /** @test */
    public function create_form_shows_all_required_fields()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get(route('activities.create'));
        
        $response->assertStatus(200);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('name="priority"', false);
        $response->assertSee('name="assigned_to"', false);
        $response->assertSee('name="due_date"', false);
    }
}