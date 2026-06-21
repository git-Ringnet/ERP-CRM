@extends('layouts.app')

@section('title', 'Tạo đơn mua hàng')
@section('page-title', 'Tạo đơn mua hàng mới')

@section('content')
<div class="w-full bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-shopping-cart text-purple-500 mr-2"></i>Thông tin đơn mua hàng (PO)
        </h2>
        <a href="{{ route('purchase-orders.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
    
    <form action="{{ route('purchase-orders.store') }}" method="POST" id="orderForm" class="p-4">
        @csrf
        @if($quotation)
            <input type="hidden" name="supplier_quotation_id" value="{{ $quotation->id }}">
        @endif
        
        @if($quotation)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-blue-800"><i class="fas fa-info-circle mr-2"></i> Tạo PO từ báo giá: <strong>{{ $quotation->code }}</strong> - {{ $quotation->supplier->name }}</p>
        </div>
        @endif

        <!-- Thông tin chung -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã PO <span class="text-red-500">*</span></label>
                <input type="text" name="code" id="poCodeInput" value="{{ old('code', $code) }}" required readonly
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CPQ đơn hàng <span class="text-red-500">*</span></label>
                <input type="text" name="cpq_number" value="{{ old('cpq_number') }}" placeholder="CPQ/non" required
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhà cung cấp <span class="text-red-500">*</span></label>
                <select name="supplier_id" id="supplierSelect" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg" {{ $quotation ? 'disabled' : '' }}>
                    <option value="">Tìm và chọn nhà cung cấp...</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" data-name="{{ $supplier->name }}" {{ ($quotation && $quotation->supplier_id == $supplier->id) ? 'selected' : '' }}>{{ $supplier->code }} - {{ $supplier->name }}</option>
                    @endforeach
                </select>
                @if($quotation)
                    <input type="hidden" name="supplier_id" value="{{ $quotation->supplier_id }}">
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày tạo <span class="text-red-500">*</span></label>
                <input type="date" name="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" required
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>
        </div>

        <!-- Tiền tệ & Tỷ giá -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tiền tệ <span class="text-red-500">*</span></label>
                <select name="currency_id" id="currencySelect" onchange="onCurrencyChange()" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" 
                            data-is-base="{{ $currency->is_base ? '1' : '0' }}"
                            data-symbol="{{ $currency->symbol }}"
                            {{ old('currency_id', $baseCurrencyId) == $currency->id ? 'selected' : '' }}>
                            {{ $currency->code }} - {{ $currency->name_vi }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div id="exchangeRateGroup" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ giá (1 ngoại tệ = ? VND)</label>
                <input type="number" name="exchange_rate" id="exchangeRateInput" step="0.01" value="{{ old('exchange_rate', 1) }}" 
                    onchange="calculateTotal()" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày giao dự kiến</label>
                <input type="date" name="expected_delivery" value="{{ old('expected_delivery', $quotation ? now()->addDays($quotation->delivery_days ?? 7)->format('Y-m-d') : '') }}"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Điều khoản thanh toán</label>
                <select name="payment_terms" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="immediate">Thanh toán ngay</option>
                    <option value="cod">COD - Thanh toán khi nhận hàng</option>
                    <option value="net15">Net 15 - Thanh toán trong 15 ngày</option>
                    <option value="net30" selected>Net 30 - Thanh toán trong 30 ngày</option>
                    <option value="net45">Net 45 - Thanh toán trong 45 ngày</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng</label>
                <input type="text" name="delivery_address" value="{{ old('delivery_address') }}" placeholder="Địa chỉ nhận hàng"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>
        </div>

        <!-- Liên kết đơn bán & Thông tin tracking -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-link text-blue-400 mr-1"></i>Đơn bán hàng liên kết
                </label>
                <select name="sale_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Không liên kết --</option>
                    @foreach($availableSales ?? [] as $sale)
                        <option value="{{ $sale->id }}" {{ old('sale_id', $selectedSaleId ?? '') == $sale->id ? 'selected' : '' }}>
                            {{ $sale->code }} - {{ $sale->customer_name }} ({{ number_format($sale->total, 0, ',', '.') }}₫)
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar-alt text-green-400 mr-1"></i>Ngày dự kiến hàng về
                </label>
                <input type="date" name="expected_arrival_date" value="{{ old('expected_arrival_date') }}"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-industry text-purple-400 mr-1"></i>Ngày hãng xuất sản phẩm
                </label>
                <input type="date" name="manufacturer_release_date" value="{{ old('manufacturer_release_date') }}"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
            <textarea name="note" rows="3" 
                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" 
                placeholder="Nhập ghi chú cho đơn hàng này...">{{ old('note') }}</textarea>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="border-t border-gray-200 pt-4 mb-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Chi tiết sản phẩm</h3>
                <div class="flex gap-2">
                    <button type="button" id="importPrBtn" class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition disabled:opacity-50 disabled:cursor-not-allowed" title="Vui lòng chọn nhà cung cấp trước">
                        <i class="fas fa-file-import mr-1"></i>Import từ PR
                    </button>
                    <button type="button" id="addItem" class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                    </button>
                </div>
            </div>
            <div id="itemsContainer" class="space-y-3">
                @if($quotation && $quotation->items->count() > 0)
                    @foreach($quotation->items as $index => $item)
                    <div class="item-row grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative">
                        <div class="col-span-2 relative product-autocomplete">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
                            <input type="text" name="items[{{ $index }}][product_name]" value="{{ $item->product_name }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-name-input" autocomplete="off" placeholder="Nhập tên sản phẩm...">
                            <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id ?? '' }}" class="product-id">
                            <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">SL</label>
                            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" min="1" required
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty" oninput="calculateRow(this)">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Giá list (USD)</label>
                            <input type="text" name="items[{{ $index }}][warehouse_unit_price]" value="{{ $item->warehouse_unit_price ? (floor($item->warehouse_unit_price) == $item->warehouse_unit_price ? number_format($item->warehouse_unit_price, 0, '.', '') : number_format($item->warehouse_unit_price, 2, '.', '')) : '0' }}"
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-list-price focus:ring-blue-500" oninput="calculateRow(this)">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Discount (%)</label>
                            <input type="text" name="items[{{ $index }}][discount_percent]" value="{{ floor($item->discount_percent ?? 0) == ($item->discount_percent ?? 0) ? number_format($item->discount_percent ?? 0, 0, '.', '') : number_format($item->discount_percent ?? 0, 2, '.', '') }}"
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-discount focus:ring-blue-500" oninput="calculateRow(this)">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Giá mua (USD)</label>
                            <input type="text" name="items[{{ $index }}][unit_price]" value="{{ floor($item->unit_price) == $item->unit_price ? number_format($item->unit_price, 0, '.', '') : number_format($item->unit_price, 2, '.', '') }}" required
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price border-blue-400 focus:ring-blue-500" oninput="calculateRow(this)">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">VAT %</label>
                            <input type="number" name="items[{{ $index }}][vat_percent]" value="10" min="0" step="0.1" 
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-vat" onchange="calculateRow(this)">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
                            @php $totalVal = $item->quantity * $item->unit_price; @endphp
                            <input type="text" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total" readonly
                                value="{{ floor($totalVal) == $totalVal ? number_format($totalVal, 0, '.', ',') : number_format($totalVal, 2, '.', ',') }}">
                        </div>
                        <div class="col-span-1 flex justify-center">
                            <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200" {{ $loop->count == 1 ? 'style=display:none' : '' }}>
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                @else
                <div class="item-row grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative">
                    <div class="col-span-2 relative product-autocomplete">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
                        <input type="text" name="items[0][product_name]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-name-input" autocomplete="off" placeholder="Nhập tên sản phẩm...">
                        <input type="hidden" name="items[0][product_id]" class="product-id">
                        <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">SL</label>
                        <input type="number" name="items[0][quantity]" value="1" min="1" required
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty" oninput="calculateRow(this)">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Giá list (USD)</label>
                        <input type="text" name="items[0][warehouse_unit_price]" value="0"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-list-price focus:ring-blue-500" oninput="calculateRow(this)">
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Discount (%)</label>
                        <input type="text" name="items[0][discount_percent]" value="0"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-discount focus:ring-blue-500" oninput="calculateRow(this)">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Giá mua (USD)</label>
                        <input type="text" name="items[0][unit_price]" required placeholder="0" value="0"
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price border-blue-400 focus:ring-blue-500" oninput="calculateRow(this)">
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">VAT %</label>
                        <input type="number" name="items[0][vat_percent]" value="10" min="0" step="0.1" 
                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-vat" onchange="calculateRow(this)">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
                        <input type="text" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total" readonly value="0">
                    </div>
                    <div class="col-span-1 flex justify-center">
                        <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200" style="display:none;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Tổng cộng -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="4" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('note') }}</textarea>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-start">
                    <span class="text-gray-600 text-sm mt-1">Tổng tiền hàng:</span>
                    <div class="text-right">
                        <div id="subtotal" class="font-medium">0 $</div>
                        <div id="subtotalVnd" class="text-xs text-gray-500 mt-0.5 hidden"></div>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Chiết khấu (%):</span>
                    <div class="flex items-center gap-2">
                        <span id="discountAmountDisplay" class="text-sm font-medium text-red-500 hidden"></span>
                        <input type="number" name="discount_percent" value="{{ old('discount_percent', $quotation->discount_percent ?? 0) }}" min="0" max="100"
                            class="w-20 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()">
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Phí vận chuyển:</span>
                    <input type="number" name="shipping_cost" value="{{ old('shipping_cost', $quotation->shipping_cost ?? 0) }}" min="0"
                        class="w-32 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()">
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 text-sm">Chi phí khác:</span>
                    <input type="number" name="other_cost" value="{{ old('other_cost', 0) }}" min="0"
                        class="w-32 px-2 py-1 text-sm border border-gray-300 rounded text-right" onchange="calculateTotal()">
                </div>
                <input type="hidden" name="vat_percent" value="0">
                <div class="flex justify-between items-start pt-3 border-t">
                    <span class="text-lg font-semibold mt-1">Tổng cộng:</span>
                    <div class="text-right">
                        <div id="total" class="text-lg font-bold text-blue-600">0 $</div>
                        <div id="totalVndReference" class="text-sm text-gray-500 mt-0.5 hidden"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
            <a href="{{ route('purchase-orders.index') }}" 
               class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
            <button type="submit" class="px-4 py-2 text-sm bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <i class="fas fa-save mr-1"></i> Lưu nháp
            </button>
            <button type="submit" name="submit_approval" value="1" class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                <i class="fas fa-paper-plane mr-1"></i> Lưu và gửi duyệt
            </button>
        </div>
    </form>
</div>

<!-- Modal Import PR -->
<div id="prModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" id="closePrModalBg"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full font-sans">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        <i class="fas fa-file-invoice text-purple-500 mr-2"></i>Chọn sản phẩm từ Yêu cầu đặt hàng (PR)
                    </h3>
                    <button type="button" id="closePrModalBtn" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-4 sm:p-6 max-h-[60vh] overflow-y-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">
                                <input type="checkbox" id="selectAllPrItems" class="rounded">
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã PR/Sale</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Part Number</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">SL Còn lại</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ĐVT</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SI Name</th>
                        </tr>
                    </thead>
                    <tbody id="prItemsList" class="bg-white divide-y divide-gray-200">
                        <!-- Ajax loading -->
                    </tbody>
                </table>
                <div id="prItemsEmpty" class="py-10 text-center text-gray-500 hidden">
                    <i class="fas fa-folder-open text-4xl mb-3"></i>
                    <p>Không có yêu cầu đặt hàng nào treo cho nhà cung cấp này.</p>
                </div>
            </div>
            <div class="bg-white px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t">
                <button type="button" id="confirmImportPr" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Xác nhận thêm
                </button>
                <button type="button" id="cancelImportPr" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Hủy
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    .ts-wrapper.single .ts-control {
        padding: 8px 12px;
        border-radius: 0.5rem;
        border-color: #d1d5db;
        min-height: 42px;
    }
    .ts-dropdown {
        border-radius: 0.5rem;
    }
    .ts-dropdown .option {
        padding: 10px 12px;
    }
    .ts-dropdown .option.active {
        background-color: #eff6ff;
        color: #1e3a8a;
    }

    /* Custom Autocomplete Suggestions */
    .suggestions-list::-webkit-scrollbar {
        width: 6px;
    }
    .suggestions-list::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 3px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var supplierEl = document.getElementById('supplierSelect');
    if (supplierEl && !supplierEl.disabled) {
        new TomSelect('#supplierSelect', {
            placeholder: 'Tìm và chọn nhà cung cấp...',
            allowEmptyOption: true,
            maxOptions: 100,
            render: {
                no_results: function() {
                    return '<div class="no-results p-3 text-gray-500">Không tìm thấy nhà cung cấp</div>';
                }
            }
        });
    }
    calculateTotal();
    updateImportPrBtn();
});

function updateImportPrBtn() {
    const btn = document.getElementById('importPrBtn');
    if (!btn) return;
    const supplierId = document.getElementById('supplierSelect').value;
    if (supplierId) {
        btn.disabled = false;
        btn.title = 'Nhấn để import các yêu cầu đặt hàng của nhà cung cấp này';
    } else {
        btn.disabled = true;
        btn.title = 'Vui lòng chọn nhà cung cấp trước';
    }
}

function updatePoCodeWithSupplier() {
    const supplierSelect = document.getElementById('supplierSelect');
    const poCodeInput = document.getElementById('poCodeInput');
    if (supplierSelect && poCodeInput) {
        const supplierId = supplierSelect.value;
        let url = '/api/purchase-orders/generate-code';
        if (supplierId) {
            url += `?supplier_id=${supplierId}`;
        }
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.code) {
                    poCodeInput.value = data.code;
                }
            })
            .catch(err => console.error('Error fetching PO code:', err));
    }
}

// Lắng nghe sự kiện thay đổi supplier để cập nhật nút import và Mã PO
document.getElementById('supplierSelect').addEventListener('change', function() {
    updateImportPrBtn();
    updatePoCodeWithSupplier();
});

// Chạy khi load trang xong để điền tên hãng nếu có sẵn nhà cung cấp
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(updatePoCodeWithSupplier, 100);
});

@php
    $productData = $products->map(function ($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code,
            'unit' => $p->unit
        ];
    });
@endphp
const products = @json($productData);

function setupProductAutocomplete(row) {
    const input = row.querySelector('.product-name-input');
    const idInput = row.querySelector('.product-id');
    const suggestions = row.querySelector('.suggestions-list');

    function renderSuggestions(matches) {
        suggestions.innerHTML = '';
        if (matches.length === 0) {
            suggestions.classList.add('hidden');
            return;
        }
        matches.forEach(p => {
            const li = document.createElement('li');
            li.className = 'px-3 py-2 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-0';
            li.innerHTML = `
                <div class="font-medium text-sm text-gray-900">${p.name}</div>
                <div class="text-xs text-gray-500">Mã: ${p.code} | ĐVT: ${p.unit || '---'}</div>
            `;
            li.addEventListener('mousedown', (e) => {
                e.preventDefault(); // Prevent blur event
                input.value = p.name;
                idInput.value = p.id;
                suggestions.classList.add('hidden');
            });
            suggestions.appendChild(li);
        });
        suggestions.classList.remove('hidden');
    }

    input.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        // Reset ID if input changes
        const exactMatch = products.find(p => p.name.toLowerCase() === val);
        idInput.value = exactMatch ? exactMatch.id : '';

        if (val.length < 1) {
            suggestions.classList.add('hidden');
            return;
        }

        const matches = products.filter(p => 
            p.name.toLowerCase().includes(val) || 
            p.code.toLowerCase().includes(val)
        ).slice(0, 20);
        renderSuggestions(matches);
    });

    input.addEventListener('focus', function() {
        if (this.value.trim() === '') {
            renderSuggestions(products.slice(0, 20));
        } else {
             this.dispatchEvent(new Event('input'));
        }
    });

    input.addEventListener('blur', function() {
       setTimeout(() => suggestions.classList.add('hidden'), 200);
    });
}

document.querySelectorAll('.item-row').forEach(row => setupProductAutocomplete(row));

let itemIndex = {{ $quotation ? $quotation->items->count() : 1 }};

function calculateRow(input) {
    const row = input.closest('.item-row');
    
    const listPriceInput = row.querySelector('.item-list-price');
    const discountInput = row.querySelector('.item-discount');
    const priceInput = row.querySelector('.item-price');
    
    let listPrice = parseFloat(listPriceInput.value) || 0;
    if (listPrice < 0) {
        listPrice = 0;
        listPriceInput.value = "0";
    }
    
    let discount = parseFloat(discountInput.value) || 0;
    if (discount < 0) {
        discount = 0;
        discountInput.value = "0";
    }
    
    let price = parseFloat(priceInput.value) || 0;
    if (price < 0) {
        price = 0;
        priceInput.value = "0";
    }
    
    // If the change came from list price or discount, calculate price
    if (input.classList.contains('item-list-price') || input.classList.contains('item-discount')) {
        price = listPrice * (1 - discount / 100);
        priceInput.value = price % 1 === 0 ? price.toFixed(0) : price.toFixed(2);
    } else if (input.classList.contains('item-price')) {
        // If price is edited directly, recalculate discount percent based on list price
        if (listPrice > 0) {
            discount = Math.max(0, ((listPrice - price) / listPrice) * 100);
            discountInput.value = discount % 1 === 0 ? discount.toFixed(0) : discount.toFixed(2);
        }
    }
    
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const itemTotal = Math.round((qty * price) * 100) / 100;
    row.querySelector('.item-total').value = itemTotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: itemTotal % 1 === 0 ? 0 : 2 });
    
    // Add logic to calculate VND
    const select = document.getElementById('currencySelect');
    const isBase = select ? (select.options[select.selectedIndex].dataset.isBase === '1') : true;
    const rate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
    
    const vndPriceDisplay = row.querySelector('.vnd-unit-price');
    const vndTotalDisplay = row.querySelector('.vnd-item-total');
    
    if (isBase) {
        if (vndPriceDisplay) vndPriceDisplay.classList.add('hidden');
        if (vndTotalDisplay) vndTotalDisplay.classList.add('hidden');
    } else {
        if (vndPriceDisplay) {
            vndPriceDisplay.textContent = Math.round(price * rate).toLocaleString('en-US') + ' đ';
            vndPriceDisplay.classList.remove('hidden');
        }
        if (vndTotalDisplay) {
            vndTotalDisplay.textContent = Math.round(itemTotal * rate).toLocaleString('en-US') + ' đ';
            vndTotalDisplay.classList.remove('hidden');
        }
    }
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        subtotal += Math.round((qty * price) * 100) / 100;
    });

    const discountInput = document.querySelector('input[name="discount_percent"]');
    const discountPercent = discountInput ? (parseFloat(discountInput.value) || 0) : 0;
    const discountAmount = Math.round((subtotal * (discountPercent / 100)) * 100) / 100;
    
    const afterDiscount = subtotal - discountAmount;
    
    const shippingInput = document.querySelector('input[name="shipping_cost"]');
    const shippingCost = shippingInput ? (parseFloat(shippingInput.value) || 0) : 0;
    
    const otherCostInput = document.querySelector('input[name="other_cost"]');
    const otherCost = otherCostInput ? (parseFloat(otherCostInput.value) || 0) : 0;
    
    const beforeVat = afterDiscount + shippingCost + otherCost;

    const total = Math.round((afterDiscount + shippingCost + otherCost) * 100) / 100;

    const select = document.getElementById('currencySelect');
    const option = select ? select.options[select.selectedIndex] : null;
    const symbol = option ? (option.dataset.symbol || 'đ') : 'đ';
    const isBase = option ? option.dataset.isBase === '1' : true;
    const rate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;

    const vatAmount = 0;

    document.getElementById('subtotal').innerHTML = subtotal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: subtotal % 1 === 0 ? 0 : 2 }) + ' $';
    document.getElementById('total').innerHTML = total.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: total % 1 === 0 ? 0 : 2 }) + ' $';

    const discDisplay = document.getElementById('discountAmountDisplay');
    if (discDisplay) {
        discDisplay.textContent = discountAmount > 0 ? ('-' + discountAmount.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: discountAmount % 1 === 0 ? 0 : 2 }) + ' $') : '';
        discDisplay.classList.toggle('hidden', discountAmount <= 0);
    }
    
    const vatDisplay = document.getElementById('vatAmountDisplay');
    if (vatDisplay) {
        vatDisplay.textContent = vatAmount > 0 ? ('+' + vatAmount.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: vatAmount % 1 === 0 ? 0 : 2 }) + ' $') : '';
        vatDisplay.classList.toggle('hidden', vatAmount <= 0);
    }
    
    document.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);

    const subtotalVnd = document.getElementById('subtotalVnd');
    const totalVnd = document.getElementById('totalVndReference');
    
    if (isBase) {
        if (subtotalVnd) subtotalVnd.classList.add('hidden');
        if (totalVnd) totalVnd.classList.add('hidden');
    } else {
        if (subtotalVnd) {
            subtotalVnd.textContent = Math.round(subtotal * rate).toLocaleString('en-US') + ' đ';
            subtotalVnd.classList.remove('hidden');
        }
        if (totalVnd) {
            totalVnd.textContent = Math.round(total * rate).toLocaleString('en-US') + ' đ';
            totalVnd.classList.remove('hidden');
        }
    }
}

function onCurrencyChange() {
    const select = document.getElementById('currencySelect');
    const option = select.options[select.selectedIndex];
    const isBase = option.dataset.isBase === '1';

    if (isBase) {
        document.getElementById('exchangeRateGroup').classList.add('hidden');
        document.getElementById('exchangeRateInput').value = 1;
    } else {
        document.getElementById('exchangeRateGroup').classList.remove('hidden');
        fetchExchangeRate(select.value);
    }
    
    document.querySelectorAll('.item-qty').forEach(input => calculateRow(input));
    calculateTotal();
}

async function fetchExchangeRate(currencyId) {
    const dateInput = document.querySelector('input[name="order_date"]');
    const date = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];
    
    try {
        const response = await fetch(`{{ route('api.exchange-rate') }}?currency_id=${currencyId}&date=${date}`);
        const data = await response.json();
        
        if (data.rate) {
            document.getElementById('exchangeRateInput').value = data.rate;
            document.querySelectorAll('.item-qty').forEach(input => calculateRow(input));
            calculateTotal();
        }
    } catch (e) {
        console.error('Failed to fetch exchange rate', e);
    }
}

// Gọi onCurrencyChange khi load trang để set trạng thái ban đầu
document.addEventListener('DOMContentLoaded', function() {
    onCurrencyChange();
});

// Lắng nghe sự kiện thay đổi ngày để cập nhật tỷ giá
document.querySelector('input[name="order_date"]').addEventListener('change', function() {
    const select = document.getElementById('currencySelect');
    if (select.options[select.selectedIndex].dataset.isBase !== '1') {
        fetchExchangeRate(select.value);
    }
});

document.getElementById('addItem').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative';
    newRow.innerHTML = `
        <div class="col-span-2 relative product-autocomplete">
            <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm</label>
            <input type="text" name="items[${itemIndex}][product_name]" class="product-name-input w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" autocomplete="off" placeholder="Nhập tên sản phẩm...">
            <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id">
            <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
        </div>
        <div class="col-span-1">
            <label class="block text-xs font-medium text-gray-600 mb-1">SL</label>
            <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty" oninput="calculateRow(this)">
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Giá list (USD)</label>
            <input type="text" name="items[${itemIndex}][warehouse_unit_price]" value="0" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-list-price focus:ring-blue-500" oninput="calculateRow(this)">
        </div>
        <div class="col-span-1">
            <label class="block text-xs font-medium text-gray-600 mb-1">Discount (%)</label>
            <input type="text" name="items[${itemIndex}][discount_percent]" value="0" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-discount focus:ring-blue-500" oninput="calculateRow(this)">
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Giá mua (USD)</label>
            <input type="text" name="items[${itemIndex}][unit_price]" value="0" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price border-blue-400 focus:ring-blue-500" oninput="calculateRow(this)">
        </div>
        <div class="col-span-1">
            <label class="block text-xs font-medium text-gray-600 mb-1">VAT %</label>
            <input type="number" name="items[${itemIndex}][vat_percent]" value="10" min="0" step="0.1" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-vat" onchange="calculateRow(this)">
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
            <input type="text" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total" readonly value="0">
        </div>
        <div class="col-span-1 flex justify-center">
            <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200"><i class="fas fa-trash"></i></button>
        </div>
    `;
    container.appendChild(newRow);
    setupProductAutocomplete(newRow);
    
    // Set currency symbol for new row elements
    const select = document.getElementById('currencySelect');
    const option = select ? select.options[select.selectedIndex] : null;
    const symbol = option ? (option.dataset.symbol || 'đ') : 'đ';
    newRow.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);
    
    itemIndex++;
    updateRemoveButtons();
});

document.getElementById('itemsContainer').addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        e.target.closest('.item-row').remove();
        updateRemoveButtons();
        calculateTotal();
    }
});

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach(row => {
        row.querySelector('.remove-item').style.display = rows.length > 1 ? 'flex' : 'none';
    });
}

// Logic Import PR
const prModal = document.getElementById('prModal');
const importPrBtn = document.getElementById('importPrBtn');
const supplierSelect = document.getElementById('supplierSelect');

// State management for PR items
let currentPrItems = [];

function togglePrModal(show) {
    if (show) prModal.classList.remove('hidden');
    else prModal.classList.add('hidden');
}

importPrBtn.addEventListener('click', async function() {
    const supplierId = supplierSelect.value;
    if (!supplierId) {
        alert('Vui lòng chọn nhà cung cấp trước!');
        return;
    }

    // Show loading state
    const tbody = document.getElementById('prItemsList');
    tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu...</td></tr>';
    document.getElementById('prItemsEmpty').classList.add('hidden');
    togglePrModal(true);

    try {
        const response = await fetch(`{{ route('purchase-orders.pr-items') }}?supplier_id=${supplierId}`);
        const items = await response.json();
        currentPrItems = items;
        renderPrItems(items);
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Lỗi khi tải dữ liệu!</td></tr>';
    }
});

function renderPrItems(items) {
    const tbody = document.getElementById('prItemsList');
    const emptyState = document.getElementById('prItemsEmpty');
    tbody.innerHTML = '';
    
    if (items.length === 0) {
        emptyState.classList.remove('hidden');
        return;
    }

    emptyState.classList.add('hidden');
    items.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50';
        tr.innerHTML = `
            <td class="px-3 py-2 text-sm">
                <input type="checkbox" class="pr-item-checkbox rounded" data-id="${item.id}">
            </td>
            <td class="px-3 py-2 text-sm text-gray-900 font-medium">
                ${item.pr_code}<br/>
                <span class="text-xs text-gray-500">${item.sale_code}</span>
            </td>
            <td class="px-3 py-2 text-sm text-gray-600">${item.part_number}</td>
            <td class="px-3 py-2 text-sm text-right font-medium text-blue-600">${item.remaining}</td>
            <td class="px-3 py-2 text-sm text-gray-500">${item.unit || '---'}</td>
            <td class="px-3 py-2 text-sm text-gray-500 max-w-[200px] truncate" title="${item.si_name || ''}">${item.si_name || '---'}</td>
        `;
        tbody.appendChild(tr);
    });
}

document.getElementById('selectAllPrItems').addEventListener('change', function() {
    document.querySelectorAll('.pr-item-checkbox').forEach(cb => cb.checked = this.checked);
});

document.getElementById('closePrModalBtn').addEventListener('click', () => togglePrModal(false));
document.getElementById('closePrModalBg').addEventListener('click', () => togglePrModal(false));
document.getElementById('cancelImportPr').addEventListener('click', () => togglePrModal(false));

document.getElementById('confirmImportPr').addEventListener('click', function() {
    const selectedIds = Array.from(document.querySelectorAll('.pr-item-checkbox:checked')).map(cb => parseInt(cb.dataset.id));
    if (selectedIds.length === 0) {
        alert('Vui lòng chọn ít nhất một sản phẩm!');
        return;
    }

    const itemsToAdd = currentPrItems.filter(item => selectedIds.includes(item.id));
    addItemsToOrder(itemsToAdd);
    togglePrModal(false);
});

function addItemsToOrder(items) {
    const container = document.getElementById('itemsContainer');
    const firstRowInput = container.querySelector('.item-row:first-child .product-name-input');
    
    // Nếu row đầu tiên rỗng, xóa nó đi
    if (container.querySelectorAll('.item-row').length === 1 && !firstRowInput.value) {
        container.innerHTML = '';
    }

    const currencySelect = document.getElementById('currencySelect');
    const isBase = currencySelect ? (currencySelect.options[currencySelect.selectedIndex].dataset.isBase === '1') : true;

    items.forEach(item => {
        const listPriceRaw = isBase ? (item.list_price_vnd || 0) : (item.list_price_usd || 0);
        const discountRaw = Math.max(0, item.discount_percent || 0);
        const defaultPriceRaw = listPriceRaw * (1 - discountRaw / 100);
        
        const listPrice = listPriceRaw % 1 === 0 ? listPriceRaw.toFixed(0) : listPriceRaw.toFixed(2);
        const discount = discountRaw % 1 === 0 ? discountRaw.toFixed(0) : discountRaw.toFixed(2);
        const defaultPrice = defaultPriceRaw % 1 === 0 ? defaultPriceRaw.toFixed(0) : defaultPriceRaw.toFixed(2);
        
        const row = document.createElement('div');
        row.className = 'item-row grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative';
        row.innerHTML = `
            <div class="col-span-2 relative product-autocomplete">
                <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm <span class="bg-purple-100 text-purple-700 text-[10px] px-1 rounded ml-1">${item.pr_code}</span></label>
                <input type="text" name="items[${itemIndex}][product_name]" value="${item.part_number}"
                    class="product-name-input w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" autocomplete="off">
                <input type="hidden" name="items[${itemIndex}][product_id]" value="" class="product-id">
                <input type="hidden" name="items[${itemIndex}][sale_order_request_item_id]" value="${item.id}">
                <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
            </div>
            <div class="col-span-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">SL</label>
                <input type="number" name="items[${itemIndex}][quantity]" value="${item.remaining}" min="0.01" step="0.01" required 
                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty" oninput="calculateRow(this)">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Giá list (USD)</label>
                <input type="text" name="items[${itemIndex}][warehouse_unit_price]" value="${listPrice}" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-list-price focus:ring-blue-500" oninput="calculateRow(this)">
            </div>
            <div class="col-span-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">Discount (%)</label>
                <input type="text" name="items[${itemIndex}][discount_percent]" value="${discount}" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-discount focus:ring-blue-500" oninput="calculateRow(this)">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Giá mua (USD)</label>
                <input type="text" name="items[${itemIndex}][unit_price]" value="${defaultPrice}" required 
                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-price border-blue-400 focus:ring-blue-500" oninput="calculateRow(this)">
            </div>
            <div class="col-span-1">
                <label class="block text-xs font-medium text-gray-600 mb-1">VAT %</label>
                <input type="number" name="items[${itemIndex}][vat_percent]" value="10" min="0" step="0.1" 
                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-vat" onchange="calculateRow(this)">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
                <input type="text" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 item-total" readonly value="0">
            </div>
            <div class="col-span-1 flex justify-center">
                <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200"><i class="fas fa-trash"></i></button>
            </div>
        `;
        container.appendChild(row);
        setupProductAutocomplete(row);
        
        // Set currency symbol for new row elements
        const select = document.getElementById('currencySelect');
        const option = select ? select.options[select.selectedIndex] : null;
        const symbol = option ? (option.dataset.symbol || 'đ') : 'đ';
        row.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);
        
        itemIndex++;
    });

    updateRemoveButtons();
    calculateTotal();
}
</script>
@endpush
@endsection
