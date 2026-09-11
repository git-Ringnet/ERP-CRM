<?php

namespace App\Policies;

use App\Models\MarketingEvent;
use App\Models\User;

class MarketingEventPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any marketing events.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_marketing_events');
    }

    /**
     * Determine whether the user can view the marketing event.
     */
    public function view(User $user, MarketingEvent $marketingEvent): bool
    {
        if (!$this->checkPermission($user, 'view_marketing_events')) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'marketing', 'marketing_manager'])) {
            return true;
        }

        // Sales may open only an event they created, one they were assigned to,
        // or an event that explicitly invites a customer managed by them.
        return $marketingEvent->created_by === $user->id
            || $marketingEvent->requests()->where('assigned_to', $user->id)->exists()
            || $marketingEvent->customers()->where('am', $user->id)->exists();
    }

    /**
     * Determine whether the user can create marketing events.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_marketing_events');
    }

    /**
     * Determine whether the user can update the marketing event.
     */
    public function update(User $user, MarketingEvent $marketingEvent): bool
    {
        return $this->checkPermission($user, 'edit_marketing_events');
    }

    /**
     * Determine whether the user can delete the marketing event.
     */
    public function delete(User $user, MarketingEvent $marketingEvent): bool
    {
        return $this->checkPermission($user, 'delete_marketing_events');
    }

    /**
     * Determine whether the user can approve the marketing event budget.
     */
    public function approve(User $user, MarketingEvent $marketingEvent): bool
    {
        return $this->checkPermission($user, 'approve_marketing_events');
    }
}
