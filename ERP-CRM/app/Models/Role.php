<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the permissions associated with the role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot('created_at');
    }

    /**
     * Get the users assigned to this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('assigned_by', 'assigned_at');
    }

    /**
     * Scope a query to only include active roles.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Assign a permission to the role.
     *
     * @param \App\Models\Permission $permission
     * @return void
     */
    public function assignPermission(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching([$permission->id => [
            'created_at' => now(),
        ]]);
    }

    /**
     * Remove a permission from the role.
     *
     * @param \App\Models\Permission $permission
     * @return void
     */
    public function removePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
    }

    /**
     * Sync permissions for the role.
     *
     * @param array $permissionIds
     * @return void
     */
    public function syncPermissions(array $permissionIds): void
    {
        $syncData = [];
        foreach ($permissionIds as $permissionId) {
            $syncData[$permissionId] = ['created_at' => now()];
        }
        
        $this->permissions()->sync($syncData);
    }
}
