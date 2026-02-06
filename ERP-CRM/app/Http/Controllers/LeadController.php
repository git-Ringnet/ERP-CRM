<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leads = \App\Models\Lead::latest()->paginate(10);
        return view('leads.index', compact('leads'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = \App\Models\User::all();
        return view('leads.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'required|string',
            'source' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        \App\Models\Lead::create($validated);

        return redirect()->route('leads.index')->with('success', 'Đã tạo đấu mối thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Lead $lead)
    {
        return view('leads.show', compact('lead'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\Lead $lead)
    {
        $users = \App\Models\User::all();
        return view('leads.edit', compact('lead', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Lead $lead)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'required|string',
            'source' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $lead->update($validated);

        return redirect()->route('leads.index')->with('success', 'Đã cập nhật đấu mối thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Lead $lead)
    {
        $lead->delete();
        return redirect()->route('leads.index')->with('success', 'Đã xóa đấu mối.');
    }

    /**
     * Convert Lead to Customer and Opportunity
     */
    public function convert(Request $request, \App\Models\Lead $lead)
    {
        // 1. Check if already converted
        if ($lead->status === 'converted') {
            return back()->with('error', 'Đấu mối này đã được chuyển đổi.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 2. Create Customer
            $customer = \App\Models\Customer::create([
                'name' => $lead->company_name ?? $lead->name,
                'code' => 'CUS-' . strtoupper(\Illuminate\Support\Str::random(6)), // Simple auto-code
                'email' => $lead->email ?? 'no-email-' . time() . '@example.com',
                'phone' => $lead->phone ?? '',
                'contact_person' => $lead->name,
                'type' => 'normal',
            ]);

            // 3. Create Opportunity
            $opportunity = \App\Models\Opportunity::create([
                'name' => 'Cơ hội từ convert: ' . $lead->title ?? $lead->name,
                'customer_id' => $customer->id,
                'lead_id' => $lead->id,
                'assigned_to' => $lead->assigned_to ?? auth()->id(),
                'stage' => 'new',
                'created_by' => auth()->id(),
            ]);

            // 4. Update Lead status
            $lead->update(['status' => 'converted']);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('opportunities.index')->with('success', 'Đã chuyển đổi thành công! Khách hàng và Cơ hội mới đã được tạo.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Lỗi chuyển đổi: ' . $e->getMessage());
        }
    }
}
