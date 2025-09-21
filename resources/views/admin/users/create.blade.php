@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.users.index') }}" 
               class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Users
            </a>
        </div>
        <div class="mt-2">
            <h1 class="text-3xl font-bold text-gray-900">Create New User</h1>
            <p class="mt-2 text-sm text-gray-600">Add a new user to the system with appropriate role and permissions</p>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf
            
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name"
                               value="{{ old('name') }}"
                               required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-300 @enderror"
                               placeholder="Enter full name">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email"
                               value="{{ old('email') }}"
                               required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-300 @enderror"
                               placeholder="user@example.com">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Employee ID -->
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Employee ID
                        </label>
                        <input type="text" 
                               name="employee_id" 
                               id="employee_id"
                               value="{{ old('employee_id') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('employee_id') border-red-300 @enderror"
                               placeholder="EMP001">
                        @error('employee_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">
                            Phone Number
                        </label>
                        <input type="tel" 
                               name="phone_number" 
                               id="phone_number"
                               value="{{ old('phone_number') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('phone_number') border-red-300 @enderror"
                               placeholder="+1 (555) 123-4567">
                        @error('phone_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select name="role" 
                                id="role" 
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('role') border-red-300 @enderror">
                            <option value="">Select a role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator (Legacy)</option>
                            <option value="supervisor" {{ old('role') === 'supervisor' ? 'selected' : '' }}>Supervisor (Legacy)</option>
                            <option value="member" {{ old('role') === 'member' ? 'selected' : '' }}>Member (Legacy)</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Department -->
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Department
                        </label>
                        <select name="department_id" 
                                id="department_id" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('department_id') border-red-300 @enderror">
                            <option value="">Select a department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">If no department is selected, user will be assigned to "General"</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Initial Status
                        </label>
                        <select name="status" 
                                id="status" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('status') border-red-300 @enderror">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending Activation</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Email Notification Option -->
                <div class="mt-6">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="send_welcome_email" 
                               id="send_welcome_email"
                               value="1"
                               {{ old('send_welcome_email', true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <label for="send_welcome_email" class="ml-2 block text-sm text-gray-700">
                            Send welcome email with login credentials
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        A temporary password will be generated and sent to the user's email address
                    </p>
                </div>

                <!-- Role Permissions Info -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg" id="roleInfo" style="display: none;">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">Role Permissions</h4>
                    <div id="rolePermissions" class="text-sm text-blue-800">
                        <!-- Dynamic content will be inserted here -->
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <a href="{{ route('admin.users.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </a>
                
                <button type="submit" 
                        class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const roleInfo = document.getElementById('roleInfo');
    const rolePermissions = document.getElementById('rolePermissions');

    const roleDescriptions = {
        'Administrator': {
            description: 'Full system access with all administrative privileges',
            permissions: [
                'Manage all users and their accounts',
                'Configure system-wide settings and policies',
                'Access all reports and audit logs',
                'Manage departments and organizational structure',
                'Full activity management capabilities'
            ]
        },
        'Supervisor': {
            description: 'Management access with team oversight capabilities',
            permissions: [
                'Manage team members and activities',
                'View and generate reports',
                'Assign and track team activities',
                'Limited user management for team members'
            ]
        },
        'Team Member': {
            description: 'Standard user access for daily operations',
            permissions: [
                'View and update assigned activities',
                'Create new activities',
                'Update personal profile and preferences',
                'View team activities and reports'
            ]
        },
        'Read-Only': {
            description: 'View-only access to system information',
            permissions: [
                'View activities and reports',
                'Access dashboard information',
                'Update personal profile only'
            ]
        },
        'admin': {
            description: 'Legacy Administrator role with full system access',
            permissions: [
                'Full system administration capabilities',
                'All user and system management features',
                'Complete access to all system functions'
            ]
        },
        'supervisor': {
            description: 'Legacy Supervisor role with management capabilities',
            permissions: [
                'Activity and team management',
                'Report generation and viewing',
                'Limited administrative functions'
            ]
        },
        'member': {
            description: 'Legacy Member role with standard user access',
            permissions: [
                'Basic activity management',
                'Personal profile management',
                'View assigned activities and reports'
            ]
        }
    };

    function updateRoleInfo() {
        const selectedRole = roleSelect.value;
        
        if (selectedRole && roleDescriptions[selectedRole]) {
            const roleData = roleDescriptions[selectedRole];
            
            let permissionsHtml = `<p class="mb-2">${roleData.description}</p><ul class="list-disc list-inside space-y-1">`;
            roleData.permissions.forEach(permission => {
                permissionsHtml += `<li>${permission}</li>`;
            });
            permissionsHtml += '</ul>';
            
            rolePermissions.innerHTML = permissionsHtml;
            roleInfo.style.display = 'block';
        } else {
            roleInfo.style.display = 'none';
        }
    }

    roleSelect.addEventListener('change', updateRoleInfo);
    
    // Initialize on page load
    updateRoleInfo();
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const role = document.getElementById('role').value;

    if (!name || !email || !role) {
        e.preventDefault();
        alert('Please fill in all required fields (Name, Email, and Role).');
        return false;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address.');
        return false;
    }

    return true;
});
</script>
@endsection