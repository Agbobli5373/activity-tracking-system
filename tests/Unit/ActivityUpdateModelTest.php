<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityUpdateModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users and activity
        $this->user = User::factory()->create([
            'name' => 'John Updater',
            'employee_id' => 'EMP001',
            'role' => 'member'
        ]);
        
        $this->activity = Activity::factory()->create([
            'name' => 'Test Activity',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_activity_update_can_be_created()
    {
        $update = ActivityUpdate::create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
            'remarks' => 'Activity completed successfully',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 Test Browser',
        ]);

        $this->assertInstanceOf(ActivityUpdate::class, $update);
        $this->assertEquals($this->activity->id, $update->activity_id);
        $this->assertEquals($this->user->id, $update->user_id);
        $this->assertEquals('pending', $update->previous_status);
        $this->assertEquals('done', $update->new_status);
        $this->assertEquals('Activity completed successfully', $update->remarks);
        $this->assertEquals('192.168.1.1', $update->ip_address);
        $this->assertEquals('Mozilla/5.0 Test Browser', $update->user_agent);
    }

    public function test_activity_update_fillable_attributes()
    {
        $fillable = [
            'activity_id',
            'user_id',
            'previous_status',
            'new_status',
            'remarks',
            'ip_address',
            'user_agent',
        ];

        $update = new ActivityUpdate();
        $this->assertEquals($fillable, $update->getFillable());
    }

    public function test_activity_update_validation_rules()
    {
        $rules = ActivityUpdate::validationRules();

        $expectedRules = [
            'activity_id' => 'required|exists:activities,id',
            'user_id' => 'required|exists:users,id',
            'previous_status' => 'nullable|in:pending,done',
            'new_status' => 'required|in:pending,done',
            'remarks' => 'required|string|min:10',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string',
        ];

        $this->assertEquals($expectedRules, $rules);
    }

    public function test_activity_update_activity_relationship()
    {
        $update = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(Activity::class, $update->activity);
        $this->assertEquals($this->activity->id, $update->activity->id);
        $this->assertEquals($this->activity->name, $update->activity->name);
    }

    public function test_activity_update_user_relationship()
    {
        $update = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $update->user);
        $this->assertEquals($this->user->id, $update->user->id);
        $this->assertEquals($this->user->name, $update->user->name);
    }

    public function test_for_activity_scope()
    {
        $activity2 = Activity::factory()->create(['created_by' => $this->user->id]);

        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
        ]);

        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $activity2->id,
            'user_id' => $this->user->id,
        ]);

        $activity1Updates = ActivityUpdate::forActivity($this->activity->id)->get();
        $activity2Updates = ActivityUpdate::forActivity($activity2->id)->get();

        $this->assertCount(1, $activity1Updates);
        $this->assertCount(1, $activity2Updates);
        $this->assertEquals($update1->id, $activity1Updates->first()->id);
        $this->assertEquals($update2->id, $activity2Updates->first()->id);
    }

    public function test_by_user_scope()
    {
        $user2 = User::factory()->create();

        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
        ]);

        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $user2->id,
        ]);

        $user1Updates = ActivityUpdate::byUser($this->user->id)->get();
        $user2Updates = ActivityUpdate::byUser($user2->id)->get();

        $this->assertCount(1, $user1Updates);
        $this->assertCount(1, $user2Updates);
        $this->assertEquals($update1->id, $user1Updates->first()->id);
        $this->assertEquals($update2->id, $user2Updates->first()->id);
    }

    public function test_status_change_scope()
    {
        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
        ]);

        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'done',
            'new_status' => 'pending',
        ]);

        $completionUpdates = ActivityUpdate::statusChange('pending', 'done')->get();
        $reopeningUpdates = ActivityUpdate::statusChange('done', 'pending')->get();

        $this->assertCount(1, $completionUpdates);
        $this->assertCount(1, $reopeningUpdates);
        $this->assertEquals($update1->id, $completionUpdates->first()->id);
        $this->assertEquals($update2->id, $reopeningUpdates->first()->id);
    }

    public function test_chronological_scope()
    {
        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subHours(2),
        ]);

        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subHour(),
        ]);

        $chronologicalUpdates = ActivityUpdate::chronological()->get();

        $this->assertEquals($update1->id, $chronologicalUpdates->first()->id);
        $this->assertEquals($update2->id, $chronologicalUpdates->last()->id);
    }

    public function test_latest_scope()
    {
        $update1 = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subHours(2),
        ]);

        $update2 = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'created_at' => now()->subHour(),
        ]);

        $latestUpdates = ActivityUpdate::latest()->get();

        $this->assertEquals($update2->id, $latestUpdates->first()->id);
        $this->assertEquals($update1->id, $latestUpdates->last()->id);
    }

    public function test_is_completion_method()
    {
        $completionUpdate = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'new_status' => 'done',
        ]);

        $pendingUpdate = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'new_status' => 'pending',
        ]);

        $this->assertTrue($completionUpdate->isCompletion());
        $this->assertFalse($pendingUpdate->isCompletion());
    }

    public function test_is_reopening_method()
    {
        $reopeningUpdate = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'done',
            'new_status' => 'pending',
        ]);

        $completionUpdate = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
        ]);

        $this->assertTrue($reopeningUpdate->isReopening());
        $this->assertFalse($completionUpdate->isReopening());
    }

    public function test_get_status_change_description_method()
    {
        $creationUpdate = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'previous_status' => null,
            'new_status' => 'pending',
        ]);

        $completionUpdate = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'done',
        ]);

        $noChangeUpdate = ActivityUpdate::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => $this->user->id,
            'previous_status' => 'pending',
            'new_status' => 'pending',
        ]);

        $this->assertEquals("Activity created with status 'pending'", $creationUpdate->getStatusChangeDescription());
        $this->assertEquals("Status changed from 'pending' to 'done'", $completionUpdate->getStatusChangeDescription());
        $this->assertEquals("Status updated (no change from 'pending')", $noChangeUpdate->getStatusChangeDescription());
    }

    public function test_create_audit_entry_static_method()
    {
        $update = ActivityUpdate::createAuditEntry(
            $this->activity->id,
            $this->user->id,
            'pending',
            'done',
            'Task completed successfully',
            '192.168.1.1',
            'Mozilla/5.0 Test Browser'
        );

        $this->assertInstanceOf(ActivityUpdate::class, $update);
        $this->assertEquals($this->activity->id, $update->activity_id);
        $this->assertEquals($this->user->id, $update->user_id);
        $this->assertEquals('pending', $update->previous_status);
        $this->assertEquals('done', $update->new_status);
        $this->assertEquals('Task completed successfully', $update->remarks);
        $this->assertEquals('192.168.1.1', $update->ip_address);
        $this->assertEquals('Mozilla/5.0 Test Browser', $update->user_agent);
    }

    public function test_create_audit_entry_with_minimal_data()
    {
        $update = ActivityUpdate::createAuditEntry(
            $this->activity->id,
            $this->user->id,
            null,
            'pending',
            'Activity created'
        );

        $this->assertInstanceOf(ActivityUpdate::class, $update);
        $this->assertEquals($this->activity->id, $update->activity_id);
        $this->assertEquals($this->user->id, $update->user_id);
        $this->assertNull($update->previous_status);
        $this->assertEquals('pending', $update->new_status);
        $this->assertEquals('Activity created', $update->remarks);
        $this->assertNull($update->ip_address);
        $this->assertNull($update->user_agent);
    }
}
