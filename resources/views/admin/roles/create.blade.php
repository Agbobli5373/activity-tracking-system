@extends('layouts.app')

@section('title', 'Create Role')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Create New Role</h1>
            <p class="mb-0 text-muted">Define a new role with specific permissions</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Roles
        </a>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Role Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.roles.store') }}">
                        @csrf

                        <!-- Role Name -->
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder="Enter role name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Role name should be descriptive and unique. Only letters, numbers, spaces, hyphens, and underscores are allowed.
                            </small>
                        </div>

                        <!-- Description -->
                        <div class="form-group mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Describe the purpose and responsibilities of this role">{{ old('description') }}</textarea>
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
                                                       onchange="toggleCategoryPermissions('{{ $category }}', this.checked)">
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
                                                       {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
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
                                <i class="fas fa-save"></i> Create Role
                            </button>
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Card -->
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle"></i> Role Guidelines
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="text-primary">Best Practices:</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Use descriptive role names
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Follow principle of least privilege
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Group related permissions together
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Provide clear descriptions
                        </li>
                    </ul>

                    <hr>

                    <h6 class="text-warning">Permission Categories:</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-1"><strong>Manage:</strong> Full control permissions</li>
                        <li class="mb-1"><strong>View:</strong> Read-only permissions</li>
                        <li class="mb-1"><strong>General:</strong> Basic system permissions</li>
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
@endpush