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
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Scope a query to only include users with a specific role.
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
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
     * Scope a query to only include users with a specific status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope a query to only include supervisor users.
     */
    public function scopeSupervisors($query)
    {
        return $query->where('role', 'supervisor');
    }

    /**
     * Scope a query to only include member users.
     */
    public function scopeMembers($query)
    {
        return $query->where('role', 'member');
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
        return $this->status === 'active';
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
}
