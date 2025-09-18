<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get comprehensive dashboard data for a specific date
     */
    public function getDailyDashboardData(string $date, array $filters = []): array
    {
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
    }

    /**
     * Get filtered activities for a specific date
     */
    public function getFilteredActivities(string $date, array $filters = []): Collection
    {
        $query = Activity::with(['creator', 'assignee', 'updates'])
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
    }

    /**
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
     * Get department-wise activity summary
     */
    public function getDepartmentSummary(string $date, array $filters = []): Collection
    {
        $query = Activity::with('creator')
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
                'in_progress' => $statusCounts->get('in_progress', 0),
                'done' => $statusCounts->get('done', 0),
                'completion_rate' => $departmentActivities->count() > 0 
                    ? round(($statusCounts->get('done', 0) / $departmentActivities->count()) * 100, 1)
                    : 0,
            ];
        })->values();
    }

    /**
     * Get handover data for shift transitions
     */
    public function getHandoverData(string $date): array
    {
        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();

        // Get activities that are still pending (need handover)
        $handoverActivities = Activity::with(['creator', 'assignee', 'updates.user'])
            ->where('status', 'pending')
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->whereBetween('created_at', [$startOfDay, $endOfDay])
                      ->orWhereHas('updates', function ($q) use ($startOfDay, $endOfDay) {
                          $q->whereBetween('created_at', [$startOfDay, $endOfDay]);
                      });
            })
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Get completed activities for the day
        $completedActivities = Activity::with('creator')
            ->where('status', 'done')
            ->whereDate('updated_at', $date)
            ->orderBy('updated_at', 'desc')
            ->get();

        // Get critical activities (high priority or overdue)
        $criticalActivities = Activity::with('creator')
            ->where(function ($query) use ($endOfDay) {
                $query->where('priority', 'high')
                      ->orWhere('due_date', '<', $endOfDay);
            })
            ->where('status', 'pending')
            ->orderBy('priority', 'desc')
            ->orderBy('due_date', 'asc')
            ->get();

        return [
            'handover_activities' => $handoverActivities,
            'completed_activities' => $completedActivities,
            'critical_activities' => $criticalActivities,
            'summary' => [
                'handover_count' => $handoverActivities->count(),
                'completed_count' => $completedActivities->count(),
                'critical_count' => $criticalActivities->count(),
            ],
        ];
    }

    /**
     * Get recent activity updates
     */
    public function getRecentUpdates(string $date, string $since = null): Collection
    {
        $query = ActivityUpdate::with(['activity', 'user'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc');

        if ($since) {
            $query->where('created_at', '>', Carbon::parse($since));
        }

        return $query->limit(20)->get();
    }

    /**
     * Get available departments for filtering
     */
    public function getAvailableDepartments(): Collection
    {
        return User::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values();
    }

    /**
     * Get activity statistics for a date range
     */
    public function getActivityStatistics(string $startDate, string $endDate): array
    {
        $activities = Activity::whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ])->get();

        $dailyStats = $activities->groupBy(function ($activity) {
            return $activity->created_at->format('Y-m-d');
        })->map(function ($dayActivities) {
            return $this->getActivitySummary($dayActivities);
        });

        return [
            'daily_stats' => $dailyStats,
            'overall_summary' => $this->getActivitySummary($activities),
            'trend_data' => $this->calculateTrendData($dailyStats),
        ];
    }

    /**
     * Calculate trend data for charts
     */
    private function calculateTrendData(Collection $dailyStats): array
    {
        $dates = $dailyStats->keys()->toArray();
        $totals = $dailyStats->pluck('total')->toArray();
        $completionRates = $dailyStats->pluck('completion_rate')->toArray();

        return [
            'dates' => $dates,
            'totals' => $totals,
            'completion_rates' => $completionRates,
        ];
    }
}