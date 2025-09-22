<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $supervisorRole = Role::firstOrCreate(['name' => 'Supervisor']);
        $memberRole = Role::firstOrCreate(['name' => 'Team Member']);
        $readOnlyRole = Role::firstOrCreate(['name' => 'Read-Only']);

        // Assign permissions to Administrator (all permissions)
        $adminRole->syncPermissions(Permission::all());

        // Assign permissions to Supervisor
        $supervisorPermissions = [
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
        ];
        $supervisorRole->syncPermissions($supervisorPermissions);

        // Assign permissions to Team Member
        $memberPermissions = [
            'view-activities',
            'create-activities',
            'edit-activities',
            'edit-own-profile',
            'change-own-password',
            'manage-own-preferences'
        ];
        $memberRole->syncPermissions($memberPermissions);

        // Assign permissions to Read-Only
        $readOnlyPermissions = [
            'view-activities',
            'edit-own-profile',
            'change-own-password',
            'manage-own-preferences'
        ];
        $readOnlyRole->syncPermissions($readOnlyPermissions);
    }
}
