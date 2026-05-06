{{-- Order Request Modal --}}
<div id="orderRequestModal"
    class="hidden fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col">
        {{-- Header --}}
        <div
            class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-teal-50 to-cyan-50 rounded-t-xl flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-cart-plus text-teal-600 mr-2"></i>Form Yêu cầu đặt hàng
                </h3>
                <p class="text-sm text-gray-500 mt-0.5">Đơn hàng: <span
                        class="font-bold text-teal-700">{{ $sale->code }}</span></p>
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
                                class="text-xs px-3 py-1.5 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors">
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
                                    <th colspan="2" class="px-2 py-1.5 text-center font-bold text-gray-800 border-b border-r border-gray-300">
                                        Thông tin CQ (Điền tay)</th>
                                    <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 w-10 align-middle"></th>
                                </tr>
                                <tr class="bg-yellow-200 text-xs border-b border-gray-300">
                                    <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px]">EU Name - MST <span class="text-red-500">*</span></th>
                                    <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px]">Address</th>
                                </tr>
                            </thead>
                            <tbody id="orderRequestRows">
                                <tr class="order-request-row border-b border-gray-100 hover:bg-gray-50" data-index="0">
                                    <td class="px-1 py-1.5">
                                        <select name="order_request_items[0][vendor_id]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                            <option value="">-- Chọn --</option>
                                            @foreach($suppliers as $s)
                                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <select name="order_request_items[0][type]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                            <option value="">-- Chọn --</option>
                                            @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                                <option value="{{ $t }}">{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][part_number]" required placeholder="P/N" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="number" name="order_request_items[0][quantity]" required step="0.01" value="1" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400 text-center">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][unit]" placeholder="Đơn vị" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][serial_number]" placeholder="SN" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="date" name="order_request_items[0][exp_date]" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][si_name]" required placeholder="SI Name" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][eu_name_mst]" required placeholder="EU Name - MST" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[0][address]" placeholder="Address" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
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
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-teal-400 focus:border-teal-400"></textarea>
                </div>

                {{-- File Attachments --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-paperclip text-blue-500 mr-1"></i> File đính kèm (PNL, Hợp đồng mua bán,...)
                    </label>
                    <input type="file" name="order_request_files[]" multiple
                        class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 border border-gray-300 rounded-lg px-2 py-1.5">
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
                    class="px-5 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors text-sm font-bold shadow-sm">
                    <i class="fas fa-paper-plane mr-1"></i> Gửi yêu cầu đặt hàng
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Order Request History --}}
@if($sale->orderRequests && $sale->orderRequests->count() > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-teal-50 to-cyan-50">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-clipboard-list text-teal-600 mr-2"></i>Lịch sử yêu cầu đặt hàng
                <span class="text-sm font-normal text-gray-500">({{ $sale->orderRequests->count() }})</span>
            </h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($sale->orderRequests()->with(['creator', 'items', 'attachments'])->latest()->get() as $req)
                <div class="p-4" x-data="{ open: false }">
                    {{-- Request Header --}}
                    <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 text-xs font-bold rounded bg-teal-100 text-teal-800">{{ $req->code }}</span>
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
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </div>

                    {{-- Expandable Detail --}}
                    <div x-show="open" x-transition class="mt-3 space-y-3">
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
                                            <td class="px-2 py-1.5 font-medium text-teal-700">{{ $item->part_number }}</td>
                                            <td class="px-2 py-1.5 text-center font-bold">{{ number_format($item->quantity, 2) }}</td>
                                            <td class="px-2 py-1.5 text-center text-gray-500">{{ $item->unit }}</td>
                                            <td class="px-2 py-1.5 text-gray-600">{{ $item->serial_number ?: '-' }}</td>
                                            <td class="px-2 py-1.5 text-gray-600">
                                                {{ $item->exp_date ? $item->exp_date->format('d/m/Y') : '-' }}</td>
                                            <td class="px-2 py-1.5">{{ $item->si_name }}</td>
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

                        @if($req->status === \App\Models\SaleOrderRequest::STATUS_NEED_INFO && $req->rejection_note)
                            <div class="bg-red-50 rounded-lg p-3 text-sm text-red-700 border border-red-100">
                                <i class="fas fa-exclamation-triangle text-red-500 mr-1"></i><strong>Lý do từ chối/Cần bổ sung:</strong> {{ $req->rejection_note }}
                            </div>
                        @endif

                        {{-- Attachments --}}
                        @if($req->attachments->count() > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach($req->attachments as $att)
                                    <a href="{{ route('sales.order-request.attachment.download', [$sale->id, $att->id]) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-blue-50 rounded-lg text-xs text-gray-700 hover:text-blue-600 transition-colors">
                                        <i class="{{ $att->file_icon }}"></i>
                                        <span>{{ $att->file_name }}</span>
                                        <span class="text-gray-400">({{ $att->file_size_formatted }})</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif