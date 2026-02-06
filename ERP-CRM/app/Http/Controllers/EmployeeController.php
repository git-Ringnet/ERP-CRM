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
        $query = User::whereNotNull('employee_code');

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

        return view('employees.index', compact('employees', 'departments'));
    }

    /**
     * Show the form for creating a new employee.
     * Requirements: 3.2
     */
    public function create()
    {
        return view('employees.create');
    }

    /**
     * Store a newly created employee in storage.
     * Requirements: 3.3, 3.4
     */
    public function store(Request $request)
    {
        // Validation (Requirement 3.4)
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50', 'unique:users,employee_code'],
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
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

        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified employee in storage.
     * Requirements: 3.6
     */
    public function update(Request $request, $id)
    {
        $employee = User::whereNotNull('employee_code')
            ->findOrFail($id);

        // Validation with unique rule ignoring current record
        $validated = $request->validate([
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
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
        $employee = User::whereNotNull('employee_code')
            ->findOrFail($id);

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
        $query = User::whereNotNull('employee_code');

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
        // Create sample data for template
        $sampleData = collect([
            (object)[
                'employee_code' => 'NV001',
                'name' => 'Nguyễn Văn A',
                'position' => 'Nhân viên',
                'department' => 'Kinh doanh',
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
}
