<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ExportService;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with search and filter functionality.
     * Requirements: 1.1, 1.9, 1.10
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search functionality (Requirement 1.9)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by type (Requirement 1.10)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     * Requirements: 1.2
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in storage.
     * Requirements: 1.3, 1.4
     */
    public function store(Request $request)
    {
        // Validation (Requirement 1.4)
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'type' => ['required', 'in:normal,vip'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'debt_limit' => ['nullable', 'numeric', 'min:0'],
            'debt_days' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Khách hàng đã được tạo thành công.');
    }

    /**
     * Display the specified customer.
     * Requirements: 1.1
     */
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     * Requirements: 1.5
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     * Requirements: 1.6
     */
    public function update(Request $request, Customer $customer)
    {
        // Validation with unique rule ignoring current record
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('customers')->ignore($customer->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'type' => ['required', 'in:normal,vip'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'debt_limit' => ['nullable', 'numeric', 'min:0'],
            'debt_days' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Khách hàng đã được cập nhật thành công.');
    }

    /**
     * Remove the specified customer from storage.
     * Requirements: 1.7, 1.8
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Khách hàng đã được xóa thành công.');
    }

    /**
     * Export customers to Excel
     * Requirements: 7.1, 7.2, 7.6, 7.7
     */
    public function export(Request $request, ExportService $exportService)
    {
        $query = Customer::query();

        // Apply filters if present (Requirement 7.6)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $customers = $query->get();

        // Generate Excel file (Requirement 7.7)
        $filepath = $exportService->exportCustomers($customers);

        return response()->download($filepath)->deleteFileAfterSend(true);
    }
}
