@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-3xl font-bold text-gray-900">Edit Role: {{ $role->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">Modify role permissions and settings</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('admin.roles.show', $role) }}" class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                View Details
            </a>
            <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Roles
            </a>
        </div>
    </div>

    <!-- System Role Warning -->
    @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-yellow-800">
                    <strong>System Role:</strong> This is a system role. Some modifications may be restricted to maintain system integrity.
                </p>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Role Information</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                        @csrf
                        @method('PUT')

                        <!-- Role Name -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Role Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $role->name) }}" 
                                   required 
                                   {{ in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']) ? 'readonly' : '' }}
                                   placeholder="Enter role name">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                                <p class="mt-1 text-sm text-yellow-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    System role names cannot be modified.
                                </p>
                            @else
                                <p class="mt-1 text-sm text-gray-500">
                                    Role name should be descriptive and unique. Only letters, numbers, spaces, hyphens, and underscores are allowed.
                                </p>
                            @endif
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Describe the purpose and responsibilities of this role">{{ old('description', $role->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Permissions -->
                        <div class="mb-8">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                            <p class="text-sm text-gray-600 mb-4">Select the permissions that users with this role should have:</p>
                            
                            @error('permissions')
                                <div class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4">
                                    <p class="text-sm text-red-800">{{ $message }}</p>
                                </div>
                            @enderror

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach($permissions as $category => $categoryPermissions)
                                <div class="bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-100 rounded-t-lg">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-gray-900 flex items-center">
                                                @if($category === 'manage')
                                                    <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                @elseif($category === 'view')
                                                    <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                                    </svg>
                                                @endif
                                                {{ ucfirst($category) }} Permissions
                                            </h4>
                                            <label class="flex items-center text-xs text-gray-600">
                                                <input type="checkbox" 
                                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-1" 
                                                       onchange="toggleCategoryPermissions('{{ $category }}', this.checked)"
                                                       id="category_{{ $category }}">
                                                Select All
                                            </label>
                                        </div>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        @foreach($categoryPermissions as $permission)
                                        <label class="flex items-start">
                                            <input class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-0.5 mr-3 permission-checkbox category-{{ $category }}" 
                                                   type="checkbox" 
                                                   name="permissions[]" 
                                                   value="{{ $permission->name }}" 
                                                   id="permission_{{ $permission->id }}"
                                                   {{ in_array($permission->name, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                            <div class="flex-1">
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ ucwords(str_replace('-', ' ', $permission->name)) }}
                                                </span>
                                                @if($permission->description)
                                                    <p class="text-xs text-gray-500 mt-1">{{ $permission->description }}</p>
                                                @endif
                                            </div>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                            <a href="{{ route('admin.roles.index') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Role Info Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Role Information
                    </h3>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <dt class="text-sm font-medium text-gray-700">Created:</dt>
                        <dd class="text-sm text-gray-600">{{ $role->created_at->format('M d, Y \a\t g:i A') }}</dd>
                    </div>
                    
                    <div class="mb-4">
                        <dt class="text-sm font-medium text-gray-700">Last Updated:</dt>
                        <dd class="text-sm text-gray-600">{{ $role->updated_at->format('M d, Y \a\t g:i A') }}</dd>
                    </div>
                    
                    <div class="mb-4">
                        <dt class="text-sm font-medium text-gray-700">Users with this role:</dt>
                        <dd class="text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $role->users()->count() }}
                            </span>
                        </dd>
                    </div>
                    
                    <div class="mb-4">
                        <dt class="text-sm font-medium text-gray-700">Current permissions:</dt>
                        <dd class="text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $role->permissions->count() }}
                            </span>
                        </dd>
                    </div>

                    @if($role->users()->count() > 0)
                    <div class="pt-4 border-t border-gray-200">
                        <a href="{{ route('admin.roles.assign-users', $role) }}" class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Manage User Assignments
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Guidelines Card -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        Important Notes
                    </h3>
                </div>
                <div class="p-6">
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-gray-600">Changes take effect immediately</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            <span class="text-gray-600">Affects {{ $role->users()->count() }} user(s)</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-gray-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-gray-600">All changes are logged for audit</span>
                        </li>
                        @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-yellow-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-6v6a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2z"></path>
                            </svg>
                            <span class="text-gray-600">System role - some restrictions apply</span>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCategoryPermissions(category, checked) {
    const checkboxes = document.querySelectorAll('.category-' + category);
    checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
    });
}

// Initialize category checkboxes based on current selections
document.addEventListener('DOMContentLoaded', function() {
    const categories = @json(array_keys($permissions->toArray()));
    
    categories.forEach(category => {
        const categoryCheckboxes = document.querySelectorAll('.category-' + category);
        const checkedCount = document.querySelectorAll('.category-' + category + ':checked').length;
        const headerCheckbox = document.getElementById('category_' + category);
        
        if (headerCheckbox) {
            headerCheckbox.checked = checkedCount === categoryCheckboxes.length;
            headerCheckbox.indeterminate = checkedCount > 0 && checkedCount < categoryCheckboxes.length;
        }
    });

    // Add change listeners
    const categoryCheckboxes = document.querySelectorAll('.permission-checkbox');
    
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const category = this.classList[1].replace('category-', '');
            const categoryCheckboxes = document.querySelectorAll('.category-' + category);
            const checkedCount = document.querySelectorAll('.category-' + category + ':checked').length;
            
            // Update category header checkbox
            const headerCheckbox = document.getElementById('category_' + category);
            if (headerCheckbox) {
                headerCheckbox.checked = checkedCount === categoryCheckboxes.length;
                headerCheckbox.indeterminate = checkedCount > 0 && checkedCount < categoryCheckboxes.length;
            }
        });
    });
});
</script>
@endsection