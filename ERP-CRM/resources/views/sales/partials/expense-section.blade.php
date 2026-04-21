{{-- Shared Expense Section for Create/Edit Sale Order --}}
{{-- Expects: $expenses (array of expense data), $currencySymbol (string) --}}

@php
    $expenses = $expenses ?? \App\Models\SaleExpense::defaultExpenses();
    $currencySymbol = $currencySymbol ?? '₫';
@endphp

<div class="border-t pt-4" x-data="expenseManager()">
    <div class="flex justify-between items-center mb-4">
        <h4 class="text-lg font-medium text-gray-900">
            <i class="fas fa-file-invoice-dollar text-purple-500 mr-2"></i>Chi phí đơn hàng
        </h4>
        <button type="button" @click="addExpense()"
                class="inline-flex items-center px-3 py-1.5 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors text-sm font-medium">
            <i class="fas fa-plus mr-1.5"></i> Thêm chi phí
        </button>
    </div>

    {{-- Header --}}
    <div class="hidden md:grid grid-cols-12 gap-2 px-3 py-2 bg-gray-100 border border-gray-200 rounded-t-lg text-xs font-bold text-gray-700">
        <div class="col-span-4">Tên chi phí</div>
        <div class="col-span-2 text-center">Kiểu nhập</div>
        <div class="col-span-2 text-center">Tỷ lệ / Giá trị</div>
        <div class="col-span-3 text-right">Thành tiền (VND)</div>
        <div class="col-span-1 text-center"><i class="fas fa-cog"></i></div>
    </div>

    {{-- Expense rows --}}
    <div id="expenseList" class="border-x border-b border-gray-200 rounded-b-lg divide-y divide-gray-100">
        <template x-for="(exp, idx) in expenses" :key="idx">
            <div class="px-3 py-2 hover:bg-gray-50 transition-colors"
                 :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/50'">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                    {{-- Tên chi phí --}}
                    <div class="col-span-4">
                        <label class="block md:hidden text-xs font-medium text-gray-600 mb-1">Tên chi phí</label>
                        <input type="text" :name="'expenses['+idx+'][type]'" x-model="exp.type"
                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400"
                               placeholder="Nhập tên chi phí...">
                    </div>

                    <div class="col-span-2">
                        <label class="block md:hidden text-xs font-medium text-gray-600 mb-1">Kiểu nhập</label>
                        <select :name="'expenses['+idx+'][input_mode]'" x-model="exp.input_mode"
                                @focus="exp._modeBeforeFocus = exp.input_mode"
                                @change="onExpenseInputModeChange(idx)"
                                class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white cursor-pointer">
                            <option value="percent">% (Phần trăm)</option>
                            <option value="fixed">VND (Số tiền)</option>
                        </select>
                    </div>

                    {{-- Giá trị input --}}
                    <div class="col-span-2">
                        <label class="block md:hidden text-xs font-medium text-gray-600 mb-1">Giá trị</label>
                        <div class="relative">
                            <input type="text"
                                   x-model="exp.input_value"
                                   @input="recalcRow(idx)"
                                   @blur="exp.input_value = formatRawInput(exp.input_value)"
                                   @focus="exp.input_value = (exp.input_value || '').toString().replace(/,/g, '')"
                                   :placeholder="exp.input_mode === 'percent' ? '0.0' : '0'"
                                   class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-1.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400"
                                  x-text="exp.input_mode === 'percent' ? '%' : '₫'"></span>
                        </div>
                        {{-- Hidden fields for server --}}
                        <input type="hidden" :name="'expenses['+idx+'][percent_value]'" :value="exp.input_mode === 'percent' ? exp.input_value : ''">
                        <input type="hidden" :name="'expenses['+idx+'][amount]'" :value="exp.calculated_amount">
                        <input type="hidden" :name="'expenses['+idx+'][description]'" x-model="exp.description">
                    </div>

                    {{-- Thành tiền (VND) - Only show for Fixed or when redundant display is OK --}}
                    {{-- User requested to stop showing individual VND for % rows to avoid redundancy --}}
                    <div class="col-span-3">
                        <label class="block md:hidden text-xs font-medium text-gray-600 mb-1">Thành tiền (VND)</label>
                        <div class="text-right font-medium text-sm px-2 py-1.5 bg-gray-50 rounded-lg border border-gray-100"
                             :class="exp.calculated_amount > 0 ? 'text-gray-900' : 'text-gray-400'">
                            <span x-show="exp.input_mode === 'fixed'" x-text="formatCurrency(exp.calculated_amount)"></span>
                            <span x-show="exp.input_mode === 'percent'" class="text-gray-300 italic text-xs">Phụ thuộc giá vốn</span>
                        </div>
                    </div>

                    {{-- Xóa --}}
                    <div class="col-span-1 flex justify-center">
                        <button type="button" @click="removeExpense(idx)"
                                class="p-1.5 text-red-500 hover:bg-red-100 rounded-lg transition-colors" title="Xóa chi phí">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Empty state --}}
        <div x-show="expenses.length === 0" class="px-3 py-6 text-center text-gray-400 text-sm">
            <i class="fas fa-inbox text-2xl mb-2"></i>
            <p>Chưa có chi phí nào. Nhấn "+ Thêm chi phí" để thêm.</p>
        </div>
    </div>

    {{-- Keep total cost for JS calculations, but hide from UI --}}
    <span id="totalCost" class="hidden" x-text="Math.round(totalExpenses)"></span>
</div>

@push('scripts')
<script>
function expenseManager() {
    // Parse existing expenses from server
    const existingExpenses = @json($expenses);

    return {
        expenses: existingExpenses.map(e => ({
            type: e.type || '',
            input_mode: e.input_mode || 'fixed',
            _modeBeforeFocus: e.input_mode || 'fixed',
            input_value: e.input_mode === 'percent' ? (parseFloat(e.percent_value) || 0) : (parseFloat(e.amount) || 0),
            calculated_amount: parseFloat(e.amount) || 0,
            description: e.description || '',
        })).map(e => {
            // Initial format
            e.input_value = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(e.input_value);
            return e;
        }),

        get totalPercent() {
            let p = this.expenses.reduce((sum, e) => sum + (e.input_mode === 'percent' ? (parseFloat(e.input_value) || 0) : 0), 0);
            return Math.round(p * 100) / 100;
        },

        get totalPercentAmount() {
            return Math.round(this.totalPercent * this.costBase / 100);
        },

        get totalFixedAmount() {
            return this.expenses.reduce((sum, e) => sum + (e.input_mode === 'fixed' ? (this.parseValue(e.input_value) || 0) : 0), 0);
        },

        get totalExpenses() {
            // Re-calculate to ensure consistency with breakdown
            return this.totalPercentAmount + this.totalFixedAmount;
        },

        get costBase() {
            // In the order form, we don't have a direct "cost total" sum yet because product costs are server-side.
            // As a fallback requested by user, we use the primary amount base (estimated).
            // Usually, this is the subtotal (Total Revenue) or a specific cost base if available.
            // For now, use the subtotal as the calculation base.
            let total = 0;
            const subtotalEl = document.getElementById('subtotal');
            if (subtotalEl) {
                total = parseFloat(subtotalEl.value.replace(/[^0-9.]/g, '')) || 0;
            }
            return total;
        },

        addExpense() {
            this.expenses.push({
                type: '',
                input_mode: 'fixed',
                _modeBeforeFocus: 'fixed',
                input_value: 0,
                calculated_amount: 0,
                description: '',
            });
        },

        onExpenseInputModeChange(idx) {
            const exp = this.expenses[idx];
            const prev = exp._modeBeforeFocus ?? exp.input_mode;
            const now = exp.input_mode;
            const base = this.costBase || 0;
            let v = this.parseValue(exp.input_value) || 0;
            if (prev === 'fixed' && now === 'percent' && base > 0 && v > 0) {
                exp.input_value = this.formatRawInput(Math.round((v / base) * 1e6) / 1e4);
            } else if (prev === 'percent' && now === 'fixed' && base > 0 && v > 0) {
                exp.input_value = this.formatRawInput(Math.round((v * base) / 100));
            }
            exp._modeBeforeFocus = now;
            this.recalcRow(idx);
        },

        removeExpense(idx) {
            this.expenses.splice(idx, 1);
            this.recalcAll();
        },

        recalcRow(idx) {
            const exp = this.expenses[idx];
            const val = this.parseValue(exp.input_value);
            if (exp.input_mode === 'percent') {
                exp.calculated_amount = Math.round((val || 0) * this.costBase / 100);
            } else {
                exp.calculated_amount = val || 0;
            }
        },

        recalcAll() {
            this.expenses.forEach((_, idx) => this.recalcRow(idx));
            
            // Sync the grand total to the margin display in parent scope if needed
            // The margin display in create.blade.php / edit.blade.php relies on this.totalExpenses
            // Actually, calculateTotal() in the main page handles it by summing .expense-amount
            // We need to ensure the hidden inputs for the server are updated
        },

        formatCurrency(value) {
            if (!value && value !== 0) return '0 ₫';
            return new Intl.NumberFormat('en-US').format(Math.round(value)) + ' ₫';
        },

        parseValue(val) {
            if (!val && val !== 0) return 0;
            return parseFloat(val.toString().replace(/,/g, '')) || 0;
        },

        formatRawInput(val) {
            if (!val && val !== 0) return '';
            const n = parseFloat(val.toString().replace(/,/g, ''));
            if (isNaN(n)) return '';
            return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(n);
        },

        init() {
            this.$watch('expenses', () => {
                this.recalcAll();
                // Trigger global calculate if function exists
                if (typeof window.calculateTotal === 'function') {
                    window.calculateTotal();
                }
            }, { deep: true });

            // Listen for subtotal changes to recalculate percent-based expenses
            const subtotalEl = document.getElementById('subtotal');
            if (subtotalEl) {
                // Poll for value changes since subtotal is updated by other JS
                setInterval(() => {
                    const currentBase = this.costBase;
                    if (this._lastBase !== currentBase) {
                        this._lastBase = currentBase;
                        this.recalcAll();
                    }
                }, 1000);
            }
        }
    };
}
</script>
@endpush
