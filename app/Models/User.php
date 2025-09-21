<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'employee_id',
        'role',
        'department',
        'phone_number',
        'department_id',
        'status',
        'password',
        'last_login_at',
        'password_changed_at',
        'two_factor_enabled',
        'failed_login_attempts',
        'account_locked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'account_locked_until' => 'datetime',
        'two_factor_enabled' => 'boolean',
    ];

    /**
     * Get all activities created by this user.
     */
    public function createdActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'created_by');
    }

    /**
     * Get all activities assigned to this user.
     */
    public function assignedActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'assigned_to');
    }

    /**
     * Get all activity updates made by this user.
     */
    public function activityUpdates(): HasMany
    {
        return $this->hasMany(ActivityUpdate::class);
    }

    /**
     * Get the department that the user belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the department attribute - prioritize relationship over string field.
     */
    public function getDepartmentAttribute($value)
    {
        // If department_id is set and we haven't already loaded the relationship
        if ($this->department_id && !array_key_exists('department', $this->relations)) {
            return $this->getRelationValue('department');
        }
        
        // If the relationship is already loaded, return it
        if (array_key_exists('department', $this->relations)) {
            return $this->relations['department'];
        }
        
        // Otherwise return the string value
        return $value;
    }



    /**
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Scope a query to only include users with a specific role (legacy role field).
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope a query to only include users with a specific Spatie role.
     */
    public function scopeBySpatiRole($query, $roleName)
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Scope a query to only include users from a specific department.
     */
    public function scopeByDepartment($query, $department)
    {
        if (is_numeric($department)) {
            return $query->where('department_id', $department);
        }
        return $query->where('department', $department);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope a query to only include locked users.
     */
    public function scopeLocked($query)
    {
        return $query->where('status', 'locked')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('account_locked_until')
                          ->where('account_locked_until', '>', now());
                    });
    }

    /**
     * Scope a query to only include pending users.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include users with a specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include admin users (legacy role).
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope a query to only include supervisor users (legacy role).
     */
    public function scopeSupervisors($query)
    {
        return $query->where('role', 'supervisor');
    }

    /**
     * Scope a query to only include member users (legacy role).
     */
    public function scopeMembers($query)
    {
        return $query->where('role', 'member');
    }

    /**
     * Scope a query to only include users with failed login attempts.
     */
    public function scopeWithFailedAttempts($query)
    {
        return $query->where('failed_login_attempts', '>', 0);
    }

    /**
     * Scope a query to only include users who have logged in recently.
     */
    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a supervisor.
     */
    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    /**
     * Check if the user is a member.
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * Check if the user can manage activities (admin or supervisor).
     */
    public function canManageActivities(): bool
    {
        return in_array($this->role, ['admin', 'supervisor']);
    }

    /**
     * Check if the user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isLocked();
    }

    /**
     * Check if the user is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Check if the user account is locked.
     */
    public function isLocked(): bool
    {
        return $this->status === 'locked' || 
               ($this->account_locked_until && $this->account_locked_until->isFuture());
    }

    /**
     * Check if the user account is pending activation.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Activate the user account.
     */
    public function activate(): bool
    {
        return $this->update([
            'status' => 'active',
            'account_locked_until' => null,
            'failed_login_attempts' => 0
        ]);
    }

    /**
     * Deactivate the user account.
     */
    public function deactivate(string $reason = null): bool
    {
        $result = $this->update(['status' => 'inactive']);
        
        if ($result && $reason) {
            // Log the deactivation reason if audit logging is available
            if (function_exists('activity')) {
                activity()
                    ->performedOn($this)
                    ->withProperties(['reason' => $reason])
                    ->log('User deactivated');
            }
        }
        
        return $result;
    }

    /**
     * Lock the user account.
     */
    public function lock(int $minutes = null, string $reason = null): bool
    {
        $lockUntil = $minutes ? now()->addMinutes($minutes) : null;
        
        $result = $this->update([
            'status' => 'locked',
            'account_locked_until' => $lockUntil
        ]);
        
        if ($result && $reason) {
            if (function_exists('activity')) {
                activity()
                    ->performedOn($this)
                    ->withProperties(['reason' => $reason, 'locked_until' => $lockUntil])
                    ->log('User account locked');
            }
        }
        
        return $result;
    }

    /**
     * Unlock the user account.
     */
    public function unlock(): bool
    {
        return $this->update([
            'status' => 'active',
            'account_locked_until' => null,
            'failed_login_attempts' => 0
        ]);
    }

    /**
     * Check if the user can access admin features.
     */
    public function canAccessAdmin(): bool
    {
        return $this->hasRole(['Administrator', 'Supervisor']) || $this->isAdmin();
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Increment failed login attempts.
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        $maxAttempts = SystemSetting::get('security_settings.max_login_attempts', 5);
        if ($this->failed_login_attempts >= $maxAttempts) {
            $lockoutDuration = SystemSetting::get('security_settings.lockout_duration', 15);
            $this->update([
                'status' => 'locked',
                'account_locked_until' => now()->addMinutes($lockoutDuration)
            ]);
        }
    }

    /**
     * Reset failed login attempts.
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'last_login_at' => now()
        ]);
        
        if ($this->status === 'locked' && $this->account_locked_until && $this->account_locked_until->isPast()) {
            $this->update([
                'status' => 'active',
                'account_locked_until' => null
            ]);
        }
    }

    /**
     * Check if user has any of the specified roles (supports both legacy and Spatie roles).
     */
    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        // Check Spatie roles first
        try {
            if (method_exists(parent::class, 'hasAnyRole') && parent::hasAnyRole($roles)) {
                return true;
            }
        } catch (\Exception $e) {
            // If Spatie role check fails, continue to legacy check
        }

        // Check legacy role field
        return in_array($this->role, $roles);
    }

    /**
     * Check if user has specific permission (enhanced to work with legacy roles).
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // Check Spatie permissions first
        try {
            if (method_exists(parent::class, 'hasPermissionTo') && parent::hasPermissionTo($permission, $guardName)) {
                return true;
            }
        } catch (\Exception $e) {
            // If Spatie permission check fails, continue to legacy check
        }

        // Fallback to legacy role-based permissions
        return $this->hasLegacyPermission($permission);
    }

    /**
     * Check legacy role-based permissions.
     */
    public function hasLegacyPermission(string $permission): bool
    {
        $rolePermissions = [
            'admin' => [
                'manage-users', 'manage-activities', 'manage-system', 'view-reports',
                'manage-departments', 'manage-roles', 'view-audit-logs'
            ],
            'supervisor' => [
                'manage-activities', 'view-reports', 'manage-team-users'
            ],
            'member' => [
                'view-activities', 'update-own-activities'
            ]
        ];

        $userPermissions = $rolePermissions[$this->role] ?? [];
        return in_array($permission, $userPermissions);
    }

    /**
     * Get all permissions for the user (combining Spatie and legacy).
     */
    public function getAllPermissions()
    {
        $spatiePermissions = collect();
        
        try {
            if (method_exists(parent::class, 'getAllPermissions')) {
                $spatiePermissions = parent::getAllPermissions();
            }
        } catch (\Exception $e) {
            // If Spatie permissions fail, use empty collection
        }
        
        $legacyPermissions = $this->getLegacyPermissions();
        
        return $spatiePermissions->merge($legacyPermissions);
    }

    /**
     * Get legacy permissions based on role.
     */
    public function getLegacyPermissions(): \Illuminate\Support\Collection
    {
        $rolePermissions = [
            'admin' => [
                'manage-users', 'manage-activities', 'manage-system', 'view-reports',
                'manage-departments', 'manage-roles', 'view-audit-logs'
            ],
            'supervisor' => [
                'manage-activities', 'view-reports', 'manage-team-users'
            ],
            'member' => [
                'view-activities', 'update-own-activities'
            ]
        ];

        $permissions = $rolePermissions[$this->role] ?? [];
        return collect($permissions)->map(function ($permission) {
            return (object) ['name' => $permission];
        });
    }

    /**
     * Check if user can manage other users.
     */
    public function canManageUsers(): bool
    {
        return $this->hasPermissionTo('manage-users') || $this->isAdmin();
    }

    /**
     * Check if user can view system reports.
     */
    public function canViewReports(): bool
    {
        return $this->hasPermissionTo('view-reports') || $this->canManageActivities();
    }

    /**
     * Check if user can manage system settings.
     */
    public function canManageSystem(): bool
    {
        return $this->hasPermissionTo('manage-system') || $this->isAdmin();
    }

    /**
     * Get user's primary role name (Spatie role or legacy role).
     */
    public function getPrimaryRoleName(): string
    {
        $spatieRole = $this->roles->first();
        return $spatieRole ? $spatieRole->name : ucfirst($this->role);
    }

    /**
     * Get user's role display name for UI.
     */
    public function getRoleDisplayName(): string
    {
        $roleName = $this->getPrimaryRoleName();
        
        $displayNames = [
            'Administrator' => 'Administrator',
            'Supervisor' => 'Supervisor', 
            'Team Member' => 'Team Member',
            'Read-Only' => 'Read-Only User',
            'Admin' => 'Administrator',
            'Member' => 'Team Member',
            'admin' => 'Administrator',
            'supervisor' => 'Supervisor',
            'member' => 'Team Member'
        ];

        return $displayNames[$roleName] ?? $roleName;
    }
}
