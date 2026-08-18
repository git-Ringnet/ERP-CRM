<?php

namespace App\Policies;

use App\Models\Export;
use App\Models\User;

class ExportPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any exports.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_exports');
    }

    /**
     * Determine whether the user can view the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function view(User $user, Export $export): bool
    {
        if (!$this->checkPermission($user, 'view_exports')) {
            return false;
        }

        // Admin, BOD, PM, PO, Warehouse, Accountant, Legal Team, Order Management see all
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'warehouse_manager', 'warehouse_staff', 'purchase_manager', 'purchase_staff', 'accountant', 'legal_team', 'order_management']) ||
            $user->department === 'PM' ||
            $user->department === 'PO' ||
            $user->department === 'Warehouse') {
            return true;
        }

        // Sales Manager sees team exports (associated with team projects or team sales)
        if ($user->hasRole('sales_manager')) {
            $relatedProject = $export->project;
            if ($relatedProject) {
                if ($relatedProject->manager_id === $user->id || ($relatedProject->manager && $relatedProject->manager->department === $user->department)) {
                    return true;
                }
            }
            $relatedSale = $export->sale;
            if ($relatedSale) {
                if ($relatedSale->user_id === $user->id || ($relatedSale->user && $relatedSale->user->department === $user->department)) {
                    return true;
                }
            }
            return $export->employee_id === $user->id;
        }

        // Standard Sales staff: only see own exports (linked to own project, own sale, or created by self)
        $relatedProject = $export->project;
        if ($relatedProject && $relatedProject->manager_id === $user->id) {
            return true;
        }
        $relatedSale = $export->sale;
        if ($relatedSale && $relatedSale->user_id === $user->id) {
            return true;
        }

        return $export->employee_id === $user->id;
    }

    /**
     * Determine whether the user can create exports.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_exports');
    }

    /**
     * Determine whether the user can update the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function update(User $user, Export $export): bool
    {
        return $this->checkPermission($user, 'edit_exports');
    }

    /**
     * Determine whether the user can delete the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function delete(User $user, Export $export): bool
    {
        return $this->checkPermission($user, 'delete_exports');
    }

    /**
     * Determine whether the user can approve the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function approve(User $user, Export $export): bool
    {
        return $this->checkPermission($user, 'approve_exports');
    }
}
