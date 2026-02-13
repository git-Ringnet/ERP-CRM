@extends('layouts.app')

@section('title', 'Chi tiết bảng giá')
@section('page-title', $supplierPriceList->name)

@section('content')
    <div class="space-y-4">
        <!-- Header Info -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-lg font-semibold text-gray-900">{{ $supplierPriceList->code }}</span>
                        @if($supplierPriceList->is_active)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt động</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Tạm dừng</span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-600 space-y-1">
                        <div><i class="fas fa-building w-5"></i> {{ $supplierPriceList->supplier->name }}</div>
                        <div><i class="fas fa-dollar-sign w-5"></i> {{ $supplierPriceList->currency }}
                            @if($supplierPriceList->exchange_rate != 1)
                                (Tỷ giá: {{ number_format($supplierPriceList->exchange_rate, 0) }} VND)
                            @endif
                        </div>
                        <div><i class="fas fa-tag w-5"></i> {{ $supplierPriceList->price_type_label }}</div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="openPricingConfigModal()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        <i class="fas fa-calculator mr-2"></i>Cấu hình giá
                    </button>
                    <button onclick="openApplyPricesModal()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-sync-alt mr-2"></i>Áp dụng giá
                    </button>
                    <a href="{{ route('supplier-price-lists.index') }}"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-arrow-left mr-2"></i>Quay lại
                    </a>
                </div>
            </div>
        </div>

        <!-- Pricing Configuration Summary -->
        @if($supplierPriceList->supplier_discount_percent > 0 || $supplierPriceList->margin_percent > 0 || $supplierPriceList->shipping_percent > 0 || $supplierPriceList->shipping_fixed > 0)
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg shadow-sm p-4 border border-purple-200">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-medium text-purple-900 mb-1">
                        <i class="fas fa-calculator mr-2"></i>Công thức tính giá bán
                    </h4>
                    <p class="text-sm text-purple-700">{{ $supplierPriceList->pricing_formula_description }}</p>
                </div>
                <div class="flex gap-4 text-sm">
                    @if($supplierPriceList->supplier_discount_percent > 0)
                    <div class="text-center">
                        <div class="text-lg font-bold text-green-600">-{{ $supplierPriceList->supplier_discount_percent }}%</div>
                        <div class="text-gray-500">CK NCC</div>
                    </div>
                    @endif
                    @if($supplierPriceList->margin_percent > 0)
                    <div class="text-center">
                        <div class="text-lg font-bold text-blue-600">+{{ $supplierPriceList->margin_percent }}%</div>
                        <div class="text-gray-500">Margin</div>
                    </div>
                    @endif
                    @if($supplierPriceList->shipping_percent > 0 || $supplierPriceList->shipping_fixed > 0)
                    <div class="text-center">
                        <div class="text-lg font-bold text-orange-600">
                            @if($supplierPriceList->shipping_percent > 0)+{{ $supplierPriceList->shipping_percent }}%@endif
                            @if($supplierPriceList->shipping_fixed > 0)+${{ number_format($supplierPriceList->shipping_fixed, 0) }}@endif
                        </div>
                        <div class="text-gray-500">Ship</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Search & Filter -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form method="GET" class="flex flex-col md:flex-row gap-3 items-end">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm SKU, tên sản phẩm, sheet source..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="w-full md:w-48">
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }} class="break-normal">
                                {{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm whitespace-nowrap">
                    <i class="fas fa-search mr-1"></i>Tìm
                </button>
            </form>
        </div>

        <!-- Items Table -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-4 py-3 border-b bg-gray-50">
                <span class="font-medium text-sm">{{ number_format($items->total()) }} sản phẩm</span>
            </div>
            <div class="overflow-x-auto overflow-y-auto max-h-[60vh]">
                <table class="w-full text-sm min-w-max">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap min-w-[120px]">SKU</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap min-w-[200px]">Tên sản phẩm</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap min-w-[120px]">Danh mục</th>
                            @foreach($priceColumns as $col)
                                <th class="px-3 py-2 text-right font-medium text-gray-500 whitespace-nowrap min-w-[110px]">
                                    {{ $col['label'] }}
                                </th>
                            @endforeach
                            <th class="px-3 py-2 text-right font-medium text-purple-600 bg-purple-50 whitespace-nowrap min-w-[130px]">
                                <i class="fas fa-calculator mr-1"></i>Giá bán
                            </th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500 whitespace-nowrap min-w-[100px]">Sheet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @php
                            $currency = $supplierPriceList->currency;
                            $symbol = match($currency) {
                                'USD' => '$',
                                'EUR' => '€',
                                'VND' => '₫',
                                default => $currency . ' '
                            };
                            $isVnd = $currency === 'VND';

                            $exchangeRate = $supplierPriceList->exchange_rate ?? 1;
                            $hasFormula = $supplierPriceList->supplier_discount_percent > 0 || 
                                          $supplierPriceList->margin_percent > 0 || 
                                          $supplierPriceList->shipping_percent > 0 || 
                                          $supplierPriceList->shipping_fixed > 0;
                        @endphp
                        @forelse($items as $item)
                            @php
                                // Calculate final price using formula
                                $basePrice = $item->list_price ?? 0;
                                $finalPriceData = $basePrice > 0 ? $supplierPriceList->calculateFinalPrice($basePrice) : null;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-mono text-blue-600 whitespace-nowrap">
                                    {{ $item->sku }}
                                </td>
                                <td class="px-3 py-2 min-w-[200px]">
                                    <div class="font-medium" title="{{ $item->product_name }}">
                                        {{ $item->product_name }}</div>
                                    @if($item->description)
                                        <div class="text-gray-400 text-xs" title="{{ $item->description }}">
                                            {{ Str::limit($item->description, 50) }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-600">
                                    <div class="text-xs">{{ $item->category ?? '-' }}</div>
                                </td>
                                @foreach($priceColumns as $col)
                                    @php
                                        $priceValue = null;
                                        if ($col['is_custom']) {
                                            // Lấy giá từ extra_data cho custom columns
                                            $priceValue = $item->extra_data['prices'][$col['key']] ?? null;
                                        } else {
                                            // Lấy giá từ cột cố định
                                            $priceValue = $item->{$col['key']} ?? null;
                                        }
                                        // Ép kiểu về float để tránh lỗi number_format
                                        $priceValue = $priceValue ? (float) $priceValue : null;
                                        $isListPrice = $col['key'] === 'list_price';
                                    @endphp
                                    <td class="px-3 py-2 text-right whitespace-nowrap {{ $isListPrice ? 'font-medium text-gray-600' : 'text-gray-500 text-xs' }}">
                                        @if($priceValue)
                                            @if($isVnd)
                                                {{ number_format($priceValue, 0) }}₫
                                            @else
                                                {{ $symbol }}{{ number_format($priceValue, 2) }}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right whitespace-nowrap bg-purple-50">
                                    @if($finalPriceData)
                                        <div class="font-bold text-purple-700">{{ number_format($finalPriceData['final_price_vnd'], 0) }}₫</div>
                                        @if(!$isVnd)
                                            <div class="text-xs text-gray-500">{{ $symbol }}{{ number_format($finalPriceData['final_price'], 2) }}</div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-400 whitespace-nowrap">
                                    <div class="text-xs">{{ $item->source_sheet }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 5 + count($priceColumns) }}" class="px-4 py-8 text-center text-gray-500">Không có sản phẩm nào</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())
                <div class="px-4 py-3 border-t text-sm">
                    {{ $items->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

        <!-- Import Log -->
        @if($supplierPriceList->import_log)
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="font-medium text-gray-900 text-sm"><i class="fas fa-history mr-2"></i>Thông tin Import</h4>
                    @if(($supplierPriceList->import_log['skipped'] ?? 0) > 0)
                        <button onclick="document.getElementById('skippedItemsModal').classList.remove('hidden')" 
                                class="text-xs text-red-600 hover:text-red-800 underline">
                            Xem chi tiết bỏ qua ({{ $supplierPriceList->import_log['skipped'] }})
                        </button>
                    @endif
                </div>
                <div class="text-xs text-gray-600">
                    <span>Ngày:
                        {{ \Carbon\Carbon::parse($supplierPriceList->import_log['imported_at'])->format('d/m/Y H:i') }}</span>
                    <span class="mx-2">|</span>
                    <span>Tổng: {{ number_format($supplierPriceList->import_log['total_items'] ?? 0) }}</span>
                    <span class="mx-2">|</span>
                    <span>Tạo mới: {{ number_format($supplierPriceList->import_log['created'] ?? 0) }}</span>
                    <span class="mx-2">|</span>
                    <span class="{{ ($supplierPriceList->import_log['skipped'] ?? 0) > 0 ? 'text-red-600 font-medium' : '' }}">
                        Bỏ qua: {{ number_format($supplierPriceList->import_log['skipped'] ?? 0) }}
                    </span>
                </div>
            </div>

            <!-- Skipped Items Modal -->
            <div id="skippedItemsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] flex flex-col">
                    <div class="px-6 py-4 border-b flex justify-between items-center bg-red-50">
                        <h3 class="text-lg font-semibold text-red-800">
                            <i class="fas fa-exclamation-circle mr-2"></i>Chi tiết các mục bị bỏ qua
                        </h3>
                        <button onclick="document.getElementById('skippedItemsModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto">
                        @foreach($supplierPriceList->import_log['sheets'] ?? [] as $sheet)
                            @if(!empty($sheet['skipped_details']) || !empty($sheet['skipped_reason']))
                                <div class="mb-4 last:mb-0">
                                    <h4 class="font-medium text-gray-900 mb-2 bg-gray-100 p-2 rounded">
                                        Sheet: {{ $sheet['name'] }}
                                        @if(!empty($sheet['skipped_reason']))
                                            <span class="ml-2 text-red-600 text-sm">- {{ $sheet['skipped_reason'] }}</span>
                                        @endif
                                    </h4>
                                    
                                    @if(!empty($sheet['skipped_details']))
                                        <div class="border rounded-lg overflow-hidden">
                                            <table class="w-full text-sm">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left w-16">Dòng</th>
                                                        <th class="px-3 py-2 text-left">SKU</th>
                                                        <th class="px-3 py-2 text-left">Lý do</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y">
                                                    @foreach($sheet['skipped_details'] as $detail)
                                                        <tr>
                                                            <td class="px-3 py-2 text-gray-500">{{ $detail['row'] }}</td>
                                                            <td class="px-3 py-2 font-mono">{{ $detail['sku'] ?? '-' }}</td>
                                                            <td class="px-3 py-2 text-red-600">{{ $detail['reason'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
                        <button onclick="document.getElementById('skippedItemsModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

<!-- Apply Prices Modal -->
<div id="applyPricesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-sync-alt mr-2 text-blue-600"></i>Áp dụng giá vào kho
            </h3>
            <button onclick="closeApplyPricesModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[70vh]">
            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Thông tin bảng giá:</p>
                        <ul class="list-disc ml-4 space-y-1">
                            <li>Nhà cung cấp: <strong>{{ $supplierPriceList->supplier->name }}</strong></li>
                            <li>Tiền tệ: <strong>{{ $supplierPriceList->currency }}</strong></li>
                            <li>Tỷ giá: <strong>{{ number_format($supplierPriceList->exchange_rate, 0) }} VND</strong></li>
                            <li>Số sản phẩm: <strong>{{ number_format($supplierPriceList->items()->count()) }}</strong></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Options -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chọn loại giá áp dụng</label>
                    <select id="priceField" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="loadPreview()">
                        @foreach($priceColumns as $col)
                            <option value="{{ $col['key'] }}">{{ $col['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chế độ cập nhật</label>
                    <select id="updateMode" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="loadPreview()">
                        <option value="all">Cập nhật tất cả (ghi đè giá cũ)</option>
                        <option value="empty_only">Chỉ cập nhật SP chưa có giá</option>
                    </select>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="border rounded-lg">
                <div class="px-4 py-3 bg-gray-50 border-b flex justify-between items-center">
                    <span class="font-medium text-sm">Xem trước sản phẩm sẽ được cập nhật</span>
                    <button onclick="loadPreview()" class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-sync-alt mr-1"></i>Làm mới
                    </button>
                </div>
                <div id="previewContent" class="p-4">
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                        <p>Đang tải...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
            <div id="previewStats" class="text-sm text-gray-600"></div>
            <div class="flex gap-2">
                <button onclick="closeApplyPricesModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Hủy
                </button>
                <button onclick="applyPrices()" id="applyBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50" disabled>
                    <i class="fas fa-check mr-2"></i>Áp dụng giá
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const priceListId = {{ $supplierPriceList->id }};

function openApplyPricesModal() {
    document.getElementById('applyPricesModal').classList.remove('hidden');
    loadPreview();
}

function closeApplyPricesModal() {
    document.getElementById('applyPricesModal').classList.add('hidden');
}

function loadPreview() {
    const priceField = document.getElementById('priceField').value;
    const updateMode = document.getElementById('updateMode').value;
    
    document.getElementById('previewContent').innerHTML = `
        <div class="text-center text-gray-500 py-8">
            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
            <p>Đang tải...</p>
        </div>
    `;
    document.getElementById('applyBtn').disabled = true;
    document.getElementById('previewStats').innerHTML = '';

    fetch(`/supplier-price-lists/${priceListId}/preview-apply?price_field=${priceField}&update_mode=${updateMode}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
            if (data.success) {
                renderPreview(data);
            } else {
                document.getElementById('previewContent').innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Lỗi: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(err => {
            document.getElementById('previewContent').innerHTML = `
                <div class="text-center text-red-500 py-8">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>Lỗi kết nối</p>
                </div>
            `;
        });
}

function renderPreview(data) {
    const { preview, match_count, total_price_items, exchange_rate, currency } = data;
    
    document.getElementById('previewStats').innerHTML = `
        <span class="text-green-600 font-medium">${match_count}</span> sản phẩm trong kho khớp 
        / ${total_price_items} sản phẩm trong bảng giá
    `;

    if (preview.length === 0) {
        document.getElementById('previewContent').innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
                <p>Không tìm thấy sản phẩm nào trong kho khớp với bảng giá này.</p>
                <p class="text-sm mt-2">Hãy chắc chắn SKU trong bảng giá trùng với SKU sản phẩm trong kho.</p>
            </div>
        `;
        document.getElementById('applyBtn').disabled = true;
        return;
    }

    let html = `
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">SKU</th>
                        <th class="px-3 py-2 text-left">Tên sản phẩm</th>
                        <th class="px-3 py-2 text-left">Kho</th>
                        <th class="px-3 py-2 text-right">Giá hiện tại (${currency})</th>
                        <th class="px-3 py-2 text-center"></th>
                        <th class="px-3 py-2 text-right">Giá mới (${currency})</th>
                        <th class="px-3 py-2 text-right">SL</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
    `;

    preview.forEach(item => {
        const currentCost = item.current_cost ? parseFloat(item.current_cost).toLocaleString() : '-';
        const newCost = parseFloat(item.new_cost).toLocaleString();
        const hasChange = item.current_cost != item.new_cost;
        
        html += `
            <tr class="${hasChange ? 'bg-yellow-50' : ''}">
                <td class="px-3 py-2 font-mono text-blue-600">${item.sku}</td>
                <td class="px-3 py-2">${item.product_name}</td>
                <td class="px-3 py-2 text-gray-600">${item.warehouse}</td>
                <td class="px-3 py-2 text-right ${hasChange ? 'text-red-500 line-through' : ''}">${currentCost}</td>
                <td class="px-3 py-2 text-center text-gray-400"><i class="fas fa-arrow-right"></i></td>
                <td class="px-3 py-2 text-right font-medium text-green-600">${newCost}</td>
                <td class="px-3 py-2 text-right">${item.quantity}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    if (preview.length >= 100) {
        html += `<p class="text-center text-gray-500 text-sm mt-2">Hiển thị 100 sản phẩm đầu tiên...</p>`;
    }

    document.getElementById('previewContent').innerHTML = html;
    document.getElementById('applyBtn').disabled = false;
}

function applyPrices() {
    const priceField = document.getElementById('priceField').value;
    const updateMode = document.getElementById('updateMode').value;
    const btn = document.getElementById('applyBtn');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';

    fetch(`/supplier-price-lists/${priceListId}/apply-prices`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            price_field: priceField,
            update_mode: updateMode
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeApplyPricesModal();
            // Show success notification
            alert(data.message);
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>Áp dụng giá';
        }
    })
    .catch(err => {
        alert('Lỗi kết nối');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check mr-2"></i>Áp dụng giá';
    });
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeApplyPricesModal();
    }
});

// Close modal on backdrop click
document.getElementById('applyPricesModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeApplyPricesModal();
    }
});

// Auto-open modal if URL has #apply hash
if (window.location.hash === '#apply') {
    openApplyPricesModal();
}

// ===== PRICING CONFIG MODAL =====
function openPricingConfigModal() {
    document.getElementById('pricingConfigModal').classList.remove('hidden');
    calculatePreviewPrice();
}

function closePricingConfigModal() {
    document.getElementById('pricingConfigModal').classList.add('hidden');
}

function calculatePreviewPrice() {
    const isVnd = '{{ $supplierPriceList->currency }}' === 'VND';
    const exchangeRate = {{ $supplierPriceList->exchange_rate ?: 1 }};
    
    // Base Price Input (If VND, treat as VND. If USD, treat as USD)
    const basePriceInput = parseFloat(document.getElementById('previewBasePrice').value) || (isVnd ? 25000000 : 1000);
    
    // Configs
    const discountPercent = parseFloat(document.getElementById('supplierDiscountPercent').value) || 0;
    const marginPercent = parseFloat(document.getElementById('marginPercent').value) || 0;
    const shippingPercent = parseFloat(document.getElementById('shippingPercent').value) || 0;
    const shippingFixed = parseFloat(document.getElementById('shippingFixed').value) || 0;
    const otherFees = parseFloat(document.getElementById('otherFees').value) || 0;

    let finalPriceVnd = 0;
    let finalPriceUsd = 0;

    // Display updates
    const formatCurrency = (val, currency) => {
        if(currency === 'VND') return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val);
    };

    if (isVnd) {
        // --- LOGIC CHO VND ---
        // Base Price is VND
        const basePrice = basePriceInput;
        
        // 1. Discount
        const discountAmount = basePrice * (discountPercent / 100);
        const afterDiscount = basePrice - discountAmount;
        
        // 2. Margin (on After Discount)
        const marginAmount = afterDiscount * (marginPercent / 100);
        
        // 3. Shipping (% on After Discount + Fixed USD converted to VND)
        const shippingFromPercent = afterDiscount * (shippingPercent / 100);
        const shippingFixedVnd = shippingFixed * exchangeRate;
        const totalShipping = shippingFromPercent + shippingFixedVnd;
        
        // 4. Other Fees (USD converted to VND)
        const otherFeesVnd = otherFees * exchangeRate;
        
        // Final
        finalPriceVnd = afterDiscount + marginAmount + totalShipping + otherFeesVnd;
        finalPriceUsd = finalPriceVnd / exchangeRate; // Approx

        // Render Params
        document.getElementById('previewBasePriceDisplay').textContent = formatCurrency(basePrice, 'VND');
        document.getElementById('previewDiscount').textContent = `- ${formatCurrency(discountAmount, 'VND')}`;
        document.getElementById('previewAfterDiscount').textContent = formatCurrency(afterDiscount, 'VND');
        document.getElementById('previewMargin').textContent = `+ ${formatCurrency(marginAmount, 'VND')}`;
        document.getElementById('previewShipping').textContent = `+ ${formatCurrency(totalShipping, 'VND')} (gồm $${shippingFixed} fix)`;
        document.getElementById('previewOther').textContent = `+ ${formatCurrency(otherFeesVnd, 'VND')}`;
        document.getElementById('previewFinal').textContent = formatCurrency(finalPriceVnd, 'VND');
        // Final VND row - hide or show USD equiv?
        document.getElementById('previewFinalVnd').textContent = `~ ${formatCurrency(finalPriceUsd, 'USD')}`;
        document.getElementById('previewFinalLabel').textContent = `Quy đổi USD (Tỷ giá ${exchangeRate}):`;

    } else {
        // --- LOGIC CHO USD (Giữ nguyên hoặc tinh chỉnh) ---
        const basePrice = basePriceInput;
        
        const discountAmount = basePrice * (discountPercent / 100);
        const afterDiscount = basePrice - discountAmount;
        const marginAmount = afterDiscount * (marginPercent / 100);
        const shippingFromPercent = afterDiscount * (shippingPercent / 100);
        const totalShipping = shippingFromPercent + shippingFixed;
        const finalPrice = afterDiscount + marginAmount + totalShipping + otherFees;
        finalPriceVnd = finalPrice * exchangeRate;

        // Render Params
        document.getElementById('previewBasePriceDisplay').textContent = formatCurrency(basePrice, 'USD');
        document.getElementById('previewDiscount').textContent = `- ${formatCurrency(discountAmount, 'USD')}`;
        document.getElementById('previewAfterDiscount').textContent = formatCurrency(afterDiscount, 'USD');
        document.getElementById('previewMargin').textContent = `+ ${formatCurrency(marginAmount, 'USD')}`;
        document.getElementById('previewShipping').textContent = `+ ${formatCurrency(totalShipping, 'USD')}`;
        document.getElementById('previewOther').textContent = `+ ${formatCurrency(otherFees, 'USD')}`;
        document.getElementById('previewFinal').textContent = formatCurrency(finalPrice, 'USD');
        document.getElementById('previewFinalVnd').textContent = formatCurrency(finalPriceVnd, 'VND');
        document.getElementById('previewFinalLabel').textContent = `Thành tiền VND (Tỷ giá ${exchangeRate}):`;
    }
}

function savePricingConfig() {
    const btn = document.getElementById('savePricingBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...';

    const data = {
        supplier_discount_percent: parseFloat(document.getElementById('supplierDiscountPercent').value) || 0,
        margin_percent: parseFloat(document.getElementById('marginPercent').value) || 0,
        shipping_percent: parseFloat(document.getElementById('shippingPercent').value) || 0,
        shipping_fixed: parseFloat(document.getElementById('shippingFixed').value) || 0,
        other_fees: parseFloat(document.getElementById('otherFees').value) || 0,
    };

    fetch(`/supplier-price-lists/${priceListId}/update-pricing-config`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(async res => {
        if (!res.ok) {
            const text = await res.text();
            throw new Error(`Server Error: ${res.status} - ${text.substring(0, 100)}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            closePricingConfigModal();
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-2"></i>Lưu cấu hình';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Lưu cấu hình';
    });
}

document.getElementById('pricingConfigModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePricingConfigModal();
    }
});
</script>

<!-- Pricing Config Modal -->
<div id="pricingConfigModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[85vh] overflow-hidden">
        <div class="px-6 py-4 border-b bg-purple-50 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-purple-900">
                <i class="fas fa-calculator mr-2"></i>Cấu hình công thức tính giá bán
            </h3>
            <button onclick="closePricingConfigModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[70vh]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left: Configuration Form -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900 border-b pb-2">Thiết lập công thức</h4>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-percent text-green-600 mr-1"></i>Chiết khấu từ NCC (%)
                        </label>
                        <input type="number" id="supplierDiscountPercent" step="0.01" min="0" max="100"
                               value="{{ $supplierPriceList->supplier_discount_percent ?? 0 }}"
                               onchange="calculatePreviewPrice()" oninput="calculatePreviewPrice()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Chiết khấu NCC cho đại lý, trừ vào giá gốc</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-chart-line text-blue-600 mr-1"></i>Margin/Markup (%)
                        </label>
                        <input type="number" id="marginPercent" step="0.01" min="0"
                               value="{{ $supplierPriceList->margin_percent ?? 0 }}"
                               onchange="calculatePreviewPrice()" oninput="calculatePreviewPrice()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Lợi nhuận thêm vào giá sau chiết khấu</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-truck text-orange-600 mr-1"></i>Phí ship (%)
                            </label>
                            <input type="number" id="shippingPercent" step="0.01" min="0"
                                   value="{{ $supplierPriceList->shipping_percent ?? 0 }}"
                                   onchange="calculatePreviewPrice()" oninput="calculatePreviewPrice()"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-truck text-orange-600 mr-1"></i>Phí ship ({{ $supplierPriceList->currency }})
                            </label>
                            <input type="number" id="shippingFixed" step="0.01" min="0"
                                   value="{{ $supplierPriceList->shipping_fixed ?? 0 }}"
                                   onchange="calculatePreviewPrice()" oninput="calculatePreviewPrice()"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-money-bill text-gray-600 mr-1"></i>Phí khác ({{ $supplierPriceList->currency }})
                        </label>
                        <input type="number" id="otherFees" step="0.01" min="0"
                               value="{{ $supplierPriceList->other_fees ?? 0 }}"
                               onchange="calculatePreviewPrice()" oninput="calculatePreviewPrice()"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1">Thuế, phí hải quan, phí khác...</p>
                    </div>
                </div>

                <!-- Right: Preview Calculator -->
                <div>
                    <h4 class="font-medium text-gray-900 border-b pb-2 mb-4">Xem trước giá bán</h4>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="mb-3">
                            <label class="block text-sm text-gray-600 mb-1">Giá gốc thử ({{ $supplierPriceList->currency }})</label>
                            <input type="number" id="previewBasePrice" value="{{ $supplierPriceList->currency == 'VND' ? 25000000 : 1000 }}" step="1" min="0"
                                   onchange="calculatePreviewPrice()" oninput="calculatePreviewPrice()"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-lg font-mono">
                        </div>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Giá gốc:</span>
                                <span class="font-mono">$<span id="previewBasePriceDisplay">1,000.00</span></span>
                            </div>
                            <div class="flex justify-between text-green-600">
                                <span>Chiết khấu NCC:</span>
                                <span class="font-mono" id="previewDiscount">- $0.00</span>
                            </div>
                            <div class="flex justify-between border-t pt-1">
                                <span class="text-gray-600">Sau CK:</span>
                                <span class="font-mono" id="previewAfterDiscount">$1,000.00</span>
                            </div>
                            <div class="flex justify-between text-blue-600">
                                <span>+ Margin:</span>
                                <span class="font-mono" id="previewMargin">+ $0.00</span>
                            </div>
                            <div class="flex justify-between text-orange-600">
                                <span>+ Shipping:</span>
                                <span class="font-mono" id="previewShipping">+ $0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>+ Phí khác:</span>
                                <span class="font-mono" id="previewOther">+ $0.00</span>
                            </div>
                            <div class="flex justify-between border-t-2 border-gray-300 pt-2 text-lg font-bold">
                                <span>Giá bán:</span>
                                <span class="text-primary font-mono" id="previewFinal">$1,000.00</span>
                            </div>
                            <div class="flex justify-between text-purple-600">
                                <span id="previewFinalLabel">Tỷ giá {{ number_format($supplierPriceList->exchange_rate, 0) }}:</span>
                                <span class="font-mono font-bold" id="previewFinalVnd">26,000,000 VND</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 bg-blue-50 rounded-lg p-3 text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Công thức:</strong><br>
                        Giá bán = (Giá gốc - CK%) + Margin% + Ship + Phí khác
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-2">
            <button onclick="closePricingConfigModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Hủy
            </button>
            <button onclick="savePricingConfig()" id="savePricingBtn" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                <i class="fas fa-save mr-2"></i>Lưu cấu hình
            </button>
        </div>
    </div>
</div>
@endsection