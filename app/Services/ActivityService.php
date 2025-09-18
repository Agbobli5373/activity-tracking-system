<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use Illuminate\Http\Request;

class ActivityService
{
    /**
     * Update activity status with audit trail
     *
     * @param Activity $activity
     * @param string $newStatus
     * @param string $remarks
     * @param Request $request
     * @return bool
     */
    public function updateStatus(Activity $activity, string $newStatus, string $remarks, Request $request): bool
    {
        $previousStatus = $activity->status;
        
        // Update the activity status
        $activity->update(['status' => $newStatus]);
        
        // Create audit trail entry
        ActivityUpdate::createAuditEntry(
            $activity->id,
            auth()->id(),
            $previousStatus,
            $newStatus,
            $remarks,
            $request->ip(),
            $request->userAgent()
        );
        
        return true;
    }
    
    /**
     * Get activity status history
     *
     * @param Activity $activity
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStatusHistory(Activity $activity)
    {
        return $activity->updates()
            ->with('user')
            ->chronological()
            ->get();
    }
    
    /**
     * Check if user can update activity status
     *
     * @param Activity $activity
     * @param \App\Models\User|null $user
     * @return bool
     */
    public function canUpdateStatus(Activity $activity, $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Users can update status of activities they created or are assigned to
        // Admins and supervisors can update any activity status
        return $user->canManageActivities() ||
               $activity->created_by === $user->id ||
               $activity->assigned_to === $user->id;
    }
    
    /**
     * Get available status transitions for an activity
     *
     * @param Activity $activity
     * @return array
     */
    public function getAvailableStatusTransitions(Activity $activity): array
    {
        $currentStatus = $activity->status;
        
        $transitions = [
            'pending' => ['done'],
            'done' => ['pending']
        ];
        
        return $transitions[$currentStatus] ?? [];
    }
}