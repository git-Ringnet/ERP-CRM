<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class ReminderController extends Controller
{
    /**
     * Display a listing of user's reminders.
     */
    public function index(Request $request)
    {
        $query = Reminder::where('user_id', auth()->id())
            ->with('remindable');

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'sent') {
                $query->where('is_sent', true);
            } elseif ($request->status === 'pending') {
                $query->unsent();
            } elseif ($request->status === 'overdue') {
                $query->unsent()->due();
            }
        }

        $reminders = $query->orderBy('remind_at', 'asc')->paginate(20);

        return view('reminders.index', compact('reminders'));
    }

    /**
     * Store a newly created reminder.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'remindable_type' => 'required|string',
            'remindable_id' => 'required|integer',
            'remind_at' => 'required|date|after:now',
            'message' => 'required|string',
        ]);

        $reminder = Reminder::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nhắc nhở đã được tạo.',
            'reminder' => $reminder,
        ]);
    }

    /**
     * Update the specified reminder.
     */
    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        // Only allow owner to update
        if ($reminder->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'remind_at' => 'required|date',
            'message' => 'required|string',
        ]);

        $reminder->update($validated);

        return redirect()->back()->with('success', 'Nhắc nhở đã được cập nhật.');
    }

    /**
     * Remove the specified reminder.
     */
    public function destroy(Reminder $reminder): RedirectResponse
    {
        // Only allow owner to delete
        if ($reminder->user_id !== auth()->id()) {
            abort(403);
        }

        $reminder->delete();

        return redirect()->back()->with('success', 'Nhắc nhở đã được xóa.');
    }

    /**
     * Snooze the reminder for specified minutes.
     */
    public function snooze(Request $request, Reminder $reminder): JsonResponse
    {
        // Only allow owner to snooze
        if ($reminder->user_id !== auth()->id()) {
            abort(403);
        }

        $minutes = $request->input('minutes', 30);
        $reminder->snooze($minutes);

        return response()->json([
            'success' => true,
            'message' => 'Nhắc nhở đã được hoãn lại ' . $minutes . ' phút.',
            'new_remind_at' => $reminder->remind_at,
        ]);
    }
}
