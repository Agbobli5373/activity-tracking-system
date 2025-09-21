@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4 mb-4">
            <a href="{{ route('admin.users.index') }}" 
               class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Users
            </a>
        </div>
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="h-16 w-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xl font-bold">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
                    <div class="flex items-center space-x-4 mt-1">
                        <span class="text-sm text-gray-600">{{ $user->email }}</span>
                        @if($user->employee_id)
                            <span class="text-sm text-gray-500">ID: {{ $user->employee_id }}</span>
                        @endif
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($user->status === 'active') bg-green-100 text-green-800
                            @elseif($user->status === 'inactive') bg-gray-100 text-gray-800
                            @elseif($user->status === 'locked') bg-red-100 text-red-800
                            @elseif($user->status === 'pending') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($user->status) }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 sm:mt-0 flex space-x-3">
                @if($user->status === 'inactive')
                    <form method="POST" action="{{ route('admin.users.restore', $user) }}" class="inline">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                onclick="return confirm('Are you sure you want to reactivate this user?')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Reactivate
                        </button>
                    </form>
                @endif
                
                <a href="{{ route('admin.users.edit', $user) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit User
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- User Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">User Information</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Employee ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->employee_id ?: 'Not assigned' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->phone_number ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Department</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $user->department?->name ?? $user->department ?? 'Not assigned' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Role</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($user->getRoleDisplayName() === 'Administrator') bg-purple-100 text-purple-800
                                    @elseif($user->getRoleDisplayName() === 'Supervisor') bg-blue-100 text-blue-800
                                    @elseif($user->getRoleDisplayName() === 'Team Member') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $user->getRoleDisplayName() }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Account Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('M j, Y g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($user->last_login_at)
                                    {{ $user->last_login_at->format('M j, Y g:i A') }}
                                    <span class="text-xs text-gray-500">({{ $user->last_login_at->diffForHumans() }})</span>
                                @else
                                    <span class="text-gray-500">Never logged in</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- User Profile -->
            @if($user->profile)
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Profile Settings</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Timezone</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->profile->timezone }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Language</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ strtoupper($user->profile->language) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Date Format</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->profile->date_format }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Time Format</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->profile->time_format }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
            @endif

            <!-- Recent Activities -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Recent Activities</h2>
                </div>
                <div class="px-6 py-4">
                    @if($recentActivities->count() > 0)
                        <div class="space-y-4">
                            @foreach($recentActivities as $activity)
                                <div class="flex items-start space-x-3 p-3 rounded-lg bg-gray-50">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-gray-900">{{ $activity->title }}</p>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                @if($activity->status === 'completed') bg-green-100 text-green-800
                                                @elseif($activity->status === 'in_progress') bg-blue-100 text-blue-800
                                                @elseif($activity->status === 'pending') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst(str_replace('_', ' ', $activity->status)) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">{{ Str::limit($activity->description, 100) }}</p>
                                        <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                            <span>Created {{ $activity->created_at->diffForHumans() }}</span>
                                            @if($activity->assignedUser && $activity->assignedUser->id !== $user->id)
                                                <span>Assigned to {{ $activity->assignedUser->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 text-center">
                            <a href="{{ route('activities.index', ['created_by' => $user->id]) }}" 
                               class="text-sm text-blue-600 hover:text-blue-800">
                                View all activities created by this user →
                            </a>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No activities</h3>
                            <p class="mt-1 text-sm text-gray-500">This user hasn't created any activities yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Quick Stats</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Activities Created</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $user->createdActivities->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Activities Assigned</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $user->assignedActivities->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Failed Login Attempts</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $user->failed_login_attempts }}</dd>
                        </div>
                        @if($user->password_changed_at)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Password Last Changed</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $user->password_changed_at->diffForHumans() }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Security Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Security</h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-gray-500">Two-Factor Auth</dt>
                            <dd>
                                @if($user->two_factor_enabled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Enabled
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Disabled
                                    </span>
                                @endif
                            </dd>
                        </div>
                        
                        @if($user->account_locked_until)
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Account Locked Until</dt>
                            <dd class="text-sm font-medium text-red-600">
                                {{ $user->account_locked_until->format('M j, Y g:i A') }}
                            </dd>
                        </div>
                        @endif

                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Account Status</dt>
                            <dd>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    @if($user->isActive()) bg-green-100 text-green-800
                                    @elseif($user->isLocked()) bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    @if($user->isActive()) Active
                                    @elseif($user->isLocked()) Locked
                                    @elseif($user->isPending()) Pending
                                    @else Inactive
                                    @endif
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Permissions -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Permissions</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-2">
                        @php
                            $permissions = [
                                'manage-users' => 'Manage Users',
                                'manage-activities' => 'Manage Activities',
                                'manage-system' => 'System Settings',
                                'view-reports' => 'View Reports',
                                'view-audit-logs' => 'Audit Logs'
                            ];
                        @endphp
                        
                        @foreach($permissions as $permission => $label)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">{{ $label }}</span>
                                @if($user->hasPermissionTo($permission))
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Quick Actions</h2>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @if($user->failed_login_attempts > 0)
                        <button onclick="resetFailedAttempts({{ $user->id }})"
                                class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md">
                            Reset Failed Login Attempts
                        </button>
                    @endif
                    
                    @if($user->isLocked())
                        <form method="POST" action="{{ route('admin.users.restore', $user) }}" class="w-full">
                            @csrf
                            <button type="submit" 
                                    class="w-full text-left px-3 py-2 text-sm text-green-600 hover:bg-green-50 rounded-md"
                                    onclick="return confirm('Are you sure you want to unlock this account?')">
                                Unlock Account
                            </button>
                        </form>
                    @endif
                    
                    <button onclick="sendPasswordReset({{ $user->id }})"
                            class="w-full text-left px-3 py-2 text-sm text-indigo-600 hover:bg-indigo-50 rounded-md">
                        Send Password Reset
                    </button>
                    
                    <a href="{{ route('audit.user-activity', $user) }}" 
                       class="block w-full text-left px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 rounded-md">
                        View Audit Log
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resetFailedAttempts(userId) {
    if (confirm('Are you sure you want to reset the failed login attempts for this user?')) {
        // Implementation would go here
        alert('Feature not yet implemented');
    }
}

function sendPasswordReset(userId) {
    if (confirm('Are you sure you want to send a password reset email to this user?')) {
        // Implementation would go here
        alert('Feature not yet implemented');
    }
}
</script>
@endsection