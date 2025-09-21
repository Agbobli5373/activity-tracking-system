@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.users.show', $user) }}" 
               class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to User Details
            </a>
        </div>
        <div class="mt-2 flex items-center space-x-4">
            <div class="flex-shrink-0">
                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold">
                    {{ substr($user->name, 0, 1) }}
                </div>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit {{ $user->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">Update user information, role, and account settings</p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="px-6 py-6">
                <!-- Basic Information -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Full Name -->
                        <div class="md:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name"
                                   value="{{ old('name', $user->name) }}"
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
                                   value="{{ old('email', $user->email) }}"
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
                                   value="{{ old('employee_id', $user->employee_id) }}"
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
                                   value="{{ old('phone_number', $user->phone_number) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('phone_number') border-red-300 @enderror"
                                   placeholder="+1 (555) 123-4567">
                            @error('phone_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Role and Department -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Role and Department</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                                Role
                            </label>
                            <select name="role" 
                                    id="role" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('role') border-red-300 @enderror">
                                <option value="">Keep current role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" 
                                            {{ old('role') === $role->name || $user->hasRole($role->name) ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                                <option value="admin" {{ old('role') === 'admin' || $user->role === 'admin' ? 'selected' : '' }}>
                                    Administrator (Legacy)
                                </option>
                                <option value="supervisor" {{ old('role') === 'supervisor' || $user->role === 'supervisor' ? 'selected' : '' }}>
                                    Supervisor (Legacy)
                                </option>
                                <option value="member" {{ old('role') === 'member' || $user->role === 'member' ? 'selected' : '' }}>
                                    Member (Legacy)
                                </option>
                            </select>
                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Current role: {{ $user->getRoleDisplayName() }}</p>
                        </div>

                        <!-- Department -->
                        <div>
                            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Department
                            </label>
                            <select name="department_id" 
                                    id="department_id" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('department_id') border-red-300 @enderror">
                                <option value="">No department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" 
                                            {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Account Status -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Account Status</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                Account Status
                            </label>
                            <select name="status" 
                                    id="status" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('status') border-red-300 @enderror"
                                    @if($user->id === auth()->id()) onchange="checkSelfDeactivation(this)" @endif>
                                <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="locked" {{ old('status', $user->status) === 'locked' ? 'selected' : '' }}>Locked</option>
                                <option value="pending" {{ old('status', $user->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @if($user->id === auth()->id())
                                <p class="mt-1 text-xs text-yellow-600">Note: You cannot deactivate your own account</p>
                            @endif
                        </div>

                        <!-- Account Actions -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Account Actions</label>
                            <div class="space-y-2">
                                @if($user->failed_login_attempts > 0)
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="reset_failed_attempts" 
                                               value="1"
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">
                                            Reset failed login attempts ({{ $user->failed_login_attempts }})
                                        </span>
                                    </label>
                                @endif
                                
                                @if($user->isLocked())
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="unlock_account" 
                                               value="1"
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">Unlock account</span>
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Management -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Password Management</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                New Password
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('password') border-red-300 @enderror"
                                   placeholder="Leave blank to keep current password">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">
                                Password must be at least 8 characters with uppercase, lowercase, and numbers
                            </p>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm New Password
                            </label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="password_confirmation"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Confirm new password">
                        </div>
                    </div>

                    <!-- Force Password Change -->
                    <div class="mt-4">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="force_password_change" 
                                   value="1"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">
                                Force user to change password on next login
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Security Information -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Security Information</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="font-medium text-gray-700">Last Login</dt>
                                <dd class="text-gray-600">
                                    @if($user->last_login_at)
                                        {{ $user->last_login_at->format('M j, Y g:i A') }}
                                    @else
                                        Never
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-700">Failed Login Attempts</dt>
                                <dd class="text-gray-600">{{ $user->failed_login_attempts }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-700">Password Last Changed</dt>
                                <dd class="text-gray-600">
                                    @if($user->password_changed_at)
                                        {{ $user->password_changed_at->format('M j, Y') }}
                                    @else
                                        Unknown
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-700">Two-Factor Authentication</dt>
                                <dd class="text-gray-600">
                                    @if($user->two_factor_enabled)
                                        <span class="text-green-600">Enabled</span>
                                    @else
                                        <span class="text-gray-500">Disabled</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <div class="flex space-x-3">
                    <a href="{{ route('admin.users.show', $user) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </a>
                </div>
                
                <div class="flex space-x-3">
                    @if($user->status === 'inactive')
                        <button type="button" 
                                onclick="activateUser()"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Activate User
                        </button>
                    @endif
                    
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function checkSelfDeactivation(select) {
    if (select.value === 'inactive') {
        alert('You cannot deactivate your own account. Please ask another administrator to do this.');
        select.value = 'active';
    }
}

function activateUser() {
    document.getElementById('status').value = 'active';
    document.querySelector('form').submit();
}

// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthIndicator = document.getElementById('passwordStrength');
    
    if (password.length === 0) {
        if (strengthIndicator) strengthIndicator.remove();
        return;
    }
    
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 8) strength++;
    else feedback.push('At least 8 characters');
    
    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('One uppercase letter');
    
    if (/[a-z]/.test(password)) strength++;
    else feedback.push('One lowercase letter');
    
    if (/\d/.test(password)) strength++;
    else feedback.push('One number');
    
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
    
    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
    const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    
    let existingIndicator = document.getElementById('passwordStrength');
    if (existingIndicator) existingIndicator.remove();
    
    const indicator = document.createElement('div');
    indicator.id = 'passwordStrength';
    indicator.className = 'mt-2';
    indicator.innerHTML = `
        <div class="flex items-center space-x-2">
            <div class="flex-1 bg-gray-200 rounded-full h-2">
                <div class="${colors[strength - 1]} h-2 rounded-full transition-all duration-300" style="width: ${(strength / 5) * 100}%"></div>
            </div>
            <span class="text-xs font-medium text-gray-600">${labels[strength - 1] || 'Very Weak'}</span>
        </div>
        ${feedback.length > 0 ? `<p class="text-xs text-gray-500 mt-1">Missing: ${feedback.join(', ')}</p>` : ''}
    `;
    
    this.parentNode.appendChild(indicator);
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirmation = document.getElementById('password_confirmation').value;
    
    if (password && password !== passwordConfirmation) {
        e.preventDefault();
        alert('Password confirmation does not match.');
        return false;
    }
    
    return true;
});
</script>
@endsection