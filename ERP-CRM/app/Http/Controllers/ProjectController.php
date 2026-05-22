<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use App\Exports\ProjectsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::with(['customer', 'manager', 'vendor']);

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->filterByStatus($request->status);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range (Active during period)
        if ($request->filled('date_from')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('end_date', '>=', $request->date_from)
                    ->orWhereNull('end_date');
            });
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(10);
        $customers = Customer::orderBy('name')->get();

        return view('projects.index', compact('projects', 'customers'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Project::class);

        $customers = Customer::orderBy('name')->get();
        $managers = User::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $code = $this->generateProjectCode();

        // Auto-fill Distributor AM from logged-in user
        $distributorAm = Auth::user()->name . ' | ' . Auth::user()->email;

        // Handle pre-filling from MarketingEvent
        $preFill = [];
        if ($request->filled('marketing_event_id')) {
            $mktEvent = \App\Models\MarketingEvent::find($request->marketing_event_id);
            if ($mktEvent) {
                $preFill['marketing_event_id'] = $mktEvent->id;
                $preFill['name'] = "DA từ " . $mktEvent->title;
                $preFill['budget'] = $mktEvent->budget;
                $preFill['description'] = "Dự án phát sinh từ sự kiện: " . $mktEvent->title . "\n" . $mktEvent->description;
            }
        }
        if ($request->filled('customer_id')) {
            $preFill['customer_id'] = $request->customer_id;
        }

        return view('projects.create', compact('customers', 'managers', 'suppliers', 'code', 'preFill', 'distributorAm'));
    }

    /**
     * Generate unique project code
     */
    private function generateProjectCode(): string
    {
        $prefix = 'DA-';
        $lastProject = Project::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastProject) {
            $lastNumber = intval(substr($lastProject->code, strlen($prefix)));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * AJAX: Check if a tax code already exists in customers.
     */
    public function checkTaxCode(Request $request)
    {
        $taxCode = trim($request->input('tax_code', ''));

        if (empty($taxCode) || strlen($taxCode) < 3) {
            return response()->json(['exists' => false]);
        }

        $customer = Customer::where('tax_code', $taxCode)->first();

        if ($customer) {
            return response()->json([
                'exists' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'tax_code' => $customer->tax_code,
                    'address' => $customer->address,
                ],
            ]);
        }

        return response()->json(['exists' => false]);
    }

    /**
     * Find existing customer by tax_code or create new one.
     * Used for auto-creating customers from End-User / Partner info.
     */
    private function findOrCreateCustomer(
        string $taxCode,
        string $name,
        ?string $nameEn = null,
        ?string $abbr = null,
        ?string $address = null,
        string $type = 'end_user'
    ): Customer {
        // Try to find by tax_code
        $customer = Customer::where('tax_code', $taxCode)->first();

        if ($customer) {
            // Update name if changed
            $customer->update([
                'name' => $name,
                'name_en' => $nameEn ?: $customer->name_en,
                'address' => $address ?: $customer->address,
            ]);
            return $customer;
        }

        // Create new customer
        return Customer::create([
            'name' => $name,
            'name_en' => $nameEn,
            'abv_name' => $abbr,
            'tax_code' => $taxCode,
            'address' => $address,
            'type' => 'normal',
            'note' => 'Tự động tạo từ đăng ký dự án',
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);

        // Dynamic validation: collaboration fields only required for Partner
        $collabRequired = $request->input('collaborate_type') === 'partner' ? 'required' : 'nullable';

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:projects,code'],
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:planning,in_progress,completed,cancelled,on_hold'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string'],
            'marketing_event_id' => ['nullable', 'exists:marketing_events,id'],
            // Distributor
            'vendor_id' => ['nullable', 'exists:suppliers,id'],
            'distributor_am' => ['nullable', 'string', 'max:255'],
            // End-User
            'eu_name_vi' => ['required', 'string', 'max:500'],
            'eu_name_en' => ['required', 'string', 'max:500'],
            'eu_name_abbr' => ['nullable', 'string', 'max:100'],
            'eu_tax_code' => ['required', 'string', 'max:100'],
            'eu_province' => ['nullable', 'string', 'max:100'],
            'eu_industry' => ['nullable', 'string', 'max:100'],
            // Collaboration (dynamic: required only for Partner)
            'collaborate_type' => ['required', 'in:partner,end_user'],
            'collaborate_customer_id' => ['nullable', 'exists:customers,id'],
            'collaborate_company' => [$collabRequired, 'string', 'max:500'],
            'collaborate_tax_code' => [$collabRequired, 'string', 'max:100'],
            'collaborate_pic_name' => [$collabRequired, 'string', 'max:255'],
            'collaborate_pic_title' => [$collabRequired, 'string', 'max:255'],
            'collaborate_pic_phone' => [$collabRequired, 'string', 'max:50'],
            'collaborate_pic_email' => ['nullable', 'string', 'email', 'max:255'],
            // Project enhancements
            'estimated_close_months' => ['required', 'in:3,6,9'],
            'bom_file' => ['nullable', 'file', 'mimes:xlsx,xls,pdf,doc,docx', 'max:10240'],
            'bom_data' => ['nullable', 'string'],
            'net_to_tech_horizon' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['nullable', 'string', 'max:50'],
            'deal_type' => ['nullable', 'string', 'max:50'],
        ], [], $this->validationAttributes());

        // Auto-set start_date and calculate end_date from estimated_close_months
        $validated['start_date'] = now()->format('Y-m-d');
        $validated['end_date'] = now()->addMonths((int) $validated['estimated_close_months'])->format('Y-m-d');

        // === Auto-create Customer from End-User info ===
        $euCustomer = $this->findOrCreateCustomer(
            $validated['eu_tax_code'],
            $validated['eu_name_vi'],
            $validated['eu_name_en'],
            $validated['eu_name_abbr'] ?? null,
            $validated['address'] ?? null,
            'end_user'
        );
        $validated['customer_id'] = $euCustomer->id;
        $validated['customer_name'] = $euCustomer->name;

        // === Handle Collaboration ===
        if ($validated['collaborate_type'] === 'end_user') {
            // End-user mode: copy EU info into collaboration fields
            $validated['collaborate_company'] = $validated['eu_name_vi'];
            $validated['collaborate_tax_code'] = $validated['eu_tax_code'];
            $validated['collaborate_customer_id'] = $euCustomer->id;
        } elseif ($validated['collaborate_type'] === 'partner') {
            // Partner mode: auto-create customer if not selected from dropdown
            if (empty($validated['collaborate_customer_id'])) {
                $partnerCustomer = $this->findOrCreateCustomer(
                    $validated['collaborate_tax_code'],
                    $validated['collaborate_company'],
                    null,
                    null,
                    null,
                    'partner'
                );
                $validated['collaborate_customer_id'] = $partnerCustomer->id;
            }
        }

        // Handle BOM file upload
        if ($request->hasFile('bom_file')) {
            $validated['bom_file'] = $request->file('bom_file')->store('bom', 'public');
        }

        Project::create($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Dự án đã được tạo thành công.');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load(['customer', 'manager', 'vendor', 'collaborateCustomer', 'sales.items', 'saleItems.sale', 'exports.warehouse']);

        // Get sales statistics
        $salesStats = [
            'total_orders' => $project->sales()->count(),
            'total_revenue' => $project->total_revenue,
            'total_cost' => $project->total_cost,
            'profit' => $project->profit,
            'profit_percent' => $project->profit_percent,
            'total_debt' => $project->total_debt,
        ];

        // Get recent sales for this project
        $recentSales = $project->sales()
            ->with('items')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        // Get export statistics
        $exportStats = [
            'total_exports' => $project->exports()->count(),
            'total_export_value' => $project->total_export_value,
        ];

        // Get recent exports for this project
        $recentExports = $project->exports()
            ->with(['warehouse', 'items.product'])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return view('projects.show', compact('project', 'salesStats', 'recentSales', 'exportStats', 'recentExports'));
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        $customers = Customer::orderBy('name')->get();
        $managers = User::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('projects.edit', compact('project', 'customers', 'managers', 'suppliers'));
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $collabRequired = $request->input('collaborate_type') === 'partner' ? 'required' : 'nullable';

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('projects')->ignore($project->id)],
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:planning,in_progress,completed,cancelled,on_hold'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string'],
            // Distributor
            'vendor_id' => ['nullable', 'exists:suppliers,id'],
            'distributor_am' => ['nullable', 'string', 'max:255'],
            // End-User
            'eu_name_vi' => ['required', 'string', 'max:500'],
            'eu_name_en' => ['required', 'string', 'max:500'],
            'eu_name_abbr' => ['nullable', 'string', 'max:100'],
            'eu_tax_code' => ['required', 'string', 'max:100'],
            'eu_province' => ['nullable', 'string', 'max:100'],
            'eu_industry' => ['nullable', 'string', 'max:100'],
            // Collaboration (dynamic)
            'collaborate_type' => ['required', 'in:partner,end_user'],
            'collaborate_customer_id' => ['nullable', 'exists:customers,id'],
            'collaborate_company' => [$collabRequired, 'string', 'max:500'],
            'collaborate_tax_code' => [$collabRequired, 'string', 'max:100'],
            'collaborate_pic_name' => [$collabRequired, 'string', 'max:255'],
            'collaborate_pic_title' => [$collabRequired, 'string', 'max:255'],
            'collaborate_pic_phone' => [$collabRequired, 'string', 'max:50'],
            'collaborate_pic_email' => ['nullable', 'string', 'email', 'max:255'],
            // Project enhancements
            'estimated_close_months' => ['required', 'in:3,6,9'],
            'bom_file' => ['nullable', 'file', 'mimes:xlsx,xls,pdf,doc,docx', 'max:10240'],
            'bom_data' => ['nullable', 'string'],
            'net_to_tech_horizon' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['nullable', 'string', 'max:50'],
            'deal_type' => ['nullable', 'string', 'max:50'],
        ], [], $this->validationAttributes());

        // Recalculate end_date if estimated_close_months changed
        if ($validated['estimated_close_months'] != $project->estimated_close_months) {
            $validated['end_date'] = ($project->start_date ?? now())->copy()->addMonths((int) $validated['estimated_close_months'])->format('Y-m-d');
        }

        // === Auto-create Customer from End-User info ===
        $euCustomer = $this->findOrCreateCustomer(
            $validated['eu_tax_code'],
            $validated['eu_name_vi'],
            $validated['eu_name_en'],
            $validated['eu_name_abbr'] ?? null,
            $validated['address'] ?? null,
            'end_user'
        );
        $validated['customer_id'] = $euCustomer->id;
        $validated['customer_name'] = $euCustomer->name;

        // === Handle Collaboration ===
        if ($validated['collaborate_type'] === 'end_user') {
            $validated['collaborate_company'] = $validated['eu_name_vi'];
            $validated['collaborate_tax_code'] = $validated['eu_tax_code'];
            $validated['collaborate_customer_id'] = $euCustomer->id;
            // Clear partner-specific PIC fields
            $validated['collaborate_pic_name'] = null;
            $validated['collaborate_pic_title'] = null;
            $validated['collaborate_pic_phone'] = null;
            $validated['collaborate_pic_email'] = null;
        } elseif ($validated['collaborate_type'] === 'partner') {
            if (empty($validated['collaborate_customer_id'])) {
                $partnerCustomer = $this->findOrCreateCustomer(
                    $validated['collaborate_tax_code'],
                    $validated['collaborate_company'],
                    null,
                    null,
                    null,
                    'partner'
                );
                $validated['collaborate_customer_id'] = $partnerCustomer->id;
            }
        }

        // Handle BOM file upload
        if ($request->hasFile('bom_file')) {
            if ($project->bom_file) {
                Storage::disk('public')->delete($project->bom_file);
            }
            $validated['bom_file'] = $request->file('bom_file')->store('bom', 'public');
        }

        $project->update($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Dự án đã được cập nhật thành công.');
    }

    /**
     * Update project status via AJAX (inline dropdown).
     */
    public function updateStatus(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'status' => ['required', 'in:planning,in_progress,completed,cancelled,on_hold'],
        ]);

        $project->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Trạng thái đã được cập nhật.',
            'status_label' => $project->fresh()->status_label,
        ]);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        // Check if project has sales
        if ($project->sales()->exists() || $project->saleItems()->exists()) {
            return back()->with('error', 'Không thể xóa dự án đã có đơn hàng.');
        }

        // Check if project has exports
        if ($project->exports()->exists()) {
            return back()->with('error', 'Không thể xóa dự án đã có phiếu xuất vật tư.');
        }

        // Delete BOM file if exists
        if ($project->bom_file) {
            Storage::disk('public')->delete($project->bom_file);
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Dự án đã được xóa thành công.');
    }

    /**
     * Get projects for API/AJAX (dropdown selection)
     */
    public function getList(Request $request)
    {
        $query = Project::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Only active projects
        $query->whereIn('status', ['planning', 'in_progress']);

        $projects = $query->orderBy('name')
            ->limit(50)
            ->get(['id', 'code', 'name', 'customer_name']);

        return response()->json($projects);
    }

    /**
     * Export projects to Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status', 'customer_id']);
        $filename = 'du-an-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new ProjectsExport($filters), $filename);
    }

    /**
     * Report: Revenue by project
     */
    public function report(Request $request)
    {
        $query = Project::with(['customer']);

        // Date range filter (Active during period)
        if ($request->filled('from_date')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('end_date', '>=', $request->from_date)
                    ->orWhereNull('end_date');
            });
        }
        if ($request->filled('to_date')) {
            $query->whereDate('start_date', '<=', $request->to_date);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->filterByStatus($request->status);
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        // Calculate totals
        $totals = [
            'budget' => $projects->sum('budget'),
            'revenue' => $projects->sum(fn($p) => $p->total_revenue),
            'cost' => $projects->sum(fn($p) => $p->total_cost),
            'profit' => $projects->sum(fn($p) => $p->profit),
            'debt' => $projects->sum(fn($p) => $p->total_debt),
        ];

        return view('projects.report', compact('projects', 'totals'));
    }

    /**
     * Vietnamese attribute names for validation messages.
     */
    private function validationAttributes(): array
    {
        return [
            'code' => 'Mã dự án',
            'name' => 'Tên dự án',
            'address' => 'Địa chỉ',
            'description' => 'Mô tả',
            'budget' => 'Ngân sách dự toán',
            'status' => 'Trạng thái',
            'note' => 'Ghi chú',
            // Distributor
            'vendor_id' => 'Vendor',
            'distributor_am' => 'Distributor AM',
            // End-User
            'eu_name_vi' => 'Tên tiếng Việt (End-User)',
            'eu_name_en' => 'Tên tiếng Anh (End-User)',
            'eu_name_abbr' => 'Tên viết tắt (End-User)',
            'eu_tax_code' => 'MST / Website (End-User)',
            'eu_province' => 'Tỉnh / Thành phố',
            'eu_industry' => 'Ngành nghề',
            // Collaboration
            'collaborate_type' => 'Loại hợp tác',
            'collaborate_company' => 'Tên công ty hợp tác',
            'collaborate_tax_code' => 'Mã số thuế (hợp tác)',
            'collaborate_pic_name' => 'Tên người liên hệ (PIC)',
            'collaborate_pic_title' => 'Chức danh (PIC)',
            'collaborate_pic_phone' => 'Số điện thoại (PIC)',
            'collaborate_pic_email' => 'Email (PIC)',
            // Project
            'estimated_close_months' => 'Thời hạn dự kiến',
            'bom_file' => 'File BOM',
            'bom_data' => 'Nội dung BOM',
            'net_to_tech_horizon' => 'Net to Tech Horizon',
            'stage' => 'Giai đoạn',
            'deal_type' => 'Loại deal',
        ];
    }
}
