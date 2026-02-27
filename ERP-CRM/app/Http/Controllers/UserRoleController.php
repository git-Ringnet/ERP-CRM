<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\RoleServiceInterface;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    /**
     * Create a new UserRoleController instance.
     *
     * @param RoleServiceInterface $roleService
     */
    public function __construct(
        private RoleServiceInterface $roleService
    ) {
        // Apply CheckPermission middleware to each action
        $this->middleware('permission:view_user_roles')->only(['show']);
        $this->middleware('permission:assign_user_roles')->only(['assign', 'sync']);
        $this->middleware('permission:revoke_user_roles')->only(['revoke']);
    }

    /**
     * Display user's roles and available roles.
     * Requirements: 4.1-4.8
     */
    public function show(Request $request, int $userId)
    {
        $user = User::with(['roles' => function ($query) {
            $query->withPivot('assigned_by', 'assigned_at');
        }])->findOrFail($userId);

        // Get all active roles for assignment
        $availableRoles = Role::active()->orderBy('name')->get();

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'current_roles' => $user->roles,
                    'available_roles' => $availableRoles,
                ],
            ]);
        }

        return view('users.roles', compact('user', 'availableRoles'));
    }

    /**
     * Assign role to user via RoleService.
     * Requirements: 4.1-4.8
     */
    public function assign(Request $request, int $userId)
    {
        // Validate the request
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        try {
            $this->roleService->assignRoleToUser($userId, $validated['role_id']);

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vai trò đã được gán cho người dùng thành công.',
                ]);
            }

            return redirect()->route('users.roles.show', $userId)
                ->with('success', 'Vai trò đã được gán cho người dùng thành công.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng hoặc vai trò không tồn tại.',
                ], 404);
            }

            return redirect()->back()
                ->with('error', 'Người dùng hoặc vai trò không tồn tại.');
        } catch (\Exception $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi gán vai trò.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi gán vai trò: ' . $e->getMessage());
        }
    }

    /**
     * Remove role from user via RoleService.
     * Requirements: 4.1-4.8
     */
    public function revoke(Request $request, int $userId, int $roleId)
    {
        try {
            $this->roleService->removeRoleFromUser($userId, $roleId);

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vai trò đã được gỡ bỏ khỏi người dùng thành công.',
                ]);
            }

            return redirect()->route('users.roles.show', $userId)
                ->with('success', 'Vai trò đã được gỡ bỏ khỏi người dùng thành công.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng hoặc vai trò không tồn tại.',
                ], 404);
            }

            return redirect()->back()
                ->with('error', 'Người dùng hoặc vai trò không tồn tại.');
        } catch (\Exception $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi gỡ bỏ vai trò.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi gỡ bỏ vai trò: ' . $e->getMessage());
        }
    }

    /**
     * Sync user's roles via RoleService.
     * Requirements: 4.1-4.8
     */
    public function sync(Request $request, int $userId)
    {
        // Validate the request
        $validated = $request->validate([
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        try {
            $this->roleService->syncUserRoles($userId, $validated['role_ids']);

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vai trò của người dùng đã được đồng bộ thành công.',
                ]);
            }

            return redirect()->route('users.roles.show', $userId)
                ->with('success', 'Vai trò của người dùng đã được đồng bộ thành công.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng không tồn tại.',
                ], 404);
            }

            return redirect()->back()
                ->with('error', 'Người dùng không tồn tại.');
        } catch (\Exception $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi đồng bộ vai trò.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi đồng bộ vai trò: ' . $e->getMessage());
        }
    }
}
