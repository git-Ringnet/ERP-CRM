<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ExportService;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with search and filter functionality.
     * Requirements: 1.1, 1.9, 1.10
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);
        
        $query = Customer::query();

        // Search functionality (Requirement 1.9)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tax_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by type (Requirement 1.10)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by debt limit
        if ($request->filled('has_debt_limit')) {
            if ($request->has_debt_limit === 'yes') {
                $query->where('debt_limit', '>', 0);
            } else {
                $query->where(function($q) {
                    $q->whereNull('debt_limit')->orWhere('debt_limit', 0);
                });
            }
        }

        $customers = $query->with('contacts')->orderBy('created_at', 'desc')->paginate(10);

        return view('customers.index', compact('customers'));
    }

    /**
     * AJAX customer search for marketing events (lightweight).
     * Returns latest 10 matched customers excluding existing invited ones.
     */
    public function ajaxSearch(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $q = trim((string) $request->get('q', ''));
        $marketingEventId = $request->integer('marketing_event_id');

        $excludeIds = [];
        if ($marketingEventId) {
            $excludeIds = DB::table('marketing_event_customers')
                ->where('marketing_event_id', $marketingEventId)
                ->pluck('customer_id')
                ->all();
        }

        $query = Customer::query()
            ->select(['id', 'name'])
            ->when(!empty($excludeIds), fn ($qb) => $qb->whereNotIn('id', $excludeIds));

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('tax_code', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $customers = $query->orderByDesc('created_at')->limit(10)->get();

        return response()->json($customers);
    }

    /**
     * Show the form for creating a new customer.
     * Requirements: 1.2
     */
    public function create()
    {
        $this->authorize('create', Customer::class);
        
        return view('customers.create');
    }

    /**
     * Store a newly created customer in storage.
     * Requirements: 1.3, 1.4
     */
    public function store(\App\Http\Requests\CustomerRequest $request)
    {
        $this->authorize('create', Customer::class);
        
        $validated = $request->validated();
        
        DB::transaction(function() use ($validated) {
            $customer = Customer::create($validated);
            
            if (!empty($validated['contacts'])) {
                foreach ($validated['contacts'] as $contactData) {
                    $customer->contacts()->create($contactData);
                }
            }
        });

        return redirect()->route('customers.index')
            ->with('success', 'Khách hàng đã được tạo thành công.');
    }

    /**
     * Display the specified customer.
     * Requirements: 1.1
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);
        
        $customer->load([
            'contacts',
            'projects' => function($query) {
                $query->with('exports')->latest();
            },
            'exports' => function($query) {
                $query->latest()->limit(10);
            },
            'activities' => function($query) {
                $query->with('user', 'createdBy')->latest()->limit(20);
            }
        ]);
        
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     * Requirements: 1.5
     */
    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);
        
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     * Requirements: 1.6
     */
    public function update(\App\Http\Requests\CustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        
        $validated = $request->validated();
        
        DB::transaction(function() use ($validated, $customer) {
            $customer->update($validated);
            
            if (isset($validated['contacts'])) {
                // Simplest way: delete all and recreate or sync
                $customer->contacts()->delete();
                foreach ($validated['contacts'] as $contactData) {
                    $customer->contacts()->create($contactData);
                }
            }
        });

        return redirect()->route('customers.index')
            ->with('success', 'Khách hàng đã được cập nhật thành công.');
    }

    /**
     * Remove the specified customer from storage.
     * Requirements: 1.7, 1.8
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        
        // Check if customer has related projects
        if ($customer->projects()->exists()) {
            return redirect()->route('customers.index')
                ->with('error', 'Không thể xóa khách hàng này vì còn dự án liên quan.');
        }

        // Check if customer has related exports through projects
        $hasExports = \App\Models\Export::whereHas('project', function($query) use ($customer) {
            $query->where('customer_id', $customer->id);
        })->exists();

        if ($hasExports) {
            return redirect()->route('customers.index')
                ->with('error', 'Không thể xóa khách hàng này vì còn đơn xuất kho liên quan.');
        }

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
        $this->authorize('viewAny', Customer::class);
        
        $query = Customer::query();

        // Apply filters if present (Requirement 7.6)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tax_code', 'like', "%{$search}%")
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

    /**
     * Download import template
     */
    public function importTemplate()
    {
        $this->authorize('create', Customer::class);
        
        $tempFile = CustomersImport::generateTemplate();
        $filename = 'customer_import_template_' . date('Y-m-d') . '.xlsx';

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Import customers from Excel
     */
    public function import(Request $request)
    {
        $this->authorize('create', Customer::class);
        
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $import = new CustomersImport();
            Excel::import($import, $request->file('file'));

            $errors = $import->getErrors();
            if (!empty($errors)) {
                return redirect()->route('customers.index')
                    ->with('error', 'Import thất bại: ' . implode(', ', array_slice($errors, 0, 5)));
            }

            $imported = $import->getImported();
            $updated = $import->getUpdated();
            $message = "Import thành công: {$imported} khách hàng mới";
            if ($updated > 0) {
                $message .= ", cập nhật {$updated} khách hàng";
            }

            return redirect()->route('customers.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->route('customers.index')
                ->with('error', 'Lỗi khi import: ' . $e->getMessage());
        }
    }
}
