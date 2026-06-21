@extends('layouts.app')

@section('content')
    <div class="">
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('purchase-requests.index') }}" class="hover:text-teal-600">Danh sách PR</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="text-gray-800 font-medium">Gom đơn đặt hàng</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Danh sách cần đặt hàng (Aggregated)</h1>
            <p class="text-sm text-gray-600">Dữ liệu được gom theo Hãng và Sản phẩm từ các yêu cầu đang chờ xử lý</p>
        </div>

        <!-- Tabs Navigation -->
        @php
            $activeTab = request('tab', 'needs-ordering');
        @endphp
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button type="button" onclick="switchTab('needs-ordering')" id="tab-needs-ordering-btn"
                    class="{{ $activeTab === 'needs-ordering' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all">
                    <i class="fas fa-list mr-1.5"></i> Cần đặt hàng
                </button>
                <button type="button" onclick="switchTab('drafts')" id="tab-drafts-btn"
                    class="{{ $activeTab === 'drafts' ? 'border-teal-500 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-all">
                    <i class="fas fa-file-signature mr-1.5"></i> Danh sách nháp
                    @if($draftPos->count() > 0)
                        <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                            {{ $draftPos->count() }}
                        </span>
                    @endif
                </button>
            </nav>
        </div>

        <!-- Tab 1: Cần đặt hàng -->
        <div id="tab-needs-ordering" class="tab-content {{ $activeTab === 'needs-ordering' ? '' : 'hidden' }}">
            @if(empty($vendorGroups))
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                    <i class="fas fa-check-circle text-5xl mb-4 text-green-100"></i>
                    <p class="text-lg font-medium text-gray-600">Tuyệt vời! Hiện tại không còn mặt hàng nào cần đặt mới.</p>
                    <a href="{{ route('purchase-requests.index') }}" class="text-teal-600 hover:underline mt-2 inline-block">Quay lại danh sách PR</a>
                </div>
            @else
                <form action="{{ route('purchase-orders.store-from-pr') }}" method="POST" id="mainPoForm">
                    @csrf
                    <input type="hidden" name="vendor_id" id="selectedVendorId" value="">

                <div class="grid grid-cols-1 gap-8" x-data="{ expandedSo: null, currentVendor: null }">
                    @foreach($vendorGroups as $vId => $vendor)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden vendor-section"
                            data-vendor-id="{{ $vId }}">
                            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center font-bold">
                                        {{ substr($vendor['name'], 0, 1) }}
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-800">{{ $vendor['name'] }}</h2>
                                        <p class="text-xs text-gray-500">{{ count($vendor['sales_orders']) }} Sales Order cần xử lý
                                        </p>
                                    </div>
                                </div>
                                <button type="button" onclick="preparePo('{{ $vId }}')"
                                    class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors text-sm font-bold shadow-sm">
                                    <i class="fas fa-plus mr-2"></i> Tạo PO cho Hãng này
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr
                                            class="text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                                            <th class="px-6 py-3 w-10 text-center">
                                                <input type="checkbox"
                                                    class="rounded text-teal-600 focus:ring-teal-500 vendor-check-all"
                                                    data-vendor-id="{{ $vId }}">
                                            </th>
                                            <th class="px-6 py-3">Mã SO</th>
                                            <th class="px-6 py-3">Partner</th>
                                            <th class="px-6 py-3">End user</th>
                                            <th class="px-6 py-3 text-center">Total Giá nhập USD</th>
                                            <th class="px-6 py-3 text-center">Đã đặt</th>
                                            <th class="px-6 py-3 text-center">Còn thiếu</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($vendor['sales_orders'] as $soId => $so)
                                            @php
                                                $soRemaining = $so['requested'] - $so['ordered'];
                                            @endphp
                                            <tr class="hover:bg-teal-50/30 transition-colors cursor-pointer"
                                                @click="expandedSo === '{{ $soId }}' ? expandedSo = null : expandedSo = '{{ $soId }}'">
                                                <td class="px-6 py-4 text-center" @click.stop>
                                                    <input type="checkbox" class="rounded text-teal-600 focus:ring-teal-500 so-checkbox"
                                                        data-vendor-id="{{ $vId }}" data-so-id="{{ $soId }}">
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <i class="fas fa-chevron-right text-gray-400 text-[10px] transition-transform duration-200"
                                                            :class="expandedSo === '{{ $soId }}' ? 'rotate-90 text-teal-600' : ''"></i>
                                                        <div>
                                                            @if(!empty($so['sale_id']))
                                                                <a href="{{ route('sales.show', $so['sale_id']) }}" target="_blank" class="font-bold text-teal-600 hover:underline hover:text-teal-700" @click.stop>
                                                                    {{ $so['code'] }}
                                                                </a>
                                                            @else
                                                                <div class="font-bold text-gray-800">{{ $so['code'] }}</div>
                                                            @endif
                                                            <div class="text-[10px] text-gray-400">{{ $so['pr_code'] }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-700">
                                                    {{ $so['partner'] ?: '-' }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-700">
                                                    {{ $so['end_user'] ?: '-' }}
                                                </td>
                                                <td class="px-6 py-4 text-center font-bold text-teal-700">
                                                    ${{ number_format($so['total_usd'], 2) }}
                                                </td>
                                                <td class="px-6 py-4 text-center text-blue-600 text-sm">
                                                    {{ number_format($so['ordered'], 0) }}</td>
                                                <td class="px-6 py-4 text-center font-bold text-red-500 text-sm">
                                                    {{ number_format($soRemaining, 0) }}</td>
                                            </tr>
 
                                             <!-- Expandable Product Detail Row -->
                                            <tr x-show="expandedSo === '{{ $soId }}'" x-cloak class="bg-gray-50/50">
                                                <td colspan="7" class="px-8 py-4">
                                                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                                        <table class="w-full text-sm">
                                                            <thead class="bg-gray-100">
                                                                <tr
                                                                    class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                                                                    <th class="px-4 py-2 w-10 text-center"></th>
                                                                    <th class="px-4 py-2">Tên Part / Sản phẩm</th>
                                                                    <th class="px-4 py-2 text-center">Số lượng</th>
                                                                    <th class="px-4 py-2 text-center">Unit Price (USD)</th>
                                                                    <th class="px-4 py-2 text-center w-16"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100">
                                                                @foreach($so['products'] as $product)
                                                                    <tr class="hover:bg-teal-50/50 transition-colors">
                                                                        <td class="px-4 py-3 text-center">
                                                                            <input type="checkbox"
                                                                                class="rounded text-teal-600 focus:ring-teal-500 item-checkbox"
                                                                                data-vendor-id="{{ $vId }}" data-so-id="{{ $soId }}"
                                                                                data-product-id="{{ $product['id'] }}">
                                                                        </td>
                                                                        <td class="px-4 py-3">
                                                                            <div class="font-medium text-gray-800">
                                                                                {{ $product['part_number'] }}</div>
                                                                            <div class="text-[10px] text-gray-400">
                                                                                {{ $product['unit'] ?: '-' }}</div>
                                                                        </td>
                                                                        <td class="px-4 py-3 text-center">
                                                                            {{ number_format($product['requested'], 0) }}</td>
                                                                        <td class="px-4 py-3 text-center text-gray-500">
                                                                            ${{ number_format($product['unit_price_usd'], 2) }}
                                                                            <input type="number" name="items_data[{{ $product['id'] }}]"
                                                                                value="{{ $product['remaining'] }}"
                                                                                class="order-qty-input hidden" disabled
                                                                                data-pr-item-id="{{ $product['id'] }}">
                                                                        </td>
                                                                        <td class="px-4 py-3 text-center">
                                                                            <button type="button"
                                                                                onclick="cancelPrItem({{ $product['id'] }}, '{{ addslashes($product['part_number']) }}')"
                                                                                class="text-red-400 hover:text-red-600 transition-colors"
                                                                                title="Hủy sản phẩm">
                                                                                <i class="fas fa-times-circle text-sm"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>

                                                        {{-- PR Notes and Attachments --}}
                                                        <div class="bg-gray-50 p-4 border-t border-gray-100 flex flex-col md:flex-row gap-6 text-xs">
                                                            <div class="flex-1">
                                                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Ghi chú yêu cầu (PR)</h4>
                                                                @if(!empty($so['note']))
                                                                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $so['note'] }}</p>
                                                                @else
                                                                    <p class="text-gray-400 italic">Không có ghi chú</p>
                                                                @endif
                                                            </div>
                                                            <div class="w-full md:w-80 shrink-0">
                                                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Tài liệu PR đính kèm</h4>
                                                                @if(isset($so['attachments']) && $so['attachments']->count() > 0)
                                                                    <div class="space-y-1.5">
                                                                        @foreach($so['attachments'] as $att)
                                                                            <div class="flex items-center justify-between bg-white px-2.5 py-1.5 rounded border border-gray-200 text-xs">
                                                                                <div class="flex items-center gap-2 overflow-hidden">
                                                                                    <i class="{{ $att->file_icon }} text-sm"></i>
                                                                                    <span class="truncate font-medium text-gray-700" title="{{ $att->file_name }}">{{ $att->file_name }}</span>
                                                                                </div>
                                                                                @if(!empty($so['sale_id']))
                                                                                    <a href="{{ route('sales.order-request.attachment.download', [$so['sale_id'], $att->id]) }}" 
                                                                                       class="text-teal-600 hover:text-teal-700 font-bold shrink-0 ml-2" @click.stop>
                                                                                        Tải về
                                                                                    </a>
                                                                                @endif
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <p class="text-gray-400 italic">Không có tệp đính kèm</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Global Note & Submit (Floating bottom bar?) -->
                <div id="submitBar"
                    class="hidden fixed bottom-6 left-1/2 transform -translate-x-1/2 w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-teal-100 p-5 z-40 transition-all duration-300">
                    <div class="space-y-4">
                        <!-- Dòng 1: CPQ Đơn hàng -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">CPQ đơn hàng <span class="text-red-500">*</span></label>
                            <input type="text" name="cpq_number" id="mainCpqInput" value=""
                                class="w-full border-gray-300 rounded-lg text-sm px-4 py-2.5 focus:ring-teal-500 focus:border-teal-500"
                                placeholder="CPQ/non">
                        </div>

                        <!-- Dòng 2: Ghi chú -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Ghi chú cho Đơn hàng
                                (PO)</label>
                            <input type="text" name="note"
                                class="w-full border-gray-300 rounded-lg text-sm px-4 py-2.5 focus:ring-teal-500 focus:border-teal-500"
                                placeholder="Nhập ghi chú chung cho PO này...">
                        </div>

                        <input type="hidden" name="currency_id" value="{{ $baseCurrencyId }}">
                        <input type="hidden" name="exchange_rate" value="1">

                        <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                            <div class="text-left">
                                <p class="text-xs text-gray-400 font-medium">Đang chọn <span id="selectedCount"
                                        class="font-bold text-teal-600">0</span> mặt hàng</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" onclick="resetSelections()"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-50 transition-colors">
                                    Hủy chọn
                                </button>
                                <button type="button" onclick="submitDraft()"
                                    class="bg-amber-500 text-white px-6 py-2.5 rounded-lg hover:bg-amber-600 font-bold shadow-lg transition-all transform hover:scale-[1.02] active:scale-95 whitespace-nowrap flex items-center gap-1.5">
                                    <i class="fas fa-file-signature text-xs"></i> GOM NHÁP (DRAFT)
                                </button>
                                <button type="button" onclick="submitPo()"
                                    class="bg-teal-600 text-white px-6 py-2.5 rounded-lg hover:bg-teal-700 font-bold shadow-lg transition-all transform hover:scale-[1.02] active:scale-95 whitespace-nowrap flex items-center gap-1.5">
                                    <i class="fas fa-check-circle text-xs"></i> XÁC NHẬN TẠO PO
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif

        {{-- Danh sách sản phẩm đã hủy --}}
        @if(isset($cancelledItems) && $cancelledItems->count() > 0)
            <div class="mt-8" x-data="{ showCancelled: false }">
                <button type="button" @click="showCancelled = !showCancelled"
                    class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-3">
                    <i class="fas fa-chevron-right text-[10px] transition-transform duration-200"
                        :class="showCancelled ? 'rotate-90' : ''"></i>
                    <i class="fas fa-ban text-red-400"></i>
                    Sản phẩm đã hủy ({{ $cancelledItems->count() }})
                </button>
                <div x-show="showCancelled" x-cloak class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden">
                    <div class="bg-red-50 px-6 py-3 border-b border-red-100">
                        <h3 class="text-sm font-bold text-red-700">
                            <i class="fas fa-ban mr-2"></i>Sản phẩm đã hủy — không tham gia đặt hàng
                        </h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                <th class="px-6 py-2">Sản phẩm</th>
                                <th class="px-6 py-2">Hãng</th>
                                <th class="px-6 py-2">Mã SO</th>
                                <th class="px-6 py-2 text-center">Số lượng</th>
                                <th class="px-6 py-2 text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($cancelledItems as $ci)
                                <tr class="hover:bg-gray-50 text-gray-400">
                                    <td class="px-6 py-3">
                                        <span class="line-through">{{ $ci->part_number }}</span>
                                        <span class="ml-2 px-1.5 py-0.5 text-[9px] font-bold bg-red-100 text-red-600 rounded">ĐÃ
                                            HỦY</span>
                                    </td>
                                    <td class="px-6 py-3">{{ $ci->vendor?->name ?? $ci->vendor }}</td>
                                    <td class="px-6 py-3">
                                        {{ $ci->saleOrderRequest?->sale?->code ?? $ci->saleOrderRequest?->code ?? 'N/A' }}</td>
                                    <td class="px-6 py-3 text-center">{{ $ci->quantity + 0 }}</td>
                                    <td class="px-6 py-3 text-center">
                                        <form action="{{ route('purchase-requests.items.restore', $ci->id) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            <button type="submit"
                                                onclick="return confirm('Khôi phục sản phẩm này về danh sách cần đặt?')"
                                                class="text-teal-500 hover:text-teal-700 transition-colors text-xs font-bold">
                                                <i class="fas fa-undo mr-1"></i>Khôi phục
                                             </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div> <!-- End tab-needs-ordering -->

    <!-- Tab 2: Danh sách nháp -->
    <div id="tab-drafts" class="tab-content {{ $activeTab === 'drafts' ? '' : 'hidden' }}">
        @if($draftPos->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                <i class="fas fa-file-invoice text-5xl mb-4 text-gray-200"></i>
                <p class="text-lg font-medium text-gray-600">Hiện tại không có đơn hàng nháp nào.</p>
            </div>
        @else
                <div class="grid grid-cols-1 gap-8">
                    @foreach($draftPos as $po)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden draft-po-card transition-all duration-200"
                            data-draft-id="{{ $po->id }}" data-supplier-id="{{ $po->supplier_id }}"
                            x-data="{ expanded: false }">
                            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center flex-wrap gap-4 cursor-pointer select-none hover:bg-gray-100/70 transition-colors"
                                @click="expanded = !expanded">
                                <div class="flex items-center gap-3">
                                    <!-- Checkbox để chọn gộp -->
                                    <div @click.stop class="flex items-center">
                                        <input type="checkbox" class="draft-checkbox rounded text-amber-500 focus:ring-amber-400 w-5 h-5 cursor-pointer"
                                            data-draft-id="{{ $po->id }}" data-supplier-id="{{ $po->supplier_id }}"
                                            onclick="event.stopPropagation(); updateDraftSelections();">
                                    </div>
                                    <i class="fas fa-chevron-right text-gray-400 text-xs transition-transform duration-200"
                                        :class="expanded ? 'rotate-90 text-amber-500' : ''"></i>
                                    <div class="w-10 h-10 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center font-bold shrink-0">
                                        {{ substr($po->supplier->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h2 class="text-lg font-bold text-gray-800">{{ $po->supplier->name }}</h2>
                                            <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Đơn nháp</span>
                                            
                                            <!-- Summary badge when collapsed -->
                                            <span x-show="!expanded" x-cloak class="bg-teal-50 text-teal-800 border border-teal-200 text-[11px] font-semibold px-2 py-0.5 rounded-lg flex items-center gap-1 transition-all">
                                                <i class="fas fa-box text-[9px]"></i> {{ $po->items->count() }} sản phẩm | ${{ number_format($po->total_foreign ?: $po->subtotal, 2) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500">Mã tạm: <span class="font-semibold">{{ $po->code }}</span> | Người tạo: {{ $po->creator->name ?? 'N/A' }} | Ngày: {{ $po->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2" @click.stop>
                                    <button type="button" 
                                        onclick="openConfirmDraftModal('{{ $po->id }}', '{{ $po->code }}', '{{ $po->cpq_number }}', '{{ addslashes($po->note) }}')"
                                        class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors text-sm font-bold shadow-sm flex items-center gap-1.5">
                                        <i class="fas fa-check-circle text-xs"></i> Xác nhận tạo PO
                                    </button>
                                    <form action="{{ route('purchase-orders.draft.destroy', $po->id) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa toàn bộ đơn hàng nháp này? Tất cả mặt hàng sẽ quay lại danh sách cần đặt.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                            class="bg-red-50 text-red-600 border border-red-200 px-3 py-2 rounded-lg hover:bg-red-100 transition-colors text-sm font-bold shadow-sm">
                                            <i class="fas fa-trash-alt"></i> Xóa nháp
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Collapsible Content -->
                            <div x-show="expanded" x-cloak>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-sm">
                                        <thead class="bg-gray-50">
                                            <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                                                <th class="px-6 py-3">Sản phẩm / Part Number</th>
                                                <th class="px-6 py-3">Mã SO</th>
                                                <th class="px-6 py-3">Khách hàng / Partner</th>
                                                <th class="px-6 py-3 text-center">Số lượng</th>
                                                <th class="px-6 py-3 text-right">Đơn giá (USD)</th>
                                                <th class="px-6 py-3 text-right">Thành tiền (USD)</th>
                                                <th class="px-6 py-3 w-16 text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @foreach($po->items as $item)
                                                @php
                                                    $prItem = $item->saleOrderRequestItem;
                                                    $pr = $prItem?->saleOrderRequest;
                                                    $displayCode = ($pr?->sale && $pr->sale->code) ? $pr->sale->code : ($pr?->code ?? 'N/A');
                                                    $partnerName = $pr?->sale?->customer_name ?: ($prItem?->si_name ?: '-');
                                                @endphp
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-6 py-4">
                                                        <div class="font-medium text-gray-800">{{ $item->product_name }}</div>
                                                        <div class="text-[10px] text-gray-400">Đơn vị: {{ $item->unit }}</div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        @if($pr?->sale_id)
                                                            <a href="{{ route('sales.show', $pr->sale_id) }}" target="_blank" class="font-bold text-teal-600 hover:underline">
                                                                {{ $displayCode }}
                                                            </a>
                                                        @else
                                                            <span class="text-gray-800">{{ $displayCode }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 text-gray-600 text-xs">
                                                        {{ $partnerName }}
                                                    </td>
                                                    <td class="px-6 py-4 text-center font-semibold">
                                                        {{ number_format($item->quantity, 0) }}
                                                    </td>
                                                    <td class="px-6 py-4 text-right text-gray-500 font-mono">
                                                        ${{ number_format($item->unit_price, 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 text-right text-teal-700 font-bold font-mono">
                                                        ${{ number_format($item->total, 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 text-center">
                                                        <form action="{{ route('purchase-orders.draft-items.destroy', $item->id) }}" method="POST" class="inline"
                                                            onsubmit="return confirm('Xóa mặt hàng này khỏi đơn nháp? Nó sẽ quay lại danh sách cần đặt.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-400 hover:text-red-600 transition-colors" title="Xóa mặt hàng">
                                                                <i class="fas fa-times-circle text-lg"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <!-- Total Row -->
                                            <tr class="bg-teal-50/20 font-bold border-t border-gray-100">
                                                <td colspan="3" class="px-6 py-4 text-right text-gray-500 text-xs">TỔNG ĐƠN HÀNG:</td>
                                                <td class="px-6 py-4 text-center text-gray-800">{{ number_format($po->items->sum('quantity'), 0) }}</td>
                                                <td></td>
                                                <td class="px-6 py-4 text-right text-teal-700 font-mono text-base">${{ number_format($po->total_foreign ?: $po->subtotal, 2) }}</td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                @if($po->note)
                                    <div class="bg-amber-50/50 p-4 border-t border-gray-100 text-xs flex gap-2">
                                        <span class="font-bold text-amber-700 shrink-0"><i class="fas fa-info-circle"></i> Ghi chú gom nháp:</span>
                                        <span class="text-gray-700 whitespace-pre-line">{{ $po->note }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Floating Action Bar for Drafts (merge) -->
                <div id="draftActionBar"
                    class="hidden fixed bottom-6 left-1/2 transform -translate-x-1/2 w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-amber-200 p-5 z-40 transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div class="text-left">
                            <p class="text-sm text-gray-700 font-medium">
                                Đang chọn <span id="draftSelectedCount" class="font-bold text-amber-600">0</span> đơn nháp
                                <span id="draftSelectedVendor" class="text-xs text-gray-400 ml-2"></span>
                            </p>
                            <p id="draftMergeWarning" class="text-xs text-red-500 mt-1 hidden">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Chỉ có thể gộp các đơn nháp cùng một Hãng!
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="resetDraftSelections()"
                                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-50 transition-colors">
                                Bỏ chọn
                            </button>
                            <button type="button" id="mergeDraftBtn" onclick="submitMergeDrafts()" disabled
                                class="bg-amber-500 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg transition-all transform hover:scale-[1.02] active:scale-95 whitespace-nowrap flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                <i class="fas fa-object-group text-xs"></i> GỘP ĐƠN NHÁP
                            </button>
                        </div>
                    </div>
                </div>
        @endif
    </div>

    <!-- Confirm Draft PO Modal -->
    <div id="confirmDraftModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Xác nhận tạo PO <span id="confirmDraftPoCode" class="text-teal-600"></span></h3>
                <button type="button" onclick="closeConfirmDraftModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="confirmDraftForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">CPQ đơn hàng <span class="text-red-500">*</span></label>
                    <input type="text" name="cpq_number" id="draftCpqInput" required
                        class="w-full border-gray-300 rounded-lg text-sm px-4 py-2.5 focus:ring-teal-500 focus:border-teal-500"
                        placeholder="CPQ/non">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ghi chú cho Đơn hàng (PO)</label>
                    <textarea name="note" id="draftNoteTextarea" rows="3"
                        class="w-full border-gray-300 rounded-lg text-sm px-4 py-2.5 focus:ring-teal-500 focus:border-teal-500"
                        placeholder="Nhập ghi chú chung cho PO này..."></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeConfirmDraftModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg">Hủy</button>
                    <button type="submit"
                        class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-bold shadow-md">Xác nhận tạo PO</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            let currentVendorId = null;

            // Sử dụng Event Delegation để xử lý tất cả checkbox
            document.addEventListener('change', async function (e) {
                const cb = e.target;
                if (!cb.classList.contains('item-checkbox') &&
                    !cb.classList.contains('so-checkbox') &&
                    !cb.classList.contains('vendor-check-all')) return;

                const vId = cb.dataset.vendorId;

                // Nếu chuyển sang Vendor khác khi đang có lựa chọn
                if (currentVendorId && currentVendorId !== vId) {
                    const hasSelections = document.querySelectorAll('.item-checkbox:checked').length > 0;
                    if (hasSelections) {
                        // Tạm thời đảo ngược lại trạng thái checkbox vừa click
                        const newState = cb.checked;
                        cb.checked = !newState;

                        const result = await Swal.fire({
                            title: 'Chuyển đổi Hãng',
                            text: 'Bạn đang chọn mặt hàng từ Hãng khác. Chuyển sang Hãng này sẽ bỏ chọn các mặt hàng cũ?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#0d9488',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Đồng ý chuyển',
                            cancelButtonText: 'Hủy',
                            reverseButtons: true
                        });

                        if (result.isConfirmed) {
                            resetSelections();
                            currentVendorId = vId;
                            document.getElementById('selectedVendorId').value = vId;
                            cb.checked = newState; // Khôi phục lại trạng thái mong muốn
                            // Tiếp tục xử lý logic bên dưới
                        } else {
                            // Giữ nguyên Hãng cũ, checkbox đã được đảo ngược ở trên rồi
                            updateSubmitBar(); // Cập nhật lại thanh bar nếu cần
                            return;
                        }
                    }
                }

                currentVendorId = vId;
                document.getElementById('selectedVendorId').value = vId;

                // Xử lý logic riêng cho từng loại checkbox
                if (cb.classList.contains('item-checkbox')) {
                    const soId = cb.dataset.soId;
                    toggleItemSelection(cb);
                    updateSoCheckbox(vId, soId);
                    updateVendorCheckbox(vId);
                }
                else if (cb.classList.contains('so-checkbox')) {
                    const soId = cb.dataset.soId;
                    const itemCbs = document.querySelectorAll(`.item-checkbox[data-vendor-id="${vId}"][data-so-id="${soId}"]`);
                    itemCbs.forEach(itemCb => {
                        itemCb.checked = cb.checked;
                        toggleItemSelection(itemCb);
                    });
                    updateVendorCheckbox(vId);
                }
                else if (cb.classList.contains('vendor-check-all')) {
                    // Check all SOs
                    document.querySelectorAll(`.so-checkbox[data-vendor-id="${vId}"]`).forEach(soCb => soCb.checked = cb.checked);
                    // Check all Items
                    document.querySelectorAll(`.item-checkbox[data-vendor-id="${vId}"]`).forEach(itemCb => {
                        itemCb.checked = cb.checked;
                        toggleItemSelection(itemCb);
                    });
                }

                updateSubmitBar();
            });

            // Ngăn chặn sự kiện click lan ra ngoài (gây đóng mở SO row)
            document.addEventListener('click', function (e) {
                if (e.target.matches('.item-checkbox, .so-checkbox, .vendor-check-all')) {
                    e.stopPropagation();
                }
            }, true);

            function toggleItemSelection(cb) {
                const row = cb.closest('tr');
                if (!row) return;
                const input = row.querySelector('.order-qty-input');
                if (input) {
                    input.disabled = !cb.checked;
                    input.classList.toggle('opacity-50', !cb.checked);
                }
            }

            function updateSoCheckbox(vId, soId) {
                const items = document.querySelectorAll(`.item-checkbox[data-vendor-id="${vId}"][data-so-id="${soId}"]`);
                const checkedItems = document.querySelectorAll(`.item-checkbox[data-vendor-id="${vId}"][data-so-id="${soId}"]:checked`);
                const soCb = document.querySelector(`.so-checkbox[data-vendor-id="${vId}"][data-so-id="${soId}"]`);

                if (soCb) {
                    soCb.checked = items.length === checkedItems.length;
                    soCb.indeterminate = checkedItems.length > 0 && checkedItems.length < items.length;
                }
            }

            function updateVendorCheckbox(vId) {
                const soCbs = document.querySelectorAll(`.so-checkbox[data-vendor-id="${vId}"]`);
                const checkedSoCbs = document.querySelectorAll(`.so-checkbox[data-vendor-id="${vId}"]:checked`);
                const vendorCb = document.querySelector(`.vendor-check-all[data-vendor-id="${vId}"]`);

                if (vendorCb) {
                    vendorCb.checked = soCbs.length === checkedSoCbs.length;
                    vendorCb.indeterminate = checkedSoCbs.length > 0 && checkedSoCbs.length < soCbs.length;
                }
            }

            function resetSelections() {
                document.querySelectorAll('.item-checkbox, .so-checkbox, .vendor-check-all').forEach(cb => {
                    cb.checked = false;
                    cb.indeterminate = false;
                });
                document.querySelectorAll('.order-qty-input').forEach(input => {
                    input.disabled = true;
                    input.classList.add('opacity-50');
                });
                currentVendorId = null;
                updateSubmitBar();
            }

            function updateSubmitBar() {
                const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
                const bar = document.getElementById('submitBar');
                const countSpan = document.getElementById('selectedCount');

                if (checkedCount > 0) {
                    bar.classList.remove('hidden');
                    if (countSpan) countSpan.innerText = checkedCount;
                } else {
                    bar.classList.add('hidden');
                    currentVendorId = null;
                }
            }

            function submitPo() {
                const form = document.getElementById('mainPoForm');
                const btn = document.querySelector('button[onclick="submitPo()"]');
                
                const cpqInput = form.querySelector('input[name="cpq_number"]');
                if (cpqInput && !cpqInput.value.trim()) {
                    Swal.fire({
                        title: 'Thiếu thông tin',
                        text: 'CPQ đơn hàng bắt buộc điền, không được để trống.',
                        icon: 'warning',
                        confirmButtonColor: '#0d9488'
                    });
                    cpqInput.focus();
                    return;
                }

                const items = document.querySelectorAll('.order-qty-input:not(:disabled)');

                const vId = document.getElementById('selectedVendorId').value;
                if (!vId || isNaN(vId)) {
                    Swal.fire({
                        title: 'Lỗi Nhà cung cấp',
                        text: 'Hãng này chưa được liên kết với Nhà cung cấp trong hệ thống. Vui lòng kiểm tra lại dữ liệu.',
                        icon: 'error',
                        confirmButtonColor: '#0d9488'
                    });
                    return;
                }

                form.querySelectorAll('.temp-input').forEach(i => i.remove());

                let idx = 0;
                items.forEach(input => {
                    const val = input.value;
                    const prItemId = input.dataset.prItemId;

                    if (val > 0) {
                        const hiddenId = document.createElement('input');
                        hiddenId.type = 'hidden';
                        hiddenId.name = `items[${idx}][pr_item_id]`;
                        hiddenId.value = prItemId;
                        hiddenId.className = 'temp-input';
                        form.appendChild(hiddenId);

                        const hiddenQty = document.createElement('input');
                        hiddenQty.type = 'hidden';
                        hiddenQty.name = `items[${idx}][quantity]`;
                        hiddenQty.value = val;
                        hiddenQty.className = 'temp-input';
                        form.appendChild(hiddenQty);

                        idx++;
                    }
                });

                if (idx === 0) {
                    Swal.fire({
                        title: 'Chưa chọn mặt hàng',
                        text: 'Vui lòng chọn ít nhất một mặt hàng để tạo đơn hàng.',
                        icon: 'warning',
                        confirmButtonColor: '#0d9488'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Xác nhận tạo PO',
                    text: `Bạn có chắc chắn muốn tạo Đơn đặt hàng (PO) cho ${idx} mặt hàng đã chọn?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d9488', // teal-600
                    cancelButtonColor: '#6b7280', // gray-500
                    confirmButtonText: 'Đồng ý, tạo PO',
                    cancelButtonText: 'Hủy',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Tắt cảnh báo thay đổi chưa lưu
                        window.formChanged = false;

                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> ĐANG XỬ LÝ...';
                        form.action = "{{ route('purchase-orders.store-from-pr') }}";
                        HTMLFormElement.prototype.submit.call(form);
                    }
                });
            }

            function submitDraft() {
                const form = document.getElementById('mainPoForm');
                const items = document.querySelectorAll('.order-qty-input:not(:disabled)');

                const vId = document.getElementById('selectedVendorId').value;
                if (!vId || isNaN(vId)) {
                    Swal.fire({
                        title: 'Lỗi Nhà cung cấp',
                        text: 'Hãng này chưa được liên kết với Nhà cung cấp trong hệ thống. Vui lòng kiểm tra lại dữ liệu.',
                        icon: 'error',
                        confirmButtonColor: '#0d9488'
                    });
                    return;
                }

                form.querySelectorAll('.temp-input').forEach(i => i.remove());

                let idx = 0;
                items.forEach(input => {
                    const val = input.value;
                    const prItemId = input.dataset.prItemId;

                    if (val > 0) {
                        const hiddenId = document.createElement('input');
                        hiddenId.type = 'hidden';
                        hiddenId.name = `items[${idx}][pr_item_id]`;
                        hiddenId.value = prItemId;
                        hiddenId.className = 'temp-input';
                        form.appendChild(hiddenId);

                        const hiddenQty = document.createElement('input');
                        hiddenQty.type = 'hidden';
                        hiddenQty.name = `items[${idx}][quantity]`;
                        hiddenQty.value = val;
                        hiddenQty.className = 'temp-input';
                        form.appendChild(hiddenQty);

                        idx++;
                    }
                });

                if (idx === 0) {
                    Swal.fire({
                        title: 'Chưa chọn mặt hàng',
                        text: 'Vui lòng chọn ít nhất một mặt hàng để gom nháp.',
                        icon: 'warning',
                        confirmButtonColor: '#f59e0b'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Gom đơn nháp',
                    text: `Bạn có chắc chắn muốn gom ${idx} mặt hàng đã chọn vào danh sách nháp của Hãng này?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Đồng ý, Gom nháp',
                    cancelButtonText: 'Hủy',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.formChanged = false;
                        form.action = "{{ route('purchase-orders.store-draft-from-pr') }}";
                        HTMLFormElement.prototype.submit.call(form);
                    }
                });
            }

            function switchTab(tabName) {
                document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
                document.getElementById('tab-' + tabName).classList.remove('hidden');

                const activeClass = ['border-teal-500', 'text-teal-600'];
                const inactiveClass = ['border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300'];

                const needsBtn = document.getElementById('tab-needs-ordering-btn');
                const draftsBtn = document.getElementById('tab-drafts-btn');

                if (tabName === 'needs-ordering') {
                    needsBtn.classList.remove(...inactiveClass);
                    needsBtn.classList.add(...activeClass);
                    draftsBtn.classList.remove(...activeClass);
                    draftsBtn.classList.add(...inactiveClass);
                } else {
                    draftsBtn.classList.remove(...inactiveClass);
                    draftsBtn.classList.add(...activeClass);
                    needsBtn.classList.remove(...activeClass);
                    needsBtn.classList.add(...inactiveClass);
                }

                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);
            }

            function openConfirmDraftModal(poId, code, currentCpq, currentNote) {
                document.getElementById('confirmDraftPoCode').innerText = code;
                document.getElementById('draftCpqInput').value = currentCpq === 'CPQ/draft' ? '' : currentCpq;
                document.getElementById('draftNoteTextarea').value = currentNote || '';
                document.getElementById('confirmDraftForm').action = `/purchase-orders/draft/${poId}/confirm`;
                document.getElementById('confirmDraftModal').classList.remove('hidden');
            }

            function closeConfirmDraftModal() {
                document.getElementById('confirmDraftModal').classList.add('hidden');
            }

            async function handleVendorSwitch(vId) {
                if (currentVendorId && currentVendorId !== vId) {
                    const hasSelections = document.querySelectorAll('.item-checkbox:checked').length > 0;
                    if (hasSelections) {
                        const result = await Swal.fire({
                            title: 'Chuyển đổi Hãng',
                            text: 'Bạn đang chọn mặt hàng từ Hãng khác. Chuyển sang Hãng này sẽ bỏ chọn các mặt hàng cũ?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#0d9488',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Đồng ý chuyển',
                            cancelButtonText: 'Hủy',
                            reverseButtons: true
                        });

                        if (result.isConfirmed) {
                            resetSelections();
                            currentVendorId = vId;
                            document.getElementById('selectedVendorId').value = vId;
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
                currentVendorId = vId;
                document.getElementById('selectedVendorId').value = vId;
                return true;
            }

            async function preparePo(vId) {
                if (await handleVendorSwitch(vId)) {
                    const checkAll = document.querySelector(`.vendor-check-all[data-vendor-id="${vId}"]`);
                    if (checkAll) {
                        checkAll.checked = true;
                        // Manual trigger of the click logic since dispatching click is tricky with async
                        // Check all SOs
                        document.querySelectorAll(`.so-checkbox[data-vendor-id="${vId}"]`).forEach(soCb => soCb.checked = true);
                        // Check all Items
                        document.querySelectorAll(`.item-checkbox[data-vendor-id="${vId}"]`).forEach(itemCb => {
                            itemCb.checked = true;
                            toggleItemSelection(itemCb);
                        });
                        updateSubmitBar();
                        document.getElementById('submitBar').scrollIntoView({ behavior: 'smooth' });
                    }
                }
            }

            function cancelPrItem(itemId, partNumber) {
                Swal.fire({
                    title: 'Hủy sản phẩm',
                    html: `Bạn có chắc chắn muốn hủy sản phẩm <strong>${partNumber}</strong>?<br><small class="text-gray-500">Sản phẩm sẽ được trả về bước Duyệt yêu cầu PR.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Xác nhận hủy',
                    cancelButtonText: 'Giữ lại',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/purchase-requests/items/${itemId}/cancel`;

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }

            // ==========================================
            // DRAFT TAB: Selection & Merge Logic
            // ==========================================
            function updateDraftSelections() {
                const checkedBoxes = document.querySelectorAll('.draft-checkbox:checked');
                const count = checkedBoxes.length;
                const bar = document.getElementById('draftActionBar');
                const countSpan = document.getElementById('draftSelectedCount');
                const vendorSpan = document.getElementById('draftSelectedVendor');
                const warningEl = document.getElementById('draftMergeWarning');
                const mergeBtn = document.getElementById('mergeDraftBtn');

                if (count > 0) {
                    bar.classList.remove('hidden');
                    countSpan.innerText = count;

                    // Check if all selected are same supplier
                    const supplierIds = new Set();
                    checkedBoxes.forEach(cb => supplierIds.add(cb.dataset.supplierId));
                    
                    const isSameVendor = supplierIds.size === 1;

                    if (isSameVendor) {
                        // Get vendor name from the card
                        const firstCard = document.querySelector(`.draft-po-card[data-supplier-id="${[...supplierIds][0]}"]`);
                        const vendorName = firstCard ? firstCard.querySelector('h2')?.innerText : '';
                        vendorSpan.innerText = `(${vendorName})`;
                        warningEl.classList.add('hidden');
                        mergeBtn.disabled = count < 2;
                    } else {
                        vendorSpan.innerText = '(nhiều hãng)';
                        warningEl.classList.remove('hidden');
                        mergeBtn.disabled = true;
                    }
                } else {
                    bar.classList.add('hidden');
                }

                // Visual highlight for selected cards
                document.querySelectorAll('.draft-po-card').forEach(card => {
                    const cb = card.querySelector('.draft-checkbox');
                    if (cb && cb.checked) {
                        card.classList.add('ring-2', 'ring-amber-400', 'border-amber-400');
                    } else {
                        card.classList.remove('ring-2', 'ring-amber-400', 'border-amber-400');
                    }
                });
            }

            function resetDraftSelections() {
                document.querySelectorAll('.draft-checkbox').forEach(cb => cb.checked = false);
                updateDraftSelections();
            }

            function submitMergeDrafts() {
                const checkedBoxes = document.querySelectorAll('.draft-checkbox:checked');
                if (checkedBoxes.length < 2) return;

                // Verify same supplier
                const supplierIds = new Set();
                checkedBoxes.forEach(cb => supplierIds.add(cb.dataset.supplierId));
                if (supplierIds.size > 1) {
                    Swal.fire({
                        title: 'Không thể gộp',
                        text: 'Chỉ có thể gộp các đơn nháp cùng một Hãng!',
                        icon: 'error',
                        confirmButtonColor: '#f59e0b'
                    });
                    return;
                }

                // Collect draft IDs
                const draftIds = [...checkedBoxes].map(cb => cb.dataset.draftId);

                Swal.fire({
                    title: 'Gộp đơn nháp',
                    text: `Bạn có chắc chắn muốn gộp ${draftIds.length} đơn nháp đã chọn thành 1 đơn nháp duy nhất?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Đồng ý, Gộp',
                    cancelButtonText: 'Hủy',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route("purchase-orders.draft.merge") }}';

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);

                        draftIds.forEach((id, idx) => {
                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = `draft_ids[${idx}]`;
                            hidden.value = id;
                            form.appendChild(hidden);
                        });

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        </script>
    @endpush

    <style>
        .vendor-section:has(.item-checkbox:checked) {
            border-color: #0d9488;
            ring: 2px;
            ring-color: #0d9488;
        }
        .draft-po-card.ring-2 {
            box-shadow: 0 0 0 2px #fbbf24;
        }
    </style>
@endsection