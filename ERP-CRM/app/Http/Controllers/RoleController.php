<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\RoleServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    /**
     * Create a new RoleController instance.
     *
     * @param RoleServiceInterface $roleService
     */
    public function __construct(
        private RoleServiceInterface $roleService
    ) {
        // Apply CheckPermission middleware to each action
        $this->middleware('permission:view_roles')->only(['index', 'show']);
        $this->middleware('permission:create_roles')->only(['create', 'store']);
        $this->middleware('permission:edit_roles')->only(['edit', 'update']);
        $this->middleware('permission:delete_roles')->only(['destroy']);
    }

    /**
     * Display a listing of roles with pagination.
     * Requirements: 1.1
     */
    public function index(Request $request)
    {
        $query = Role::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $roles = $query->withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $roles,
            ]);
        }

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     * Requirements: 1.2
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Store a newly created role in storage.
     * Validates and creates role via RoleService.
     * Requirements: 1.2, 1.3, 1.4, 1.7
     */
    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:roles,slug', 'regex:/^[a-z0-9_-]+$/'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'slug.regex' => 'Slug chỉ được chứa chữ thường, số, gạch ngang và gạch dưới.',
        ]);

        try {
            $role = $this->roleService->createRole($validated);

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vai trò đã được tạo thành công.',
                    'data' => $role,
                ], 201);
            }

            return redirect()->route('roles.index')
                ->with('success', 'Vai trò đã được tạo thành công.');
        } catch (ValidationException $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Display the specified role.
     * Requirements: 1.1
     */
    public function show(Request $request, int $id)
    {
        $role = Role::with(['permissions', 'users'])->findOrFail($id);

        // Return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $role,
            ]);
        }

        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role.
     * Requirements: 1.5
     */
    public function edit(int $id)
    {
        $role = Role::findOrFail($id);
        return view('roles.edit', compact('role'));
    }

    /**
     * Update the specified role in storage.
     * Validates and updates role via RoleService.
     * Requirements: 1.5, 1.6
     */
    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        // Validation with unique rule ignoring current record
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('roles')->ignore($id), 'regex:/^[a-z0-9_-]+$/'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'slug.regex' => 'Slug chỉ được chứa chữ thường, số, gạch ngang và gạch dưới.',
        ]);

        try {
            $role = $this->roleService->updateRole($id, $validated);

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vai trò đã được cập nhật thành công.',
                    'data' => $role,
                ]);
            }

            return redirect()->route('roles.index')
                ->with('success', 'Vai trò đã được cập nhật thành công.');
        } catch (ValidationException $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Remove the specified role from storage.
     * Deletes role via RoleService (with user check).
     * Requirements: 1.6, 1.7
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $this->roleService->deleteRole($id);

            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vai trò đã được xóa thành công.',
                ]);
            }

            return redirect()->route('roles.index')
                ->with('success', 'Vai trò đã được xóa thành công.');
        } catch (ValidationException $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->route('roles.index')
                ->with('error', 'Không thể xóa vai trò này vì còn người dùng được gán.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return JSON for API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vai trò không tồn tại.',
                ], 404);
            }

            return redirect()->route('roles.index')
                ->with('error', 'Vai trò không tồn tại.');
        }
    }
}
