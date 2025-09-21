<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create permissions
        $permissions = [
            // User Management
            'manage-users',
            'create-users',
            'edit-users',
            'delete-users',
            'view-users',
            
            // Role Management
            'manage-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            'assign-roles',
            
            // Department Management
            'manage-departments',
            'create-departments',
            'edit-departments',
            'delete-departments',
            
            // System Settings
            'manage-system-settings',
            'view-system-settings',
            'edit-system-settings',
            
            // Activity Management (existing)
            'manage-activities',
            'create-activities',
            'edit-activities',
            'delete-activities',
            'view-activities',
            'assign-activities',
            
            // Reports and Analytics
            'view-reports',
            'export-reports',
            'view-audit-logs',
            
            // Profile Management
            'edit-own-profile',
            'change-own-password',
            'manage-own-preferences'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::create(['name' => $permission]);
        }

        // Create roles
        $adminRole = \Spatie\Permission\Models\Role::create(['name' => 'Administrator']);
        $supervisorRole = \Spatie\Permission\Models\Role::create(['name' => 'Supervisor']);
        $memberRole = \Spatie\Permission\Models\Role::create(['name' => 'Team Member']);
        $readOnlyRole = \Spatie\Permission\Models\Role::create(['name' => 'Read-Only']);

        // Assign permissions to Administrator (all permissions)
        $adminRole->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        // Assign permissions to Supervisor
        $supervisorRole->givePermissionTo([
            'view-users',
            'manage-activities',
            'create-activities',
            'edit-activities',
            'delete-activities',
            'view-activities',
            'assign-activities',
            'view-reports',
            'export-reports',
            'edit-own-profile',
            'change-own-password',
            'manage-own-preferences'
        ]);

        // Assign permissions to Team Member
        $memberRole->givePermissionTo([
            'view-activities',
            'create-activities',
            'edit-activities',
            'edit-own-profile',
            'change-own-password',
            'manage-own-preferences'
        ]);

        // Assign permissions to Read-Only
        $readOnlyRole->givePermissionTo([
            'view-activities',
            'edit-own-profile',
            'change-own-password',
            'manage-own-preferences'
        ]);
    }
}
