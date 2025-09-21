# Role and Permission Middleware Usage

This document explains how to use the newly implemented role and permission middleware in the Activity Tracking System.

## Available Middleware

### 1. RoleMiddleware (`role`)

Protects routes based on user roles (supports both Spatie roles and legacy role field).

### 2. PermissionMiddleware (`permission`)

Protects routes based on specific permissions (supports both Spatie permissions and legacy role-based permissions).

## Usage Examples

### Using Role Middleware

```php
// Single role requirement
Route::get('/admin/users', [UserController::class, 'index'])
    ->middleware('role:Administrator');

// Multiple role options (user needs ANY of these roles)
Route::get('/admin/activities', [ActivityController::class, 'index'])
    ->middleware('role:Administrator,Supervisor');

// Legacy role support
Route::get('/admin/legacy', [AdminController::class, 'index'])
    ->middleware('role:admin,supervisor');
```

### Using Permission Middleware

```php
// Single permission requirement
Route::post('/admin/users', [UserController::class, 'store'])
    ->middleware('permission:manage-users');

// Permission with specific guard
Route::get('/api/admin/users', [UserController::class, 'apiIndex'])
    ->middleware('permission:manage-users,web');

// Different permissions for different actions
Route::resource('users', UserController::class)->middleware([
    'index' => 'permission:view-users',
    'create' => 'permission:manage-users',
    'store' => 'permission:manage-users',
    'show' => 'permission:view-users',
    'edit' => 'permission:manage-users',
    'update' => 'permission:manage-users',
    'destroy' => 'permission:manage-users'
]);
```

### Combining Middleware

```php
// Require authentication, specific role, and permission
Route::group(['middleware' => ['auth', 'role:Administrator', 'permission:manage-system']], function () {
    Route::get('/admin/settings', [SettingsController::class, 'index']);
    Route::post('/admin/settings', [SettingsController::class, 'update']);
});

// Multiple middleware options
Route::get('/reports', [ReportController::class, 'index'])
    ->middleware(['auth', 'role:Administrator,Supervisor,Team Member']);
```

## Supported Roles and Permissions

### Default Roles

-   `Administrator` - Full system access
-   `Supervisor` - Activity management and reporting
-   `Team Member` - Basic activity access
-   `Read-Only` - View-only access

### Default Permissions

-   `manage-users` - Create, edit, delete users
-   `manage-activities` - Create, edit, delete activities
-   `manage-system` - System settings and configuration
-   `view-reports` - Access to reports and analytics
-   `manage-departments` - Department management
-   `manage-roles` - Role and permission management
-   `view-audit-logs` - Access to audit logs

## Error Handling

### Web Requests

-   Unauthenticated users are redirected to login page
-   Unauthorized users receive 403 error page
-   Inactive/locked users are logged out and redirected to login

### API Requests (JSON)

-   Unauthenticated: `401 Unauthorized`
-   Insufficient permissions: `403 Forbidden`
-   Inactive/locked account: `403 Forbidden`

## Security Features

### Automatic Logging

Both middleware automatically log unauthorized access attempts with:

-   User information
-   Required roles/permissions
-   Request details (route, URL, IP, user agent)

### Account Status Checking

Both middleware verify:

-   User is authenticated
-   Account is active (not inactive, locked, or pending)
-   Account lockout status

### Legacy Compatibility

Both middleware support:

-   Spatie Laravel Permission package roles and permissions
-   Legacy role field for backward compatibility
-   Graceful fallback between systems

## Testing

Comprehensive unit tests are available:

-   `tests/Unit/Middleware/RoleMiddlewareTest.php`
-   `tests/Unit/Middleware/PermissionMiddlewareTest.php`

Run tests with:

```bash
php artisan test tests/Unit/Middleware/
```
