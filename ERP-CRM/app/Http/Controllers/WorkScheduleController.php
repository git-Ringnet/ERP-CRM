<?php

namespace App\Http\Controllers;

use App\Models\WorkSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkScheduleController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('work-schedules.index', compact('users'));
    }

    public function getEvents(Request $request)
    {
        $query = WorkSchedule::with('participants');
        
        // Show personal events created by current user OR events where current user is a participant
        // OR group events (depending on requirements, maybe all group events are visible?)
        // For now: User sees their own personal events AND any group event they are part of OR created.
        
        $user = Auth::user();
        
        $query->where(function($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhereHas('participants', function($subQ) use ($user) {
                  $subQ->where('users.id', $user->id);
              });
        });

        if ($request->filter_type && $request->filter_type !== 'all') {
            $query->where('type', $request->filter_type);
        }

        if ($request->start) {
            $query->where('start_datetime', '>=', $request->start);
        }
        if ($request->end) {
            $query->where('start_datetime', '<=', $request->end);
        }

        $events = $query->get()->map(function ($event) {
            $color = '#3788d8'; // default blue
            if ($event->type === 'group') $color = '#8e44ad'; // purple
            if ($event->status === 'completed') $color = '#27ae60'; // green
            if ($event->status === 'overdue') $color = '#e74c3c'; // red
            
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start_datetime->toIso8601String(),
                'end' => $event->end_datetime ? $event->end_datetime->toIso8601String() : null,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'description' => $event->description,
                    'type' => $event->type,
                    'status' => $event->status,
                    'priority' => $event->priority,
                    'participants' => $event->participants->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'avatar' => $p->avatar // assuming avatar column exists or handled via accessor
                    ]),
                    'participant_ids' => $event->participants->pluck('id'),
                    'creator' => $event->creator ? $event->creator->name : 'Unknown',
                    'can_edit' => $event->created_by === Auth::id(),
                ]
            ];
        });

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after_or_equal:start_datetime',
            'type' => 'required|in:personal,group',
            'priority' => 'required|in:low,medium,high',
            'description' => 'nullable|string',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:users,id'
        ]);

        DB::beginTransaction();
        try {
            $validationData = $validated;
            $validationData['created_by'] = Auth::id();
            $validationData['status'] = 'new';
            
            // Remove participants from data to save model
            $participants = $request->participants ?? [];
            unset($validationData['participants']);
            
            $schedule = WorkSchedule::create($validationData);

            if ($schedule->type === 'group' && !empty($participants)) {
                $schedule->participants()->sync($participants);
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Tạo lịch làm việc thành công', 'data' => $schedule]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, WorkSchedule $workSchedule)
    {
        if ($workSchedule->created_by !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa lịch này'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after_or_equal:start_datetime',
            'type' => 'required|in:personal,group',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:new,in_progress,completed,overdue',
            'description' => 'nullable|string',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:users,id'
        ]);

        DB::beginTransaction();
        try {
            $validationData = $validated;
            $participants = $request->participants ?? [];
            unset($validationData['participants']);

            $workSchedule->update($validationData);

            if ($workSchedule->type === 'group') {
                $workSchedule->participants()->sync($participants);
            } else {
                $workSchedule->participants()->detach();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Cập nhật lịch làm việc thành công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(WorkSchedule $workSchedule)
    {
        if ($workSchedule->created_by !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa lịch này'], 403);
        }

        $workSchedule->delete();
        return response()->json(['success' => true, 'message' => 'Xóa lịch làm việc thành công']);
    }
}
