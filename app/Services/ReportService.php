<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    /**
     * Generate activity report based on criteria
     *
     * @param array $criteria
     * @return array
     */
    public function generateActivityReport(array $criteria): array
    {
        $cacheKey = $this->getReportCacheKey($criteria);
        
        return Cache::remember($cacheKey, 1800, function () use ($criteria) { // 30 minutes cache
            $startDate = Carbon::parse($criteria['start_date'] ?? now()->startOfMonth())->startOfDay();
            $endDate = Carbon::parse($criteria['end_date'] ?? now()->endOfMonth())->endOfDay();
            
            $query = Activity::forReports()
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Apply filters
            if (!empty($criteria['status'])) {
                $query->where('status', $criteria['status']);
            }

            if (!empty($criteria['creator_id'])) {
                $query->where('created_by', $criteria['creator_id']);
            }

            if (!empty($criteria['assignee_id'])) {
                $query->where('assigned_to', $criteria['assignee_id']);
            }

            if (!empty($criteria['priority'])) {
                $query->where('priority', $criteria['priority']);
            }

            if (!empty($criteria['department'])) {
                $query->whereHas('creator', function ($q) use ($criteria) {
                    $q->where('department', $criteria['department']);
                });
            }

            $activities = $query->get();
            
            return [
                'activities' => $activities,
                'statistics' => $this->calculateStatistics($activities, $startDate, $endDate),
                'period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
                'filters' => $criteria
            ];
        });
    }

    /**
     * Calculate activity statistics
     *
     * @param Collection|SupportCollection $activities
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function calculateStatistics(Collection|SupportCollection $activities, Carbon $startDate, Carbon $endDate): array
    {
        $totalActivities = $activities->count();
        $completedActivities = $activities->where('status', 'done')->count();
        $pendingActivities = $activities->where('status', 'pending')->count();
        
        $completionRate = $totalActivities > 0 ? round(($completedActivities / $totalActivities) * 100, 2) : 0;
        
        // Calculate average resolution time for completed activities
        $avgResolutionTime = $this->calculateAverageResolutionTime($activities->where('status', 'done'));
        
        // Activities by priority
        $priorityBreakdown = [
            'high' => $activities->where('priority', 'high')->count(),
            'medium' => $activities->where('priority', 'medium')->count(),
            'low' => $activities->where('priority', 'low')->count(),
        ];
        
        // Activities by user
        $userStats = $this->calculateUserStatistics($activities);
        
        // Daily activity counts
        $dailyStats = $this->calculateDailyStatistics($activities, $startDate, $endDate);
        
        return [
            'total_activities' => $totalActivities,
            'completed_activities' => $completedActivities,
            'pending_activities' => $pendingActivities,
            'completion_rate' => $completionRate,
            'average_resolution_time' => $avgResolutionTime,
            'priority_breakdown' => $priorityBreakdown,
            'user_statistics' => $userStats,
            'daily_statistics' => $dailyStats,
        ];
    }

    /**
     * Calculate average resolution time for completed activities
     *
     * @param Collection|SupportCollection $completedActivities
     * @return float|null
     */
    private function calculateAverageResolutionTime(Collection|SupportCollection $completedActivities): ?float
    {
        if ($completedActivities->isEmpty()) {
            return null;
        }

        $totalResolutionTime = 0;
        $count = 0;

        foreach ($completedActivities as $activity) {
            $completionUpdate = $activity->updates()
                ->where('new_status', 'done')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($completionUpdate) {
                $resolutionTime = $activity->created_at->diffInHours($completionUpdate->created_at);
                $totalResolutionTime += $resolutionTime;
                $count++;
            }
        }

        return $count > 0 ? round($totalResolutionTime / $count, 2) : null;
    }

    /**
     * Calculate user statistics
     *
     * @param Collection|SupportCollection $activities
     * @return array
     */
    private function calculateUserStatistics(Collection|SupportCollection $activities): array
    {
        $userStats = [];

        foreach ($activities->groupBy('created_by') as $userId => $userActivities) {
            $user = $userActivities->first()->creator;
            $completed = $userActivities->where('status', 'done')->count();
            $total = $userActivities->count();
            
            $userStats[] = [
                'user_id' => $userId,
                'user_name' => $user->name,
                'department' => $user->department,
                'total_activities' => $total,
                'completed_activities' => $completed,
                'pending_activities' => $total - $completed,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            ];
        }

        // Sort by total activities descending
        usort($userStats, function ($a, $b) {
            return $b['total_activities'] <=> $a['total_activities'];
        });

        return $userStats;
    }

    /**
     * Calculate daily statistics
     *
     * @param Collection|SupportCollection $activities
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function calculateDailyStatistics(Collection|SupportCollection $activities, Carbon $startDate, Carbon $endDate): array
    {
        $dailyStats = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dayActivities = $activities->filter(function ($activity) use ($current) {
                return $activity->created_at->isSameDay($current);
            });

            $dailyStats[] = [
                'date' => $current->format('Y-m-d'),
                'day_name' => $current->format('l'),
                'total_activities' => $dayActivities->count(),
                'completed_activities' => $dayActivities->where('status', 'done')->count(),
                'pending_activities' => $dayActivities->where('status', 'pending')->count(),
            ];

            $current->addDay();
        }

        return $dailyStats;
    }

    /**
     * Get department statistics
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getDepartmentStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "dept_stats_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate) { // 30 minutes cache
            return DB::table('activities')
                ->join('users', 'activities.created_by', '=', 'users.id')
                ->select(
                    'users.department',
                    DB::raw('COUNT(*) as total_activities'),
                    DB::raw('SUM(CASE WHEN activities.status = "done" THEN 1 ELSE 0 END) as completed_activities'),
                    DB::raw('SUM(CASE WHEN activities.status = "pending" THEN 1 ELSE 0 END) as pending_activities')
                )
                ->whereBetween('activities.created_at', [$startDate, $endDate])
                ->groupBy('users.department')
                ->orderBy('total_activities', 'desc')
                ->get()
                ->map(function ($item) {
                    $item->completion_rate = $item->total_activities > 0 
                        ? round(($item->completed_activities / $item->total_activities) * 100, 2) 
                        : 0;
                    return $item;
                })
                ->toArray();
        });
    }

    /**
     * Get activity trend data for charts
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $groupBy (day, week, month)
     * @return array
     */
    public function getActivityTrends(Carbon $startDate, Carbon $endDate, string $groupBy = 'day'): array
    {
        $cacheKey = "trends_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}_{$groupBy}";
        
        return Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate, $groupBy) { // 30 minutes cache
            $dateFormat = match ($groupBy) {
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                default => '%Y-%m-%d',
            };

            $activities = DB::table('activities')
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status = "done" THEN 1 ELSE 0 END) as completed'),
                    DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            return [
                'labels' => $activities->pluck('period')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Total Activities',
                        'data' => $activities->pluck('total')->toArray(),
                        'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                        'borderColor' => 'rgb(59, 130, 246)',
                    ],
                    [
                        'label' => 'Completed',
                        'data' => $activities->pluck('completed')->toArray(),
                        'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                        'borderColor' => 'rgb(34, 197, 94)',
                    ],
                    [
                        'label' => 'Pending',
                        'data' => $activities->pluck('pending')->toArray(),
                        'backgroundColor' => 'rgba(251, 191, 36, 0.5)',
                        'borderColor' => 'rgb(251, 191, 36)',
                    ],
                ]
            ];
        });
    }

    /**
     * Get filter options for reports
     *
     * @return array
     */
    public function getFilterOptions(): array
    {
        return Cache::remember('report_filter_options', 3600, function () { // 1 hour cache
            return [
                'users' => User::select('id', 'name', 'department')->orderBy('name')->get(),
                'departments' => User::select('department')->distinct()->whereNotNull('department')->orderBy('department')->pluck('department'),
                'statuses' => ['pending', 'done'],
                'priorities' => ['low', 'medium', 'high'],
            ];
        });
    }

    /**
     * Generate cache key for report data
     */
    private function getReportCacheKey(array $criteria): string
    {
        return 'report_' . md5(serialize($criteria));
    }

    /**
     * Clear report caches
     */
    public function clearReportCaches(): void
    {
        Cache::forget('report_filter_options');
        // In production, implement more specific cache clearing
        Cache::flush();
    }
}