<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ProductionSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:production-setup {--force : Force setup even if already configured}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the application for production deployment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up Activity Tracking System for production...');

        // Check if already in production
        if (app()->environment('production') && !$this->option('force')) {
            if (!$this->confirm('Application is already in production environment. Continue?')) {
                return 0;
            }
        }

        // Step 1: Environment check
        $this->checkEnvironment();

        // Step 2: Generate application key if needed
        $this->generateAppKey();

        // Step 3: Run migrations
        $this->runMigrations();

        // Step 4: Seed production data
        $this->seedProductionData();

        // Step 5: Optimize application
        $this->optimizeApplication();

        // Step 6: Set up storage and permissions
        $this->setupStorage();

        // Step 7: Clear and cache configurations
        $this->cacheConfigurations();

        // Step 8: Security checks
        $this->performSecurityChecks();

        $this->info('Production setup completed successfully!');
        $this->warn('Please review the following:');
        $this->line('1. Update .env file with production database credentials');
        $this->line('2. Configure Redis connection settings');
        $this->line('3. Set up SSL certificates');
        $this->line('4. Configure backup storage (AWS S3, etc.)');
        $this->line('5. Change default admin passwords');
        $this->line('6. Set up monitoring and alerting');

        return 0;
    }

    /**
     * Check environment requirements
     */
    protected function checkEnvironment()
    {
        $this->info('Checking environment requirements...');

        // Check PHP version
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $this->error('PHP 8.1.0 or higher is required. Current version: ' . PHP_VERSION);
            return;
        }

        // Check required extensions
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $this->error("Required PHP extension '{$extension}' is not loaded.");
                return;
            }
        }

        // Check storage permissions
        $storagePath = storage_path();
        if (!is_writable($storagePath)) {
            $this->error("Storage directory is not writable: {$storagePath}");
            return;
        }

        $this->info('Environment requirements check passed.');
    }

    /**
     * Generate application key if needed
     */
    protected function generateAppKey()
    {
        if (empty(config('app.key'))) {
            $this->info('Generating application key...');
            Artisan::call('key:generate', ['--force' => true]);
            $this->info('Application key generated.');
        } else {
            $this->info('Application key already exists.');
        }
    }

    /**
     * Run database migrations
     */
    protected function runMigrations()
    {
        $this->info('Running database migrations...');
        
        if ($this->confirm('Run database migrations?', true)) {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('Database migrations completed.');
        }
    }

    /**
     * Seed production data
     */
    protected function seedProductionData()
    {
        $this->info('Seeding production data...');
        
        if ($this->confirm('Seed production data (admin users)?', true)) {
            Artisan::call('db:seed', [
                '--class' => 'ProductionUserSeeder',
                '--force' => true
            ]);
            $this->info('Production data seeded.');
        }
    }

    /**
     * Optimize application for production
     */
    protected function optimizeApplication()
    {
        $this->info('Optimizing application...');

        // Clear all caches first
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Cache configurations
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        // Optimize autoloader
        if (file_exists(base_path('composer.json'))) {
            $this->info('Optimizing Composer autoloader...');
            exec('composer install --optimize-autoloader --no-dev', $output, $returnCode);
            if ($returnCode === 0) {
                $this->info('Composer autoloader optimized.');
            } else {
                $this->warn('Failed to optimize Composer autoloader.');
            }
        }

        $this->info('Application optimization completed.');
    }

    /**
     * Set up storage directories and permissions
     */
    protected function setupStorage()
    {
        $this->info('Setting up storage directories...');

        // Create storage directories
        $directories = [
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/app/backups',
            'storage/app/exports',
        ];

        foreach ($directories as $directory) {
            $path = base_path($directory);
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->info("Created directory: {$directory}");
            }
        }

        // Create symbolic link for storage
        if (!File::exists(public_path('storage'))) {
            Artisan::call('storage:link');
            $this->info('Storage symbolic link created.');
        }

        $this->info('Storage setup completed.');
    }

    /**
     * Cache configurations for production
     */
    protected function cacheConfigurations()
    {
        $this->info('Caching configurations...');

        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->info('Configurations cached successfully.');
    }

    /**
     * Perform security checks
     */
    protected function performSecurityChecks()
    {
        $this->info('Performing security checks...');

        $issues = [];

        // Check if debug mode is disabled
        if (config('app.debug')) {
            $issues[] = 'Debug mode is enabled (APP_DEBUG=true)';
        }

        // Check if app key is set
        if (empty(config('app.key'))) {
            $issues[] = 'Application key is not set';
        }

        // Check if default passwords are being used
        if (env('ADMIN_DEFAULT_PASSWORD') === 'ChangeMe123!' || env('SUPERVISOR_DEFAULT_PASSWORD') === 'ChangeMe123!') {
            $issues[] = 'Default passwords are still in use';
        }

        // Check if HTTPS is enforced
        if (!env('FORCE_HTTPS', false)) {
            $issues[] = 'HTTPS is not enforced (FORCE_HTTPS=false)';
        }

        if (empty($issues)) {
            $this->info('Security checks passed.');
        } else {
            $this->warn('Security issues found:');
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
        }
    }
}
