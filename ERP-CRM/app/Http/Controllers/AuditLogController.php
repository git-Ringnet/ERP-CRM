<?php

namespace App\Http\Controllers;

use App\Models\PermissionAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Create a new AuditLogController instance.
     */
    public function __construct()
    {
        // Apply CheckPermission middleware (only admins can view audit logs)
        $this->middleware('permission:view_audit_logs');
    }

    /**
     * Display audit logs with filters.
     * Supports filtering by date range, user, action type, and entity type.
     * Requirements: 9.1-9.8
     */
    public function index(Request $request)
    {
        // Validate filter parameters
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'in:created,updated,deleted,assigned,removed,synced'],
            'entity_type' => ['nullable', 'string', 'in:role,permission,user_role,user_permission,role_permission'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        // Build query with filters
        $query = PermissionAuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        // Apply user filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $validated['user_id']);
        }

        // Apply action type filter
        if ($request->filled('action')) {
            $query->where('action', $validated['action']);
        }

        // Apply entity type filter
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $validated['entity_type']);
        }

        // Paginate results
        $perPage = $validated['per_page'] ?? 15;
        $logs = $query->paginate($perPage);

        // Get all users for filter dropdown
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // Available action types
        $actionTypes = [
            'created' => 'Tạo mới',
            'updated' => 'Cập nhật',
            'deleted' => 'Xóa',
            'assigned' => 'Gán',
            'removed' => 'Gỡ bỏ',
            'synced' => 'Đồng bộ',
        ];

        // Available entity types
        $entityTypes = [
            'role' => 'Vai trò',
            'permission' => 'Quyền',
            'user_role' => 'Vai trò người dùng',
            'user_permission' => 'Quyền người dùng',
            'role_permission' => 'Quyền vai trò',
        ];

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'filters' => [
                        'users' => $users,
                        'action_types' => $actionTypes,
                        'entity_types' => $entityTypes,
                    ],
                ],
            ]);
        }

        return view('audit-logs.index', compact(
            'logs',
            'users',
            'actionTypes',
            'entityTypes'
        ));
    }
}
