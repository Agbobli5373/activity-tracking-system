<?php

namespace Tests\Feature\Audit;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin',
            'department' => 'IT'
        ]);
    }

    /** @test */
    public function it_logs_authentication_events()
    {
        // Test login success
        $response = $this->post('/login', [
            'login' => $this->user->employee_id,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'login_success',
        ]);

        // Test logout
        $this->actingAs($this->user);
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'logout',
        ]);
    }

    /** @test */
    public function it_logs_failed_login_attempts()
    {
        $response = $this->post('/login', [
            'login' => $this->user->employee_id,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['login']);
        
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null, // Failed login attempts don't have user_id
            'action' => 'login_failed',
        ]);
    }

    /** @test */
    public function it_logs_activity_creation()
    {
        $this->actingAs($this->user);

        $activityData = [
            'name' => 'Test Activity',
            'description' => 'Test Description',
            'priority' => 'high',
            'assigned_to' => $this->user->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ];

        $response = $this->post('/activities', $activityData);

        $activity = Activity::where('name', 'Test Activity')->first();
        $response->assertRedirect("/activities/{$activity->id}");

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'activity_created',
            'model_type' => Activity::class,
            'model_id' => $activity->id,
        ]);
    }

    /** @test */
    public function it_logs_activity_updates()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'assigned_to' => $this->user->id,
        ]);

        $updateData = [
            'name' => 'Updated Activity Name',
            'description' => $activity->description,
            'priority' => 'medium',
            'assigned_to' => $this->user->id,
            'due_date' => $activity->due_date ? $activity->due_date->format('Y-m-d') : now()->addDays(7)->format('Y-m-d'),
        ];

        $response = $this->put("/activities/{$activity->id}", $updateData);

        $response->assertRedirect("/activities/{$activity->id}");

        $auditLog = AuditLog::where([
            'action' => 'activity_updated',
            'model_type' => Activity::class,
            'model_id' => $activity->id,
        ])->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertNotNull($auditLog->old_values);
        $this->assertNotNull($auditLog->new_values);
    }

    /** @test */
    public function it_logs_activity_status_changes()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'assigned_to' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/activities/{$activity->id}/status", [
            'status' => 'done',
            'remarks' => 'Completed this activity successfully',
        ]);

        $response->assertJson(['success' => true]);

        // Check if activity update was logged
        $this->assertDatabaseHas('activity_updates', [
            'activity_id' => $activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Completed this activity successfully',
        ]);
    }

    /** @test */
    public function it_captures_request_metadata()
    {
        $this->actingAs($this->user);

        $response = $this->post('/activities', [
            'name' => 'Test Activity',
            'description' => 'Test Description',
            'priority' => 'high',
            'assigned_to' => $this->user->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $auditLog = AuditLog::where('action', 'activity_created')->first();

        $this->assertNotNull($auditLog->ip_address);
        $this->assertNotNull($auditLog->user_agent);
        $this->assertNotNull($auditLog->url);
        $this->assertEquals('POST', $auditLog->method);
        $this->assertNotNull($auditLog->session_id);
    }

    /** @test */
    public function it_sanitizes_sensitive_data()
    {
        $this->actingAs($this->user);

        $request = Request::create('/test', 'POST', [
            'name' => 'Test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            '_token' => 'csrf-token',
            'api_key' => 'secret-key',
        ]);
        
        // Set up session for the request
        $request->setLaravelSession($this->app['session.store']);

        AuditService::log('test_action', null, null, null, null, $request);

        $auditLog = AuditLog::where('action', 'test_action')->first();
        $requestData = $auditLog->request_data;

        $this->assertEquals('[REDACTED]', $requestData['password']);
        $this->assertEquals('[REDACTED]', $requestData['password_confirmation']);
        $this->assertEquals('[REDACTED]', $requestData['_token']);
        $this->assertEquals('[REDACTED]', $requestData['api_key']);
        $this->assertEquals('Test', $requestData['name']);
    }

    /** @test */
    public function it_provides_audit_trail_for_models()
    {
        $this->actingAs($this->user);

        $activity = Activity::factory()->create([
            'created_by' => $this->user->id,
            'assigned_to' => $this->user->id,
        ]);

        // Create a proper request for the audit service
        $request = Request::create('/test', 'POST');
        $request->setLaravelSession($this->app['session.store']);

        // Log some changes
        AuditService::logModelChange('activity_created', $activity, null, $request);
        AuditService::logModelChange('activity_updated', $activity, ['name' => 'Old Name'], $request);

        $auditLogs = AuditService::getModelAuditLogs($activity);

        $this->assertCount(2, $auditLogs);
        
        // Check that both actions exist (order may vary)
        $actions = $auditLogs->pluck('action')->toArray();
        $this->assertContains('activity_created', $actions);
        $this->assertContains('activity_updated', $actions);
    }

    /** @test */
    public function it_gets_user_activity_logs()
    {
        $this->actingAs($this->user);

        $request = Request::create('/test', 'POST');
        $request->setLaravelSession($this->app['session.store']);

        // Create some audit logs
        AuditService::logAuth('login_success', $this->user->id, $request);
        AuditService::log('dashboard_viewed', null, null, null, null, $request);
        AuditService::log('report_generated', null, null, null, null, $request);

        $userLogs = AuditService::getUserActivityLogs($this->user->id);

        $this->assertCount(3, $userLogs);
        $this->assertEquals($this->user->id, $userLogs->first()->user_id);
    }

    /** @test */
    public function it_gets_security_logs()
    {
        $request = Request::create('/test', 'POST');
        $request->setLaravelSession($this->app['session.store']);

        // Create security-related logs
        AuditService::logAuth('login_success', $this->user->id, $request);
        AuditService::logAuth('login_failed', $this->user->id, $request);
        AuditService::logAuth('logout', $this->user->id, $request);
        AuditService::log('unauthorized_access', null, null, null, null, $request);

        $securityLogs = AuditService::getSecurityLogs();

        $this->assertCount(4, $securityLogs);
        
        $actions = $securityLogs->pluck('action')->toArray();
        $this->assertContains('login_success', $actions);
        $this->assertContains('login_failed', $actions);
        $this->assertContains('logout', $actions);
        $this->assertContains('unauthorized_access', $actions);
    }

    /** @test */
    public function it_handles_database_transactions_with_audit_logging()
    {
        $this->actingAs($this->user);

        // Create a proper request with session
        $request = Request::create('/test', 'POST');
        $request->setLaravelSession($this->app['session.store']);
        
        // Mock the request for the transaction
        $this->app->instance('request', $request);

        $result = AuditService::transaction(function () {
            return 'success';
        }, 'test_transaction', 'Testing transaction logging');

        $this->assertEquals('success', $result);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test_transaction_success',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_logs_failed_transactions()
    {
        $this->actingAs($this->user);

        // Create a proper request with session
        $request = Request::create('/test', 'POST');
        $request->setLaravelSession($this->app['session.store']);
        
        // Mock the request for the transaction
        $this->app->instance('request', $request);

        try {
            AuditService::transaction(function () {
                throw new \Exception('Test error');
            }, 'test_transaction', 'Testing failed transaction');
        } catch (\Exception $e) {
            $this->assertDatabaseHas('audit_logs', [
                'action' => 'test_transaction_failed',
                'user_id' => $this->user->id,
            ]);
            
            return; // Test passed
        }
        
        $this->fail('Expected exception was not thrown');
    }

    /** @test */
    public function it_provides_human_readable_action_descriptions()
    {
        $auditLog = new AuditLog(['action' => 'login_success']);
        $this->assertEquals('Successfully logged in', $auditLog->action_description);

        $auditLog = new AuditLog(['action' => 'activity_created']);
        $this->assertEquals('Created new activity', $auditLog->action_description);

        $auditLog = new AuditLog(['action' => 'custom_action']);
        $this->assertEquals('Custom action', $auditLog->action_description);
    }

    /** @test */
    public function it_provides_changes_summary()
    {
        $auditLog = new AuditLog([
            'old_values' => ['name' => 'Old Name', 'status' => 'pending'],
            'new_values' => ['name' => 'New Name', 'status' => 'done'],
        ]);

        $summary = $auditLog->changes_summary;
        
        $this->assertStringContainsString("name: 'Old Name' → 'New Name'", $summary);
        $this->assertStringContainsString("status: 'pending' → 'done'", $summary);
    }

    /** @test */
    public function it_cleans_up_old_audit_logs()
    {
        // Create old audit logs
        AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'old_action',
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subDays(400),
        ]);

        // Create recent audit log
        AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'recent_action',
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subDays(30),
        ]);

        $deletedCount = AuditService::cleanup(365);

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'old_action']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'recent_action']);
    }
}