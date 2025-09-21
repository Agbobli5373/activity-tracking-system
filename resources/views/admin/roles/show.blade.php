@extends('layouts.app')

@section('title', 'Role Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                Role: {{ $role->name }}
                @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                    <span class="badge badge-warning ms-2">System Role</span>
                @else
                    <span class="badge badge-info ms-2">Custom Role</span>
                @endif
            </h1>
            <p class="mb-0 text-muted">{{ $role->description ?? 'No description provided' }}</p>
        </div>
        <div class="d-flex gap-2">
            @can('manage-roles')
            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Role
            </a>
            <a href="{{ route('admin.roles.assign-users', $role) }}" class="btn btn-outline-secondary">
                <i class="fas fa-users"></i> Manage Users
            </a>
            @endcan
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Roles
            </a>
        </div>
    </div>

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
                    </div>
                    
                    <div class="mb-3">
                        <strong>Description:</strong><br>
                        <span class="text-muted">{{ $role->description ?? 'No description provided' }}</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Type:</strong><br>
                        @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                            <span class="badge badge-warning">System Role</span>
                        @else
                            <span class="badge badge-info">Custom Role</span>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <strong>Created:</strong><br>
                        <span class="text-muted">{{ $role->created_at->format('M d, Y \a\t g:i A') }}</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Last Updated:</strong><br>
                        <span class="text-muted">{{ $role->updated_at->format('M d, Y \a\t g:i A') }}</span>
                    </div>
                    
                    <div class="mb-0">
                        <strong>Guard:</strong><br>
                        <span class="text-muted">{{ $role->guard_name }}</span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <div class="h4 font-weight-bold text-primary">{{ $role->users->count() }}</div>
                                <div class="text-xs text-uppercase text-muted">Users</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="h4 font-weight-bold text-success">{{ $role->permissions->count() }}</div>
                            <div class="text-xs text-uppercase text-muted">Permissions</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-key"></i> Permissions ({{ $role->permissions->count() }})
                    </h6>
                </div>
                <div class="card-body">
                    @if($role->permissions->count() > 0)
                        @php
                            $groupedPermissions = $role->permissions->groupBy(function ($permission) {
                                $parts = explode('-', $permission->name);
                                return count($parts) > 1 ? $parts[0] : 'general';
                            });
                        @endphp

                        <div class="row">
                            @foreach($groupedPermissions as $category => $permissions)
                            <div class="col-md-6 mb-4">
                                <div class="card border-left-success">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 text-capitalize">
                                            <i class="fas fa-{{ $category === 'manage' ? 'cog' : ($category === 'view' ? 'eye' : 'key') }} me-2"></i>
                                            {{ ucfirst($category) }} Permissions
                                        </h6>
                                    </div>
                                    <div class="card-body py-2">
                                        @foreach($permissions as $permission)
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span>{{ ucwords(str_replace('-', ' ', $permission->name)) }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-key fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted mb-0">No permissions assigned to this role</p>
                            @can('manage-roles')
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-primary mt-2">
                                <i class="fas fa-plus"></i> Add Permissions
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>

            <!-- Users with this Role -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-users"></i> Users with this Role ({{ $role->users->count() }})
                    </h6>
                </div>
                <div class="card-body">
                    @if($role->users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($role->users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <div class="avatar-initial bg-primary rounded-circle">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                </div>
                                                {{ $user->name }}
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
                                            @can('manage-users')
                                            <a href="{{ route('admin.users.show', $user) }}" 
                                               class="btn btn-sm btn-outline-info" title="View User">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.users.edit', $user) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted mb-0">No users assigned to this role</p>
                            @can('manage-roles')
                            <a href="{{ route('admin.roles.assign-users', $role) }}" class="btn btn-sm btn-primary mt-2">
                                <i class="fas fa-plus"></i> Assign Users
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

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

.border-right {
    border-right: 1px solid #e3e6f0 !important;
}
</style>
@endpush