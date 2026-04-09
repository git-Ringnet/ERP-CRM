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
                        <select :name="'expenses['+idx+'][input_mode]'" x-model="exp.input_mode" @change="recalcRow(idx)"
                                class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white cursor-pointer">
                            <option value="percent">% (Phần trăm)</option>
                            <option value="fixed">VND (Số tiền)</option>
                        </select>
                    </div>

                    {{-- Giá trị input --}}
                    <div class="col-span-2">
                        <label class="block md:hidden text-xs font-medium text-gray-600 mb-1">Giá trị</label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0"
                                   x-model.number="exp.input_value"
                                   @input="recalcRow(idx)"
                                   :placeholder="exp.input_mode === 'percent' ? '0.0' : '0'"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-purple-400">
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

    {{-- Detailed Summary Footer --}}
    <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500">Tổng chi phí theo tỉ lệ:</span>
                    <div class="text-right">
                        <span class="font-bold text-purple-700" x-text="totalPercent + '%'"></span>
                        <span class="text-gray-400 mx-1">→</span>
                        <span class="font-medium text-gray-900" x-text="formatCurrency(totalPercentAmount)"></span>
                    </div>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500">Tổng chi phí cố định:</span>
                    <span class="font-bold text-orange-600" x-text="formatCurrency(totalFixedAmount)"></span>
                </div>
                <div class="text-[10px] text-gray-400 italic mt-1">
                    * Chi phí % được tính dựa trên giá vốn (Cost Base) của đơn hàng.
                </div>
            </div>
            
            <div class="flex flex-col justify-center items-end border-t md:border-t-0 md:border-l pt-3 md:pt-0 md:pl-6 border-gray-200">
                <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">Tổng chi phí đơn hàng</div>
                <div class="text-2xl font-black text-red-600" x-text="formatCurrency(totalExpenses)"></div>
            </div>
        </div>
    </div>
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
            input_value: e.input_mode === 'percent' ? (parseFloat(e.percent_value) || 0) : (parseFloat(e.amount) || 0),
            calculated_amount: parseFloat(e.amount) || 0,
            description: e.description || '',
        })),

        get totalPercent() {
            let p = this.expenses.reduce((sum, e) => sum + (e.input_mode === 'percent' ? (parseFloat(e.input_value) || 0) : 0), 0);
            return Math.round(p * 100) / 100;
        },

        get totalPercentAmount() {
            return Math.round(this.totalPercent * this.costBase / 100);
        },

        get totalFixedAmount() {
            return this.expenses.reduce((sum, e) => sum + (e.input_mode === 'fixed' ? (parseFloat(e.input_value) || 0) : 0), 0);
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
                input_value: 0,
                calculated_amount: 0,
                description: '',
            });
        },

        removeExpense(idx) {
            this.expenses.splice(idx, 1);
            this.recalcAll();
        },

        recalcRow(idx) {
            const exp = this.expenses[idx];
            if (exp.input_mode === 'percent') {
                exp.calculated_amount = Math.round((exp.input_value || 0) * this.costBase / 100);
            } else {
                exp.calculated_amount = parseFloat(exp.input_value) || 0;
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
            if (!value || value === 0) return '0 ₫';
            return new Intl.NumberFormat('vi-VN').format(Math.round(value)) + ' ₫';
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
