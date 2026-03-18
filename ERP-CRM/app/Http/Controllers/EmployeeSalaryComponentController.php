<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SalaryComponent;
use App\Models\EmployeeSalaryComponent;
use Illuminate\Http\Request;

class EmployeeSalaryComponentController extends Controller
{
    /**
     * Show the form for editing the employee's salary setup.
     */
    public function edit($id)
    {
        $employee = User::whereNotNull('employee_code')->findOrFail($id);
        
        $this->authorize('update', $employee);

        $availableComponents = SalaryComponent::orderBy('type')->orderBy('name')->get();
        
        $employeeComponents = $employee->employeeSalaryComponents()
            ->get()
            ->keyBy('salary_component_id');

        return view('employees.salary_setup', compact('employee', 'availableComponents', 'employeeComponents'));
    }

    /**
     * Update the employee's salary setup in storage.
     */
    public function update(Request $request, $id)
    {
        $employee = User::whereNotNull('employee_code')->findOrFail($id);
        
        $this->authorize('update', $employee);

        // Pre-process numeric inputs to remove commas
        if ($request->has('salary') && is_string($request->salary)) {
            $request->merge(['salary' => str_replace(',', '', $request->salary)]);
        }
        
        if ($request->has('components')) {
            $cleanedComponents = [];
            foreach ($request->components as $compId => $val) {
                $cleanedComponents[$compId] = is_string($val) ? str_replace(',', '', $val) : $val;
            }
            $request->merge(['components' => $cleanedComponents]);
        }

        $request->validate([
            'salary' => 'nullable|numeric|min:0',
            'components' => 'array',
            'components.*' => 'nullable|numeric|min:0',
        ]);

        // Update basic salary if provided
        if ($request->has('salary')) {
            $employee->update(['salary' => $request->salary]);
        }

        // Sync components
        $components = $request->input('components', []);
        
        // Remove old components
        $employee->employeeSalaryComponents()->delete();

        // Add new components
        foreach ($components as $componentId => $amount) {
            if ($amount !== null && $amount !== '') {
                EmployeeSalaryComponent::create([
                    'user_id' => $employee->id,
                    'salary_component_id' => $componentId,
                    'amount' => $amount
                ]);
            }
        }

        return redirect()->route('employees.show', $employee->id)
            ->with('success', 'Đã cập nhật cấu hình lương và phụ cấp cho nhân viên thành công.');
    }
}
