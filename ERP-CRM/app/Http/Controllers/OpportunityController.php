<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    /**
     * Display a listing of the resource (Kanban Board).
     */
    public function index(Request $request)
    {
        $viewType = $request->get('view', 'kanban'); // 'kanban' or 'list'

        if ($viewType === 'list') {
            $opportunities = \App\Models\Opportunity::with('customer', 'assignedTo')->latest()->paginate(20);
            return view('opportunities.index_list', compact('opportunities'));
        }

        // Kanban View grouping
        $opportunities = \App\Models\Opportunity::with('customer', 'assignedTo')->get();
        $stages = [
            'new' => 'Mới',
            'qualification' => 'Đánh giá',
            'proposal' => 'Báo giá',
            'negotiation' => 'Đàm phán',
            'won' => 'Thành công',
            'lost' => 'Thất bại',
        ];

        $kanbanData = [];
        foreach ($stages as $key => $label) {
            $kanbanData[$key] = $opportunities->where('stage', $key);
        }

        return view('opportunities.index', compact('kanbanData', 'stages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = \App\Models\Customer::all();
        $users = \App\Models\User::all();
        return view('opportunities.create', compact('customers', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'stage' => 'required|string',
            'expected_close_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        \App\Models\Opportunity::create($validated);

        return redirect()->route('opportunities.index')->with('success', 'Đã tạo cơ hội thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Opportunity $opportunity)
    {
        return view('opportunities.show', compact('opportunity'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\Opportunity $opportunity)
    {
        $customers = \App\Models\Customer::all();
        $users = \App\Models\User::all();
        return view('opportunities.edit', compact('opportunity', 'customers', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Opportunity $opportunity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'stage' => 'required|string',
            'expected_close_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        $opportunity->update($validated);

        return redirect()->route('opportunities.index')->with('success', 'Đã cập nhật cơ hội thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Opportunity $opportunity)
    {
        $opportunity->delete();
        return redirect()->route('opportunities.index')->with('success', 'Đã xóa cơ hội.');
    }

    /**
     * API to update stage via Drag & Drop
     */
    public function updateStage(Request $request, \App\Models\Opportunity $opportunity)
    {
        $validated = $request->validate([
            'stage' => 'required|string',
        ]);

        $opportunity->update(['stage' => $validated['stage']]);

        return response()->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
    }
}
