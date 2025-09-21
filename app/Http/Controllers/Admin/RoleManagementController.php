<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class RoleManagementController extends Controller
{
    /**
     * Display a listing of roles and permissions.
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Group permissions by category (prefix before the first dash)
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[0] : 'general';
        });

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[0] : 'general';
        });

        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(CreateRoleRequest $request)
    {
        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'guard_name' => 'web'
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();

            Log::info('Role created', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions' => $request->permissions ?? [],
                'created_by' => auth()->id()
            ]);

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create role', [
                'error' => $e->getMessage(),
                'request_data' => $request->validated(),
                'user_id' => auth()->id()
            ]);

            return back()->withInput()
                ->withErrors(['error' => 'Failed to create role. Please try again.']);
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load('permissions', 'users');
        
        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[0] : 'general';
        });

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        try {
            DB::beginTransaction();

            $originalPermissions = $role->permissions->pluck('name')->toArray();

            $role->update([
                'name' => $request->name,
                'description' => $request->description
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            } else {
                $role->syncPermissions([]);
            }

            DB::commit();

            Log::info('Role updated', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'old_permissions' => $originalPermissions,
                'new_permissions' => $request->permissions ?? [],
                'updated_by' => auth()->id()
            ]);

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'request_data' => $request->validated(),
                'user_id' => auth()->id()
            ]);

            return back()->withInput()
                ->withErrors(['error' => 'Failed to update role. Please try again.']);
        }
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        try {
            // Check if role is assigned to any users
            if ($role->users()->count() > 0) {
                return back()->withErrors([
                    'error' => 'Cannot delete role that is assigned to users. Please reassign users first.'
                ]);
            }

            // Prevent deletion of system roles
            $systemRoles = ['Administrator', 'Supervisor', 'Team Member', 'Read-Only'];
            if (in_array($role->name, $systemRoles)) {
                return back()->withErrors([
                    'error' => 'Cannot delete system role.'
                ]);
            }

            $roleName = $role->name;
            $role->delete();

            Log::info('Role deleted', [
                'role_name' => $roleName,
                'deleted_by' => auth()->id()
            ]);

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->withErrors(['error' => 'Failed to delete role. Please try again.']);
        }
    }

    /**
     * Show the permission matrix interface.
     */
    public function permissions()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[0] : 'general';
        });

        return view('admin.roles.permissions', compact('roles', 'permissions'));
    }

    /**
     * Update permission matrix.
     */
    public function updatePermissions(Request $request)
    {
        $request->validate([
            'role_permissions' => 'array',
            'role_permissions.*' => 'array'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->role_permissions as $roleId => $permissions) {
                $role = Role::findOrFail($roleId);
                $role->syncPermissions($permissions);
            }

            DB::commit();

            Log::info('Permission matrix updated', [
                'updated_by' => auth()->id(),
                'role_permissions' => $request->role_permissions
            ]);

            return redirect()->route('admin.roles.permissions')
                ->with('success', 'Permissions updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update permission matrix', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->withErrors(['error' => 'Failed to update permissions. Please try again.']);
        }
    }

    /**
     * Show role assignment interface for bulk operations.
     */
    public function assignUsers(Role $role)
    {
        $users = User::with('roles')->get();
        $usersWithRole = $role->users->pluck('id')->toArray();

        return view('admin.roles.assign-users', compact('role', 'users', 'usersWithRole'));
    }

    /**
     * Bulk assign/remove users to/from role.
     */
    public function updateUserAssignments(Request $request, Role $role)
    {
        $request->validate([
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id'
        ]);

        try {
            DB::beginTransaction();

            $currentUserIds = $role->users->pluck('id')->toArray();
            $newUserIds = $request->user_ids ?? [];

            // Users to add to role
            $usersToAdd = array_diff($newUserIds, $currentUserIds);
            
            // Users to remove from role
            $usersToRemove = array_diff($currentUserIds, $newUserIds);

            // Add users to role
            foreach ($usersToAdd as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->assignRole($role);
                }
            }

            // Remove users from role
            foreach ($usersToRemove as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->removeRole($role);
                }
            }

            DB::commit();

            Log::info('Role assignments updated', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'users_added' => $usersToAdd,
                'users_removed' => $usersToRemove,
                'updated_by' => auth()->id()
            ]);

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role assignments updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update role assignments', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->withErrors(['error' => 'Failed to update role assignments. Please try again.']);
        }
    }

    /**
     * Get role statistics for dashboard.
     */
    public function statistics()
    {
        $stats = [
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'users_with_roles' => User::whereHas('roles')->count(),
            'users_without_roles' => User::whereDoesntHave('roles')->count(),
            'role_distribution' => Role::withCount('users')->get()->map(function ($role) {
                return [
                    'name' => $role->name,
                    'users_count' => $role->users_count
                ];
            })
        ];

        return response()->json($stats);
    }
}