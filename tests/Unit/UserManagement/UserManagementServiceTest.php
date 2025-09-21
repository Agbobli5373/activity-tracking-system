<?php

namespace Tests\Unit\UserManagement;

use Tests\TestCase;
use App\Services\UserManagementService;
use App\Models\User;
use App\Models\SystemSetting;
use App\Exceptions\UserManagement\DuplicateUserException;
use App\Exceptions\UserManagement\InvalidRoleException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserManagementService();
        
        // Create test roles
        Role::create(['name' => 'Administrator']);
        Role::create(['name' => 'Team Member']);
    }

    public function test_create_user_successfully()
    {
        Mail::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP001',
            'phone_number' => '123-456-7890',
            'role' => 'Team Member',
        ];

        $user = $this->service->createUser($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('EMP001', $user->employee_id);
        $this->assertEquals('active', $user->status);
        $this->assertTrue($user->hasRole('Team Member'));
        $this->assertNotNull($user->profile);

        Mail::assertSent(\App\Mail\WelcomeUserMail::class);
    }

    public function test_create_user_throws_exception_for_duplicate_email()
    {
        User::factory()->create(['email' => 'john@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->expectException(DuplicateUserException::class);
        $this->expectExceptionMessage('A user with this email already exists.');

        $this->service->createUser($userData);
    }

    public function test_create_user_throws_exception_for_duplicate_employee_id()
    {
        User::factory()->create(['employee_id' => 'EMP001']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP001',
        ];

        $this->expectException(DuplicateUserException::class);
        $this->expectExceptionMessage('A user with this employee ID already exists.');

        $this->service->createUser($userData);
    }

    public function test_update_user_successfully()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $updateData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone_number' => '987-654-3210',
        ];

        $updatedUser = $this->service->updateUser($user, $updateData);

        $this->assertEquals('Jane Doe', $updatedUser->name);
        $this->assertEquals('jane@example.com', $updatedUser->email);
        $this->assertEquals('987-654-3210', $updatedUser->phone_number);
    }

    public function test_update_user_throws_exception_for_duplicate_email()
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create(['email' => 'john@example.com']);

        $updateData = ['email' => 'existing@example.com'];

        $this->expectException(DuplicateUserException::class);
        $this->expectExceptionMessage('A user with this email already exists.');

        $this->service->updateUser($user, $updateData);
    }

    public function test_deactivate_user_successfully()
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->service->deactivateUser($user, 'No longer employed');

        $user->refresh();
        $this->assertEquals('inactive', $user->status);
    }

    public function test_assign_role_successfully()
    {
        $user = User::factory()->create();

        $this->service->assignRole($user, 'Administrator');

        $this->assertTrue($user->hasRole('Administrator'));
    }

    public function test_assign_role_throws_exception_for_invalid_role()
    {
        $user = User::factory()->create();

        $this->expectException(InvalidRoleException::class);
        $this->expectExceptionMessage('Invalid role: NonExistentRole');

        $this->service->assignRole($user, 'NonExistentRole');
    }

    public function test_generate_temporary_password()
    {
        SystemSetting::create([
            'key' => 'password_policy',
            'value' => [
                'min_length' => 10,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => false,
            ],
        ]);

        $password = $this->service->generateTemporaryPassword();

        $this->assertIsString($password);
        $this->assertGreaterThanOrEqual(10, strlen($password));
    }

    public function test_get_user_activity_report()
    {
        // Clear existing users to have a clean test
        User::query()->delete();
        
        $users = User::factory()->count(5)->create([
            'status' => 'active',
            'department' => 'Test Department'
        ]);
        $users[0]->assignRole('Administrator');
        $users[1]->assignRole('Team Member');

        $report = $this->service->getUserActivityReport([
            'status' => 'active',
        ]);

        $this->assertCount(5, $report);
    }

    public function test_bulk_update_users()
    {
        $users = User::factory()->count(3)->create(['status' => 'active']);
        $userIds = $users->pluck('id')->toArray();

        $updates = [
            'status' => 'inactive',
            'role' => 'Team Member',
        ];

        $this->service->bulkUpdateUsers($userIds, $updates);

        foreach ($users as $user) {
            $user->refresh();
            $this->assertEquals('inactive', $user->status);
            $this->assertTrue($user->hasRole('Team Member'));
        }
    }
}