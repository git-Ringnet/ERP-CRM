<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerDebtController extends Controller
{
    /**
     * Display customer debt list
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $debtStatus = $request->input('debt_status');
        $sortBy = $request->input('sort_by', 'debt_amount');
        $sortOrder = $request->input('sort_order', 'desc');

        // Get customers with debt summary using subquery approach
        $customers = Customer::select([
                'customers.id',
                'customers.code',
                'customers.name',
                'customers.phone',
                'customers.debt_limit',
                'customers.debt_days',
            ])
            ->selectRaw('COALESCE((SELECT SUM(s.total) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")), 0) as total_sales')
            ->selectRaw('COALESCE((SELECT SUM(s.paid_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")), 0) as total_paid')
            ->selectRaw('COALESCE((SELECT SUM(s.debt_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")), 0) as total_debt')
            ->selectRaw('COALESCE((SELECT COUNT(*) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed") AND s.debt_amount > 0), 0) as unpaid_orders')
            ->when($search, function($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('customers.name', 'like', "%{$search}%")
                      ->orWhere('customers.code', 'like', "%{$search}%")
                      ->orWhere('customers.phone', 'like', "%{$search}%");
                });
            })
            ->when($debtStatus === 'has_debt', function($query) {
                $query->whereRaw('(SELECT SUM(s.debt_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")) > 0');
            })
            ->when($debtStatus === 'no_debt', function($query) {
                $query->whereRaw('COALESCE((SELECT SUM(s.debt_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")), 0) = 0');
            })
            ->when($debtStatus === 'over_limit', function($query) {
                $query->whereRaw('(SELECT SUM(s.debt_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")) > customers.debt_limit AND customers.debt_limit > 0');
            })
            ->when($sortBy === 'debt_amount', function($query) use ($sortOrder) {
                $query->orderByRaw('total_debt ' . $sortOrder);
            }, function($query) use ($sortBy, $sortOrder) {
                $query->orderBy("customers.{$sortBy}", $sortOrder);
            })
            ->paginate(15)
            ->withQueryString();

        // Summary statistics
        $summary = [
            'total_customers_with_debt' => Customer::whereHas('sales', function($q) {
                $q->where('debt_amount', '>', 0)
                  ->whereIn('status', ['approved', 'shipping', 'completed']);
            })->count(),
            'total_debt' => Sale::whereIn('status', ['approved', 'shipping', 'completed'])->sum('debt_amount'),
            'total_overdue' => $this->getOverdueDebt(),
        ];

        return view('customer-debts.index', compact('customers', 'summary', 'search', 'debtStatus', 'sortBy', 'sortOrder'));
    }

    /**
     * Show customer debt detail
     */
    public function show(Customer $customer)
    {
        $sales = Sale::where('customer_id', $customer->id)
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->orderBy('date', 'desc')
            ->get();

        $paymentHistories = PaymentHistory::where('customer_id', $customer->id)
            ->orderBy('payment_date', 'desc')
            ->get();

        $summary = [
            'total_sales' => $sales->sum('total'),
            'total_paid' => $sales->sum('paid_amount'),
            'total_debt' => $sales->sum('debt_amount'),
            'unpaid_orders' => $sales->where('debt_amount', '>', 0)->count(),
        ];

        return view('customer-debts.show', compact('customer', 'sales', 'paymentHistories', 'summary'));
    }


    /**
     * Record payment for a sale
     */
    public function recordPayment(Request $request, Sale $sale)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $sale->debt_amount,
            'payment_method' => 'required|in:cash,bank_transfer,card,other',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
        ], [
            'amount.required' => 'Vui lòng nhập số tiền thanh toán',
            'amount.min' => 'Số tiền phải lớn hơn 0',
            'amount.max' => 'Số tiền không được vượt quá công nợ còn lại',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán',
            'payment_date.required' => 'Vui lòng chọn ngày thanh toán',
        ]);

        DB::beginTransaction();
        try {
            // Create payment history
            PaymentHistory::create([
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'payment_date' => $request->payment_date,
                'note' => $request->note,
                'created_by' => 'Admin', // TODO: Replace with auth user
            ]);

            // Update sale payment info
            $sale->paid_amount += $request->amount;
            $sale->updateDebt();
            $sale->save();

            DB::commit();

            return redirect()->back()->with('success', 'Ghi nhận thanh toán thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Delete payment record
     */
    public function deletePayment(PaymentHistory $payment)
    {
        DB::beginTransaction();
        try {
            $sale = $payment->sale;
            
            // Revert payment from sale
            $sale->paid_amount -= $payment->amount;
            $sale->updateDebt();
            $sale->save();

            // Delete payment record
            $payment->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Đã xóa bản ghi thanh toán!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Export customer debt report
     */
    public function export()
    {
        $customers = Customer::select([
                'customers.id',
                'customers.code',
                'customers.name',
                'customers.phone',
                'customers.debt_limit',
            ])
            ->selectRaw('COALESCE((SELECT SUM(s.total) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")), 0) as total_sales')
            ->selectRaw('COALESCE((SELECT SUM(s.paid_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")), 0) as total_paid')
            ->selectRaw('COALESCE((SELECT SUM(s.debt_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")), 0) as total_debt')
            ->whereRaw('(SELECT SUM(s.debt_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")) > 0')
            ->orderByRaw('total_debt DESC')
            ->get();

        $filename = 'cong-no-khach-hang-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($customers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            
            fputcsv($file, ['Mã KH', 'Tên khách hàng', 'Điện thoại', 'Tổng mua', 'Đã thanh toán', 'Công nợ', 'Hạn mức nợ']);
            
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->code,
                    $customer->name,
                    $customer->phone,
                    number_format($customer->total_sales, 0, ',', '.'),
                    number_format($customer->total_paid, 0, ',', '.'),
                    number_format($customer->total_debt, 0, ',', '.'),
                    number_format($customer->debt_limit ?? 0, 0, ',', '.'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get overdue debt amount
     */
    private function getOverdueDebt(): float
    {
        return Sale::whereIn('status', ['approved', 'shipping', 'completed'])
            ->where('debt_amount', '>', 0)
            ->whereHas('customer', function($q) {
                $q->where('debt_days', '>', 0);
            })
            ->get()
            ->filter(function($sale) {
                $dueDate = $sale->date->addDays($sale->customer->debt_days ?? 30);
                return now()->gt($dueDate);
            })
            ->sum('debt_amount');
    }

}
