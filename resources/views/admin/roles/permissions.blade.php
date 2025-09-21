@extends('layouts.app')

@section('title', 'Permission Matrix')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-3xl font-bold text-gray-900">Permission Matrix</h1>
            <p class="mt-1 text-sm text-gray-600">Visual overview of role permissions</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Role
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

    <!-- Permission Matrix Card -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-medium text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0V4a1 1 0 011-1h3M8 3v3h3"></path>
                    </svg>
                    Permission Matrix
                </h3>
                @can('manage-roles')
                <button type="button" 
                        onclick="savePermissions()" 
                        class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Changes
                </button>
                @endcan
            </div>
        </div>
        <div class="p-6">
            <form id="permissionForm" method="POST" action="{{ route('admin.roles.update-permissions') }}">
                @csrf
                
                <!-- Legend -->
                <div class="mb-6">
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="flex items-center">
                            <div class="w-5 h-5 bg-green-500 rounded mr-2"></div>
                            <span class="text-sm text-gray-600">Permission Granted</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-5 h-5 bg-gray-200 border border-gray-300 rounded mr-2"></div>
                            <span class="text-sm text-gray-600">Permission Not Granted</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-6v6a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2z"></path>
                            </svg>
                            <span class="text-sm text-gray-600">System Role</span>
                        </div>
                    </div>
                </div>

                <!-- Matrix Table -->
                <div class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 200px;">Permission</th>
                                    @foreach($roles as $role)
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 120px;">
                                        <div class="flex flex-col items-center">
                                            @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                                                <svg class="w-4 h-4 text-yellow-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="System Role">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-6v6a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2z"></path>
                                                </svg>
                                            @endif
                                            <div class="font-medium text-gray-900">{{ $role->name }}</div>
                                            <div class="text-xs text-gray-500">({{ $role->users()->count() }} users)</div>
                                        </div>
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($permissions as $category => $categoryPermissions)
                                <!-- Category Header -->
                                <tr class="bg-gray-100">
                                    <td colspan="{{ $roles->count() + 1 }}" class="px-6 py-3 text-sm font-medium text-gray-900">
                                        @if($category === 'manage')
                                            <svg class="w-4 h-4 inline mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        @elseif($category === 'view')
                                            <svg class="w-4 h-4 inline mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 inline mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                            </svg>
                                        @endif
                                        {{ ucfirst($category) }} Permissions
                                    </td>
                                </tr>
                                
                                @foreach($categoryPermissions as $permission)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ ucwords(str_replace('-', ' ', $permission->name)) }}</div>
                                        @if($permission->description)
                                            <div class="text-xs text-gray-500 mt-1">{{ $permission->description }}</div>
                                        @endif
                                    </td>
                                    @foreach($roles as $role)
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $hasPermission = $role->permissions->contains('name', $permission->name);
                                            $isSystemRole = in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']);
                                        @endphp
                                        
                                        @can('manage-roles')
                                        <div class="flex justify-center">
                                            <input class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 permission-checkbox" 
                                                   type="checkbox" 
                                                   name="role_permissions[{{ $role->id }}][]" 
                                                   value="{{ $permission->name }}"
                                                   id="permission_{{ $role->id }}_{{ $permission->id }}"
                                                   {{ $hasPermission ? 'checked' : '' }}
                                                   data-role="{{ $role->id }}"
                                                   data-permission="{{ $permission->name }}"
                                                   onchange="updatePermissionStatus(this)">
                                        </div>
                                        @else
                                        <div class="flex justify-center">
                                            @if($hasPermission)
                                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        @endcan
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @can('manage-roles')
                <!-- Bulk Actions -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="mb-4 sm:mb-0">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Bulk Actions:</h4>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" 
                                        onclick="selectAllPermissions()" 
                                        class="inline-flex items-center px-3 py-2 border border-green-300 rounded-lg text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Select All
                                </button>
                                <button type="button" 
                                        onclick="deselectAllPermissions()" 
                                        class="inline-flex items-center px-3 py-2 border border-red-300 rounded-lg text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Deselect All
                                </button>
                            </div>
                        </div>
                        <div>
                            <button type="button" 
                                    onclick="savePermissions()" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Save All Changes
                            </button>
                        </div>
                    </div>
                </div>
                @endcan
            </form>
        </div>
    </div>

    <!-- Role Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">
        @foreach($roles as $role)
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border-l-4 {{ in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']) ? 'border-yellow-400' : 'border-blue-400' }}">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a1.994 1.994 0 01-1.414.586H7a4 4 0 01-4-4v-10a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex items-center">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $role->name }}
                            </div>
                            @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                                <svg class="w-4 h-4 text-yellow-500 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="System Role">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5-6v6a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2z"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="text-lg font-semibold text-gray-900">
                            {{ $role->permissions->count() }} permissions
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $role->users()->count() }} users assigned
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<script>
let hasChanges = false;

function updatePermissionStatus(checkbox) {
    hasChanges = true;
    
    // Visual feedback
    if (checkbox.checked) {
        checkbox.closest('td').classList.add('bg-green-50');
    } else {
        checkbox.closest('td').classList.remove('bg-green-50');
    }
    
    // Update save button state
    updateSaveButtonState();
}

function updateSaveButtonState() {
    const saveButtons = document.querySelectorAll('button[onclick="savePermissions()"]');
    saveButtons.forEach(button => {
        if (hasChanges) {
            button.classList.remove('bg-green-600', 'hover:bg-green-700', 'focus:ring-green-500');
            button.classList.add('bg-yellow-600', 'hover:bg-yellow-700', 'focus:ring-yellow-500');
            button.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path></svg>Save Changes';
        } else {
            button.classList.remove('bg-yellow-600', 'hover:bg-yellow-700', 'focus:ring-yellow-500');
            button.classList.add('bg-green-600', 'hover:bg-green-700', 'focus:ring-green-500');
            button.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Save Changes';
        }
    });
}

function selectAllPermissions() {
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            updatePermissionStatus(checkbox);
        }
    });
}

function deselectAllPermissions() {
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.checked = false;
            updatePermissionStatus(checkbox);
        }
    });
}

function savePermissions() {
    if (!hasChanges) {
        alert('No changes to save.');
        return;
    }
    
    if (confirm('Are you sure you want to save all permission changes? This will affect all users with these roles.')) {
        document.getElementById('permissionForm').submit();
    }
}

// Initialize visual state
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.closest('td').classList.add('bg-green-50');
        }
        
        checkbox.addEventListener('change', function() {
            updatePermissionStatus(this);
        });
    });
});

// Warn about unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        return e.returnValue;
    }
});
</script>
@endsection