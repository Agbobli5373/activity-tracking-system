@extends('layouts.app')

@section('title', 'Assign Users to Role')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Assign Users to Role: {{ $role->name }}</h1>
            <p class="mb-0 text-muted">Manage user assignments for this role</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-outline-info">
                <i class="fas fa-eye"></i> View Role
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

    <div class="row">
        <!-- Role Information -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Role Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Role Name:</strong><br>
                        <span class="text-muted">{{ $role->name }}</span>
                        @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                            <span class="badge badge-warning ms-2">System Role</span>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <strong>Description:</strong><br>
                        <span class="text-muted">{{ $role->description ?? 'No description provided' }}</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Current Users:</strong><br>
                        <span class="badge badge-primary">{{ count($usersWithRole) }}</span>
                    </div>
                    
                    <div class="mb-0">
                        <strong>Permissions:</strong><br>
                        <span class="badge badge-success">{{ $role->permissions->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Assignment Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Users:</span>
                            <strong>{{ $users->count() }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Users with Role:</span>
                            <strong id="assignedCount">{{ count($usersWithRole) }}</strong>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between">
                            <span>Users without Role:</span>
                            <strong id="unassignedCount">{{ $users->count() - count($usersWithRole) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Assignment Form -->
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="fas fa-users"></i> User Assignments
                        </h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAllUsers()">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deselectAllUsers()">
                                <i class="fas fa-square"></i> Deselect All
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.roles.update-user-assignments', $role) }}" id="assignmentForm">
                        @csrf

                        <!-- Search and Filter -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="userSearch" placeholder="Search users...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="locked">Locked</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>

                        <!-- Users List -->
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleAllUsers(this.checked)">
                                        </th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Current Roles</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    @foreach($users as $user)
                                    <tr class="user-row" 
                                        data-name="{{ strtolower($user->name) }}" 
                                        data-email="{{ strtolower($user->email) }}"
                                        data-status="{{ $user->status }}">
                                        <td>
                                            <input type="checkbox" 
                                                   class="form-check-input user-checkbox" 
                                                   name="user_ids[]" 
                                                   value="{{ $user->id }}"
                                                   {{ in_array($user->id, $usersWithRole) ? 'checked' : '' }}
                                                   onchange="updateCounts()">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <div class="avatar-initial bg-primary rounded-circle">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <strong>{{ $user->name }}</strong>
                                                    @if($user->employee_id)
                                                        <br><small class="text-muted">ID: {{ $user->employee_id }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @if($user->department)
                                                {{ is_object($user->department) ? $user->department->name : $user->department }}
                                            @else
                                                <span class="text-muted">Not assigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($user->status)
                                                @case('active')
                                                    <span class="badge badge-success">Active</span>
                                                    @break
                                                @case('inactive')
                                                    <span class="badge badge-secondary">Inactive</span>
                                                    @break
                                                @case('locked')
                                                    <span class="badge badge-danger">Locked</span>
                                                    @break
                                                @case('pending')
                                                    <span class="badge badge-warning">Pending</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-light">{{ ucfirst($user->status) }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($user->roles->count() > 0)
                                                @foreach($user->roles as $userRole)
                                                    <span class="badge badge-{{ $userRole->name === $role->name ? 'primary' : 'secondary' }} me-1">
                                                        {{ $userRole->name }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">No roles</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <span id="selectedCount">{{ count($usersWithRole) }}</span> of {{ $users->count() }} users selected
                                </small>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Update Assignments
                                </button>
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
@endpush

@push('styles')
<style>
.avatar {
    width: 32px;
    height: 32px;
}

.avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
    color: white;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.user-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.table-responsive {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
}
</style>
@endpush