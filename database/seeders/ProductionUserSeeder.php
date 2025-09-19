<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create system admin user if it doesn't exist
        if (!User::where('employee_id', 'ADMIN001')->exists()) {
            User::create([
                'name' => 'System Administrator',
                'email' => 'admin@your-domain.com',
                'employee_id' => 'ADMIN001',
                'role' => 'admin',
                'department' => 'IT',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'ChangeMe123!')),
                'email_verified_at' => now(),
            ]);

            $this->command->info('System administrator user created successfully.');
        }

        // Create supervisor user if it doesn't exist
        if (!User::where('employee_id', 'SUP001')->exists()) {
            User::create([
                'name' => 'Support Supervisor',
                'email' => 'supervisor@your-domain.com',
                'employee_id' => 'SUP001',
                'role' => 'supervisor',
                'department' => 'Support',
                'password' => Hash::make(env('SUPERVISOR_DEFAULT_PASSWORD', 'ChangeMe123!')),
                'email_verified_at' => now(),
            ]);

            $this->command->info('Support supervisor user created successfully.');
        }

        $this->command->warn('IMPORTANT: Please change default passwords immediately after deployment!');
    }
}