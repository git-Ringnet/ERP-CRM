<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\SupplierPaymentHistory;
use App\Services\SupplierDebtAgingReportService;
use App\Services\CurrencyService;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierDebtController extends Controller
{
    protected SupplierDebtAgingReportService $agingReportService;
    protected CurrencyService $currencyService;

    public function __construct(SupplierDebtAgingReportService $agingReportService, CurrencyService $currencyService)
    {
        $this->agingReportService = $agingReportService;
        $this->currencyService = $currencyService;
    }

    /**
     * Display supplier debt list
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $debtStatus = $request->input('debt_status');
        $sortBy = $request->input('sort_by', 'debt_amount');
        $sortOrder = $request->input('sort_order', 'desc');

        $suppliers = Supplier::select([
            'suppliers.id',
            'suppliers.code',
            'suppliers.name',
            'suppliers.phone',
            'suppliers.payment_terms',
        ])
            ->selectRaw('COALESCE((SELECT SUM(po.total) FROM purchase_orders po WHERE po.supplier_id = suppliers.id AND po.status NOT IN ("cancelled", "draft")), 0) as total_purchases')
            ->selectRaw('COALESCE((SELECT SUM(po.paid_amount) FROM purchase_orders po WHERE po.supplier_id = suppliers.id AND po.status NOT IN ("cancelled", "draft")), 0) as total_paid')
            ->selectRaw('COALESCE((SELECT SUM(po.debt_amount) FROM purchase_orders po WHERE po.supplier_id = suppliers.id AND po.status NOT IN ("cancelled", "draft")), 0) as total_debt')
            ->selectRaw('COALESCE((SELECT COUNT(*) FROM purchase_orders po WHERE po.supplier_id = suppliers.id AND po.status NOT IN ("cancelled", "draft") AND po.debt_amount > 0), 0) as unpaid_orders')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('suppliers.name', 'like', "%{$search}%")
                        ->orWhere('suppliers.code', 'like', "%{$search}%")
                        ->orWhere('suppliers.phone', 'like', "%{$search}%");
                });
            })
            ->when($debtStatus === 'has_debt', function ($query) {
                $query->whereRaw('(SELECT SUM(po.debt_amount) FROM purchase_orders po WHERE po.supplier_id = suppliers.id AND po.status NOT IN ("cancelled", "draft")) > 0');
            })
            ->when($debtStatus === 'no_debt', function ($query) {
                $query->whereRaw('COALESCE((SELECT SUM(po.debt_amount) FROM purchase_orders po WHERE po.supplier_id = suppliers.id AND po.status NOT IN ("cancelled", "draft")), 0) = 0');
            })
            ->when($debtStatus === 'overdue', function ($query) {
                $query->whereExists(function ($subquery) {
                    $subquery->selectRaw('1')
                        ->from('purchase_orders')
                        ->whereColumn('purchase_orders.supplier_id', 'suppliers.id')
                        ->whereNotIn('purchase_orders.status', ['cancelled', 'draft'])
                        ->where('purchase_orders.debt_amount', '>', 0)
                        ->whereRaw('DATEDIFF(CURDATE(), purchase_orders.order_date) > COALESCE(suppliers.payment_terms, 30)');
                });
            })
            ->when($sortBy === 'debt_amount', function ($query) use ($sortOrder) {
                $query->orderByRaw('total_debt ' . $sortOrder);
            }, function ($query) use ($sortBy, $sortOrder) {
                $query->orderBy("suppliers.{$sortBy}", $sortOrder);
            })
            ->paginate(15)
            ->withQueryString();

        // Summary statistics
        $summary = [
            'total_suppliers_with_debt' => Supplier::whereHas('purchaseOrders', function ($q) {
                $q->where('debt_amount', '>', 0)
                    ->whereNotIn('status', ['cancelled', 'draft']);
            })->count(),
            'total_debt' => PurchaseOrder::whereNotIn('status', ['cancelled', 'draft'])->sum('debt_amount'),
            'total_overdue' => $this->getOverdueDebt(),
        ];

        return view('supplier-debts.index', compact('suppliers', 'summary', 'search', 'debtStatus', 'sortBy', 'sortOrder'));
    }

    /**
     * Show supplier debt detail
     */
    public function show(Supplier $supplier)
    {
        $purchaseOrders = PurchaseOrder::where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->orderBy('order_date', 'desc')
            ->get();

        $paymentHistories = SupplierPaymentHistory::where('supplier_id', $supplier->id)
            ->orderBy('payment_date', 'desc')
            ->get();

        $summary = [
            'total_purchases' => $purchaseOrders->sum('total'),
            'total_paid' => $purchaseOrders->sum('paid_amount'),
            'total_debt' => $purchaseOrders->sum('debt_amount'),
            'unpaid_orders' => $purchaseOrders->where('debt_amount', '>', 0)->count(),
        ];

        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();

        return view('supplier-debts.show', compact('supplier', 'purchaseOrders', 'paymentHistories', 'summary', 'currencies', 'baseCurrencyId'));
    }

    /**
     * Record payment for a purchase order
     */
    public function recordPayment(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'payment_method' => 'required|in:cash,bank_transfer,card,other',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
        ], [
            'amount.required' => 'Vui lòng nhập số tiền thanh toán',
            'amount.min' => 'Số tiền phải lớn hơn 0',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán',
            'payment_date.required' => 'Vui lòng chọn ngày thanh toán',
        ]);

        $currencyId = $request->input('currency_id') ?? $purchaseOrder->currency_id ?? \App\Models\Currency::getBaseCurrencyId();
        $exchangeRate = $request->input('exchange_rate') ?? $purchaseOrder->exchange_rate ?? 1;
        $inputAmount = $request->amount;

        $currencyService = app(\App\Services\CurrencyService::class);
        $isForeign = $currencyService->isForeign($currencyId);

        $amountVnd = $inputAmount;
        $amountForeign = null;

        if ($isForeign) {
            $amountForeign = $inputAmount;
            $amountVnd = $currencyService->convertToVnd($amountForeign, $exchangeRate);
        } else {
            $exchangeRate = 1;
        }

        // Validate amount doesn't exceed debt
        if ($amountVnd > (float)$purchaseOrder->debt_amount + 0.01) {
            return redirect()->back()->with('error', 'Số tiền thanh toán vượt quá công nợ còn lại!');
        }

        DB::beginTransaction();
        try {
            SupplierPaymentHistory::create([
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'amount' => $amountVnd,
                'amount_foreign' => $amountForeign,
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'payment_date' => $request->payment_date,
                'note' => $request->note,
                'created_by' => auth()->user()->name ?? 'Admin',
            ]);

            $purchaseOrder->paid_amount += $amountVnd;
            
            // Cập nhật paid_amount_foreign
            if ($purchaseOrder->currency_id && $purchaseOrder->currency_id != \App\Models\Currency::getBaseCurrencyId()) {
                if ($currencyId == $purchaseOrder->currency_id) {
                    $purchaseOrder->paid_amount_foreign += $amountForeign;
                } else {
                    // Nếu trả bằng tiền khác, quy đổi về tiền của PO
                    $purchaseOrder->paid_amount_foreign += ($amountVnd / ($purchaseOrder->exchange_rate ?: 1));
                }
            }

            $purchaseOrder->updateDebt();
            $purchaseOrder->save();

            // Tạo giao dịch chi vào financial_transactions và ghi nhận chênh lệch tỷ giá
            $financialService = app(\App\Services\FinancialTransactionService::class);
            $financialService->createFromPurchaseOrder(
                $purchaseOrder,
                $inputAmount,
                $request->payment_method,
                $request->note,
                $currencyId,
                $exchangeRate
            );

            DB::commit();

            return redirect()->back()->with('success', 'Ghi nhận thanh toán NCC thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Delete payment record
     */
    public function deletePayment(SupplierPaymentHistory $payment)
    {
        DB::beginTransaction();
        try {
            $purchaseOrder = $payment->purchaseOrder;

            $purchaseOrder->paid_amount -= $payment->amount;
            $purchaseOrder->updateDebt();
            $purchaseOrder->save();

            $payment->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Đã xóa bản ghi thanh toán!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Statement of Account for a supplier
     */
    public function statement(Request $request, Supplier $supplier)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Get opening balance (debt before date_from)
        $openingBalance = PurchaseOrder::where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->where('order_date', '<', $dateFrom)
            ->sum('debt_amount');

        // Get POs in period
        $purchaseOrders = PurchaseOrder::where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->orderBy('order_date')
            ->get();

        // Get payments in period
        $payments = SupplierPaymentHistory::where('supplier_id', $supplier->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date')
            ->get();

        // Build transaction list
        $transactions = collect();

        foreach ($purchaseOrders as $po) {
            $transactions->push([
                'date' => $po->order_date->format('Y-m-d'),
                'type' => 'debit',
                'code' => $po->code,
                'description' => 'Đơn mua hàng',
                'debit' => (float) $po->total,
                'credit' => 0,
            ]);
        }

        foreach ($payments as $payment) {
            $description = 'Thanh toán - ' . $payment->payment_method_label;
            if ($payment->currency !== 'VND' && $payment->amount_foreign) {
                $description .= " ({$payment->amount_foreign} {$payment->currency})";
            }
            $transactions->push([
                'date' => $payment->payment_date->format('Y-m-d'),
                'type' => 'credit',
                'code' => $payment->reference_number ?: $payment->purchaseOrder->code,
                'description' => $description,
                'debit' => 0,
                'credit' => (float) $payment->amount,
            ]);
        }

        $transactions = $transactions->sortBy('date')->values();

        // Calculate running balance
        $runningBalance = (float) $openingBalance;
        $transactions = $transactions->map(function ($item) use (&$runningBalance) {
            $runningBalance += $item['debit'] - $item['credit'];
            $item['balance'] = $runningBalance;
            return $item;
        });

        $closingBalance = $runningBalance;

        return view('supplier-debts.statement', compact(
            'supplier', 'transactions', 'openingBalance', 'closingBalance',
            'dateFrom', 'dateTo'
        ));
    }

    /**
     * Export statement to CSV
     */
    public function exportStatement(Request $request, Supplier $supplier)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Reuse the logic from statement method
        $openingBalance = PurchaseOrder::where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->where('order_date', '<', $dateFrom)
            ->sum('debt_amount');

        $purchaseOrders = PurchaseOrder::where('supplier_id', $supplier->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->orderBy('order_date')
            ->get();

        $payments = SupplierPaymentHistory::where('supplier_id', $supplier->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date')
            ->get();

        $filename = "sao-ke-ncc-{$supplier->code}-{$dateFrom}-{$dateTo}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($supplier, $purchaseOrders, $payments, $openingBalance, $dateFrom, $dateTo) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ["SAO KÊ CÔNG NỢ NHÀ CUNG CẤP"]);
            fputcsv($file, ["NCC: {$supplier->name} ({$supplier->code})"]);
            fputcsv($file, ["Kỳ: {$dateFrom} đến {$dateTo}"]);
            fputcsv($file, []);

            fputcsv($file, ['Ngày', 'Loại', 'Mã chứng từ', 'Nội dung', 'Phát sinh Nợ', 'Phát sinh Có', 'Lũy kế']);

            $balance = (float) $openingBalance;
            fputcsv($file, ['', '', '', 'Dư đầu kỳ', '', '', number_format($balance, 0, ',', '.')]);

            $transactions = collect();
            foreach ($purchaseOrders as $po) {
                $transactions->push(['date' => $po->order_date->format('Y-m-d'), 'type' => 'Mua hàng', 'code' => $po->code, 'desc' => 'Đơn mua hàng', 'debit' => (float)$po->total, 'credit' => 0]);
            }
            foreach ($payments as $payment) {
                $transactions->push(['date' => $payment->payment_date->format('Y-m-d'), 'type' => 'Thanh toán', 'code' => $payment->reference_number ?: 'TT', 'desc' => $payment->payment_method_label, 'debit' => 0, 'credit' => (float)$payment->amount]);
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

    /**
     * Export supplier debts list
     */
    public function export()
    {
        $suppliers = Supplier::select(['suppliers.id', 'suppliers.code', 'suppliers.name', 'suppliers.phone'])
            ->selectRaw('COALESCE((SELECT SUM(po.total) FROM purchase_orders po WHERE po.supplier_id = suppliers.id AND po.status NOT IN ("cancelled", "draft")), 0) as total_purchases')
            ->selectRaw('COALESCE((SELECT SUM(po.paid_amount) FROM purchase_orders po WHERE po.supplier_id = suppliers.id AND po.status NOT IN ("cancelled", "draft")), 0) as total_paid')
            ->selectRaw('COALESCE((SELECT SUM(po.debt_amount) FROM purchase_orders po WHERE po.supplier_id = suppliers.id AND po.status NOT IN ("cancelled", "draft")), 0) as total_debt')
            ->orderByRaw('total_debt DESC')
            ->get();

        $filename = 'cong-no-ncc-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($suppliers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['Mã NCC', 'Tên NCC', 'Điện thoại', 'Tổng mua', 'Đã trả', 'Còn nợ']);

            foreach ($suppliers as $supplier) {
                fputcsv($file, [
                    $supplier->code,
                    $supplier->name,
                    $supplier->phone,
                    number_format($supplier->total_purchases, 0, ',', '.'),
                    number_format($supplier->total_paid, 0, ',', '.'),
                    number_format($supplier->total_debt, 0, ',', '.'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display aging report
     */
    public function agingReport(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'supplier_id' => $request->input('supplier_id'),
        ];

        $report = $this->agingReportService->getAgingReport($filters);
        $stats = $this->agingReportService->getSummaryStats();

        return view('supplier-debts.aging-report', compact('report', 'stats', 'filters'));
    }

    /**
     * Get overdue debt amount
     */
    private function getOverdueDebt(): float
    {
        return PurchaseOrder::whereNotIn('status', ['cancelled', 'draft'])
            ->where('debt_amount', '>', 0)
            ->with('supplier')
            ->get()
            ->filter(function ($po) {
                $paymentDays = is_numeric($po->payment_terms) ? (int) $po->payment_terms : 30;
                $paymentDays = match($po->payment_terms) {
                    'immediate', 'cod' => 0,
                    'net15' => 15,
                    'net30' => 30,
                    'net45' => 45,
                    'net60' => 60,
                    default => $paymentDays,
                };
                $dueDate = $po->order_date->addDays($paymentDays);
                return now()->gt($dueDate);
            })
            ->sum('debt_amount');
    }
}
