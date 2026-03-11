<?php

namespace App\Http\Controllers;

use App\Models\DepartmentKpi;
use App\Models\DepartmentKpiCriterion;
use App\Models\DepartmentKpiResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentKpiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kpis = DepartmentKpi::with(['evaluator', 'creator'])->latest()->paginate(20);
        return view('department-kpis.index', compact('kpis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = User::select('department')->whereNotNull('department')->distinct()->pluck('department');
        return view('department-kpis.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'evaluation_period' => 'required|string|max:255',
            'status' => 'required|in:draft,pending,approved,completed',
            'note' => 'nullable|string',
            'criteria' => 'required|array',
            'criteria.*.name' => 'required|string|max:255',
            'criteria.*.weight' => 'required|numeric|min:0|max:100',
            'criteria.*.target' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $kpi = DepartmentKpi::create([
                'title' => $validated['title'],
                'department' => $validated['department'],
                'evaluation_period' => $validated['evaluation_period'],
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'creator_id' => auth()->id(),
            ]);

            foreach ($validated['criteria'] as $criterion) {
                DepartmentKpiResult::create([
                    'department_kpi_id' => $kpi->id,
                    'criterion_name' => $criterion['name'],
                    'weight' => $criterion['weight'],
                    'target' => $criterion['target'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('department-kpis.show', $kpi)->with('success', 'Đã tạo Kỳ đánh giá KPI thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DepartmentKpi $departmentKpi)
    {
        $departmentKpi->load(['results', 'evaluator', 'creator']);
        return view('department-kpis.show', compact('departmentKpi'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DepartmentKpi $departmentKpi)
    {
        $departmentKpi->load('results');
        $departments = User::select('department')->whereNotNull('department')->distinct()->pluck('department');
        return view('department-kpis.edit', compact('departmentKpi', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DepartmentKpi $departmentKpi)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'evaluation_period' => 'required|string|max:255',
            'status' => 'required|in:draft,pending,approved,completed',
            'note' => 'nullable|string',
            'results' => 'required|array',
            'results.*.id' => 'required|exists:department_kpi_results,id',
            'results.*.actual_value' => 'nullable|string|max:255',
            'results.*.score' => 'required|numeric|min:0',
            'results.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $departmentKpi->update([
                'title' => $validated['title'],
                'department' => $validated['department'],
                'evaluation_period' => $validated['evaluation_period'],
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
            ]);

            $totalScore = 0;

            foreach ($validated['results'] as $resultData) {
                $result = DepartmentKpiResult::findOrFail($resultData['id']);
                
                // Only update results belonging to this KPI (security check)
                if ($result->department_kpi_id == $departmentKpi->id) {
                    $result->update([
                        'actual_value' => $resultData['actual_value'] ?? null,
                        'score' => $resultData['score'],
                        'note' => $resultData['note'] ?? null,
                    ]);
                    $totalScore += floatval($resultData['score']);
                }
            }

            $departmentKpi->update([
                'total_score' => $totalScore,
                'evaluator_id' => auth()->id() // Track who updated last
            ]);

            DB::commit();

            return redirect()->route('department-kpis.show', $departmentKpi)->with('success', 'Đã cập nhật Kết quả KPI thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DepartmentKpi $departmentKpi)
    {
        $departmentKpi->delete();
        return redirect()->route('department-kpis.index')->with('success', 'Đã xoá Kỳ đánh giá KPI thành công.');
    }
}
