<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditService;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        AuditService::logModelChange('user_created', $user);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        AuditService::logModelChange('user_updated', $user, $user->getOriginal());
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        AuditService::logModelChange('user_deleted', $user, $user->toArray());
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        AuditService::logModelChange('user_restored', $user);
    }
}