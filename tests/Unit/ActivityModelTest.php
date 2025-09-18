<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ActivityModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->creator = User::factory()->create([
            'name' => 'John Creator',
            'employee_id' => 'EMP001',
            'role' => 'member'
        ]);
        
        $this->assignee = User::factory()->create([
            'name' => 'Jane Assignee',
            'employee_id' => 'EMP002',
            'role' => 'member'
        ]);
    }

    public function test_activity_can_be_created_with_required_fields()
    {
        $activity = Activity::create([
            'name' => 'Test Activity',
            'description' => 'This is a test activity',
            'created_by' => $this->creator->id,
        ]);

        $this->assertInstanceOf(Activity::class, $activity);
        $this->assertEquals('Test Activity', $activity->name);
        $this->assertEquals('This is a test activity', $activity->description);
        $this->assertEquals('pending', $activity->status); // Default status
        $this->assertEquals('medium', $activity->priority); // Default priority
        $this->assertEquals($this->creator->id, $activity->created_by);
    }

    public function test_activity_fillable_attributes()
    {
        $fillable = [
            'name',
            'description',
            'status',
            'priority',
            'created_by',
            'assigned_to',
            'due_date',
        ];

        $activity = new Activity();
        $this->assertEquals($fillable, $activity->getFillable());
    }

    public function test_activity_validation_rules()
    {
        $rules = Activity::validationRules();

        $expectedRules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'in:pending,done',
            'priority' => 'in:low,medium,high',
            'created_by' => 'required|exists:users,id',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
        ];

        $this->assertEquals($expectedRules, $rules);
    }

    public function test_activity_creator_relationship()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->creator->id,
        ]);

        $this->assertInstanceOf(User::class, $activity->creator);
        $this->assertEquals($this->creator->id, $activity->creator->id);
        $this->assertEquals($this->creator->name, $activity->creator->name);
    }

    public function test_activity_assignee_relationship()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'assigned_to' => $this->assignee->id,
        ]);

        $this->assertInstanceOf(User::class, $activity->assignee);
        $this->assertEquals($this->assignee->id, $activity->assignee->id);
        $this->assertEquals($this->assignee->name, $activity->assignee->name);
    }

    public function test_activity_assignee_can_be_null()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'assigned_to' => null,
        ]);

        $this->assertNull($activity->assignee);
    }

    public function test_by_date_scope()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todayActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'created_at' => $today,
        ]);

        $yesterdayActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'created_at' => $yesterday,
        ]);

        $todayActivities = Activity::byDate($today)->get();
        $yesterdayActivities = Activity::byDate($yesterday)->get();

        $this->assertCount(1, $todayActivities);
        $this->assertCount(1, $yesterdayActivities);
        $this->assertEquals($todayActivity->id, $todayActivities->first()->id);
        $this->assertEquals($yesterdayActivity->id, $yesterdayActivities->first()->id);
    }

    public function test_by_status_scope()
    {
        $pendingActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'pending',
        ]);

        $doneActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'done',
        ]);

        $pendingActivities = Activity::byStatus('pending')->get();
        $doneActivities = Activity::byStatus('done')->get();

        $this->assertCount(1, $pendingActivities);
        $this->assertCount(1, $doneActivities);
        $this->assertEquals($pendingActivity->id, $pendingActivities->first()->id);
        $this->assertEquals($doneActivity->id, $doneActivities->first()->id);
    }

    public function test_by_creator_scope()
    {
        $activity1 = Activity::factory()->create(['created_by' => $this->creator->id]);
        $activity2 = Activity::factory()->create(['created_by' => $this->assignee->id]);

        $creatorActivities = Activity::byCreator($this->creator->id)->get();
        $assigneeActivities = Activity::byCreator($this->assignee->id)->get();

        $this->assertCount(1, $creatorActivities);
        $this->assertCount(1, $assigneeActivities);
        $this->assertEquals($activity1->id, $creatorActivities->first()->id);
        $this->assertEquals($activity2->id, $assigneeActivities->first()->id);
    }

    public function test_by_assignee_scope()
    {
        $activity1 = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'assigned_to' => $this->assignee->id,
        ]);
        
        $activity2 = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'assigned_to' => $this->creator->id,
        ]);

        $assigneeActivities = Activity::byAssignee($this->assignee->id)->get();
        $creatorActivities = Activity::byAssignee($this->creator->id)->get();

        $this->assertCount(1, $assigneeActivities);
        $this->assertCount(1, $creatorActivities);
        $this->assertEquals($activity1->id, $assigneeActivities->first()->id);
        $this->assertEquals($activity2->id, $creatorActivities->first()->id);
    }

    public function test_pending_scope()
    {
        $pendingActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'pending',
        ]);

        $doneActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'done',
        ]);

        $pendingActivities = Activity::pending()->get();

        $this->assertCount(1, $pendingActivities);
        $this->assertEquals($pendingActivity->id, $pendingActivities->first()->id);
    }

    public function test_completed_scope()
    {
        $pendingActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'pending',
        ]);

        $doneActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'done',
        ]);

        $completedActivities = Activity::completed()->get();

        $this->assertCount(1, $completedActivities);
        $this->assertEquals($doneActivity->id, $completedActivities->first()->id);
    }

    public function test_is_pending_method()
    {
        $pendingActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'pending',
        ]);

        $doneActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'done',
        ]);

        $this->assertTrue($pendingActivity->isPending());
        $this->assertFalse($doneActivity->isPending());
    }

    public function test_is_completed_method()
    {
        $pendingActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'pending',
        ]);

        $doneActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'done',
        ]);

        $this->assertFalse($pendingActivity->isCompleted());
        $this->assertTrue($doneActivity->isCompleted());
    }

    public function test_is_overdue_method()
    {
        $overdueActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'pending',
            'due_date' => Carbon::yesterday(),
        ]);

        $futureActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'pending',
            'due_date' => Carbon::tomorrow(),
        ]);

        $completedOverdueActivity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'status' => 'done',
            'due_date' => Carbon::yesterday(),
        ]);

        $this->assertTrue($overdueActivity->isOverdue());
        $this->assertFalse($futureActivity->isOverdue());
        $this->assertFalse($completedOverdueActivity->isOverdue());
    }

    public function test_due_date_casting()
    {
        $activity = Activity::factory()->create([
            'created_by' => $this->creator->id,
            'due_date' => '2024-12-25',
        ]);

        $this->assertInstanceOf(Carbon::class, $activity->due_date);
        $this->assertEquals('2024-12-25', $activity->due_date->format('Y-m-d'));
    }
}
