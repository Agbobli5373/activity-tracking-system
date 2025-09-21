<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'description', 'guard_name'];

    /**
     * Check if this is a system role that cannot be deleted.
     */
    public function isSystemRole(): bool
    {
        $systemRoles = ['Administrator', 'Supervisor', 'Team Member', 'Read-Only'];
        return in_array($this->name, $systemRoles);
    }

    /**
     * Check if the role can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return !$this->isSystemRole() && $this->users()->count() === 0;
    }
}