<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSaleRevenue;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeSaleRevenueController extends Controller
{
    /**
     * Display a listing of recorded sales revenues.
     */
    public function index(Request $request)
    {
        $query = EmployeeSaleRevenue::with(['user', 'recorder']);

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $revenues = $query->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $employees = User::whereHas('roles', function($q) {
            $q->where('name', 'sales'); // Giả định có role sales
        })->get();
        
        // Nếu không có role sales hoặc filter trả về rỗng, lấy tất cả active users để đảm bảo UI hoạt động
        if ($employees->isEmpty()) {
            $employees = User::where('status', 'active')->get();
        }

        return view('employee-sales-revenues.index', compact('revenues', 'employees'));
    }

    /**
     * Show the form for creating a new recorded revenue.
     */
    public function create()
    {
        $employees = User::where('status', 'active')->orderBy('name')->get();
        $currentMonth = date('n');
        $currentYear = date('Y');

        return view('employee-sales-revenues.create', compact('employees', 'currentMonth', 'currentYear'));
    }

    /**
     * Store a newly created recorded revenue in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'total_revenue' => 'required|numeric|min:0',
            'total_profit' => 'required|numeric',
            'quantity_on_target' => 'nullable|integer|min:0',
            'note' => 'nullable|string',
        ]);

        // Check for duplicates
        if (EmployeeSaleRevenue::where('user_id', $validated['user_id'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists()) {
            return back()->with('error', 'Nhân viên này đã được ghi nhận doanh số cho tháng/năm đã chọn.')->withInput();
        }

        $validated['recorded_by'] = auth()->id();
        
        EmployeeSaleRevenue::create($validated);

        return redirect()->route('employee-sales-revenues.index')
            ->with('success', 'Đã ghi nhận doanh số thành công.');
    }

    /**
     * API method to get suggested revenue and profit.
     */
    public function getSuggestedRevenue(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $userId = $request->user_id;
        $month = $request->month;
        $year = $request->year;

        // Tính toán dựa trên đơn hàng (Sale)
        // Lấy các đơn hàng của nhân viên trong tháng/năm đó
        // Giả định trạng thái 'completed' hoặc 'shipping' là tính doanh số
        $stats = Sale::where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('status', ['completed', 'shipping', 'approved']) 
            ->select(
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(margin) as profit'),
                DB::raw('COUNT(*) as count')
            )
            ->first();

        return response()->json([
            'revenue' => (float)($stats->revenue ?? 0),
            'profit' => (float)($stats->profit ?? 0),
            'count' => (int)($stats->count ?? 0),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeSaleRevenue $employeeSaleRevenue)
    {
        $employeeSaleRevenue->delete();
        return back()->with('success', 'Đã xóa bản ghi doanh số.');
    }
}
