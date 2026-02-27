<?php

namespace App\Traits;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasPermissions
{
    /**
     * Get the permissions directly assigned to the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('assigned_by', 'assigned_at');
    }

    /**
     * Override the can method to integrate with PermissionService.
     * First checks Laravel's default authorization, then checks RBAC permissions.
     *
     * @param string $permission
     * @param array|mixed $arguments
     * @return bool
     */
    public function can($permission, $arguments = []): bool
    {
        // First check Laravel's default authorization (gates, policies)
        if (parent::can($permission, $arguments)) {
            return true;
        }

        // Then check RBAC permissions via PermissionService
        return app(\App\Services\PermissionService::class)->checkPermission($this->id, $permission);
    }

    /**
     * Get all effective permissions for the user.
     * This includes both role-based permissions and direct permissions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions(): Collection
    {
        return app(\App\Services\PermissionService::class)->getUserPermissions($this->id);
    }

    /**
     * Get the effective permissions for the user.
     * This is an alias for getAllPermissions().
     *
     * @return \Illuminate\Support\Collection
     */
    public function getEffectivePermissions(): Collection
    {
        return $this->getAllPermissions();
    }
}
