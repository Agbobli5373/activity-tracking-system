<?php

namespace App\Observers;

use App\Models\Activity;
use App\Services\CacheService;
use Carbon\Carbon;

class ActivityObserver
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Activity "created" event.
     */
    public function created(Activity $activity): void
    {
        $this->clearRelatedCaches($activity);
    }

    /**
     * Handle the Activity "updated" event.
     */
    public function updated(Activity $activity): void
    {
        $this->clearRelatedCaches($activity);
        
        // If the activity was updated today, also clear today's cache
        if ($activity->updated_at->isToday()) {
            $today = Carbon::today()->format('Y-m-d');
            $this->cacheService->clearDashboardCache($today);
        }
    }

    /**
     * Handle the Activity "deleted" event.
     */
    public function deleted(Activity $activity): void
    {
        $this->clearRelatedCaches($activity);
    }

    /**
     * Clear caches related to the activity
     */
    private function clearRelatedCaches(Activity $activity): void
    {
        $date = $activity->created_at->format('Y-m-d');
        $this->cacheService->clearActivityRelatedCaches($date);
    }
}