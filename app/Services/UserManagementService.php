<?php

namespace App\Services;

use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\WelcomeUserMail;
use App\Exceptions\UserManagement\DuplicateUserException;
use App\Exceptions\UserManagement\InvalidRoleException;

class UserManagementService
{
    /**
     * Create a new user with automatic password generation and email sending
     * 
     * @param array $userData
     * @return User
     * @throws DuplicateUserException
     */
    public function createUser(array $userData): User
    {
        // Check for duplicate email or employee_id
        if (User::where('email', $userData['email'])->exists()) {
            throw new DuplicateUserException('A user with this email already exists.');
        }

        if (isset($userData['employee_id']) && User::where('employee_id', $userData['employee_id'])->exists()) {
            throw new DuplicateUserException('A user with this employee ID already exists.');
        }

        DB::beginTransaction();

        try {
            // Generate temporary password
            $temporaryPassword = $this->generateTemporaryPassword();
            
            // Create user with hashed password
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'employee_id' => $userData['employee_id'] ?? null,
                'phone_number' => $userData['phone_number'] ?? null,
                'department' => $userData['department'] ?? 'General',
                'department_id' => $userData['department_id'] ?? null,
                'password' => Hash::make($temporaryPassword),
                'status' => 'active',
                'password_changed_at' => now(),
            ]);

            // Assign role if provided
            if (isset($userData['role'])) {
                $this->assignRole($user, $userData['role']);
            }

            // Create user profile
            $user->profile()->create([
                'timezone' => 'UTC',
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'language' => 'en',
            ]);

            // Send welcome email with credentials
            $this->sendWelcomeEmail($user, $temporaryPassword);

            DB::commit();

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_by' => auth()->id(),
            ]);

            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'user_data' => $userData,
            ]);
            throw $e;
        }
    }

    /**
     * Update user information
     * 
     * @param User $user
     * @param array $userData
     * @return User
     * @throws DuplicateUserException
     */
    public function updateUser(User $user, array $userData): User
    {
        // Check for duplicate email (excluding current user)
        if (isset($userData['email']) && 
            User::where('email', $userData['email'])->where('id', '!=', $user->id)->exists()) {
            throw new DuplicateUserException('A user with this email already exists.');
        }

        // Check for duplicate employee_id (excluding current user)
        if (isset($userData['employee_id']) && 
            User::where('employee_id', $userData['employee_id'])->where('id', '!=', $user->id)->exists()) {
            throw new DuplicateUserException('A user with this employee ID already exists.');
        }

        DB::beginTransaction();

        try {
            // Update user fields
            $user->update(array_filter([
                'name' => $userData['name'] ?? $user->name,
                'email' => $userData['email'] ?? $user->email,
                'employee_id' => $userData['employee_id'] ?? $user->employee_id,
                'phone_number' => $userData['phone_number'] ?? $user->phone_number,
                'department' => $userData['department'] ?? $user->department,
                'department_id' => $userData['department_id'] ?? $user->department_id,
            ]));

            // Update role if provided
            if (isset($userData['role'])) {
                $user->syncRoles([$userData['role']]);
            }

            DB::commit();

            Log::info('User updated successfully', [
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
                'changes' => $userData,
            ]);

            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Deactivate a user account
     * 
     * @param User $user
     * @param string $reason
     * @return void
     */
    public function deactivateUser(User $user, string $reason): void
    {
        DB::beginTransaction();

        try {
            $user->update([
                'status' => 'inactive',
            ]);

            Log::info('User deactivated', [
                'user_id' => $user->id,
                'reason' => $reason,
                'deactivated_by' => auth()->id(),
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to deactivate user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Assign a role to a user
     * 
     * @param User $user
     * @param string $roleName
     * @return void
     * @throws InvalidRoleException
     */
    public function assignRole(User $user, string $roleName): void
    {
        try {
            $user->assignRole($roleName);

            Log::info('Role assigned to user', [
                'user_id' => $user->id,
                'role' => $roleName,
                'assigned_by' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to assign role', [
                'user_id' => $user->id,
                'role' => $roleName,
                'error' => $e->getMessage(),
            ]);
            throw new InvalidRoleException("Invalid role: {$roleName}");
        }
    }

    /**
     * Generate a secure temporary password
     * 
     * @return string
     */
    public function generateTemporaryPassword(): string
    {
        // Get password policy from system settings
        $passwordPolicy = SystemSetting::get('password_policy', [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
        ]);

        $length = max($passwordPolicy['min_length'] ?? 8, 8);
        
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters .= '0123456789';
        
        if ($passwordPolicy['require_symbols'] ?? false) {
            $characters .= '!@#$%^&*';
        }

        return Str::random($length);
    }

    /**
     * Send welcome email to new user
     * 
     * @param User $user
     * @param string $password
     * @return void
     */
    public function sendWelcomeEmail(User $user, string $password): void
    {
        try {
            Mail::to($user->email)->send(new WelcomeUserMail($user, $password));

            Log::info('Welcome email sent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            // Don't throw exception as user creation should still succeed
        }
    }

    /**
     * Get user activity report with filters
     * 
     * @param array $filters
     * @return Collection
     */
    public function getUserActivityReport(array $filters): Collection
    {
        $query = User::with(['roles', 'department']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['role'])) {
            $query->byRole($filters['role']);
        }

        if (isset($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (isset($filters['last_login_from'])) {
            $query->where('last_login_at', '>=', $filters['last_login_from']);
        }

        return $query->get();
    }

    /**
     * Bulk update users
     * 
     * @param array $userIds
     * @param array $updates
     * @return void
     */
    public function bulkUpdateUsers(array $userIds, array $updates): void
    {
        DB::beginTransaction();

        try {
            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                // Update basic fields
                if (isset($updates['status'])) {
                    $user->status = $updates['status'];
                }

                if (isset($updates['department_id'])) {
                    $user->department_id = $updates['department_id'];
                }

                $user->save();

                // Update role if provided
                if (isset($updates['role'])) {
                    $user->syncRoles([$updates['role']]);
                }
            }

            DB::commit();

            Log::info('Bulk user update completed', [
                'user_count' => count($userIds),
                'updates' => $updates,
                'updated_by' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk user update failed', [
                'user_ids' => $userIds,
                'updates' => $updates,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}