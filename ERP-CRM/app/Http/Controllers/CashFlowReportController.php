<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinancialTransaction;
use App\Models\Export;
use App\Models\Import;
use App\Models\Sale;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CashFlowReportExport;

class CashFlowReportController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', now()->year);
        
        $reportData = $this->prepareMonthlyReportData($year);

        return view('cash-flow-report.index', compact(
            'year',
            'reportData'
        ));
    }

    public function config()
    {
        $items = \App\Models\CashFlowConfigItem::orderBy('type')->orderBy('sort_order')->get();
        return view('cash-flow-report.config', compact('items'));
    }

    public function storeConfig(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::unique('cash_flow_config_items')->where(function ($query) use ($request) {
                    return $query->where('type', $request->type);
                })
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'sort_order' => 'required|integer',
        ], [
            'code.unique' => 'Mã lưu chuyển này đã tồn tại trong nhóm này.',
            'code.required' => 'Mã lưu chuyển không được để trống.',
            'name.required' => 'Tên hạng mục không được để trống.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('error', 'Có lỗi xảy ra, vui lòng kiểm tra lại dữ liệu.');
        }

        \App\Models\CashFlowConfigItem::create($request->all());

        return redirect()->back()->with('success', 'Đã thêm hạng mục mới thành công.');
    }

    public function destroyConfig(\App\Models\CashFlowConfigItem $configItem)
    {
        // Check if this item is in use by any TransactionCategory
        $inUse = \App\Models\TransactionCategory::where('cash_flow_code', $configItem->code)->exists();
        if ($inUse) {
            return redirect()->back()->with('error', 'Không thể xóa hạng mục này vì đang được sử dụng trong danh mục Thu Chi.');
        }

        $configItem->delete();
        return redirect()->back()->with('success', 'Đã xóa hạng mục báo cáo.');
    }

    public function prepareMonthlyReportData($year)
    {
        $months = range(1, 12);
        
        $templateItems = $this->getTemplateItems();
        
        $openingBalances = [];
        $incomeData = []; // [item_name][month]
        $expenseData = []; // [item_name][month]
        $totalIncome = array_fill(1, 12, 0);
        $totalExpense = array_fill(1, 12, 0);
        $closingBalances = [];

        $currentOpeningBalance = $this->calculateOpeningTotal($year . '-01-01');

        foreach ($months as $month) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

            $openingBalances[$month] = $currentOpeningBalance;

            // Income
            FinancialTransaction::where('type', 'income')
                ->whereBetween('date', [$startDate, $endDate])
                ->with('category')
                ->each(function($t) use (&$incomeData, &$totalIncome, $month, $templateItems) {
                    $code = $t->category->cash_flow_code ?? '4'; // Default to "Các khoản thu khác" (Row 4)
                    $itemName = $this->findItemNameByCode($code, $templateItems['income']);
                    $incomeData[$itemName][$month] = ($incomeData[$itemName][$month] ?? 0) + $t->amount;
                    $totalIncome[$month] += $t->amount;
                });

            // Expense
            FinancialTransaction::where('type', 'expense')
                ->whereBetween('date', [$startDate, $endDate])
                ->with('category')
                ->each(function($t) use (&$expenseData, &$totalExpense, $month, $templateItems) {
                    $code = $t->category->cash_flow_code ?? '8'; // Default to "Các khoản chi khác" (Row 8)
                    $itemName = $this->findItemNameByCode($code, $templateItems['expense']);
                    $expenseData[$itemName][$month] = ($expenseData[$itemName][$month] ?? 0) + $t->amount;
                    $totalExpense[$month] += $t->amount;
                });

            $closingBalances[$month] = $openingBalances[$month] + $totalIncome[$month] - $totalExpense[$month];
            $currentOpeningBalance = $closingBalances[$month];
        }

        return [
            'year' => $year,
            'months' => $months,
            'opening_balances' => $openingBalances,
            'income_items' => array_keys($templateItems['income']),
            'income_data' => $incomeData,
            'total_income' => $totalIncome,
            'expense_items' => array_keys($templateItems['expense']),
            'expense_data' => $expenseData,
            'total_expense' => $totalExpense,
            'closing_balances' => $closingBalances,
        ];
    }

    private function findItemNameByCode($code, $items)
    {
        foreach ($items as $name => $dummy) {
            if (str_starts_with($name, $code . '.')) {
                return $name;
            }
        }
        // Final fallback: use the last item (usually 'Other') if code doesn't match
        return array_key_last($items);
    }

    public static function getStandardItems()
    {
        $items = \App\Models\CashFlowConfigItem::orderBy('sort_order')->get();
        
        $income = [];
        $expense = [];
        
        foreach ($items as $item) {
            if ($item->type === 'income') {
                $income[$item->name] = $item->code;
            } else {
                $expense[$item->name] = $item->code;
            }
        }
        
        return [
            'income' => $income,
            'expense' => $expense,
        ];
    }

    private function getTemplateItems()
    {
        $items = self::getStandardItems();
        $formatted = [
            'income' => [],
            'expense' => []
        ];
        
        foreach ($items as $type => $list) {
            foreach ($list as $name => $code) {
                $formatted[$type][$name] = [];
            }
        }
        return $formatted;
    }

    private function mapToTemplateItem($transaction, $items)
    {
        $categoryName = strtolower($transaction->category->name ?? 'khác');
        
        foreach ($items as $itemName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($categoryName, $keyword)) {
                    return $itemName;
                }
            }
        }
        
        // Default fallbacks
        if ($transaction->type == 'income') {
            return '4. Tiền thu khác';
        }
        return '38. Các chi phí khác';
    }

    private function getMisaName($code) {
        $names = [
            '04' => '4. Tiền chi trả lãi vay',
            '05' => '5. Tiền chi nộp thuế thu nhập doanh nghiệp',
            '07' => '7. Tiền chi khác cho hoạt động kinh doanh',
            '21' => '1. Tiền chi để mua sắm, xây dựng TSCĐ và các tài sản dài hạn khác',
            '22' => '2. Tiền thu từ thanh lý, nhượng bán TSCĐ và các tài sản dài hạn khác',
            '23' => '3. Tiền chi cho vay, mua các công cụ nợ của đơn vị khác',
            '24' => '4. Tiền thu hồi cho vay, bán lại các công cụ nợ của đơn vị khác',
            '25' => '5. Tiền chi đầu tư góp vốn vào đơn vị khác',
            '26' => '6. Tiền thu hồi đầu tư góp vốn vào đơn vị khác',
            '27' => '7. Tiền thu lãi cho vay, cổ tức và lợi nhuận được chia',
            '31' => '1. Tiền thu từ phát hành cổ phiếu, nhận vốn góp của chủ sở hữu',
            '32' => '2. Tiền chi trả vốn góp cho các chủ sở hữu, mua lại cổ phiếu của đơn vị đã phát hành',
            '33' => '3. Tiền thu từ đi vay',
            '34' => '4. Tiền chi trả nợ gốc vay',
            '35' => '5. Tiền chi trả nợ gốc thuê tài chính',
            '36' => '6. Tiền chi trả cổ tức, lợi nhuận cho chủ sở hữu',
            '61' => 'Ảnh hưởng của thay đổi tỷ giá hối đoái quy đổi ngoại tệ',
        ];
        return $names[$code] ?? '';
    }

    private function calculateOpeningTotal($date)
    {
        $income = FinancialTransaction::where('type', 'income')
            ->where('date', '<', $date)
            ->sum('amount');

        $expense = FinancialTransaction::where('type', 'expense')
            ->where('date', '<', $date)
            ->sum('amount');

        return $income - $expense;
    }


    public function export(Request $request)
    {
        $year = $request->input('year', now()->year);

        return Excel::download(
            new CashFlowReportExport($year),
            'BaoCaoDongTien_' . $year . '.xlsx'
        );
    }
}
