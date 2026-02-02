<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = \App\Models\Activity::where('user_id', auth()->id())
            ->where('is_completed', false)
            ->with('opportunity', 'customer')
            ->orderBy('due_date')
            ->paginate(20);

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
        // Simple update for status toggle or full edit
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
