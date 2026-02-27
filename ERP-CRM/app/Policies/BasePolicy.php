<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

abstract class BasePolicy
{
    /**
     * The permission service instance.
     *
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * Create a new policy instance.
     *
     * @param PermissionService $permissionService
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Check if a user has a specific permission.
     *
     * @param User $user
     * @param string $permission Permission slug
     * @return bool
     */
    protected function checkPermission(User $user, string $permission): bool
    {
        return $this->permissionService->checkPermission($user->id, $permission);
    }
}
