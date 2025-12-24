<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request)
    {
        $query = Project::with(['customer', 'manager']);

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

        $projects = $query->orderBy('created_at', 'desc')->paginate(10);
        $customers = Customer::orderBy('name')->get();

        return view('projects.index', compact('projects', 'customers'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $managers = User::orderBy('name')->get();
        $code = $this->generateProjectCode();

        return view('projects.create', compact('customers', 'managers', 'code'));
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
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:projects,code'],
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:planning,in_progress,completed,cancelled,on_hold'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string'],
        ]);

        // Get customer name
        if (!empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $validated['customer_name'] = $customer?->name;
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
        $project->load(['customer', 'manager', 'sales.items', 'saleItems.sale', 'exports.warehouse']);
        
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
        $customers = Customer::orderBy('name')->get();
        $managers = User::orderBy('name')->get();

        return view('projects.edit', compact('project', 'customers', 'managers'));
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('projects')->ignore($project->id)],
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:planning,in_progress,completed,cancelled,on_hold'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string'],
        ]);

        // Get customer name
        if (!empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $validated['customer_name'] = $customer?->name;
        } else {
            $validated['customer_name'] = null;
        }

        $project->update($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Dự án đã được cập nhật thành công.');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project)
    {
        // Check if project has sales
        if ($project->sales()->exists() || $project->saleItems()->exists()) {
            return back()->with('error', 'Không thể xóa dự án đã có đơn hàng.');
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
     * Report: Revenue by project
     */
    public function report(Request $request)
    {
        $query = Project::with(['customer']);

        // Date range filter
        if ($request->filled('from_date')) {
            $query->where('start_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('start_date', '<=', $request->to_date);
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
}
