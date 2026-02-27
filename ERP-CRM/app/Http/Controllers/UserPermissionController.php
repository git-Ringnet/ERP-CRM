<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use App\Repositories\PermissionRepository;
use App\Services\AuditServiceInterface;
use App\Services\CacheServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPermissionController extends Controller
{
    /**
     * Create a new UserPermissionController instance.
     *
     * @param PermissionRepository $permissionRepo
     * @param CacheServiceInterface $cache
     * @param AuditServiceInterface $audit
     */
    public function __construct(
        private PermissionRepository $permissionRepo,
        private CacheServiceInterface $cache,
        private AuditServiceInterface $audit
    ) {
        // Apply CheckPermission middleware to each action
        $this->middleware('permission:view_user_permissions')->only(['show']);
        $this->middleware('permission:assign_user_permissions')->only(['assign']);
        $this->middleware('permission:revoke_user_permissions')->only(['revoke']);
    }

    /**
     * Display user's direct permissions.
     * Requirements: 5.1-5.6
     */
    public function show(Request $request, int $userId)
    {
        $user = User::with(['directPermissions' => function ($query) {
            $query->withPivot('assigned_by', 'assigned_at');
        }])->findOrFail($userId);

        // Get all permissions grouped by module for assignment
        $availablePermissions = Permission::orderBy('module')
            ->orderBy('action')
            ->get()
            ->groupBy('module');

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'direct_permissions' => $user->directPermissions,
                    'available_permissions' => $availablePermissions,
                ],
            ]);
        }

        return view('users.permissions', compact('user', 'availablePermissions'));
    }

    /**
     * Assign direct permission to user.
     * Requirements: 5.1-5.6
     */
    public function assign(Request $request, int $userId)
    {
        // Validate the request
        $validated = $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        try {
            DB::transaction(function () use ($userId, $validated) {
                // Verify user exists
                $user = User::findOrFail($userId);

                // Assign direct permission
                $assignedBy = auth()->id();
                $this->permissionRepo->attachUserPermission(
                    $userId,
                    $validated['permission_id'],
                    $assignedBy
                );

                // Invalidate user's permission cache
                $this->cache->forget(sprintf('user_permissions:%d', $userId));

                // Log assignment
                $this->audit->log(
                    actionType: 'assigned',
                    entityType: 'user_permission',
                    entityId: $userId,
                    data: [
                        'new_value' => [
                            'user_id' => $userId,
                            'permission_id' => $validated['permission_id'],
                        ],
                    ]
                );
            });

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quyền đã được gán cho người dùng thành công.',
                ]);
            }

            return redirect()->route('users.permissions.show', $userId)
                ->with('success', 'Quyền đã được gán cho người dùng thành công.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng hoặc quyền không tồn tại.',
                ], 404);
            }

            return redirect()->back()
                ->with('error', 'Người dùng hoặc quyền không tồn tại.');
        } catch (\Exception $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi gán quyền.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi gán quyền: ' . $e->getMessage());
        }
    }

    /**
     * Remove direct permission from user.
     * Requirements: 5.1-5.6
     */
    public function revoke(Request $request, int $userId, int $permissionId)
    {
        try {
            DB::transaction(function () use ($userId, $permissionId) {
                // Verify user exists
                $user = User::findOrFail($userId);

                // Verify permission exists
                $permission = $this->permissionRepo->findById($permissionId);
                if (!$permission) {
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                        "Permission with ID {$permissionId} not found."
                    );
                }

                // Detach direct permission
                $this->permissionRepo->detachUserPermission($userId, $permissionId);

                // Invalidate user's permission cache
                $this->cache->forget(sprintf('user_permissions:%d', $userId));

                // Log removal
                $this->audit->log(
                    actionType: 'removed',
                    entityType: 'user_permission',
                    entityId: $userId,
                    data: [
                        'old_value' => [
                            'user_id' => $userId,
                            'permission_id' => $permissionId,
                        ],
                    ]
                );
            });

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quyền đã được gỡ bỏ khỏi người dùng thành công.',
                ]);
            }

            return redirect()->route('users.permissions.show', $userId)
                ->with('success', 'Quyền đã được gỡ bỏ khỏi người dùng thành công.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng hoặc quyền không tồn tại.',
                ], 404);
            }

            return redirect()->back()
                ->with('error', 'Người dùng hoặc quyền không tồn tại.');
        } catch (\Exception $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi gỡ bỏ quyền.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi gỡ bỏ quyền: ' . $e->getMessage());
        }
    }
}
