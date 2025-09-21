@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Edit Role: {{ $role->name }}</h1>
            <p class="mb-0 text-muted">Modify role permissions and settings</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-outline-info">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Roles
            </a>
        </div>
    </div>

    <!-- System Role Warning -->
    @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
    <div class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>System Role:</strong> This is a system role. Some modifications may be restricted to maintain system integrity.
    </div>
    @endif

    <!-- Form Card -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Role Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                        @csrf
                        @method('PUT')

                        <!-- Role Name -->
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $role->name) }}" 
                                   required 
                                   {{ in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']) ? 'readonly' : '' }}
                                   placeholder="Enter role name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                                <small class="form-text text-warning">
                                    <i class="fas fa-lock me-1"></i>System role names cannot be modified.
                                </small>
                            @else
                                <small class="form-text text-muted">
                                    Role name should be descriptive and unique. Only letters, numbers, spaces, hyphens, and underscores are allowed.
                                </small>
                            @endif
                        </div>

                        <!-- Description -->
                        <div class="form-group mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Describe the purpose and responsibilities of this role">{{ old('description', $role->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Permissions -->
                        <div class="form-group mb-4">
                            <label class="form-label">Permissions</label>
                            <p class="text-muted small mb-3">Select the permissions that users with this role should have:</p>
                            
                            @error('permissions')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <div class="row">
                                @foreach($permissions as $category => $categoryPermissions)
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header bg-light py-2">
                                            <h6 class="mb-0 text-capitalize">
                                                <i class="fas fa-{{ $category === 'manage' ? 'cog' : ($category === 'view' ? 'eye' : 'key') }} me-2"></i>
                                                {{ ucfirst($category) }} Permissions
                                            </h6>
                                            <small class="text-muted">
                                                <input type="checkbox" class="form-check-input me-1" 
                                                       onchange="toggleCategoryPermissions('{{ $category }}', this.checked)"
                                                       id="category_{{ $category }}">
                                                Select All
                                            </small>
                                        </div>
                                        <div class="card-body py-2">
                                            @foreach($categoryPermissions as $permission)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input permission-checkbox category-{{ $category }}" 
                                                       type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $permission->name }}" 
                                                       id="permission_{{ $permission->id }}"
                                                       {{ in_array($permission->name, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                    <strong>{{ ucwords(str_replace('-', ' ', $permission->name)) }}</strong>
                                                    @if($permission->description)
                                                        <br><small class="text-muted">{{ $permission->description }}</small>
                                                    @endif
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Role
                            </button>
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Role Info Card -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle"></i> Role Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Created:</strong><br>
                        <small class="text-muted">{{ $role->created_at->format('M d, Y \a\t g:i A') }}</small>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Last Updated:</strong><br>
                        <small class="text-muted">{{ $role->updated_at->format('M d, Y \a\t g:i A') }}</small>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Users with this role:</strong><br>
                        <span class="badge badge-primary">{{ $role->users()->count() }}</span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Current permissions:</strong><br>
                        <span class="badge badge-success">{{ $role->permissions->count() }}</span>
                    </div>

                    @if($role->users()->count() > 0)
                    <hr>
                    <div class="mb-0">
                        <a href="{{ route('admin.roles.assign-users', $role) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-users"></i> Manage User Assignments
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Guidelines Card -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-exclamation-triangle"></i> Important Notes
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            Changes take effect immediately
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-users text-primary me-2"></i>
                            Affects {{ $role->users()->count() }} user(s)
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-history text-secondary me-2"></i>
                            All changes are logged for audit
                        </li>
                        @if(in_array($role->name, ['Administrator', 'Supervisor', 'Team Member', 'Read-Only']))
                        <li class="mb-0">
                            <i class="fas fa-shield-alt text-warning me-2"></i>
                            System role - some restrictions apply
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
@endpush