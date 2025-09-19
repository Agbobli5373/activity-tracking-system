<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CacheService
{
    /**
     * Cache key prefixes
     */
    const DASHBOARD_PREFIX = 'dashboard_';
    const REPORT_PREFIX = 'report_';
    const DEPARTMENT_PREFIX = 'dept_stats_';
    const TRENDS_PREFIX = 'trends_';
    const UPDATES_PREFIX = 'recent_updates_';

    /**
     * Clear all dashboard caches for a specific date
     */
    public function clearDashboardCache(string $date): void
    {
        $patterns = [
            self::DASHBOARD_PREFIX . "data_{$date}_*",
            "department_summary_{$date}_*",
            self::UPDATES_PREFIX . $date,
        ];

        foreach ($patterns as $pattern) {
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * Clear all report caches
     */
    public function clearReportCaches(): void
    {
        Cache::forget('report_filter_options');
        $this->clearCacheByPattern(self::REPORT_PREFIX . '*');
        $this->clearCacheByPattern(self::DEPARTMENT_PREFIX . '*');
        $this->clearCacheByPattern(self::TRENDS_PREFIX . '*');
    }

    /**
     * Clear cache when activity is created or updated
     */
    public function clearActivityRelatedCaches(string $date): void
    {
        $this->clearDashboardCache($date);
        $this->clearReportCaches();
    }

    /**
     * Warm up dashboard cache for today
     */
    public function warmUpDashboardCache(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        
        // Pre-load common dashboard queries
        app(DashboardService::class)->getDailyDashboardData($today);
        app(DashboardService::class)->getDailyDashboardData($today, ['status' => 'pending']);
        app(DashboardService::class)->getDailyDashboardData($today, ['status' => 'done']);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        // This would need Redis or another cache store that supports stats
        // For file cache, we can't easily get detailed stats
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
            'estimated_keys' => 'N/A for file driver',
        ];
    }

    /**
     * Clear cache by pattern (simplified implementation)
     * In production, this would need to be implemented based on cache driver
     */
    private function clearCacheByPattern(string $pattern): void
    {
        // For file cache, we'd need to scan the cache directory
        // For Redis, we could use SCAN with pattern matching
        // For now, we'll use a simple flush for demonstration
        Cache::flush();
    }

    /**
     * Set cache with tags (if supported by cache driver)
     */
    public function setWithTags(string $key, $value, array $tags, int $ttl = 3600): void
    {
        if (method_exists(Cache::store(), 'tags')) {
            Cache::tags($tags)->put($key, $value, $ttl);
        } else {
            Cache::put($key, $value, $ttl);
        }
    }

    /**
     * Clear cache by tags (if supported by cache driver)
     */
    public function clearByTags(array $tags): void
    {
        if (method_exists(Cache::store(), 'tags')) {
            Cache::tags($tags)->flush();
        } else {
            Cache::flush();
        }
    }
}