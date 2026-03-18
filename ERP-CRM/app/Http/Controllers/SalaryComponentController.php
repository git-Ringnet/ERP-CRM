<?php

namespace App\Http\Controllers;

use App\Models\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryComponentController extends Controller
{
    public function index(Request $request)
    {
        $query = SalaryComponent::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $components = $query->orderBy('type')->orderBy('name')->paginate(15);

        return view('salary-components.index', compact('components'));
    }

    public function create()
    {
        return view('salary-components.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['allowance', 'deduction'])],
            'amount_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'default_amount' => 'required|numeric|min:0',
            'is_taxable' => 'boolean',
        ]);

        $validated['is_taxable'] = $request->has('is_taxable');

        SalaryComponent::create($validated);

        return redirect()->route('salary-components.index')
            ->with('success', 'Khoản phụ cấp/khấu trừ đã được tạo thành công.');
    }

    public function edit(SalaryComponent $salaryComponent)
    {
        return view('salary-components.edit', compact('salaryComponent'));
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['allowance', 'deduction'])],
            'amount_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'default_amount' => 'required|numeric|min:0',
            'is_taxable' => 'boolean',
        ]);

        $validated['is_taxable'] = $request->has('is_taxable');

        $salaryComponent->update($validated);

        return redirect()->route('salary-components.index')
            ->with('success', 'Khoản phụ cấp/khấu trừ đã được cập nhật thành công.');
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        if ($salaryComponent->employeeComponents()->exists()) {
            return redirect()->route('salary-components.index')
                ->with('error', 'Không thể xóa do khoản này đang được áp dụng cho nhân viên.');
        }

        $salaryComponent->delete();

        return redirect()->route('salary-components.index')
            ->with('success', 'Xóa thành công.');
    }
}
