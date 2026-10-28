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

        $user = auth()->user();
        $userSqlCondition = (!$user->can('view_all_sales')) ? " AND s.user_id = " . (int)$user->id : "";

        // BS 16: giai đoạn báo cáo (lọc theo ngày ghi nhận nợ = ngày XHĐ)
        [$period, $dateFrom, $dateTo] = $this->resolvePeriod($request);
        $periodSql = $this->periodSql('s', $dateFrom, $dateTo);

        // BS 06: chỉ đơn đủ điều kiện + đã XHĐ mới là công nợ phải thu
        $receivableCond = 's.customer_id = customers.id AND ' . Sale::receivableSql('s') . $userSqlCondition . $periodSql;
        // Ứng trước: đơn đủ điều kiện chưa XHĐ nhưng khách đã chuyển tiền (theo dõi riêng, không tính vào nợ)
        $advanceCond = 's.customer_id = customers.id AND s.status IN ("' . implode('","', Sale::RECEIVABLE_STATUSES) . '")'
            . ' AND NOT ' . Sale::invoicedSql('s') . ' AND s.paid_amount > 0' . $userSqlCondition
            . $this->periodSql('s', $dateFrom, $dateTo, 'DATE(s.date)');

        // Get customers with debt summary using subquery approach
        $customersQuery = Customer::select([
            'customers.id',
            'customers.tax_code',
            'customers.name',
            'customers.phone',
            'customers.debt_limit',
            'customers.debt_days',
        ])
            ->selectRaw('COALESCE((SELECT SUM(s.total) FROM sales s WHERE ' . $receivableCond . '), 0) as receivable_sales')
            ->selectRaw('COALESCE((SELECT SUM(s.paid_amount) FROM sales s WHERE ' . $receivableCond . '), 0) as receivable_paid')
            ->selectRaw('COALESCE((SELECT SUM(s.debt_amount) FROM sales s WHERE ' . $receivableCond . '), 0) as receivable_debt')
            ->selectRaw('COALESCE((SELECT COUNT(*) FROM sales s WHERE ' . $receivableCond . ' AND s.debt_amount > 0), 0) as unpaid_orders')
            ->selectRaw('COALESCE((SELECT SUM(s.paid_amount) FROM sales s WHERE ' . $advanceCond . '), 0) as total_advance');

        if (!$user->can('view_all_sales')) {
            $userId = $user->id;
            $customersQuery->whereHas('sales', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        // Chỉ khách hàng có phát sinh công nợ (đang nợ hoặc đã từng nợ và trả xong) hoặc có tiền ứng trước trong kỳ;
        // khách hàng chưa phát sinh tiền với công ty không hiện ở trang công nợ.
        $customersQuery->whereRaw('(EXISTS (SELECT 1 FROM sales s WHERE ' . $receivableCond . ')'
            . ' OR EXISTS (SELECT 1 FROM sales s WHERE ' . $advanceCond . '))');

        $customers = $customersQuery
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('customers.name', 'like', "%{$search}%")
                        ->orWhere('customers.tax_code', 'like', "%{$search}%")
                        ->orWhere('customers.phone', 'like', "%{$search}%");
                });
            })
            ->when($debtStatus === 'has_debt', function ($query) use ($receivableCond) {
                $query->whereRaw('(SELECT SUM(s.debt_amount) FROM sales s WHERE ' . $receivableCond . ') > 0');
            })
            ->when($debtStatus === 'no_debt', function ($query) use ($receivableCond) {
                $query->whereRaw('COALESCE((SELECT SUM(s.debt_amount) FROM sales s WHERE ' . $receivableCond . '), 0) = 0');
            })
            ->when($debtStatus === 'over_limit', function ($query) use ($receivableCond) {
                $query->whereRaw('(SELECT SUM(s.debt_amount) FROM sales s WHERE ' . $receivableCond . ') > customers.debt_limit AND customers.debt_limit > 0');
            })
            ->when($debtStatus === 'advance', function ($query) use ($advanceCond) {
                $query->whereRaw('(SELECT SUM(s.paid_amount) FROM sales s WHERE ' . $advanceCond . ') > 0');
            })
            ->when($debtStatus === 'overdue', function ($query) use ($receivableCond) {
                // Customers with overdue debt (quá số ngày nợ kể từ ngày XHĐ)
                $query->whereRaw('EXISTS (SELECT 1 FROM sales s WHERE ' . $receivableCond . ' AND s.debt_amount > 0'
                    . ' AND DATEDIFF(CURDATE(), ' . Sale::receivableDateSql('s') . ') > COALESCE(customers.debt_days, 30))');
            })
            ->when($debtStatus === 'due_soon', function ($query) use ($receivableCond) {
                // Customers with debt due within 7 days
                $query->whereRaw('EXISTS (SELECT 1 FROM sales s WHERE ' . $receivableCond . ' AND s.debt_amount > 0'
                    . ' AND DATEDIFF(CURDATE(), ' . Sale::receivableDateSql('s') . ') BETWEEN (COALESCE(customers.debt_days, 30) - 7) AND COALESCE(customers.debt_days, 30))');
            })
            ->when($sortBy === 'debt_amount', function ($query) use ($sortOrder) {
                $query->orderByRaw('receivable_debt ' . $sortOrder);
            }, function ($query) use ($sortBy, $sortOrder) {
                $query->orderBy("customers.{$sortBy}", $sortOrder);
            })
            ->paginate(15)
            ->withQueryString();

        // BS 16: dropdown danh sách đơn của từng khách hàng trên trang (cùng điều kiện & kỳ lọc với các cột tổng)
        $ordersByCustomer = $this->debtOrdersQuery($user, $dateFrom, $dateTo)
            ->whereIn('sales.customer_id', $customers->pluck('id'))
            ->get()
            ->groupBy('customer_id');
        $advanceOrdersByCustomer = $this->advanceOrdersQuery($user, $dateFrom, $dateTo)
            ->whereIn('sales.customer_id', $customers->pluck('id'))
            ->get()
            ->groupBy('customer_id');

        // Summary statistics (using Aging Report Service to get detailed dashboard stats) - cùng kỳ lọc
        $summary = $this->agingReportService->getSummaryStats(
            $dateFrom,
            $dateTo,
            $user->can('view_all_sales') ? null : (int) $user->id
        );
        $summary['total_customers_with_debt'] = $summary['total_customers'] ?? 0;
        $summary['total_overdue'] = $summary['overdue_amount'] ?? 0;
        $summary['total_advance'] = (float) $this->advanceOrdersQuery($user, $dateFrom, $dateTo)->sum('sales.paid_amount');

        return view('customer-debts.index', compact(
            'customers', 'summary', 'search', 'debtStatus', 'sortBy', 'sortOrder',
            'period', 'dateFrom', 'dateTo', 'ordersByCustomer', 'advanceOrdersByCustomer'
        ));
    }

    /**
     * BS 16: giai đoạn báo cáo -> [period, date_from (Y-m-d|null), date_to (Y-m-d|null)].
     */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->input('period', 'all');
        $today = now();

        [$from, $to] = match ($period) {
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$today->copy()->startOfQuarter(), $today->copy()->endOfQuarter()],
            'last_quarter' => [$today->copy()->subQuarterNoOverflow()->startOfQuarter(), $today->copy()->subQuarterNoOverflow()->endOfQuarter()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'custom' => [
                $request->filled('date_from') ? \Carbon\Carbon::parse($request->input('date_from')) : null,
                $request->filled('date_to') ? \Carbon\Carbon::parse($request->input('date_to')) : null,
            ],
            default => [null, null],
        };

        if (!in_array($period, ['this_month', 'last_month', 'this_quarter', 'last_quarter', 'this_year', 'custom'], true)) {
            $period = 'all';
        }

        return [$period, $from?->format('Y-m-d'), $to?->format('Y-m-d')];
    }

    /** Điều kiện kỳ lọc dạng SQL (ngày đã chuẩn hóa Y-m-d nên nhúng an toàn). */
    private function periodSql(string $alias, ?string $dateFrom, ?string $dateTo, ?string $dateExpr = null): string
    {
        $dateExpr = $dateExpr ?? Sale::receivableDateSql($alias);
        $sql = '';
        if ($dateFrom) {
            $sql .= " AND {$dateExpr} >= '{$dateFrom}'";
        }
        if ($dateTo) {
            $sql .= " AND {$dateExpr} <= '{$dateTo}'";
        }

        return $sql;
    }

    /** Đơn đã ghi nhận công nợ trong kỳ (theo quyền xem) - cùng tập đơn với các cột tổng của khách hàng. */
    private function debtOrdersQuery($user, ?string $dateFrom, ?string $dateTo)
    {
        return Sale::select(['sales.id', 'sales.code', 'sales.customer_id', 'sales.user_id', 'sales.date', 'sales.invoice_date', 'sales.total', 'sales.paid_amount', 'sales.debt_amount'])
            ->with('user:id,name')
            ->receivable()
            ->when($dateFrom, fn ($q) => $q->whereRaw(Sale::receivableDateSql() . ' >= ?', [$dateFrom]))
            ->when($dateTo, fn ($q) => $q->whereRaw(Sale::receivableDateSql() . ' <= ?', [$dateTo]))
            ->when(!$user->can('view_all_sales'), fn ($q) => $q->where('sales.user_id', $user->id))
            ->orderByRaw(Sale::receivableDateSql() . ' asc');
    }

    /** Đơn chưa XHĐ nhưng khách đã ứng trước (theo dõi riêng, không tính vào công nợ). */
    private function advanceOrdersQuery($user, ?string $dateFrom, ?string $dateTo)
    {
        return Sale::select(['sales.id', 'sales.code', 'sales.customer_id', 'sales.user_id', 'sales.date', 'sales.total', 'sales.paid_amount'])
            ->with('user:id,name')
            ->advancePaid()
            ->when($dateFrom, fn ($q) => $q->whereDate('sales.date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('sales.date', '<=', $dateTo))
            ->when(!$user->can('view_all_sales'), fn ($q) => $q->where('sales.user_id', $user->id))
            ->orderBy('sales.date');
    }

    /**
     * Show customer debt detail
     */
    public function show(Customer $customer)
    {
        $this->authorize('viewAny', CustomerDebt::class);

        $user = auth()->user();
        // BS 06: chỉ đơn đã ghi nhận công nợ (đủ điều kiện + đã XHĐ)
        $salesQuery = Sale::where('customer_id', $customer->id)->receivable();

        if (!$user->can('view_all_sales')) {
            $salesQuery->where('user_id', $user->id);
        }

        $sales = $salesQuery->orderBy('date', 'desc')->get();

        // Đơn chưa XHĐ nhưng khách đã ứng trước - hiển thị riêng
        $advanceSales = Sale::where('customer_id', $customer->id)->advancePaid()
            ->when(!$user->can('view_all_sales'), fn ($q) => $q->where('user_id', $user->id))
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

        $summary['total_advance'] = $advanceSales->sum('paid_amount');

        return view('customer-debts.show', compact('customer', 'sales', 'advanceSales', 'paymentHistories', 'summary'));
    }


    /**
     * Record payment for a sale
     */
    public function recordPayment(Request $request, Sale $sale)
    {
        $this->authorize('recordPayment', CustomerDebt::class);

        // This legacy endpoint must not post a payment directly. The sale
        // payment workflow requires UNC evidence and Finance approval.
        return redirect()->route('sales.show', $sale)->with(
            'warning',
            'Vui lòng gửi UNC tại chi tiết đơn hàng để Finance xác nhận thanh toán. Công nợ chỉ được cập nhật sau khi duyệt.'
        );

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
                // Keep an auditable actor instead of assigning every payment
                // to "Admin" regardless of who actually recorded it.
                'created_by' => auth()->user()?->name,
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

            // Delete payment record
            $payment->delete();

            // Lần thu gắn với một đợt (ghi chú: thanh toán đợt "<tên đợt>"): số đã thu của đợt không được vượt tổng lịch sử thu
            // còn lại của đợt. Xóa bản ghi trùng thì đợt giữ nguyên; xóa lần thu thật thì đợt quay về thu 1 phần / chưa thu.
            if (preg_match('/thanh toán đợt "(.+?)"/u', (string) $payment->note, $m)) {
                $schedule = $sale->paymentSchedules()->where('milestone_name', $m[1])->first();
                if ($schedule && !in_array($schedule->confirmed_by, ['System Auto', null, ''], true)) {
                    $histLeft = (float) PaymentHistory::where('sale_id', $sale->id)
                        ->where('note', 'like', '%thanh toán đợt "' . $m[1] . '"%')->sum('amount');
                    $schedPaid = (float) $schedule->paid_amount > 0 ? (float) $schedule->paid_amount : (float) $schedule->amount;
                    $newPaid = min($schedPaid, $histLeft);
                    if ($newPaid < $schedPaid) {
                        $schedule->paid_amount = $newPaid;
                        $schedule->status = $newPaid >= (float) $schedule->amount ? 'paid' : ($newPaid > 0 ? 'partially_paid' : 'unpaid');
                        if ($newPaid <= 0) {
                            $schedule->confirmed_by = null;
                            $schedule->confirmed_at = null;
                        }
                        $schedule->save();
                    }
                }
            }

            // Revert payment from sale
            $sale->paid_amount -= $payment->amount;
            $sale->updateDebt();
            $sale->save();

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
    public function export(Request $request)
    {
        $this->authorize('export', CustomerDebt::class);

        [, $dateFrom, $dateTo] = $this->resolvePeriod($request);
        $receivableCond = 's.customer_id = customers.id AND ' . Sale::receivableSql('s') . $this->periodSql('s', $dateFrom, $dateTo);

        $customers = Customer::select([
            'customers.id',
            'customers.tax_code',
            'customers.name',
            'customers.phone',
            'customers.debt_limit',
            'customers.debt_days',
        ])
            ->selectRaw('COALESCE((SELECT SUM(s.total) FROM sales s WHERE ' . $receivableCond . '), 0) as receivable_sales')
            ->selectRaw('COALESCE((SELECT SUM(s.paid_amount) FROM sales s WHERE ' . $receivableCond . '), 0) as receivable_paid')
            ->selectRaw('COALESCE((SELECT SUM(s.debt_amount) FROM sales s WHERE ' . $receivableCond . '), 0) as receivable_debt')
            ->selectRaw('COALESCE((SELECT COUNT(*) FROM sales s WHERE ' . $receivableCond . ' AND s.debt_amount > 0), 0) as unpaid_orders')
            ->whereRaw('EXISTS (SELECT 1 FROM sales s WHERE ' . $receivableCond . ')')
            ->orderByRaw('receivable_debt DESC')
            ->get();

        return \Excel::download(
            new \App\Exports\CustomerDebtsExport($customers),
            'cong-no-khach-hang-' . date('Y-m-d') . '.xlsx'
        );
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
            ->whereNotNull('invoice_date')
            ->where('invoice_date', '<', $dateFrom)
            ->sum('debt_amount');

        $sales = Sale::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('invoice_date')
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->orderBy('invoice_date')
            ->get();

        $payments = PaymentHistory::where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date')
            ->get();

        $transactions = collect();

        foreach ($sales as $sale) {
            $transactions->push([
                'date' => \Carbon\Carbon::parse($sale->invoice_date)->format('Y-m-d'),
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
            ->whereNotNull('invoice_date')
            ->where('invoice_date', '<', $dateFrom)
            ->sum('debt_amount');

        $sales = Sale::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('invoice_date')
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->orderBy('invoice_date')
            ->get();

        $payments = PaymentHistory::where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date')
            ->get();

        $filename = "sao-ke-kh-{$customer->tax_code}-{$dateFrom}-{$dateTo}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($customer, $sales, $payments, $openingBalance, $dateFrom, $dateTo) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ["SAO KÊ CÔNG NỢ KHÁCH HÀNG"]);
            fputcsv($file, ["KH: {$customer->name} ({$customer->tax_code})"]);
            fputcsv($file, ["Kỳ: {$dateFrom} đến {$dateTo}"]);
            fputcsv($file, []);
            fputcsv($file, ['Ngày', 'Loại', 'Mã chứng từ', 'Nội dung', 'Phát sinh Nợ', 'Phát sinh Có', 'Lũy kế']);

            $balance = (float) $openingBalance;
            fputcsv($file, ['', '', '', 'Dư đầu kỳ', '', '', number_format($balance, 0, ',', '.')]);

            $transactions = collect();
            foreach ($sales as $sale) {
                $transactions->push(['date' => \Carbon\Carbon::parse($sale->invoice_date)->format('Y-m-d'), 'type' => 'Bán hàng', 'code' => $sale->code, 'desc' => 'Đơn hàng bán', 'debit' => (float)$sale->total, 'credit' => 0]);
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
