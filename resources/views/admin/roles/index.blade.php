@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Role Management</h1>
            <p class="mb-0 text-muted">Manage system roles and permissions</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.roles.permissions') }}" class="btn btn-outline-primary">
                <i class="fas fa-table"></i> Permission Matrix
            </a>
            @can('manage-roles')
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Role
            </a>
            @endcan
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

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Roles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $roles->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users-cog fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Permissions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $permissions->flatten()->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-key fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Permission Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $permissions->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">System Roles</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $roles->whereIn('name', ['Administrator', 'Supervisor', 'Team Member', 'Read-Only'])->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Roles Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">System Roles</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="rolesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Users Count</th>
                            <th>Permissions Count</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                                        <i class="fas fa-shield-alt text-warning me-2" title="System Role"></i>
                                    @else
                                        <i class="fas fa-user-tag text-info me-2" title="Custom Role"></i>
                                    @endif
                                    <strong>{{ $role->name }}</strong>
                                </div>
                            </td>
                            <td>
                                <span class="text-muted">{{ $role->description ?? 'No description provided' }}</span>
                            </td>
                            <td>
                                <span class="badge badge-primary">{{ $role->users()->count() }}</span>
                            </td>
                            <td>
                                <span class="badge badge-success">{{ $role->permissions->count() }}</span>
                            </td>
                            <td>
                                @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                                    <span class="badge badge-warning">System</span>
                                @else
                                    <span class="badge badge-info">Custom</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.roles.show', $role) }}" 
                                       class="btn btn-sm btn-outline-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @can('manage-roles')
                                    <a href="{{ route('admin.roles.edit', $role) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Edit Role">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <a href="{{ route('admin.roles.assign-users', $role) }}" 
                                       class="btn btn-sm btn-outline-secondary" title="Assign Users">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    
                                    @if(!in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']) && $role->users()->count() === 0)
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete('{{ $role->name }}', '{{ route('admin.roles.destroy', $role) }}')"
                                            title="Delete Role">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-users-cog fa-3x mb-3 text-gray-300"></i>
                                <p class="mb-0">No roles found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the role <strong id="roleNameToDelete"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Role</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#rolesTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "asc" ]],
        "columnDefs": [
            { "orderable": false, "targets": 5 }
        ]
    });
});

function confirmDelete(roleName, deleteUrl) {
    $('#roleNameToDelete').text(roleName);
    $('#deleteForm').attr('action', deleteUrl);
    $('#deleteModal').modal('show');
}
</script>
@endpush