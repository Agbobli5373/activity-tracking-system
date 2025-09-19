<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Activity;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Request $mockRequest;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'employee_id' => 'EMP001',
            'role' => 'member'
        ]);

        // Create a mock request
        $this->mockRequest = Request::create('/test', 'POST', [
            'test_field' => 'test_value',
            'password' => 'secret123'
        ]);
        $this->mockRequest->setUserResolver(function () {
            return $this->user;
        });
        $this->mockRequest->server->set('REMOTE_ADDR', '192.168.1.1');
        $this->mockRequest->server->set('HTTP_USER_AGENT', 'Test Browser');
    }

    public function test_log_creates_audit_entry_with_all_parameters()
    {
        Auth::login($this->user);

        $auditLog = AuditService::log(
            'test_action',
            'App\Models\Activity',
            123,
            ['old' => 'value'],
            ['new' => 'value'],
            $this->mockRequest
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('test_action', $auditLog->action);
        $this->assertEquals('App\Models\Activity', $auditLog->model_type);
        $this->assertEquals(123, $auditLog->model_id);
        $this->assertEquals(['old' => 'value'], $auditLog->old_values);
        $this->assertEquals(['new' => 'value'], $auditLog->new_values);
        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertEquals('Test Browser', $auditLog->user_agent);
        $this->assertEquals('http://localhost/test', $auditLog->url);
        $this->assertEquals('POST', $auditLog->method);
    }

    public function test_log_creates_audit_entry_with_minimal_parameters()
    {
        Auth::login($this->user);

        $auditLog = AuditService::log('simple_action');

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('simple_action', $auditLog->action);
        $this->assertNull($auditLog->model_type);
        $this->assertNull($auditLog->model_id);
        $this->assertNull($auditLog->old_values);
        $this->assertNull($auditLog->new_values);
    }

    public function test_log_sanitizes_request_data()
    {
        Auth::login($this->user);

        $auditLog = AuditService::log('test_action', null, null, null, null, $this->mockRequest);

        $requestData = $auditLog->request_data;
        $this->assertEquals('test_value', $requestData['test_field']);
        $this->assertEquals('[REDACTED]', $requestData['password']);
    }

    public function test_log_auth_successful_login()
    {
        $auditLog = AuditService::logAuth('login_success', $this->user->id, $this->mockRequest);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('login_success', $auditLog->action);
        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertEquals('Test Browser', $auditLog->user_agent);
    }

    public function test_log_auth_failed_login()
    {
        $auditLog = AuditService::logAuth('login_failed', null, $this->mockRequest);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertNull($auditLog->user_id);
        $this->assertEquals('login_failed', $auditLog->action);
        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertEquals('Test Browser', $auditLog->user_agent);
    }

    public function test_log_auth_with_authenticated_user()
    {
        Auth::login($this->user);

        $auditLog = AuditService::logAuth('logout', null, $this->mockRequest);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('logout', $auditLog->action);
    }

    public function test_log_model_change_with_model()
    {
        Auth::login($this->user);

        $activity = Activity::factory()->create(['created_by' => $this->user->id]);
        $oldValues = ['status' => 'pending'];

        $auditLog = AuditService::logModelChange('activity_updated', $activity, $oldValues, $this->mockRequest);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('activity_updated', $auditLog->action);
        $this->assertEquals(get_class($activity), $auditLog->model_type);
        $this->assertEquals($activity->id, $auditLog->model_id);
        $this->assertEquals($oldValues, $auditLog->old_values);
        $this->assertIsArray($auditLog->new_values);
        $this->assertArrayHasKey('id', $auditLog->new_values);
        $this->assertEquals($activity->id, $auditLog->new_values['id']);
    }

    public function test_get_model_audit_logs()
    {
        Auth::login($this->user);

        $activity = Activity::factory()->create(['created_by' => $this->user->id]);

        // Create some audit logs for the activity
        AuditService::logModelChange('activity_created', $activity);
        AuditService::logModelChange('activity_updated', $activity);
        AuditService::logModelChange('activity_deleted', $activity);

        // Create audit log for different model (should not be included)
        $otherActivity = Activity::factory()->create(['created_by' => $this->user->id]);
        AuditService::logModelChange('activity_created', $otherActivity);

        $auditLogs = AuditService::getModelAuditLogs($activity);

        $this->assertCount(3, $auditLogs);
        foreach ($auditLogs as $log) {
            $this->assertEquals(get_class($activity), $log->model_type);
            $this->assertEquals($activity->id, $log->model_id);
        }

        // Check ordering (should be desc by created_at)
        $this->assertEquals('activity_deleted', $auditLogs->first()->action);
        $this->assertEquals('activity_created', $auditLogs->last()->action);
    }

    public function test_get_user_activity_logs()
    {
        $otherUser = User::factory()->create();
        
        Auth::login($this->user);
        AuditService::log('user_action_1');
        AuditService::log('user_action_2');

        Auth::login($otherUser);
        AuditService::log('other_user_action');

        $userLogs = AuditService::getUserActivityLogs($this->user->id);

        $this->assertCount(2, $userLogs);
        foreach ($userLogs as $log) {
            $this->assertEquals($this->user->id, $log->user_id);
        }
    }

    public function test_get_security_logs()
    {
        Auth::login($this->user);

        // Create security-related logs
        AuditService::logAuth('login_success', $this->user->id);
        AuditService::logAuth('login_failed', null);
        AuditService::logAuth('logout', $this->user->id);
        AuditService::log('password_change');
        AuditService::log('unauthorized_access');

        // Create non-security log (should not be included)
        AuditService::log('regular_action');

        $securityLogs = AuditService::getSecurityLogs();

        $this->assertCount(5, $securityLogs);
        
        $actions = $securityLogs->pluck('action')->toArray();
        $this->assertContains('login_success', $actions);
        $this->assertContains('login_failed', $actions);
        $this->assertContains('logout', $actions);
        $this->assertContains('password_change', $actions);
        $this->assertContains('unauthorized_access', $actions);
        $this->assertNotContains('regular_action', $actions);
    }

    public function test_get_security_logs_with_date_filter()
    {
        Auth::login($this->user);

        // Create old log (should not be included with default 7 days)
        $oldLog = AuditService::logAuth('login_success', $this->user->id);
        $oldLog->created_at = Carbon::now()->subDays(10);
        $oldLog->save();

        // Create recent log (should be included)
        AuditService::logAuth('login_failed', null);

        $securityLogs = AuditService::getSecurityLogs(7);

        $this->assertCount(1, $securityLogs);
        $this->assertEquals('login_failed', $securityLogs->first()->action);
    }

    public function test_cleanup_removes_old_audit_logs()
    {
        Auth::login($this->user);

        // Create old logs
        $oldLog1 = AuditService::log('old_action_1');
        $oldLog1->created_at = Carbon::now()->subDays(400);
        $oldLog1->save();

        $oldLog2 = AuditService::log('old_action_2');
        $oldLog2->created_at = Carbon::now()->subDays(370);
        $oldLog2->save();

        // Create recent log (should not be deleted)
        $recentLog = AuditService::log('recent_action');

        $deletedCount = AuditService::cleanup(365);

        $this->assertEquals(2, $deletedCount);
        $this->assertFalse(AuditLog::where('id', $oldLog1->id)->exists());
        $this->assertFalse(AuditLog::where('id', $oldLog2->id)->exists());
        $this->assertTrue(AuditLog::where('id', $recentLog->id)->exists());
    }

    public function test_sanitize_request_data_removes_sensitive_fields()
    {
        $sensitiveData = [
            'username' => 'testuser',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'current_password' => 'oldsecret',
            'new_password' => 'newsecret',
            '_token' => 'csrf_token',
            'api_key' => 'api_secret',
            'secret' => 'secret_value',
            'token' => 'auth_token',
            'normal_field' => 'normal_value'
        ];

        $request = Request::create('/test', 'POST', $sensitiveData);
        Auth::login($this->user);

        $auditLog = AuditService::log('test_action', null, null, null, null, $request);

        $requestData = $auditLog->request_data;
        
        $this->assertEquals('testuser', $requestData['username']);
        $this->assertEquals('normal_value', $requestData['normal_field']);
        
        // All sensitive fields should be redacted
        $this->assertEquals('[REDACTED]', $requestData['password']);
        $this->assertEquals('[REDACTED]', $requestData['password_confirmation']);
        $this->assertEquals('[REDACTED]', $requestData['current_password']);
        $this->assertEquals('[REDACTED]', $requestData['new_password']);
        $this->assertEquals('[REDACTED]', $requestData['_token']);
        $this->assertEquals('[REDACTED]', $requestData['api_key']);
        $this->assertEquals('[REDACTED]', $requestData['secret']);
        $this->assertEquals('[REDACTED]', $requestData['token']);
    }

    public function test_transaction_success()
    {
        Auth::login($this->user);

        $result = AuditService::transaction(function () {
            return 'success_result';
        }, 'test_transaction', 'Test transaction description');

        $this->assertEquals('success_result', $result);

        // Check that success audit log was created
        $auditLog = AuditLog::where('action', 'test_transaction_success')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('Test transaction description', $auditLog->new_values['description']);
        $this->assertEquals('success', $auditLog->new_values['result']);
    }

    public function test_transaction_failure()
    {
        Auth::login($this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        try {
            AuditService::transaction(function () {
                throw new \Exception('Test exception');
            }, 'test_transaction', 'Test transaction description');
        } catch (\Exception $e) {
            // Check that failure audit log was created
            $auditLog = AuditLog::where('action', 'test_transaction_failed')->first();
            $this->assertNotNull($auditLog);
            $this->assertEquals('Test transaction description', $auditLog->new_values['description']);
            $this->assertEquals('Test exception', $auditLog->new_values['error']);
            $this->assertEquals('failed', $auditLog->new_values['result']);

            throw $e;
        }
    }

    public function test_log_without_authenticated_user()
    {
        // Ensure no user is authenticated
        Auth::logout();

        $auditLog = AuditService::log('anonymous_action');

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertNull($auditLog->user_id);
        $this->assertEquals('anonymous_action', $auditLog->action);
    }

    public function test_log_uses_current_request_when_none_provided()
    {
        Auth::login($this->user);

        // Set up the current request
        $this->app->instance('request', $this->mockRequest);

        $auditLog = AuditService::log('test_action');

        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertEquals('Test Browser', $auditLog->user_agent);
        $this->assertEquals('http://localhost/test', $auditLog->url);
        $this->assertEquals('POST', $auditLog->method);
    }

    public function test_get_model_audit_logs_with_limit()
    {
        Auth::login($this->user);

        $activity = Activity::factory()->create(['created_by' => $this->user->id]);

        // Create more logs than the default limit
        for ($i = 0; $i < 60; $i++) {
            AuditService::logModelChange("activity_action_{$i}", $activity);
        }

        $auditLogs = AuditService::getModelAuditLogs($activity, 25);

        $this->assertCount(25, $auditLogs);
    }

    public function test_get_user_activity_logs_with_limit()
    {
        Auth::login($this->user);

        // Create more logs than the default limit
        for ($i = 0; $i < 120; $i++) {
            AuditService::log("user_action_{$i}");
        }

        $userLogs = AuditService::getUserActivityLogs($this->user->id, 50);

        $this->assertCount(50, $userLogs);
    }
}