<?php

namespace App\Http\Controllers;

use App\Models\DepartmentKpiCriterion;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentKpiCriterionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $criteria = DepartmentKpiCriterion::with('creator')->latest()->paginate(20);
        return view('department-kpi-criteria.index', compact('criteria'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = User::select('department')->whereNotNull('department')->distinct()->pluck('department');
        return view('department-kpi-criteria.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'required|numeric|min:0|max:100',
            'target' => 'nullable|string|max:255',
        ]);

        $validated['creator_id'] = auth()->id();

        DepartmentKpiCriterion::create($validated);

        return redirect()->route('department-kpi-criteria.index')->with('success', 'Đã thêm Tiêu chí KPI thành công.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DepartmentKpiCriterion $departmentKpiCriterion)
    {
        $departments = User::select('department')->whereNotNull('department')->distinct()->pluck('department');
        return view('department-kpi-criteria.edit', compact('departmentKpiCriterion', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DepartmentKpiCriterion $departmentKpiCriterion)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'required|numeric|min:0|max:100',
            'target' => 'nullable|string|max:255',
        ]);

        $departmentKpiCriterion->update($validated);

        return redirect()->route('department-kpi-criteria.index')->with('success', 'Đã cập nhật Tiêu chí KPI thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DepartmentKpiCriterion $departmentKpiCriterion)
    {
        $departmentKpiCriterion->delete();
        return redirect()->route('department-kpi-criteria.index')->with('success', 'Đã xoá Tiêu chí KPI thành công.');
    }

    /**
     * API: Get criteria by department
     */
    public function getByDepartment(Request $request)
    {
        $department = $request->get('department');
        $criteria = DepartmentKpiCriterion::where('department', $department)->get();
        return response()->json($criteria);
    }
}
