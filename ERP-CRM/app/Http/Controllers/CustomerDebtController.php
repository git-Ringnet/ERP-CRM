<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\PaymentHistory;
use App\Services\DebtAgingReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerDebtController extends Controller
{
    protected DebtAgingReportService $agingReportService;

    public function __construct(DebtAgingReportService $agingReportService)
    {
        $this->agingReportService = $agingReportService;
    }
    /**
     * Display customer debt list
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', CustomerDebt::class);

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
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('customers.name', 'like', "%{$search}%")
                        ->orWhere('customers.code', 'like', "%{$search}%")
                        ->orWhere('customers.phone', 'like', "%{$search}%");
                });
            })
            ->when($debtStatus === 'has_debt', function ($query) {
                $query->whereRaw('(SELECT SUM(s.debt_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")) > 0');
            })
            ->when($debtStatus === 'no_debt', function ($query) {
                $query->whereRaw('COALESCE((SELECT SUM(s.debt_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")), 0) = 0');
            })
            ->when($debtStatus === 'over_limit', function ($query) {
                $query->whereRaw('(SELECT SUM(s.debt_amount) FROM sales s WHERE s.customer_id = customers.id AND s.status IN ("approved", "shipping", "completed")) > customers.debt_limit AND customers.debt_limit > 0');
            })
            ->when($debtStatus === 'overdue', function ($query) {
                // Customers with overdue debt (sales older than debt_days)
                $query->whereExists(function ($subquery) {
                    $subquery->selectRaw('1')
                        ->from('sales')
                        ->whereColumn('sales.customer_id', 'customers.id')
                        ->whereIn('sales.status', ['approved', 'shipping', 'completed'])
                        ->where('sales.debt_amount', '>', 0)
                        ->whereRaw('DATEDIFF(CURDATE(), sales.date) > COALESCE(customers.debt_days, 30)');
                });
            })
            ->when($debtStatus === 'due_soon', function ($query) {
                // Customers with debt due within 7 days
                $query->whereExists(function ($subquery) {
                    $subquery->selectRaw('1')
                        ->from('sales')
                        ->whereColumn('sales.customer_id', 'customers.id')
                        ->whereIn('sales.status', ['approved', 'shipping', 'completed'])
                        ->where('sales.debt_amount', '>', 0)
                        ->whereRaw('DATEDIFF(CURDATE(), sales.date) BETWEEN (COALESCE(customers.debt_days, 30) - 7) AND COALESCE(customers.debt_days, 30)');
                });
            })
            ->when($sortBy === 'debt_amount', function ($query) use ($sortOrder) {
                $query->orderByRaw('total_debt ' . $sortOrder);
            }, function ($query) use ($sortBy, $sortOrder) {
                $query->orderBy("customers.{$sortBy}", $sortOrder);
            })
            ->paginate(15)
            ->withQueryString();

        // Summary statistics
        $summary = [
            'total_customers_with_debt' => Customer::whereHas('sales', function ($q) {
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
        $this->authorize('viewAny', CustomerDebt::class);

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
        $this->authorize('recordPayment', CustomerDebt::class);

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
        $this->authorize('deletePayment', CustomerDebt::class);

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
        $this->authorize('export', CustomerDebt::class);

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
            ->orderByRaw('total_debt DESC')
            ->get();

        return \Excel::download(
            new \App\Exports\CustomerDebtsExport($customers),
            'cong-no-khach-hang-' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Get overdue debt amount
     */
    private function getOverdueDebt(): float
    {
        return Sale::whereIn('status', ['approved', 'shipping', 'completed'])
            ->where('debt_amount', '>', 0)
            ->whereHas('customer', function ($q) {
                $q->where('debt_days', '>', 0);
            })
            ->get()
            ->filter(function ($sale) {
                $dueDate = $sale->date->addDays($sale->customer->debt_days ?? 30);
                return now()->gt($dueDate);
            })
            ->sum('debt_amount');
    }

    /**
     * Display aging report
     */
    public function agingReport(Request $request)
    {
        $this->authorize('viewAny', CustomerDebt::class);

        $filters = [
            'search' => $request->input('search'),
            'customer_id' => $request->input('customer_id'),
        ];

        $report = $this->agingReportService->getAgingReport($filters);
        $stats = $this->agingReportService->getSummaryStats();

        return view('customer-debts.aging-report', compact('report', 'stats', 'filters'));
    }

    /**
     * Export aging report to CSV
     */
    public function exportAgingReport(Request $request)
    {
        $this->authorize('export', CustomerDebt::class);

        $filters = [
            'search' => $request->input('search'),
            'customer_id' => $request->input('customer_id'),
        ];

        $report = $this->agingReportService->getAgingReport($filters);

        $filename = 'bao-cao-tuoi-no-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($report) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Header row
            fputcsv($file, [
                'Mã KH',
                'Tên khách hàng',
                'Điện thoại',
                'Hiện tại (0-30 ngày)',
                '31-60 ngày',
                '61-90 ngày',
                'Trên 90 ngày',
                'Tổng nợ',
                'Nợ quá hạn',
                'Mức rủi ro',
            ]);

            foreach ($report['customers'] as $customer) {
                fputcsv($file, [
                    $customer['customer_code'],
                    $customer['customer_name'],
                    $customer['phone'],
                    number_format($customer['current'], 0, ',', '.'),
                    number_format($customer['days_31_60'], 0, ',', '.'),
                    number_format($customer['days_61_90'], 0, ',', '.'),
                    number_format($customer['over_90'], 0, ',', '.'),
                    number_format($customer['total'], 0, ',', '.'),
                    number_format($customer['overdue_amount'], 0, ',', '.'),
                    DebtAgingReportService::getRiskLabel($customer['risk_level']),
                ]);
            }

            // Summary row
            fputcsv($file, []);
            fputcsv($file, [
                'TỔNG CỘNG',
                '',
                '',
                number_format($report['summary']['current'], 0, ',', '.'),
                number_format($report['summary']['days_31_60'], 0, ',', '.'),
                number_format($report['summary']['days_61_90'], 0, ',', '.'),
                number_format($report['summary']['over_90'], 0, ',', '.'),
                number_format($report['summary']['total'], 0, ',', '.'),
                '',
                '',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Statement of Account for a customer
     */
    public function statement(Request $request, Customer $customer)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $openingBalance = Sale::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->where('date', '<', $dateFrom)
            ->sum('debt_amount');

        $sales = Sale::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get();

        $payments = PaymentHistory::where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date')
            ->get();

        $transactions = collect();

        foreach ($sales as $sale) {
            $transactions->push([
                'date' => $sale->date->format('Y-m-d'),
                'type' => 'debit',
                'code' => $sale->code,
                'description' => 'Đơn hàng bán',
                'debit' => (float) $sale->total,
                'credit' => 0,
            ]);
        }

        foreach ($payments as $payment) {
            $description = 'Thanh toán';
            if ($payment->payment_method) {
                $methodLabels = ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'card' => 'Thẻ', 'other' => 'Khác'];
                $description .= ' - ' . ($methodLabels[$payment->payment_method] ?? $payment->payment_method);
            }
            $transactions->push([
                'date' => $payment->payment_date->format('Y-m-d'),
                'type' => 'credit',
                'code' => $payment->sale?->code ?? 'TT',
                'description' => $description,
                'debit' => 0,
                'credit' => (float) $payment->amount,
            ]);
        }

        $transactions = $transactions->sortBy('date')->values();

        $runningBalance = (float) $openingBalance;
        $transactions = $transactions->map(function ($item) use (&$runningBalance) {
            $runningBalance += $item['debit'] - $item['credit'];
            $item['balance'] = $runningBalance;
            return $item;
        });

        $closingBalance = $runningBalance;

        return view('customer-debts.statement', compact(
            'customer', 'transactions', 'openingBalance', 'closingBalance',
            'dateFrom', 'dateTo'
        ));
    }

    /**
     * Export customer statement to CSV
     */
    public function exportStatement(Request $request, Customer $customer)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $openingBalance = Sale::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->where('date', '<', $dateFrom)
            ->sum('debt_amount');

        $sales = Sale::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get();

        $payments = PaymentHistory::where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date')
            ->get();

        $filename = "sao-ke-kh-{$customer->code}-{$dateFrom}-{$dateTo}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($customer, $sales, $payments, $openingBalance, $dateFrom, $dateTo) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ["SAO KÊ CÔNG NỢ KHÁCH HÀNG"]);
            fputcsv($file, ["KH: {$customer->name} ({$customer->code})"]);
            fputcsv($file, ["Kỳ: {$dateFrom} đến {$dateTo}"]);
            fputcsv($file, []);
            fputcsv($file, ['Ngày', 'Loại', 'Mã chứng từ', 'Diễn giải', 'Phát sinh Nợ', 'Phát sinh Có', 'Lũy kế']);

            $balance = (float) $openingBalance;
            fputcsv($file, ['', '', '', 'Dư đầu kỳ', '', '', number_format($balance, 0, ',', '.')]);

            $transactions = collect();
            foreach ($sales as $sale) {
                $transactions->push(['date' => $sale->date->format('Y-m-d'), 'type' => 'Bán hàng', 'code' => $sale->code, 'desc' => 'Đơn hàng bán', 'debit' => (float)$sale->total, 'credit' => 0]);
            }
            foreach ($payments as $payment) {
                $transactions->push(['date' => $payment->payment_date->format('Y-m-d'), 'type' => 'Thanh toán', 'code' => $payment->sale?->code ?? 'TT', 'desc' => 'Thanh toán', 'debit' => 0, 'credit' => (float)$payment->amount]);
            }
            $transactions = $transactions->sortBy('date');

            foreach ($transactions as $t) {
                $balance += $t['debit'] - $t['credit'];
                fputcsv($file, [
                    $t['date'], $t['type'], $t['code'], $t['desc'],
                    $t['debit'] > 0 ? number_format($t['debit'], 0, ',', '.') : '',
                    $t['credit'] > 0 ? number_format($t['credit'], 0, ',', '.') : '',
                    number_format($balance, 0, ',', '.'),
                ]);
            }

            fputcsv($file, ['', '', '', 'Dư cuối kỳ', '', '', number_format($balance, 0, ',', '.')]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

