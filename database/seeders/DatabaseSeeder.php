<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Always seed admin user
        $this->call([
            AdminUserSeeder::class,
        ]);

        // Only seed sample data in non-production environments
        if (app()->environment(['local', 'staging', 'testing'])) {
            $this->call([
                SampleDataSeeder::class,
            ]);
        }

        // Production-specific seeders
        if (app()->environment('production')) {
            $this->call([
                ProductionUserSeeder::class,
            ]);
        }
    }
}
