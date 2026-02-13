<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Activity::where('user_id', auth()->id())
            ->with(['opportunity', 'customer', 'lead']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status == 'completed') {
                $query->where('is_completed', true);
            } elseif ($request->status == 'pending') {
                $query->where('is_completed', false);
            }
        } else {
            // Default: show pending
            $query->where('is_completed', false);
        }

        // Filter by type
        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }

        $activities = $query->orderBy('due_date')->paginate(20);

        return view('activities.index', compact('activities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'type' => 'required|in:call,meeting,email,task,note',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'customer_id' => 'nullable|exists:customers,id',
            'lead_id' => 'nullable|exists:leads,id',
        ]);

        $validated['user_id'] = auth()->id(); // Assign to creator by default, can be changed later
        $validated['created_by'] = auth()->id();

        \App\Models\Activity::create($validated);

        return back()->with('success', 'Đã thêm hoạt động mới.');
    }

    public function update(Request $request, \App\Models\Activity $activity)
    {
        // Handle "Complete & Next Action" workflow
        if ($request->has('complete_with_result')) {
            $request->validate([
                'result_note' => 'required|string',
                'next_action_subject' => 'nullable|required_if:has_next_action,1|string|max:255',
                'next_action_date' => 'nullable|required_if:has_next_action,1|date',
                'next_action_type' => 'nullable|required_if:has_next_action,1|in:call,meeting,email,task,note',
            ]);

            // 1. Update current activity
            $activity->update([
                'is_completed' => true,
                'completed_at' => now(),
                'description' => $activity->description . "\n\n[KẾT QUẢ " . now()->format('d/m/Y H:i') . "]: " . $request->result_note
            ]);

            // 2. Create next action if requested
            if ($request->has('has_next_action') && $request->has_next_action == '1') {
                \App\Models\Activity::create([
                    'subject' => $request->next_action_subject,
                    'type' => $request->next_action_type,
                    'due_date' => $request->next_action_date,
                    'user_id' => auth()->id(),
                    'created_by' => auth()->id(),
                    'opportunity_id' => $activity->opportunity_id,
                    'customer_id' => $activity->customer_id, // Inherit relation
                    'lead_id' => $activity->lead_id,         // Inherit relation
                    'description' => "Công việc tiếp theo từ: " . $activity->subject
                ]);
                return back()->with('success', 'Đã cập nhật kết quả và tạo công việc tiếp theo.');
            }

            return back()->with('success', 'Đã cập nhật kết quả công việc.');
        }

        // Simple update for status toggle
        if ($request->has('toggle_status')) {
            $activity->update([
                'is_completed' => !$activity->is_completed,
                'completed_at' => !$activity->is_completed ? now() : null,
            ]);
            return back()->with('success', 'Đã cập nhật trạng thái.');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'type' => 'required|in:call,meeting,email,task,note',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $activity->update($validated);

        return back()->with('success', 'Đã cập nhật hoạt động.');
    }

    public function destroy(\App\Models\Activity $activity)
    {
        $activity->delete();
        return back()->with('success', 'Đã xóa hoạt động.');
    }
}
