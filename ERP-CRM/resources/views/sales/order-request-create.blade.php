@extends('layouts.app')

@section('title', 'Tạo yêu cầu đặt hàng')
@section('page-title', 'Yêu cầu đặt hàng cho đơn: ' . $sale->code)

@section('content')
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-4 sm:p-6 bg-teal-50 border-b border-teal-100 flex items-center justify-between">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-teal-500 rounded-lg flex items-center justify-center text-white mr-4">
                <i class="fas fa-cart-plus text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Khởi tạo yêu cầu đặt hàng</h3>
                <p class="text-sm text-teal-700">Theo mẫu chuẩn của hệ thống</p>
            </div>
        </div>
        <a href="{{ route('sales.show', $sale->id) }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times text-xl"></i>
        </a>
    </div>

    <form action="{{ route('sales.order-request.store', $sale->id) }}" method="POST" enctype="multipart/form-data" id="orderRequestForm">
        @csrf
        <div class="p-4 sm:p-6 space-y-6">
            {{-- Info Banner --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                <div class="text-xs text-blue-800">
                    <span class="font-bold">Đơn hàng:</span> {{ $sale->code }} | 
                    <span class="font-bold">Khách hàng:</span> {{ $sale->customer_name }}
                </div>
            </div>

            {{-- Items Table --}}
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 flex items-center justify-between border-b border-gray-200">
                    <span class="text-sm font-bold text-gray-700">
                        <i class="fas fa-list mr-1"></i> Chi tiết yêu cầu
                    </span>
                    <button type="button" onclick="addRow()"
                        class="text-xs px-3 py-1.5 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors">
                        <i class="fas fa-plus mr-1"></i> Thêm dòng
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="itemsTable">
                        <thead>
                            <tr class="bg-yellow-200 text-[10px] border-b border-gray-300">
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[140px] align-middle uppercase">Vendor <span class="text-red-500">*</span></th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[90px] align-middle uppercase">Type <span class="text-red-500">*</span></th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[180px] align-middle uppercase">Part Number <span class="text-red-500">*</span></th>
                                <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 border-r border-gray-300 w-16 align-middle uppercase">Qty <span class="text-red-500">*</span></th>
                                <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 border-r border-gray-300 w-16 align-middle uppercase">Unit</th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[100px] align-middle uppercase">SN</th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[110px] align-middle uppercase">Exp date</th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[130px] align-middle uppercase">SI Name <span class="text-red-500">*</span></th>
                                <th colspan="2" class="px-2 py-1.5 text-center font-bold text-gray-800 border-b border-r border-gray-300 uppercase">Thông tin CQ (Điền tay)</th>
                                <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 w-10 align-middle"></th>
                            </tr>
                            <tr class="bg-yellow-200 text-[10px] border-b border-gray-300">
                                <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px] uppercase">EU Name - MST <span class="text-red-500">*</span></th>
                                <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px] uppercase">Address</th>
                            </tr>
                        </thead>
                        <tbody id="itemRows">
                            @foreach($sale->items as $idx => $saleItem)
                            @php
                                $partNumber = $saleItem->product ? $saleItem->product->code : $saleItem->product_name;
                            @endphp
                            <tr class="item-row border-b border-gray-100 hover:bg-gray-50" data-index="{{ $idx }}">
                                <td class="px-1 py-1">
                                    <select name="order_request_items[{{ $idx }}][vendor_id]" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                        <option value="">-- Chọn --</option>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-1 py-1">
                                    <select name="order_request_items[{{ $idx }}][type]" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                        <option value="">-- Chọn --</option>
                                        @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][part_number]" required
                                        value="{{ $partNumber }}" placeholder="P/N"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-teal-700 bg-teal-50 focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                    <input type="hidden" name="order_request_items[{{ $idx }}][product_id]" value="{{ $saleItem->product_id }}">
                                    <input type="hidden" name="order_request_items[{{ $idx }}][sale_item_id]" value="{{ $saleItem->id }}">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="number" name="order_request_items[{{ $idx }}][quantity]" required step="0.01"
                                        value="{{ $saleItem->quantity }}"
                                        class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][unit]"
                                        value="{{ $saleItem->product->unit ?? '' }}" placeholder="Đơn vị"
                                        class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][serial_number]" placeholder="SN"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="date" name="order_request_items[{{ $idx }}][exp_date]"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][si_name]" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400 bg-gray-50" placeholder="Nhập thông tin" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][eu_name_mst]" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400 bg-gray-50" placeholder="Nhập thông tin" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][address]"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400 bg-gray-50" placeholder="Nhập thông tin" autocomplete="off">
                                </td>
                                <td class="px-1 py-1 text-center">
                                    <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-2 bg-yellow-50 text-[10px] text-gray-600 border-t border-gray-200">
                    <span class="font-bold text-red-500">(*)</span>: Bắt buộc điền. <span class="bg-gray-100 px-1 border border-gray-200">Vùng màu xám</span>: Sales tự điền tay.
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Ghi chú cho PO team</label>
                    <textarea name="order_request_note" rows="2" placeholder="Ghi chú thêm nếu có..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-teal-400 focus:border-teal-400"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">File đính kèm</label>
                    <input type="file" name="order_request_files[]" multiple
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 border border-gray-300 rounded-lg p-1">
                </div>
            </div>
        </div>

        <div class="px-4 py-4 bg-gray-50 border-t flex items-center justify-end gap-3">
            <a href="{{ route('sales.show', $sale->id) }}" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Hủy bỏ
            </a>
            <button type="submit" 
                class="px-8 py-2 bg-teal-600 text-white font-bold text-sm rounded-lg hover:bg-teal-700 shadow-md transition-colors">
                <i class="fas fa-paper-plane mr-2"></i> Gửi yêu cầu
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    let rowIdx = {{ count($sale->items) }};
    const suppliers = @json($suppliers->map(fn($s) => ['id' => $s->id, 'name' => $s->name]));
    const orderTypes = @json(\App\Models\SaleOrderRequest::TYPES);

    function addRow() {
        const tbody = document.getElementById('itemRows');
        const tr = document.createElement('tr');
        tr.className = 'item-row border-b border-gray-100 hover:bg-gray-50';
        
        let supplierOptions = '<option value="">-- Chọn --</option>';
        suppliers.forEach(s => {
            supplierOptions += `<option value="${s.id}">${s.name}</option>`;
        });

        let typeOptions = '<option value="">-- Chọn --</option>';
        orderTypes.forEach(t => {
            typeOptions += `<option value="${t}">${t}</option>`;
        });

        tr.innerHTML = `
            <td class="px-1 py-1">
                <select name="order_request_items[${rowIdx}][vendor_id]" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                    ${supplierOptions}
                </select>
            </td>
            <td class="px-1 py-1">
                <select name="order_request_items[${rowIdx}][type]" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                    ${typeOptions}
                </select>
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][part_number]" required placeholder="P/N"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-teal-700 bg-teal-50 focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
                <input type="hidden" name="order_request_items[${rowIdx}][product_id]" value="">
            </td>
            <td class="px-1 py-1">
                <input type="number" name="order_request_items[${rowIdx}][quantity]" required step="0.01" value="1"
                    class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][unit]" placeholder="Đơn vị"
                    class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][serial_number]" placeholder="SN"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
            </td>
            <td class="px-1 py-1">
                <input type="date" name="order_request_items[${rowIdx}][exp_date]"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][si_name]" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400 bg-gray-50" placeholder="Nhập thông tin">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][eu_name_mst]" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400 bg-gray-50" placeholder="Nhập thông tin">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][address]"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-teal-400 focus:border-teal-400 bg-gray-50" placeholder="Nhập thông tin">
            </td>
            <td class="px-1 py-1 text-center">
                <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
        rowIdx++;
    }

    function removeRow(btn) {
        if (document.querySelectorAll('.item-row').length > 1) {
            btn.closest('.item-row').remove();
        } else {
            alert('Yêu cầu đặt hàng phải có ít nhất 1 sản phẩm.');
        }
    }
</script>
@endpush
@endsection
