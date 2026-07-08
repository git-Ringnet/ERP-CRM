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
    // Include all non-standard expenses as columns in the P&L grid (allocated or unallocated)
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

    // Pre-compute PNL extra costs data for JavaScript — include ALL expenses (standard + extra)
    $pnlExtraCostsData = $allExpenses
        ->values()
        ->map(function($e) use ($totalCostBase, $standardTypes) {
            $calcAmount = ($e->input_mode === 'percent') 
                ? round($totalCostBase * ($e->percent_value ?? 0) / 100)
                : (float)($e->amount ?? 0);
            return [
                'id' => $e->id,
                'type' => $e->type,
                'input_mode' => $e->input_mode ?? 'fixed',
                'input_value' => ($e->input_mode === 'percent') ? (float)($e->percent_value ?? 0) : (float)($e->amount ?? 0),
                'calculated_amount' => $calcAmount,
                'description' => $e->description ?? '',
                'is_new' => false,
                'is_standard' => in_array($e->type, $standardTypes),
            ];
        })->toArray();
    $pnlSuggestedTypes = \App\Models\SaleExpense::pnlSuggestedTypes();
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
            overdue_amt: {{ (float) ($overdueExpense->amount ?? 0) }},
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

            cleanMoneyString(val) {
                return cleanMoneyString(val);
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

            global_cost: {{ (float) ($sale->items->sum('cost_total') ?? 0) }},
            global_revenue: {{ (float) ($sale->items->sum('total') ?? 0) }},
            global_profit: {{ (float) ($sale->margin ?? 0) }},
            global_margin_p: {{ (float) ($sale->margin_percent ?? 0) }},

            // Chi phí bổ sung PNL (deal-level, not allocated to items)
            pnl_extra_costs: (@json($pnlExtraCostsData) || []).map(exp => ({ ...exp, is_custom: false })),
            pnl_suggested_types: @json($pnlSuggestedTypes),
            standard_types: @json($standardTypes),

            // Check if an expense is already deducted as a column in the top P&L grid
            isColumnExpense(exp) {
                // Standard types are always grid columns (finance, mgmt, support, etc.)
                if (exp.is_standard || this.standard_types.includes(exp.type)) return true;
                // Extra expenses that have a grid column (existing DB records with extra_modes entry)
                if (exp.id && this.extra_modes[exp.id]) return true;
                return false;
            },

            get totalPnlExtraCosts() {
                let total = 0;
                const costBase = this.global_cost;
                (this.pnl_extra_costs || []).forEach(exp => {
                    // Only deduct in global_profit if it is NOT already a grid column
                    if (!this.isColumnExpense(exp)) {
                        if (exp.input_mode === 'percent') {
                            total += Math.round(costBase * (parseFloat(exp.input_value) || 0) / 100);
                        } else {
                            total += Math.round(parseFloat(exp.input_value) || 0);
                        }
                    }
                });
                return total;
            },

            addPnlExpense() {
                this.pnl_extra_costs.push({
                    id: null,
                    type: '',
                    input_mode: 'fixed',
                    input_value: 0,
                    calculated_amount: 0,
                    description: '',
                    is_new: true,
                    is_custom: false,
                });
            },

            removePnlExpense(exp) {
                const idx = this.pnl_extra_costs.indexOf(exp);
                if (idx === -1) return;
                if (exp && exp.id && !exp.is_new) {
                    // AJAX delete existing expense
                    if (!confirm('Xóa chi phí "' + (exp.type || '') + '"?')) return;
                    fetch('/sales/{{ $sale->id }}/pnl-expenses/' + exp.id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).then(r => r.json()).then(data => {
                        if (data.success) {
                            this.pnl_extra_costs.splice(idx, 1);
                            this.updateGlobalTotals();
                        } else {
                            alert(data.message || 'Lỗi khi xóa.');
                        }
                    }).catch(() => alert('Lỗi kết nối.'));
                } else {
                    this.pnl_extra_costs.splice(idx, 1);
                    this.updateGlobalTotals();
                }
            },

            recalcPnlExpense(exp) {
                if (!exp) return;
                const val = parseFloat(exp.input_value) || 0;
                if (exp.input_mode === 'percent') {
                    exp.calculated_amount = Math.round(this.global_cost * val / 100);
                } else {
                    exp.calculated_amount = Math.round(val);
                }

                // Sync change from bottom table to top grid column/header live
                if (exp.id && this.extra_modes[exp.id]) {
                    this.extra_modes[exp.id] = exp.input_mode;

                    const modeSelect = document.querySelector(`.extra-expense-mode[data-expense-id="${exp.id}"]`);
                    if (modeSelect && modeSelect.value !== exp.input_mode) {
                        modeSelect.value = exp.input_mode;
                        modeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    
                    if (exp.input_mode === 'percent') {
                        const headerInput = document.querySelector(`.extra-expense-input[data-expense-id="${exp.id}"][data-mode="percent"]`);
                        if (headerInput) {
                            headerInput.value = val;
                            headerInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    } else {
                        const rowInputs = document.querySelectorAll(`.extra-item-input[data-expense-id="${exp.id}"]`);
                        if (rowInputs.length > 0) {
                            rowInputs.forEach((el, rIdx) => {
                                el.value = (rIdx === 0) ? val : 0;
                                el.dispatchEvent(new Event('input', { bubbles: true }));
                                el.dispatchEvent(new Event('blur', { bubbles: true }));
                            });
                        }
                    }
                }

                // Sync standard expenses from bottom table to top grid variables
                if (exp.is_standard || this.standard_types.includes(exp.type)) {
                    const stdSyncMap = {
                        'Chi phí Tài chính': { p: 'finance_p', amt: 'finance_amt' },
                        'Lãi vay phát sinh do nợ quá hạn': { p: 'overdue_p', amt: 'overdue_amt' },
                        'Chi phí Quản lí, Back Office & kỹ thuật': { p: 'mgmt_p', amt: 'mgmt_amt' },
                        '24x7 Support cost': { p: 'support_p', amt: 'support_amt' },
                        'Other Support': { p: 'other_p', amt: 'other_amt' },
                    };
                    const mapping = stdSyncMap[exp.type];
                    if (mapping) {
                        if (exp.input_mode === 'percent') {
                            this[mapping.p] = val;
                        } else {
                            this[mapping.amt] = val;
                        }
                    }
                    // POC: sync to row-level poc_p or poc
                    if (exp.type === 'Technical support/POC' || exp.type === 'Technical support/POC 30%') {
                        document.querySelectorAll('tr[x-data]').forEach(row => {
                            try {
                                const rd = Alpine.$data(row);
                                if (rd && typeof rd.poc_v !== 'undefined') {
                                    if (exp.input_mode === 'percent') {
                                        rd.poc_mode = 'percent';
                                        rd.poc_p = val;
                                    } else {
                                        rd.poc_mode = 'fixed';
                                        rd.poc = val;
                                    }
                                    rd.calculate();
                                }
                            } catch(e) {}
                        });
                    }
                    // Implementation: sync to row-level imp_p or imp
                    if (exp.type === 'Chi phí triển khai hợp đồng') {
                        document.querySelectorAll('tr[x-data]').forEach(row => {
                            try {
                                const rd = Alpine.$data(row);
                                if (rd && typeof rd.imp_v !== 'undefined') {
                                    if (exp.input_mode === 'percent') {
                                        rd.imp_mode = 'percent';
                                        rd.imp_p = val;
                                    } else {
                                        rd.imp_mode = 'fixed';
                                        rd.imp = val;
                                    }
                                    rd.calculate();
                                }
                            } catch(e) {}
                        });
                    }
                    // Contractor Tax: sync to row-level
                    if (exp.type === 'Thuế nhà thầu') {
                        document.querySelectorAll('tr[x-data]').forEach(row => {
                            try {
                                const rd = Alpine.$data(row);
                                if (rd && typeof rd.tax_v !== 'undefined') {
                                    rd.tax_enabled = val > 0;
                                    rd.calculate();
                                }
                            } catch(e) {}
                        });
                    }
                }

                this.updateGlobalTotals();
                this.$dispatch('pnl-recalc');
            },

            updateGlobalTotals() {
                let cost = 0;
                let rev = 0;
                let profit = 0;
                
                document.querySelectorAll('.row-cost-total-input').forEach(el => cost += parseFloat(el.value) || 0);
                document.querySelectorAll('.row-revenue-total-input').forEach(el => rev += parseFloat(el.value) || 0);
                document.querySelectorAll('.row-net-profit-input').forEach(el => profit += parseFloat(el.value) || 0);
                
                this.global_cost = cost;
                this.global_revenue = rev;

                // Sync bottom table values from the grid headers/totals live
                (this.pnl_extra_costs || []).forEach(exp => {
                    // Sync non-standard extra expenses that have grid columns
                    if (exp.id && this.extra_modes[exp.id]) {
                        exp.input_mode = this.extra_modes[exp.id];
                        if (exp.input_mode === 'percent') {
                            const headerInput = document.querySelector(`.extra-expense-input[data-expense-id="${exp.id}"][data-mode="percent"]`);
                            if (headerInput) {
                                exp.input_value = parseFloat(headerInput.value) || 0;
                            }
                        } else {
                            exp.input_value = this.extra_amts[exp.id] || 0;
                        }
                    }

                    // Sync standard expenses from the top grid variables
                    if (exp.is_standard || this.standard_types.includes(exp.type)) {
                        const typeMap = {
                            'Chi phí Tài chính': { p: 'finance_p', amt: 'finance_amt', mode: '{{ $financeInputMode }}' },
                            'Lãi vay phát sinh do nợ quá hạn': { p: 'overdue_p', amt: 'overdue_amt', mode: '{{ $overdueInputMode }}' },
                            'Chi phí Quản lí, Back Office & kỹ thuật': { p: 'mgmt_p', amt: 'mgmt_amt', mode: '{{ $managementInputMode }}' },
                            '24x7 Support cost': { p: 'support_p', amt: 'support_amt', mode: '{{ $supportInputMode }}' },
                            'Other Support': { p: 'other_p', amt: 'other_amt', mode: '{{ $otherSupportInputMode }}' },
                        };
                        const mapping = typeMap[exp.type];
                        if (mapping) {
                            exp.input_mode = mapping.mode;
                            if (mapping.mode === 'percent') {
                                exp.input_value = parseFloat(this[mapping.p]) || 0;
                            } else {
                                exp.input_value = parseFloat(this[mapping.amt]) || 0;
                            }
                            // Compute calculated_amount by summing row-level values
                            const rowVarMap = {
                                'Chi phí Tài chính': 'finance_v',
                                'Lãi vay phát sinh do nợ quá hạn': 'overdue_v',
                                'Chi phí Quản lí, Back Office & kỹ thuật': 'mgmt_v',
                                '24x7 Support cost': 'support_v',
                                'Other Support': 'other_v',
                            };
                            const rowVar = rowVarMap[exp.type];
                            if (rowVar) {
                                let rowSum = 0;
                                document.querySelectorAll('tr[x-data]').forEach(row => {
                                    try {
                                        const rd = Alpine.$data(row);
                                        if (rd && typeof rd[rowVar] !== 'undefined') rowSum += rd[rowVar] || 0;
                                    } catch(e) {}
                                });
                                exp.calculated_amount = Math.round(rowSum);
                            }
                        }
                        // For POC, Implementation, Contractor Tax — sum from row values
                        if (exp.type === 'Technical support/POC' || exp.type === 'Technical support/POC 30%') {
                            let pocSum = 0;
                            document.querySelectorAll('.row-net-profit-input').forEach((el, i) => {
                                const row = el.closest('tr');
                                if (row && row.__x) {
                                    pocSum += row.__x.$data.poc_v || 0;
                                }
                            });
                            // Fallback: read from Alpine row data via DOM
                            if (pocSum === 0) {
                                document.querySelectorAll('tr[x-data]').forEach(row => {
                                    try {
                                        const rd = Alpine.$data(row);
                                        if (rd && typeof rd.poc_v !== 'undefined') pocSum += rd.poc_v || 0;
                                    } catch(e) {}
                                });
                            }
                            exp.calculated_amount = Math.round(pocSum);
                        } else if (exp.type === 'Chi phí triển khai hợp đồng') {
                            let impSum = 0;
                            document.querySelectorAll('tr[x-data]').forEach(row => {
                                try {
                                    const rd = Alpine.$data(row);
                                    if (rd && typeof rd.imp_v !== 'undefined') impSum += rd.imp_v || 0;
                                } catch(e) {}
                            });
                            exp.calculated_amount = Math.round(impSum);
                        } else if (exp.type === 'Thuế nhà thầu') {
                            let taxSum = 0;
                            document.querySelectorAll('tr[x-data]').forEach(row => {
                                try {
                                    const rd = Alpine.$data(row);
                                    if (rd && typeof rd.tax_v !== 'undefined') taxSum += rd.tax_v || 0;
                                } catch(e) {}
                            });
                            exp.calculated_amount = Math.round(taxSum);
                        }
                    }
                });

                // Recalc calculated amounts for bottom list (non-standard only, standard handled above)
                (this.pnl_extra_costs || []).forEach(exp => {
                    if (exp.is_standard || this.standard_types.includes(exp.type)) return;
                    const val = parseFloat(exp.input_value) || 0;
                    if (exp.input_mode === 'percent') {
                        exp.calculated_amount = Math.round(cost * val / 100);
                    } else {
                        exp.calculated_amount = Math.round(val);
                    }
                });

                // Deduct only non-column extra costs from profit
                this.global_profit = profit - this.totalPnlExtraCosts;
                
                // Net Revenue tổng = Tổng doanh thu các dòng * (1 - chiết khấu đơn hàng)
                let netRevTotal = rev * (1 - {{ $sale->discount ?? 0 }} / 100);
                this.global_margin_p = netRevTotal > 0 ? ((this.global_profit / netRevTotal) * 100).toFixed(1) : 0;
            },

            init() {
                // Listen for row extra expense changes and sync totals
                this.$watch('extra_amts', () => {
                    this.updateGlobalTotals();
                }, { deep: true });
                
                // Listen for row updates to refresh global totals
                window.addEventListener('pnl-row-updated', () => {
                    this.updateGlobalTotals();
                });

                // Sync extra_amts on initial load after rows render
                setTimeout(() => {
                    this.syncExtraAmtsFromRows();
                    this.updateGlobalTotals();
                }, 500);

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
            order_discount: data.order_discount || 0,
            net_revenue: 0,
            
            finance_na: data.finance_na,
            finance_mode: data.finance_mode || 'percent',
            finance_allocated: data.finance_allocated || 0,
            overdue_na: data.overdue_na,
            overdue_mode: data.overdue_mode || 'percent',
            overdue_allocated: data.overdue_allocated || 0,
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
            has_tax: data.has_tax || false,
            tax_enabled: data.tax_enabled || false,
            
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
            net_revenue: 0,

            init() {
                // Defer first calculation to ensure all rows are in DOM
                setTimeout(() => {
                    this.calculate();
                }, 200);

                // Lắng nghe sự kiện thay đổi chi phí để cập nhật has_tax
                window.addEventListener('expense-updated', (e) => {
                    const expenses = e.detail.expenses || [];
                    const hasTaxRow = expenses.some(exp => exp.type === 'Thuế nhà thầu');
                    if (this.has_tax !== hasTaxRow) {
                        this.has_tax = hasTaxRow;
                        this.calculate();
                    }
                });
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
                
                // Doanh thu thuần của dòng = Doanh thu dòng * (1 - chiết khấu tổng đơn)
                this.net_revenue = this.revenue_total * (1 - this.order_discount / 100);
                
                this.gross_profit = this.net_revenue - this.cost_total;
                this.gross_p = this.net_revenue > 0 ? ((this.gross_profit / this.net_revenue) * 100).toFixed(1) : 0;

                this.finance_v = this.finance_na ? 0 : (this.finance_mode === 'fixed' ? Math.round(calcShareFromTotal(this.finance_amt, this.cost_total, this.revenue_total, this.row_index)) : Math.round(this.cost_total * (this.finance_p / 100)));
                this.overdue_v = this.overdue_na ? 0 : (this.overdue_mode === 'fixed' ? Math.round(unformat(this.oic)) : Math.round(this.cost_total * (this.overdue_p / 100)));
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

                // Thuế nhà thầu: Formula = Cost / (1 - 10%) * 10% (only if enabled)
                this.tax_v = this.tax_enabled ? Math.round(this.cost_total / 0.9 * 0.1) : 0;
                
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
                this.margin_p = this.net_revenue > 0 ? ((this.net_profit / this.net_revenue) * 100).toFixed(1) : 0;
                
                // Notify parent about update
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('pnl-row-updated'));
                });
            },

            formatNumber(n, decimals = 0) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                }).format(n);
            },

            cleanMoneyString(val) {
                return cleanMoneyString(val);
            }
        }
    }

    function cleanMoneyString(val) {
        if (val === null || val === undefined) return '';
        return val.toString().replace(/,/g, '');
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

    function getAlpineData(el) {
        if (typeof Alpine !== 'undefined' && Alpine.$data) {
            return Alpine.$data(el);
        }
        return el.__x ? el.__x.$data : null;
    }

    function preparePnlJsonData(form) {
        // 1. Gom items
        const items = [];
        document.querySelectorAll('tr[x-data]').forEach(row => {
            try {
                const rowData = getAlpineData(row);
                if (rowData && typeof rowData.qty !== 'undefined' && typeof rowData.usd_p !== 'undefined') {
                    const extra_expenses_data = {};
                    if (rowData.extra_costs && Array.isArray(rowData.extra_costs)) {
                        rowData.extra_costs.forEach(ec => {
                            const editorEl = document.querySelector('[x-data^="pnlEditor"]');
                            const editorData = editorEl ? getAlpineData(editorEl) : null;
                            const currentMode = editorData ? editorData.getExtraMode(ec.id) : 'fixed';
                            if (currentMode === 'percent') {
                                const expenseInput = document.querySelector(`.extra-expense-input[data-expense-id="${ec.id}"][data-mode="percent"]`);
                                const ecVal = expenseInput ? parseFloat(expenseInput.value.toString().replace(/,/g, '')) || 0 : (parseFloat(ec.val) || 0);
                                extra_expenses_data[ec.id] = ecVal;
                            } else {
                                extra_expenses_data[ec.id] = parseFloat(rowData.extra_fixed_vals[ec.id]) || 0;
                            }
                        });
                    }

                    items.push({
                        id: rowData.id,
                        product_id: rowData.product_id,
                        finance_na: rowData.finance_na ? 1 : 0,
                        overdue_na: rowData.overdue_na ? 1 : 0,
                        management_na: rowData.mgmt_na ? 1 : 0,
                        support_na: rowData.support_na ? 1 : 0,
                        other_na: rowData.other_na ? 1 : 0,
                        cost_price: rowData.cost_price,
                        cost_total: rowData.cost_total,
                        total: rowData.revenue_total,
                        estimated_cost_usd: rowData.est_usd_total,
                        usd_price: rowData.usd_p,
                        discount_rate: rowData.disc,
                        import_cost_rate: rowData.imp_r,
                        exchange_rate: rowData.rate,
                        finance_cost_percent: rowData.finance_p,
                        overdue_interest_percent: rowData.overdue_p,
                        overdue_interest_cost: rowData.oic,
                        management_cost_percent: rowData.mgmt_p,
                        support_247_cost_percent: rowData.support_p,
                        other_support_cost: rowData.other_p,
                        technical_poc_percent: rowData.poc_mode === 'percent' ? rowData.poc_p : null,
                        technical_poc_cost: rowData.poc_v,
                        implementation_cost_percent: rowData.imp_mode === 'percent' ? rowData.imp_p : null,
                        implementation_cost: rowData.imp_v,
                        contractor_tax_enabled: rowData.tax_enabled ? 1 : 0,
                        contractor_tax: rowData.tax_v,
                        extra_expenses_data: extra_expenses_data
                    });
                }
            } catch(e) {
                console.error("Error serializing row:", e);
            }
        });

        // 2. Gom expenses
        const expenses = {};
        document.querySelectorAll('[name^="expenses["]').forEach(input => {
            const match = input.name.match(/^expenses\[([^\]]+)\]\[([^\]]+)\]/);
            if (match) {
                const idx = match[1];
                const key = match[2];
                if (!expenses[idx]) {
                    expenses[idx] = {};
                }
                let val = input.value;
                if (input.classList.contains('extra-expense-money')) {
                    val = val.toString().replace(/,/g, '');
                }
                expenses[idx][key] = val;
            }
        });

        // 3. Gom new_expenses & pnl_extra_expenses
        const new_expenses = [];
        const pnl_extra_expenses = [];
        const editorEl = document.querySelector('[x-data^="pnlEditor"]');
        if (editorEl) {
            try {
                const editorData = getAlpineData(editorEl);
                if (editorData && editorData.pnl_extra_costs) {
                    editorData.pnl_extra_costs.forEach((exp, idx) => {
                        const cleanVal = (exp.input_value || '').toString().replace(/,/g, '');
                        const calcAmt = (exp.calculated_amount || 0).toString().replace(/,/g, '');
                        
                        const expData = {
                            id: exp.id,
                            type: exp.type,
                            input_mode: exp.input_mode,
                            percent_value: exp.input_mode === 'percent' ? cleanVal : '',
                            amount: exp.input_mode === 'fixed' ? cleanVal : calcAmt,
                            description: exp.description
                        };
                        
                        if (exp.is_new) {
                            new_expenses.push(expData);
                        } else if (exp.id) {
                            pnl_extra_expenses.push(expData);
                        }
                    });
                }
            } catch(e) {
                console.error("Error serializing extra costs:", e);
            }
        }

        // Set JSON strings to hidden inputs
        setOrHiddenInput(form, 'items_json', JSON.stringify(items));
        setOrHiddenInput(form, 'expenses_json', JSON.stringify(Object.values(expenses)));
        setOrHiddenInput(form, 'new_expenses_json', JSON.stringify(new_expenses));
        setOrHiddenInput(form, 'pnl_extra_expenses_json', JSON.stringify(pnl_extra_expenses));

        // Disable all old inputs to avoid max_input_vars
        form.querySelectorAll('[name^="items["], [name^="expenses["], [name^="new_expenses["], [name^="pnl_extra_expenses["]').forEach(input => {
            input.disabled = true;
        });
    }

    function setOrHiddenInput(form, name, value) {
        let input = form.querySelector(`input[name="${name}"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            form.appendChild(input);
        }
        input.value = value;
    }

    function submitPnlFormAction(url, message) {
        // Kiểm tra xem đã chọn điều khoản thanh toán chưa
        const paymentTermType = '{{ $sale->payment_term_type }}';
        if (!paymentTermType) {
            Swal.fire({
                icon: 'warning',
                title: 'Chưa cấu hình điều khoản thanh toán',
                text: 'Vui lòng thiết lập và chọn mẫu điều khoản thanh toán cho đơn hàng trước khi gửi duyệt P&L.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Đồng ý'
            });
            return;
        }

        // Kiểm tra xem tổng tỷ lệ các đợt có bằng 100% không
        const milestones = {!! json_encode($sale->payment_terms ?? []) !!};
        let percentSum = 0;
        if (milestones && milestones.length > 0) {
            milestones.forEach(ms => {
                percentSum += parseFloat(ms.percentage || ms.percent || 0);
            });
        }
        if (milestones.length === 0 || Math.abs(percentSum - 100) > 0.01) {
            Swal.fire({
                icon: 'warning',
                title: 'Tỷ lệ thanh toán không hợp lệ',
                text: `Tổng tỷ lệ phần trăm các đợt thanh toán phải bằng chính xác 100% (Hiện tại: ${percentSum.toFixed(1)}%). Vui lòng cấu hình lại trước khi gửi duyệt P&L.`,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Đóng'
            });
            return;
        }

        Swal.fire({
            title: 'Xác nhận gửi duyệt P&L?',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const pnlForm = document.getElementById('pnlForm');
                if (pnlForm) {
                    // Unformat money values before submit
                    document.querySelectorAll('.extra-expense-money').forEach((input) => {
                        input.value = (input.value || '').toString().replace(/,/g, '');
                    });
                    
                    // Thêm flag để backend biết cần gửi duyệt sau khi lưu
                    let flag = pnlForm.querySelector('input[name="_submit_for_approval"]');
                    if (!flag) {
                        flag = document.createElement('input');
                        flag.type = 'hidden';
                        flag.name = '_submit_for_approval';
                        pnlForm.appendChild(flag);
                    }
                    flag.value = '1';

                    // Đóng gói dữ liệu JSON và disable input con
                    preparePnlJsonData(pnlForm);
                    
                    pnlForm.submit();
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => { 
        initExtraExpenseMoneyInputs(); 
        const pnlForm = document.getElementById('pnlForm');
        if (pnlForm) {
            pnlForm.addEventListener('submit', function() {
                document.querySelectorAll('.extra-expense-money').forEach((input) => {
                    input.value = (input.value || '').toString().replace(/,/g, '');
                });
                
                // Đóng gói dữ liệu JSON và disable input con
                preparePnlJsonData(this);
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


    <form id="pnlForm" action="{{ route('sales.updatePnL', $sale) }}" method="POST" enctype="multipart/form-data">
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
                                    <input type="number" step="any" x-model="finance_p" @input="$dispatch('pnl-recalc')" 
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
                                    <input type="number" step="any" x-model="overdue_p" @input="$dispatch('pnl-recalc')"
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
                                    <input type="number" step="any" x-model="mgmt_p" @input="$dispatch('pnl-recalc')" 
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
                                    <input type="number" step="any" x-model="support_p" @input="$dispatch('pnl-recalc')" 
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
                                    <input type="number" step="any" x-model="other_p" @input="$dispatch('pnl-recalc')" 
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
                                        <input type="number" step="any"
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
                            $overdueAllocated = 0;
                            if ($overdueExpenseRow && $overdueExpenseRow->input_mode === 'fixed') {
                                $totalCostBaseRow = $sale->items->sum('cost_total') ?: 1;
                                $share = $item->cost_total / $totalCostBaseRow;
                                $overdueAllocated = round($overdueExpenseRow->amount * $share);
                            }
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
                            $odIdx = $getExpIdx('Lãi vay phát sinh do nợ quá hạn');
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
                                order_discount: {{ $sale->discount ?? 0 }},
                                finance_na: {{ (!is_null($item->finance_cost_percent) && floatval($item->finance_cost_percent) <= 0) ? 'true' : 'false' }},
                                finance_mode: '{{ $financeMode }}',
                                finance_allocated: {{ $financeAllocated }},
                                overdue_na: {{ 
                                    $overdueMode === 'fixed' 
                                        ? 'false'
                                        : ((!is_null($item->overdue_interest_percent) && floatval($item->overdue_interest_percent) <= 0) ? 'true' : 'false')
                                }},
                                overdue_mode: '{{ $overdueMode }}',
                                overdue_allocated: {{ $overdueAllocated }},
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
                                has_tax: {{ $hasContractorTax ? 'true' : 'false' }},
                                tax_enabled: {{ $item->contractor_tax_enabled ? 'true' : 'false' }},
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
                                <input type="hidden" :value="net_profit" class="row-net-profit-input">
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
                                <input type="hidden" name="items[{{ $index }}][contractor_tax_enabled]" value="0">
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center border border-gray-400">{{ number_format($item->quantity) }}</td>
                            
                            <!-- PriceList USD -->
                            <td class="px-1 py-1 border border-gray-400 bg-blue-50">
                                <input type="number" step="any" name="items[{{ $index }}][usd_price]" 
                                    x-model="usd_p" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-right {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            <!-- Tỷ lệ discount -->
                            <td class="px-1 py-1 border border-gray-400 bg-blue-50">
                                <input type="number" step="any" name="items[{{ $index }}][discount_rate]" 
                                    x-model="disc" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-center {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            <!-- Tỷ lệ chi phí nhập hàng -->
                            <td class="px-1 py-1 border border-gray-400 bg-blue-50">
                                <input type="number" step="any" name="items[{{ $index }}][import_cost_rate]" 
                                    x-model="imp_r" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-center {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            <!-- Giá kho tạm tính (USD) -->
                            <td class="px-2 py-2 text-right border border-gray-400 bg-gray-50" x-text="formatNumber(est_usd_total, 2)"></td>
                            <!-- Tỷ giá -->
                            <td class="px-1 py-1 border border-gray-400">
                                <input type="number" step="any" name="items[{{ $index }}][exchange_rate]" 
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
                            <!-- Lãi vay phát sinh -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs bg-orange-50/10">
                                <input type="hidden" name="items[{{ $index }}][overdue_interest_percent]" :value="overdue_na || overdue_mode !== 'percent' ? '' : overdue_p">
                                <input type="hidden" name="items[{{ $index }}][overdue_interest_cost]" :value="overdue_na || overdue_mode !== 'fixed' ? 0 : overdue_v">
                                
                                <div class="flex flex-col items-end space-y-1">
                                    {{-- Percent mode: show calculated value --}}
                                    <span x-show="overdue_mode === 'percent'"
                                        @click="if($sale_editable) { overdue_na = !overdue_na; calculate() }"
                                        class="cursor-pointer hover:bg-yellow-50 px-1 rounded block w-full"
                                        x-text="overdue_na ? 'n/a' : formatNumber(overdue_v)" 
                                        :class="overdue_na ? 'text-gray-400' : ''"></span>
                                    
                                    {{-- Fixed mode: editable per-row input --}}
                                    <div x-show="overdue_mode === 'fixed'" class="w-full">
                                        <input type="text" inputmode="numeric" 
                                            class="w-full text-xs p-1 border border-gray-300 rounded text-right extra-expense-money {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                            x-model="oic"
                                            @input="calculate(); $dispatch('pnl-recalc')"
                                            {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                    </div>
                                </div>
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
                            <td class="px-1 py-1 border border-gray-400 bg-orange-50 text-center text-xs">
                                <div class="flex flex-col items-center justify-center gap-1 min-w-[80px]">
                                    <input type="hidden" name="items[{{ $index }}][contractor_tax_enabled]" :value="tax_enabled ? '1' : '0'">
                                    <input type="checkbox" x-model="tax_enabled" @change="calculate()"
                                           class="w-4 h-4 rounded border-2 border-indigo-600 text-indigo-600 shadow-sm focus:border-indigo-700 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 cursor-pointer"
                                           style="border: 2px solid #4f46e5 !important; background-color: #ffffff !important; width: 16px; height: 16px; min-width: 16px; min-height: 16px; cursor: pointer; -webkit-appearance: checkbox; appearance: checkbox;"
                                           {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                                    <input type="hidden" name="items[{{ $index }}][contractor_tax]" :value="tax_v">
                                    <span class="block px-1 font-bold text-blue-700 text-right w-full" x-text="formatNumber(tax_v)"></span>
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
                                           :value="cleanMoneyString(extra_fixed_vals['{{ $extra->id }}'] || 0)">
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
                <tfoot class="bg-gray-800 text-white font-bold text-xs sticky bottom-0">
                    <tr>
                        <td colspan="10" class="px-2 py-3 text-right uppercase tracking-wider">Tổng cộng (Đơn hàng):</td>
                        <td class="px-2 py-3 text-right"></td> {{-- Giá bán lẻ --}}
                        <td class="px-2 py-3 text-right bg-blue-900" x-text="formatNumber(global_revenue)"></td>
                        <td class="px-2 py-3 text-right" x-text="formatNumber(global_revenue * (1 - {{ $sale->discount ?? 0 }} / 100) - global_cost)"></td>
                        <td class="px-2 py-3 text-center" x-text="(global_revenue > 0 ? (((global_revenue * (1 - {{ $sale->discount ?? 0 }} / 100)) - global_cost) / (global_revenue * (1 - {{ $sale->discount ?? 0 }} / 100)) * 100).toFixed(1) : 0) + '%'"></td>
                        
                        {{-- Spacing for standard expenses --}}
                        <td colspan="{{ $visibleStandardCols + $extraExpenses->count() }}" class="px-2 py-3"></td>
                        
                        <td class="px-2 py-3 text-right bg-yellow-600"></td> {{-- Tổng chi phí dòng --}}
                        
                        <td class="px-2 py-3 text-right bg-green-700" x-text="formatNumber(global_profit)">{{ number_format($sale->margin) }}</td>
                        <td class="px-2 py-3 text-center bg-green-700" x-text="global_margin_p + '%'">{{ number_format($sale->margin_percent, 1) }}%</td>
                    </tr>
                </tfoot>

            </table>
        </div>


        <!-- PNL Extra Costs Section -->
        <div class="mt-4 p-4 bg-rose-50/50 rounded-lg border border-rose-200">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-xs font-semibold text-rose-700 uppercase flex items-center gap-1.5">
                    <i class="fas fa-coins"></i> Chi phí bổ sung P&L
                </h4>
                @if($sale->isPlEditable())
                <div class="flex items-center gap-2">
                    <span class="text-xs text-rose-600 font-medium mr-2">Nhấn lưu chi phí để cập nhật chi phí bảng P&L</span>
                    <button type="submit" form="pnlForm"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-xs font-medium shadow-sm">
                        <i class="fas fa-save mr-1.5"></i> Lưu chi phí
                    </button>
                    <button type="button" @click="addPnlExpense()"
                            class="inline-flex items-center px-3 py-1.5 bg-rose-100 text-rose-700 rounded-lg hover:bg-rose-200 transition-colors text-xs font-medium">
                        <i class="fas fa-plus mr-1.5"></i> Thêm chi phí
                    </button>
                </div>
                @endif
            </div>

            {{-- Header --}}
            <div class="hidden md:grid grid-cols-12 gap-2 px-3 py-2 bg-rose-100 border border-rose-200 rounded-t-lg text-[10px] font-bold text-rose-800 uppercase">
                <div class="col-span-3">Tên chi phí</div>
                <div class="col-span-2 text-center">Kiểu nhập</div>
                <div class="col-span-2 text-center">Tỷ lệ / Giá trị</div>
                <div class="col-span-3 text-right">Thành tiền (VND)</div>
                <div class="col-span-1 text-center">Ghi chú</div>
                <div class="col-span-1 text-center"><i class="fas fa-cog"></i></div>
            </div>

            {{-- Expense rows --}}
            <div class="border-x border-b border-rose-200 rounded-b-lg divide-y divide-rose-100">
                <template x-for="(exp, idx) in pnl_extra_costs.filter(e => !e.is_standard && !standard_types.includes(e.type))" :key="exp.id || idx">
                    <div class="px-3 py-2 hover:bg-rose-50 transition-colors"
                         :class="idx % 2 === 0 ? 'bg-white' : 'bg-rose-50/30'">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                            {{-- Tên chi phí --}}
                            <div class="col-span-3">
                                <label class="block md:hidden text-xs font-medium text-gray-600 mb-1">Tên chi phí</label>
                                <template x-if="exp.is_new">
                                    <div>
                                        <div x-show="!exp.is_custom">
                                            <select x-model="exp.type"
                                                    class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-rose-400 bg-white"
                                                    @change="if(exp.type === '__custom__') { exp.is_custom = true; exp.type = ''; $nextTick(() => { $el.closest('.col-span-3').querySelector('input[type=text]')?.focus(); }) }">
                                                <option value="">-- Chọn loại chi phí --</option>
                                                <template x-for="stype in pnl_suggested_types" :key="stype">
                                                    <option :value="stype" x-text="stype"></option>
                                                </template>
                                                <option value="__custom__">✏️ Nhập tên khác...</option>
                                            </select>
                                        </div>
                                        <div x-show="exp.is_custom" class="relative">
                                            <input x-ref="customType"
                                                   type="text" x-model="exp.type" placeholder="Nhập tên chi phí..."
                                                   class="w-full border border-gray-300 rounded-lg pl-2 pr-8 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-rose-400 bg-white">
                                            <button type="button" @click="exp.is_custom = false; exp.type = '';"
                                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-rose-500 transition-colors"
                                                    title="Chọn từ danh sách">
                                                <i class="fas fa-list text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!exp.is_new">
                                    <div class="flex items-center gap-2 py-1.5">
                                        <span class="text-xs font-medium text-gray-800" x-text="exp.type"></span>
                                        <span x-show="isColumnExpense(exp)" 
                                              class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold bg-blue-50 text-blue-600 border border-blue-100 whitespace-nowrap"
                                              title="Chi phí này cũng hiển thị như cột trong bảng P&L phía trên">
                                            <i class="fas fa-link mr-0.5"></i> Liên kết P&L
                                        </span>
                                    </div>
                                </template>
                            </div>

                            {{-- Kiểu nhập --}}
                            <div class="col-span-2">
                                <label class="block md:hidden text-xs font-medium text-gray-600 mb-1">Kiểu nhập</label>
                                <select x-model="exp.input_mode" @change="recalcPnlExpense(exp)"
                                        {{ !$sale->isPlEditable() ? 'disabled' : '' }}
                                        class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-rose-400 bg-white cursor-pointer {{ !$sale->isPlEditable() ? 'bg-gray-50' : '' }}">
                                    <option value="percent">% (Phần trăm)</option>
                                    <option value="fixed">VND (Số tiền)</option>
                                </select>
                            </div>

                            {{-- Giá trị --}}
                            <div class="col-span-2">
                                <label class="block md:hidden text-xs font-medium text-gray-600 mb-1">Giá trị</label>
                                <div class="relative">
                                    <input type="text" inputmode="numeric"
                                           x-model="exp.input_value"
                                           @input="recalcPnlExpense(exp)"
                                           @blur="exp.input_value = parseFloat((exp.input_value || '0').toString().replace(/,/g, '')) || 0"
                                           :placeholder="exp.input_mode === 'percent' ? '0.0' : '0'"
                                           {{ !$sale->isPlEditable() ? 'readonly' : '' }}
                                           class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-1.5 text-xs text-right focus:outline-none focus:ring-2 focus:ring-rose-400 {{ !$sale->isPlEditable() ? 'bg-gray-50' : '' }}">
                                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400"
                                          x-text="exp.input_mode === 'percent' ? '%' : '₫'"></span>
                                </div>
                            </div>

                            {{-- Thành tiền --}}
                            <div class="col-span-3">
                                <label class="block md:hidden text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
                                <div class="text-right font-semibold text-xs px-2 py-1.5 bg-white rounded-lg border border-rose-100"
                                     :class="exp.calculated_amount > 0 ? 'text-rose-700' : 'text-gray-400'">
                                    <span x-text="formatNumber(exp.calculated_amount) + ' ₫'"></span>
                                </div>
                            </div>

                            {{-- Ghi chú --}}
                            <div class="col-span-1">
                                <input type="text" x-model="exp.description" placeholder="..."
                                       {{ !$sale->isPlEditable() ? 'readonly' : '' }}
                                       class="w-full border border-gray-200 rounded px-1 py-1 text-[10px] {{ !$sale->isPlEditable() ? 'bg-gray-50' : '' }}">
                            </div>

                            {{-- Xóa --}}
                            <div class="col-span-1 flex justify-center">
                                @if($sale->isPlEditable())
                                <button type="button" @click="removePnlExpense(exp)"
                                        class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-100 rounded-lg transition-colors" title="Xóa chi phí">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                                @endif
                            </div>
                        </div>

                        {{-- Hidden inputs for form submission --}}
                        <div x-show="exp.is_new">
                            <input type="hidden" :name="'new_expenses['+idx+'][type]'" :value="exp.type" :disabled="!exp.is_new">
                            <input type="hidden" :name="'new_expenses['+idx+'][input_mode]'" :value="exp.input_mode" :disabled="!exp.is_new">
                            <input type="hidden" :name="'new_expenses['+idx+'][percent_value]'" :value="cleanMoneyString(exp.input_mode === 'percent' ? exp.input_value : '')" :disabled="!exp.is_new">
                            <input type="hidden" :name="'new_expenses['+idx+'][amount]'" :value="cleanMoneyString(exp.input_mode === 'fixed' ? exp.input_value : exp.calculated_amount)" :disabled="!exp.is_new">
                            <input type="hidden" :name="'new_expenses['+idx+'][description]'" :value="exp.description" :disabled="!exp.is_new">
                        </div>
                        <div x-show="!exp.is_new && exp.id">
                            <input type="hidden" :name="'pnl_extra_expenses['+idx+'][id]'" :value="exp.id" :disabled="exp.is_new || !exp.id">
                            <input type="hidden" :name="'pnl_extra_expenses['+idx+'][input_mode]'" :value="exp.input_mode" :disabled="exp.is_new || !exp.id">
                            <input type="hidden" :name="'pnl_extra_expenses['+idx+'][percent_value]'" :value="cleanMoneyString(exp.input_mode === 'percent' ? exp.input_value : '')" :disabled="exp.is_new || !exp.id">
                            <input type="hidden" :name="'pnl_extra_expenses['+idx+'][amount]'" :value="cleanMoneyString(exp.input_mode === 'fixed' ? exp.input_value : exp.calculated_amount)" :disabled="exp.is_new || !exp.id">
                            <input type="hidden" :name="'pnl_extra_expenses['+idx+'][description]'" :value="exp.description" :disabled="exp.is_new || !exp.id">
                        </div>
                    </div>
                </template>

                {{-- Empty state --}}
                <div x-show="pnl_extra_costs.filter(e => !e.is_standard && !standard_types.includes(e.type)).length === 0" class="px-3 py-6 text-center text-gray-400 text-xs">
                    <i class="fas fa-inbox text-xl mb-2"></i>
                    <p>Chưa có chi phí bổ sung. Nhấn "+ Thêm chi phí" để thêm.</p>
                </div>
            </div>

            {{-- Total --}}
            <div x-show="pnl_extra_costs.filter(e => !e.is_standard && !standard_types.includes(e.type)).length > 0" class="mt-3 flex justify-between items-center px-3 py-2.5 bg-rose-100 rounded-lg border border-rose-200">
                <span class="text-xs font-bold text-rose-800 uppercase">Tổng chi phí bổ sung:</span>
                <span class="text-sm font-bold text-rose-800" x-text="formatNumber(totalPnlExtraCosts) + ' ₫'"></span>
            </div>

            {{-- Net profit after extra costs --}}
            <div x-show="pnl_extra_costs.filter(e => !e.is_standard && !standard_types.includes(e.type)).length > 0" class="mt-2 flex justify-between items-center px-3 py-2 bg-gray-800 rounded-lg">
                <span class="text-xs font-bold text-white uppercase">Lợi nhuận ròng sau chi phí bổ sung:</span>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold" :class="global_profit >= 0 ? 'text-green-400' : 'text-red-400'" x-text="formatNumber(global_profit) + ' ₫'"></span>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded" :class="global_profit >= 0 ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300'" x-text="global_margin_p + '%'"></span>
                </div>
            </div>
        </div>

        <!-- PNL Approval Attachments Section -->
        <div class="mt-4 p-4 bg-white rounded-lg border border-gray-200">
            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">
                <i class="fas fa-paperclip mr-1"></i> Hồ sơ đính kèm phê duyệt P&L
            </h4>

            <!-- Danh sách file đã upload (chỉ hiện file nháp chưa gửi duyệt) -->
            @php
                $currentPnlAttachments = $sale->pnlAttachments->where('approval_history_id', null);
            @endphp
            @if($currentPnlAttachments->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                    @foreach($currentPnlAttachments as $attachment)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition duration-150">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <span class="text-xl flex-shrink-0">
                                    <i class="{{ $attachment->file_icon }}"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <a href="{{ route('sales.pnl-attachments.download', [$sale, $attachment]) }}" 
                                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-900 truncate block" 
                                       title="Tải xuống: {{ $attachment->file_name }}">
                                        {{ $attachment->file_name }}
                                    </a>
                                    <span class="text-[10px] text-gray-400 block">{{ $attachment->file_size_human }} — bởi {{ $attachment->uploader->name ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        onclick="openFilePreviewModal('{{ route('sales.pnl-attachments.preview', [$sale, $attachment]) }}', '{{ $attachment->file_name }}')"
                                        class="text-gray-400 hover:text-indigo-600 p-1 rounded transition duration-150"
                                        title="Xem trước">
                                    <i class="fas fa-eye text-xs"></i>
                                </button>
                                @if($sale->isPlEditable())
                                    <button type="button" 
                                            onclick="deletePnlAttachment('{{ route('sales.pnl-attachments.delete', [$sale, $attachment]) }}', this)"
                                            class="text-gray-400 hover:text-red-600 p-1 rounded transition duration-150"
                                            title="Xóa file">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-400 italic mb-3">Chưa có tài liệu đính kèm.</p>
            @endif

            <!-- Khu vực tải lên file mới (chỉ hiện khi chỉnh sửa được) -->
            @if($sale->isPlEditable())
                <div class="pnl-upload-zone border-2 border-dashed border-gray-300 rounded-lg p-6 hover:bg-indigo-50/30 hover:border-indigo-400 transition cursor-pointer flex flex-col items-center justify-center gap-2"
                     onclick="document.getElementById('pnlAttachmentsInput').click()">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 hover:text-indigo-500 transition"></i>
                    <p class="text-xs font-semibold text-gray-600">Kéo thả file hoặc <span class="text-indigo-600 hover:underline">chọn file từ máy tính</span> để đính kèm</p>
                    <p class="text-[10px] text-gray-400">Hỗ trợ file: PDF, Word, Excel, PowerPoint, Zip, Rar, Hình ảnh (Tối đa 20MB/file)</p>
                    <input type="file" name="pnl_attachments[]" multiple class="hidden" id="pnlAttachmentsInput"
                           onchange="handleSelectedPnlFiles(this)">
                </div>
                
                <!-- Danh sách file đang chờ upload -->
                <div id="pnlFilesQueue" class="mt-3 space-y-2 hidden">
                    <h5 class="text-[10px] font-semibold text-gray-400 uppercase">Tệp chuẩn bị tải lên:</h5>
                    <div id="pnlFilesQueueList" class="space-y-1"></div>
                </div>
            @endif
        </div>

    </form>
    <div class="p-6 bg-gray-50 border-t border-gray-200" x-data="{ showRejectForm: false, showRevisionForm: false }">
            {{-- Lịch sử duyệt P&L --}}
            @php
                $pnlHistory = \App\Models\ApprovalHistory::where('document_type', 'sale_pnl')
                    ->where('document_id', $sale->id)
                    ->where('action', '!=', 'submitted')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get();
            @endphp

            @if($pnlHistory->isNotEmpty())
            <div class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
                <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2"><i class="fas fa-history mr-1"></i> Lịch sử duyệt P&L</h4>
                <div class="space-y-3">
                    @foreach($pnlHistory as $h)
                    <div class="flex items-start gap-3 text-xs border-b border-gray-100 pb-3 last:border-b-0 last:pb-0">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5
                            {{ $h->action === 'approved' ? 'bg-green-100 text-green-600' : ($h->action === 'rejected' ? 'bg-red-100 text-red-600' : ($h->action === 'need_revision' ? 'bg-amber-100 text-amber-600' : ($h->action === 'pending' ? 'bg-yellow-100 text-yellow-600' : ($h->action === 'submitted' ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500')))) }}">
                            <i class="fas fa-{{ $h->action === 'approved' ? 'check' : ($h->action === 'rejected' ? 'times' : ($h->action === 'need_revision' ? 'pen' : ($h->action === 'pending' ? 'clock' : ($h->action === 'submitted' ? 'paper-plane' : 'forward')))) }} text-[10px]"></i>
                        </span>
                        <div class="flex-1">
                            <div class="flex items-center flex-wrap gap-1.5">
                                <span class="font-semibold text-gray-800">Cấp {{ $h->level }}: {{ $h->level_name }}</span>
                                @if($h->action === 'submitted')
                                    <span class="px-1.5 py-0.5 text-[9px] font-semibold bg-indigo-50 text-indigo-700 rounded border border-indigo-200">Yêu cầu duyệt</span>
                                @elseif($h->action === 'approved')
                                    <span class="px-1.5 py-0.5 text-[9px] font-semibold bg-green-50 text-green-700 rounded border border-green-200">Đã duyệt</span>
                                @elseif($h->action === 'rejected')
                                    <span class="px-1.5 py-0.5 text-[9px] font-semibold bg-red-50 text-red-700 rounded border border-red-200">Từ chối</span>
                                @elseif($h->action === 'need_revision')
                                    <span class="px-1.5 py-0.5 text-[9px] font-semibold bg-amber-50 text-amber-700 rounded border border-amber-200">Yêu cầu chỉnh sửa</span>
                                @elseif($h->action === 'pending')
                                    <span class="px-1.5 py-0.5 text-[9px] font-semibold bg-yellow-50 text-yellow-700 rounded border border-yellow-200">Chờ duyệt</span>
                                @endif
                                <span class="text-gray-400">—</span>
                                <span class="text-gray-600 font-medium">{{ $h->approver_name }}</span>
                                @if($h->action_at)
                                    <span class="text-gray-400 text-[10px]">({{ $h->action_at->format('d/m/Y H:i') }})</span>
                                @endif
                            </div>
                            @if($h->comment)
                                <div class="italic text-gray-500 mt-1 bg-gray-50 p-2 rounded border border-gray-100">"{{ $h->comment }}"</div>
                            @endif

                            @if($h->pnlAttachments && $h->pnlAttachments->isNotEmpty())
                                <div class="mt-2 pl-2 border-l-2 border-indigo-200">
                                    <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mb-1">Tài liệu đã gửi kèm:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($h->pnlAttachments as $att)
                                            <div class="inline-flex items-center gap-2 bg-indigo-50/50 hover:bg-indigo-50 border border-indigo-100 text-indigo-950 rounded px-2.5 py-1 text-[11px] transition">
                                                <i class="{{ $att->file_icon }}"></i>
                                                <span class="font-medium truncate max-w-[180px]" title="{{ $att->file_name }}">{{ $att->file_name }}</span>
                                                <span class="text-gray-400 text-[10px]">({{ $att->file_size_human }})</span>
                                                <div class="flex items-center gap-2 ml-1 border-l border-indigo-200 pl-2">
                                                    <!-- Preview -->
                                                    <a href="javascript:void(0)" 
                                                       onclick="openFilePreviewModal('{{ route('sales.pnl-attachments.preview', [$sale, $att]) }}', '{{ $att->file_name }}')"
                                                       class="text-indigo-600 hover:text-indigo-900 transition flex items-center" 
                                                       title="Xem trực tiếp">
                                                        <i class="fas fa-eye text-xs"></i>
                                                    </a>
                                                    <!-- Download -->
                                                    <a href="{{ route('sales.pnl-attachments.download', [$sale, $att]) }}" 
                                                       class="text-indigo-600 hover:text-indigo-900 transition flex items-center" 
                                                       title="Tải về máy">
                                                        <i class="fas fa-download text-xs"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
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

                    @if($sale->pl_status !== null && $sale->pl_status !== 'pending')
                        <button type="button" onclick="submitPnlFormAction('{{ route('sales.submitPnL', $sale) }}', 'Gửi duyệt P&L này?')"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition ease-in-out duration-150">
                            <i class="fas fa-paper-plane mr-2"></i> {{ in_array($sale->pl_status, ['rejected', 'need_revision']) ? 'Gửi duyệt lại P&L' : 'Gửi duyệt P&L' }}
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
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition ease-in-out duration-150">
                            <i class="fas fa-check mr-2"></i> Duyệt P&L
                        </button>
                    </form>

                    {{-- Reject --}}
                    <button type="button" @click="showRejectForm = !showRejectForm; showRevisionForm = false"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition ease-in-out duration-150">
                        <i class="fas fa-times mr-2"></i> Từ chối
                    </button>

                    {{-- Request Revision --}}
                    <button type="button" @click="showRevisionForm = !showRevisionForm; showRejectForm = false"
                        class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600 transition ease-in-out duration-150">
                        <i class="fas fa-pen mr-2"></i> Yêu cầu chỉnh sửa
                    </button>

                    {{-- Reject Form --}}
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

                    {{-- Revision Form --}}
                    <div x-show="showRevisionForm" x-transition class="w-full mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <form action="{{ route('sales.requestRevisionPnL', $sale) }}" method="POST">
                            @csrf
                            <label class="block text-xs font-medium text-amber-700 mb-1">Nội dung cần chỉnh sửa (bắt buộc):</label>
                            <textarea name="comment" rows="2" required placeholder="Mô tả những điểm cần chỉnh sửa..."
                                class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-amber-400"></textarea>
                            <button type="submit" class="px-4 py-1.5 bg-amber-500 text-white rounded-lg text-xs hover:bg-amber-600">
                                Gửi yêu cầu chỉnh sửa
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

<script>
    function handleSelectedPnlFiles(input) {
        const queue = document.getElementById('pnlFilesQueue');
        const list = document.getElementById('pnlFilesQueueList');
        if (!queue || !list) return;

        list.innerHTML = '';
        if (input.files && input.files.length > 0) {
            queue.classList.remove('hidden');
            Array.from(input.files).forEach((file, index) => {
                let size = file.size;
                let sizeStr = size >= 1048576 ? (size / 1048576).toFixed(1) + ' MB' : (size / 1024).toFixed(1) + ' KB';
                
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-2 bg-indigo-50 rounded border border-indigo-100 text-xs';
                item.innerHTML = `
                    <div class="flex items-center gap-2 overflow-hidden">
                        <i class="fas fa-file text-indigo-500"></i>
                        <span class="font-medium text-indigo-900 truncate">${file.name}</span>
                        <span class="text-indigo-400 text-[10px]">(${sizeStr})</span>
                    </div>
                    <span class="text-[10px] text-indigo-500 font-semibold uppercase">Sẵn sàng</span>
                `;
                list.appendChild(item);
            });
        } else {
            queue.classList.add('hidden');
        }
    }

    function deletePnlAttachment(url, buttonEl) {
        if (confirm('Bạn chắc chắn muốn xóa tệp đính kèm này?')) {
            buttonEl.disabled = true;
            buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i>';
            
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    buttonEl.closest('.flex.items-center.justify-between').remove();
                } else {
                    alert(data.message || 'Lỗi khi xóa tệp đính kèm.');
                    buttonEl.disabled = false;
                    buttonEl.innerHTML = '<i class="fas fa-trash-alt text-xs"></i>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Lỗi kết nối hoặc hệ thống.');
                buttonEl.disabled = false;
                buttonEl.innerHTML = '<i class="fas fa-trash-alt text-xs"></i>';
            });
        }
    }
</script>

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
