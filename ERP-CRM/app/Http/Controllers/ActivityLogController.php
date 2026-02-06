<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs with filters
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        // Filter by user
        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->ofAction($request->action);
        }

        // Filter by subject type (module)
        if ($request->filled('subject_type')) {
            $query->forSubjectType($request->subject_type);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Search in description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(50);

        // Get unique users for filter
        $users = User::orderBy('name')->get(['id', 'name']);

        // Get available actions
        $actions = ActivityLog::distinct()->pluck('action');

        // Get available subject types
        $subjectTypes = ActivityLog::whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->map(function ($type) {
                return class_basename($type);
            })
            ->sort()
            ->values();

        return view('activity-logs.index', compact('logs', 'users', 'actions', 'subjectTypes'));
    }

    /**
     * Display logs for a specific user
     */
    public function userLogs(User $user)
    {
        $logs = ActivityLog::forUser($user->id)->latest()->paginate(50);

        return view('activity-logs.user', compact('logs', 'user'));
    }
}
