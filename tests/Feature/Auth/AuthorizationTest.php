<?php

namespace Tests\Feature\Auth;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $member;
    protected User $otherMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'department' => 'IT']);
        $this->supervisor = User::factory()->create(['role' => 'supervisor', 'department' => 'IT']);
        $this->member = User::factory()->create(['role' => 'member', 'department' => 'IT']);
        $this->otherMember = User::factory()->create(['role' => 'member', 'department' => 'HR']);
    }

    /** @test */
    public function admin_can_access_all_activities()
    {
        $activity = Activity::factory()->create(['created_by' => $this->member->id]);

        $this->actingAs($this->admin);

        $response = $this->get(route('activities.show', $activity));
        $response->assertStatus(200);

        $response = $this->get(route('activities.edit', $activity));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_any_activity()
    {
        $activity = Activity::factory()->create(['created_by' => $this->member->id]);

        $this->actingAs($this->admin);

        $response = $this->put(route('activities.update', $activity), [
            'name' => 'Updated by Admin',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('activities.show', $activity));
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'name' => 'Updated by Admin',
        ]);
    }

    /** @test */
    public function admin_can_delete_any_activity()
    {
        $activity = Activity::factory()->create(['created_by' => $this->member->id]);

        $this->actingAs($this->admin);

        $response = $this->delete(route('activities.destroy', $activity));
        $response->assertRedirect(route('activities.index'));
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    /** @test */
    public function supervisor_can_view_department_activities()
    {
        $departmentActivity = Activity::factory()->create(['created_by' => $this->member->id]);
        $otherDepartmentActivity = Activity::factory()->create(['created_by' => $this->otherMember->id]);

        $this->actingAs($this->supervisor);

        // Can view same department activity
        $response = $this->get(route('activities.show', $departmentActivity));
        $response->assertStatus(200);

        // Cannot view other department activity
        $response = $this->get(route('activities.show', $otherDepartmentActivity));
        $response->assertStatus(403);
    }

    /** @test */
    public function supervisor_can_update_department_activities()
    {
        $departmentActivity = Activity::factory()->create(['created_by' => $this->member->id]);

        $this->actingAs($this->supervisor);

        $response = $this->put(route('activities.update', $departmentActivity), [
            'name' => 'Updated by Supervisor',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('activities.show', $departmentActivity));
        $this->assertDatabaseHas('activities', [
            'id' => $departmentActivity->id,
            'name' => 'Updated by Supervisor',
        ]);
    }

    /** @test */
    public function supervisor_cannot_update_other_department_activities()
    {
        $otherDepartmentActivity = Activity::factory()->create(['created_by' => $this->otherMember->id]);

        $this->actingAs($this->supervisor);

        $response = $this->put(route('activities.update', $otherDepartmentActivity), [
            'name' => 'Should not update',
            'description' => 'Should not update',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function member_can_view_own_activities()
    {
        $ownActivity = Activity::factory()->create(['created_by' => $this->member->id]);

        $this->actingAs($this->member);

        $response = $this->get(route('activities.show', $ownActivity));
        $response->assertStatus(200);
    }

    /** @test */
    public function member_can_update_own_activities()
    {
        $ownActivity = Activity::factory()->create(['created_by' => $this->member->id]);

        $this->actingAs($this->member);

        $response = $this->put(route('activities.update', $ownActivity), [
            'name' => 'Updated by Owner',
            'description' => 'Updated description',
        ]);

        $response->assertRedirect(route('activities.show', $ownActivity));
        $this->assertDatabaseHas('activities', [
            'id' => $ownActivity->id,
            'name' => 'Updated by Owner',
        ]);
    }

    /** @test */
    public function member_cannot_view_others_activities()
    {
        $otherActivity = Activity::factory()->create(['created_by' => $this->otherMember->id]);

        $this->actingAs($this->member);

        $response = $this->get(route('activities.show', $otherActivity));
        $response->assertStatus(403);
    }

    /** @test */
    public function member_cannot_update_others_activities()
    {
        $otherActivity = Activity::factory()->create(['created_by' => $this->otherMember->id]);

        $this->actingAs($this->member);

        $response = $this->put(route('activities.update', $otherActivity), [
            'name' => 'Should not update',
            'description' => 'Should not update',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function member_can_delete_own_pending_activities()
    {
        $ownActivity = Activity::factory()->create([
            'created_by' => $this->member->id,
            'status' => 'pending'
        ]);

        $this->actingAs($this->member);

        $response = $this->delete(route('activities.destroy', $ownActivity));
        $response->assertRedirect(route('activities.index'));
        $this->assertDatabaseMissing('activities', ['id' => $ownActivity->id]);
    }

    /** @test */
    public function member_cannot_delete_completed_activities()
    {
        $ownActivity = Activity::factory()->create([
            'created_by' => $this->member->id,
            'status' => 'done'
        ]);

        $this->actingAs($this->member);

        $response = $this->delete(route('activities.destroy', $ownActivity));
        $response->assertStatus(403);
        $this->assertDatabaseHas('activities', ['id' => $ownActivity->id]);
    }

    /** @test */
    public function users_can_update_status_of_assigned_activities()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->otherMember->id,
            'assigned_to' => $this->member->id,
            'status' => 'pending'
        ]);

        $this->actingAs($this->member);

        $response = $this->post(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => 'Completed the assigned task',
        ]);

        $response->assertRedirect(route('activities.show', $activity));
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'done',
        ]);
    }

    /** @test */
    public function users_cannot_update_status_of_unrelated_activities()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->otherMember->id,
            'assigned_to' => $this->otherMember->id,
            'status' => 'pending'
        ]);

        $this->actingAs($this->member);

        $response = $this->post(route('activities.update-status', $activity), [
            'status' => 'done',
            'remarks' => 'Should not be allowed',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_reports()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function supervisor_can_access_reports()
    {
        $this->actingAs($this->supervisor);

        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function member_cannot_access_reports()
    {
        $this->actingAs($this->member);

        $response = $this->get(route('reports.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_audit_logs()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('audit.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function non_admin_cannot_access_audit_logs()
    {
        $this->actingAs($this->supervisor);

        $response = $this->get(route('audit.index'));
        $response->assertStatus(403);

        $this->actingAs($this->member);

        $response = $this->get(route('audit.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function role_based_navigation_is_displayed_correctly()
    {
        // Admin should see all navigation items
        $this->actingAs($this->admin);
        $response = $this->get('/dashboard');
        $response->assertSee('Reports');
        $response->assertSee('Audit Logs');

        // Supervisor should see reports but not audit logs
        $this->actingAs($this->supervisor);
        $response = $this->get('/dashboard');
        $response->assertSee('Reports');
        $response->assertDontSee('Audit Logs');

        // Member should not see reports or audit logs
        $this->actingAs($this->member);
        $response = $this->get('/dashboard');
        $response->assertDontSee('Reports');
        $response->assertDontSee('Audit Logs');
    }
}