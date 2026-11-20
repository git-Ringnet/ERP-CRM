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
        return $this->checkPermission($user, 'view_marketing_events')
            || $user->hasAnyRole(['order_management', 'director', 'super_admin']);
    }

    /**
     * Determine whether the user can view the marketing event.
     */
    public function view(User $user, MarketingEvent $marketingEvent): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'order_management', 'marketing', 'marketing_manager', 'sales_manager'])) {
            return true;
        }

        if ($marketingEvent->created_by === $user->id
            || $marketingEvent->requests()->where('assigned_to', $user->id)->exists()) {
            return true;
        }

        // Allow members of a support_team requested to collaborate on this event (MKT-EVENT-006)
        $teamRoleMap = [
            'technical' => ['technical_lead', 'technical_engineer'],
            'sales' => ['sales_manager', 'sales_staff'],
            'accounting' => ['accountant'],
            'po' => ['purchase_manager', 'purchase_staff'],
            'purchasing' => ['purchase_manager', 'purchase_staff'],
            'warehouse' => ['warehouse_manager', 'warehouse_staff'],
            'marketing' => ['marketing', 'marketing_manager'],
        ];
        $userTeams = [];
        foreach ($teamRoleMap as $team => $roles) {
            if ($user->hasAnyRole($roles)) {
                $userTeams[] = $team;
            }
        }
        if (!empty($userTeams) && $marketingEvent->requests()->whereIn('support_team', $userTeams)->exists()) {
            return true;
        }

        if (!$this->checkPermission($user, 'view_marketing_events')) {
            return false;
        }

        // Sales may view:
        // 1. Events marked as public/company-wide for all sales to invite clients
        // 2. Events created by themselves
        // 3. Events where they are assigned requests/tasks
        // 4. Events inviting customers managed by them (AM)
        return (bool) $marketingEvent->is_public_to_sales
            || $marketingEvent->customers()->where(function ($q) use ($user) {
                $q->where('am', $user->id)
                  ->orWhere('am', 'like', '%' . $user->name . '%');
            })->exists();
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
        return $this->checkPermission($user, 'approve_marketing_events')
            || $user->hasAnyRole(['order_management', 'director', 'super_admin']);
    }
}
