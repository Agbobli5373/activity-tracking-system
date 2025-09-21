@extends('layouts.app')

@section('title', 'Permission Matrix')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Permission Matrix</h1>
            <p class="mb-0 text-muted">Visual overview of role permissions</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Role
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Roles
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error') || $errors->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') ?? $errors->first('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Permission Matrix Card -->
    <div class="card shadow">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-table"></i> Permission Matrix
                </h6>
                @can('manage-roles')
                <button type="button" class="btn btn-sm btn-success" onclick="savePermissions()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form id="permissionForm" method="POST" action="{{ route('admin.roles.update-permissions') }}">
                @csrf
                
                <!-- Legend -->
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-success rounded me-2" style="width: 20px; height: 20px;"></div>
                            <small>Permission Granted</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-light border rounded me-2" style="width: 20px; height: 20px;"></div>
                            <small>Permission Not Granted</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-shield-alt text-warning me-2"></i>
                            <small>System Role</small>
                        </div>
                    </div>
                </div>

                <!-- Matrix Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 200px;">Permission</th>
                                @foreach($roles as $role)
                                <th class="text-center" style="min-width: 120px;">
                                    <div class="d-flex flex-column align-items-center">
                                        @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                                            <i class="fas fa-shield-alt text-warning mb-1" title="System Role"></i>
                                        @endif
                                        <strong>{{ $role->name }}</strong>
                                        <small class="text-muted">({{ $role->users()->count() }} users)</small>
                                    </div>
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissions as $category => $categoryPermissions)
                            <!-- Category Header -->
                            <tr class="table-secondary">
                                <td colspan="{{ $roles->count() + 1 }}" class="font-weight-bold">
                                    <i class="fas fa-{{ $category === 'manage' ? 'cog' : ($category === 'view' ? 'eye' : 'key') }} me-2"></i>
                                    {{ ucfirst($category) }} Permissions
                                </td>
                            </tr>
                            
                            @foreach($categoryPermissions as $permission)
                            <tr>
                                <td>
                                    <strong>{{ ucwords(str_replace('-', ' ', $permission->name)) }}</strong>
                                    @if($permission->description)
                                        <br><small class="text-muted">{{ $permission->description }}</small>
                                    @endif
                                </td>
                                @foreach($roles as $role)
                                <td class="text-center">
                                    @php
                                        $hasPermission = $role->permissions->contains('name', $permission->name);
                                        $isSystemRole = in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']);
                                    @endphp
                                    
                                    @can('manage-roles')
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input permission-checkbox" 
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
                                    <div class="d-flex justify-content-center">
                                        @if($hasPermission)
                                            <i class="fas fa-check-circle text-success"></i>
                                        @else
                                            <i class="fas fa-times-circle text-muted"></i>
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

                @can('manage-roles')
                <!-- Bulk Actions -->
                <div class="mt-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Bulk Actions:</h6>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAllPermissions()">
                                    <i class="fas fa-check-square"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deselectAllPermissions()">
                                    <i class="fas fa-square"></i> Deselect All
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-success" onclick="savePermissions()">
                                <i class="fas fa-save"></i> Save All Changes
                            </button>
                        </div>
                    </div>
                </div>
                @endcan
            </form>
        </div>
    </div>

    <!-- Role Summary Cards -->
    <div class="row mt-4">
        @foreach($roles as $role)
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-left-{{ in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']) ? 'warning' : 'info' }} shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">
                                {{ $role->name }}
                                @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                                    <i class="fas fa-shield-alt text-warning ms-1" title="System Role"></i>
                                @endif
                            </div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                {{ $role->permissions->count() }} permissions
                            </div>
                            <div class="text-xs text-muted">
                                {{ $role->users()->count() }} users assigned
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-tag fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
let hasChanges = false;

function updatePermissionStatus(checkbox) {
    hasChanges = true;
    
    // Visual feedback
    if (checkbox.checked) {
        checkbox.closest('td').classList.add('table-success');
    } else {
        checkbox.closest('td').classList.remove('table-success');
    }
    
    // Update save button state
    updateSaveButtonState();
}

function updateSaveButtonState() {
    const saveButtons = document.querySelectorAll('button[onclick="savePermissions()"]');
    saveButtons.forEach(button => {
        if (hasChanges) {
            button.classList.remove('btn-success');
            button.classList.add('btn-warning');
            button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Save Changes';
        } else {
            button.classList.remove('btn-warning');
            button.classList.add('btn-success');
            button.innerHTML = '<i class="fas fa-save"></i> Save Changes';
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
            checkbox.closest('td').classList.add('table-success');
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
@endpush

@push('styles')
<style>
.table th, .table td {
    vertical-align: middle;
}

.permission-checkbox {
    transform: scale(1.2);
}

.table-success {
    background-color: rgba(40, 167, 69, 0.1) !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
</style>
@endpush