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

    @php
        $standardTypes = [
            'Chi phí Tài chính',
            'Lãi vay phát sinh do nợ quá hạn',
            'Chi phí Quản lí, Back Office & kỹ thuật',
            '24x7 Support cost',
            'Other Support',
            'Technical support/POC',
            'Chi phí triển khai hợp đồng',
            'Thuế nhà thầu'
        ];
        
        $allExpenses = $sale->expenses;
        $standardExpenses = $allExpenses->whereIn('type', $standardTypes);
        $extraExpenses = $allExpenses->whereNotIn('type', $standardTypes);
        
        $totalCostBase = $sale->items->sum('cost_total') ?: 1;
        $totalColspan = 8 + $extraExpenses->count();
    @endphp

    <form id="pnlForm" action="{{ route('sales.updatePnL', $sale) }}" method="POST">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse table-pnl-editor min-w-[2400px]">
                <thead>
                    <!-- Header Row 1: Main sections -->
                    <tr class="bg-yellow-400 text-xs font-bold text-center border border-gray-800">
                        <th class="px-2 py-2 border border-gray-800" rowspan="2">P/N<br/>Supplier</th>
                        <th class="px-2 py-2 border border-gray-800" rowspan="2">Hàng hóa/Dịch vụ</th>
                        <th class="px-2 py-2 border border-gray-800" rowspan="2">SL</th>
                        <th class="px-2 py-2 border border-gray-800" rowspan="2">PriceList<br/>USD</th>
                        <th class="px-2 py-2 border border-gray-800" rowspan="2">Tỷ lệ<br/>discount</th>
                        <th class="px-2 py-2 border border-gray-800" rowspan="2">Tỷ lệ chi phí<br/>nhập hàng</th>
                        <th class="px-2 py-2 border border-gray-800" rowspan="2">Giá kho tạm tính<br/>(USD)</th>
                        <th class="px-2 py-2 border border-gray-800" rowspan="2">Tỷ giá</th>
                        <th class="px-2 py-2 border border-gray-800" colspan="2">Giá đầu vào (chưa VAT)</th>
                        <th class="px-2 py-2 border border-gray-800" colspan="2">Giá bán (chưa VAT)</th>
                        <th class="px-2 py-2 border border-gray-800" colspan="2">Lợi nhuận gộp</th>
                        <th class="px-2 py-2 border border-gray-800 bg-yellow-400" colspan="{{ $totalColspan }}">Chi phí</th>
                        <th class="px-2 py-2 border border-gray-800 bg-yellow-400" rowspan="2">Tổng chi phí<br/><span class="font-normal text-[10px]">VND</span></th>
                        <th class="px-2 py-2 border border-gray-800 bg-gray-200" colspan="2">Lợi nhuận sau chi phí</th>
                    </tr>
                    <!-- Header Row 2: Column details -->
                    <tr class="bg-yellow-400 text-[10px] font-bold text-center border border-gray-800">
                        <th class="px-2 py-1 border border-gray-800">Giá VND</th>
                        <th class="px-2 py-1 border border-gray-800">Thành tiền</th>
                        <th class="px-2 py-1 border border-gray-800">Đơn Giá bán</th>
                        <th class="px-2 py-1 border border-gray-800">Thành tiền</th>
                        <th class="px-2 py-1 border border-gray-800">VND</th>
                        <th class="px-2 py-1 border border-gray-800">%</th>
                        
                        {{-- Standard columns --}}
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400">
                            Chi phí Tài chính
                            <div class="text-red-600 font-normal flex items-center justify-center gap-0.5">
                                <input type="number" step="0.1" x-model="finance_p" @input="$dispatch('pnl-recalc')" 
                                    class="w-10 text-center text-red-600 text-[10px] p-0 border-0 border-b border-red-400 bg-transparent focus:ring-0"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                            </div>
                        </th>
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400">
                            Lãi vay phát sinh do nợ quá hạn
                        </th>
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400">
                            Chi phí Quản lí, Back Office & kỹ thuật
                            <div class="text-red-600 font-normal flex items-center justify-center gap-0.5">
                                <input type="number" step="0.1" x-model="mgmt_p" @input="$dispatch('pnl-recalc')" 
                                    class="w-10 text-center text-red-600 text-[10px] p-0 border-0 border-b border-red-400 bg-transparent focus:ring-0"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                            </div>
                        </th>
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400">
                            24x7 Support cost
                            <div class="text-red-600 font-normal flex items-center justify-center gap-0.5">
                                <input type="number" step="0.1" x-model="support_p" @input="$dispatch('pnl-recalc')" 
                                    class="w-10 text-center text-red-600 text-[10px] p-0 border-0 border-b border-red-400 bg-transparent focus:ring-0"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                            </div>
                        </th>
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400">
                            Other Support
                            <div class="text-red-600 font-normal flex items-center justify-center gap-0.5">
                                <input type="number" step="0.1" x-model="other_p" @input="$dispatch('pnl-recalc')" 
                                    class="w-10 text-center text-red-600 text-[10px] p-0 border-0 border-b border-red-400 bg-transparent focus:ring-0"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>%
                            </div>
                        </th>
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400">Technical support/POC 30%</th>
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400">Chi phí triển khai hợp đồng (Tiếp khách, cấu hình)</th>
                        <th class="px-2 py-1 border border-gray-800 bg-yellow-400">Thuế nhà thầu</th>
                        
                        {{-- Extra columns --}}
                        @foreach($extraExpenses as $extra)
                            <th class="px-2 py-1 border border-gray-800 bg-orange-100">
                                {{ $extra->type }}
                                @if($extra->input_mode === 'percent')
                                    <div class="text-orange-600 font-normal">{{ number_format($extra->percent_value, 1) }}%</div>
                                @endif
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
                                        $plPrice = $priceItem->priceList->getPrimaryPriceForItem($priceItem) ?: 0;
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
                                        $plPrice = $priceItem->priceList->getPrimaryPriceForItem($priceItem) ?: 0;
                                    }
                                }
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors border border-gray-400" 
                            x-data="pnlRow({
                                id: {{ $item->id }},
                                qty: {{ $item->quantity }},
                                usd_price: {{ (float)$item->usd_price > 0 ? $item->usd_price : $plPrice }},
                                discount_rate: {{ $item->discount_rate ?? 0 }},
                                import_cost_rate: {{ $item->import_cost_rate ?? 0 }},
                                exchange_rate: {{ $item->exchange_rate ?: ($sale->exchange_rate ?: 1) }},
                                cost_price: {{ $item->cost_price ?: 0 }},
                                cost_total: {{ $item->cost_total ?: 0 }},
                                revenue_total: {{ $item->total }},
                                finance_na: {{ $item->finance_cost_percent ? 'false' : 'true' }},
                                mgmt_na: {{ $item->management_cost_percent ? 'false' : 'true' }},
                                support_na: {{ $item->support_247_cost_percent ? 'false' : 'true' }},
                                other_na: {{ $item->other_support_cost ? 'false' : 'true' }},
                                oic: {{ $item->overdue_interest_cost ?: 0 }},
                                poc: {{ $item->technical_poc_cost ?: 0 }},
                                imp: {{ $item->implementation_cost ?: 0 }},
                                tax: {{ $item->contractor_tax ?: 0 }},
                                extra_costs: [
                                    @foreach($extraExpenses as $extra)
                                        {
                                            type: '{{ $extra->type }}',
                                            mode: '{{ $extra->input_mode }}',
                                            val: {{ $extra->input_mode === 'percent' ? ($extra->percent_value ?: 0) : ($extra->amount ?: 0) }}
                                        },
                                    @endforeach
                                ]
                            })"
                            @pnl-recalc.window="calculate()">
                            <td class="px-2 py-2 text-center border border-gray-400 text-xs">{{ $item->product->code ?? '' }}</td>
                            <td class="px-2 py-2 border border-gray-400">
                                {{ $item->product_name }}
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $index }}][cost_price]" :value="cost_price">
                                <input type="hidden" name="items[{{ $index }}][cost_total]" :value="cost_total">
                                <input type="hidden" name="items[{{ $index }}][estimated_cost_usd]" :value="est_usd_total">
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
                            
                            <!-- Chi phí - VND = giá nhập × % header (click để toggle n/a) -->
                            <!-- Chi phí Tài chính -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs cursor-pointer hover:bg-yellow-50" 
                                @click="if($sale_editable) { finance_na = !finance_na; calculate() }" :title="finance_na ? 'Click để bật chi phí' : 'Click để tắt (n/a)'">
                                <input type="hidden" name="items[{{ $index }}][finance_cost_percent]" :value="finance_na ? '' : finance_p">
                                <span x-text="finance_na ? 'n/a' : formatNumber(finance_v)" :class="finance_na ? 'text-gray-400' : ''"></span>
                            </td>
                            <!-- Lãi vay phát sinh (nhập VND trực tiếp) -->
                            <td class="px-1 py-1 border border-gray-400 bg-orange-50">
                                <input type="number" name="items[{{ $index }}][overdue_interest_cost]" 
                                    x-model="oic" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-right {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            <!-- Chi phí Quản lí -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs cursor-pointer hover:bg-yellow-50" 
                                @click="if($sale_editable) { mgmt_na = !mgmt_na; calculate() }" :title="mgmt_na ? 'Click để bật chi phí' : 'Click để tắt (n/a)'">
                                <input type="hidden" name="items[{{ $index }}][management_cost_percent]" :value="mgmt_na ? '' : mgmt_p">
                                <span x-text="mgmt_na ? 'n/a' : formatNumber(mgmt_v)" :class="mgmt_na ? 'text-gray-400' : ''"></span>
                            </td>
                            <!-- 24x7 Support -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs cursor-pointer hover:bg-yellow-50" 
                                @click="if($sale_editable) { support_na = !support_na; calculate() }" :title="support_na ? 'Click để bật chi phí' : 'Click để tắt (n/a)'">
                                <input type="hidden" name="items[{{ $index }}][support_247_cost_percent]" :value="support_na ? '' : support_p">
                                <span x-text="support_na ? 'n/a' : formatNumber(support_v)" :class="support_na ? 'text-gray-400' : ''"></span>
                            </td>
                            <!-- Other Support -->
                            <td class="px-2 py-2 text-right border border-gray-400 text-xs cursor-pointer hover:bg-yellow-50" 
                                @click="if($sale_editable) { other_na = !other_na; calculate() }" :title="other_na ? 'Click để bật chi phí' : 'Click để tắt (n/a)'">
                                <input type="hidden" name="items[{{ $index }}][other_support_cost]" :value="other_na ? '' : other_p">
                                <span x-text="other_na ? 'n/a' : formatNumber(other_v)" :class="other_na ? 'text-gray-400' : ''"></span>
                            </td>
                            <!-- Technical POC -->
                            <td class="px-1 py-1 border border-gray-400 bg-orange-50">
                                <input type="number" name="items[{{ $index }}][technical_poc_cost]" 
                                    x-model="poc" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-right {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            <!-- Chi phí triển khai -->
                            <td class="px-1 py-1 border border-gray-400 bg-orange-50">
                                <input type="number" name="items[{{ $index }}][implementation_cost]" 
                                    x-model="imp" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-right {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            <!-- Thuế nhà thầu -->
                            <td class="px-1 py-1 border border-gray-400 bg-orange-50">
                                <input type="number" name="items[{{ $index }}][contractor_tax]" 
                                    x-model="tax" @input="calculate()"
                                    class="w-full text-xs p-1 border-gray-300 rounded text-right {{ !$sale->isPlEditable() ? 'bg-gray-100' : '' }}"
                                    {{ !$sale->isPlEditable() ? 'disabled' : '' }}>
                            </td>
                            
                            {{-- Extra Expenses Cells --}}
                            @foreach($extraExpenses as $eIdx => $extra)
                                <td class="px-2 py-2 text-right border border-gray-400 text-[10px] bg-orange-50/30">
                                    <span x-text="formatNumber(extra_vals[{{ $eIdx }}])"></span>
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
                @if($sale->isPlEditable())
                    <button type="submit" form="pnlForm" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                        <i class="fas fa-save mr-2"></i> Lưu nháp P&L
                    </button>

                    @if($sale->pl_status !== null)
                        <button type="button" onclick="confirmAction('{{ route('sales.submitPnL', $sale) }}', 'Gửi duyệt P&L này?')"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition ease-in-out duration-150">
                            <i class="fas fa-paper-plane mr-2"></i> Gửi duyệt P&L
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
    </form>
</div>

<script>
    function pnlEditor() {
        return {
            // Global percentages (shared across all items, shown in header)
            finance_p: {{ $sale->items->whereNotNull('finance_cost_percent')->where('finance_cost_percent', '>', 0)->first()?->finance_cost_percent ?? 1 }},
            mgmt_p: {{ $sale->items->whereNotNull('management_cost_percent')->where('management_cost_percent', '>', 0)->first()?->management_cost_percent ?? 1 }},
            support_p: {{ $sale->items->whereNotNull('support_247_cost_percent')->where('support_247_cost_percent', '>', 0)->first()?->support_247_cost_percent ?? 0.5 }},
            other_p: {{ $sale->items->where('other_support_cost', '>', 0)->first()?->other_support_cost ?? 1 }},
            
            formatNumber(n) {
                return new Intl.NumberFormat('vi-VN').format(Math.round(n));
            }
        }
    }

    function pnlRow(data) {
        return {
            id: data.id,
            qty: data.qty,
            usd_p: data.usd_price,
            disc: data.discount_rate,
            imp_r: data.import_cost_rate,
            rate: data.exchange_rate,
            cost_price: data.cost_price,
            cost_total: data.cost_total,
            revenue_total: data.revenue_total,
            
            // Per-item n/a flags (true = cost not applicable)
            finance_na: data.finance_na,
            mgmt_na: data.mgmt_na,
            support_na: data.support_na,
            other_na: data.other_na,
            
            // Fixed amount costs (per item)
            oic: data.oic,
            poc: data.poc,
            imp: data.imp,
            tax: data.tax,
            
            // Extra dynamic costs
            extra_costs: data.extra_costs || [],
            extra_vals: [],
            
            // Editable flag
            $sale_editable: {{ $sale->isPlEditable() ? 'true' : 'false' }},
            
            est_usd_total: 0,
            gross_profit: 0,
            gross_p: 0,
            finance_v: 0,
            mgmt_v: 0,
            support_v: 0,
            other_v: 0,
            total_costs: 0,
            net_profit: 0,
            margin_p: 0,

            init() {
                this.calculate();
            },

            calculate() {
                // Giá kho tạm tính (USD) = Price USD * (1 - Disc) * (1 + Import)
                const estimatedCostUsd = this.usd_p * (1 - (this.disc / 100)) * (1 + (this.imp_r / 100));
                this.est_usd_total = estimatedCostUsd;
                
                // Giá VND (Đơn giá) = Giá kho tạm tính (USD) * Exchange Rate
                this.cost_price = Math.round(estimatedCostUsd * this.rate);
                
                // Giá VND (Thành tiền) = Đơn giá VND * SL
                this.cost_total = this.cost_price * this.qty;
                
                // Lợi nhuận gộp VND = Revenue - Cost VND
                this.gross_profit = this.revenue_total - this.cost_total;
                this.gross_p = this.revenue_total > 0 ? ((this.gross_profit / this.revenue_total) * 100).toFixed(1) : 0;

                // Chi phí (%) chuẩn
                this.finance_v = this.finance_na ? 0 : Math.round(this.cost_total * (this.finance_p / 100));
                this.mgmt_v = this.mgmt_na ? 0 : Math.round(this.cost_total * (this.mgmt_p / 100));
                this.support_v = this.support_na ? 0 : Math.round(this.cost_total * (this.support_p / 100));
                this.other_v = this.other_na ? 0 : Math.round(this.cost_total * (this.other_p / 100));
                
                // Chi phí động (Extra)
                let extraSum = 0;
                this.extra_vals = [];
                this.extra_costs.forEach(ec => {
                    let v = 0;
                    if (ec.mode === 'percent') {
                        v = Math.round(this.cost_total * (ec.val / 100));
                    } else {
                        // Phân bổ phí cố định theo tỉ lệ giá vốn của dòng này trên tổng giá vốn đơn hàng
                        const share = this.cost_total / {{ $totalCostBase }};
                        v = Math.round(ec.val * share);
                    }
                    this.extra_vals.push(v);
                    extraSum += v;
                });
                
                this.total_costs = Math.round(
                    this.finance_v + 
                    parseFloat(this.oic || 0) +
                    this.mgmt_v + 
                    this.support_v + 
                    this.other_v + 
                    parseFloat(this.poc || 0) + 
                    parseFloat(this.imp || 0) + 
                    parseFloat(this.tax || 0) +
                    extraSum
                );
                                  
                this.net_profit = this.gross_profit - this.total_costs;
                this.margin_p = this.revenue_total > 0 ? ((this.net_profit / this.revenue_total) * 100).toFixed(1) : 0;
            },

            formatNumber(n, decimals = 0) {
                return new Intl.NumberFormat('vi-VN', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                }).format(n);
            }
        }
    }

    function confirmAction(url, message) {
        if (confirm(message)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            
            document.body.appendChild(form);
            form.submit();
        }
    }

</script>

<style>
    .table-pnl-editor th {
        font-size: 10px;
        padding: 4px 2px;
        white-space: normal;
        line-height: 1.1;
        background-color: #facc15; /* Vibrant yellow-400 */
        color: #000;
        vertical-align: middle;
        text-transform: none;
    }
    .table-pnl-editor td {
        font-size: 11px;
        padding: 4px 6px;
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
