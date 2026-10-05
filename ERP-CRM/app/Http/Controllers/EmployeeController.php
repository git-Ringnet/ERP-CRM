<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ExportService;
use App\Imports\EmployeesImport;
use App\Exports\EmployeesExport;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees with search and filter functionality.
     * Requirements: 3.1, 3.9, 3.10
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        
        $query = User::whereNotNull('employee_code')->where('is_hidden', false)->with('roles');

        // Search functionality (Requirement 3.9)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Filter by department (Requirement 3.10)
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get unique departments for filter dropdown
        $departments = User::whereNotNull('employee_code')
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        $availableRoles = \App\Models\Role::active()->orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments', 'availableRoles'));
    }

    /**
     * Show the form for creating a new employee.
     * Requirements: 3.2
     */
    public function create()
    {
        $this->authorize('create', User::class);
        
        $workLocations = \App\Models\WorkLocation::where('is_active', true)->get();
        return view('employees.create', compact('workLocations'));
    }

    /**
     * Store a newly created employee in storage.
     * Requirements: 3.3, 3.4
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        
        // Pre-process numeric inputs to remove commas
        if ($request->has('salary') && is_string($request->salary)) {
            $request->merge(['salary' => str_replace(',', '', $request->salary)]);
        }

        // Validation (Requirement 3.4)
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50', 'unique:users,employee_code'],
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'address' => ['nullable', 'string'],
            'department' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'id_card' => ['nullable', 'string', 'max:50'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,leave,resigned'],
            'note' => ['nullable', 'string'],
            'work_location_id' => ['nullable', 'exists:work_locations,id'],
            'timekeeping_type' => ['required', 'in:regular,irregular'],
        ]);

        // Hash password
        $validated['password'] = bcrypt($validated['password']);

        // Use User model to trigger LogsActivity trait
        User::create($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Nhân viên đã được tạo thành công.');
    }

    /**
     * Display the specified employee.
     * Requirements: 3.1
     */
    public function show($id)
    {
        $employee = User::whereNotNull('employee_code')
            ->findOrFail($id);
        
        $this->authorize('view', $employee);

        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified employee.
     * Requirements: 3.5
     */
    public function edit($id)
    {
        $employee = User::whereNotNull('employee_code')
            ->findOrFail($id);
        
        $this->authorize('update', $employee);

        $workLocations = \App\Models\WorkLocation::where('is_active', true)->get();
        return view('employees.edit', compact('employee', 'workLocations'));
    }

    /**
     * Update the specified employee in storage.
     * Requirements: 3.6
     */
    public function update(Request $request, $id)
    {
        $employee = User::whereNotNull('employee_code')
            ->findOrFail($id);
        
        $this->authorize('update', $employee);

        // Pre-process numeric inputs to remove commas
        if ($request->has('salary') && is_string($request->salary)) {
            $request->merge(['salary' => str_replace(',', '', $request->salary)]);
        }

        // Validation with unique rule ignoring current record
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($id)],
            'password' => ['nullable', 'string', 'min:8'],
            'address' => ['nullable', 'string'],
            'department' => ['required', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'id_card' => ['nullable', 'string', 'max:50'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,leave,resigned'],
            'note' => ['nullable', 'string'],
            'work_location_id' => ['nullable', 'exists:work_locations,id'],
            'timekeeping_type' => ['required', 'in:regular,irregular'],
        ]);

        // Only update password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Use update method on model instance to trigger events
        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Nhân viên đã được cập nhật thành công.');
    }

    /**
     * Remove the specified employee from storage.
     * Requirements: 3.7, 3.8
     */
    public function destroy($id)
    {
        $employee = User::whereNotNull('employee_code')->findOrFail($id);
        
        if ($employee->is_hidden || $employee->hasRole('super_admin')) {
            return redirect()->route('employees.index')
                ->with('error', 'Không thể xóa tài khoản Quản trị viên hệ thống.');
        }

        $this->authorize('delete', $employee);

        // Use delete method on model instance to trigger events
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Nhân viên đã được xóa thành công.');
    }

    /**
     * Export employees to Excel
     * Requirements: 7.1, 7.4, 7.6, 7.7
     */
    public function export(Request $request, ExportService $exportService)
    {
        $this->authorize('viewAny', User::class);
        
        $query = User::whereNotNull('employee_code')->where('is_hidden', false);

        // Apply filters if present (Requirement 7.6)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->get();

        // Generate Excel file (Requirement 7.7)
        $filepath = $exportService->exportEmployees($employees);

        return response()->download($filepath)->deleteFileAfterSend(true);
    }

    /**
     * Download import template for employees
     */
    public function importTemplate()
    {
        $this->authorize('create', User::class);
        
        // Create sample data for template
        $sampleData = collect([
            (object)[
                'employee_code' => 'NV001',
                'name' => 'Nguyễn Văn A',
                'position' => 'Nhân viên kinh doanh',
                'department' => 'Kinh doanh',
                'roles' => collect([(object)['name' => 'Kinh doanh']]),
                'email' => 'nguyenvana@company.com',
                'phone' => '0901234567',
                'password' => 'password123',
                'status' => 'Đang làm việc',
                'join_date' => '2024-01-15',
                'salary' => 15000000,
                'birth_date' => '1990-05-20',
                'address' => '123 Đường ABC, Quận 1, TP.HCM',
                'id_card' => '079123456789',
                'bank_account' => '1234567890',
                'bank_name' => 'Vietcombank',
                'note' => 'Ghi chú mẫu',
            ],
        ]);

        $filename = 'employees_import_template_' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new EmployeesExport($sampleData), $filename);
    }

    /**
     * Import employees from Excel file
     */
    public function import(Request $request)
    {
        $this->authorize('create', User::class);
        
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'file.required' => 'Vui lòng chọn file Excel để import.',
            'file.mimes' => 'File phải có định dạng .xlsx hoặc .xls',
            'file.max' => 'File không được vượt quá 10MB.',
        ]);

        try {
            $import = new EmployeesImport();
            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();
            $imported = $import->getImportedCount();
            $updated = $import->getUpdatedCount();

            // Không có dữ liệu nào được xử lý
            if ($imported === 0 && $updated === 0 && empty($errors)) {
                return redirect()->route('employees.index')
                    ->with('warning', 'Không tìm thấy dữ liệu hợp lệ trong file. Vui lòng kiểm tra lại định dạng file và các cột tiêu đề.');
            }

            if (!empty($errors)) {
                return redirect()->route('employees.index')
                    ->with('warning', "Import hoàn tất với một số lỗi. Đã thêm: {$imported}, Đã cập nhật: {$updated}. Lỗi: " . implode('; ', array_slice($errors, 0, 5)));
            }

            return redirect()->route('employees.index')
                ->with('success', "Import thành công! Đã thêm: {$imported} nhân viên, Đã cập nhật: {$updated} nhân viên.");

        } catch (\Exception $e) {
            \Log::error('Employee Import Error: ' . $e->getMessage());
            return redirect()->route('employees.index')
                ->with('error', 'Lỗi khi import: ' . $e->getMessage());
        }
    }

    /**
     * Toggle lock/unlock employee account
     */
    public function toggleLock($id)
    {
        $employee = User::whereNotNull('employee_code')
            ->findOrFail($id);
        
        $this->authorize('update', $employee);

        $newLockStatus = !$employee->is_locked;
        
        $employee->update([
            'is_locked' => $newLockStatus,
        ]);

        $message = $newLockStatus 
            ? "Đã khóa tài khoản nhân viên {$employee->name}." 
            : "Đã mở khóa tài khoản nhân viên {$employee->name}.";

        return redirect()->route('employees.index')
            ->with('success', $message);
    }

    /**
     * Bulk assign roles to multiple selected employees
     */
    public function bulkAssignRoles(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:users,id'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'action_type' => ['required', 'in:append,replace'],
        ], [
            'employee_ids.required' => 'Vui lòng chọn ít nhất một nhân viên.',
            'employee_ids.min' => 'Vui lòng chọn ít nhất một nhân viên.',
            'role_ids.required' => 'Vui lòng chọn ít nhất một vai trò cần gán.',
            'role_ids.min' => 'Vui lòng chọn ít nhất một vai trò cần gán.',
            'action_type.required' => 'Vui lòng chọn phương thức gán vai trò.',
        ]);

        $employeeIds = $validated['employee_ids'];
        $roleIds = $validated['role_ids'];
        $actionType = $validated['action_type']; // 'append' hoặc 'replace'
        $authId = auth()->id();

        $syncData = [];
        foreach ($roleIds as $roleId) {
            $syncData[$roleId] = [
                'assigned_by' => $authId,
                'assigned_at' => now(),
            ];
        }

        DB::transaction(function () use ($employeeIds, $syncData, $actionType) {
            $employees = User::whereIn('id', $employeeIds)->get();
            foreach ($employees as $employee) {
                if ($actionType === 'replace') {
                    $employee->roles()->sync($syncData);
                } else {
                    $employee->roles()->syncWithoutDetaching($syncData);
                }

                try {
                    if (interface_exists(\App\Services\PermissionServiceInterface::class) && app()->bound(\App\Services\PermissionServiceInterface::class)) {
                        app(\App\Services\PermissionServiceInterface::class)->invalidateUserCache($employee->id);
                    }
                } catch (\Throwable $e) {
                    // Ignore cache invalidation failure
                }
            }
        });

        $count = count($employeeIds);
        $actionText = $actionType === 'replace' ? 'thay thế' : 'bổ sung';

        return redirect()->route('employees.index')
            ->with('success', "Đã {$actionText} vai trò thành công cho {$count} nhân viên được chọn.");
    }
}
