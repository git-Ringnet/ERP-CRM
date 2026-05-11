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

            <div class="grid grid-cols-1 gap-8">
                @foreach($vendorGroups as $vId => $vendor)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden vendor-section" data-vendor-id="{{ $vId }}">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center font-bold">
                                {{ substr($vendor['name'], 0, 1) }}
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">{{ $vendor['name'] }}</h2>
                                <p class="text-xs text-gray-500">{{ count($vendor['products']) }} mặt hàng cần đặt</p>
                            </div>
                        </div>
                        <button type="button" onclick="preparePo('{{ $vId }}')" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors text-sm font-bold shadow-sm">
                            <i class="fas fa-plus mr-2"></i> Tạo PO cho Hãng này
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                                    <th class="px-6 py-3 w-10">
                                        <input type="checkbox" class="rounded text-teal-600 focus:ring-teal-500 vendor-check-all" data-vendor-id="{{ $vId }}">
                                    </th>
                                    <th class="px-6 py-3">Sản phẩm / Part Number</th>
                                    <th class="px-6 py-3 text-center">Đơn vị</th>
                                    <th class="px-6 py-3 text-center">Tổng Yêu cầu</th>
                                    <th class="px-6 py-3 text-center">Đã đặt</th>
                                    <th class="px-6 py-3 text-center">Còn thiếu</th>
                                    <th class="px-6 py-3 text-center">Đặt đợt này</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($vendor['products'] as $pIdx => $product)
                                @php
                                    $remaining = $product['requested'] - $product['ordered'];
                                @endphp
                                <tr class="hover:bg-teal-50/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" class="rounded text-teal-600 focus:ring-teal-500 item-checkbox" 
                                            data-vendor-id="{{ $vId }}" 
                                            data-product-key="{{ $vId }}-{{ $pIdx }}">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-800">{{ $product['part_number'] }}</div>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($product['items'] as $source)
                                                @if($source['remaining'] > 0)
                                                <span class="text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded cursor-help" title="PR #{{ $source['pr_code'] }}: Yêu cầu {{ $source['quantity'] }}, Còn {{ $source['remaining'] }}">
                                                    #{{ $source['pr_code'] }} ({{ $source['remaining'] }})
                                                </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $product['unit'] ?: '-' }}</td>
                                    <td class="px-6 py-4 text-center font-medium">{{ $product['requested'] }}</td>
                                    <td class="px-6 py-4 text-center text-blue-600">{{ $product['ordered'] }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-red-500">{{ $remaining }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col gap-2">
                                            @foreach($product['items'] as $sourceIdx => $source)
                                                @if($source['remaining'] > 0)
                                                <div class="flex items-center gap-2 justify-center item-input-row hidden" data-product-key="{{ $vId }}-{{ $pIdx }}">
                                                    <span class="text-[10px] font-bold text-gray-400 w-12 text-right">#{{ $source['pr_code'] }}</span>
                                                    <input type="number" 
                                                        name="items_data[{{ $source['id'] }}]" 
                                                        value="{{ $source['remaining'] }}" 
                                                        max="{{ $source['remaining'] }}" 
                                                        step="0.01"
                                                        class="w-20 border-gray-300 rounded text-xs text-center focus:ring-teal-500 order-qty-input"
                                                        disabled
                                                        data-pr-item-id="{{ $source['id'] }}">
                                                </div>
                                                @endif
                                            @endforeach
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
            <div id="submitBar" class="hidden fixed bottom-6 left-1/2 transform -translate-x-1/2 w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-teal-100 p-4 z-40">
                <div class="flex items-center gap-6">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ghi chú cho Đơn hàng (PO)</label>
                        <input type="text" name="note" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Nhập ghi chú chung cho PO này...">
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 mb-1">Đang chọn <span id="selectedCount" class="font-bold text-teal-600">0</span> mặt hàng</p>
                        <button type="button" onclick="submitPo()" class="bg-teal-600 text-white px-8 py-2.5 rounded-xl hover:bg-teal-700 font-bold shadow-lg transition-all transform hover:scale-105">
                            XÁC NHẬN TẠO PO
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
    let currentVendorId = null;

    // Khi chọn checkbox của một item
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const vId = this.dataset.vendorId;
            const pKey = this.dataset.productKey;
            
            // Nếu chuyển sang Vendor khác, bỏ chọn tất cả vendor cũ
            if (currentVendorId && currentVendorId !== vId) {
                if (confirm('Bạn đang chọn mặt hàng từ Hãng khác. Chuyển sang Hãng này sẽ bỏ chọn các mặt hàng cũ?')) {
                    resetSelections();
                } else {
                    this.checked = false;
                    return;
                }
            }

            currentVendorId = vId;
            document.getElementById('selectedVendorId').value = vId;

            // Hiển thị/Ẩn các input số lượng tương ứng
            const inputRows = document.querySelectorAll(`.item-input-row[data-product-key="${pKey}"]`);
            inputRows.forEach(row => {
                row.classList.toggle('hidden', !this.checked);
                const input = row.querySelector('input');
                input.disabled = !this.checked;
            });

            updateSubmitBar();
        });
    });

    // Chọn tất cả trong một Vendor
    document.querySelectorAll('.vendor-check-all').forEach(cb => {
        cb.addEventListener('change', function() {
            const vId = this.dataset.vendorId;
            
            if (currentVendorId && currentVendorId !== vId) {
                if (!confirm('Bạn đang chọn mặt hàng từ Hãng khác. Chuyển sang Hãng này sẽ bỏ chọn các mặt hàng cũ?')) {
                    this.checked = false;
                    return;
                }
                resetSelections();
            }

            const itemCbs = document.querySelectorAll(`.item-checkbox[data-vendor-id="${vId}"]`);
            itemCbs.forEach(itemCb => {
                itemCb.checked = this.checked;
                // Kích hoạt sự kiện change thủ công
                itemCb.dispatchEvent(new Event('change'));
            });
        });
    });

    function resetSelections() {
        document.querySelectorAll('.item-checkbox, .vendor-check-all').forEach(cb => cb.checked = false);
        document.querySelectorAll('.item-input-row').forEach(row => row.classList.add('hidden'));
        document.querySelectorAll('.order-qty-input').forEach(input => input.disabled = true);
        currentVendorId = null;
        updateSubmitBar();
    }

    function updateSubmitBar() {
        const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
        const bar = document.getElementById('submitBar');
        const countSpan = document.getElementById('selectedCount');
        
        if (checkedCount > 0) {
            bar.classList.remove('hidden');
            countSpan.innerText = checkedCount;
        } else {
            bar.classList.add('hidden');
            currentVendorId = null;
        }
    }

    function submitPo() {
        const form = document.getElementById('mainPoForm');
        const btn = event.currentTarget;
        const items = document.querySelectorAll('.order-qty-input:not(:disabled)');
        
        // Validation: Check if vendor_id is valid (numeric)
        const vId = document.getElementById('selectedVendorId').value;
        if (!vId || isNaN(vId)) {
            alert('Hãng này chưa được liên kết với Nhà cung cấp trong hệ thống. Vui lòng kiểm tra lại dữ liệu hoặc tạo Nhà cung cấp tương ứng.');
            return;
        }

        // Xóa các input cũ nếu có (trường hợp nhấn nhiều lần)
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
            alert('Vui lòng nhập số lượng đặt hàng cho ít nhất một mặt hàng.');
            return;
        }

        if (confirm(`Tạo Đơn đặt hàng (PO) cho ${idx} mặt hàng này?`)) {
            // Hiển thị trạng thái đang xử lý
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> ĐANG XỬ LÝ...';
            
            // Sử dụng native submit để tránh xung đột với các library khác
            HTMLFormElement.prototype.submit.call(form);
        }
    }

    function preparePo(vId) {
        // Tự động check-all cho vendor này
        const checkAll = document.querySelector(`.vendor-check-all[data-vendor-id="${vId}"]`);
        if (checkAll) {
            checkAll.checked = true;
            checkAll.dispatchEvent(new Event('change'));
            // Cuộn xuống thanh submit
            document.getElementById('submitBar').scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>
@endpush

<style>
    .vendor-section:has(.item-checkbox:checked) {
        border-color: #0d9488;
        ring: 2px;
        ring-color: #0d9488;
    }
</style>
@endsection
