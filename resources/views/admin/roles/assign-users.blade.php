@extends('layouts.app')

@section('title', 'Assign Users to Role')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-3xl font-bold text-gray-900">Assign Users to Role: {{ $role->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">Manage user assignments for this role</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('admin.roles.show', $role) }}" class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                View Role
            </a>
            <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Roles
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(session('error') || $errors->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') ?? $errors->first('error') }}</p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="inline-flex bg-red-50 rounded-md p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-red-50 focus:ring-red-600" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Role Information -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Role Information</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-700">Role Name:</dt>
                            <dd class="mt-1 flex items-center">
                                <span class="text-sm text-gray-900">{{ $role->name }}</span>
                                @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">System Role</span>
                                @endif
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-700">Description:</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $role->description ?? 'No description provided' }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-700">Current Users:</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ count($usersWithRole) }}</span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-700">Permissions:</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $role->permissions->count() }}</span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Statistics -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Assignment Statistics</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Total Users:</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ $users->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Users with Role:</dt>
                            <dd class="text-sm font-medium text-gray-900" id="assignedCount">{{ count($usersWithRole) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Users without Role:</dt>
                            <dd class="text-sm font-medium text-gray-900" id="unassignedCount">{{ $users->count() - count($usersWithRole) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- User Assignment Form -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            User Assignments
                        </h3>
                        <div class="mt-3 sm:mt-0 flex flex-wrap gap-2">
                            <button type="button" 
                                    onclick="selectAllUsers()" 
                                    class="inline-flex items-center px-3 py-2 border border-green-300 rounded-lg text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Select All
                            </button>
                            <button type="button" 
                                    onclick="deselectAllUsers()" 
                                    class="inline-flex items-center px-3 py-2 border border-red-300 rounded-lg text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Deselect All
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.roles.update-user-assignments', $role) }}" id="assignmentForm">
                        @csrf

                        <!-- Search and Filter -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" 
                                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       id="userSearch" 
                                       placeholder="Search users...">
                            </div>
                            <div>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="locked">Locked</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <!-- Users List -->
                        <div class="overflow-hidden">
                            <div class="overflow-x-auto border border-gray-200 rounded-lg" style="max-height: 600px;">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0 z-10">
                                        <tr>
                                            <th scope="col" class="w-16 px-4 py-3 text-center">
                                                <input type="checkbox" 
                                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2" 
                                                       id="selectAllCheckbox" 
                                                       onchange="toggleAllUsers(this.checked)">
                                            </th>
                                            <th scope="col" class="min-w-0 w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th scope="col" class="min-w-0 w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th scope="col" class="min-w-0 w-1/6 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                            <th scope="col" class="min-w-0 w-20 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="min-w-0 w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Roles</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                                        @foreach($users as $user)
                                        <tr class="user-row hover:bg-gray-50" 
                                            data-name="{{ strtolower($user->name) }}" 
                                            data-email="{{ strtolower($user->email) }}"
                                            data-status="{{ $user->status }}">
                                            <td class="px-4 py-4 text-center">
                                                <input type="checkbox" 
                                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 user-checkbox" 
                                                       name="user_ids[]" 
                                                       value="{{ $user->id }}"
                                                       {{ in_array($user->id, $usersWithRole) ? 'checked' : '' }}
                                                       onchange="updateCounts()">
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center min-w-0">
                                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                        <span class="text-sm font-medium text-blue-600">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</div>
                                                        @if($user->employee_id)
                                                            <div class="text-xs text-gray-500 truncate">ID: {{ $user->employee_id }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="text-sm text-gray-900 truncate">{{ $user->email }}</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="text-sm text-gray-900 truncate">
                                                    @if($user->department)
                                                        {{ is_object($user->department) ? $user->department->name : $user->department }}
                                                    @else
                                                        <span class="text-gray-500">Not assigned</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                @switch($user->status)
                                                    @case('active')
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Act</span>
                                                        @break
                                                    @case('inactive')
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inact</span>
                                                        @break
                                                    @case('locked')
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Lock</span>
                                                        @break
                                                    @case('pending')
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pend</span>
                                                        @break
                                                    @default
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($user->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td class="px-4 py-4">
                                                @if($user->roles->count() > 0)
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($user->roles as $userRole)
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $userRole->name === $role->name ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                                                {{ Str::limit($userRole->name, 10) }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-500">No roles</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div class="mb-4 sm:mb-0">
                                <p class="text-sm text-gray-600">
                                    <span id="selectedCount">{{ count($usersWithRole) }}</span> of {{ $users->count() }} users selected
                                </p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Update Assignments
                                </button>
                                <a href="{{ route('admin.roles.index') }}" 
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('userSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    filterUsers();
});

// Status filter
document.getElementById('statusFilter').addEventListener('change', function() {
    filterUsers();
});

function filterUsers() {
    const searchTerm = document.getElementById('userSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.user-row');
    
    rows.forEach(row => {
        const name = row.dataset.name;
        const email = row.dataset.email;
        const status = row.dataset.status;
        
        const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        
        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    updateCounts();
}

function selectAllUsers() {
    const visibleCheckboxes = document.querySelectorAll('.user-row:not([style*="display: none"]) .user-checkbox');
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    updateCounts();
}

function deselectAllUsers() {
    const visibleCheckboxes = document.querySelectorAll('.user-row:not([style*="display: none"]) .user-checkbox');
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateCounts();
}

function toggleAllUsers(checked) {
    const visibleCheckboxes = document.querySelectorAll('.user-row:not([style*="display: none"]) .user-checkbox');
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = checked;
    });
    updateCounts();
}

function updateCounts() {
    const totalUsers = {{ $users->count() }};
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedCount = checkedBoxes.length;
    
    document.getElementById('selectedCount').textContent = selectedCount;
    document.getElementById('assignedCount').textContent = selectedCount;
    document.getElementById('unassignedCount').textContent = totalUsers - selectedCount;
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.user-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    if (selectedCount === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (selectedCount === allCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

// Initialize counts on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCounts();
    
    // Add change listeners to all checkboxes
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateCounts);
    });
});

// Form submission confirmation
document.getElementById('assignmentForm').addEventListener('submit', function(e) {
    const selectedCount = document.querySelectorAll('.user-checkbox:checked').length;
    const roleName = '{{ $role->name }}';
    
    if (!confirm(`Are you sure you want to assign ${selectedCount} users to the "${roleName}" role? This will update their permissions immediately.`)) {
        e.preventDefault();
    }
});
</script>
@endsection