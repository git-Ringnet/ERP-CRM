{{-- Order Request Form Section (inline, for create/edit pages) --}}
{{-- Usage: @include('sales.partials.order-request-form', ['orderRequest' => $existingOrderRequest ?? null]) --}}
@php
    $existingItems = isset($orderRequest) && $orderRequest ? $orderRequest->items : collect();
    $existingNote = isset($orderRequest) && $orderRequest ? $orderRequest->note : '';
    $existingAttachments = isset($orderRequest) && $orderRequest ? $orderRequest->attachments : collect();
@endphp

<div class="border-t pt-4">
    {{-- Header with toggle --}}
    <button type="button" onclick="toggleOrderRequestSection()"
        class="w-full flex items-center justify-between text-left">
        <h4 class="text-lg font-medium text-gray-900">
            <i class="fas fa-cart-plus text-teal-500 mr-2"></i>Yêu cầu đặt hàng
            @if($existingItems->count() > 0)
                <span
                    class="ml-2 px-2 py-0.5 text-xs font-bold bg-teal-100 text-teal-700 rounded-full">{{ $existingItems->count() }}
                    SP</span>
            @endif
        </h4>
        <i id="orderRequestToggleIcon"
            class="fas fa-chevron-down text-gray-400 transition-transform {{ $existingItems->count() > 0 ? 'rotate-180' : '' }}"></i>
    </button>

    {{-- Collapsible Section --}}
    <div id="orderRequestSection" class="{{ $existingItems->count() > 0 ? '' : 'hidden' }} mt-3 space-y-4">

        {{-- Items Table --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 flex items-center justify-between">
                <span class="text-sm font-bold text-gray-700">
                    <i class="fas fa-list mr-1"></i> Chi tiết sản phẩm cần đặt
                </span>
                <button type="button" onclick="addOrderRequestRow()"
                    class="text-xs px-3 py-1.5 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors">
                    <i class="fas fa-plus mr-1"></i> Thêm dòng
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="orderRequestTable">
                    <thead>
                        <tr class="bg-yellow-200 text-xs border-b border-gray-300">
                            <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[140px] align-middle">Vendor <span class="text-red-500">*</span></th>
                            <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[100px] align-middle">Type <span class="text-red-500">*</span></th>
                            <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[160px] align-middle">Part Number <span class="text-red-500">*</span></th>
                            <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[100px] align-middle">SN</th>
                            <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[120px] align-middle">Exp date</th>
                            <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[120px] align-middle">SI Name <span class="text-red-500">*</span></th>
                            <th colspan="2" class="px-2 py-1.5 text-center font-bold text-gray-800 border-b border-r border-gray-300">Thông tin CQ (Điền tay)</th>
                            <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 w-10 align-middle"></th>
                        </tr>
                        <tr class="bg-yellow-200 text-xs border-b border-gray-300">
                            <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px]">EU Name - MST <span class="text-red-500">*</span></th>
                            <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px]">Address</th>
                        </tr>
                    </thead>
                    <tbody id="orderRequestRows">
                        @if($existingItems->count() > 0)
                            @foreach($existingItems as $idx => $item)
                                <tr class="order-request-row border-b border-gray-100 hover:bg-gray-50" data-index="{{ $idx }}">
                                    <td class="px-1 py-1.5">
                                        <select name="order_request_items[{{ $idx }}][vendor]" required
                                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                            <option value="">-- Chọn --</option>
                                            @foreach(\App\Models\SaleOrderRequest::VENDORS as $v)
                                                <option value="{{ $v }}" {{ $item->vendor == $v ? 'selected' : '' }}>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <select name="order_request_items[{{ $idx }}][type]" required
                                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                            <option value="">-- Chọn --</option>
                                            @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                                <option value="{{ $t }}" {{ $item->type == $t ? 'selected' : '' }}>{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[{{ $idx }}][part_number]" required
                                            value="{{ $item->part_number }}"
                                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[{{ $idx }}][serial_number]"
                                            value="{{ $item->serial_number }}"
                                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="date" name="order_request_items[{{ $idx }}][exp_date]"
                                            value="{{ $item->exp_date ? $item->exp_date->format('Y-m-d') : '' }}"
                                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[{{ $idx }}][si_name]" required
                                            value="{{ $item->si_name }}"
                                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[{{ $idx }}][eu_name_mst]" required
                                            value="{{ $item->eu_name_mst }}"
                                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <input type="text" name="order_request_items[{{ $idx }}][address]"
                                            value="{{ $item->address }}"
                                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    </td>
                                    <td class="px-1 py-1.5 text-center">
                                        <button type="button" onclick="removeOrderRequestRow(this)"
                                            class="text-red-400 hover:text-red-600">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr class="order-request-row border-b border-gray-100 hover:bg-gray-50" data-index="0">
                                <td class="px-1 py-1.5">
                                    <select name="order_request_items[0][vendor]" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                        <option value="">-- Chọn --</option>
                                        @foreach(\App\Models\SaleOrderRequest::VENDORS as $v)
                                            <option value="{{ $v }}">{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-1 py-1.5">
                                    <select name="order_request_items[0][type]" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                        <option value="">-- Chọn --</option>
                                        @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-1 py-1.5">
                                    <input type="text" name="order_request_items[0][part_number]" required placeholder="P/N"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                </td>
                                <td class="px-1 py-1.5">
                                    <input type="text" name="order_request_items[0][serial_number]" placeholder="SN"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                </td>
                                <td class="px-1 py-1.5">
                                    <input type="date" name="order_request_items[0][exp_date]"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                </td>
                                <td class="px-1 py-1.5">
                                    <input type="text" name="order_request_items[0][si_name]" required placeholder="SI Name"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                </td>
                                <td class="px-1 py-1.5">
                                    <input type="text" name="order_request_items[0][eu_name_mst]" required
                                        placeholder="EU Name - MST"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                </td>
                                <td class="px-1 py-1.5">
                                    <input type="text" name="order_request_items[0][address]" placeholder="Address"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                </td>
                                <td class="px-1 py-1.5 text-center">
                                    <button type="button" onclick="removeOrderRequestRow(this)"
                                        class="text-red-400 hover:text-red-600">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        @endif
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
                <i class="fas fa-sticky-note text-yellow-500 mr-1"></i> Ghi chú cho PO team
            </label>
            <textarea name="order_request_note" rows="2" placeholder="Ghi chú thêm cho PO team..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-teal-400 focus:border-teal-400">{{ $existingNote }}</textarea>
        </div>

        {{-- File Attachments --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <i class="fas fa-paperclip text-blue-500 mr-1"></i> File đính kèm (PNL, Hợp đồng mua bán,...)
            </label>
            <input type="file" name="order_request_files[]" multiple
                class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 border border-gray-300 rounded-lg px-2 py-1.5">
            <p class="text-xs text-gray-400 mt-1">Có thể chọn nhiều file cùng lúc. Tối đa 20MB/file.</p>

            {{-- Show existing attachments --}}
            @if($existingAttachments->count() > 0)
                <div class="mt-2 space-y-1">
                    <p class="text-xs font-medium text-gray-600">File đã tải lên:</p>
                    @foreach($existingAttachments as $att)
                        <div class="flex items-center gap-2 text-xs text-gray-600 bg-gray-50 px-2 py-1 rounded">
                            <i class="{{ $att->file_icon }}"></i>
                            <span>{{ $att->file_name }}</span>
                            <span class="text-gray-400">({{ $att->file_size_formatted }})</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>