<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use App\Models\EmployeeSkill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    /**
     * Danh sách kỹ năng, nhóm theo danh mục.
     */
    public function index()
    {
        $categories = SkillCategory::with('skills')->orderBy('name')->get();

        return view('skills.index', compact('categories'));
    }

    /**
     * Form tạo kỹ năng mới.
     */
    public function create()
    {
        $categories = SkillCategory::orderBy('name')->get();

        return view('skills.create', compact('categories'));
    }

    /**
     * Lưu kỹ năng mới (có thể tạo danh mục mới nếu cần).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:150',
            'skill_category_id' => 'nullable|exists:skill_categories,id',
            'new_category'      => 'nullable|string|max:100',
            'description'       => 'nullable|string|max:500',
        ]);

        // Tạo danh mục mới nếu user nhập
        if (!empty($validated['new_category'])) {
            $category = SkillCategory::firstOrCreate(
                ['name' => trim($validated['new_category'])]
            );
            $validated['skill_category_id'] = $category->id;
        }

        if (empty($validated['skill_category_id'])) {
            return back()->withErrors(['skill_category_id' => 'Vui lòng chọn hoặc tạo danh mục.'])->withInput();
        }

        Skill::create([
            'skill_category_id' => $validated['skill_category_id'],
            'name'              => $validated['name'],
            'description'       => $validated['description'] ?? null,
        ]);

        return redirect()->route('skills.index')
            ->with('success', 'Kỹ năng đã được tạo thành công.');
    }

    /**
     * Form sửa kỹ năng.
     */
    public function edit(Skill $skill)
    {
        $categories = SkillCategory::orderBy('name')->get();

        return view('skills.edit', compact('skill', 'categories'));
    }

    /**
     * Cập nhật kỹ năng.
     */
    public function update(Request $request, Skill $skill)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:150',
            'skill_category_id' => 'required|exists:skill_categories,id',
            'description'       => 'nullable|string|max:500',
        ]);

        $skill->update($validated);

        return redirect()->route('skills.index')
            ->with('success', 'Kỹ năng đã được cập nhật.');
    }

    /**
     * Xem danh sách nhân viên có kỹ năng này.
     */
    public function show(Skill $skill)
    {
        $skill->load(['employeeSkills.user', 'category']);
        
        return view('skills.show', compact('skill'));
    }

    /**
     * Form gán kỹ năng/đánh giá cho nhiều nhân viên.
     */
    public function employees(Skill $skill)
    {
        $skill->load('category');
        $employees = User::orderBy('name')->get();
        // Lấy danh sách đánh giá hiện tại của skill này
        $currentSkills = $skill->employeeSkills->keyBy('user_id');

        return view('skills.employees', compact('skill', 'employees', 'currentSkills'));
    }

    /**
     * Xử lý lưu đánh giá từ trang gán (bulk update).
     */
    public function updateEmployees(Request $request, Skill $skill)
    {
        $validated = $request->validate([
            'employees'          => 'nullable|array',
            'employees.*.level'  => 'required_with:employees|integer|min:1|max:5',
            'employees.*.note'   => 'nullable|string|max:255',
        ]);

        $employeeData = $validated['employees'] ?? [];
        $userIdsToKeep = array_keys($employeeData);

        // Delete records for employees not in the submitted list
        EmployeeSkill::where('skill_id', $skill->id)
            ->whereNotIn('user_id', $userIdsToKeep)
            ->delete();

        // Update or Create
        foreach ($employeeData as $userId => $data) {
            EmployeeSkill::updateOrCreate(
                ['user_id' => $userId, 'skill_id' => $skill->id],
                [
                    'level'        => $data['level'],
                    'note'         => $data['note'] ?? null,
                    'evaluated_at' => now(),
                    'evaluated_by' => auth()->id(),
                ]
            );
        }

        return redirect()->route('skills.show', $skill->id)
            ->with('success', 'Đã cập nhật danh sách nhân viên cho kỹ năng này.');
    }

    /**
     * Xóa kỹ năng (cascade xóa employee_skills liên quan).
     */
    public function destroy(Skill $skill)
    {
        $skill->delete();

        return redirect()->route('skills.index')
            ->with('success', 'Kỹ năng đã được xóa.');
    }

    /**
     * Xóa danh mục (AJAX hoặc form).
     */
    public function destroyCategory(SkillCategory $category)
    {
        $category->delete();

        return redirect()->route('skills.index')
            ->with('success', 'Danh mục đã được xóa.');
    }
}
