<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use App\Models\Attendance;
use App\Models\EmployeeSaleRevenue;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Payroll::class); // HR permission

        $payrolls = Payroll::orderBy('year', 'desc')->orderBy('month', 'desc')->paginate(10);
        return view('payrolls.index', compact('payrolls'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Payroll::class);

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        return view('payrolls.create', compact('currentMonth', 'currentYear'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Payroll::class);

        $request->validate([
            'title' => 'required|string|max:255',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'standard_working_days' => 'required|numeric|min:1',
        ]);

        // Check if payroll already exists for this month/year
        $exists = Payroll::where('month', $request->month)->where('year', $request->year)->exists();
        if ($exists) {
            return back()->with('error', 'Đã tồn tại bảng lương cho Tháng ' . $request->month . '/' . $request->year . '. Không thể tạo mới.');
        }

        DB::beginTransaction();
        try {
            $payroll = Payroll::create([
                'title' => $request->title,
                'month' => $request->month,
                'year' => $request->year,
                'status' => 'draft',
                'standard_working_days' => $request->standard_working_days,
            ]);

            // Tự động sinh PayrollItems cho nhân viên
            $this->generatePayrollItems($payroll);

            DB::commit();
            return redirect()->route('payrolls.show', $payroll->id)->with('success', 'Bảng lương đã được tạo và tự động tổng hợp dữ liệu.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra trong quá trình tính lương: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Payroll $payroll)
    {
        // For general users, later we can add policy to only let them see their own item
        // But for now context is HR managing payroll
        $this->authorize('view', $payroll);

        $items = $payroll->items()->with('user')->get();
        return view('payrolls.show', compact('payroll', 'items'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payroll $payroll)
    {
        $this->authorize('delete', $payroll);

        if ($payroll->status == 'paid') {
            return back()->with('error', 'Không thể xóa bảng lương đã được thanh toán.');
        }

        $payroll->delete(); // This will cascade delete items due to DB constraints (or we do it manually if needed)
        
        return redirect()->route('payrolls.index')->with('success', 'Đã xóa bảng lương.');
    }

    /**
     * Cập nhật trạng thái bảng lương
     */
    public function updateStatus(Request $request, Payroll $payroll)
    {
        $this->authorize('update', $payroll);

        $statusLabels = [
            'draft' => 'Bản nháp',
            'pending_approval' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'paid' => 'Đã thanh toán (Hoàn tất)',
        ];

        $payroll->update(['status' => $request->status]);
        $labelText = $statusLabels[$request->status] ?? $request->status;

        return back()->with('success', 'Trạng thái bảng lương đã được cập nhật sang "' . $labelText . '".');
    }

    /**
     * Service Tự động tính toán lương
     */
    private function generatePayrollItems(Payroll $payroll)
    {
        // Lấy tất cả nhân viên còn làm việc
        $users = User::whereNotNull('employee_code')
            ->whereIn('status', ['active', 'leave'])
            ->get();

        foreach ($users as $user) {
            // Đếm attendance hợp lệ trong tháng của user
            $validAttendances = Attendance::where('user_id', $user->id)
                ->whereYear('date', $payroll->year)
                ->whereMonth('date', $payroll->month)
                ->whereNotNull('check_in_time')
                // ->whereNotNull('check_out_time') // Optional strictness
                ->count();

            // Tính Lương cơ bản dựa trên thời gian
            $basicSalary = $user->salary ?? 0;
            $salaryPerDay = $payroll->standard_working_days > 0 ? ($basicSalary / $payroll->standard_working_days) : 0;
            $calculatedSalary = $salaryPerDay * $validAttendances;

            // Tính các Phụ cấp & Khấu trừ
            $totalAllowance = 0;
            $totalDeduction = 0;
            
            $components = $user->employeeSalaryComponents()->with('salaryComponent')->get();
            foreach ($components as $ec) {
                if (!$ec->salaryComponent) continue;

                $comp = $ec->salaryComponent;
                $amount = $ec->amount;
                
                // Nếu là tỷ lệ %
                if ($comp->amount_type == 'percentage') {
                    $amount = ($basicSalary * $amount) / 100;
                }

                if ($comp->type == 'allowance') {
                    $totalAllowance += $amount;
                } else {
                    $totalDeduction += $amount;
                }
            }

            // Hoa hồng bán hàng (Từ module đơn hàng và chi phí)
            $commission = \App\Models\SaleExpense::where('type', 'commission')
                ->whereHas('sale', function($q) use ($user, $payroll) {
                    $q->where('user_id', $user->id)
                      ->whereYear('date', $payroll->year)
                      ->whereMonth('date', $payroll->month)
                      ->whereIn('status', ['approved', 'shipping', 'completed']);
                })
                ->sum('amount');

            // Tính Net Salary (Thực nhận)
            $netSalary = $calculatedSalary + $totalAllowance - $totalDeduction + $commission;
            
            if ($netSalary < 0) {
                $netSalary = 0; // Tránh tình trạng nợ lương vô lý
            }

            // Ghi nhận vào payroll_items
            PayrollItem::create([
                'payroll_id' => $payroll->id,
                'user_id' => $user->id,
                'basic_salary' => $basicSalary,
                'actual_working_days' => $validAttendances,
                'total_allowance' => $totalAllowance,
                'total_deduction' => $totalDeduction,
                'commission_bonus' => $commission,
                'net_salary' => $netSalary,
            ]);
        }
    }
}
