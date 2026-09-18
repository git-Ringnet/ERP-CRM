@extends('layouts.app')

@section('title', 'Tạo đơn hàng')
@section('page-title', 'Tạo đơn hàng mới')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    {{-- Show all validation errors from server --}}
    @if ($errors->any())
    <div class="p-4 bg-red-50 border-b border-red-200">
        <div class="flex items-start">
            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-2"></i>
            <div>
                <p class="text-sm font-medium text-red-800">Có lỗi xảy ra:</p>
                <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <form action="{{ route('sales.store') }}" method="POST" id="saleForm" enctype="multipart/form-data">
        @csrf
        
        <div class="p-4 sm:p-6 space-y-6">
            @if(isset($selectedProjects) && $selectedProjects->count() > 1)
                <!-- Multi-Project Creation Banner -->
                <div class="bg-gradient-to-r from-indigo-50 via-purple-50 to-blue-50 border-2 border-indigo-200 rounded-2xl p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-indigo-600 text-white font-bold text-lg shadow-md">
                                <i class="fas fa-layer-group"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">
                                    Đang tạo đơn hàng tổng hợp từ {{ $selectedProjects->count() }} dự án
                                </h3>
                                <p class="text-xs text-gray-600 mt-0.5">
                                    Mỗi dòng hàng trong bảng sản phẩm có thể gắn cho từng dự án tương ứng.
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($selectedProjects as $sp)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-indigo-200 text-indigo-800 shadow-xs">
                                    <i class="fas fa-project-diagram mr-1 text-indigo-500"></i> {{ $sp->code }} - {{ $sp->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @if(!empty($clearPartnerEu))
                        <div class="bg-amber-100/80 border border-amber-300 rounded-xl p-3 text-xs text-amber-900 flex items-start space-x-2">
                            <i class="fas fa-exclamation-triangle text-amber-600 mt-0.5"></i>
                            <div>
                                <strong>Lưu ý:</strong> Thông tin Partner (SI) hoặc End-User (EU) giữa các dự án đã chọn không trùng khớp nên hệ thống để trống thông tin Khách hàng (Partner/SI). Vui lòng chọn Khách hàng và thông tin liên quan phù hợp cho đơn hàng này.
                            </div>
                        </div>
                    @else
                        <div class="bg-emerald-100/80 border border-emerald-300 rounded-xl p-2.5 text-xs text-emerald-900 flex items-center space-x-2">
                            <i class="fas fa-check-circle text-emerald-600"></i>
                            <div>
                                Thông tin Partner (SI) và End-User (EU) giữa các dự án trùng khớp và được giữ nguyên.
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mã đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code', $code) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror"
                           placeholder="VD: SO-20251205-0001">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Mã tự động: {{ $code }} (có thể sửa nếu cần)</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Loại đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <select name="type" id="saleType" required onchange="toggleProjectSelect()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="retail" {{ old('type', (isset($selectedProject) || (isset($selectedProjects) && $selectedProjects->count() > 0)) ? 'project' : 'retail') == 'retail' ? 'selected' : '' }}>Bán lẻ</option>
                        <option value="project" {{ old('type', (isset($selectedProject) || (isset($selectedProjects) && $selectedProjects->count() > 0)) ? 'project' : 'retail') == 'project' ? 'selected' : '' }}>Bán theo dự án</option>
                    </select>
                </div>
            </div>

            <!-- Project Selection (shown when type = project) -->
            <div id="projectSelectWrapper" class="{{ old('type', (isset($selectedProject) || (isset($selectedProjects) && $selectedProjects->count() > 0)) ? 'project' : 'retail') == 'project' ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-project-diagram text-purple-500 mr-1"></i>
                            Dự án chính
                        </label>
                        <select name="project_id" id="projectSelect" onchange="handleProjectSelection()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Chọn dự án chính --</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" 
                                    data-customer-id="{{ $project->customer_id }}"
                                    data-customer-name="{{ $project->customer ? $project->customer->name . ($project->customer->code ? ' (' . $project->customer->code . ')' : '') : '' }}"
                                    {{ old('project_id', $selectedProject?->id ?? '') == $project->id ? 'selected' : '' }}>
                                    {{ $project->code }} - {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            <a href="{{ route('projects.create') }}" class="text-purple-600 hover:underline">
                                <i class="fas fa-plus mr-1"></i>Tạo dự án mới
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Khách hàng (Partner/SI) <span class="text-red-500">*</span>
                    </label>
                    @php
                        $oldCustomerId = old('customer_id', $selectedCustomerId ?? ($prefill['customer_id'] ?? ''));
                    @endphp
                    <select name="customer_id" id="customer_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('customer_id') border-red-500 @enderror">
                        <option value="">Chọn khách hàng</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" 
                                    data-tax-code="{{ $customer->tax_code }}" 
                                    data-abv-name="{{ $customer->abv_name }}"
                                    data-debt-days="{{ $customer->debt_days }}"
                                    data-payment-terms="{{ json_encode($customer->payment_terms) }}"
                                    {{ $oldCustomerId == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}{{ $customer->code ? ' (' . $customer->code . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1 gap-1 whitespace-nowrap overflow-hidden">
                        <label class="text-sm font-medium text-gray-700 truncate">
                            Người phụ trách (P.I.C) <span class="text-red-500">*</span>
                        </label>
                        <button type="button" id="btn-quick-add-contact" class="text-xs text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap flex-shrink-0 hidden">
                            <i class="fas fa-plus mr-1"></i>Thêm mới
                        </button>
                    </div>
                    <select name="contact_id" id="contact_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('contact_id') border-red-500 @enderror">
                        <option value="">Chọn người phụ trách</option>
                    </select>
                    @error('contact_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror

                    <div id="pic_details" class="hidden mt-2 p-2 bg-slate-50 border border-slate-100 rounded-lg text-xs text-gray-600 space-y-1">
                        <p class="font-medium text-gray-700 mb-1"><span id="pic_name"></span></p>
                        <p><i class="fas fa-envelope text-gray-400 mr-1.5 w-4"></i><span id="pic_email"></span></p>
                        <p><i class="fas fa-phone text-gray-400 mr-1.5 w-4"></i><span id="pic_phone"></span></p>
                        <p><i class="fas fa-briefcase text-gray-400 mr-1.5 w-4"></i><span id="pic_position"></span></p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ngày tạo <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng</label>
                <textarea name="delivery_address" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('delivery_address') }}</textarea>
            </div>

            <!-- Currency Selection -->
            <div class="border-t pt-4">
                <h4 class="text-lg font-medium text-gray-900 mb-3">
                    <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Tiền tệ giao dịch
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Loại tiền tệ</label>
                        <select name="currency_id" id="currencySelect" onchange="onCurrencyChange()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}"
                                    data-is-base="{{ $currency->is_base ? '1' : '0' }}"
                                    data-code="{{ $currency->code }}"
                                    data-symbol="{{ $currency->symbol }}"
                                    {{ old('currency_id', $baseCurrencyId) == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->code }} - {{ $currency->name_vi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="exchangeRateGroup" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tỷ giá (1 ngoại tệ = ? VND)
                            <span id="rateSource" class="text-xs text-blue-500 ml-1"></span>
                        </label>
                        <input type="number" name="exchange_rate" id="exchangeRateInput" step="0.000001" min="0"
                            value="{{ old('exchange_rate', 1) }}"
                            onchange="calculateTotal()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1" id="rateHint">Tỷ giá sẽ tự động lấy từ Vietcombank</p>
                    </div>
                    <div id="dualPricePlaceholder" class="hidden">
                        <!-- Removed dualPriceGroup as per user request -->
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="border-t pt-4">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h4 class="text-lg font-medium text-gray-900">Chi tiết sản phẩm</h4>
                    <button type="button" id="btnOpenBomModal" class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 hover:text-indigo-800 transition-colors shadow-xs cursor-pointer">
                        <i class="fas fa-file-excel text-emerald-600 mr-1.5 text-sm"></i> Nhập nhanh BOM / Dán từ Excel
                    </button>
                </div>
                
                <!-- Product List Header (Desktop) -->
                <div class="hidden md:grid grid-cols-12 gap-3 px-4 py-2 bg-gray-100 border border-gray-200 rounded-t-lg font-bold text-gray-700">
                    <div class="md:col-span-3 product-name-header">Sản phẩm <span class="text-red-500">*</span></div>
                    <div class="md:col-span-1">Số lượng <span class="text-red-500">*</span></div>
                    <div class="md:col-span-2">Đơn giá (<span class="currency-symbol">₫</span>) <span class="text-red-500">*</span></div>
                    <div class="md:col-span-1 text-center">VAT (%)</div>
                    <div class="md:col-span-1">Bảo hành</div>
                    <div class="md:col-span-1 text-center product-tax-header">Thuế nhà thầu</div>
                    <div class="md:col-span-2 text-right">Thành tiền (gồm VAT)</div>
                    <div class="md:col-span-1 text-center"><i class="fas fa-cog"></i></div>
                </div>

                @php
                    $initialProducts = old('products');
                    if (!$initialProducts && !empty($prefilledProducts)) {
                        $initialProducts = $prefilledProducts;
                    }
                    if (empty($initialProducts)) {
                        $initialProducts = [
                            [
                                'product_id' => '',
                                'quantity' => 1,
                                'price' => '',
                                'vat' => 8,
                                'warranty_months' => '',
                                'contractor_tax_enabled' => 0,
                                'new_name' => '',
                                'new_code' => '',
                                'new_unit' => 'Cái',
                                'display_text' => '',
                            ]
                        ];
                    }
                @endphp

                <div id="productList" class="space-y-0 border-x border-b border-gray-200 rounded-b-lg">
                    @foreach($initialProducts as $idx => $prod)
                        @php
                            $pid = is_array($prod) ? ($prod['product_id'] ?? '') : ($prod->product_id ?? '');
                            $pname = is_array($prod) ? ($prod['new_name'] ?? ($prod['name'] ?? '')) : '';
                            $pcode = is_array($prod) ? ($prod['new_code'] ?? ($prod['code'] ?? '')) : '';
                            $punit = is_array($prod) ? ($prod['new_unit'] ?? ($prod['unit'] ?? 'Cái')) : 'Cái';
                            $pqty = is_array($prod) ? ($prod['quantity'] ?? 1) : 1;
                            $pprice = is_array($prod) ? ($prod['price'] ?? '') : '';
                            $pvat = is_array($prod) ? ($prod['vat'] ?? 8) : 8;
                            $pwarranty = is_array($prod) ? ($prod['warranty_months'] ?? '') : '';
                            $pContractorTax = is_array($prod) ? (!empty($prod['contractor_tax_enabled']) ? 1 : 0) : 0;
                            $displayText = is_array($prod) ? ($prod['display_text'] ?? '') : '';
                            if (empty($displayText)) {
                                if ($pid === 'new') {
                                    $displayText = '[SP Mới] ' . ($pname ?: $pcode);
                                } elseif ($pcode || $pname) {
                                    $displayText = '[' . ($pcode ?: '') . '] ' . ($pname ?: '');
                                }
                            }
                        @endphp
                        <div class="product-item {{ $idx % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} p-4 border-b last:border-b-0 border-gray-100">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                                <div class="md:col-span-3 product-name-col">
                                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                                    <div class="searchable-select product-searchable" data-index="{{ $idx }}" data-ajax-url="{{ route('api.products.search') }}">
                                        <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" 
                                               placeholder="Gõ để tìm sản phẩm..." autocomplete="off" value="{{ old("products.{$idx}.searchable_text", $displayText) }}">
                                        <input type="hidden" name="products[{{ $idx }}][product_id]" required class="product-id-input" value="{{ old("products.{$idx}.product_id", $pid) }}">
                                        <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg"></div>
                                        <input type="hidden" name="products[{{ $idx }}][is_liquidation]" value="{{ old("products.{$idx}.is_liquidation", 0) }}" class="is-liquidation-input">
                                    </div>
                                    <input type="hidden" name="products[{{ $idx }}][new_name]" class="new-name-input" value="{{ old("products.{$idx}.new_name", $pname) }}">
                                    <input type="hidden" name="products[{{ $idx }}][new_code]" class="new-code-input" value="{{ old("products.{$idx}.new_code", $pcode) }}">
                                    <input type="hidden" name="products[{{ $idx }}][new_unit]" class="new-unit-input" value="{{ old("products.{$idx}.new_unit", $punit) }}">
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Số lượng <span class="text-red-500">*</span></label>
                                    <input type="number" name="products[{{ $idx }}][quantity]" min="1" value="{{ old("products.{$idx}.quantity", $pqty) }}" required
                                           onchange="calculateRowTotal({{ $idx }})"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Đơn giá <span class="text-red-500">*</span></label>
                                    <input type="text" name="products[{{ $idx }}][price]" min="0" required
                                           value="{{ old("products.{$idx}.price", $pprice ? (is_numeric($pprice) ? number_format($pprice, 0, ',', '.') : $pprice) : '') }}"
                                           onchange="calculateRowTotal({{ $idx }})"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                                    <small class="block text-xs text-gray-500 mt-1 base-price-reference"></small>
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">VAT (%)</label>
                                    <select name="products[{{ $idx }}][vat]"
                                            onchange="handleVatChange(this)"
                                            class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary vat-input">
                                        <option value="-1" {{ old("products.{$idx}.vat", $pvat) == -1 ? 'selected' : '' }}>KCT</option>
                                        <option value="0" {{ old("products.{$idx}.vat", $pvat) == 0 ? 'selected' : '' }}>0%</option>
                                        <option value="5" {{ old("products.{$idx}.vat", $pvat) == 5 ? 'selected' : '' }}>5%</option>
                                        <option value="8" {{ old("products.{$idx}.vat", $pvat) == 8 ? 'selected' : '' }}>8%</option>
                                        <option value="10" {{ old("products.{$idx}.vat", $pvat) == 10 ? 'selected' : '' }}>10%</option>
                                        <option value="custom">Khác...</option>
                                    </select>
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Bảo hành (tháng)</label>
                                    <input type="number" name="products[{{ $idx }}][warranty_months]" min="0" max="120" value="{{ old("products.{$idx}.warranty_months", $pwarranty) }}"
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary warranty-input"
                                           placeholder="0">
                                </div>
                                <div class="md:col-span-1 text-center product-tax-col">
                                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Thuế nhà thầu</label>
                                    <input type="hidden" name="products[{{ $idx }}][contractor_tax_enabled]" value="0">
                                    <input type="checkbox" name="products[{{ $idx }}][contractor_tax_enabled]" value="1" {{ old("products.{$idx}.contractor_tax_enabled", $pContractorTax) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 contractor-tax-checkbox">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Thành tiền (gồm VAT)</label>
                                    <input type="text" readonly
                                           class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total text-right font-medium">
                                </div>
                                <div class="md:col-span-1 flex items-end md:items-center">
                                    <button type="button" onclick="removeProductRow(this)" 
                                            class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" onclick="addProductRow()" 
                        class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
                </button>
            </div>

            {{-- Expenses Section — Flexible P/L Cost Entry --}}
            @include('sales.partials.expense-section', [
                'expenses' => \App\Models\SaleExpense::defaultExpenses(),
                'currencySymbol' => '₫',
            ])

            <!-- Totals Section -->
            <!-- Totals Section -->
            <div class="border-t pt-4">
                <div class="space-y-3 max-w-md ml-auto">
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-medium text-gray-700">Tổng tiền hàng (chưa VAT) (<span class="currency-symbol">₫</span>)</label>
                        <input type="text" id="subtotal" readonly
                               class="w-48 text-right border border-gray-200 bg-gray-100 rounded-lg px-3 py-2">
                    </div>
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-medium text-gray-700">Tổng tiền hàng (đã gồm VAT) (<span class="currency-symbol">₫</span>)</label>
                        <input type="text" id="subtotalWithVat" readonly value=""
                               class="w-48 text-right border border-gray-200 bg-gray-100 rounded-lg px-3 py-2">
                    </div>
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-medium text-gray-700">Chiết khấu (%)</label>
                        <div class="flex gap-2 items-center">
                            <input type="number" name="discount" id="discount" value="{{ old('discount') }}" min="0" max="100" step="1"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')" onchange="calculateTotal()"
                                   class="w-16 text-center border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="0">
                            <input type="text" id="discountAmount" readonly
                                   class="w-32 text-right border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 text-red-600">
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-medium text-gray-700">Thuế VAT</label>
                        <input type="hidden" name="vat" id="vat" value="0">
                        <input type="text" id="vatAmount" readonly
                               class="w-48 text-right border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 text-blue-600">
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t">
                        <label class="text-base font-bold text-gray-900">Tổng cộng (gồm VAT & CK) (<span class="currency-symbol">₫</span>)</label>
                        <div class="text-right">
                            <input type="text" id="total" readonly value="0"
                                   class="w-48 text-right border border-gray-200 bg-primary/10 rounded-lg px-3 py-2 font-bold text-lg text-primary">
                            <small id="totalVndReference" class="block text-xs text-gray-500 mt-1"></small>
                        </div>
                    </div>
                    <input type="hidden" name="paid_amount" id="paid_amount" value="0">
                </div>
            </div>

            <!-- Payment terms type and Milestones Editor -->
            @php
                $currentUser = auth()->user();
                $canCustomizePaymentTerms = $currentUser && (
                    $currentUser->hasRole('super_admin') || 
                    $currentUser->hasRole('admin') || 
                    $currentUser->hasRole('director') || 
                    $currentUser->hasRole('accountant')
                );
            @endphp
            <script>
                // Sales may tailor milestones for the negotiated contract; the server
                // still enforces the one-billion post-delivery safeguard.
                window.canCustomizePaymentTerms = true;
            </script>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4">
                <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center justify-between">
                    <span class="flex items-center"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i> Lộ trình thanh toán chi tiết</span>
                    <span class="text-xs font-normal text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200"><i class="fas fa-info-circle mr-1"></i>Chọn theo Điều khoản mẫu quy định</span>
                </h4>
                
                <input type="hidden" name="payment_term_type" id="payment_term_type" value="">
                <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <label class="flex items-center gap-2 text-sm font-medium text-amber-900">
                        <input type="hidden" name="has_bank_guarantee" value="0">
                        <input type="checkbox" name="has_bank_guarantee" value="1" {{ old('has_bank_guarantee') ? 'checked' : '' }}>
                        Có bảo lãnh thanh toán (Bank Guarantee)
                    </label>
                    <input type="text" name="bank_guarantee_note" value="{{ old('bank_guarantee_note') }}" maxlength="1000"
                           class="mt-2 w-full rounded border border-amber-200 px-3 py-2 text-sm" placeholder="Ghi chú/số bảo lãnh (nếu có)">
                </div>
                <div class="grid grid-cols-1 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Điều khoản thanh toán</label>
                        <select id="milestonePresetSelect"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Chọn điều khoản thanh toán --</option>
                            <option value="customer_default">Mặc định theo khách hàng</option>
                            @foreach($paymentTemplates as $tpl)
                                <option value="template_{{ $tpl->id }}" data-items="{{ json_encode($tpl->items) }}" data-code="{{ $tpl->code }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="bodExceptionFileInput" class="hidden mt-3">
                    <label class="block text-xs font-medium text-red-700 mb-1">
                        <i class="fas fa-exclamation-triangle"></i> Tệp phê duyệt của BOD (Bắt buộc)
                    </label>
                    <input type="file" name="payment_exception_file" class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                </div>

                <datalist id="milestone-names">
                    <option value="Đợt 1">
                    <option value="Đợt 2">
                    <option value="Đợt 3">
                    <option value="Đặt cọc (Deposit)">
                    <option value="Thanh toán cuối (Final Payment)">
                </datalist>

                <div id="milestonesTableContainer" class="hidden mt-3">
                    <div class="overflow-x-auto pb-2">
                        <table class="w-full text-left border-collapse min-w-[1000px]">
                            <thead>
                                <tr class="bg-gray-100 text-xs font-semibold text-gray-600 border-b border-gray-200">
                                    <th class="p-2 min-w-[220px] text-sm">Tên đợt thanh toán</th>
                                    <th class="p-2 min-w-[90px] text-sm">Tỷ lệ (%)</th>
                                    <th class="p-2 min-w-[160px] text-sm">Số tiền (Tự tính)</th>
                                    <th class="p-2 min-w-[180px] text-sm">Thời điểm thanh toán</th>
                                    <th class="p-2 min-w-[160px] text-sm">Giai đoạn kiểm soát</th>
                                    <th class="p-2 min-w-[140px] text-sm">Chứng từ bắt buộc</th>
                                    <th class="p-2 min-w-[100px] text-sm">Hạn (ngày)</th>
                                </tr>
                            </thead>
                            <tbody id="milestoneList" class="divide-y divide-gray-100">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-200">
                        <span id="milestonePercentSumIndicator" class="text-sm font-semibold text-gray-700">Tổng tỷ lệ: 0%</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả điều khoản thanh toán</label>
                    <textarea name="payment_term" rows="2" placeholder="VD: Tạm ứng 30%..., thanh toán 70%..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('payment_term') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="note" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('note') }}</textarea>
                </div>
            </div>


        </div>

        <!-- Validation Error Message -->
        <div id="validationErrors" class="hidden px-4 sm:px-6 py-3 bg-red-50 border-t border-red-200">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-2"></i>
                <div>
                    <p class="text-sm font-medium text-red-800">Vui lòng điền đầy đủ các trường bắt buộc:</p>
                    <ul id="errorList" class="mt-1 text-sm text-red-700 list-disc list-inside"></ul>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row gap-2 justify-end">
            <a href="{{ route('sales.index') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
            <button type="button" onclick="validateAndSubmit()"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-save mr-2"></i> Lưu đơn hàng
            </button>
        </div>
    </form>
</div>

<!-- Quick Add Customer Modal -->
<div id="addCustomerModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="modalOverlay"></div>

        <!-- Trick to center the modal contents -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg leading-6 font-semibold text-gray-900" id="modal-title">
                        <i class="fas fa-user-plus text-blue-500 mr-2"></i> Thêm khách hàng nhanh
                    </h3>
                    <button type="button" id="closeCustomerModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Validation Error Message Block -->
                <div id="modalErrors" class="hidden p-3 mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"></div>

                <form id="customerModalForm" class="space-y-4">
                    @csrf
                    <!-- MST with lookup -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Mã số thuế (MST) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" name="tax_code" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="Nhập MST để tra cứu...">
                            <button type="button" id="btn-modal-search-tax"
                                    class="absolute right-0 top-0 h-full px-3 text-gray-400 hover:text-primary transition-colors focus:outline-none"
                                    title="Tra cứu thông tin doanh nghiệp từ MST">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tên khách hàng/Công ty <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                               placeholder="Nhập tên khách hàng...">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tên viết tắt <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="abv_name" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="VD: ADG, IIJ...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email công ty
                            </label>
                            <input type="email" name="email"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="email@company.com">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Số điện thoại công ty
                            </label>
                            <input type="text" name="phone"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="0123456789">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Địa chỉ
                            </label>
                            <input type="text" name="address"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="Nhập địa chỉ...">
                        </div>
                    </div>

                    <!-- Dynamic Contacts Section -->
                    <div class="border-t pt-3 mt-4">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-semibold text-gray-900">
                                <i class="fas fa-users text-blue-500 mr-1.5"></i> Danh sách người liên hệ <span class="text-red-500">*</span>
                            </h4>
                            <button type="button" id="modalAddContactBtn"
                                    class="inline-flex items-center px-2.5 py-1 border border-transparent text-xs font-medium rounded bg-blue-600 hover:bg-blue-700 text-white focus:outline-none transition-colors">
                                <i class="fas fa-plus mr-1"></i> Thêm người liên hệ
                            </button>
                        </div>
                        <div id="modalContactsContainer" class="space-y-3">
                            <!-- First contact card (always present) -->
                            <div class="modal-contact-card p-3 border border-gray-200 rounded-lg bg-gray-50/50" data-contact-index="0">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-bold text-gray-500 uppercase contact-label">Người liên hệ #1</span>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="modal_primary_contact" value="0" checked class="form-radio text-primary h-3.5 w-3.5">
                                            <span class="ml-1.5 text-xs text-gray-600">Liên hệ chính</span>
                                        </label>
                                        <button type="button" class="btn-remove-modal-contact text-red-400 hover:text-red-600 transition-colors hidden">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Họ & Tên <span class="text-red-500">*</span></label>
                                        <input type="text" class="contact-name w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nhập họ tên...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                                        <input type="text" class="contact-position w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="VD: Giám đốc...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                                        <input type="text" class="contact-phone w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nhập SĐT...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                                        <input type="email" class="contact-email w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="email@example.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                <button type="button" id="saveCustomerBtn"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                    <i class="fas fa-save mr-1.5 mt-0.5"></i> Lưu
                </button>
                <button type="button" id="cancelCustomerBtn"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Hủy
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Single Contact Modal -->
<div id="addSingleContactModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="singleContactModalOverlay"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg leading-6 font-semibold text-gray-900">
                        <i class="fas fa-user-plus text-blue-500 mr-2"></i> Thêm người phụ trách mới
                    </h3>
                    <button type="button" id="closeSingleContactModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div id="singleContactModalErrors" class="hidden p-3 mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"></div>

                <form id="singleContactModalForm" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Họ & Tên <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Nhập họ tên...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Chức vụ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="position" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="VD: Giám đốc, Kế toán...">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Số điện thoại <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="phone" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="0123456789">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="email@company.com">
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                <button type="button" id="saveSingleContactBtn" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                    <i class="fas fa-save mr-1.5 mt-0.5"></i> Lưu
                </button>
                <button type="button" id="cancelSingleContactBtn" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Hủy
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick BOM Import Modal -->
<div id="bomImportModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="bomModalOverlay"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-4 text-white flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-file-excel text-xl text-emerald-300"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-bold">Nhập nhanh BOM / Dán từ Excel</h3>
                        <p class="text-xs text-blue-100">Dán bảng danh sách sản phẩm từ Excel, email hoặc báo giá dự án</p>
                    </div>
                </div>
                <button type="button" id="closeBomModal" class="text-white/80 hover:text-white text-xl p-1 cursor-pointer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-900">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-lightbulb text-amber-500 text-base flex-shrink-0"></i>
                        <div>
                            <strong>Hướng dẫn:</strong> Copy trực tiếp các cột từ Excel (STT, Mã Part Number, Tên/Model, Số lượng, Đơn giá) hoặc dán danh sách theo dòng. Hệ thống tự động nhận diện và khớp với sản phẩm trong kho.
                        </div>
                    </div>
                </div>

                @if(isset($projects) && $projects->count() > 0)
                <div class="flex items-center space-x-3">
                    <label class="text-xs font-semibold text-gray-700 whitespace-nowrap">Gán cho dự án:</label>
                    <select id="bomModalProjectSelect" class="border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-primary w-full max-w-xs">
                        <option value="">-- Mặc định theo dự án đơn hàng --</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ (isset($selectedProject) && $selectedProject->id == $p->id) ? 'selected' : '' }}>{{ $p->code }} - {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nội dung BOM / Dữ liệu Excel</label>
                    <textarea id="bomInputText" rows="6" 
                        class="w-full font-mono text-xs border border-gray-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-inner"
                        placeholder="Ví dụ dán từ Excel:&#10;AW210040&#9;AirEngine 5760-51&#9;2&#9;15000000&#10;FG-60F-BDL&#9;FortiGate 60F Hardware&#9;1&#9;12500000&#10;&#10;Hoặc định dạng tự do:&#10;2x FG-60F-BDL&#10;AW210040 - Huawei AirEngine - 5 cái @ 14,000,000"></textarea>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex space-x-2">
                        <button type="button" id="btnParseBom" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow transition-colors cursor-pointer">
                            <i class="fas fa-wand-magic-sparkles mr-2"></i> Phân tích dữ liệu
                        </button>
                        <button type="button" id="btnClearBomText" class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition-colors cursor-pointer">
                            <i class="fas fa-eraser mr-1.5"></i> Xóa
                        </button>
                    </div>
                    <div id="bomParseStatus" class="text-xs text-gray-500"></div>
                </div>

                <!-- Preview Table Area -->
                <div id="bomPreviewArea" class="hidden border border-gray-200 rounded-xl overflow-hidden bg-gray-50">
                    <div class="bg-gray-100 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                        <div class="text-xs font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-list-check text-indigo-600"></i>
                            <span>Kết quả nhận diện (<span id="bomParsedCount">0</span> dòng)</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-medium">
                                <i class="fas fa-check mr-1"></i> Khớp kho: <span id="bomMatchedCount" class="ml-1 font-bold">0</span>
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-medium">
                                <i class="fas fa-plus mr-1"></i> Sản phẩm mới: <span id="bomNewCount" class="ml-1 font-bold">0</span>
                            </span>
                        </div>
                    </div>
                    <div class="max-h-60 overflow-y-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-gray-50 text-gray-600 font-semibold sticky top-0 border-b border-gray-200">
                                <tr>
                                    <th class="p-2 text-center w-10">STT</th>
                                    <th class="p-2">Part Number / Mã</th>
                                    <th class="p-2">Tên sản phẩm / Model</th>
                                    <th class="p-2 text-center w-16">SL</th>
                                    <th class="p-2 text-right w-28">Đơn giá</th>
                                    <th class="p-2 text-center w-28">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody id="bomPreviewTableBody" class="divide-y divide-gray-200 bg-white">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
                <div class="text-xs text-gray-500">
                    * Các sản phẩm mới sẽ được tự động tạo mã và tên khi lưu đơn hàng.
                </div>
                <div class="flex space-x-2">
                    <button type="button" id="btnApplyBomAppend" class="hidden inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow transition-colors cursor-pointer">
                        <i class="fas fa-plus mr-1.5"></i> Thêm nối tiếp vào bảng
                    </button>
                    <button type="button" id="btnApplyBomReplace" class="hidden inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow transition-colors cursor-pointer">
                        <i class="fas fa-sync-alt mr-1.5"></i> Ghi đè bảng sản phẩm
                    </button>
                    <button type="button" id="btnCloseBomModalFooter" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 cursor-pointer">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Select2 Height & Styling Customization to match Tailwind Inputs */
.select2-container .select2-selection--single {
    height: 42px !important;
    border-color: #d1d5db !important;
    border-radius: 0.5rem !important;
    display: flex !important;
    align-items: center !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    top: 1px !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px !important;
    color: #374151 !important;
    font-size: 0.875rem !important;
    padding-left: 0.75rem !important;
}
.searchable-select {
    position: relative;
}
.searchable-dropdown {
    top: 100%;
    left: 0;
    right: 0;
}
.searchable-option.highlighted {
    background-color: #dbeafe;
}
.no-results {
    padding: 8px 12px;
    color: #6b7280;
    font-style: italic;
}
/* Hide black arrow icon on datalist inputs */
input[list]::-webkit-calendar-picker-indicator {
    display: none !important;
    -webkit-appearance: none;
}
</style>
@endpush

@push('scripts')
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@php
    $initialExpenses = old('expenses', \App\Models\SaleExpense::defaultExpenses());
    $hasContractorTax = false;
    foreach ($initialExpenses as $exp) {
        $type = is_array($exp) ? ($exp['type'] ?? '') : $exp->type;
        if ($type === 'Thuế nhà thầu') {
            $hasContractorTax = true;
            break;
        }
    }
@endphp
<script>
window.initialHasContractorTax = @json($hasContractorTax);
let productIndex = {{ count($initialProducts ?? (old('products', []))) ?: 1 }};
let expenseIndex = {{ count(old('expenses', [])) ?: 0 }};
let isSubmitting = false;

function formatMoney(amount) {
    return new Intl.NumberFormat('en-US').format(amount);
}




// Searchable Select Functions
function initSearchableSelect(container, onSelect) {
    const input = container.querySelector('.searchable-input');
    const hiddenInput = container.querySelector('input[type="hidden"]');
    const dropdown = container.querySelector('.searchable-dropdown');
    const ajaxUrl = container.dataset.ajaxUrl;
    let debounceTimer;
    
    input.addEventListener('focus', () => {
        dropdown.classList.remove('hidden');
        if (!ajaxUrl) {
            filterOptions('');
        }
    });
    
    input.addEventListener('input', (e) => {
        const query = e.target.value;
        if (ajaxUrl) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchAjaxOptions(query);
            }, 300);
        } else {
            filterOptions(query);
        }
    });

    async function fetchAjaxOptions(query) {
        if (query.trim().length === 0) {
            dropdown.innerHTML = '';
            dropdown.classList.add('hidden');
            return;
        }

        dropdown.innerHTML = '<div class="px-3 py-2 text-gray-500 italic">Đang tìm kiếm...</div>';
        dropdown.classList.remove('hidden');

        try {
            const response = await fetch(`${ajaxUrl}?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            renderAjaxOptions(data);
        } catch (error) {
            console.error('Search error:', error);
            dropdown.innerHTML = '<div class="px-3 py-2 text-red-500">Lỗi khi tìm kiếm</div>';
        }
    }

    function renderAjaxOptions(data) {
        dropdown.innerHTML = '';
        
        const query = input.value.trim();

        if (data.length === 0 && query.length === 0) {
            dropdown.innerHTML = '<div class="px-3 py-2 text-gray-500">Không tìm thấy kết quả</div>';
            return;
        }

        // Lấy danh sách ID sản phẩm đã chọn ở các dòng khác
        const selectedProductIds = [];
        document.querySelectorAll('.product-id-input').forEach(input_el => {
            if (input_el.value && input_el !== hiddenInput) {
                selectedProductIds.push(input_el.value);
            }
        });

        data.forEach(item => {
            // Check if both normal and liquidation variants should be filtered out
            // But normally we only filter out if the exact variant is selected
            // However, the current logic filters by product ID
            if (selectedProductIds.includes(item.id.toString()) && item.is_liquidation === 0) {
                // If it's a normal product and already selected, we might skip it
                // But for now let's keep it simple and just show everything since AJAX is limited
            }

            const opt = document.createElement('div');
            opt.className = 'searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer';
            opt.dataset.value = item.id;
            opt.dataset.text = `[${item.code || ''}] ${item.name}`;
            opt.dataset.code = item.code || '';
            opt.dataset.price = item.price;
            opt.dataset.isLiquidation = item.is_liquidation;
            opt.dataset.warranty = item.warranty_months;
            opt.dataset.liquidationCount = item.liquidation_count;
            
            const displayCode = item.code || '';
            const suffix = item.is_liquidation === 1 ? ' - Hàng thanh lý' : '';
            opt.innerHTML = `
                <span class="font-medium">${displayCode}${suffix}</span>
            `;
            
            opt.addEventListener('click', () => {
                input.value = opt.dataset.code;
                hiddenInput.value = opt.dataset.value;
                dropdown.classList.add('hidden');
                if (onSelect) onSelect(opt);
            });
            
            dropdown.appendChild(opt);
        });

        if (query.length > 0) {
            const addOpt = document.createElement('div');
            addOpt.className = 'searchable-option px-3 py-2 hover:bg-emerald-50 text-emerald-600 font-bold border-t border-gray-100 cursor-pointer';
            addOpt.dataset.value = 'new';
            addOpt.dataset.text = `+ Thêm sản phẩm mới: "${query}"`;
            addOpt.dataset.code = `[SP Mới] ${query}`;
            addOpt.dataset.name = query;
            addOpt.innerHTML = `
                <div class="flex items-center text-xs">
                    <i class="fas fa-plus mr-1.5"></i>
                    <span>Tạo sản phẩm mới: "${query}"</span>
                </div>
            `;
            addOpt.addEventListener('click', () => {
                input.value = `[SP Mới] ${query}`;
                hiddenInput.value = 'new';
                dropdown.classList.add('hidden');
                if (onSelect) onSelect(addOpt);
            });
            dropdown.appendChild(addOpt);
        }
    }
    
    function filterOptions(query) {
        const q = query.toLowerCase();
        let hasResults = false;
        const options = dropdown.querySelectorAll('.searchable-option');
        
        // Lấy danh sách ID sản phẩm đã chọn ở các dòng khác
        const selectedProductIds = [];
        document.querySelectorAll('.product-id-input').forEach(el => {
            if (el.value && el !== hiddenInput) {
                selectedProductIds.push(el.value);
            }
        });

        options.forEach(opt => {
            const text = opt.dataset.text.toLowerCase();
            const value = opt.dataset.value;
            
            if (selectedProductIds.includes(value)) {
                opt.classList.add('hidden');
            } else if (text.includes(q)) {
                opt.classList.remove('hidden');
                hasResults = true;
            } else {
                opt.classList.add('hidden');
            }
        });
        
        // Show no results message
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
    
    // Static options click binding (only for non-ajax)
    if (!ajaxUrl) {
        const options = dropdown.querySelectorAll('.searchable-option');
        options.forEach(opt => {
            opt.addEventListener('click', () => {
                input.value = opt.dataset.text;
                hiddenInput.value = opt.dataset.value;
                dropdown.classList.add('hidden');
                if (onSelect) onSelect(opt);
            });
        });
    }
    
    // Keyboard navigation
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
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlighted) highlighted.click();
        } else if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
        }
    });
    
    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}



// Update customer select to load milestones
function initAllSearchableSelects() {
    // Product selects
    document.querySelectorAll('.product-searchable').forEach(container => {
        if (!container.dataset.initialized) {
            initSearchableSelect(container, (opt) => {
                const row = container.closest('.product-item');
                const priceInput = row.querySelector('.price-input');
                const warrantyInput = row.querySelector('.warranty-input');
                
                // Populate hidden fields if new product is selected
                const newNameInput = row.querySelector('.new-name-input');
                const newCodeInput = row.querySelector('.new-code-input');
                const newUnitInput = row.querySelector('.new-unit-input');
                
                if (opt.dataset.value === 'new') {
                    if (newNameInput) newNameInput.value = opt.dataset.name || '';
                    if (newCodeInput) newCodeInput.value = opt.dataset.name || '';
                    if (newUnitInput) newUnitInput.value = 'Cái';
                } else {
                    if (newNameInput) newNameInput.value = '';
                    if (newCodeInput) newCodeInput.value = '';
                    if (newUnitInput) newUnitInput.value = 'Cái';
                }

                if (priceInput && opt.dataset.price) {
                    const basePriceVnd = parseFloat(opt.dataset.price);
                    
                    // Show base price reference only, don't auto-fill
                    const basePriceRef = row.querySelector('.base-price-reference');
                    if (basePriceRef) {
                        // basePriceRef.textContent = `Giá gốc kho: ${formatMoney(basePriceVnd)} ₫`;
                    }
                    
                    // Don't auto-fill price - let user enter manually
                    // const currentRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
                    // const priceInCurrency = basePriceVnd / currentRate;
                    // priceInput.value = formatMoney(priceInCurrency);
                }
                const isLiquidationInput = row.querySelector('.is-liquidation-input');
                if (isLiquidationInput && opt.dataset.isLiquidation) {
                    isLiquidationInput.value = opt.dataset.isLiquidation;
                }
                // Auto-fill warranty from product
                if (warrantyInput && opt.dataset.warranty) {
                    const warrantyMonths = parseInt(opt.dataset.warranty) || 0;
                    warrantyInput.value = warrantyMonths > 0 ? warrantyMonths : '';
                }
                // removed autoCalculateExpenses();
            });
            container.dataset.initialized = 'true';
        }
    });
}

function initProductRowLiveCalc() {
    const productList = document.getElementById('productList');
    if (!productList || productList.dataset.liveCalcInit) return;
    productList.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity-input')) {
            calculateRowTotal();
        }
    });
    productList.dataset.liveCalcInit = 'true';
}

        function matchCustomer(params, data) {
            if ($.trim(params.term) === '') {
                return data;
            }
            if (typeof data.text === 'undefined') {
                return null;
            }
            var term = params.term.toLowerCase();
            var text = data.text.toLowerCase();
            
            var taxCode = '';
            var abvName = '';
            if (data.element) {
                taxCode = $(data.element).data('tax-code') ? $(data.element).data('tax-code').toString().toLowerCase() : '';
                abvName = $(data.element).data('abv-name') ? $(data.element).data('abv-name').toString().toLowerCase() : '';
            }

            if (text.indexOf(term) > -1 || taxCode.indexOf(term) > -1 || abvName.indexOf(term) > -1) {
                return data;
            }
            return null;
        }

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for Customer
    $('select[name="customer_id"]').select2({
        placeholder: "Chọn khách hàng",
        allowClear: true,
        width: '100%',
        matcher: matchCustomer,
        language: {
            noResults: function () {
                return `<div class="p-2 text-center text-gray-500">
                            <div class="mb-1 text-xs">Không tìm thấy khách hàng nào</div>
                            <button type="button" id="btn-quick-add-customer" class="w-full inline-flex justify-center items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded bg-blue-600 hover:bg-blue-700 text-white focus:outline-none transition-colors">
                                <i class="fas fa-plus mr-1"></i> Thêm khách hàng nhanh
                            </button>
                        </div>`;
            }
        },
        escapeMarkup: function (markup) {
            return markup;
        }
    });

    $('select[name="customer_id"]').on('select2:select', function () {
        $(this).select2('close');
    });

    // Customer change event
    $('select[name="customer_id"]').on('change', function() {
        const customerId = $(this).val();
        const opt = $(this).find(':selected');
        if (opt.length && customerId) {
            window.selectedCustomerDebtDays = parseInt(opt.data('debt-days')) || 0;
            loadContacts(customerId);
        } else {
            window.selectedCustomerDebtDays = 0;
            loadContacts('');
        }
    });

    initAllSearchableSelects();
    initMoneyInputs();
    initProductRowLiveCalc();
    toggleProjectSelect(); // Initialize project select visibility

    // Auto-fill customer if project is pre-selected and customer is empty
    const projectSelect = document.getElementById('projectSelect');
    const customerSelect = document.getElementById('customer_id');
    if (projectSelect && projectSelect.value && (!customerSelect || !customerSelect.value)) {
        handleProjectSelection();
    } else {
        // Load contacts if customer is already populated on load
        const initialCustomerId = customerSelect ? customerSelect.value : '';
        const oldContactId = '{{ old('contact_id') }}';
        if (initialCustomerId) {
            const opt = $(customerSelect).find(':selected');
            if (opt.length) {
                window.selectedCustomerDebtDays = parseInt(opt.data('debt-days')) || 0;
            }
            loadContacts(initialCustomerId, oldContactId);
        }
    }

    calculateTotal();

    // Show SweetAlert2 error modal when there are server validation errors
    @if($errors->any())
        const errorMessages = @json($errors->all());
        const errorList = errorMessages.map(msg => `<li class="text-left text-sm">${msg}</li>`).join('');
        Swal.fire({
            icon: 'error',
            title: 'Vui lòng kiểm tra lại thông tin đơn hàng!',
            html: `<ul class="list-disc pl-5 space-y-1 max-h-60 overflow-y-auto">${errorList}</ul>`,
            confirmButtonText: 'Đã hiểu',
            confirmButtonColor: '#3B82F6',
            customClass: { popup: 'text-sm' }
        });

        const firstError = document.querySelector('.border-red-500');
        if (firstError) {
            setTimeout(() => {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }, 500);
        }
    @endif
});

// PIC Selection logic
const contactSelect = document.getElementById('contact_id');
const picDetails = document.getElementById('pic_details');
const picName = document.getElementById('pic_name');
const picEmail = document.getElementById('pic_email');
const picPhone = document.getElementById('pic_phone');
const picPosition = document.getElementById('pic_position');

let contactsData = [];

async function loadContacts(customerId, selectedContactId = null) {
    if (!customerId) {
        $('#btn-quick-add-contact').addClass('hidden');
        contactSelect.innerHTML = '<option value="">Vui lòng chọn khách hàng trước</option>';
        contactSelect.disabled = true;
        picDetails.classList.add('hidden');
        contactsData = [];
        return;
    }
    
    $('#btn-quick-add-contact').removeClass('hidden');
    contactSelect.disabled = true;
    contactSelect.innerHTML = '<option value="">Đang tải...</option>';
    
    try {
        const response = await fetch(`/ajax/customers/${customerId}/contacts`);
        contactsData = await response.json();
        
        if (!contactsData || contactsData.length === 0) {
            contactSelect.innerHTML = '<option value="">-- Chưa có người phụ trách (Bấm Thêm mới) --</option>';
            contactSelect.disabled = false;
            picDetails.classList.add('hidden');
            return;
        }

        // Determine which contact to select
        let autoSelectedId = selectedContactId;
        if (!autoSelectedId) {
            const primaryContact = contactsData.find(c => c.is_primary);
            autoSelectedId = primaryContact ? primaryContact.id : contactsData[0].id;
        }

        let options = '<option value="">Chọn người phụ trách</option>';
        contactsData.forEach(contact => {
            const isSelected = autoSelectedId == contact.id ? 'selected' : '';
            const cName = contact.name || (contact.first_name + ' ' + (contact.last_name || ''));
            options += `<option value="${contact.id}" ${isSelected}>${cName} ${contact.is_primary ? '(Mặc định)' : ''}</option>`;
        });
        contactSelect.innerHTML = options;
        contactSelect.disabled = false;
        
        if (autoSelectedId) {
            contactSelect.value = autoSelectedId;
            updatePicDetails();
        } else {
            picDetails.classList.add('hidden');
        }
    } catch (e) {
        console.error('Error fetching contacts:', e);
        contactSelect.innerHTML = '<option value="">Không tải được người liên hệ</option>';
        contactSelect.disabled = false;
    }
}

function updatePicDetails() {
    const val = contactSelect.value;
    const contact = contactsData.find(c => c.id == val);
    if (contact) {
        picName.textContent = contact.name;
        picEmail.textContent = contact.email || 'N/A';
        picPhone.textContent = contact.phone || 'N/A';
        picPosition.textContent = contact.position || 'N/A';
        picDetails.classList.remove('hidden');
    } else {
        picDetails.classList.add('hidden');
    }
}

contactSelect.addEventListener('change', updatePicDetails);

// Toggle project select visibility based on sale type
function toggleProjectSelect() {
    const saleType = document.getElementById('saleType').value;
    const projectWrapper = document.getElementById('projectSelectWrapper');
    const projectSelect = document.getElementById('projectSelect');
    
    if (saleType === 'project') {
        projectWrapper.classList.remove('hidden');
    } else {
        projectWrapper.classList.add('hidden');
        if (projectSelect) projectSelect.value = ''; // Clear project selection when switching to retail
    }
}

function handleProjectSelection() {
    const projectSelect = document.getElementById('projectSelect');
    const option = projectSelect ? projectSelect.options[projectSelect.selectedIndex] : null;
    
    if (!option || !option.value) return;
    
    const customerId = option.dataset.customerId;
    if (customerId) {
        $('select[name="customer_id"]').val(customerId).trigger('change');
    }
}

// Format money input (supports decimals for foreign currencies)
function formatMoney(value) {
    if (value === undefined || value === null || value === '') return '';
    
    // Determine if we need decimals (foreign currency)
    const select = document.getElementById('currencySelect');
    const isVnd = select ? (select.options[select.selectedIndex]?.dataset.isBase === '1') : true;
    const decimals = isVnd ? 0 : 2;

    const num = parseFloat(value.toString().replace(/[^0-9.]/g, ''));
    if (isNaN(num)) return '';

    return num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function unformatMoney(value) {
    if (value === undefined || value === null || value === '') return 0;
    // Replace everything except digits and decimal point
    return parseFloat(value.toString().replace(/[^0-9.]/g, '')) || 0;
}

function initMoneyInputs() {
    // Apply to price inputs
    document.querySelectorAll('.price-input').forEach(input => {
        if (!input.dataset.moneyInit) {
            setupMoneyInput(input);
            input.dataset.moneyInit = 'true';
        }
    });
    
    // Apply to expense amount inputs
    document.querySelectorAll('.expense-amount').forEach(input => {
        if (!input.dataset.moneyInit) {
            setupMoneyInput(input);
            input.dataset.moneyInit = 'true';
        }
    });
    
    // Apply to paid amount (if visible)
    const paidAmount = document.getElementById('paid_amount');
    if (paidAmount && paidAmount.type !== 'hidden' && !paidAmount.dataset.moneyInit) {
        setupMoneyInput(paidAmount);
        paidAmount.dataset.moneyInit = 'true';
    }
}

function setupMoneyInput(input) {
    // Change type to text for formatting
    input.type = 'text';
    input.inputMode = 'numeric';
    
    // Format existing value
    if (input.value) {
        input.value = formatMoney(input.value);
    }
    
    input.addEventListener('input', function(e) {
        const cursorPos = this.selectionStart;
        const oldLength = this.value.length;
        const oldValue = this.value;
        
        // Get raw number
        const rawValue = unformatMoney(this.value);
        
        // Format and set
        this.value = formatMoney(rawValue);
        
        // Adjust cursor position
        const newLength = this.value.length;
        const diff = newLength - oldLength;
        this.setSelectionRange(cursorPos + diff, cursorPos + diff);

        if (this.classList.contains('price-input')) {
            calculateRowTotal();
        }
    });
    
    input.addEventListener('blur', function() {
        if (this.value) {
            this.value = formatMoney(unformatMoney(this.value));
        }
    });
}

function addProductRow() {
    const productList = document.getElementById('productList');
    const newRow = document.createElement('div');
    newRow.className = `product-item ${productIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50'} p-4 border-b last:border-b-0 border-gray-100`;
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <div class="md:col-span-3 product-name-col">
                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                <div class="searchable-select product-searchable" data-index="${productIndex}" data-ajax-url="{{ route('api.products.search') }}">
                    <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" 
                           placeholder="Gõ để tìm sản phẩm..." autocomplete="off">
                    <input type="hidden" name="products[${productIndex}][product_id]" required class="product-id-input">
                    <input type="hidden" name="products[${productIndex}][is_liquidation]" value="0" class="is-liquidation-input">
                    <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg"></div>
                </div>
                <input type="hidden" name="products[${productIndex}][new_name]" class="new-name-input">
                <input type="hidden" name="products[${productIndex}][new_code]" class="new-code-input">
                <input type="hidden" name="products[${productIndex}][new_unit]" class="new-unit-input" value="Cái">
            </div>
            <div class="md:col-span-1">
                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Số lượng</label>
                <input type="number" name="products[${productIndex}][quantity]" min="1" value="1" required
                       onchange="calculateRowTotal(${productIndex})"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
            </div>
            <div class="md:col-span-2">
                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Đơn giá (<span class="currency-symbol">₫</span>)</label>
                <input type="text" name="products[${productIndex}][price]" min="0" required
                       onchange="calculateRowTotal(${productIndex})"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                <small class="block text-xs text-gray-500 mt-1 base-price-reference"></small>
            </div>
            <div class="md:col-span-1">
                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">VAT (%)</label>
                <select name="products[${productIndex}][vat]"
                        onchange="handleVatChange(this)"
                        class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary vat-input">
                    <option value="-1">KCT</option>
                    <option value="0">0%</option>
                    <option value="5">5%</option>
                    <option value="8" selected>8%</option>
                    <option value="10">10%</option>
                    <option value="custom">Khác...</option>
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Bảo hành (tháng)</label>
                <input type="number" name="products[${productIndex}][warranty_months]" min="0" max="120" value=""
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary warranty-input"
                       placeholder="0">
            </div>
            <div class="md:col-span-1 text-center product-tax-col">
                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Thuế nhà thầu</label>
                <input type="hidden" name="products[${productIndex}][contractor_tax_enabled]" value="0">
                <input type="checkbox" name="products[${productIndex}][contractor_tax_enabled]" value="1"
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 contractor-tax-checkbox">
            </div>
            <div class="md:col-span-2">
                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Thành tiền (gồm VAT)</label>
                <input type="text" readonly
                       class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total text-right font-medium">
            </div>
            <div class="md:col-span-1 flex items-end md:items-center">
                <button type="button" onclick="removeProductRow(this)" 
                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    productList.appendChild(newRow);
    productIndex++;
    
    // Initialize searchable select and money inputs for new row
    initAllSearchableSelects();
    initMoneyInputs();
    
    // Apply current contractor tax visibility state
    updateContractorTaxVisibility(window.hasContractorTaxActive);
}

function removeProductRow(btn) {
    const items = document.querySelectorAll('.product-item');
    if (items.length > 1) {
        btn.closest('.product-item').remove();
        calculateTotal();
    }
}

function updatePrice(select, index) {
    const option = select.options[select.selectedIndex];
    const price = option.dataset.price || 0;
    const row = select.closest('.product-item');
    row.querySelector('.price-input').value = price;
    calculateRowTotal(index);
    
    // Auto-calculate expenses based on formulas - REMOVED in favor of manual button
    // autoCalculateExpenses();
}

async function calculateSuggestedExpenses() {
    const customerId = document.querySelector('input[name="customer_id"]').value;
    const items = [];
    document.querySelectorAll('.product-item').forEach(row => {
        const productId = row.querySelector('.product-id-input').value;
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = unformatMoney(row.querySelector('.price-input').value) || 0;
        
        if (productId && quantity > 0) {
            items.push({
                product_id: productId,
                quantity: quantity,
                price: price
            });
        }
    });
    
    if (items.length === 0) {
        alert('Vui lòng thêm sản phẩm trước khi tính chi phí.');
        return;
    }
    
    // Show loading
    const btn = document.querySelector('button[onclick="calculateSuggestedExpenses()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Đang tính...';
    btn.disabled = true;
    
    try {
        const response = await fetch('{{ route("cost-formulas.calculate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                customer_id: customerId,
                items: items
            })
        });
        
        const data = await response.json();
        
        if (data && data.length > 0) {
            // Optional: Clear existing "Automatic" expenses?
            // For now, let's keep it simple and just append.
            // Or maybe clear only empty rows?
            
            let count = 0;
            data.forEach(expense => {
                addExpenseRow({
                    type: expense.type,
                    description: expense.description + ' (Tự động)',
                    amount: expense.amount
                });
                count++;
            });
            alert(`Đã tìm thấy và thêm ${count} mục chi phí phù hợp.`);
            updateExpenseSummary();
        } else {
            alert('Không tìm thấy công thức chi phí phù hợp.');
        }
        
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tính chi phí.');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener to customer select
    // Add event listener to customer select
    const customerSelect = document.querySelector('select[name="customer_id"]');
    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            // removed autoCalculateExpenses();
        });
    }
});
function calculateRowTotal(index) {
    const rows = document.querySelectorAll('.product-item');
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = unformatMoney(row.querySelector('.price-input').value);
        let vatPercent = parseFloat(row.querySelector('.vat-input').value) || 0;
        if (vatPercent < 0) {
            vatPercent = 0;
        }
        const total = qty * price * (1 + vatPercent / 100);
        row.querySelector('.row-total').value = formatMoney(total);
    });
    calculateTotal();
}

function calculateTotal() {
    const select = document.getElementById('currencySelect');
    const option = select.options[select.selectedIndex];
    const symbol = option.dataset.symbol || '';
    
    let subtotal = 0;
    let subtotalWithVat = 0;
    let totalVatAmount = 0;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    
    document.querySelectorAll('.product-item').forEach(row => {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = unformatMoney(row.querySelector('.price-input').value);
        const rowSubtotal = qty * price;
        subtotal += Math.round(rowSubtotal * 100) / 100;
        
        let vatPercent = parseFloat(row.querySelector('.vat-input').value) || 0;
        if (vatPercent < 0) {
            vatPercent = 0;
        }
        const rowSubtotalWithVat = rowSubtotal * (1 + vatPercent / 100);
        subtotalWithVat += Math.round(rowSubtotalWithVat * 100) / 100;

        const rowDiscount = rowSubtotal * discount / 100;
        const rowBaseForVat = rowSubtotal - rowDiscount;
        const rowVatAmount = rowBaseForVat * vatPercent / 100;
        totalVatAmount += Math.round(rowVatAmount * 100) / 100;
    });
    
    const discountAmount = Math.round((subtotal * discount / 100) * 100) / 100;
    const total = Math.round((subtotal - discountAmount + totalVatAmount) * 100) / 100;
    
    document.getElementById('subtotal').value = formatMoney(subtotal);
    const subtotalWithVatEl = document.getElementById('subtotalWithVat');
    if (subtotalWithVatEl) {
        subtotalWithVatEl.value = formatMoney(subtotalWithVat);
    }
    document.getElementById('discountAmount').value = discountAmount > 0 ? formatMoney(discountAmount) : '0';
    document.getElementById('vatAmount').value = totalVatAmount > 0 ? formatMoney(totalVatAmount) : '0';
    document.getElementById('total').value = formatMoney(total);
    
    // Update VND reference for total
    const totalVndRef = document.getElementById('totalVndReference');
    if (totalVndRef) {
        if (option.dataset.isBase === '1') {
            totalVndRef.textContent = '';
        } else {
            const exchangeRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
            const vndValue = Math.round(total * exchangeRate);
            totalVndRef.textContent = `= ${formatMoney(vndValue)} ₫`;
        }
    }

    // Update currency labels
    document.querySelectorAll('.currency-symbol').forEach(el => {
        el.textContent = symbol;
    });
    
    calculateMargin();
    calculateDebt();
    if (typeof calculateMilestoneAmounts === 'function') {
        calculateMilestoneAmounts();
    }
}

function handleVatChange(selectEl) {
    const val = selectEl.value;
    if (val === 'custom') {
        Swal.fire({
            title: 'Nhập % thuế VAT',
            input: 'number',
            inputLabel: 'Tỷ lệ phần trăm (%)',
            inputPlaceholder: 'Nhập số...',
            inputAttributes: {
                min: 0,
                step: 0.01
            },
            showCancelButton: true,
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed && result.value !== '') {
                const customVal = parseFloat(result.value);
                if (!isNaN(customVal) && customVal >= 0) {
                    let option = selectEl.querySelector(`option[value="${customVal}"]`);
                    if (!option) {
                        option = document.createElement('option');
                        option.value = customVal;
                        option.textContent = customVal + '%';
                        const customOption = selectEl.querySelector('option[value="custom"]');
                        selectEl.insertBefore(option, customOption);
                    }
                    selectEl.value = customVal;
                    selectEl.dispatchEvent(new Event('change'));
                } else {
                    const prevVal = selectEl.dataset.prev || 8;
                    selectEl.value = prevVal;
                    selectEl.dispatchEvent(new Event('change'));
                }
            } else {
                const prevVal = selectEl.dataset.prev || 8;
                selectEl.value = prevVal;
                selectEl.dispatchEvent(new Event('change'));
            }
        });
    } else {
        selectEl.dataset.prev = val;
        calculateRowTotal();
    }
}

function calculateMargin() {
    const marginInput = document.getElementById('margin');
    const marginPercentInput = document.getElementById('marginPercent');
    if (!marginInput || !marginPercentInput) return;

    const total = unformatMoney(document.getElementById('total').value);
    const costVnd = unformatMoney(document.getElementById('totalCost').textContent);
    const select = document.getElementById('currencySelect');
    const option = select.options[select.selectedIndex];
    const isBase = option.dataset.isBase === '1';
    const currentRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;

    // Output cost directly since users enter internal costs using the selected transaction currency
    const costInCurrency = costVnd;
    
    const margin = total - costInCurrency;
    const marginPercent = total > 0 ? (margin / total * 100).toFixed(2) : 0;
    
    marginInput.value = formatMoney(margin);
    marginPercentInput.value = marginPercent + '%';
    
    const marginWarning = document.getElementById('marginWarning');
    
    // Remove all color classes
    marginInput.classList.remove('bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700', 'bg-yellow-50', 'text-yellow-700');
    marginPercentInput.classList.remove('bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700', 'bg-yellow-50', 'text-yellow-700');
    
    if (margin < 0) {
        // Negative margin (loss)
        marginInput.classList.add('bg-red-50', 'text-red-700');
        marginPercentInput.classList.add('bg-red-50', 'text-red-700');
        if (marginWarning) marginWarning.classList.remove('hidden');
    } else if (marginPercent < 10) {
        // Low margin
        marginInput.classList.add('bg-yellow-50', 'text-yellow-700');
        marginPercentInput.classList.add('bg-yellow-50', 'text-yellow-700');
        if (marginWarning) marginWarning.classList.add('hidden');
    } else {
        // Good margin
        marginInput.classList.add('bg-green-50', 'text-green-700');
        marginPercentInput.classList.add('bg-green-50', 'text-green-700');
        if (marginWarning) marginWarning.classList.add('hidden');
    }
}

function calculateDebt() {
    const debtInput = document.getElementById('debt');
    const paidInput = document.getElementById('paid_amount');
    if (!debtInput || !paidInput) return;

    const total = unformatMoney(document.getElementById('total').value);
    const paid = unformatMoney(paidInput.value);
    const debt = total - paid;
    
    debtInput.value = formatMoney(debt);
}

// Expense functions
function addExpenseRow(data = null) {
    const expenseList = document.getElementById('expenseList');
    const newRow = document.createElement('div');
    newRow.className = 'expense-item bg-yellow-50 p-3 rounded-lg';
    
    // Default values
    const type = data ? data.type : 'shipping';
    const description = data ? data.description : '';
    const amount = data ? formatMoney(data.amount) : '0';
    
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại chi phí</label>
                <select name="expenses[${expenseIndex}][type]" onchange="updateExpenseSummary()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-type">
                    <option value="shipping" ${type === 'shipping' ? 'selected' : ''}>Vận chuyển</option>
                    <option value="marketing" ${type === 'marketing' ? 'selected' : ''}>Marketing</option>
                    <option value="commission" ${type === 'commission' ? 'selected' : ''}>Hoa hồng</option>
                    <option value="other" ${type === 'other' ? 'selected' : ''}>Khác</option>
                </select>
            </div>
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                <input type="text" name="expenses[${expenseIndex}][description]" value="${description}" placeholder="VD: Chi phí vận chuyển"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền (<span class="currency-symbol">${document.getElementById('currencySelect') ? (document.getElementById('currencySelect').options[document.getElementById('currencySelect').selectedIndex].dataset.symbol || '₫') : '₫'}</span>)</label>
                <input type="text" name="expenses[${expenseIndex}][amount]" value="${amount}"
                       onchange="updateExpenseSummary()"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-amount price-input">
            </div>
            <div class="md:col-span-1 flex items-end">
                <button type="button" onclick="removeExpenseRow(this)" 
                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    expenseList.appendChild(newRow);
    expenseIndex++;
    initMoneyInputs();
    updateExpenseSummary();
}

function removeExpenseRow(btn) {
    const items = document.querySelectorAll('.expense-item');
    if (items.length > 0) { // Allow removing all if needed, or keep 1
        btn.closest('.expense-item').remove();
        updateExpenseSummary();
    }
}

function updateExpenseSummary() {
    let shipping = 0, marketing = 0, commission = 0, other = 0;
    
    document.querySelectorAll('.expense-item').forEach(row => {
        const type = row.querySelector('.expense-type').value;
        const amount = unformatMoney(row.querySelector('.expense-amount').value);
        
        switch(type) {
            case 'shipping': shipping += amount; break;
            case 'marketing': marketing += amount; break;
            case 'commission': commission += amount; break;
            case 'other': other += amount; break;
        }
    });
    
    const total = shipping + marketing + commission + other;
    
    document.getElementById('shippingTotal').textContent = formatMoney(shipping);
    document.getElementById('marketingTotal').textContent = formatMoney(marketing);
    document.getElementById('commissionTotal').textContent = formatMoney(commission);
    document.getElementById('otherTotal').textContent = formatMoney(other);
    document.getElementById('totalCost').textContent = formatMoney(total);
    
    calculateMargin();
}

// Validation function
function validateAndSubmit() {
    if (isSubmitting) return;

    const errors = [];
    const errorContainer = document.getElementById('validationErrors');
    const errorList = document.getElementById('errorList');
    
    // Reset error styles
    document.querySelectorAll('.border-red-500').forEach(el => {
        el.classList.remove('border-red-500');
    });
    document.querySelectorAll('.select2-selection').forEach(el => {
        el.classList.remove('border-red-500');
    });
    
    // Check required fields
    const code = document.querySelector('input[name="code"]');
    if (!code.value.trim()) {
        errors.push('Mã đơn hàng');
        code.classList.add('border-red-500');
    }
    
    const customerId = document.querySelector('select[name="customer_id"]');
    if (!customerId || !customerId.value) {
        errors.push('Khách hàng');
        const select2Selection = document.querySelector('.select2-selection');
        if (select2Selection) {
            select2Selection.classList.add('border-red-500');
        }
    }

    const contactId = document.querySelector('select[name="contact_id"]');
    if (!contactId || !contactId.value) {
        errors.push('Người phụ trách (P.I.C)');
        if (contactId) contactId.classList.add('border-red-500');
    }
    
    const date = document.querySelector('input[name="date"]');
    if (!date.value) {
        errors.push('Ngày tạo');
        date.classList.add('border-red-500');
    }
    
    // Check products
    let hasValidProduct = false;
    document.querySelectorAll('.product-item').forEach((row, index) => {
        const productId = row.querySelector('.product-id-input');
        const productInput = row.querySelector('.searchable-input');
        const quantity = row.querySelector('.quantity-input');
        const price = row.querySelector('.price-input');
        
        if (!productId.value) {
            if (index === 0 || productInput.value.trim()) {
                errors.push(`Sản phẩm (dòng ${index + 1})`);
                productInput.classList.add('border-red-500');
            }
        } else {
            hasValidProduct = true;
            
            // If it is a new product, validate new name and new code
            if (productId.value === 'new') {
                const newName = row.querySelector('.new-name-input');
                const newCode = row.querySelector('.new-code-input');
                if (!newName || !newName.value.trim() || !newCode || !newCode.value.trim()) {
                    errors.push(`Sản phẩm mới chưa hợp lệ (dòng ${index + 1})`);
                    productInput.classList.add('border-red-500');
                }
            }
        }
        
        if (productId.value) {
            if (!quantity.value || parseFloat(quantity.value) < 1) {
                errors.push(`Số lượng (dòng ${index + 1})`);
                quantity.classList.add('border-red-500');
            }
            const priceValue = unformatMoney(price.value);
            if (!price.value || priceValue < 0) {
                errors.push(`Đơn giá (dòng ${index + 1})`);
                price.classList.add('border-red-500');
            }
        }
    });
    
    if (!hasValidProduct) {
        errors.push('Cần ít nhất 1 sản phẩm');
    }
    
    // Show confirmation modal or submit
    if (errors.length > 0) {
        errorList.innerHTML = errors.map(e => `<li>${e}</li>`).join('');
        errorContainer.classList.remove('hidden');
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        errorContainer.classList.add('hidden');
        
        Swal.fire({
            title: 'Xác nhận lưu đơn hàng?',
            text: "Bạn có chắc chắn muốn lưu đơn hàng này không?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Đồng ý, lưu ngay!',
            cancelButtonText: 'Hủy',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                isSubmitting = true;
                const submitBtn = document.querySelector('button[onclick="validateAndSubmit()"]');
                if (submitBtn) {
                    submitBtn.classList.add('opacity-75', 'pointer-events-none');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang lưu đơn hàng...';
                }

                // Unformat money values before submit
                document.querySelectorAll('.price-input').forEach(input => {
                    input.value = unformatMoney(input.value);
                });
                document.querySelectorAll('.expense-amount').forEach(input => {
                    input.value = unformatMoney(input.value);
                });
                document.querySelectorAll('.milestone-amount-input').forEach(input => {
                    input.value = unformatMoney(input.value);
                });
                const paidAmount = document.getElementById('paid_amount');
                if (paidAmount) {
                    paidAmount.value = unformatMoney(paidAmount.value);
                }
                
                // Set flag to prevent "Leave site?" warning from app.js
                window.formChanged = false;
                const form = document.getElementById('saleForm');
                if (form) {
                    HTMLFormElement.prototype.submit.call(form);
                }
            }
        });
    }
}

// ─── Multi-Currency Functions ───────────────────────────────────
const baseCurrencyId = {{ $baseCurrencyId ?? 'null' }};
let currentExchangeRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;

function onCurrencyChange() {
    const select = document.getElementById('currencySelect');
    const option = select.options[select.selectedIndex];
    const isBase = option.dataset.isBase === '1';
    const exchangeRateGroup = document.getElementById('exchangeRateGroup');
    
    const oldRate = currentExchangeRate;

    if (isBase) {
        exchangeRateGroup.classList.add('hidden');
        document.getElementById('exchangeRateInput').value = 1;
        currentExchangeRate = 1;
    } else {
        exchangeRateGroup.classList.remove('hidden');
        // We don't update currentExchangeRate here yet, we wait for fetchExchangeRate 
        // OR we update it if the user manually typed it.
        fetchExchangeRate(select.value).then(() => {
            // After fetching new rate, convert all prices
            const newRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
            recalculateAllPrices(oldRate, newRate);
            currentExchangeRate = newRate;
        });
        return; // Exit and wait for fetch
    }
    
    recalculateAllPrices(oldRate, currentExchangeRate);
    calculateTotal();
}

function recalculateAllPrices(oldRate, newRate) {
    if (oldRate === newRate) return;
    
    document.querySelectorAll('.product-item').forEach(row => {
        const priceInput = row.querySelector('.price-input');
        if (priceInput && priceInput.value) {
            const oldPrice = unformatMoney(priceInput.value);
            // Convert back to VND, then to NEW currency
            // PriceNew = (PriceOld * RateOld) / RateNew
            const baseVnd = oldPrice * oldRate;
            const newPrice = baseVnd / newRate;
            priceInput.value = formatMoney(newPrice);
        }
    });

    // Sync Row Totals
    calculateRowTotal();
}

async function fetchExchangeRate(currencyId) {
    const dateInput = document.querySelector('input[name="date"]');
    const date = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];
    
    try {
        const response = await fetch(`{{ route('api.exchange-rate') }}?currency_id=${currencyId}&date=${date}`);
        const data = await response.json();
        
        if (data.rate && !data.is_base) {
            document.getElementById('exchangeRateInput').value = data.rate;
            const sourceLabel = data.source === 'auto' ? '(Vietcombank)' : '(Thủ công)';
            document.getElementById('rateSource').textContent = sourceLabel;
            document.getElementById('rateHint').textContent = 
                `Ngày: ${data.effective_date || date} ${sourceLabel}`;
            calculateTotal();
        } else if (!data.rate && !data.is_base) {
            document.getElementById('rateHint').textContent = 
                '⚠ Chưa có tỷ giá cho ngày này. Vui lòng nhập thủ công.';
            document.getElementById('rateSource').textContent = '';
        }
    } catch (error) {
        console.error('Error fetching exchange rate:', error);
        document.getElementById('rateHint').textContent = '⚠ Lỗi kết nối. Vui lòng nhập tỷ giá thủ công.';
    }
}

function updateDualPriceDisplay(foreignTotal) {
    const select = document.getElementById('currencySelect');
    const option = select.options[select.selectedIndex];
    const isBase = option.dataset.isBase === '1';
    const display = document.getElementById('dualPriceDisplay');
    
    if (isBase || !display) return;
    
    const exchangeRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
    const vndTotal = Math.round(foreignTotal * exchangeRate);
    const currencyCode = option.dataset.code;
    const symbol = option.dataset.symbol;
    
    display.innerHTML = `<span class="font-semibold">${symbol}${formatMoney(foreignTotal)}</span> × ${formatMoney(exchangeRate)} = <span class="font-bold text-blue-900">${formatMoney(vndTotal)} ₫</span>`;
}

function updateContractorTaxVisibility(hasTax) {
    const nameHeader = document.querySelector('.product-name-header');
    const taxHeader = document.querySelector('.product-tax-header');
    if (nameHeader) {
        if (hasTax) {
            nameHeader.classList.remove('md:col-span-4');
            nameHeader.classList.add('md:col-span-3');
        } else {
            nameHeader.classList.remove('md:col-span-3');
            nameHeader.classList.add('md:col-span-4');
        }
    }
    if (taxHeader) {
        if (hasTax) {
            taxHeader.classList.remove('hidden');
        } else {
            taxHeader.classList.add('hidden');
        }
    }

    document.querySelectorAll('.product-item').forEach(row => {
        const nameCol = row.querySelector('.product-name-col');
        const taxCol = row.querySelector('.product-tax-col');
        const checkbox = row.querySelector('.contractor-tax-checkbox');

        if (nameCol) {
            if (hasTax) {
                nameCol.classList.remove('md:col-span-4');
                nameCol.classList.add('md:col-span-3');
            } else {
                nameCol.classList.remove('md:col-span-3');
                nameCol.classList.add('md:col-span-4');
            }
        }
        if (taxCol) {
            if (hasTax) {
                taxCol.classList.remove('hidden');
                taxCol.classList.add('md:col-span-1');
            } else {
                taxCol.classList.add('hidden');
                taxCol.classList.remove('md:col-span-1');
            }
        }
        if (!hasTax && checkbox) {
            checkbox.checked = false;
        }
    });
}

window.hasContractorTaxActive = false;
window.addEventListener('expense-updated', function(e) {
    const expenses = e.detail.expenses || [];
    window.hasContractorTaxActive = expenses.some(exp => exp.type === 'Thuế nhà thầu');
    updateContractorTaxVisibility(window.hasContractorTaxActive);
});

// Re-fetch rate when date changes
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.querySelector('input[name="date"]');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            const select = document.getElementById('currencySelect');
            const option = select.options[select.selectedIndex];
            if (option.dataset.isBase !== '1') {
                fetchExchangeRate(select.value);
            }
        });
    }
    // Initialize currency display on page load
    onCurrencyChange();
    
    // Initialize contractor tax visibility state synchronously
    window.hasContractorTaxActive = window.initialHasContractorTax;
    updateContractorTaxVisibility(window.hasContractorTaxActive);



    const milestonePresetSelect = document.getElementById('milestonePresetSelect');
    if (milestonePresetSelect) {
        milestonePresetSelect.addEventListener('change', function() {
            onMilestonePresetChange(this.value);
        });
    }

    // Detect manual changes on milestone inputs -> switch preset to 'custom'
    const milestoneList = document.getElementById('milestoneList');
    if (milestoneList) {
        milestoneList.addEventListener('input', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
                switchMilestonePresetToCustom();
            }
        });
    }
});

// --- Payment Term & Due Date Functions ---
window.selectedCustomerDebtDays = 0;
let milestoneIndex = 0;

function onMilestonePresetChange(preset) {
    const list = document.getElementById('milestoneList');
    const tableContainer = document.getElementById('milestonesTableContainer');
    const exceptionFileInput = document.getElementById('bodExceptionFileInput');
    const typeInput = document.getElementById('payment_term_type');
    
    if (!list || !tableContainer) return;
    
    list.innerHTML = '';
    milestoneIndex = 0;
    
    if (exceptionFileInput) exceptionFileInput.classList.add('hidden');
    
    if (!preset) {
        tableContainer.classList.add('hidden');
        if (typeInput) typeInput.value = '';
        return;
    }
    
    tableContainer.classList.remove('hidden');
    
    if (preset === 'customer_default') {
        if (typeInput) typeInput.value = 'milestones';
        const customerSelect = document.querySelector('select[name="customer_id"]');
        if (customerSelect && customerSelect.value) {
            const customerOpt = customerSelect.options[customerSelect.selectedIndex];
            const paymentTerms = customerOpt ? customerOpt.dataset.paymentTerms : null;
            if (customerOpt && paymentTerms) {
                try {
                    const milestones = JSON.parse(paymentTerms);
                    if (milestones && milestones.length > 0) {
                        milestones.forEach(ms => addPaymentMilestone(ms));
                    }
                } catch (e) {
                    console.error('Error parsing milestones:', e);
                }
            }
        }
    } else if (preset.startsWith('template_')) {
        const presetSelect = document.getElementById('milestonePresetSelect');
        const selectedOpt = presetSelect.querySelector(`option[value="${preset}"]`);
        
        // Auto set hidden payment_term_type based on template code
        const code = selectedOpt.dataset.code || '';
        if (typeInput) {
            if (code.includes('NGOAI_LE')) {
                typeInput.value = 'bod_exception';
                if (exceptionFileInput) exceptionFileInput.classList.remove('hidden');
            } else if (code.includes('TRUOC_KHI_DAT_HANG')) {
                typeInput.value = 'prepaid_100';
            } else if (code.includes('SAU_KHI_GIAO_HANG')) {
                typeInput.value = 'postpaid';
            } else {
                typeInput.value = 'milestones';
            }
        }

        if (selectedOpt && selectedOpt.dataset.items) {
            try {
                const items = JSON.parse(selectedOpt.dataset.items);
                items.forEach(item => {
                    let requiredBefore = 'after_delivery';
                    let isBlocking = 'no';
                    if (item.blocking_stage) {
                        isBlocking = 'yes';
                        if (item.blocking_stage === 'BLOCK_PO_SEND') {
                            requiredBefore = 'before_order';
                        } else if (item.blocking_stage === 'BLOCK_WAREHOUSE_EXPORT') {
                            requiredBefore = 'before_export';
                        }
                    }
                    
                    let timing = 'after_contract';
                    if (item.trigger_type === 'ON_GOODS_DELIVERED') {
                        timing = 'after_delivery';
                    } else if (item.trigger_type === 'ON_INVOICE_ISSUED') {
                        timing = 'after_invoice';
                    } else if (item.trigger_type === 'ON_DELIVERY_NOTICE') {
                        timing = 'after_delivery_notice';
                    } else if (item.trigger_type === 'BEFORE_EXPORT') {
                        timing = 'before_export';
                    }

                    addPaymentMilestone({
                        milestone_name: item.milestone_name,
                        percentage: item.percentage,
                        timing: timing,
                        required_before: requiredBefore,
                        is_blocking: isBlocking,
                        required_docs: item.required_docs,
                        due_days: item.due_days,
                    });
                });
            } catch (e) {
                console.error('Error parsing template items:', e);
            }
        }
    } else if (preset === 'custom') {
        if (typeInput) typeInput.value = 'milestones';
        addPaymentMilestone({
            milestone_name: 'Đợt 1',
            percentage: 100,
            timing: 'after_contract',
            required_before: 'after_delivery',
            is_blocking: 'no',
            required_docs: 'none',
            due_days: 0
        });
    }
}

function getContractTotal() {
    const totalEl = document.getElementById('total');
    let totalValue = 0;
    if (totalEl) {
        const rawVal = totalEl.value || totalEl.innerText || '0';
        totalValue = parseFloat(rawVal.replace(/[^0-9.-]/g, '')) || 0;
    }
    return totalValue;
}

function addPaymentMilestone(ms = {}) {
    const list = document.getElementById('milestoneList');
    if (!list) return;

    const isSalesReadOnly = !window.canCustomizePaymentTerms;
    const readOnlyAttr = isSalesReadOnly ? 'readonly' : '';
    const disabledAttr = isSalesReadOnly ? 'disabled' : '';
    const bgClass = isSalesReadOnly ? 'bg-gray-100 text-gray-700 cursor-not-allowed' : '';

    const index = milestoneIndex++;
    const label = ms.milestone_name || ms.label || '';
    const percent = ms.percentage || ms.percent || 0;
    const timing = ms.timing || 'after_contract';
    const requiredBefore = ms.required_before || 'after_delivery';
    const isBlocking = ms.is_blocking || 'yes';
    const requiredDocs = ms.required_docs || 'none';
    const dueDays = ms.due_days || ms.days || 0;

    const total = getContractTotal();
    let numAmount = unformatMoney(ms.amount || 0);
    if (!numAmount && percent > 0 && total > 0) {
        numAmount = Math.round(total * percent / 100);
    }
    const formattedAmount = formatMoney(numAmount);

    const row = document.createElement('tr');
    row.className = 'border-b border-gray-100 hover:bg-gray-50';
    row.id = `milestone-row-${index}`;
    row.innerHTML = `
        <td class="p-2">
            <input type="text" name="payment_terms[${index}][milestone_name]" value="${label}" required ${readOnlyAttr}
                   list="milestone-names" placeholder="VD: Cọc, Đợt 1,..." class="w-full border border-gray-300 rounded px-2 py-1 text-sm ${bgClass || 'bg-white'} text-gray-800">
        </td>
        <td class="p-2">
            <div class="flex items-center">
                <input type="number" name="payment_terms[${index}][percentage]" value="${percent}" required min="0" max="100" step="any" ${readOnlyAttr}
                       class="milestone-percent-input w-20 border border-gray-300 rounded px-2 py-1 text-sm text-right ${bgClass || 'bg-white'} text-gray-800">
                <span class="ml-1 text-sm text-gray-500">%</span>
            </div>
        </td>
        <td class="p-2">
            <div class="flex items-center">
                <input type="text" inputmode="numeric" name="payment_terms[${index}][amount]" value="${formattedAmount}" required ${readOnlyAttr}
                       class="milestone-amount-input w-36 border border-gray-300 rounded px-2 py-1 text-sm text-right font-medium ${bgClass || 'bg-white'} text-gray-800">
                <span class="ml-1 text-sm text-gray-500">₫</span>
            </div>
        </td>
        <td class="p-2">
            <select name="payment_terms[${index}][timing]" ${disabledAttr} class="w-full border border-gray-300 rounded px-2 py-1 text-sm ${bgClass || 'bg-white'} text-gray-800">
                <option value="after_contract" ${timing === 'after_contract' ? 'selected' : ''}>Sau khi ký HĐMB</option>
                <option value="after_delivery_notice" ${timing === 'after_delivery_notice' ? 'selected' : ''}>Sau khi có thông báo giao hàng</option>
                <option value="before_export" ${timing === 'before_export' ? 'selected' : ''}>Trước khi xuất hàng</option>
                <option value="after_delivery" ${timing === 'after_delivery' ? 'selected' : ''}>Sau khi giao hàng</option>
                <option value="after_invoice" ${timing === 'after_invoice' ? 'selected' : ''}>Sau khi xuất hóa đơn</option>
            </select>
            ${isSalesReadOnly ? `<input type="hidden" name="payment_terms[${index}][timing]" value="${timing}">` : ''}
        </td>
        <td class="p-2">
            <select name="payment_terms[${index}][required_before]" ${disabledAttr} class="w-full border border-gray-300 rounded px-2 py-1 text-sm ${bgClass || 'bg-white'} text-gray-800">
                <option value="before_order" ${requiredBefore === 'before_order' ? 'selected' : ''}>Trước khi đặt hàng</option>
                <option value="before_export" ${requiredBefore === 'before_export' ? 'selected' : ''}>Trước khi xuất hàng</option>
                <option value="after_delivery" ${requiredBefore === 'after_delivery' ? 'selected' : ''}>Sau khi giao hàng</option>
            </select>
            ${isSalesReadOnly ? `<input type="hidden" name="payment_terms[${index}][required_before]" value="${requiredBefore}">` : ''}
        </td>
        <td class="p-2">
            <select name="payment_terms[${index}][required_docs]" ${disabledAttr} class="w-full border border-gray-300 rounded px-2 py-1 text-sm ${bgClass || 'bg-white'} text-gray-800">
                <option value="unc" ${requiredDocs === 'unc' ? 'selected' : ''}>UNC</option>
                <option value="credit_note" ${requiredDocs === 'credit_note' ? 'selected' : ''}>Giấy báo có</option>
                <option value="other" ${requiredDocs === 'other' ? 'selected' : ''}>Chứng từ khác</option>
                <option value="none" ${requiredDocs === 'none' ? 'selected' : ''}>Không yêu cầu</option>
            </select>
            ${isSalesReadOnly ? `<input type="hidden" name="payment_terms[${index}][required_docs]" value="${requiredDocs}">` : ''}
        </td>
        <td class="p-2">
            <div class="flex items-center">
                <input type="number" name="payment_terms[${index}][due_days]" value="${dueDays}" required min="0"
                       class="w-16 border border-gray-300 rounded px-2 py-1 text-sm text-right bg-white text-gray-800">
                <span class="ml-1 text-xs text-gray-500">ngày</span>
            </div>
            <input type="hidden" name="payment_terms[${index}][is_blocking]" value="${isBlocking}">
            <input type="hidden" name="payment_terms[${index}][status]" value="${ms.status || 'unpaid'}">
            <input type="hidden" name="payment_terms[${index}][confirmed_by]" value="${ms.confirmed_by || ''}">
            <input type="hidden" name="payment_terms[${index}][confirmed_at]" value="${ms.confirmed_at || ''}">
            <input type="hidden" name="payment_terms[${index}][proof_file_path]" value="${ms.proof_file_path || ''}">
            <input type="hidden" name="payment_terms[${index}][bod_approval_file_path]" value="${ms.bod_approval_file_path || ''}">
            <input type="hidden" name="payment_terms[${index}][delegated_to_id]" value="${ms.delegated_to_id || ''}">
        </td>
    `;
    list.appendChild(row);
    calculateMilestoneAmounts();
    
    row.querySelector('.milestone-percent-input').addEventListener('input', function() {
        const totalValue = getContractTotal();
        const pct = parseFloat(this.value) || 0;
        const amtInput = row.querySelector('.milestone-amount-input');
        if (amtInput) {
            amtInput.value = formatMoney(Math.round(totalValue * pct / 100));
        }
        calculateMilestoneAmounts(true);
        switchMilestonePresetToCustom();
    });

    row.querySelector('.milestone-amount-input').addEventListener('input', function() {
        const cursorPos = this.selectionStart;
        const oldLength = this.value.length;
        
        const amt = unformatMoney(this.value);
        this.value = formatMoney(amt);
        
        const newLength = this.value.length;
        const diff = newLength - oldLength;
        if (this.setSelectionRange && cursorPos !== null) {
            this.setSelectionRange(cursorPos + diff, cursorPos + diff);
        }

        const totalValue = getContractTotal();
        const pctInput = row.querySelector('.milestone-percent-input');
        if (pctInput && totalValue > 0) {
            pctInput.value = (amt / totalValue * 100).toFixed(2);
        }
        calculateMilestoneAmounts(true);
        switchMilestonePresetToCustom();
    });
}

function removePaymentMilestone(index) {
    const row = document.getElementById(`milestone-row-${index}`);
    if (row) {
        row.remove();
        calculateMilestoneAmounts();
        switchMilestonePresetToCustom();
    }
}

function calculateMilestoneAmounts(fromManualInput = false) {
    const totalValue = getContractTotal();
    const rows = document.querySelectorAll('#milestoneList tr');
    let percentSum = 0;
    
    rows.forEach(row => {
        const pctInput = row.querySelector('.milestone-percent-input');
        const amtInput = row.querySelector('.milestone-amount-input');
        if (pctInput && amtInput) {
            let pct = parseFloat(pctInput.value) || 0;
            let amt = unformatMoney(amtInput.value);
            
            if (totalValue > 0) {
                if (!fromManualInput) {
                    if (amt > 0) {
                        pct = amt / totalValue * 100;
                        pctInput.value = pct.toFixed(2);
                    } else if (pct > 0) {
                        amt = Math.round(totalValue * pct / 100);
                        amtInput.value = formatMoney(amt);
                    }
                }
            }
            percentSum += pct;
        }
    });
    
    const indicator = document.getElementById('milestonePercentSumIndicator');
    if (indicator) {
        indicator.innerText = `Tổng tỷ lệ: ${percentSum.toFixed(1)}%`;
        if (Math.abs(percentSum - 100) > 0.01) {
            indicator.className = 'text-sm font-semibold text-red-600';
        } else {
            indicator.className = 'text-sm font-semibold text-green-600';
        }
    }
}

function switchMilestonePresetToCustom() {
    const presetSelect = document.getElementById('milestonePresetSelect');
    if (presetSelect && presetSelect.value !== 'custom' && presetSelect.value !== '') {
        presetSelect.value = 'custom';
    }
}

// --- Start of Quick Add Customer Modal Script ---
let modalContactCount = 1;

function updateModalContactHeaders() {
    const cards = $('#modalContactsContainer .modal-contact-card');
    cards.each(function(idx, el) {
        $(el).find('.contact-label').text(`Người liên hệ #${idx + 1}`);
        if (cards.length > 1) {
            $(el).find('.btn-remove-modal-contact').removeClass('hidden');
        } else {
            $(el).find('.btn-remove-modal-contact').addClass('hidden');
        }
    });
}

$(document).on('click', '#modalAddContactBtn', function(e) {
    e.preventDefault();
    const newIndex = modalContactCount++;
    const contactCardHtml = `
        <div class="modal-contact-card p-3 border border-gray-200 rounded-lg bg-gray-50/50 mt-3" data-contact-index="${newIndex}">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase contact-label">Người liên hệ #${newIndex + 1}</span>
                <div class="flex items-center gap-3">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="modal_primary_contact" value="${newIndex}" class="form-radio text-primary h-3.5 w-3.5">
                        <span class="ml-1.5 text-xs text-gray-600">Liên hệ chính</span>
                    </label>
                    <button type="button" class="btn-remove-modal-contact text-red-400 hover:text-red-600 transition-colors">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Họ & Tên <span class="text-red-500">*</span></label>
                    <input type="text" class="contact-name w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nhập họ tên...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                    <input type="text" class="contact-position w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="VD: Giám đốc...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="text" class="contact-phone w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nhập SĐT...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" class="contact-email w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="email@example.com">
                </div>
            </div>
        </div>
    `;
    $('#modalContactsContainer').append(contactCardHtml);
    updateModalContactHeaders();
});

$(document).on('click', '.btn-remove-modal-contact', function(e) {
    e.preventDefault();
    const card = $(this).closest('.modal-contact-card');
    const wasChecked = card.find('input[name="modal_primary_contact"]').is(':checked');
    card.remove();
    if (wasChecked) {
        $('#modalContactsContainer .modal-contact-card').first().find('input[name="modal_primary_contact"]').prop('checked', true);
    }
    updateModalContactHeaders();
});

$(document).on('click', '#btn-modal-search-tax', async function(e) {
    e.preventDefault();
    const taxCode = $('#customerModalForm input[name="tax_code"]').val().trim();
    if (!taxCode) {
        Swal.fire({
            icon: 'warning',
            title: 'Thông báo',
            text: 'Vui lòng nhập mã số thuế trước khi tra cứu',
            confirmButtonColor: '#3085d6',
        });
        return;
    }

    const btn = $(this);
    const originalIcon = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin text-primary"></i>').prop('disabled', true);

    try {
        const response = await fetch(`https://api.vietqr.io/v2/business/${taxCode}`);
        const data = await response.json();
        
        if (data.code === '00' && data.data) {
            const biz = data.data;
            if (biz.name) {
                $('#customerModalForm input[name="name"]').val(biz.name);
            }
            if (biz.address) {
                $('#customerModalForm input[name="address"]').val(biz.address);
            }
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: 'Đã lấy được thông tin doanh nghiệp',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            throw new Error(data.desc || 'Không tìm thấy thông tin cho mã số thuế này');
        }
    } catch (error) {
        console.error('Tax lookup error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi tra cứu',
            text: error.message || 'Có lỗi xảy ra khi tra cứu mã số thuế',
            confirmButtonColor: '#d33',
        });
    } finally {
        btn.html(originalIcon).prop('disabled', false);
    }
});

$(document).on('click', '#btn-quick-add-customer', function(e) {
    e.preventDefault();
    $('select[name="customer_id"]').select2('close');
    $('#addCustomerModal').removeClass('hidden');
    const select2Search = $('.select2-search__field').val() || '';
    if (select2Search) {
        $('#customerModalForm input[name="name"]').val(select2Search);
    }
});

function resetCustomerModal() {
    $('#addCustomerModal').addClass('hidden');
    $('#customerModalForm')[0].reset();
    $('#modalErrors').addClass('hidden').html('');
    const container = $('#modalContactsContainer');
    container.find('.modal-contact-card').slice(1).remove();
    const firstCard = container.find('.modal-contact-card').first();
    firstCard.attr('data-contact-index', '0');
    firstCard.find('input[name="modal_primary_contact"]').val('0').prop('checked', true);
    firstCard.find('.contact-name').val('');
    firstCard.find('.contact-position').val('');
    firstCard.find('.contact-phone').val('');
    firstCard.find('.contact-email').val('');
    modalContactCount = 1;
    updateModalContactHeaders();
}

$('#closeCustomerModal, #cancelCustomerBtn, #modalOverlay').on('click', function() {
    resetCustomerModal();
});

$('#saveCustomerBtn').on('click', async function() {
    const form = $('#customerModalForm');
    const saveBtn = $(this);
    const errorsDiv = $('#modalErrors');

    const name = form.find('input[name="name"]').val().trim();
    const taxCode = form.find('input[name="tax_code"]').val().trim();
    const abvName = form.find('input[name="abv_name"]').val().trim();

    if (!name || !taxCode || !abvName) {
        errorsDiv.removeClass('hidden').html('Vui lòng điền đầy đủ các thông tin bắt buộc của doanh nghiệp (*).');
        return;
    }

    const contacts = [];
    let contactsValid = true;

    $('#modalContactsContainer .modal-contact-card').each(function() {
        const card = $(this);
        const cName = card.find('.contact-name').val().trim();
        const cPosition = card.find('.contact-position').val().trim();
        const cPhone = card.find('.contact-phone').val().trim();
        const cEmail = card.find('.contact-email').val().trim();
        const isPrimary = card.find('input[name="modal_primary_contact"]').is(':checked') ? 1 : 0;

        if (!cName || !cPosition || !cPhone || !cEmail) {
            contactsValid = false;
            return false;
        }

        contacts.push({
            name: cName,
            position: cPosition,
            phone: cPhone,
            email: cEmail,
            is_primary: isPrimary
        });
    });

    if (!contactsValid || contacts.length === 0) {
        errorsDiv.removeClass('hidden').html('Vui lòng điền đầy đủ các trường thông tin bắt buộc (*) của tất cả người liên hệ.');
        return;
    }

    saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1.5"></i> Đang lưu...');
    $('#cancelCustomerBtn').prop('disabled', true);
    errorsDiv.addClass('hidden').html('');

    try {
        const response = await fetch("{{ route('customers.store-ajax') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                name: name,
                tax_code: taxCode,
                abv_name: abvName,
                phone: form.find('input[name="phone"]').val().trim(),
                email: form.find('input[name="email"]').val().trim(),
                address: form.find('input[name="address"]').val().trim(),
                contacts: contacts
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            const customer = result.customer;
            const displayName = customer.name + (customer.code ? ' (' + customer.code + ')' : '');
            const newOption = new Option(displayName, customer.id, true, true);
            newOption.dataset.taxCode = customer.tax_code || '';
            newOption.dataset.abvName = customer.abv_name || '';
            newOption.dataset.debtDays = customer.debt_days || '0';
            newOption.dataset.paymentTerms = JSON.stringify(customer.payment_terms || null);
            
            $('select[name="customer_id"]').append(newOption).trigger('change');
            resetCustomerModal();
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: 'Đã thêm khách hàng mới thành công!',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            let errorMsg = result.message || 'Có lỗi xảy ra khi tạo khách hàng.';
            if (result.errors) {
                errorMsg = Object.values(result.errors).flat().join('<br>');
            }
            errorsDiv.removeClass('hidden').html(errorMsg);
        }
    } catch (error) {
        console.error('Error adding customer:', error);
        errorsDiv.removeClass('hidden').html('Có lỗi kết nối mạng. Vui lòng thử lại.');
    } finally {
        saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-1.5 mt-0.5"></i> Lưu');
        $('#cancelCustomerBtn').prop('disabled', false);
    }
});

// Single contact modal handlers
$(document).on('click', '#btn-quick-add-contact', function(e) {
    e.preventDefault();
    const customerId = $('select[name="customer_id"]').val();
    if (!customerId) {
        Swal.fire({
            icon: 'warning',
            title: 'Thông báo',
            text: 'Vui lòng chọn Khách hàng trước khi thêm người phụ trách mới',
        });
        return;
    }
    $('#addSingleContactModal').removeClass('hidden');
});

function resetSingleContactModal() {
    $('#addSingleContactModal').addClass('hidden');
    $('#singleContactModalForm')[0].reset();
    $('#singleContactModalErrors').addClass('hidden').html('');
}

$('#closeSingleContactModal, #cancelSingleContactBtn, #singleContactModalOverlay').on('click', function() {
    resetSingleContactModal();
});

$('#saveSingleContactBtn').on('click', async function() {
    const customerId = $('select[name="customer_id"]').val();
    if (!customerId) return;

    const form = $('#singleContactModalForm');
    const saveBtn = $(this);
    const errorsDiv = $('#singleContactModalErrors');

    const name = form.find('input[name="name"]').val().trim();
    const position = form.find('input[name="position"]').val().trim();
    const phone = form.find('input[name="phone"]').val().trim();
    const email = form.find('input[name="email"]').val().trim();

    if (!name || !position || !phone || !email) {
        errorsDiv.removeClass('hidden').html('Vui lòng điền đầy đủ các thông tin bắt buộc (*).');
        return;
    }

    saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1.5"></i> Đang lưu...');
    $('#cancelSingleContactBtn').prop('disabled', true);
    errorsDiv.addClass('hidden').html('');

    try {
        const response = await fetch(`/ajax/customers/${customerId}/contacts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                first_name: name,
                position: position,
                phone: phone,
                email: email
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            resetSingleContactModal();
            loadContacts(customerId, result.contact.id);
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: 'Đã thêm người phụ trách mới thành công!',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            let errorMsg = result.message || 'Có lỗi xảy ra khi tạo người phụ trách.';
            if (result.errors) {
                errorMsg = Object.values(result.errors).flat().join('<br>');
            }
            errorsDiv.removeClass('hidden').html(errorMsg);
        }
    } catch (error) {
        console.error('Error adding contact:', error);
        errorsDiv.removeClass('hidden').html('Có lỗi kết nối mạng. Vui lòng thử lại.');
    } finally {
        saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-1.5 mt-0.5"></i> Lưu');
        $('#cancelSingleContactBtn').prop('disabled', false);
    }
});

// ==========================================
// BOM Import Modal & Quick Paste Logic
// ==========================================
const bomModal = document.getElementById('bomImportModal');
const btnOpenBomModal = document.getElementById('btnOpenBomModal');
const closeBomModal = document.getElementById('closeBomModal');
const closeBomModalFooter = document.getElementById('btnCloseBomModalFooter');
const bomModalOverlay = document.getElementById('bomModalOverlay');
const btnParseBom = document.getElementById('btnParseBom');
const btnClearBomText = document.getElementById('btnClearBomText');
const bomInputText = document.getElementById('bomInputText');
const bomPreviewArea = document.getElementById('bomPreviewArea');
const bomPreviewTableBody = document.getElementById('bomPreviewTableBody');
const btnApplyBomAppend = document.getElementById('btnApplyBomAppend');
const btnApplyBomReplace = document.getElementById('btnApplyBomReplace');
const bomParseStatus = document.getElementById('bomParseStatus');

let parsedBomItems = [];

function openBomModal() {
    if (bomModal) bomModal.classList.remove('hidden');
}

function hideBomModal() {
    if (bomModal) bomModal.classList.add('hidden');
}

if (btnOpenBomModal) btnOpenBomModal.addEventListener('click', openBomModal);
if (closeBomModal) closeBomModal.addEventListener('click', hideBomModal);
if (closeBomModalFooter) closeBomModalFooter.addEventListener('click', hideBomModal);
if (bomModalOverlay) bomModalOverlay.addEventListener('click', hideBomModal);

if (btnClearBomText) {
    btnClearBomText.addEventListener('click', () => {
        bomInputText.value = '';
        bomPreviewArea.classList.add('hidden');
        btnApplyBomAppend.classList.add('hidden');
        btnApplyBomReplace.classList.add('hidden');
        bomParseStatus.textContent = '';
        parsedBomItems = [];
    });
}

if (btnParseBom) {
    btnParseBom.addEventListener('click', async () => {
        const text = bomInputText.value.trim();
        if (!text) {
            alert('Vui lòng dán hoặc nhập nội dung BOM cần phân tích.');
            return;
        }

        const projectSelect = document.getElementById('bomModalProjectSelect');
        const selectedProjId = projectSelect ? projectSelect.value : (document.getElementById('projectSelect')?.value || '');

        btnParseBom.disabled = true;
        btnParseBom.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Đang phân tích...';
        bomParseStatus.innerHTML = '<span class="text-indigo-600">Đang tra cứu cơ sở dữ liệu sản phẩm...</span>';

        try {
            const res = await fetch('{{ route("sales.parse-bom") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    bom_data: text,
                    project_id: selectedProjId
                })
            });

            const data = await res.json();
            if (data.success && data.items && data.items.length > 0) {
                parsedBomItems = data.items;
                renderBomPreview(data.items);
                btnApplyBomAppend.classList.remove('hidden');
                btnApplyBomReplace.classList.remove('hidden');
                bomParseStatus.innerHTML = `<span class="text-emerald-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Đã phân tích thành công ${data.items.length} dòng hàng</span>`;
            } else {
                bomPreviewArea.classList.add('hidden');
                btnApplyBomAppend.classList.add('hidden');
                btnApplyBomReplace.classList.add('hidden');
                bomParseStatus.innerHTML = '<span class="text-amber-600">Không tìm thấy sản phẩm hợp lệ trong nội dung đã nhập.</span>';
            }
        } catch (err) {
            console.error(err);
            bomParseStatus.innerHTML = '<span class="text-red-600">Có lỗi xảy ra khi kết nối máy chủ.</span>';
        } finally {
            btnParseBom.disabled = false;
            btnParseBom.innerHTML = '<i class="fas fa-wand-magic-sparkles mr-2"></i> Phân tích dữ liệu';
        }
    });
}

function renderBomPreview(items) {
    let matchedCount = 0;
    let newCount = 0;
    let html = '';

    items.forEach((it, idx) => {
        if (it.is_matched) matchedCount++;
        else newCount++;

        const statusBadge = it.is_matched 
            ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800"><i class="fas fa-check mr-1"></i> Khớp kho</span>'
            : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800"><i class="fas fa-plus mr-1"></i> SP mới</span>';

        html += `
            <tr class="hover:bg-gray-50">
                <td class="p-2 text-center text-gray-500">${idx + 1}</td>
                <td class="p-2 font-mono font-semibold text-gray-800">${escapeHtml(it.code || '')}</td>
                <td class="p-2 text-gray-700">${escapeHtml(it.name || '')}</td>
                <td class="p-2 text-center font-bold text-gray-900">${it.quantity}</td>
                <td class="p-2 text-right font-medium text-gray-800">${formatMoney(it.price || 0)}</td>
                <td class="p-2 text-center">${statusBadge}</td>
            </tr>
        `;
    });

    document.getElementById('bomParsedCount').textContent = items.length;
    document.getElementById('bomMatchedCount').textContent = matchedCount;
    document.getElementById('bomNewCount').textContent = newCount;
    bomPreviewTableBody.innerHTML = html;
    bomPreviewArea.classList.remove('hidden');
}

function escapeHtml(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function applyBomItems(isAppend) {
    if (!parsedBomItems || parsedBomItems.length === 0) return;

    const productList = document.getElementById('productList');
    if (!isAppend) {
        productList.innerHTML = '';
        productIndex = 0;
    } else {
        // If there's only 1 row and it's completely empty, remove it
        const existingRows = productList.querySelectorAll('.product-item');
        if (existingRows.length === 1) {
            const firstRowId = existingRows[0].querySelector('.product-id-input')?.value;
            const firstRowSearch = existingRows[0].querySelector('.searchable-input')?.value;
            if (!firstRowId && !firstRowSearch) {
                existingRows[0].remove();
                productIndex = 0;
            }
        }
    }

    parsedBomItems.forEach(item => {
        const row = document.createElement('div');
        row.className = `product-item ${productIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50'} p-4 border-b last:border-b-0 border-gray-100`;
        
        const isNew = item.product_id === 'new';
        const newName = isNew ? (item.new_name || item.name || '') : '';
        const newCode = isNew ? (item.new_code || item.code || '') : '';
        const newUnit = item.unit || item.new_unit || 'Cái';
        const displayText = item.display_text || (isNew ? `[SP Mới] ${newName}` : `[${item.code}] ${item.name}`);

        row.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                <div class="md:col-span-3 product-name-col">
                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                    <div class="searchable-select product-searchable" data-index="${productIndex}" data-ajax-url="{{ route('api.products.search') }}">
                        <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary" 
                               placeholder="Gõ để tìm sản phẩm..." autocomplete="off" value="${escapeHtml(displayText)}">
                        <input type="hidden" name="products[${productIndex}][product_id]" required class="product-id-input" value="${item.product_id}">
                        <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg"></div>
                        <input type="hidden" name="products[${productIndex}][is_liquidation]" value="0" class="is-liquidation-input">
                    </div>
                    <input type="hidden" name="products[${productIndex}][new_name]" class="new-name-input" value="${escapeHtml(newName)}">
                    <input type="hidden" name="products[${productIndex}][new_code]" class="new-code-input" value="${escapeHtml(newCode)}">
                    <input type="hidden" name="products[${productIndex}][new_unit]" class="new-unit-input" value="${escapeHtml(newUnit)}">
                </div>
                <div class="md:col-span-1">
                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Số lượng <span class="text-red-500">*</span></label>
                    <input type="number" name="products[${productIndex}][quantity]" min="1" value="${item.quantity || 1}" required
                           onchange="calculateRowTotal(${productIndex})"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                </div>
                <div class="md:col-span-2">
                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Đơn giá <span class="text-red-500">*</span></label>
                    <input type="text" name="products[${productIndex}][price]" min="0" required
                           value="${formatMoney(item.price || 0)}"
                           onchange="calculateRowTotal(${productIndex})"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input">
                    <small class="block text-xs text-gray-500 mt-1 base-price-reference"></small>
                </div>
                <div class="md:col-span-1">
                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">VAT (%)</label>
                    <select name="products[${productIndex}][vat]"
                            onchange="handleVatChange(this)"
                            class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary vat-input">
                        <option value="-1">KCT</option>
                        <option value="0">0%</option>
                        <option value="5">5%</option>
                        <option value="8" ${item.vat == 8 ? 'selected' : ''}>8%</option>
                        <option value="10" ${item.vat == 10 ? 'selected' : ''}>10%</option>
                        <option value="custom">Khác...</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Bảo hành (tháng)</label>
                    <input type="number" name="products[${productIndex}][warranty_months]" min="0" max="120" value="${item.warranty_months || 12}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary warranty-input"
                           placeholder="0">
                </div>
                <div class="md:col-span-1 text-center product-tax-col">
                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Thuế nhà thầu</label>
                    <input type="hidden" name="products[${productIndex}][contractor_tax_enabled]" value="0">
                    <input type="checkbox" name="products[${productIndex}][contractor_tax_enabled]" value="1"
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 contractor-tax-checkbox">
                </div>
                <div class="md:col-span-2">
                    <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Thành tiền (gồm VAT)</label>
                    <input type="text" readonly
                           class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total text-right font-medium">
                </div>
                <div class="md:col-span-1 flex items-end md:items-center">
                    <button type="button" onclick="removeProductRow(this)" 
                            class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        productList.appendChild(row);
        productIndex++;
    });

    initAllSearchableSelects();
    initMoneyInputs();
    updateContractorTaxVisibility(window.hasContractorTaxActive);
    calculateTotal();
    hideBomModal();
}

if (btnApplyBomAppend) btnApplyBomAppend.addEventListener('click', () => applyBomItems(true));
if (btnApplyBomReplace) btnApplyBomReplace.addEventListener('click', () => applyBomItems(false));
</script>

@endpush
