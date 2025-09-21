<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Services\UserManagementService;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    protected UserManagementService $userManagementService;

    public function __construct(UserManagementService $userManagementService)
    {
        $this->middleware(['auth', 'permission:manage-users']);
        $this->userManagementService = $userManagementService;
    }

    /**
     * Display a listing of users with search, filtering, and pagination
     */
    public function index(Request $request): View
    {
        $query = User::with(['roles', 'department']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Role filter (supports both legacy and Spatie roles)
        if ($request->filled('role')) {
            $role = $request->get('role');
            $query->where(function ($q) use ($role) {
                $q->where('role', $role)
                  ->orWhereHas('roles', function ($roleQuery) use ($role) {
                      $roleQuery->where('name', $role);
                  });
            });
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        // Date range filter
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->get('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->get('created_to'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $allowedSortFields = ['name', 'email', 'created_at', 'last_login_at', 'status'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $users = $query->paginate(15)->withQueryString();

        // Get filter options
        $departments = Department::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $legacyRoles = ['admin', 'supervisor', 'member'];

        return view('admin.users.index', compact(
            'users', 
            'departments', 
            'roles', 
            'legacyRoles'
        ));
    }

    /**
     * Show the form for creating a new user
     */
    public function create(): View
    {
        $departments = Department::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        
        return view('admin.users.create', compact('departments', 'roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(CreateUserRequest $request): RedirectResponse
    {
        try {
            $user = $this->userManagementService->createUser($request->validated());

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', 'User created successfully. Welcome email has been sent.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user): View
    {
        $user->load(['roles', 'department', 'profile', 'createdActivities', 'assignedActivities']);
        
        // Get recent activity updates
        $recentActivities = $user->createdActivities()
            ->with(['assignedUser', 'updates'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.users.show', compact('user', 'recentActivities'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user): View
    {
        $user->load(['roles', 'department', 'profile']);
        $departments = Department::orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        
        return view('admin.users.edit', compact('user', 'departments', 'roles'));
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userManagementService->updateUser($user, $request->validated());

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', 'User updated successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate the specified user
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            // Prevent self-deactivation
            if ($user->id === auth()->id()) {
                return redirect()
                    ->back()
                    ->with('error', 'You cannot deactivate your own account.');
            }

            $reason = request('reason', 'Deactivated by administrator');
            $this->userManagementService->deactivateUser($user, $reason);

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User deactivated successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to deactivate user: ' . $e->getMessage());
        }
    }

    /**
     * Reactivate the specified user
     */
    public function restore(User $user): RedirectResponse
    {
        try {
            $user->activate();

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', 'User reactivated successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to reactivate user: ' . $e->getMessage());
        }
    }

    /**
     * Handle bulk actions on users
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,assign_role,change_department',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'role' => 'required_if:action,assign_role|exists:roles,name',
            'department_id' => 'required_if:action,change_department|exists:departments,id',
        ]);

        try {
            $userIds = $request->get('user_ids');
            $action = $request->get('action');

            // Prevent bulk action on current user for certain operations
            if (in_array($action, ['deactivate']) && in_array(auth()->id(), $userIds)) {
                return redirect()
                    ->back()
                    ->with('error', 'You cannot perform this action on your own account.');
            }

            switch ($action) {
                case 'activate':
                    User::whereIn('id', $userIds)->update(['status' => 'active']);
                    $message = 'Users activated successfully.';
                    break;

                case 'deactivate':
                    User::whereIn('id', $userIds)->update(['status' => 'inactive']);
                    $message = 'Users deactivated successfully.';
                    break;

                case 'assign_role':
                    $updates = ['role' => $request->get('role')];
                    $this->userManagementService->bulkUpdateUsers($userIds, $updates);
                    $message = 'Role assigned to users successfully.';
                    break;

                case 'change_department':
                    $updates = ['department_id' => $request->get('department_id')];
                    $this->userManagementService->bulkUpdateUsers($userIds, $updates);
                    $message = 'Department changed for users successfully.';
                    break;
            }

            return redirect()
                ->route('admin.users.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Bulk action failed: ' . $e->getMessage());
        }
    }

    /**
     * Export users data
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'status', 'role', 'department_id', 
                'created_from', 'created_to'
            ]);

            $users = $this->userManagementService->getUserActivityReport($filters);

            $exportData = $users->map(function ($user) {
                return [
                    'ID' => $user->id,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Employee ID' => $user->employee_id,
                    'Role' => $user->getRoleDisplayName(),
                    'Department' => $user->department?->name ?? $user->department,
                    'Status' => ucfirst($user->status),
                    'Last Login' => $user->last_login_at?->format('Y-m-d H:i:s'),
                    'Created At' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'filename' => 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user statistics for dashboard
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'inactive_users' => User::inactive()->count(),
                'locked_users' => User::locked()->count(),
                'pending_users' => User::pending()->count(),
                'recent_logins' => User::recentlyActive(7)->count(),
                'users_by_role' => User::selectRaw('role, COUNT(*) as count')
                    ->groupBy('role')
                    ->pluck('count', 'role'),
                'users_by_department' => User::with('department')
                    ->get()
                    ->groupBy('department.name')
                    ->map->count(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}