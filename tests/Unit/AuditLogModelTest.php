<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AuditLogModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'employee_id' => 'EMP001',
            'role' => 'member'
        ]);
    }

    public function test_audit_log_can_be_created_with_required_fields()
    {
        $auditLog = AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'test_action',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'url' => 'http://localhost/test',
            'method' => 'POST',
        ]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('test_action', $auditLog->action);
        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertEquals('Test Browser', $auditLog->user_agent);
        $this->assertEquals('http://localhost/test', $auditLog->url);
        $this->assertEquals('POST', $auditLog->method);
    }

    public function test_audit_log_fillable_attributes()
    {
        $fillable = [
            'user_id',
            'action',
            'model_type',
            'model_id',
            'old_values',
            'new_values',
            'ip_address',
            'user_agent',
            'url',
            'method',
            'request_data',
            'session_id',
            'created_at',
        ];

        $auditLog = new AuditLog();
        $this->assertEquals($fillable, $auditLog->getFillable());
    }

    public function test_audit_log_casts()
    {
        $auditLog = AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'test_action',
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'done'],
            'request_data' => ['field' => 'value'],
            'ip_address' => '192.168.1.1',
            'created_at' => now(),
        ]);

        $this->assertIsArray($auditLog->old_values);
        $this->assertIsArray($auditLog->new_values);
        $this->assertIsArray($auditLog->request_data);
        $this->assertInstanceOf(Carbon::class, $auditLog->created_at);
        
        // Since timestamps is false, updated_at should be null
        $this->assertNull($auditLog->updated_at);
    }

    public function test_audit_log_user_relationship()
    {
        $auditLog = AuditLog::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $auditLog->user);
        $this->assertEquals($this->user->id, $auditLog->user->id);
        $this->assertEquals($this->user->name, $auditLog->user->name);
    }

    public function test_audit_log_user_relationship_can_be_null()
    {
        $auditLog = AuditLog::factory()->create([
            'user_id' => null,
        ]);

        $this->assertNull($auditLog->user);
    }

    public function test_audit_log_scopes_by_action()
    {
        $loginLog = AuditLog::factory()->create([
            'user_id' => $this->user->id,
            'action' => 'login',
        ]);

        $logoutLog = AuditLog::factory()->create([
            'user_id' => $this->user->id,
            'action' => 'logout',
        ]);

        $loginLogs = AuditLog::byAction('login')->get();
        $logoutLogs = AuditLog::byAction('logout')->get();

        $this->assertCount(1, $loginLogs);
        $this->assertCount(1, $logoutLogs);
        $this->assertEquals($loginLog->id, $loginLogs->first()->id);
        $this->assertEquals($logoutLog->id, $logoutLogs->first()->id);
    }

    public function test_audit_log_scopes_by_user()
    {
        $user2 = User::factory()->create();

        $user1Log = AuditLog::factory()->create([
            'user_id' => $this->user->id,
            'action' => 'test_action',
        ]);

        $user2Log = AuditLog::factory()->create([
            'user_id' => $user2->id,
            'action' => 'test_action',
        ]);

        $user1Logs = AuditLog::byUser($this->user->id)->get();
        $user2Logs = AuditLog::byUser($user2->id)->get();

        $this->assertCount(1, $user1Logs);
        $this->assertCount(1, $user2Logs);
        $this->assertEquals($user1Log->id, $user1Logs->first()->id);
        $this->assertEquals($user2Log->id, $user2Logs->first()->id);
    }

    public function test_audit_log_scopes_by_date_range()
    {
        $oldLog = AuditLog::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subWeek(),
        ]);

        $recentLog = AuditLog::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDay(),
        ]);

        $startDate = Carbon::now()->subDays(3);
        $endDate = Carbon::now();

        $recentLogs = AuditLog::dateRange($startDate, $endDate)->get();

        $this->assertCount(1, $recentLogs);
        $this->assertEquals($recentLog->id, $recentLogs->first()->id);
    }

    public function test_audit_log_with_model_tracking()
    {
        $activity = \App\Models\Activity::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $auditLog = AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'activity_created',
            'model_type' => 'App\Models\Activity',
            'model_id' => $activity->id,
            'new_values' => ['name' => $activity->name, 'status' => $activity->status],
            'ip_address' => '192.168.1.1',
        ]);

        $this->assertEquals('App\Models\Activity', $auditLog->model_type);
        $this->assertEquals($activity->id, $auditLog->model_id);
        $this->assertIsArray($auditLog->new_values);
        $this->assertEquals($activity->name, $auditLog->new_values['name']);
    }
}