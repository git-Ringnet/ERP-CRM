{{-- Order Request Modal --}}
<div id="orderRequestModal"
    class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col">
        {{-- Header --}}
        <div
            class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-cyan-50 rounded-t-xl flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-cart-plus text-emerald-600 mr-2"></i>Form Yêu cầu đặt hàng
                </h3>
                <p class="text-sm text-gray-500 mt-0.5">Đơn hàng: <span
                        class="font-bold text-emerald-700">{{ $sale->code }}</span></p>
            </div>
            <button onclick="closeOrderRequestModal()" class="text-gray-400 hover:text-gray-600 text-xl">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Body --}}
        <form action="{{ route('sales.order-request.store', $sale->id) }}" method="POST" enctype="multipart/form-data"
            id="orderRequestForm">
            @csrf
            <div class="overflow-y-auto flex-1 px-6 py-4 space-y-4" style="max-height: calc(90vh - 160px);">

                {{-- Global Vendor/Type Selector Panel --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-emerald-50/50 p-3 rounded-lg border border-emerald-100 items-end">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Vendor (Chung)</label>
                        <select id="global_vendor_id_modal" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white">
                            <option value="">-- Chọn Vendor --</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Type (Chung)</label>
                        <select id="global_type_modal" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white">
                            <option value="">-- Chọn Type --</option>
                            @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="button" onclick="applyGlobalVendorType('modal')"
                            class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm flex items-center justify-center gap-1.5">
                            <i class="fas fa-check-double"></i> Áp dụng cho tất cả hàng
                        </button>
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 flex items-center justify-between">
                        <span class="text-sm font-bold text-gray-700">
                            <i class="fas fa-list mr-1"></i> Chi tiết sản phẩm cần đặt
                        </span>
                        <div class="flex gap-2">
                            <button type="button" onclick="importFromItems()"
                                class="text-xs px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                <i class="fas fa-sync mr-1"></i> Lấy từ sản phẩm đơn hàng
                            </button>
                            <button type="button" onclick="addOrderRequestRow()"
                                class="text-xs px-3 py-1.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors">
                                <i class="fas fa-plus mr-1"></i> Thêm dòng
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="orderRequestTable">
                            <thead class="bg-yellow-200">
                                <tr class="text-xs border-b border-gray-300">
                                    <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[140px] align-middle">
                                    Vendor <span class="text-red-500">*</span></th>
                                    <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[100px] align-middle">Type
                                        <span class="text-red-500">*</span></th>
                                    <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[160px] align-middle">Part
                                        Number <span class="text-red-500">*</span></th>
                                    <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[60px] align-middle">Qty
                                        <span class="text-red-500">*</span></th>
                                    <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[60px] align-middle">Unit
                                    </th>
                                    <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[100px] align-middle">SN
                                    </th>
                                    <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[120px] align-middle">Exp
                                        date</th>
                                    <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[120px] align-middle">SI
                                        Name <span class="text-red-500">*</span></th>
                                    <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[110px] align-middle">POS ID</th>
                                    <th colspan="3" class="px-2 py-1.5 text-center font-bold text-gray-800 border-b border-r border-gray-300">
                                        Thông tin CQ (Điền tay)</th>
                                    <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 w-10 align-middle"></th>
                                </tr>
                                <tr class="bg-yellow-200 text-xs border-b border-gray-300">
                                    <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px]">EU Name <span class="text-red-500">*</span></th>
                                    <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[100px]">MST <span class="text-red-500">*</span></th>
                                    <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px]">Address</th>
                                </tr>
                            </thead>
                            <tbody id="orderRequestRows">
                                <tr class="order-request-row border-b border-gray-100 hover:bg-gray-50" data-index="0">
                                    <td class="px-1 py-1.5">
                                        <select name="order_request_items[0][vendor_id]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                            <option value="">-- Chọn --</option>
                                            @foreach($suppliers as $s)
                                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <select name="order_request_items[0][type]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                            <option value="">-- Chọn --</option>
                                            @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                                <option value="{{ $t }}">{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][part_number]" required placeholder="P/N" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="number" name="order_request_items[0][quantity]" required step="0.01" value="1" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 text-center">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][unit]" placeholder="Đơn vị" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][serial_number]" placeholder="SN" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][exp_date]" placeholder="YYYY-MM-DD" class="exp-date-picker w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][si_name]" required placeholder="SI Name" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][pos_id]" placeholder="POS ID" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][eu_name]" required placeholder="EU Name" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][mst]" required placeholder="MST" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][address]" placeholder="Address" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    </td>
                                    <td class="px-1 py-1.5 text-center">
                                        <button type="button" onclick="removeOrderRequestRow(this)" class="text-red-400 hover:text-red-600">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-2 bg-gray-50 border-t text-xs text-gray-500">
                        <i class="fas fa-info-circle text-yellow-500 mr-1"></i>
                        <span class="text-red-500">(*)</span> bắt buộc điền. Vùng không có dấu (*) là không bắt buộc.
                    </div>
                </div>

                {{-- Note --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-sticky-note text-yellow-500 mr-1"></i> Ghi chú
                    </label>
                    <textarea name="order_request_note" rows="2" placeholder="Ghi chú thêm cho PO team..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"></textarea>
                </div>

                {{-- File Attachments --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-paperclip text-blue-500 mr-1"></i> File đính kèm (PNL, Hợp đồng mua bán,...)
                    </label>
                    <input type="file" name="order_request_files[]" multiple
                        class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-300 rounded-lg px-2 py-1.5">
                    <p class="text-xs text-gray-400 mt-1">Có thể chọn nhiều file. Tối đa 20MB/file.</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl flex items-center justify-end gap-3">
                <button type="button" onclick="closeOrderRequestModal()"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-sm">
                    <i class="fas fa-times mr-1"></i> Hủy
                </button>
                <button type="submit"
                    class="px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-bold shadow-sm">
                    <i class="fas fa-paper-plane mr-1"></i> Gửi yêu cầu đặt hàng
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Order Request History --}}
@if($sale->orderRequests && $sale->orderRequests->count() > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-cyan-50">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-clipboard-list text-emerald-600 mr-2"></i>Lịch sử yêu cầu đặt hàng
                <span class="text-sm font-normal text-gray-500">({{ $sale->orderRequests->count() }})</span>
            </h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($sale->orderRequests()->with(['creator', 'items', 'attachments'])->latest()->get() as $req)
                <div class="p-4" x-data="{ open: {{ $req->status === \App\Models\SaleOrderRequest::STATUS_NEED_INFO ? 'true' : 'false' }}, editMode: false }">
                    {{-- Request Header --}}
                    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="px-2 py-1 text-xs font-bold rounded bg-emerald-100 text-emerald-800">{{ $req->code }}</span>
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-user text-gray-400 mr-1"></i>{{ $req->creator->name ?? 'N/A' }}
                            </span>
                            <span class="text-xs text-gray-400">
                                <i
                                    class="fas fa-clock mr-1"></i>{{ $req->sent_at ? $req->sent_at->format('d/m/Y H:i') : $req->created_at->format('d/m/Y H:i') }}
                            </span>
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-boxes mr-1"></i>{{ $req->items->count() }} sản phẩm
                            </span>
                            @if($req->attachments->count() > 0)
                                <span class="text-xs text-blue-500">
                                    <i class="fas fa-paperclip mr-1"></i>{{ $req->attachments->count() }} file
                                </span>
                            @endif
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-{{ $req->status_color }}-100 text-{{ $req->status_color }}-700 uppercase">
                                {{ $req->status_label }}
                            </span>
                            @if($req->status === \App\Models\SaleOrderRequest::STATUS_NEED_INFO)
                                <button type="button" @click.stop="editMode = !editMode; open = true"
                                    class="ml-1 px-3 py-1 text-[11px] font-bold rounded-lg transition-all shadow-sm"
                                    :class="editMode ? 'bg-gray-200 text-gray-600 hover:bg-gray-300' : 'bg-orange-500 text-white hover:bg-orange-600 animate-pulse'">
                                    <i class="fas" :class="editMode ? 'fa-times' : 'fa-edit'"></i>
                                    <span x-text="editMode ? 'Hủy chỉnh sửa' : 'Chỉnh sửa & Gửi lại'"></span>
                                </button>
                            @endif
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </div>

                    {{-- Rejection Note Banner (always visible when need_info) --}}
                    @if($req->status === \App\Models\SaleOrderRequest::STATUS_NEED_INFO && $req->rejection_note)
                        <div class="mt-3 bg-orange-50 rounded-lg p-3 text-sm text-orange-800 border border-orange-200 flex items-start gap-2">
                            <i class="fas fa-exclamation-triangle text-orange-500 mt-0.5 flex-shrink-0"></i>
                            <div>
                                <strong>Lý do cần bổ sung:</strong> {{ $req->rejection_note }}
                            </div>
                        </div>
                    @endif

                    {{-- Expandable Detail --}}
                    <div x-show="open" x-transition class="mt-3 space-y-3">

                        {{-- ===== READ-ONLY VIEW ===== --}}
                        <div x-show="!editMode">
                            {{-- Items Table --}}
                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="w-full text-xs">
                                    <thead class="bg-yellow-200">
                                        <tr class="border-b border-gray-300">
                                            <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">Vendor</th>
                                            <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">Type</th>
                                            <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">Part Number</th>
                                            <th rowspan="2" class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 align-middle">Qty</th>
                                            <th rowspan="2" class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 align-middle">Unit</th>
                                            <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">SN</th>
                                            <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">Exp date</th>
                                            <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">SI Name</th>
                                            <th rowspan="2" class="px-2 py-1.5 text-left font-bold text-gray-800 border-r border-gray-300 align-middle">POS ID</th>
                                            <th colspan="2" class="px-2 py-1.5 text-center font-bold text-gray-800 border-b border-gray-300">Thông tin CQ (Điền tay)</th>
                                        </tr>
                                        <tr class="border-b border-gray-300">
                                            <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300">EU Name - MST</th>
                                            <th class="px-2 py-1.5 text-center font-bold text-gray-800">Address</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($req->items as $item)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-2 py-1.5 font-medium">{{ $item->vendor->name ?? $item->vendor }}</td>
                                                <td class="px-2 py-1.5"><span
                                                        class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold">{{ $item->type }}</span>
                                                </td>
                                                <td class="px-2 py-1.5 font-medium text-emerald-700">{{ $item->part_number }}</td>
                                                <td class="px-2 py-1.5 text-center font-bold">{{ number_format($item->quantity, 2) }}</td>
                                                <td class="px-2 py-1.5 text-center text-gray-500">{{ $item->unit }}</td>
                                                <td class="px-2 py-1.5 text-gray-600">{{ $item->serial_number ?: '-' }}</td>
                                                <td class="px-2 py-1.5 text-gray-600">
                                                    {{ $item->exp_date ? $item->exp_date->format('d/m/Y') : '-' }}</td>
                                                <td class="px-2 py-1.5">{{ $item->si_name }}</td>
                                                <td class="px-2 py-1.5 text-gray-600">{{ $item->pos_id ?: '-' }}</td>
                                                <td class="px-2 py-1.5">{{ $item->eu_name_mst }}</td>
                                                <td class="px-2 py-1.5 text-gray-600">{{ $item->address ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Note --}}
                            @if($req->note)
                                <div class="bg-yellow-50 rounded-lg p-3 text-sm text-gray-700">
                                    <i class="fas fa-sticky-note text-yellow-500 mr-1"></i>Note: {{ $req->note }}
                                </div>
                            @endif

                            {{-- Attachments --}}
                            @if($req->attachments->count() > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($req->attachments as $att)
                                        <a href="javascript:void(0)"
                                            onclick="openFilePreviewModal('{{ route('sales.order-request.attachment.preview', [$sale->id, $att->id]) }}', '{{ $att->file_name }}')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-blue-50 rounded-lg text-xs text-gray-700 hover:text-blue-600 transition-colors">
                                            <i class="fas fa-eye text-emerald-500"></i>
                                            <span>{{ $att->file_name }}</span>
                                            <span class="text-gray-400">({{ $att->file_size_formatted }})</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- ===== EDIT MODE (only for need_info) ===== --}}
                        @if($req->status === \App\Models\SaleOrderRequest::STATUS_NEED_INFO)
                        <div x-show="editMode" x-transition>
                            <form action="{{ route('sales.order-request.update', [$sale->id, $req->id]) }}" method="POST" enctype="multipart/form-data"
                                  id="editPrForm_{{ $req->id }}">
                                @csrf
                                @method('PUT')

                                <div class="space-y-4">
                                    {{-- Edit Items Table --}}
                                    <div class="border border-orange-200 rounded-lg overflow-hidden">
                                        <div class="bg-orange-50 px-4 py-2 flex items-center justify-between border-b border-orange-200">
                                            <span class="text-sm font-bold text-orange-800">
                                                <i class="fas fa-edit mr-1"></i> Chỉnh sửa chi tiết yêu cầu
                                            </span>
                                            <button type="button" onclick="addEditRow('{{ $req->id }}')"
                                                class="text-xs px-3 py-1.5 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                                                <i class="fas fa-plus mr-1"></i> Thêm dòng
                                            </button>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm" id="editItemsTable_{{ $req->id }}">
                                                <thead>
                                                    <tr class="bg-yellow-200 text-[10px] border-b border-gray-300">
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[140px] uppercase">Vendor <span class="text-red-500">*</span></th>
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[90px] uppercase">Type <span class="text-red-500">*</span></th>
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[160px] uppercase">Part Number <span class="text-red-500">*</span></th>
                                                        <th class="px-2 py-2 text-center font-bold text-gray-800 border-r border-gray-300 w-16 uppercase">Qty <span class="text-red-500">*</span></th>
                                                        <th class="px-2 py-2 text-center font-bold text-gray-800 border-r border-gray-300 w-16 uppercase">Unit</th>
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[100px] uppercase">SN</th>
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[110px] uppercase">Exp date</th>
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[130px] uppercase">SI Name <span class="text-red-500">*</span></th>
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[110px] uppercase">POS ID</th>
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[140px] uppercase">EU Name <span class="text-red-500">*</span></th>
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[100px] uppercase">MST <span class="text-red-500">*</span></th>
                                                        <th class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[140px] uppercase">Address</th>
                                                        <th class="px-2 py-2 text-center font-bold text-gray-800 w-10"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="editItemRows_{{ $req->id }}">
                                                    @foreach($req->items as $idx => $item)
                                                    <tr class="edit-item-row border-b border-gray-100 hover:bg-gray-50" data-index="{{ $idx }}">
                                                        <td class="px-1 py-1">
                                                            <select name="order_request_items[{{ $idx }}][vendor_id]" required
                                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
                                                                <option value="">-- Chọn --</option>
                                                                @foreach($suppliers as $s)
                                                                    <option value="{{ $s->id }}" {{ $s->id == $item->vendor_id ? 'selected' : '' }}>{{ $s->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <select name="order_request_items[{{ $idx }}][type]" required
                                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
                                                                <option value="">-- Chọn --</option>
                                                                @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                                                    <option value="{{ $t }}" {{ $item->type == $t ? 'selected' : '' }}>{{ $t }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <input type="text" name="order_request_items[{{ $idx }}][part_number]" required
                                                                value="{{ $item->part_number }}" placeholder="P/N"
                                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
                                                            <input type="hidden" name="order_request_items[{{ $idx }}][product_id]" value="{{ $item->product_id }}">
                                                            <input type="hidden" name="order_request_items[{{ $idx }}][sale_item_id]" value="{{ $item->sale_item_id }}">
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <input type="number" name="order_request_items[{{ $idx }}][quantity]" required step="0.01"
                                                                value="{{ $item->quantity }}"
                                                                class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <input type="text" name="order_request_items[{{ $idx }}][unit]"
                                                                value="{{ $item->unit }}" placeholder="Đơn vị"
                                                                class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <input type="text" name="order_request_items[{{ $idx }}][serial_number]"
                                                                value="{{ $item->serial_number }}" placeholder="SN"
                                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <input type="text" name="order_request_items[{{ $idx }}][exp_date]"
                                                                value="{{ $item->exp_date ? $item->exp_date->format('Y-m-d') : '' }}" placeholder="YYYY-MM-DD"
                                                                class="exp-date-picker w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <input type="text" name="order_request_items[{{ $idx }}][si_name]" required
                                                                value="{{ $item->si_name }}" placeholder="SI Name"
                                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <input type="text" name="order_request_items[{{ $idx }}][pos_id]"
                                                                value="{{ $item->pos_id }}" placeholder="POS ID"
                                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
                                                        </td>
                                                        @php
                                                            $parts = explode(' - ', $item->eu_name_mst, 2);
                                                            $euNameVal = $parts[0] ?? '';
                                                            $mstVal = $parts[1] ?? '';
                                                        @endphp
                                                        <td class="px-1 py-1">
                                                            <input type="text" name="order_request_items[{{ $idx }}][eu_name]" required
                                                                value="{{ $euNameVal }}" placeholder="EU Name"
                                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <input type="text" name="order_request_items[{{ $idx }}][mst]" required
                                                                value="{{ $mstVal }}" placeholder="MST"
                                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
                                                        </td>
                                                        <td class="px-1 py-1">
                                                            <input type="text" name="order_request_items[{{ $idx }}][address]"
                                                                value="{{ $item->address }}" placeholder="Địa chỉ"
                                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
                                                        </td>
                                                        <td class="px-1 py-1 text-center">
                                                            <button type="button" onclick="removeEditRow(this, '{{ $req->id }}')" class="text-red-400 hover:text-red-600">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- Note & Attachments --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Ghi chú cho PO team</label>
                                            <textarea name="order_request_note" rows="2" placeholder="Ghi chú thêm nếu có..."
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-orange-400 focus:border-orange-400">{{ $req->note }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">File đính kèm mới (giữ lại file cũ)</label>
                                            <input type="file" name="order_request_files[]" multiple
                                                class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 border border-gray-300 rounded-lg p-1">
                                            @if($req->attachments->count() > 0)
                                                <div class="mt-1 text-[10px] text-gray-500">
                                                    <i class="fas fa-paperclip mr-1"></i>File hiện có: 
                                                    @foreach($req->attachments as $att)
                                                        <span class="font-medium">{{ $att->file_name }}</span>{{ !$loop->last ? ', ' : '' }}
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Submit --}}
                                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-200">
                                        <button type="button" @click="editMode = false"
                                            class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                            Hủy bỏ
                                        </button>
                                        <button type="submit"
                                            class="px-6 py-2 bg-orange-500 text-white font-bold text-sm rounded-lg hover:bg-orange-600 shadow-md transition-colors"
                                            onclick="return confirm('Xác nhận chỉnh sửa và gửi lại yêu cầu đặt hàng?')">
                                            <i class="fas fa-paper-plane mr-2"></i>Gửi lại yêu cầu
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @endif

                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif