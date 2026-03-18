<?php

namespace App\Http\Controllers;

use App\Models\WorkLocation;
use Illuminate\Http\Request;

class WorkLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = WorkLocation::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $workLocations = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('work-locations.index', compact('workLocations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $defaultLat = 21.028511; // Hanoi
        $defaultLng = 105.804817;
        
        return view('work-locations.create', compact('defaultLat', 'defaultLng'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        WorkLocation::create($validated);

        return redirect()->route('work-locations.index')
            ->with('success', 'Địa điểm làm việc đã được tạo thành công.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WorkLocation $workLocation)
    {
        $defaultLat = $workLocation->latitude ?: 21.028511;
        $defaultLng = $workLocation->longitude ?: 105.804817;

        return view('work-locations.edit', compact('workLocation', 'defaultLat', 'defaultLng'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkLocation $workLocation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $workLocation->update($validated);

        return redirect()->route('work-locations.index')
            ->with('success', 'Địa điểm làm việc đã được cập nhật thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkLocation $workLocation)
    {
        // Check if there are any attendances linked to this location
        if ($workLocation->attendances()->exists()) {
            return redirect()->route('work-locations.index')
                ->with('error', 'Không thể xóa địa điểm này vì đã có dữ liệu chấm công liên quan.');
        }

        $workLocation->delete();

        return redirect()->route('work-locations.index')
            ->with('success', 'Địa điểm làm việc đã được xóa thành công.');
    }
}
