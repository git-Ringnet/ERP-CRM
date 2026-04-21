@php
    $standardTypes = [
        'Chi phí Tài chính',
        'Lãi vay phát sinh do nợ quá hạn',
        'Chi phí Quản lí, Back Office & kỹ thuật',
        '24x7 Support cost',
        'Other Support',
        'Technical support/POC',
        'Technical support/POC 30%',
        'Chi phí triển khai hợp đồng',
        'Thuế nhà thầu'
    ];
    
    $allExpenses = $sale->expenses;
    $normalizeExpenseType = function ($type) {
        $normalized = \Illuminate\Support\Str::ascii((string) $type);
        $normalized = \Illuminate\Support\Str::lower($normalized);
        return preg_replace('/[^a-z0-9]+/u', '', $normalized) ?? '';
    };
    $findExpenseByTypes = function ($types) use ($allExpenses, $normalizeExpenseType) {
        foreach ($types as $type) {
            $exact = $allExpenses->firstWhere('type', $type);
            if ($exact) {
                return $exact;
            }
        }
        $needles = array_map($normalizeExpenseType, $types);
        return $allExpenses->first(function ($expense) use ($needles, $normalizeExpenseType) {
            return in_array($normalizeExpenseType($expense->type), $needles, true);
        });
    };
    $standardExpenses = $allExpenses->whereIn('type', $standardTypes);
    $extraExpenses = $allExpenses->whereNotIn('type', $standardTypes);
    $financeExpense = $findExpenseByTypes(['Chi phí Tài chính']);
    $financeInputMode = $financeExpense->input_mode ?? 'percent';
    $overdueExpense = $findExpenseByTypes(['Lãi vay phát sinh do nợ quá hạn']);
    $overdueInputMode = $overdueExpense->input_mode ?? 'percent';
    $managementExpense = $findExpenseByTypes(['Chi phí Quản lí, Back Office & kỹ thuật']);
    $managementInputMode = $managementExpense->input_mode ?? 'percent';
    $supportExpense = $findExpenseByTypes(['24x7 Support cost']);
    $supportInputMode = $supportExpense->input_mode ?? 'percent';
    $otherSupportExpense = $findExpenseByTypes(['Other Support']);
    $otherSupportInputMode = $otherSupportExpense->input_mode ?? 'percent';
    
    $totalCostBase = $sale->items->sum('cost_total') ?: 1;

    // Visibility flags
    $expenseTypes = $sale->expenses->pluck('type')->toArray();
    
    $hasFinanceCost = in_array('Chi phí Tài chính', $expenseTypes) || $sale->items->filter(function($item) {
        return !is_null($item->finance_cost_percent) && $item->finance_cost_percent !== '' && $item->finance_cost_percent > 0;
    })->count() > 0;
    
    $hasOverdueInterest = in_array('Lãi vay phát sinh do nợ quá hạn', $expenseTypes) || $sale->items->filter(function($item) {
        return (!is_null($item->overdue_interest_percent) && $item->overdue_interest_percent > 0)
            || (!is_null($item->overdue_interest_cost) && $item->overdue_interest_cost > 0);
    })->count() > 0;
    
    $hasManagementCost = in_array('Chi phí Quản lí, Back Office & kỹ thuật', $expenseTypes) || $sale->items->filter(function($item) {
        return !is_null($item->management_cost_percent) && $item->management_cost_percent !== '' && $item->management_cost_percent > 0;
    })->count() > 0;
    
    $hasSupport247 = in_array('24x7 Support cost', $expenseTypes) || $sale->items->filter(function($item) {
        return !is_null($item->support_247_cost_percent) && $item->support_247_cost_percent !== '' && $item->support_247_cost_percent > 0;
    })->count() > 0;
    
    $hasOtherSupport = in_array('Other Support', $expenseTypes) || $sale->items->filter(function($item) {
        return !is_null($item->other_support_cost) && $item->other_support_cost > 0;
    })->count() > 0;
    
    $hasTechnicalPoc = in_array('Technical support/POC', $expenseTypes)
        || in_array('Technical support/POC 30%', $expenseTypes)
        || $sale->items->filter(function ($item) {
            return ! is_null($item->technical_poc_percent)
                || (! is_null($item->technical_poc_cost) && (float) $item->technical_poc_cost > 0);
        })->count() > 0;
    
    $hasImplementation = in_array('Chi phí triển khai hợp đồng', $expenseTypes) || $sale->items->filter(function ($item) {
        return ! is_null($item->implementation_cost_percent)
            || (! is_null($item->implementation_cost) && (float) $item->implementation_cost > 0);
    })->count() > 0;
    
    $hasContractorTax = in_array('Thuế nhà thầu', $expenseTypes) || $sale->items->filter(function ($item) {
        return ! is_null($item->contractor_tax_percent)
            || (! is_null($item->contractor_tax) && (float) $item->contractor_tax > 0);
    })->count() > 0;
    
    $visibleStandardCols = 0;
    if ($hasFinanceCost) $visibleStandardCols++;
    if ($hasOverdueInterest) $visibleStandardCols++;
    if ($hasManagementCost) $visibleStandardCols++;
    if ($hasSupport247) $visibleStandardCols++;
    if ($hasOtherSupport) $visibleStandardCols++;
    if ($hasTechnicalPoc) $visibleStandardCols++;
    if ($hasImplementation) $visibleStandardCols++;
    if ($hasContractorTax) $visibleStandardCols++;
    
    $totalColspan = $visibleStandardCols + $extraExpenses->count();
@endphp

<script>
    function pnlEditor() {
        return {
            finance_p: {{ ($financeExpense && $financeExpense->input_mode === 'percent') ? (float) ($financeExpense->percent_value ?? 0) : 0 }},
            overdue_p: {{ ($overdueExpense && $overdueExpense->input_mode === 'percent') ? (float) ($overdueExpense->percent_value ?? 0) : 0 }},
            mgmt_p: {{ ($managementExpense && $managementExpense->input_mode === 'percent') ? (float) ($managementExpense->percent_value ?? 0) : 0 }},
            support_p: {{ ($supportExpense && $supportExpense->input_mode === 'percent') ? (float) ($supportExpense->percent_value ?? 0) : 0 }},
            other_p: {{ ($otherSupportExpense && $otherSupportExpense->input_mode === 'percent') ? (float) ($otherSupportExpense->percent_value ?? 0) : 0 }},
            
            finance_amt: {{ (float) ($financeExpense->amount ?? 0) }},
            mgmt_amt: {{ (float) ($managementExpense->amount ?? 0) }},
            support_amt: {{ (float) ($supportExpense->amount ?? 0) }},
            other_amt: {{ (float) ($otherSupportExpense->amount ?? 0) }},

            extra_modes: {
                @foreach($extraExpenses as $extra)
                    '{{ $extra->id }}': '{{ $extra->input_mode }}',
                @endforeach
            },

            // Fixed amounts for Extra Expenses (Order Level) — used as fallback
            extra_amts: {
                @foreach($extraExpenses as $extra)
                    '{{ $extra->id }}': {{ (float) ($extra->amount ?? 0) }},
                @endforeach
            },

            updateExtraMode(id, mode) {
                this.extra_modes[id] = mode;
                this.$dispatch('pnl-recalc');
            },

            getExtraMode(expenseId) {
                return this.extra_modes[expenseId] || 'fixed';
            },

            // Compute auto-sum of per-item fixed values for a given extra expense
            getExtraFixedTotal(expenseId) {
                return this.extra_amts[expenseId] || 0;
            },

            // Recalculate extra_amts from all row DOM inputs
            syncExtraAmtsFromRows() {
                const sums = {};
                document.querySelectorAll('.extra-item-input').forEach(el => {
                    const eid = el.dataset.expenseId;
                    if (!eid) return;
                    if (!sums[eid]) sums[eid] = 0;
                    sums[eid] += parseFloat(el.value.toString().replace(/,/g, '')) || 0;
                });
                for (const eid of Object.keys(sums)) {
                    this.extra_amts[eid] = Math.round(sums[eid]);
                }
            },
            
            unformat(val) {
                if (typeof val === 'number') return val;
                if (!val) return 0;
                return parseFloat(val.toString().replace(/,/g, '')) || 0;
            },

            formatNumber(n) {
                return new Intl.NumberFormat('en-US').format(Math.round(n));
            },

            getGlobalBases() {
                let totalCost = 0;
                let totalRevenue = 0;
                let itemCount = 0;

                // Scan all row inputs in the DOM
                document.querySelectorAll('.row-cost-total-input').forEach(el => {
                    totalCost += parseFloat(el.value) || 0;
                    itemCount++;
                });
                document.querySelectorAll('.row-revenue-total-input').forEach(el => {
                    totalRevenue += parseFloat(el.value) || 0;
                });

                return { totalCost, totalRevenue, itemCount };
            },

            init() {
                // Listen for row extra expense changes and sync totals
                this.$watch('extra_amts', () => {}, { deep: true });
                // Sync extra_amts on initial load after rows render
                setTimeout(() => this.syncExtraAmtsFromRows(), 300);
            }
        }
    }

    function pnlRow(data) {
        return {
            id: data.id,
            row_index: data.row_index || 0,
            qty: data.qty,
            usd_p: data.usd_price,
            disc: data.discount_rate,
            imp_r: data.import_cost_rate,
            rate: data.exchange_rate,
            cost_price: data.cost_price,
            cost_total: data.cost_total,
            revenue_total: data.revenue_total,
            
            finance_na: data.finance_na,
            finance_mode: data.finance_mode || 'percent',
            finance_allocated: data.finance_allocated || 0,
            overdue_na: data.overdue_na,
            overdue_mode: data.overdue_mode || 'percent',
            mgmt_na: data.mgmt_na,
            mgmt_mode: data.mgmt_mode || 'percent',
            mgmt_allocated: data.mgmt_allocated || 0,
            support_na: data.support_na,
            support_mode: data.support_mode || 'percent',
            support_allocated: data.support_allocated || 0,
            other_na: data.other_na,
            other_mode: data.other_mode || 'percent',
            other_allocated: data.other_allocated || 0,
            
            oic: data.oic,
            poc: data.poc,
            poc_mode: data.poc_mode || 'fixed',
            poc_p: data.poc_p || 0,
            imp: data.imp,
            imp_mode: data.imp_mode || 'fixed',
            imp_p: data.imp_p || 0,
            tax: data.tax,
            tax_mode: data.tax_mode || 'fixed',
            tax_p: data.tax_p || 0,
            tax_allocated: data.tax_allocated || 0,
            
            extra_costs: data.extra_costs || [],
            extra_vals: [],
            extra_fixed_vals: data.extra_fixed_vals || {},
            
            $sale_editable: {{ $sale->isPlEditable() ? 'true' : 'false' }},
            
            est_usd_total: 0,
            gross_profit: 0,
            gross_p: 0,
            finance_v: 0,
            overdue_v: 0,
            mgmt_v: 0,
            support_v: 0,
            other_v: 0,
            total_costs: 0,
            poc_v: 0,
            imp_v: 0,
            tax_v: 0,
            net_profit: 0,
            margin_p: 0,

            init() {
                // Defer first calculation to ensure all rows are in DOM
                setTimeout(() => {
                    this.calculate();
                }, 200);
            },

            calculate() {
                // Determine dynamic totals for distribution from parent component
                const bases = this.getGlobalBases();
                
                // Prioritize Dynamic Totals, then Dynamic Revenue, finally initial totals if > 1000
                const totalBase = bases.totalCost > 1000 ? bases.totalCost : (bases.totalRevenue > 1000 ? bases.totalRevenue : ({{ (float) $totalCostBase }} > 1000 ? {{ (float) $totalCostBase }} : 0));
                const revenueBase = bases.totalRevenue > 1000 ? bases.totalRevenue : ({{ (float) ($sale->items->sum('total') ?: 0) }} > 1000 ? {{ (float) ($sale->items->sum('total') ?: 0) }} : 0);
                const itemCount = bases.itemCount || {{ $sale->items->count() }};

                const unformat = (val) => {
                    if (typeof val === 'number') return val;
                    if (!val) return 0;
                    return parseFloat(val.toString().replace(/,/g, '')) || 0;
                };

                function calcShareFromTotal(totalAmtRaw, rowCost, rowRev, idx) {
                    const totalAmt = unformat(totalAmtRaw);
                    if (totalAmt <= 0) return 0;
                    
                    if (itemCount <= 1) return totalAmt;
                    
                    // Distribution logic: Cost share > Revenue share > Equal share
                    if (totalBase > 1000 && rowCost > 0) {
                        return totalAmt * (rowCost / totalBase);
                    }
                    if (revenueBase > 1000 && rowRev > 0) {
                        return totalAmt * (rowRev / revenueBase);
                    }
                    return totalAmt / itemCount;
                }

                // Core Calculations
                this.est_usd_total = Math.round(this.usd_p * (1 - this.disc / 100) * (1 + this.imp_r / 100) * 100) / 100;
                this.cost_price = Math.round(this.est_usd_total * this.rate);
                this.cost_total = Math.round(this.cost_price * this.qty);
                this.gross_profit = this.revenue_total - this.cost_total;
                this.gross_p = this.revenue_total > 0 ? ((this.gross_profit / this.revenue_total) * 100).toFixed(1) : 0;

                this.finance_v = this.finance_na ? 0 : (this.finance_mode === 'fixed' ? Math.round(calcShareFromTotal(this.finance_amt, this.cost_total, this.revenue_total, this.row_index)) : Math.round(this.cost_total * (this.finance_p / 100)));
                this.overdue_v = this.overdue_na ? 0 : (this.overdue_mode === 'fixed' ? (unformat(this.oic) || 0) : Math.round(this.cost_total * (this.overdue_p / 100)));
                this.mgmt_v = this.mgmt_na ? 0 : (this.mgmt_mode === 'fixed' ? Math.round(calcShareFromTotal(this.mgmt_amt, this.cost_total, this.revenue_total, this.row_index)) : Math.round(this.cost_total * (this.mgmt_p / 100)));
                this.support_v = this.support_na ? 0 : (this.support_mode === 'fixed' ? Math.round(calcShareFromTotal(this.support_amt, this.cost_total, this.revenue_total, this.row_index)) : Math.round(this.cost_total * (this.support_p / 100)));
                this.other_v = this.other_na ? 0 : (this.other_mode === 'fixed' ? Math.round(calcShareFromTotal(this.other_amt, this.cost_total, this.revenue_total, this.row_index)) : Math.round(this.cost_total * (this.other_p / 100)));

                if (this.poc_mode === 'percent') {
                   this.poc_v = Math.round(this.cost_total * ((parseFloat(this.poc_p) || 0) / 100));
                } else {
                    this.poc_v = Math.round(unformat(this.poc));
                }

                if (this.imp_mode === 'percent') {
                   this.imp_v = Math.round(this.cost_total * ((parseFloat(this.imp_p) || 0) / 100));
                } else {
                    this.imp_v = Math.round(unformat(this.imp));
                }

                if (this.tax_mode === 'percent') {
                   this.tax_v = Math.round(this.cost_total * ((parseFloat(this.tax_p) || 0) / 100));
                } else {
                    this.tax_v = Math.round(unformat(this.tax));
                }
                
                let extraSum = 0;
                this.extra_vals = [];
                
                this.extra_costs.forEach((ec) => {
                    let v = 0;
                    const currentMode = this.getExtraMode(ec.id);
                    
                    if (currentMode === 'percent') {
                        // For percent, find the header input or use ec.val
                        const expenseInput = document.querySelector(`.extra-expense-input[data-expense-id="${ec.id}"][data-mode="percent"]`);
                        const ecVal = expenseInput ? unformat(expenseInput.value) : (parseFloat(ec.val) || 0);
                        v = Math.round(this.cost_total * (ecVal / 100));
                    } else {
                        // For fixed, use per-item value directly (no distribution)
                        v = Math.round(unformat(this.extra_fixed_vals[ec.id] || 0));
                    }
                    this.extra_vals.push(v);
                    extraSum += v;
                });
                
                this.total_costs = Math.round((!this.finance_na ? this.finance_v : 0) + (!this.overdue_na ? this.overdue_v : 0) + (!this.mgmt_na ? this.mgmt_v : 0) + (!this.support_na ? this.support_v : 0) + (!this.other_na ? this.other_v : 0) + this.tax_v + this.poc_v + this.imp_v + extraSum);
                this.net_profit = this.gross_profit - this.total_costs;
                this.margin_p = this.revenue_total > 0 ? ((this.net_profit / this.revenue_total) * 100).toFixed(1) : 0;
            },

            formatNumber(n, decimals = 0) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                }).format(n);
            }
        }
    }

    function formatExpenseMoneyValue(rawValue) {
        if (!rawValue && rawValue !== 0) return '';
        // Remove only commas (thousands separator)
        const clean = rawValue.toString().replace(/,/g, '');
        const numeric = parseFloat(clean);
        if (isNaN(numeric)) return '';
        return new Intl.NumberFormat('en-US', { 
            maximumFractionDigits: 2 
        }).format(numeric);
    }

    function initExtraExpenseMoneyInputs() {
        const inputs = document.querySelectorAll('.extra-expense-money');
        inputs.forEach((input) => {
            if (input.dataset.moneyBound === '1') return;
            input.value = formatExpenseMoneyValue(input.value);
            input.addEventListener('focus', function() { 
                // When focusing, show raw number with dot decimal
                this.value = (this.value || '').toString().replace(/,/g, ''); 
            });
            input.addEventListener('blur', function() { this.value = formatExpenseMoneyValue(this.value); });
            input.dataset.moneyBound = '1';
        });
    }

    function confirmAction(url, message) {
        if (confirm(message)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            const csrf = document.createElement('input');
            csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', () => { 
        initExtraExpenseMoneyInputs(); 
        const pnlForm = document.getElementById('pnlForm');
        if (pnlForm) {
            pnlForm.addEventListener('submit', function() {
                document.querySelectorAll('.extra-expense-money').forEach((input) => {
                    input.value = (input.value || '').toString().replace(/,/g, '');
                });
            });
        }
    });
</script>

<div x-data="pnlEditor()" class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
    <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-bold text-gray-800">
            <i class="fas fa-chart-line mr-2 text-cyan-600"></i>
            BẢNG PHÂN TÍCH HIỆU QUẢ KINH DOANH (P&L - Profit and Loss Statement)
        </h3>
        <div class="flex items-center space-x-2">
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $sale->pl_status_color }}">
                {{ $sale->pl_status_label }}
            </span>
            
            @if($sale->pl_status === 'approved')
                <div class="text-xs text-gray-500 italic">
                    Được duyệt bởi {{ $sale->plApprover->name ?? 'Hệ thống' }} lúc {{ $sale->pl_approved_at->format('d/m/Y H:i') }}
                </div>
            @endif
        </div>
    </div>


    <form id="pnlForm" action="{{ route('sales.updatePnL', $sale) }}" method="POST">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse table-pnl-editor min-w-[4200px]">
                <thead>
                    <!-- Header Row 1: Main sections -->
                    <tr class="bg-yellow-400 text-xs font-bold text-center border border-gray-800">
                        <th class="px-2 py-1 border border-gray-800" style="min-width: 180px; width: 180px;" rowspan="2">P/N<br/>Supplier</th>
                        <th class="px-2 py-1 border border-gray-800" style="min-width: 600px; width: 600px;" rowspan="2">Hàng hóa/Dịch vụ</th>
                        <th class="px-2 py-1 border border-gray-800" style="min-width: 80px; width: 80px;" rowspan="2">SL</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[130px]" rowspan="2">PriceList<br/>USD</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[100px]" rowspan="2">Tỷ lệ<br/>discount</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[100px]" rowspan="2">Tỷ lệ chi phí<br/>nhập hàng</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[150px]" rowspan="2">Giá kho tạm tính<br/>(USD)</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[100px]" rowspan="2">Tỷ giá</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[280px]" colspan="2">Giá đầu vào (chưa VAT)</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[280px]" colspan="2">Giá bán (chưa VAT)</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[200px]" colspan="2">Lợi nhuận gộp</th>
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[600px]" colspan="{{ $totalColspan ?? 1 }}">Chi phí</th>
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[180px]" rowspan="2">Tổng chi phí<br/><span class="font-normal text-[10px]">VND</span></th>
                        <th class="px-2 py-1 border border-gray-800 bg-gray-200 min-w-[300px]" colspan="2">Lợi nhuận sau chi phí</th>
                    </tr>
                    <!-- Header Row 2: Column details -->
                    <tr class="bg-yellow-400 text-[10px] font-bold text-center border border-gray-800">
                        <th class="px-2 py-1 border border-gray-800 min-w-[140px]">Giá VND</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[140px]">Thành tiền</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[140px]">Đơn Giá bán</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[140px]">Thành tiền</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[120px]">VND</th>
                        <th class="px-2 py-1 border border-gray-800 min-w-[80px]">%</th>
                        
                        {{-- Standard columns - chỉ hiển thị cột có dữ liệu --}}
                        @if($hasFinanceCost)
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[120px]">
                            Chi phí Tài chính
                            @if($financeInputMode === 'percent')
                                <div class="text-red-600 font-normal flex items-center justify-center gap-0.5">
                                    <input type="number" step="0.1" x-model="finance_p" @input="$dispatch('pnl-recalc')" 
                                        class="w-10 text-center text-red-600 text-[10px] p-0 border-0 border-b border-red-400 bg-transparent focus:ring-0"
                                        {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                                </div>
                            @else
                                <div class="text-red-600 font-normal text-[10px] text-center">VND</div>
                            @endif
                        </th>
                        @endif
                        @if($hasOverdueInterest)
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[120px]">
                            Lãi vay phát sinh do nợ quá hạn
                            @if($overdueInputMode === 'percent')
                                <div class="text-red-600 font-normal flex items-center justify-center gap-0.5">
                                    <input type="number" step="0.1" x-model="overdue_p" @input="$dispatch('pnl-recalc')"
                                        class="w-10 text-center text-red-600 text-[10px] p-0 border-0 border-b border-red-400 bg-transparent focus:ring-0"
                                        {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                                </div>
                            @else
                                <div class="text-red-600 font-normal text-[10px] text-center">VND</div>
                            @endif
                        </th>
                        @endif
                        @if($hasManagementCost)
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[120px]">
                            Chi phí Quản lí, Back Office & kỹ thuật
                            @if($managementInputMode === 'percent')
                                <div class="text-red-600 font-normal flex items-center justify-center gap-0.5">
                                    <input type="number" step="0.1" x-model="mgmt_p" @input="$dispatch('pnl-recalc')" 
                                        class="w-10 text-center text-red-600 text-[10px] p-0 border-0 border-b border-red-400 bg-transparent focus:ring-0"
                                        {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                                </div>
                            @else
                                <div class="text-red-600 font-normal text-[10px] text-center">VND</div>
                            @endif
                        </th>
                        @endif
                        @if($hasSupport247)
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[120px]">
                            24x7 Support cost
                            @if($supportInputMode === 'percent')
                                <div class="text-red-600 font-normal flex items-center justify-center gap-0.5">
                                    <input type="number" step="0.1" x-model="support_p" @input="$dispatch('pnl-recalc')" 
                                        class="w-10 text-center text-red-600 text-[10px] p-0 border-0 border-b border-red-400 bg-transparent focus:ring-0"
                                        {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                                </div>
                            @else
                                <div class="text-red-600 font-normal text-[10px] text-center">VND</div>
                            @endif
                        </th>
                        @endif
                        @if($hasOtherSupport)
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[120px]">
                            Other Support
                            @if($otherSupportInputMode === 'percent')
                                <div class="text-red-600 font-normal flex items-center justify-center gap-0.5">
                                    <input type="number" step="0.1" x-model="other_p" @input="$dispatch('pnl-recalc')" 
                                        class="w-10 text-center text-red-600 text-[10px] p-0 border-0 border-b border-red-400 bg-transparent focus:ring-0"
                                        {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                                </div>
                            @else
                                <div class="text-red-600 font-normal text-[10px] text-center">VND</div>
                            @endif
                        </th>
                        @endif
                        @if($hasTechnicalPoc)
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[120px]">Technical support/POC 30%</th>
                        @endif
                        @if($hasImplementation)
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[150px]">Chi phí triển khai hợp đồng (Tiếp khách, cấu hình)</th>
                        @endif
                        @if($hasContractorTax)
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400 min-w-[120px]">Thuế nhà thầu</th>
                        @endif
                        
                        {{-- Extra columns --}}
                        @foreach($extraExpenses->values() as $extraIndex => $extra)
                            <th class="px-2 py-1 border border-gray-800 bg-orange-100 min-w-[200px]">
                                <div class="mb-0.5">{{ $extra->type }}</div>
                                <input type="hidden" name="expenses[{{ $extraIndex }}][id]" value="{{ $extra->id }}">
                                <input type="hidden" name="expenses[{{ $extraIndex }}][type]" value="{{ $extra->type }}">
                                
                                <div class="flex flex-col items-center justify-center min-h-[32px] mt-1 space-y-1">
                                    {{-- Mode Selector --}}
                                    <select name="expenses[{{ $extraIndex }}][input_mode]"
                                            data-expense-id="{{ $extra->id }}"
                                            class="extra-expense-mode text-[10px] text-gray-600 bg-white border border-orange-300 rounded px-1 py-0.5 focus:ring-1 focus:ring-orange-400"
                                            @change="updateExtraMode('{{ $extra->id }}', $event.target.value)"
                                            {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                        <option value="percent" {{ $extra->input_mode === 'percent' ? 'selected' : '' }}>Chế độ: %</option>
                                        <option value="fixed" {{ $extra->input_mode === 'fixed' ? 'selected' : '' }}>Chế độ: VND</option>
                                    </select>

                                    {{-- Percent Input (Header) --}}
                                    <div x-show="getExtraMode('{{ $extra->id }}') === 'percent'" 
                                         class="text-red-600 font-bold flex items-center justify-center gap-1">
                                        <input type="number" step="0.1"
                                            data-expense-id="{{ $extra->id }}"
                                            data-mode="percent"
                                            name="expenses[{{ $extraIndex }}][percent_value]"
                                            class="extra-expense-input w-16 text-center text-red-600 font-bold text-[11px] p-0.5 border-0 border-b-2 border-red-400 bg-transparent focus:ring-0"
                                            value="{{ (floatval($extra->percent_value) ?: 0) }}"
                                            @input="$dispatch('pnl-recalc')"
                                            {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                                    </div>
                                    
                                    {{-- Fixed Mode: Hidden sum total (used for persistence) --}}
                                    <div x-show="getExtraMode('{{ $extra->id }}') === 'fixed'" 
                                         class="w-full text-center">
                                        <input type="hidden" name="expenses[{{ $extraIndex }}][amount]" 
                                               :value="getExtraFixedTotal('{{ $extra->id }}')">
                                    </div>
                                </div>
                            </th>
                        @endforeach
                        
                        <th class="px-2 py-1 border border-gray-800 bg-gray-200 text-xs">VND</th>
                        <th class="px-2 py-1 border border-gray-800 bg-gray-200 text-xs">%</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    @foreach($sale->items as $index => $item)
                        @php
                            $grossProfit = $item->total - $item->cost_total;
                            $grossProfitPercent = $item->total > 0 ? ($grossProfit / $item->total * 100) : 0;
                        @endphp
                        @php
                            $plPrice = 0;
                            if (isset($item->product)) {
                                $skuCode = trim($item->product->code ?? '');
                                $pName = trim($item->product->name ?? '');
                                
                                // 1. Try SKU exact or with wildcards
                                if ($skuCode) {
                                    $priceItem = \App\Models\SupplierPriceListItem::where(function($q) use ($skuCode) {
                                            $q->where('sku', 'like', $skuCode)
                                              ->orWhere('sku', 'like', '%' . $skuCode . '%');
                                        })
                                        ->whereHas('priceList', function($q) { 
                                            $q->where('is_active', true); 
                                        })
                                        ->join('supplier_price_lists', 'supplier_price_list_items.supplier_price_list_id', '=', 'supplier_price_lists.id')
                                        ->select('supplier_price_list_items.*')
                                        ->orderBy('supplier_price_lists.effective_date', 'desc')
                                        ->orderBy('supplier_price_list_items.id', 'desc')
                                        ->first();
                                    
                                    if ($priceItem) {
                                        $rawPlPrice = $priceItem->priceList->getPrimaryPriceForItem($priceItem) ?: 0;
                                        $plCurrency = $priceItem->priceList->currency ?? 'USD';
                                        $currentRate = $sale->exchange_rate ?: 24750;

                                        // Nếu bảng giá là VND, quy đổi về USD để nạp vào cột PriceList USD
                                        if (strtoupper($plCurrency) === 'VND' && $rawPlPrice > 0) {
                                            $plPrice = $rawPlPrice / $currentRate;
                                        } else {
                                            $plPrice = $rawPlPrice;
                                        }
                                    }
                                }
                                
                                // 2. If still 0, try name match as fallback
                                if ($plPrice == 0 && $pName) {
                                    $priceItem = \App\Models\SupplierPriceListItem::where('product_name', 'like', '%' . $pName . '%')
                                        ->whereHas('priceList', function($q) { 
                                            $q->where('is_active', true); 
                                        })
                                        ->join('supplier_price_lists', 'supplier_price_list_items.supplier_price_list_id', '=', 'supplier_price_lists.id')
                                        ->select('supplier_price_list_items.*')
                                        ->orderBy('supplier_price_lists.effective_date', 'desc')
                                        ->orderBy('supplier_price_list_items.id', 'desc')
                                        ->first();
                                    if ($priceItem) {
                                        $rawPlPrice = $priceItem->priceList->getPrimaryPriceForItem($priceItem) ?: 0;
                                        $plCurrency = $priceItem->priceList->currency ?? 'USD';
                                        $currentRate = $sale->exchange_rate ?: 24750;

                                        // Nếu bảng giá là VND, quy đổi về USD để nạp vào cột PriceList USD
                                        if (strtoupper($plCurrency) === 'VND' && $rawPlPrice > 0) {
                                            $plPrice = $rawPlPrice / $currentRate;
                                        } else {
                                            $plPrice = $rawPlPrice;
                                        }
                                    }
                                }
                            }
                        @endphp
                        @php
                            // Kiểm tra xem các loại chi phí có trong danh sách expenses không
                            $hasFinanceInExpenses = (bool) $findExpenseByTypes(['Chi phí Tài chính']);
                            $hasOverdueInExpenses = (bool) $findExpenseByTypes(['Lãi vay phát sinh do nợ quá hạn']);
                            $hasMgmtInExpenses = (bool) $findExpenseByTypes(['Chi phí Quản lí, Back Office & kỹ thuật']);
                            $hasSupport247InExpenses = (bool) $findExpenseByTypes(['24x7 Support cost']);
                            $hasOtherInExpenses = (bool) $findExpenseByTypes(['Other Support']);
                            $overdueExpenseRow = $findExpenseByTypes(['Lãi vay phát sinh do nợ quá hạn']);
                            $overdueMode = $overdueExpenseRow->input_mode ?? 'percent';
                            $financeExpenseRow = $findExpenseByTypes(['Chi phí Tài chính']);
                            $financeMode = $financeExpenseRow->input_mode ?? 'percent';
                            $financeAllocated = 0;
                            if ($financeExpenseRow && $financeExpenseRow->input_mode === 'fixed') {
                                $totalCostBaseRow = $sale->items->sum('cost_total') ?: 1;
                                $share = $item->cost_total / $totalCostBaseRow;
                                $financeAllocated = round($financeExpenseRow->amount * $share);
                            }
                            $managementExpenseRow = $findExpenseByTypes(['Chi phí Quản lí, Back Office & kỹ thuật']);
                            $managementMode = $managementExpenseRow->input_mode ?? 'percent';
                            $managementAllocated = 0;
                            if ($managementExpenseRow && $managementExpenseRow->input_mode === 'fixed') {
                                $totalCostBaseRow = $sale->items->sum('cost_total') ?: 1;
                                $share = $item->cost_total / $totalCostBaseRow;
                                $managementAllocated = round($managementExpenseRow->amount * $share);
                            }
                            $supportExpenseRow = $findExpenseByTypes(['24x7 Support cost']);
                            $supportMode = $supportExpenseRow->input_mode ?? 'percent';
                            $supportAllocated = 0;
                            if ($supportExpenseRow && $supportExpenseRow->input_mode === 'fixed') {
                                $totalCostBaseRow = $sale->items->sum('cost_total') ?: 1;
                                $share = $item->cost_total / $totalCostBaseRow;
                                $supportAllocated = round($supportExpenseRow->amount * $share);
                            }
                            $otherSupportExpenseRow = $findExpenseByTypes(['Other Support']);
                            $otherSupportMode = $otherSupportExpenseRow->input_mode ?? 'percent';
                            $otherSupportAllocated = 0;
                            if ($otherSupportExpenseRow && $otherSupportExpenseRow->input_mode === 'fixed') {
                                $totalCostBaseRow = $sale->items->sum('cost_total') ?: 1;
                                $share = $item->cost_total / $totalCostBaseRow;
                                $otherSupportAllocated = round($otherSupportExpenseRow->amount * $share);
                            }
                            
                            // Tính giá trị phân bổ cho Thuế nhà thầu từ SaleExpense (nếu có)
                            $contractorTaxExpense = $findExpenseByTypes(['Thuế nhà thầu']);
                            $contractorTaxAllocated = 0;
                            if ($contractorTaxExpense && $contractorTaxExpense->input_mode === 'fixed') {
                                $totalCostBaseRow = $sale->items->sum('cost_total') ?: 1;
                                $share = $item->cost_total / $totalCostBaseRow;
                                $contractorTaxAllocated = round($contractorTaxExpense->amount * $share);
                            } elseif (! is_null($item->contractor_tax_percent)) {
                                $contractorTaxAllocated = round($item->cost_total * ((float) $item->contractor_tax_percent / 100));
                            }

                            // POC & Imp: Manual only per item
                            $pocAllocated = 0;
                            $implAllocated = 0;

                            $pocModeRow = ! is_null($item->technical_poc_percent) ? 'percent' : 'fixed';
                            $pocPercentRow = (float) ($item->technical_poc_percent ?? 0);
                            $implModeRow = ! is_null($item->implementation_cost_percent) ? 'percent' : 'fixed';
                            $implPercentRow = (float) ($item->implementation_cost_percent ?? 0);
                            $taxModeRow = ! is_null($item->contractor_tax_percent) ? 'percent' : 'fixed';
                            $taxPercentRow = (float) ($item->contractor_tax_percent ?? 0);

                            $allExpensesList = $sale->expenses->values();
                            $getExpIdx = fn($type) => $allExpensesList->search(fn($e) => $e->type === $type);
                            
                            $fIdx = $getExpIdx('Chi phí Tài chính');
                            $mIdx = $getExpIdx('Chi phí Quản lí, Back Office & kỹ thuật');
                            $sIdx = $getExpIdx('24x7 Support cost');
                            $oIdx = $getExpIdx('Other Support');
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors border border-gray-400" 
                            x-data="pnlRow({
                                id: {{ $item->id }},
                                row_index: {{ $index }},
                                qty: {{ $item->quantity }},
                                usd_price: {{ (float)$item->usd_price > 0 ? $item->usd_price : $plPrice }},
                                discount_rate: {{ $item->discount_rate ?? 0 }},
                                import_cost_rate: {{ $item->import_cost_rate ?? 0 }},
                                exchange_rate: {{ $item->exchange_rate ?: ($sale->exchange_rate ?: 1) }},
                                cost_price: {{ $item->cost_price ?: 0 }},
                                cost_total: {{ $item->cost_total ?: 0 }},
                                revenue_total: {{ $item->total }},
                                finance_na: {{ (!is_null($item->finance_cost_percent) && floatval($item->finance_cost_percent) <= 0) ? 'true' : 'false' }},
                                finance_mode: '{{ $financeMode }}',
                                finance_allocated: {{ $financeAllocated }},
                                overdue_na: {{ (!is_null($item->overdue_interest_percent) && floatval($item->overdue_interest_percent) <= 0 && (!is_null($item->overdue_interest_cost) && floatval($item->overdue_interest_cost) <= 0)) ? 'true' : 'false' }},
                                overdue_mode: '{{ $overdueMode }}',
                                mgmt_na: {{ (!is_null($item->management_cost_percent) && floatval($item->management_cost_percent) <= 0) ? 'true' : 'false' }},
                                mgmt_mode: '{{ $managementMode }}',
                                mgmt_allocated: {{ $managementAllocated }},
                                support_na: {{ (!is_null($item->support_247_cost_percent) && floatval($item->support_247_cost_percent) <= 0) ? 'true' : 'false' }},
                                support_mode: '{{ $supportMode }}',
                                support_allocated: {{ $supportAllocated }},
                                other_na: {{ (!is_null($item->other_support_cost) && floatval($item->other_support_cost) <= 0) ? 'true' : 'false' }},
                                other_mode: '{{ $otherSupportMode }}',
                                other_allocated: {{ $otherSupportAllocated }},
                                oic: {{ $item->overdue_interest_cost ?: 0 }},
                                poc: {{ $item->technical_poc_cost ?: 0 }},
                                poc_mode: '{{ $pocModeRow }}',
                                poc_p: {{ $pocPercentRow }},
                                poc_allocated: {{ $pocAllocated }},
                                imp: {{ $item->implementation_cost ?: 0 }},
                                imp_mode: '{{ $implModeRow }}',
                                imp_p: {{ $implPercentRow }},
                                imp_allocated: {{ $implAllocated }},
                                tax: {{ $item->contractor_tax ?: 0 }},
                                tax_mode: '{{ $taxModeRow }}',
                                tax_p: {{ $taxPercentRow }},
                                tax_allocated: {{ $contractorTaxAllocated }},
                                extra_costs: [
                                    @foreach($extraExpenses as $extra)
                                        {
                                            id: {{ $extra->id }},
                                            type: '{{ $extra->type }}',
                                            mode: '{{ $extra->input_mode }}',
                                            val: {{ $extra->input_mode === 'percent' ? (floatval($extra->percent_value) ?: 0) : 0 }}
                                        },
                                    @endforeach
                                ],
                                extra_fixed_vals: {
                                    @foreach($extraExpenses as $extra)
                                        @php
                                            $itemExtraData = $item->extra_expenses_data ?? [];
                                            $perItemVal = null;
                                            if (isset($itemExtraData[(string)$extra->id])) {
                                                $perItemVal = (float) $itemExtraData[(string)$extra->id];
                                            }

                                            // Fallback: If no per-item data exists, and it's the first row,
                                            // default to the full expense amount found in the general section.
                                            if (is_null($perItemVal) && $index === 0 && ($extra->input_mode === 'fixed')) {
                                                $perItemVal = (float)($extra->amount ?? 0);
                                            }
                                        @endphp
                                        '{{ $extra->id }}': {{ $perItemVal ?: 0 }},
                                    @endforeach
                                }
                            })"
                            @pnl-recalc.window="calculate()">
                            <td class="px-2 py-2 text-center border border-gray-400 text-xs">{{ $item->product->code ?? '' }}</td>
                            <td class="px-2 py-2 border border-gray-400">
                                {{ $item->product_name }}
                                <!-- P&L Row Hidden Fields -->
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                <input type="hidden" name="items[{{ $index }}][finance_na]" :value="finance_na ? 1 : 0">
                                <input type="hidden" name="items[{{ $index }}][overdue_na]" :value="overdue_na ? 1 : 0">
                                <input type="hidden" name="items[{{ $index }}][management_na]" :value="mgmt_na ? 1 : 0">
                                <input type="hidden" name="items[{{ $index }}][support_na]" :value="support_na ? 1 : 0">
                                <input type="hidden" name="items[{{ $index }}][other_na]" :value="other_na ? 1 : 0">
                                
                                <input type="hidden" name="items[{{ $index }}][cost_price]" :value="cost_price">
                                <input type="hidden" name="items[{{ $index }}][cost_total]" :value="cost_total" class="row-cost-total-input">
                                <input type="hidden" name="items[{{ $index }}][total]" :value="revenue_total" class="row-revenue-total-input">
                                <input type="hidden" name="items[{{ $index }}][estimated_cost_usd]" :value="est_usd_total">
                                
                                {{-- Hidden inputs cho các trường chi phí không hiển thị --}}
                                @if(!$hasFinanceCost)
                                <input type="hidden" name="items[{{ $index }}][finance_cost_percent]" value="">
                                @endif
                                @if(!$hasOverdueInterest)
                                <input type="hidden" name="items[{{ $index }}][overdue_interest_cost]" value="0">
                                <input type="hidden" name="items[{{ $index }}][overdue_interest_percent]" value="">
                                @endif
                                @if(!$hasManagementCost)
                                <input type="hidden" name="items[{{ $index }}][management_cost_percent]" value="">
                                @endif
                                @if(!$hasSupport247)
                                <input type="hidden" name="items[{{ $index }}][support_247_cost_percent]" value="">
                                @endif
                                @if(!$hasOtherSupport)
                                <input type="hidden" name="items[{{ $index }}][other_support_cost]" value="0">
                                @endif
                                @if(!$hasTechnicalPoc)
                                <input type="hidden" name="items[{{ $index }}][technical_poc_cost]" value="0">
                                <input type="hidden" name="items[{{ $index }}][technical_poc_percent]" value="">
                                @endif
                                @if(!$hasImplementation)
                                <input type="hidden" name="items[{{ $index }}][implementation_cost]" value="0">
                                <input type="hidden" name="items[{{ $index }}][implementation_cost_percent]" value="">
                                @endif
                                @if(!$hasContractorTax)
                                <input type="hidden" name="items[{{ $index }}][contractor_tax]" value="0">
                                <input type="hidden" name="items[{{ $index }}][contractor_tax_percent]" value="">
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center border border-gray-400">{{ number_format($item->quantity) }}</td>
                            
                            <!-- PriceList USD -->
                            <td class="px-1 py-1 border border-gray-400 bg-blue-50">
                                <input type="number" step="0.01" name="items[{{ $index }}][usd_price]" 
                                    x-model="usd_p" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-right {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            <!-- Tỷ lệ discount -->
                            <td class="px-1 py-1 border border-gray-400 bg-blue-50">
                                <input type="number" step="0.1" name="items[{{ $index }}][discount_rate]" 
                                    x-model="disc" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-center {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            <!-- Tỷ lệ chi phí nhập hàng -->
                            <td class="px-1 py-1 border border-gray-400 bg-blue-50">
                                <input type="number" step="0.1" name="items[{{ $index }}][import_cost_rate]" 
                                    x-model="imp_r" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-center {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            <!-- Giá kho tạm tính (USD) -->
                            <td class="px-2 py-2 text-right border border-gray-400 bg-gray-50" x-text="formatNumber(est_usd_total, 2)"></td>
                            <!-- Tỷ giá -->
                            <td class="px-1 py-1 border border-gray-400">
                                <input type="number" name="items[{{ $index }}][exchange_rate]" 
                                    x-model="rate" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-right {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>

                            <!-- Giá đầu vào -->
                            <td class="px-2 py-2 text-right border border-gray-400 font-semibold" x-text="formatNumber(cost_price)"></td>
                            <td class="px-2 py-2 text-right border border-gray-400 font-bold bg-yellow-50" x-text="formatNumber(cost_total)"></td>
                            
                            <!-- Giá bán -->
                            <td class="px-2 py-2 text-right border border-gray-400">{{ number_format($item->price) }}</td>
                            <td class="px-2 py-2 text-right border border-gray-400 font-semibold bg-blue-50">{{ number_format($item->total) }}</td>
                            
                            <!-- Lãi/Lỗ VND -->
                            <td class="px-2 py-2 text-right border border-gray-400 font-bold" :class="gross_profit >= 0 ? 'text-green-700' : 'text-red-700'" x-text="formatNumber(gross_profit)"></td>
                            <!-- Lãi/Lỗ % -->
                            <td class="px-2 py-2 text-center border border-gray-400 font-bold" :class="gross_profit >= 0 ? 'text-green-700' : 'text-red-700'" x-text="gross_p + '%'"></td>
                            
                            <!-- Chi phí - chỉ hiển thị cột có dữ liệu -->
                            @if($hasFinanceCost)
                            <!-- Chi phí Tài chính -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs">
                                <input type="hidden" name="items[{{ $index }}][finance_cost_percent]" :value="finance_na || finance_mode !== 'percent' ? '' : finance_p">
                                
                                <div class="flex flex-col items-end space-y-1">
                                    <div x-show="finance_mode === 'fixed' && row_index === 0" class="w-full">
                                        @if($fIdx !== false)
                                        <input type="text" inputmode="numeric" 
                                            class="w-full text-xs p-1 border-2 border-red-300 rounded text-right bg-red-50 focus:bg-white font-bold extra-expense-money"
                                            name="expenses[{{ $fIdx }}][amount]"
                                            x-model="finance_amt"
                                            @input="$dispatch('pnl-recalc')"
                                            {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                        @endif
                                    </div>
                                    <span x-show="finance_mode !== 'fixed' || row_index !== 0"
                                        @click="if($sale_editable) { finance_na = !finance_na; calculate() }"
                                        class="cursor-pointer hover:bg-yellow-50 px-1 rounded block w-full"
                                        x-text="finance_na ? 'n/a' : formatNumber(finance_v)" 
                                        :class="finance_na ? 'text-gray-400' : ''"></span>
                                </div>
                            </td>
                            @endif
                            @if($hasOverdueInterest)
                            <!-- Lãi vay phát sinh (% như các trường khác) -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs cursor-pointer hover:bg-yellow-50" 
                                @click="if($sale_editable) { overdue_na = !overdue_na; calculate() }" :title="overdue_na ? 'Click để bật chi phí' : 'Click để tắt (n/a)'">
                                <input type="hidden" name="items[{{ $index }}][overdue_interest_percent]" :value="overdue_na || overdue_mode !== 'percent' ? '' : overdue_p">
                                <input type="hidden" name="items[{{ $index }}][overdue_interest_cost]" :value="overdue_na || overdue_mode !== 'fixed' ? 0 : oic">
                                <span x-text="overdue_na ? 'n/a' : formatNumber(overdue_v)" :class="overdue_na ? 'text-gray-400' : ''"></span>
                            </td>
                            @endif
                            @if($hasManagementCost)
                            <!-- Chi phí Quản lí -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs">
                                <input type="hidden" name="items[{{ $index }}][management_cost_percent]" :value="mgmt_na || mgmt_mode !== 'percent' ? '' : mgmt_p">
                                
                                <div class="flex flex-col items-end space-y-1">
                                    <div x-show="mgmt_mode === 'fixed' && row_index === 0" class="w-full">
                                        @if($mIdx !== false)
                                        <input type="text" inputmode="numeric" 
                                            class="w-full text-xs p-1 border-2 border-red-300 rounded text-right bg-red-50 focus:bg-white font-bold extra-expense-money"
                                            name="expenses[{{ $mIdx }}][amount]"
                                            x-model="mgmt_amt"
                                            @input="$dispatch('pnl-recalc')"
                                            {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                        @endif
                                    </div>
                                    <span x-show="mgmt_mode !== 'fixed' || row_index !== 0"
                                        @click="if($sale_editable) { mgmt_na = !mgmt_na; calculate() }"
                                        class="cursor-pointer hover:bg-yellow-50 px-1 rounded block w-full"
                                        x-text="mgmt_na ? 'n/a' : formatNumber(mgmt_v)" 
                                        :class="mgmt_na ? 'text-gray-400' : ''"></span>
                                </div>
                            </td>
                            @endif
                            @if($hasSupport247)
                            <!-- 24x7 Support -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs">
                                <input type="hidden" name="items[{{ $index }}][support_247_cost_percent]" :value="support_na || support_mode !== 'percent' ? '' : support_p">
                                
                                <div class="flex flex-col items-end space-y-1">
                                    <div x-show="support_mode === 'fixed' && row_index === 0" class="w-full">
                                        @if($sIdx !== false)
                                        <input type="text" inputmode="numeric" 
                                            class="w-full text-xs p-1 border-2 border-red-300 rounded text-right bg-red-50 focus:bg-white font-bold extra-expense-money"
                                            name="expenses[{{ $sIdx }}][amount]"
                                            x-model="support_amt"
                                            @input="$dispatch('pnl-recalc')"
                                            {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                        @endif
                                    </div>
                                    <span x-show="support_mode !== 'fixed' || row_index !== 0"
                                        @click="if($sale_editable) { support_na = !support_na; calculate() }"
                                        class="cursor-pointer hover:bg-yellow-50 px-1 rounded block w-full"
                                        x-text="support_na ? 'n/a' : formatNumber(support_v)" 
                                        :class="support_na ? 'text-gray-400' : ''"></span>
                                </div>
                            </td>
                            @endif
                            @if($hasOtherSupport)
                            <!-- Other Support -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs">
                                <input type="hidden" name="items[{{ $index }}][other_support_cost]" :value="other_na || other_mode !== 'percent' ? '' : other_p">
                                
                                <div class="flex flex-col items-end space-y-1">
                                    <div x-show="other_mode === 'fixed' && row_index === 0" class="w-full">
                                        @if($oIdx !== false)
                                        <input type="text" inputmode="numeric" 
                                            class="w-full text-xs p-1 border-2 border-red-300 rounded text-right bg-red-50 focus:bg-white font-bold extra-expense-money"
                                            name="expenses[{{ $oIdx }}][amount]"
                                            x-model="other_amt"
                                            @input="$dispatch('pnl-recalc')"
                                            {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                        @endif
                                    </div>
                                    <span x-show="other_mode !== 'fixed' || row_index !== 0"
                                        @click="if($sale_editable) { other_na = !other_na; calculate() }"
                                        class="cursor-pointer hover:bg-yellow-50 px-1 rounded block w-full"
                                        x-text="other_na ? 'n/a' : formatNumber(other_v)" 
                                        :class="other_na ? 'text-gray-400' : ''"></span>
                                </div>
                            </td>
                            @endif
                            @if($hasTechnicalPoc)
                            <!-- Technical POC (% đơn hàng → VND theo giá vốn dòng) -->
                            <td class="px-1 py-1 border border-gray-400 bg-orange-50 text-right text-xs">
                                <input type="hidden" name="items[{{ $index }}][technical_poc_percent]" :value="poc_mode === 'percent' ? poc_p : ''">
                                <input type="hidden" name="items[{{ $index }}][technical_poc_cost]" :value="poc_v">
                                <div class="flex flex-col items-end">
                                    <span x-show="poc_mode === 'percent'" class="block px-1 py-1 font-medium" x-text="formatNumber(poc_v)"></span>
                                    <input type="text" inputmode="numeric"
                                        x-show="poc_mode !== 'percent'"
                                        x-model="poc" @input="calculate()"
                                        class="w-full text-xs p-1 border-gray-300 rounded text-right extra-expense-money {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                        {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                </div>
                            </td>
                            @endif
                            @if($hasImplementation)
                            <td class="px-1 py-1 border border-gray-400 bg-orange-50 text-right text-xs">
                                <input type="hidden" name="items[{{ $index }}][implementation_cost_percent]" :value="imp_mode === 'percent' ? imp_p : ''">
                                <input type="hidden" name="items[{{ $index }}][implementation_cost]" :value="imp_v">
                                <div class="flex flex-col items-end">
                                    <span x-show="imp_mode === 'percent'" class="block px-1 py-1 font-medium" x-text="formatNumber(imp_v)"></span>
                                    <input type="text" inputmode="numeric"
                                        x-show="imp_mode !== 'percent'"
                                        x-model="imp" @input="calculate()"
                                        class="w-full text-xs p-1 border-gray-300 rounded text-right extra-expense-money {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                        {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                </div>
                            </td>
                            @endif
                            @if($hasContractorTax)
                            <td class="px-1 py-1 border border-gray-400 bg-orange-50 text-right text-xs">
                                <input type="hidden" name="items[{{ $index }}][contractor_tax_percent]" :value="tax_mode === 'percent' ? tax_p : ''">
                                <input type="hidden" name="items[{{ $index }}][contractor_tax]" :value="tax_v">
                                <div class="flex flex-col items-end">
                                    <span x-show="tax_mode === 'percent'" class="block px-1 py-1 font-medium" x-text="formatNumber(tax_v)"></span>
                                    <input type="text" inputmode="numeric"
                                        x-show="tax_mode !== 'percent'"
                                        x-model="tax" @input="calculate()"
                                        class="w-full text-xs p-1 border-gray-300 rounded text-right extra-expense-money {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                        {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                </div>
                            </td>
                            @endif
                            
                            {{-- Extra Expenses Cells --}}
                            @foreach($extraExpenses->values() as $eIdx => $extra)
                                <td class="px-1 py-1 text-right border border-gray-400 text-[10px] bg-orange-50/30">
                                    {{-- Percent mode: show calculated value --}}
                                    <span x-show="getExtraMode('{{ $extra->id }}') === 'percent'"
                                          class="cursor-pointer hover:bg-orange-100 px-1 rounded transition-colors block"
                                          x-text="isNaN(extra_vals[{{ $eIdx }}]) ? '0' : formatNumber(extra_vals[{{ $eIdx }}])"></span>
                                    {{-- Fixed mode: editable per-row input --}}
                                    <div x-show="getExtraMode('{{ $extra->id }}') === 'fixed'">
                                        <input type="text" inputmode="numeric"
                                            data-expense-id="{{ $extra->id }}"
                                            class="extra-item-input extra-expense-money w-full text-xs p-1 border-gray-300 rounded text-right {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                            x-model="extra_fixed_vals['{{ $extra->id }}']"
                                            @input="calculate(); setTimeout(() => { syncExtraAmtsFromRows(); $dispatch('pnl-recalc'); }, 50)"
                                            @blur="extra_fixed_vals['{{ $extra->id }}'] = Math.round(parseFloat(($event.target.value || '0').toString().replace(/,/g, '')) || 0); calculate(); setTimeout(() => { syncExtraAmtsFromRows(); $dispatch('pnl-recalc'); }, 50)"
                                            {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                    </div>
                                    {{-- Hidden input to submit per-item data --}}
                                    <input type="hidden" 
                                           :name="'items[{{ $index }}][extra_expenses_data][{{ $extra->id }}]'"
                                           :value="extra_fixed_vals['{{ $extra->id }}'] || 0">
                                </td>
                            @endforeach
                            
                            <!-- Tổng chi phí VND -->
                            <td class="px-2 py-2 text-right border border-gray-400 font-bold bg-yellow-100" x-text="formatNumber(total_costs)"></td>

                            <!-- Lợi nhuận sau chi phí VND -->
                            <td class="px-2 py-2 text-right border border-gray-400 font-bold bg-gray-100" 
                                :class="net_profit < 0 ? 'text-red-700' : 'text-green-700'"
                                x-text="formatNumber(net_profit)"></td>
                            <!-- Lợi nhuận sau chi phí % -->
                            <td class="px-2 py-2 text-center border border-gray-400 font-bold bg-gray-100" 
                                :class="net_profit < 0 ? 'text-red-700' : 'text-green-700'"
                                x-text="margin_p + '%'"></td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

    </form>
    <div class="p-6 bg-gray-50 border-t border-gray-200" x-data="{ showRejectForm: false }">
            {{-- Lịch sử duyệt P&L --}}
            @php
                $pnlHistory = \App\Models\ApprovalHistory::where('document_type', 'sale_pnl')
                    ->where('document_id', $sale->id)
                    ->orderBy('level')->orderBy('created_at')
                    ->get();
            @endphp

            @if($pnlHistory->isNotEmpty())
            <div class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2"><i class="fas fa-history mr-1"></i> Lịch sử duyệt P&L</h4>
                <div class="space-y-2">
                    @foreach($pnlHistory as $h)
                    <div class="flex items-start gap-3 text-xs">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5
                            {{ $h->action === 'approved' ? 'bg-green-100 text-green-600' : ($h->action === 'rejected' ? 'bg-red-100 text-red-600' : ($h->action === 'pending' ? 'bg-yellow-100 text-yellow-600' : 'bg-gray-100 text-gray-500')) }}">
                            <i class="fas fa-{{ $h->action === 'approved' ? 'check' : ($h->action === 'rejected' ? 'times' : ($h->action === 'pending' ? 'clock' : 'forward')) }} text-[10px]"></i>
                        </span>
                        <div>
                            <span class="font-medium text-gray-700">Cấp {{ $h->level }}: {{ $h->level_name }}</span>
                            — <span class="text-gray-500">{{ $h->approver_name }}</span>
                            @if($h->action_at) <span class="text-gray-400">– {{ $h->action_at->format('d/m/Y H:i') }}</span>@endif
                            @if($h->comment) <div class="italic text-gray-500 mt-0.5">"{{ $h->comment }}"</div>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Action buttons --}}
            <div class="flex flex-wrap items-center gap-3">
                @if($sale->isPlEditable() || $sale->pl_status === 'pending')
                    @if($sale->isPlEditable())
                        <button type="submit" form="pnlForm" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                            <i class="fas fa-save mr-2"></i> Lưu nháp P&L
                        </button>
                    @endif

                    @if($sale->pl_status !== null)
                        <button type="button" onclick="confirmAction('{{ route('sales.submitPnL', $sale) }}', '{{ $sale->pl_status === 'pending' ? 'Gửi duyệt lại P&L này (Dùng để sửa lỗi nếu bị kẹt)?' : 'Gửi duyệt P&L này?' }}')"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition ease-in-out duration-150">
                            <i class="fas fa-paper-plane mr-2"></i> {{ in_array($sale->pl_status, ['rejected', 'pending']) ? 'Gửi duyệt lại P&L' : 'Gửi duyệt P&L' }}
                        </button>
                    @endif
                @endif

                @if($sale->pl_status === 'pending')
                    @php
                        // Chỉ hiện nút duyệt nếu user này đúng là người được cấu hình duyệt
                        $pnlWorkflow  = \App\Models\ApprovalWorkflow::getForDocumentType('sale_pnl');
                        $pnlNextLevel = null;
                        $canApprovePnl = false;
                        if ($pnlWorkflow) {
                            $pendingHist = \App\Models\ApprovalHistory::where('document_type', 'sale_pnl')
                                ->where('document_id', $sale->id)
                                ->where('action', 'pending')
                                ->orderBy('level')
                                ->first();
                            if ($pendingHist) {
                                $pnlNextLevel = $pnlWorkflow->levels()->where('level', $pendingHist->level)->first();
                                if ($pnlNextLevel) {
                                    $canApprovePnl = $pnlNextLevel->canApprove(auth()->user(), $sale->total ?? 0);
                                }
                            }
                        }
                    @endphp

                    @if($canApprovePnl)
                    {{-- Approve --}}
                    <form action="{{ route('sales.approvePnL', $sale) }}" method="POST" class="inline" id="approveForm">
                        @csrf
                        <input type="hidden" name="comment" id="approveComment" value="">
                        <button type="submit" onclick="this.form.comment.value = prompt('Ghi chú duyệt (tùy chọn):') ?? ''"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition ease-in-out duration-150">
                            <i class="fas fa-check mr-2"></i> Duyệt P&L
                        </button>
                    </form>

                    {{-- Reject --}}
                    <button type="button" @click="showRejectForm = !showRejectForm"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition ease-in-out duration-150">
                        <i class="fas fa-times mr-2"></i> Từ chối
                    </button>

                    <div x-show="showRejectForm" x-transition class="w-full mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <form action="{{ route('sales.rejectPnL', $sale) }}" method="POST">
                            @csrf
                            <label class="block text-xs font-medium text-red-700 mb-1">Lý do từ chối (bắt buộc):</label>
                            <textarea name="comment" rows="2" required placeholder="Nhập lý do từ chối..."
                                class="w-full border border-red-300 rounded-lg px-3 py-2 text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
                            <button type="submit" class="px-4 py-1.5 bg-red-600 text-white rounded-lg text-xs hover:bg-red-700">
                                Xác nhận từ chối
                            </button>
                        </form>
                    </div>
                    @else
                    {{-- Người dùng không có quyền → chỉ hiện thông tin --}}
                    <div class="text-xs text-amber-600 flex items-center gap-1.5 px-3 py-2 bg-amber-50 rounded-lg border border-amber-200">
                        <i class="fas fa-clock"></i>
                        Đang chờ duyệt bởi: <strong>{{ $pnlNextLevel?->approver_label ?? 'người được cấu hình' }}</strong>
                    </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .table-pnl-editor th {
        font-size: 11px;
        padding: 4px 6px;
        white-space: normal;
        line-height: 1.1;
        background-color: #facc15; /* Vibrant yellow-400 */
        color: #000;
        vertical-align: middle;
        text-transform: none;
    }
    .table-pnl-editor td {
        font-size: 11px;
        padding: 2px 8px;
        border-color: #4b5563 !important; /* Darker borders like Excel */
    }
    .table-pnl-editor input {
        text-align: inherit;
        padding: 2px 4px;
        height: 24px;
        background-color: transparent;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    .table-pnl-editor input:hover {
        border-color: #94a3b8;
        background-color: rgba(255, 255, 255, 0.5);
    }
    .table-pnl-editor input:focus {
        border-color: #3b82f6;
        background-color: #fff;
        box-shadow: none;
        outline: none;
    }
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type=number] {
        -moz-appearance: textfield;
    }
    .bg-orange-50 {
        background-color: #fffaf0;
    }
</style>
