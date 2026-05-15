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

            <div class="grid grid-cols-1 gap-8" x-data="{ expandedSo: null, currentVendor: null }">
                @foreach($vendorGroups as $vId => $vendor)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden vendor-section" data-vendor-id="{{ $vId }}">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center font-bold">
                                {{ substr($vendor['name'], 0, 1) }}
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">{{ $vendor['name'] }}</h2>
                                <p class="text-xs text-gray-500">{{ count($vendor['sales_orders']) }} Sales Order cần xử lý</p>
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
                                    <th class="px-6 py-3 w-10 text-center">
                                        <input type="checkbox" class="rounded text-teal-600 focus:ring-teal-500 vendor-check-all" data-vendor-id="{{ $vId }}">
                                    </th>
                                    <th class="px-6 py-3">Mã SO</th>
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
                                <tr class="hover:bg-teal-50/30 transition-colors cursor-pointer" @click="expandedSo === '{{ $soId }}' ? expandedSo = null : expandedSo = '{{ $soId }}'">
                                    <td class="px-6 py-4 text-center" @click.stop>
                                        <input type="checkbox" class="rounded text-teal-600 focus:ring-teal-500 so-checkbox" 
                                            data-vendor-id="{{ $vId }}" 
                                            data-so-id="{{ $soId }}">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <i class="fas fa-chevron-right text-gray-400 text-[10px] transition-transform duration-200" :class="expandedSo === '{{ $soId }}' ? 'rotate-90 text-teal-600' : ''"></i>
                                            <div>
                                                <div class="font-bold text-gray-800">{{ $so['code'] }}</div>
                                                <div class="text-[10px] text-gray-400">{{ $so['pr_code'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-teal-700">
                                        ${{ number_format($so['total_usd'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-blue-600 text-sm">{{ number_format($so['ordered'], 0) }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-red-500 text-sm">{{ number_format($soRemaining, 0) }}</td>
                                </tr>

                                <!-- Expandable Product Detail Row -->
                                <tr x-show="expandedSo === '{{ $soId }}'" x-cloak class="bg-gray-50/50">
                                    <td colspan="5" class="px-8 py-4">
                                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
                                            <table class="w-full text-sm">
                                                <thead class="bg-gray-100">
                                                    <tr class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">
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
                                                            <input type="checkbox" class="rounded text-teal-600 focus:ring-teal-500 item-checkbox" 
                                                                data-vendor-id="{{ $vId }}" 
                                                                data-so-id="{{ $soId }}"
                                                                data-product-id="{{ $product['id'] }}">
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div class="font-medium text-gray-800">{{ $product['part_number'] }}</div>
                                                            <div class="text-[10px] text-gray-400">{{ $product['unit'] ?: '-' }}</div>
                                                        </td>
                                                        <td class="px-4 py-3 text-center">{{ number_format($product['requested'], 0) }}</td>
                                                        <td class="px-4 py-3 text-center text-gray-500">
                                                            ${{ number_format($product['unit_price_usd'], 2) }}
                                                            <input type="number" 
                                                                name="items_data[{{ $product['id'] }}]" 
                                                                value="{{ $product['remaining'] }}" 
                                                                class="order-qty-input hidden"
                                                                disabled
                                                                data-pr-item-id="{{ $product['id'] }}">
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <button type="button" onclick="cancelPrItem({{ $product['id'] }}, '{{ addslashes($product['part_number']) }}')" 
                                                                class="text-red-400 hover:text-red-600 transition-colors" title="Hủy sản phẩm">
                                                                <i class="fas fa-times-circle text-sm"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
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
            <div id="submitBar" class="hidden fixed bottom-6 left-1/2 transform -translate-x-1/2 w-full max-w-5xl bg-white rounded-2xl shadow-2xl border border-teal-100 p-5 z-40">
                <div class="flex flex-col md:flex-row items-end gap-4">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Ghi chú cho Đơn hàng (PO)</label>
                        <input type="text" name="note" class="w-full border-gray-300 rounded-lg text-sm px-4 py-2.5 focus:ring-teal-500 focus:border-teal-500" placeholder="Nhập ghi chú chung cho PO này...">
                    </div>
                    <input type="hidden" name="currency_id" value="{{ $baseCurrencyId }}">
                    <input type="hidden" name="exchange_rate" value="1">

                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <div class="text-right hidden md:block">
                            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1">Đang chọn <span id="selectedCount" class="text-teal-600">0</span> items</p>
                            <button type="button" onclick="submitPo()" class="bg-teal-600 text-white px-8 py-3 rounded-xl hover:bg-teal-700 font-bold shadow-lg transition-all transform hover:scale-[1.02] active:scale-95 whitespace-nowrap">
                                XÁC NHẬN TẠO PO
                            </button>
                        </div>
                        <button type="button" onclick="submitPo()" class="md:hidden w-full bg-teal-600 text-white py-3 rounded-xl hover:bg-teal-700 font-bold shadow-lg transition-all">
                            XÁC NHẬN TẠO PO (<span id="selectedCountMobile">0</span>)
                        </button>
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
            <i class="fas fa-chevron-right text-[10px] transition-transform duration-200" :class="showCancelled ? 'rotate-90' : ''"></i>
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
                            <span class="ml-2 px-1.5 py-0.5 text-[9px] font-bold bg-red-100 text-red-600 rounded">ĐÃ HỦY</span>
                        </td>
                        <td class="px-6 py-3">{{ $ci->vendor?->name ?? $ci->vendor }}</td>
                        <td class="px-6 py-3">{{ $ci->saleOrderRequest?->sale?->code ?? $ci->saleOrderRequest?->code ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-center">{{ $ci->quantity + 0 }}</td>
                        <td class="px-6 py-3 text-center">
                            <form action="{{ route('purchase-requests.items.restore', $ci->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('Khôi phục sản phẩm này về danh sách cần đặt?')" 
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
</div>

@push('scripts')
<script>
    let currentVendorId = null;

    // Sử dụng Event Delegation để xử lý tất cả checkbox
    document.addEventListener('change', async function(e) {
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
    document.addEventListener('click', function(e) {
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
        const countSpanMobile = document.getElementById('selectedCountMobile');
        
        if (checkedCount > 0) {
            bar.classList.remove('hidden');
            if (countSpan) countSpan.innerText = checkedCount;
            if (countSpanMobile) countSpanMobile.innerText = checkedCount;
        } else {
            bar.classList.add('hidden');
            currentVendorId = null;
        }
    }

    function submitPo() {
        const form = document.getElementById('mainPoForm');
        const btn = document.querySelector('button[onclick="submitPo()"]');
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
                HTMLFormElement.prototype.submit.call(form);
            }
        });
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
