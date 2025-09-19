<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CacheService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = new CacheService();
        Cache::flush(); // Start with clean cache
    }

    public function test_cache_service_constants()
    {
        $this->assertEquals('dashboard_', CacheService::DASHBOARD_PREFIX);
        $this->assertEquals('report_', CacheService::REPORT_PREFIX);
        $this->assertEquals('dept_stats_', CacheService::DEPARTMENT_PREFIX);
        $this->assertEquals('trends_', CacheService::TRENDS_PREFIX);
        $this->assertEquals('recent_updates_', CacheService::UPDATES_PREFIX);
    }

    public function test_clear_dashboard_cache()
    {
        $date = '2024-01-01';
        
        // Set some test cache entries
        Cache::put('dashboard_data_' . $date . '_test', 'test_data', 60);
        Cache::put('department_summary_' . $date . '_test', 'dept_data', 60);
        Cache::put('recent_updates_' . $date, 'updates_data', 60);
        Cache::put('unrelated_cache_key', 'other_data', 60);

        $this->cacheService->clearDashboardCache($date);

        // Since the implementation uses Cache::flush(), all cache should be cleared
        $this->assertFalse(Cache::has('dashboard_data_' . $date . '_test'));
        $this->assertFalse(Cache::has('department_summary_' . $date . '_test'));
        $this->assertFalse(Cache::has('recent_updates_' . $date));
        $this->assertFalse(Cache::has('unrelated_cache_key'));
    }

    public function test_clear_report_caches()
    {
        // Set some test cache entries
        Cache::put('report_filter_options', 'filter_data', 60);
        Cache::put('report_test_data', 'report_data', 60);
        Cache::put('dept_stats_test', 'dept_data', 60);
        Cache::put('trends_test', 'trends_data', 60);
        Cache::put('unrelated_cache_key', 'other_data', 60);

        $this->cacheService->clearReportCaches();

        // Since the implementation uses Cache::flush(), all cache should be cleared
        $this->assertFalse(Cache::has('report_filter_options'));
        $this->assertFalse(Cache::has('report_test_data'));
        $this->assertFalse(Cache::has('dept_stats_test'));
        $this->assertFalse(Cache::has('trends_test'));
        $this->assertFalse(Cache::has('unrelated_cache_key'));
    }

    public function test_clear_activity_related_caches()
    {
        $date = '2024-01-01';
        
        // Set some test cache entries
        Cache::put('dashboard_data_' . $date . '_test', 'dashboard_data', 60);
        Cache::put('report_test_data', 'report_data', 60);
        Cache::put('unrelated_cache_key', 'other_data', 60);

        $this->cacheService->clearActivityRelatedCaches($date);

        // Since both clearDashboardCache and clearReportCaches use Cache::flush()
        $this->assertFalse(Cache::has('dashboard_data_' . $date . '_test'));
        $this->assertFalse(Cache::has('report_test_data'));
        $this->assertFalse(Cache::has('unrelated_cache_key'));
    }

    public function test_warm_up_dashboard_cache()
    {
        // Mock the DashboardService
        $dashboardService = $this->createMock(DashboardService::class);
        $today = Carbon::today()->format('Y-m-d');

        // Expect the service to be called 3 times with different filters
        $dashboardService->expects($this->exactly(3))
            ->method('getDailyDashboardData')
            ->withConsecutive(
                [$today],
                [$today, ['status' => 'pending']],
                [$today, ['status' => 'done']]
            )
            ->willReturn(['test' => 'data']);

        // Bind the mock to the container
        $this->app->instance(DashboardService::class, $dashboardService);

        $this->cacheService->warmUpDashboardCache();

        // The test passes if the mock expectations are met
        $this->assertTrue(true);
    }

    public function test_get_cache_stats()
    {
        $stats = $this->cacheService->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_driver', $stats);
        $this->assertArrayHasKey('cache_prefix', $stats);
        $this->assertArrayHasKey('estimated_keys', $stats);
        
        $this->assertEquals(config('cache.default'), $stats['cache_driver']);
        $this->assertEquals(config('cache.prefix'), $stats['cache_prefix']);
        $this->assertEquals('N/A for file driver', $stats['estimated_keys']);
    }

    public function test_set_with_tags_without_tag_support()
    {
        $key = 'test_key';
        $value = 'test_value';
        $tags = ['tag1', 'tag2'];
        $ttl = 3600;

        // For file cache driver (default in testing), tags are not supported
        // So it should fall back to regular Cache::put
        $this->cacheService->setWithTags($key, $value, $tags, $ttl);

        $this->assertEquals($value, Cache::get($key));
    }

    public function test_clear_by_tags_without_tag_support()
    {
        $tags = ['tag1', 'tag2'];
        
        // Set some test data
        Cache::put('test_key1', 'value1', 60);
        Cache::put('test_key2', 'value2', 60);

        // For file cache driver, this should fall back to Cache::flush()
        $this->cacheService->clearByTags($tags);

        // All cache should be cleared
        $this->assertFalse(Cache::has('test_key1'));
        $this->assertFalse(Cache::has('test_key2'));
    }

    public function test_cache_key_patterns()
    {
        $date = '2024-01-01';
        
        // Test that the cache service works with expected key patterns
        $dashboardKey = CacheService::DASHBOARD_PREFIX . "data_{$date}_test";
        $reportKey = CacheService::REPORT_PREFIX . 'test_report';
        $departmentKey = CacheService::DEPARTMENT_PREFIX . 'test_dept';
        $trendsKey = CacheService::TRENDS_PREFIX . 'test_trends';
        $updatesKey = CacheService::UPDATES_PREFIX . $date;

        // Set cache entries
        Cache::put($dashboardKey, 'dashboard_data', 60);
        Cache::put($reportKey, 'report_data', 60);
        Cache::put($departmentKey, 'dept_data', 60);
        Cache::put($trendsKey, 'trends_data', 60);
        Cache::put($updatesKey, 'updates_data', 60);

        // Verify they exist
        $this->assertTrue(Cache::has($dashboardKey));
        $this->assertTrue(Cache::has($reportKey));
        $this->assertTrue(Cache::has($departmentKey));
        $this->assertTrue(Cache::has($trendsKey));
        $this->assertTrue(Cache::has($updatesKey));

        // Clear dashboard cache
        $this->cacheService->clearDashboardCache($date);

        // All should be cleared due to Cache::flush() implementation
        $this->assertFalse(Cache::has($dashboardKey));
        $this->assertFalse(Cache::has($reportKey));
        $this->assertFalse(Cache::has($departmentKey));
        $this->assertFalse(Cache::has($trendsKey));
        $this->assertFalse(Cache::has($updatesKey));
    }

    public function test_cache_service_integration_with_different_dates()
    {
        $date1 = '2024-01-01';
        $date2 = '2024-01-02';
        
        // Set cache for different dates
        Cache::put("dashboard_data_{$date1}_test", 'data1', 60);
        Cache::put("dashboard_data_{$date2}_test", 'data2', 60);
        Cache::put("recent_updates_{$date1}", 'updates1', 60);
        Cache::put("recent_updates_{$date2}", 'updates2', 60);

        // Clear cache for date1
        $this->cacheService->clearDashboardCache($date1);

        // Due to Cache::flush() implementation, all cache is cleared
        $this->assertFalse(Cache::has("dashboard_data_{$date1}_test"));
        $this->assertFalse(Cache::has("dashboard_data_{$date2}_test"));
        $this->assertFalse(Cache::has("recent_updates_{$date1}"));
        $this->assertFalse(Cache::has("recent_updates_{$date2}"));
    }

    public function test_cache_service_handles_empty_cache()
    {
        // Ensure cache is empty
        Cache::flush();

        // These operations should not throw errors on empty cache
        $this->cacheService->clearDashboardCache('2024-01-01');
        $this->cacheService->clearReportCaches();
        $this->cacheService->clearActivityRelatedCaches('2024-01-01');

        // Should complete without errors
        $this->assertTrue(true);
    }

    public function test_set_with_tags_with_custom_ttl()
    {
        $key = 'test_key_ttl';
        $value = 'test_value_ttl';
        $tags = ['tag1'];
        $customTtl = 1800; // 30 minutes

        $this->cacheService->setWithTags($key, $value, $tags, $customTtl);

        $this->assertEquals($value, Cache::get($key));
        
        // We can't easily test TTL with file cache, but we can verify the value is set
        $this->assertTrue(Cache::has($key));
    }

    public function test_cache_service_method_chaining_behavior()
    {
        $date = '2024-01-01';
        
        // Set some initial cache
        Cache::put('test_key', 'test_value', 60);
        
        // Chain multiple cache operations
        $this->cacheService->clearDashboardCache($date);
        $this->cacheService->clearReportCaches();
        $this->cacheService->clearActivityRelatedCaches($date);

        // All operations should complete successfully
        $this->assertFalse(Cache::has('test_key'));
    }

    public function test_warm_up_dashboard_cache_uses_current_date()
    {
        $expectedDate = Carbon::today()->format('Y-m-d');
        
        // Create a partial mock to verify the date being used
        $dashboardService = $this->createMock(DashboardService::class);
        
        $dashboardService->expects($this->exactly(3))
            ->method('getDailyDashboardData')
            ->with($this->callback(function ($date) use ($expectedDate) {
                return $date === $expectedDate;
            }))
            ->willReturn(['test' => 'data']);

        $this->app->instance(DashboardService::class, $dashboardService);

        $this->cacheService->warmUpDashboardCache();
        
        // Test passes if mock expectations are met
        $this->assertTrue(true);
    }
}