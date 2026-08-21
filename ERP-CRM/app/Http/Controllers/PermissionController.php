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
            // Super admin bypasses all permission checks and is not configurable.
            ->where('slug', '!=', 'super_admin')
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
        // Super admin always has full access and must not be configurable from the matrix.
        // Update every other role, including roles with no selected permissions.
        $allRoles = Role::where('slug', '!=', 'super_admin')->pluck('id');

        /*
         * The matrix can contain thousands of checkboxes. Sending them as
         * permissions[role_id][] may exceed PHP's max_input_vars limit, causing
         * PHP to silently discard part of the request. Because this action syncs
         * every role, that previously resulted in permissions being removed.
         *
         * The UI now sends one JSON field. Keep the old payload as a fallback
         * for compatibility, but never treat a malformed JSON payload as an
         * empty matrix.
         */
        if ($request->has('permissions_json')) {
            $permissionsData = json_decode($request->input('permissions_json'), true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($permissionsData)) {
                return redirect()->back()->with('error', 'Dữ liệu phân quyền không hợp lệ. Quyền chưa được thay đổi.');
            }
        } else {
            $permissionsData = $request->input('permissions', []);
        }

        $validPermissionIds = Permission::pluck('id')->flip();

        foreach ($permissionsData as $roleId => $permissionIds) {
            if (!$allRoles->contains((int) $roleId) || !is_array($permissionIds)) {
                return redirect()->back()->with('error', 'Dữ liệu phân quyền không hợp lệ. Quyền chưa được thay đổi.');
            }

            foreach ($permissionIds as $permissionId) {
                if (!$validPermissionIds->has((int) $permissionId)) {
                    return redirect()->back()->with('error', 'Dữ liệu phân quyền không hợp lệ. Quyền chưa được thay đổi.');
                }
            }
        }

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
