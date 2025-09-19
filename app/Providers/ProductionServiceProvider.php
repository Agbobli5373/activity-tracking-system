<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ProductionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register production-specific services
        if ($this->app->environment('production')) {
            $this->registerProductionServices();
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->environment('production')) {
            $this->configureProductionSettings();
        }
    }

    /**
     * Register production-specific services
     *
     * @return void
     */
    protected function registerProductionServices()
    {
        // Force HTTPS in production
        if (env('FORCE_HTTPS', true)) {
            URL::forceScheme('https');
        }

        // Configure database query logging for performance monitoring
        if (env('LOG_QUERIES', false)) {
            DB::listen(function ($query) {
                if ($query->time > 1000) { // Log slow queries (>1 second)
                    Log::channel('performance')->warning('Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }
    }

    /**
     * Configure production-specific settings
     *
     * @return void
     */
    protected function configureProductionSettings()
    {
        // Set security headers
        $this->app->make('router')->pushMiddlewareToGroup('web', \App\Http\Middleware\SecurityHeadersMiddleware::class);

        // Configure session settings
        config([
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'strict',
        ]);

        // Configure cache settings
        config([
            'cache.default' => 'redis',
            'session.driver' => 'redis',
            'queue.default' => 'redis',
        ]);

        // Configure logging
        config([
            'logging.default' => 'daily',
            'logging.channels.daily.days' => env('LOG_RETENTION_DAYS', 14),
        ]);

        // Disable debug mode and telescope in production
        config([
            'app.debug' => false,
            'telescope.enabled' => env('TELESCOPE_ENABLED', false),
        ]);

        // Configure opcache if enabled
        if (env('OPCACHE_ENABLE', true) && function_exists('opcache_get_status')) {
            ini_set('opcache.enable', 1);
            ini_set('opcache.memory_consumption', env('OPCACHE_MEMORY_CONSUMPTION', 256));
            ini_set('opcache.max_accelerated_files', env('OPCACHE_MAX_ACCELERATED_FILES', 20000));
            ini_set('opcache.validate_timestamps', 0); // Disable in production for better performance
        }
    }
}