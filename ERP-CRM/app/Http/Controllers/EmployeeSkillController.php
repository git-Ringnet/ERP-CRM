<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\EmployeeSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeSkillController extends Controller
{
    /**
     * Xem skillset của một nhân viên.
     */
    public function show($employeeId)
    {
        $employee = User::whereNotNull('employee_code')->findOrFail($employeeId);

        $categories = SkillCategory::with(['skills' => function ($q) use ($employeeId) {
            $q->with(['employeeSkills' => function ($q2) use ($employeeId) {
                $q2->where('user_id', $employeeId);
            }]);
        }])->orderBy('name')->get();

        return view('employee-skills.show', compact('employee', 'categories'));
    }

    /**
     * Form đánh giá / cập nhật kỹ năng cho nhân viên.
     */
    public function edit($employeeId)
    {
        $employee = User::whereNotNull('employee_code')->findOrFail($employeeId);

        $categories = SkillCategory::with('skills')->orderBy('name')->get();

        // Lấy skill hiện tại của nhân viên
        $currentSkills = EmployeeSkill::where('user_id', $employeeId)
            ->get()
            ->keyBy('skill_id');

        return view('employee-skills.edit', compact('employee', 'categories', 'currentSkills'));
    }

    /**
     * Lưu kết quả đánh giá (upsert tất cả skill cùng lúc).
     */
    public function update(Request $request, $employeeId)
    {
        $employee = User::whereNotNull('employee_code')->findOrFail($employeeId);

        $validated = $request->validate([
            'skills'          => 'nullable|array',
            'skills.*.level'  => 'required|integer|min:1|max:5',
            'skills.*.note'   => 'nullable|string|max:500',
        ]);

        $submittedSkills = $validated['skills'] ?? [];

        // Lấy tất cả skill IDs hợp lệ
        $allSkillIds = Skill::pluck('id')->toArray();

        // Xóa các skill không được chọn
        EmployeeSkill::where('user_id', $employeeId)
            ->whereNotIn('skill_id', array_keys($submittedSkills))
            ->delete();

        // Upsert các skill được chọn
        foreach ($submittedSkills as $skillId => $data) {
            if (!in_array($skillId, $allSkillIds)) {
                continue;
            }

            EmployeeSkill::updateOrCreate(
                [
                    'user_id'  => $employeeId,
                    'skill_id' => $skillId,
                ],
                [
                    'level'        => $data['level'],
                    'note'         => $data['note'] ?? null,
                    'evaluated_at' => now()->toDateString(),
                    'evaluated_by' => Auth::id(),
                ]
            );
        }

        return redirect()->route('employee-skills.show', $employeeId)
            ->with('success', 'Kỹ năng nhân viên đã được cập nhật thành công.');
    }
}
