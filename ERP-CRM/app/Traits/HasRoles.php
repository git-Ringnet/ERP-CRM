<?php

namespace App\Traits;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    /**
     * Get the roles assigned to the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('assigned_by', 'assigned_at');
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $roleName The role slug to check
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('slug', $roleName)->exists();
    }

    /**
     * Check if the user has any of the specified roles.
     *
     * @param array $roleNames Array of role slugs to check
     * @return bool
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles()->whereIn('slug', $roleNames)->exists();
    }

    /**
     * Check if the user has all of the specified roles.
     *
     * @param array $roleNames Array of role slugs to check
     * @return bool
     */
    public function hasAllRoles(array $roleNames): bool
    {
        return $this->roles()->whereIn('slug', $roleNames)->count() === count($roleNames);
    }

    /**
     * Assign a role to the user.
     *
     * @param string|Role $role Role instance or role slug
     * @return void
     */
    public function assignRole(string|Role $role): void
    {
        $roleId = $role instanceof Role ? $role->id : Role::where('slug', $role)->firstOrFail()->id;
        
        $this->roles()->syncWithoutDetaching([
            $roleId => [
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]
        ]);
    }

    /**
     * Remove a role from the user.
     *
     * @param string|Role $role Role instance or role slug
     * @return void
     */
    public function removeRole(string|Role $role): void
    {
        $roleId = $role instanceof Role ? $role->id : Role::where('slug', $role)->firstOrFail()->id;
        
        $this->roles()->detach($roleId);
    }
}
