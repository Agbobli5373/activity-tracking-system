<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@activitytracker.com',
            'employee_id' => 'ADMIN001',
            'role' => 'Administrator',
            'department' => 'IT Support',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }
}
