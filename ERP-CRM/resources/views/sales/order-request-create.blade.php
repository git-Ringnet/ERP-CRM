@extends('layouts.app')

@section('title', 'Tạo yêu cầu đặt hàng')
@section('page-title', 'Yêu cầu đặt hàng cho đơn: ' . $sale->code)

@section('content')
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-4 sm:p-6 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center text-white mr-4">
                <i class="fas fa-cart-plus text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Khởi tạo yêu cầu đặt hàng</h3>
                <p class="text-sm text-emerald-700">Theo mẫu chuẩn của hệ thống</p>
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

            {{-- Global SI/EU inputs (only need to fill once) --}}
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SI Name <span class="text-red-500">*</span></label>
                    <div class="searchable-select" id="globalSiNameSelect">
                        <input type="text" id="global_si_name" name="global_si_name" required
                            class="searchable-input w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white"
                            placeholder="Gõ để tìm khách hàng..." autocomplete="off"
                            value="{{ old('global_si_name', '') }}">
                        <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                            @foreach($customers as $customer)
                                <div class="searchable-option px-3 py-2 hover:bg-emerald-50 cursor-pointer text-sm"
                                     data-value="{{ $customer->id }}"
                                     data-text="{{ $customer->name }}"
                                     data-name="{{ $customer->name }}">
                                    {{ $customer->name }}@if($customer->tax_code) <span class="text-gray-400 text-xs">({{ $customer->tax_code }})</span>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reseller POS ID</label>
                    <input type="text" id="global_pos_id" name="global_pos_id"
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">EU Name <span class="text-red-500">*</span></label>
                    <input type="text" id="global_eu_name" name="global_eu_name" required
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">MST <span class="text-red-500">*</span></label>
                    <input type="text" id="global_mst" name="global_mst" required
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" id="global_address" name="global_address"
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50">
                </div>
            </div>

            {{-- Global Vendor/Type Selector Panel --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-emerald-50/50 p-3 rounded-lg border border-emerald-100 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Vendor (Chung)</label>
                    <select id="global_vendor_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white">
                        <option value="">-- Chọn Vendor --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Type (Chung)</label>
                    <select id="global_type" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white">
                        <option value="">-- Chọn Type --</option>
                        @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="button" onclick="applyGlobalVendorType()"
                        class="w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition-colors shadow-sm flex items-center justify-center gap-1.5">
                        <i class="fas fa-check-double"></i> Áp dụng cho tất cả hàng
                    </button>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 flex items-center justify-between border-b border-gray-200">
                    <span class="text-sm font-bold text-gray-700">
                        <i class="fas fa-list mr-1"></i> Chi tiết yêu cầu
                    </span>
                    <button type="button" onclick="addRow()"
                        class="text-xs px-3 py-1.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors">
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
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[110px] align-middle uppercase">POS ID</th>
                                <th colspan="3" class="px-2 py-1.5 text-center font-bold text-gray-800 border-b border-r border-gray-300 uppercase">Thông tin CQ (Điền tay)</th>
                                <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 w-10 align-middle"></th>
                            </tr>
                            <tr class="bg-yellow-200 text-[10px] border-b border-gray-300">
                                <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px] uppercase">EU Name <span class="text-red-500">*</span></th>
                                <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[100px] uppercase">MST <span class="text-red-500">*</span></th>
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
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                        <option value="">-- Chọn --</option>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}" {{ $s->id == $saleItem->vendor_id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-1 py-1">
                                    <select name="order_request_items[{{ $idx }}][type]" required
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                        <option value="">-- Chọn --</option>
                                        @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                            <option value="{{ $t }}" {{ $saleItem->type == $t ? 'selected' : '' }}>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][part_number]" required
                                        value="{{ $partNumber }}" placeholder="P/N"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    <input type="hidden" name="order_request_items[{{ $idx }}][product_id]" value="{{ $saleItem->product_id }}">
                                    <input type="hidden" name="order_request_items[{{ $idx }}][sale_item_id]" value="{{ $saleItem->id }}">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="number" name="order_request_items[{{ $idx }}][quantity]" required step="0.01"
                                        value="{{ $saleItem->quantity }}"
                                        class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][unit]"
                                        value="{{ $saleItem->product->unit ?? '' }}" placeholder="Đơn vị"
                                        class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][serial_number]" placeholder="SN"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][exp_date]" placeholder="YYYY-MM-DD"
                                        class="exp-date-picker w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][si_name]" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập thông tin" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][pos_id]" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="POS ID" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][eu_name]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập EU Name" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][mst]" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập MST" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][address]"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập thông tin" autocomplete="off">
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
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">File đính kèm</label>
                    <input type="file" name="order_request_files[]" multiple
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-300 rounded-lg p-1">
                </div>
            </div>
        </div>

        <div class="px-4 py-4 bg-gray-50 border-t flex items-center justify-end gap-3">
            <a href="{{ route('sales.show', $sale->id) }}" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Hủy bỏ
            </a>
            <button type="submit" 
                class="px-8 py-2 bg-emerald-600 text-white font-bold text-sm rounded-lg hover:bg-emerald-700 shadow-md transition-colors">
                <i class="fas fa-paper-plane mr-2"></i> Gửi yêu cầu
            </button>
        </div>
    </form>
</div>

{{-- Confirm Submit Modal --}}
<div id="confirmSubmitModal" class="fixed inset-0 z-[200] hidden">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" id="confirmModalBg"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all scale-95 opacity-0" id="confirmModalContent">
            <div class="p-6 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-paper-plane text-2xl text-emerald-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Xác nhận gửi đơn hàng</h3>
                <p class="text-sm text-gray-500 mb-6">Bạn có chắc muốn gửi đơn hàng?</p>
                <div class="flex gap-3 justify-center">
                    <button type="button" id="cancelSubmitBtn"
                        class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="confirmSubmitBtn"
                        class="px-6 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-md transition-colors">
                        <i class="fas fa-check mr-1.5"></i>Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.searchable-select {
    position: relative;
}
.searchable-dropdown {
    top: 100%;
    left: 0;
    right: 0;
}
.searchable-option.highlighted {
    background-color: #d1fae5;
}
.no-results {
    padding: 8px 12px;
    color: #6b7280;
    font-style: italic;
    font-size: 0.875rem;
}
</style>
@endpush

@push('scripts')
<script>
    function initExpDatePicker(selectorOrElement) {
        if (typeof flatpickr !== 'undefined') {
            flatpickr(selectorOrElement, {
                dateFormat: "Y-m-d",
                allowInput: true,
                parseDate: function(datestr, format) {
                    const matches = datestr.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if (matches) {
                        return new Date(
                            parseInt(matches[1], 10),
                            parseInt(matches[2], 10) - 1,
                            parseInt(matches[3], 10)
                        );
                    }
                    const d = new Date(datestr);
                    if (!isNaN(d.getTime())) {
                        return d;
                    }
                    return null;
                },
                formatDate: function(date, format, locale) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }
            });
            
            const elements = (typeof selectorOrElement === 'string') 
                ? document.querySelectorAll(selectorOrElement) 
                : [selectorOrElement];
                
            elements.forEach(el => {
                if (el && !el.dataset.maskBound) {
                    el.dataset.maskBound = 'true';
                    
                    let prevValue = el.value || '';
                    
                    el.addEventListener('input', function(e) {
                        const currentVal = this.value;
                        if (currentVal.length < prevValue.length) {
                            prevValue = currentVal;
                            return;
                        }
                        
                        let digits = currentVal.replace(/\D/g, '');
                        let formatted = '';
                        if (digits.length > 0) {
                            formatted += digits.substring(0, 4);
                            if (digits.length >= 4) {
                                formatted += '-';
                                formatted += digits.substring(4, 6);
                                if (digits.length >= 6) {
                                    formatted += '-';
                                    formatted += digits.substring(6, 8);
                                }
                            }
                        }
                        
                        this.value = formatted;
                        prevValue = formatted;
                    });
                    
                    el.addEventListener('blur', function() {
                        prevValue = this.value;
                    });
                    
                    el.addEventListener('change', function() {
                        prevValue = this.value;
                    });
                }
            });
        }
    }

    let rowIdx = {{ count($sale->items) }};
    const suppliers = @json($suppliers->map(fn($s) => ['id' => $s->id, 'name' => $s->name]));
    const orderTypes = @json(\App\Models\SaleOrderRequest::TYPES);

    function initSearchableSelect(container, onSelect) {
        const input = container.querySelector('.searchable-input');
        const dropdown = container.querySelector('.searchable-dropdown');

        input.addEventListener('focus', () => {
            if (input.value.trim()) {
                filterOptions(input.value);
            }
        });

        input.addEventListener('input', (e) => {
            filterOptions(e.target.value);
        });

        function filterOptions(query) {
            const q = query.trim().toLowerCase();
            if (!q) {
                dropdown.classList.add('hidden');
                dropdown.querySelectorAll('.searchable-option.highlighted').forEach(o => o.classList.remove('highlighted'));
                return;
            }

            dropdown.classList.remove('hidden');
            let hasResults = false;
            dropdown.querySelectorAll('.searchable-option').forEach(opt => {
                const text = opt.dataset.text.toLowerCase();
                if (text.includes(q)) {
                    opt.classList.remove('hidden');
                    hasResults = true;
                } else {
                    opt.classList.add('hidden');
                }
            });

            let noResults = dropdown.querySelector('.no-results');
            if (!hasResults) {
                if (!noResults) {
                    noResults = document.createElement('div');
                    noResults.className = 'no-results px-3 py-2 text-gray-500';
                    noResults.textContent = 'Không tìm thấy kết quả';
                    dropdown.appendChild(noResults);
                }
                noResults.classList.remove('hidden');
            } else if (noResults) {
                noResults.classList.add('hidden');
            }
        }

        dropdown.querySelectorAll('.searchable-option').forEach(opt => {
            opt.addEventListener('click', () => {
                input.value = opt.dataset.name || opt.dataset.text;
                dropdown.classList.add('hidden');
                if (onSelect) onSelect(opt);
            });
        });

        input.addEventListener('keydown', (e) => {
            const visibleOptions = [...dropdown.querySelectorAll('.searchable-option')].filter(o => !o.classList.contains('hidden'));
            const highlighted = dropdown.querySelector('.searchable-option.highlighted');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!highlighted && visibleOptions.length) {
                    visibleOptions[0].classList.add('highlighted');
                } else if (highlighted) {
                    const idx = visibleOptions.indexOf(highlighted);
                    if (idx < visibleOptions.length - 1) {
                        highlighted.classList.remove('highlighted');
                        visibleOptions[idx + 1].classList.add('highlighted');
                        visibleOptions[idx + 1].scrollIntoView({ block: 'nearest' });
                    }
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (highlighted) {
                    const idx = visibleOptions.indexOf(highlighted);
                    if (idx > 0) {
                        highlighted.classList.remove('highlighted');
                        visibleOptions[idx - 1].classList.add('highlighted');
                        visibleOptions[idx - 1].scrollIntoView({ block: 'nearest' });
                    }
                }
            } else if (e.key === 'Enter' && highlighted) {
                e.preventDefault();
                e.stopPropagation();
                highlighted.click();
            } else if (e.key === 'Escape') {
                dropdown.classList.add('hidden');
            }
        });

        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    // Populate existing rows with global SI/EU values if present
    function syncGlobalToRows(){
        const si = document.getElementById('global_si_name').value;
        const pos = document.getElementById('global_pos_id').value;
        const eu = document.getElementById('global_eu_name').value;
        const mst = document.getElementById('global_mst').value;
        const addr = document.getElementById('global_address').value;
        document.querySelectorAll('input[name$="[si_name]"]').forEach(el => el.value = si);
        document.querySelectorAll('input[name$="[pos_id]"]').forEach(el => el.value = pos);
        document.querySelectorAll('input[name$="[eu_name]"]').forEach(el => el.value = eu);
        document.querySelectorAll('input[name$="[mst]"]').forEach(el => el.value = mst);
        document.querySelectorAll('input[name$="[address]"]').forEach(el => el.value = addr);
    }

    document.getElementById('global_si_name').addEventListener('input', syncGlobalToRows);
    document.getElementById('global_pos_id').addEventListener('input', syncGlobalToRows);
    document.getElementById('global_eu_name').addEventListener('input', syncGlobalToRows);
    document.getElementById('global_mst').addEventListener('input', syncGlobalToRows);
    document.getElementById('global_address').addEventListener('input', syncGlobalToRows);

    function applyGlobalVendorType() {
        const globalVendor = document.getElementById('global_vendor_id').value;
        const globalType = document.getElementById('global_type').value;

        if (!globalVendor && !globalType) {
            alert('Vui lòng chọn Vendor hoặc Type trước khi áp dụng.');
            return;
        }

        if (globalVendor) {
            document.querySelectorAll('select[name$="[vendor_id]"]').forEach(select => {
                select.value = globalVendor;
            });
        }

        if (globalType) {
            document.querySelectorAll('select[name$="[type]"]').forEach(select => {
                select.value = globalType;
            });
        }
    }

    function addRow() {
        const tbody = document.getElementById('itemRows');
        const tr = document.createElement('tr');
        tr.className = 'item-row border-b border-gray-100 hover:bg-gray-50';
        
        const globalVendor = document.getElementById('global_vendor_id').value;
        const globalType = document.getElementById('global_type').value;

        let supplierOptions = '<option value="">-- Chọn --</option>';
        suppliers.forEach(s => {
            supplierOptions += `<option value="${s.id}" ${globalVendor == s.id ? 'selected' : ''}>${s.name}</option>`;
        });

        let typeOptions = '<option value="">-- Chọn --</option>';
        orderTypes.forEach(t => {
            typeOptions += `<option value="${t}" ${globalType == t ? 'selected' : ''}>${t}</option>`;
        });
        const siGlobal = document.getElementById('global_si_name').value;
        const posGlobal = document.getElementById('global_pos_id').value;
        const euGlobal = document.getElementById('global_eu_name').value;
        const mstGlobal = document.getElementById('global_mst').value;
        const addrGlobal = document.getElementById('global_address').value;

        tr.innerHTML = `
            <td class="px-1 py-1">
                <select name="order_request_items[${rowIdx}][vendor_id]" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                    ${supplierOptions}
                </select>
            </td>
            <td class="px-1 py-1">
                <select name="order_request_items[${rowIdx}][type]" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                    ${typeOptions}
                </select>
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][part_number]" required placeholder="P/N"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                <input type="hidden" name="order_request_items[${rowIdx}][product_id]" value="">
                <input type="hidden" name="order_request_items[${rowIdx}][sale_item_id]" value="">
            </td>
            <td class="px-1 py-1">
                <input type="number" name="order_request_items[${rowIdx}][quantity]" required step="0.01" value="1"
                    class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][unit]" placeholder="Đơn vị"
                    class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][serial_number]" placeholder="SN"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][exp_date]" placeholder="YYYY-MM-DD"
                    class="exp-date-picker w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][si_name]" value="${siGlobal}"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập thông tin">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][pos_id]" value="${posGlobal}"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="POS ID">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][eu_name]" value="${euGlobal}" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập EU Name">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][mst]" value="${mstGlobal}" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập MST">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][address]" value="${addrGlobal}"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập thông tin">
            </td>
            <td class="px-1 py-1 text-center">
                <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(tr);
        initExpDatePicker(tr.querySelector('.exp-date-picker'));
        rowIdx++;
    }

    function removeRow(btn) {
        if (document.querySelectorAll('.item-row').length > 1) {
            btn.closest('.item-row').remove();
        } else {
            alert('Yêu cầu đặt hàng phải có ít nhất 1 sản phẩm.');
        }
    }

    // Initial sync on page load in case global values already filled (e.g., after validation error)
    document.addEventListener('DOMContentLoaded', function() {
        const siSelect = document.getElementById('globalSiNameSelect');
        if (siSelect) {
            initSearchableSelect(siSelect, () => syncGlobalToRows());
        }
        syncGlobalToRows();
        initExpDatePicker(".exp-date-picker");
    });

    // === Double-Enter to submit with confirmation modal ===
    let lastEnterTime = 0;
    const DOUBLE_ENTER_THRESHOLD = 500; // ms

    const confirmModal = document.getElementById('confirmSubmitModal');
    const confirmModalContent = document.getElementById('confirmModalContent');
    const confirmModalBg = document.getElementById('confirmModalBg');
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    const cancelSubmitBtn = document.getElementById('cancelSubmitBtn');
    const orderForm = document.getElementById('orderRequestForm');

    function showConfirmModal() {
        confirmModal.classList.remove('hidden');
        // Trigger animation
        requestAnimationFrame(() => {
            confirmModalContent.classList.remove('scale-95', 'opacity-0');
            confirmModalContent.classList.add('scale-100', 'opacity-100');
        });
    }

    function hideConfirmModal() {
        confirmModalContent.classList.remove('scale-100', 'opacity-100');
        confirmModalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => confirmModal.classList.add('hidden'), 150);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter') return;

        // Ignore Enter inside textarea
        if (e.target.tagName === 'TEXTAREA') return;

        // Prevent default form submit on Enter
        e.preventDefault();

        const now = Date.now();
        if (now - lastEnterTime <= DOUBLE_ENTER_THRESHOLD) {
            lastEnterTime = 0;
            showConfirmModal();
        } else {
            lastEnterTime = now;
        }
    });

    confirmSubmitBtn.addEventListener('click', function() {
        hideConfirmModal();
        orderForm.submit();
    });

    cancelSubmitBtn.addEventListener('click', hideConfirmModal);
    confirmModalBg.addEventListener('click', hideConfirmModal);

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !confirmModal.classList.contains('hidden')) {
            hideConfirmModal();
        }
    });
</script>
@endpush
@endsection
