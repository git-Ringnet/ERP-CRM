<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\RoleServiceInterface;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Create a new PermissionController instance.
     *
     * @param RoleServiceInterface $roleService
     */
    public function __construct(
        private RoleServiceInterface $roleService
    ) {
        // Apply CheckPermission middleware to each action
        $this->middleware('permission:view_permissions')->only(['index', 'matrix']);
        $this->middleware('permission:edit_permissions')->only(['updateMatrix']);
    }

    /**
     * Display all permissions grouped by module.
     * Requirements: 2.1-2.7
     */
    public function index(Request $request)
    {
        // Get all permissions ordered by module and action
        $permissions = Permission::orderBy('module')
            ->orderBy('action')
            ->get();

        // Group permissions by module
        $groupedPermissions = $permissions->groupBy('module');

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $groupedPermissions,
            ]);
        }

        return view('permissions.index', compact('groupedPermissions'));
    }

    /**
     * Display permission matrix (roles × permissions).
     * Requirements: 3.1-3.8
     */
    public function matrix(Request $request)
    {
        // Get all active roles with their permissions
        $roles = Role::with('permissions')
            ->orderBy('name')
            ->get();

        // Get all permissions grouped by module
        $permissions = Permission::orderBy('module')
            ->orderBy('action')
            ->get();

        $groupedPermissions = $permissions->groupBy('module');

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => $roles,
                    'permissions' => $groupedPermissions,
                ],
            ]);
        }

        return view('permissions.matrix', compact('roles', 'groupedPermissions'));
    }

    /**
     * Bulk update role-permission assignments.
     * Requirements: 3.1-3.8
     */
    public function updateMatrix(Request $request)
    {
        // Get all roles to ensure we update all of them (even if no permissions selected)
        $allRoles = Role::pluck('id');
        
        // Get permissions data from request (may be empty for some roles)
        $permissionsData = $request->input('permissions', []);

        try {
            // Loop through each role and update their permissions
            foreach ($allRoles as $roleId) {
                // Get permission IDs for this role (empty array if none selected)
                $permissionIds = $permissionsData[$roleId] ?? [];
                
                // Update permissions for this role
                $this->roleService->assignPermissionsToRole(
                    $roleId,
                    $permissionIds
                );
            }

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quyền đã được cập nhật thành công.',
                ]);
            }

            return redirect()->route('permissions.matrix')
                ->with('success', 'Quyền đã được cập nhật thành công.');
        } catch (\Exception $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi cập nhật quyền.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi cập nhật quyền: ' . $e->getMessage());
        }
    }
}
