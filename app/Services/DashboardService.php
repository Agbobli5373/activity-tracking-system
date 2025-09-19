<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get comprehensive dashboard data for a specific date
     */
    public function getDailyDashboardData(string $date, array $filters = []): array
    {
        $cacheKey = $this->getDashboardCacheKey($date, $filters);
        
        return Cache::remember($cacheKey, 300, function () use ($date, $filters) {
            $activities = $this->getFilteredActivities($date, $filters);
            $summary = $this->getActivitySummary($activities);
            $departments = $this->getDepartmentSummary($date, $filters);
            $recentUpdates = $this->getRecentUpdates($date);

            return [
                'activities' => $activities,
                'summary' => $summary,
                'departments' => $departments,
                'recent_updates' => $recentUpdates,
                'date' => $date,
            ];
        });
    }

    /**
     * Get filtered activities for a specific date
     */
    public function getFilteredActivities(string $date, array $filters = []): Collection
    {
        $query = Activity::forDashboard()
            ->whereDate('created_at', $date);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['department'])) {
            $query->whereHas('creator', function ($q) use ($filters) {
                $q->where('department', $filters['department']);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }    /*
*
     * Get activity summary statistics
     */
    public function getActivitySummary(Collection $activities): array
    {
        $statusCounts = $activities->groupBy('status')->map->count();
        
        return [
            'total' => $activities->count(),
            'pending' => $statusCounts->get('pending', 0),
            'done' => $statusCounts->get('done', 0),
            'completion_rate' => $activities->count() > 0 
                ? round(($statusCounts->get('done', 0) / $activities->count()) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get department summary with caching
     */
    public function getDepartmentSummary(string $date, array $filters = []): Collection
    {
        $cacheKey = "department_summary_{$date}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 600, function () use ($date, $filters) {
            $query = Activity::with('creator:id,name,department')
                ->whereDate('created_at', $date);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $activities = $query->get();

            return $activities->groupBy('creator.department')->map(function ($departmentActivities, $department) {
                $statusCounts = $departmentActivities->groupBy('status')->map->count();
                
                return [
                    'department' => $department ?? 'Unknown',
                    'total' => $departmentActivities->count(),
                    'pending' => $statusCounts->get('pending', 0),
                    'done' => $statusCounts->get('done', 0),
                    'completion_rate' => $departmentActivities->count() > 0 
                        ? round(($statusCounts->get('done', 0) / $departmentActivities->count()) * 100, 1)
                        : 0,
                ];
            })->values();
        });
    }    /*
*
     * Get recent activity updates with caching
     */
    public function getRecentUpdates(string $date, string $since = null): Collection
    {
        if ($since) {
            return ActivityUpdate::with(['activity:id,name', 'user:id,name,role'])
                ->whereDate('created_at', $date)
                ->where('created_at', '>', Carbon::parse($since))
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
        }

        $cacheKey = "recent_updates_{$date}";
        
        return Cache::remember($cacheKey, 120, function () use ($date) {
            return ActivityUpdate::with(['activity:id,name', 'user:id,name,role'])
                ->whereDate('created_at', $date)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();
        });
    }

    /**
     * Generate cache key for dashboard data
     */
    private function getDashboardCacheKey(string $date, array $filters = []): string
    {
        $filterString = md5(serialize($filters));
        return "dashboard_data_{$date}_{$filterString}";
    }

    /**
     * Clear dashboard cache for a specific date
     */
    public function clearDashboardCache(string $date): void
    {
        Cache::flush();
    }
}