@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-3xl font-bold text-gray-900">Create New Role</h1>
            <p class="mt-1 text-sm text-gray-600">Define a new role with specific permissions</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Roles
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Role Information</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.roles.store') }}">
                        @csrf

                        <!-- Role Name -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Role Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder="Enter role name">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Role name should be descriptive and unique. Only letters, numbers, spaces, hyphens, and underscores are allowed.
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Describe the purpose and responsibilities of this role">{{ old('description') }}</textarea>
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
                                                       onchange="toggleCategoryPermissions('{{ $category }}', this.checked)">
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
                                                   {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
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
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Create Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Role Guidelines
                    </h3>
                </div>
                <div class="p-6">
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Best Practices:</h4>
                        <ul class="space-y-2">
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm text-gray-600">Use descriptive role names</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm text-gray-600">Follow principle of least privilege</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm text-gray-600">Group related permissions together</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm text-gray-600">Provide clear descriptions</span>
                            </li>
                        </ul>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Permission Categories:</h4>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-700">Manage:</dt>
                                <dd class="text-sm text-gray-600">Full control permissions</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-700">View:</dt>
                                <dd class="text-sm text-gray-600">Read-only permissions</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-700">General:</dt>
                                <dd class="text-sm text-gray-600">Basic system permissions</dd>
                            </div>
                        </dl>
                    </div>
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

// Auto-check category header when all permissions in category are selected
document.addEventListener('DOMContentLoaded', function() {
    const categoryCheckboxes = document.querySelectorAll('.permission-checkbox');
    
    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const category = this.classList[1].replace('category-', '');
            const categoryCheckboxes = document.querySelectorAll('.category-' + category);
            const checkedCount = document.querySelectorAll('.category-' + category + ':checked').length;
            
            // Update category header checkbox
            const headerCheckbox = document.querySelector(`input[onchange*="${category}"]`);
            if (headerCheckbox) {
                headerCheckbox.checked = checkedCount === categoryCheckboxes.length;
                headerCheckbox.indeterminate = checkedCount > 0 && checkedCount < categoryCheckboxes.length;
            }
        });
    });
});
</script>
@endsection