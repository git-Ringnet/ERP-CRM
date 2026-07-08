@extends('layouts.app')

@section('title', 'Sửa đơn hàng')
@section('page-title', 'Sửa đơn hàng: ' . $sale->code)

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <form action="{{ route('sales.update', $sale->id) }}" method="POST" id="saleForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        @php $isLocked = $sale->pl_status === 'approved'; @endphp
        
        @if($isLocked)
        <div class="p-4 mx-6 mt-6 bg-yellow-50 border border-yellow-200 rounded-lg flex items-start">
            <i class="fas fa-lock text-yellow-600 mt-1 mr-3"></i>
            <div class="text-sm text-yellow-800">
                <span class="font-bold">Đơn hàng đã được duyệt P&L:</span> Thông tin sản phẩm, chi phí và khách hàng đã bị khóa để đảm bảo tính nhất quán.
            </div>
        </div>
        @endif

        <div class="p-4 sm:p-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mã đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" value="{{ old('code', $sale->code) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Có thể sửa mã đơn hàng nếu cần</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Loại đơn hàng <span class="text-red-500">*</span>
                    </label>
                    <select name="type" id="saleType" required onchange="toggleProjectSelect()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="retail" {{ old('type', $sale->type) == 'retail' ? 'selected' : '' }}>Bán lẻ</option>
                        <option value="project" {{ old('type', $sale->type) == 'project' ? 'selected' : '' }}>Bán theo dự án</option>
                    </select>
                    @if($isLocked) <input type="hidden" name="type" value="{{ $sale->type }}"> @endif
                </div>
            </div>

            <!-- Project Selection (shown when type = project) -->
            <div id="projectSelectWrapper" class="{{ old('type', $sale->type) == 'project' ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-project-diagram text-purple-500 mr-1"></i>
                            Dự án
                        </label>
                        <select name="project_id" id="projectSelect" onchange="handleProjectSelection()"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Chọn dự án --</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" 
                                    data-customer-id="{{ $project->customer_id }}"
                                    data-customer-name="{{ $project->customer ? $project->customer->name . ($project->customer->code ? ' (' . $project->customer->code . ')' : '') : '' }}"
                                    {{ old('project_id', $sale->project_id) == $project->id ? 'selected' : '' }}>
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
                        Khách hàng <span class="text-red-500">*</span>
                    </label>
                    <div class="searchable-select {{ $isLocked ? 'pointer-events-none opacity-80' : '' }}" id="customerSelect">
                        @php
                            $oldCustomerId = old('customer_id', $sale->customer_id);
                            $oldCustomerName = '';
                            if ($oldCustomerId) {
                                $c = $customers->firstWhere('id', $oldCustomerId);
                                if ($c) {
                                    $oldCustomerName = $c->name . ($c->code ? ' (' . $c->code . ')' : '');
                                }
                            }
                        @endphp
                        <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('customer_id') border-red-500 @enderror {{ $isLocked ? 'bg-gray-100' : '' }}" 
                               placeholder="Gõ để tìm khách hàng..." autocomplete="off"
                               value="{{ $oldCustomerName }}" {{ $isLocked ? 'readonly' : '' }}>
                        <input type="hidden" name="customer_id" required value="{{ $oldCustomerId }}">
                        @if(!$isLocked)
                        <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                            @foreach($customers as $customer)
                                <div class="searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer" 
                                     data-value="{{ $customer->id }}" 
                                     data-text="{{ $customer->name }}{{ $customer->code ? ' (' . $customer->code . ')' : '' }}"
                                     data-milestones="{{ json_encode($customer->payment_terms) }}"
                                     data-debt-days="{{ $customer->debt_days ?? '' }}">
                                    {{ $customer->name }}{{ $customer->code ? ' (' . $customer->code . ')' : '' }}
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @error('customer_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Người phụ trách (P.I.C) <span class="text-red-500">*</span>
                    </label>
                    <select name="contact_id" id="contact_id" required {{ $isLocked ? 'disabled' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('contact_id') border-red-500 @enderror {{ $isLocked ? 'bg-gray-100' : '' }}">
                        <option value="">Chọn người phụ trách</option>
                    </select>
                    @if($isLocked) <input type="hidden" name="contact_id" value="{{ $sale->contact_id }}"> @endif
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
                    <input type="date" name="date" value="{{ old('date', $sale->date->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ giao hàng</label>
                    <textarea name="delivery_address" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('delivery_address', $sale->delivery_address) }}</textarea>
                </div>
                <div>
                    {{-- Status removed from edit per request --}}
                </div>
            </div>

            <!-- Currency Selection -->
            <div class="border-t pt-4">
                <h4 class="text-lg font-medium text-gray-900 mb-3">
                    <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Tiền tệ giao dịch
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Loại tiền tệ</label>
                        <select name="currency_id" id="currencySelect" onchange="onCurrencyChange()" {{ $isLocked ? 'disabled' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary {{ $isLocked ? 'bg-gray-100' : '' }}">
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->id }}"
                                    data-is-base="{{ $currency->is_base ? '1' : '0' }}"
                                    data-code="{{ $currency->code }}"
                                    data-symbol="{{ $currency->symbol }}"
                                    {{ old('currency_id', $sale->currency_id ?? $baseCurrencyId) == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->code }} - {{ $currency->name_vi }}
                                </option>
                            @endforeach
                        </select>
                        @if($isLocked) <input type="hidden" name="currency_id" value="{{ $sale->currency_id }}"> @endif
                    </div>
                    <div id="exchangeRateGroup" class="{{ ($sale->currency_id && $sale->currency_id != $baseCurrencyId) ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tỷ giá (1 ngoại tệ = ? VND)
                            <span id="rateSource" class="text-xs text-blue-500 ml-1"></span>
                        </label>
                        <input type="number" name="exchange_rate" id="exchangeRateInput" step="0.000001" min="0"
                            value="{{ old('exchange_rate', $sale->exchange_rate ? floatval($sale->exchange_rate) : 1) }}"
                            onchange="calculateTotal()" {{ $isLocked ? 'readonly' : '' }}
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary {{ $isLocked ? 'bg-gray-100' : '' }}">
                        <p class="text-xs text-gray-500 mt-1" id="rateHint">Tỷ giá từ lúc tạo đơn</p>
                    </div>
                    <div id="dualPricePlaceholder" class="hidden">
                        <!-- Removed dualPriceGroup as per user request -->
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            @php
                $isForeign = $sale->currency && !$sale->currency->is_base;
                $decimals = $isForeign ? ($sale->currency->decimal_places ?? 2) : 0;
            @endphp
            <div class="border-t pt-4">
                   <h4 class="text-lg font-medium text-gray-900 mb-4">Chi tiết sản phẩm</h4>
                
                <!-- Product List Header (Desktop) -->
                <div class="hidden md:grid grid-cols-12 gap-3 px-4 py-2 bg-gray-100 border border-gray-200 rounded-t-lg font-bold text-gray-700">
                    <div class="md:col-span-3 product-name-header">Sản phẩm <span class="text-red-500">*</span></div>
                    <div class="md:col-span-1">Số lượng <span class="text-red-500">*</span></div>
                    <div class="md:col-span-2">Đơn giá (<span class="currency-symbol">{{ $sale->currency ? ($sale->currency->symbol ?? $sale->currency->code) : '₫' }}</span>) <span class="text-red-500">*</span></div>
                    <div class="md:col-span-1 text-center">VAT (%)</div>
                    <div class="md:col-span-1">Bảo hành</div>
                    <div class="md:col-span-1 text-center product-tax-header">Thuế nhà thầu</div>
                    <div class="md:col-span-2 text-right">Thành tiền (gồm VAT)</div>
                    <div class="md:col-span-1 text-center"><i class="fas fa-cog"></i></div>
                </div>

                <div id="productList" class="space-y-0 border-x border-b border-gray-200 rounded-b-lg">
                    @foreach($sale->items as $index => $item)
                    <div class="product-item {{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }} p-4 border-b last:border-b-0 border-gray-100" data-index="{{ $index }}">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                            <div class="md:col-span-3 product-name-col">
                                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Sản phẩm <span class="text-red-500">*</span></label>
                                <div class="searchable-select product-searchable {{ $isLocked ? 'pointer-events-none opacity-80' : '' }}" data-index="{{ $index }}" data-ajax-url="{{ route('api.products.search') }}">
                                    <input type="text" class="searchable-input w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary {{ $isLocked ? 'bg-gray-100' : '' }}" 
                                           placeholder="Gõ để tìm sản phẩm..." autocomplete="off"
                                           value="[{{ $item->product?->code }}] {{ $item->product?->name }}" {{ $isLocked ? 'readonly' : '' }}>
                                    <input type="hidden" name="products[{{ $index }}][product_id]" required class="product-id-input" value="{{ $item->product_id }}">
                                    @if(!$isLocked)
                                    <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg"></div>
                                    @endif
                                </div>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Số lượng <span class="text-red-500">*</span></label>
                                <input type="number" name="products[{{ $index }}][quantity]" min="1" value="{{ $item->quantity }}" required
                                       onchange="calculateRowTotal({{ $index }})" {{ $isLocked ? 'readonly' : '' }}
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary quantity-input {{ $isLocked ? 'bg-gray-100' : '' }}">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Đơn giá <span class="text-red-500">*</span></label>
                                <input type="text" name="products[{{ $index }}][price]" min="0" value="{{ number_format($item->price, $decimals, '.', ',') }}" required
                                       onchange="calculateRowTotal({{ $index }})" {{ $isLocked ? 'readonly' : '' }}
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input {{ $isLocked ? 'bg-gray-100' : '' }}">
                                <small class="block text-xs text-gray-500 mt-1 base-price-reference"></small>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">VAT (%)</label>
                                <select name="products[{{ $index }}][vat]"
                                        onchange="handleVatChange(this)"
                                        {{ $isLocked ? 'disabled' : '' }}
                                        class="w-full border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary vat-input {{ $isLocked ? 'bg-gray-100' : '' }}">
                                    @php
                                        $vatVal = isset($item->vat) ? (float)$item->vat : 8;
                                    @endphp
                                    <option value="-1" {{ $vatVal == -1 ? 'selected' : '' }}>KCT</option>
                                    <option value="0" {{ $vatVal == 0 ? 'selected' : '' }}>0%</option>
                                    <option value="5" {{ $vatVal == 5 ? 'selected' : '' }}>5%</option>
                                    <option value="8" {{ $vatVal == 8 ? 'selected' : '' }}>8%</option>
                                    <option value="10" {{ $vatVal == 10 ? 'selected' : '' }}>10%</option>
                                    @if(!in_array($vatVal, [-1, 0, 5, 8, 10]))
                                        <option value="{{ $vatVal }}" selected>{{ $vatVal }}%</option>
                                    @endif
                                    <option value="custom">Khác...</option>
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Bảo hành</label>
                                <input type="number" name="products[{{ $index }}][warranty_months]" min="0" max="120" value="{{ $item->warranty_months }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary warranty-input {{ $isLocked ? 'bg-gray-100' : '' }}"
                                       placeholder="0" {{ $isLocked ? 'readonly' : '' }}>
                            </div>
                            <div class="md:col-span-1 text-center product-tax-col">
                                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Thuế nhà thầu</label>
                                <input type="hidden" name="products[{{ $index }}][contractor_tax_enabled]" value="0">
                                <input type="checkbox" name="products[{{ $index }}][contractor_tax_enabled]" value="1"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 contractor-tax-checkbox"
                                       {{ $item->contractor_tax_enabled ? 'checked' : '' }}
                                       {{ $isLocked ? 'disabled' : '' }}>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Thành tiền (gồm VAT)</label>
                                <input type="text" readonly value="{{ number_format($item->total, $decimals, '.', ',') }}"
                                       class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2 row-total text-right font-medium">
                            </div>
                            <div class="md:col-span-1 flex items-end md:items-center">
                                @if(!$isLocked)
                                <button type="button" onclick="removeProductRow(this)" 
                                        class="w-full px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if(!$isLocked)
                <button type="button" onclick="addProductRow()" 
                        class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm sản phẩm
                </button>
                @endif
            </div>

            {{-- Expenses Section — Flexible P/L Cost Entry --}}
            @php
                $expenseData = $sale->expenses->map(fn($e) => [
                    'type' => $e->type,
                    'input_mode' => $e->input_mode ?? 'fixed',
                    'percent_value' => $e->percent_value,
                    'amount' => $e->amount,
                    'description' => $e->description ?? '',
                ])->toArray();
                // Không tự động load expenses mặc định nếu rỗng - cho phép user xóa hết
            @endphp
            @include('sales.partials.expense-section', [
                'expenses' => $expenseData,
                'currencySymbol' => $sale->currency ? ($sale->currency->symbol ?? $sale->currency->code) : '₫',
                'isLocked' => $isLocked,
            ])
            <div class="border-t pt-4">
                <div class="space-y-3 max-w-md ml-auto">
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-medium text-gray-700">Tổng tiền hàng (chưa VAT) (<span class="currency-symbol">₫</span>)</label>
                        <input type="text" id="subtotal" readonly value="{{ number_format($sale->subtotal, 0, '.', ',') }}"
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
                            <input type="number" name="discount" id="discount" value="{{ old('discount', $sale->discount ? (int)$sale->discount : '') }}" min="0" max="100" step="1"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')" onchange="calculateTotal()"
                                   class="w-16 text-center border border-gray-300 rounded-lg px-2 py-2 focus:outline-none focus:ring-2 focus:ring-primary {{ $isLocked ? 'bg-gray-100' : '' }}"
                                   placeholder="0" {{ $isLocked ? 'readonly' : '' }}>
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
                            <input type="text" id="total" readonly value="{{ number_format($isForeign ? ($sale->total_foreign ?? ($sale->total / ($sale->exchange_rate ?: 1))) : $sale->total, $decimals, '.', ',') }}"
                                   class="w-48 text-right border border-gray-200 bg-primary/10 rounded-lg px-3 py-2 font-bold text-lg text-primary">
                            <small id="totalVndReference" class="block text-xs text-gray-500 mt-1"></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment terms type and Milestones Editor -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4">
                <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-file-invoice-dollar text-primary mr-2"></i> Lộ trình thanh toán chi tiết
                </h4>
                
                <input type="hidden" name="payment_term_type" id="payment_term_type" value="{{ $sale->payment_term_type }}">
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
                            <option value="custom">Tùy chỉnh...</option>
                        </select>
                    </div>
                </div>

                <div id="bodExceptionFileInput" class="hidden mt-3">
                    <label class="block text-xs font-medium text-red-700 mb-1">
                        <i class="fas fa-exclamation-triangle"></i> Tệp phê duyệt của BOD (Có thể tải lên tệp mới)
                    </label>
                    @if($sale->payment_exception_file)
                        <div class="mb-1 text-xs text-green-700 flex items-center">
                            <i class="fas fa-check-circle mr-1"></i> Đã có tệp đính kèm: 
                            <a href="{{ asset('storage/' . $sale->payment_exception_file) }}" target="_blank" class="underline ml-1 font-medium">Xem tệp</a>
                        </div>
                    @endif
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
                        <table class="w-full text-left border-collapse min-w-[1200px]">
                            <thead>
                                <tr class="bg-gray-100 text-xs font-semibold text-gray-600 border-b border-gray-200">
                                    <th class="p-2 min-w-[250px] text-sm">Tên đợt thanh toán</th>
                                    <th class="p-2 min-w-[90px] text-sm">Tỷ lệ (%)</th>
                                    <th class="p-2 min-w-[160px] text-sm">Số tiền (Tự tính)</th>
                                    <th class="p-2 min-w-[180px] text-sm">Thời điểm thanh toán</th>
                                    <th class="p-2 min-w-[160px] text-sm">Giai đoạn kiểm soát</th>
                                    <th class="p-2 min-w-[80px] text-sm">Có chặn?</th>
                                    <th class="p-2 min-w-[140px] text-sm">Chứng từ bắt buộc</th>
                                    <th class="p-2 min-w-[100px] text-sm">Hạn (ngày)</th>
                                    <th class="p-2 w-10 text-center text-sm">Xóa</th>
                                </tr>
                            </thead>
                            <tbody id="milestoneList" class="divide-y divide-gray-100">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-200">
                        <span id="milestonePercentSumIndicator" class="text-sm font-semibold text-gray-700">Tổng tỷ lệ: 0%</span>
                        <button type="button" onclick="addPaymentMilestone()" class="btn-secondary text-xs py-1.5 px-3">
                            <i class="fas fa-plus mr-1"></i> Thêm đợt thanh toán
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả điều khoản thanh toán</label>
                    <textarea name="payment_term" rows="2" placeholder="VD: Tạm ứng 30%..., thanh toán 70%..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('payment_term', $sale->payment_term) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                    <textarea name="note" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">{{ old('note', $sale->note) }}</textarea>
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

        </div>

        <!-- Actions -->
        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row gap-2 justify-end">
            <a href="{{ route('sales.index') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
            <button type="button" onclick="validateAndSubmit()"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-save mr-2"></i> Cập nhật
            </button>
        </div>
    </form>
</div>
@endsection

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
@php
    $initialExpenses = old('expenses', $sale->expenses);
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
let productIndex = {{ count($sale->items) }};
let isSubmitting = false;

function formatMoney(amount) {
    const select = document.getElementById('currencySelect');
    const isVnd = select ? (select.options[select.selectedIndex]?.dataset.isBase === '1') : true;
    const decimals = isVnd ? 0 : 2;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(amount);
}




// Searchable Select Functions
function initSearchableSelect(container, onSelect) {
    const input = container.querySelector('.searchable-input');
    const hiddenInput = container.querySelector('input[type="hidden"]');
    const dropdown = container.querySelector('.searchable-dropdown');
    if (!dropdown) return; // Fix: return early if dropdown is not rendered (locked)
    
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
        
        if (data.length === 0) {
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
            const opt = document.createElement('div');
            opt.className = 'searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer';
            opt.dataset.value = item.id;
            opt.dataset.text = `[${item.code || ''}] ${item.name}`;
            opt.dataset.price = item.price;
            opt.dataset.isLiquidation = item.is_liquidation;
            opt.dataset.warranty = item.warranty_months;
            opt.dataset.liquidationCount = item.liquidation_count;
            
            opt.innerHTML = `
                <span class="font-medium">[${item.code || ''}]</span> ${item.name}
                ${item.liquidation_count > 0 && item.is_liquidation === 0 ? 
                    `<span class="text-orange-600 italic text-xs ml-1">(Có ${item.liquidation_count} sẵn)</span>` : ''}
            `;
            
            opt.addEventListener('click', () => {
                input.value = opt.dataset.text;
                hiddenInput.value = opt.dataset.value;
                dropdown.classList.add('hidden');
                if (onSelect) onSelect(opt);
            });
            
            dropdown.appendChild(opt);
        });
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
    
    input.addEventListener('keydown', (e) => {
        const visibleOptions = [...options].filter(o => !o.classList.contains('hidden'));
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
    
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

// Format money input (supports decimals for foreign currencies)
function toggleProjectSelect() {
    const saleType = document.getElementById('saleType').value;
    const projectWrapper = document.getElementById('projectSelectWrapper');
    const projectSelect = document.getElementById('projectSelect');
    
    if (saleType === 'project') {
        projectWrapper.classList.remove('hidden');
    } else {
        projectWrapper.classList.add('hidden');
        projectSelect.value = ''; // Clear project selection when switching to retail
    }
}

function handleProjectSelection() {
    const projectSelect = document.getElementById('projectSelect');
    const option = projectSelect.options[projectSelect.selectedIndex];
    
    if (!option || !option.value) return;
    
    const customerId = option.dataset.customerId;
    const customerName = option.dataset.customerName;
    
    if (customerId && customerName) {
        const customerSelect = document.getElementById('customerSelect');
        const input = customerSelect.querySelector('.searchable-input');
        const hiddenInput = customerSelect.querySelector('input[type="hidden"]');
        
        input.value = customerName;
        hiddenInput.value = customerId;
        
        // Load contacts for the project customer
        loadContacts(customerId);
    }
}

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
    return parseFloat(value.toString().replace(/[^0-9.]/g, '')) || 0;
}

function initMoneyInputs() {
    document.querySelectorAll('.price-input').forEach(input => {
        if (!input.dataset.moneyInit) {
            setupMoneyInput(input);
            input.dataset.moneyInit = 'true';
        }
    });
}

function setupMoneyInput(input) {
    input.type = 'text';
    input.inputMode = 'numeric';
    
    // Don't re-format if already formatted (contains comma)
    // Don't re-format if already formatted (contains comma as thousand separator)
    if (input.value && !input.value.includes(',')) {
        // Parse float formatting instead of stripping out decimals to int
        const numValue = parseFloat(input.value);
        input.value = formatMoney(numValue);
    }
    
    input.addEventListener('input', function(e) {
        const cursorPos = this.selectionStart;
        const oldLength = this.value.length;
        const rawValue = unformatMoney(this.value);
        this.value = formatMoney(rawValue);
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

function initAllSearchableSelects() {
    const customerSelect = document.getElementById('customerSelect');
    if (customerSelect && !customerSelect.dataset.initialized) {
        initSearchableSelect(customerSelect, (opt) => {
            // Store customer debt days
            window.selectedCustomerDebtDays = parseInt(opt.dataset.debtDays) || 0;
            

            

            
            // Load contacts for chosen customer
            loadContacts(opt.dataset.value);
        });
        customerSelect.dataset.initialized = 'true';
    }
    
    // Product selects
    document.querySelectorAll('.product-searchable').forEach(container => {
        if (!container.dataset.initialized) {
            initSearchableSelect(container, (opt) => {
                const row = container.closest('.product-item');
                const priceInput = row.querySelector('.price-input');
                const warrantyInput = row.querySelector('.warranty-input');
                
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

document.addEventListener('DOMContentLoaded', function() {
    initAllSearchableSelects();
    initMoneyInputs();
    initProductRowLiveCalc();
    if (document.getElementById('saleType')) {
        toggleProjectSelect();
    }

    // Auto-fill customer if project is pre-selected and customer is empty
    const projectSelect = document.getElementById('projectSelect');
    const customerHiddenInput = document.querySelector('input[name="customer_id"]');
    if (projectSelect && projectSelect.value && (!customerHiddenInput || !customerHiddenInput.value)) {
        handleProjectSelection();
    } else {
        // Load contacts if customer is already populated on load
        const initialCustomerId = customerHiddenInput ? customerHiddenInput.value : '';
        const oldContactId = '{{ old('contact_id', $sale->contact_id) }}';
        if (initialCustomerId) {
            loadContacts(initialCustomerId, oldContactId);
        }
    }


    
    calculateRowTotal();
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
        if (contactSelect) {
            contactSelect.innerHTML = '<option value="">Chọn người phụ trách</option>';
        }
        picDetails.classList.add('hidden');
        contactsData = [];
        return;
    }
    
    try {
        const response = await fetch(`/ajax/customers/${customerId}/contacts`);
        contactsData = await response.json();
        
        if (contactSelect) {
            let options = '<option value="">Chọn người phụ trách</option>';
            contactsData.forEach(contact => {
                const isSelected = selectedContactId == contact.id || (!selectedContactId && contact.is_primary) ? 'selected' : '';
                options += `<option value="${contact.id}" ${isSelected}>${contact.name} ${contact.is_primary ? '(Mặc định)' : ''}</option>`;
            });
            contactSelect.innerHTML = options;
        }
        
        // Trigger update of PIC details
        updatePicDetails();
    } catch (e) {
        console.error('Error fetching contacts:', e);
    }
}

function updatePicDetails() {
    if (!contactSelect) return;
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

if (contactSelect) {
    contactSelect.addEventListener('change', updatePicDetails);
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
                    <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg"></div>
                </div>
                <small class="block text-xs text-gray-500 mt-1 base-price-reference"></small>
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
                <label class="block md:hidden text-sm font-medium text-gray-700 mb-1">Thành tiền (gồm VAT) (<span class="currency-symbol">₫</span>)</label>
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

// Calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateRowTotal();
});

function calculateMargin() {
    const total = unformatMoney(document.getElementById('total').value);
    const costVnd = unformatMoney(document.getElementById('totalCost').textContent);
    const select = document.getElementById('currencySelect');
    const option = select.options[select.selectedIndex];
    const isBase = option.dataset.isBase === '1';
    
    // In edit mode, assuming similar logic as create
    const costInCurrency = costVnd;
    
    const margin = total - costInCurrency;
    const marginPercent = total > 0 ? (margin / total * 100).toFixed(2) : 0;
    
    const marginEl = document.getElementById('margin');
    const marginPercentEl = document.getElementById('marginPercent');
    if (!marginEl || !marginPercentEl) return;

    marginEl.value = formatMoney(margin);
    marginPercentEl.value = marginPercent + '%';
    
    // Update colors based on margin
    marginEl.classList.remove('bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700', 'bg-yellow-50', 'text-yellow-700');
    marginPercentEl.classList.remove('bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700', 'bg-yellow-50', 'text-yellow-700');
    
    if (margin < 0) {
        marginEl.classList.add('bg-red-50', 'text-red-700');
        marginPercentEl.classList.add('bg-red-50', 'text-red-700');
    } else if (marginPercent < 10) {
        marginEl.classList.add('bg-yellow-50', 'text-yellow-700');
        marginPercentEl.classList.add('bg-yellow-50', 'text-yellow-700');
    } else {
        marginEl.classList.add('bg-green-50', 'text-green-700');
        marginPercentEl.classList.add('bg-green-50', 'text-green-700');
    }
}

function calculateDebt() {
    const total = unformatMoney(document.getElementById('total').value);
    const paidInput = document.getElementById('paid_amount');
    const paid = paidInput ? unformatMoney(paidInput.value) : 0;
    const debt = total - paid;
    
    const debtEl = document.getElementById('debt');
    if (debtEl) {
        debtEl.value = formatMoney(debt);
    }
}

// Validation function
function validateAndSubmit() {
    const errors = [];
    const errorContainer = document.getElementById('validationErrors');
    const errorList = document.getElementById('errorList');
    
    // Reset error styles
    document.querySelectorAll('.border-red-500').forEach(el => {
        el.classList.remove('border-red-500');
    });
    
    // Check required fields
    const code = document.querySelector('input[name="code"]');
    if (!code.value.trim()) {
        errors.push('Mã đơn hàng');
        code.classList.add('border-red-500');
    }
    
    const customerId = document.querySelector('input[name="customer_id"]');
    const customerInput = document.querySelector('#customerSelect .searchable-input');
    if (!customerId.value) {
        errors.push('Khách hàng');
        customerInput.classList.add('border-red-500');
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
            errors.push(`Sản phẩm (dòng ${index + 1})`);
            productInput.classList.add('border-red-500');
        } else {
            hasValidProduct = true;
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
            title: 'Xác nhận cập nhật đơn hàng?',
            text: "Bạn có chắc chắn muốn lưu các thay đổi cho đơn hàng này?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Đồng ý, cập nhật!',
            cancelButtonText: 'Hủy',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
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
                isSubmitting = true;
                document.getElementById('saleForm').submit();
            }
        });
    }
}

// Expense Management
let expenseIndex = {{ count($sale->expenses) }};

function addExpenseRow(data = null) {
    const expenseList = document.getElementById('expenseList');
    const newRow = document.createElement('div');
    newRow.className = 'expense-item bg-gray-50 p-3 rounded-lg';
    
    // Default values
    const type = data ? data.type : 'other';
    const description = data ? data.description : '';
    const amount = data ? formatMoney(data.amount) : '';
    
    newRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại chi phí</label>
                <select name="expenses[${expenseIndex}][type]" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary expense-type">
                    <option value="shipping" ${type === 'shipping' ? 'selected' : ''}>Vận chuyển</option>
                    <option value="marketing" ${type === 'marketing' ? 'selected' : ''}>Marketing</option>
                    <option value="commission" ${type === 'commission' ? 'selected' : ''}>Hoa hồng</option>
                    <option value="other" ${type === 'other' ? 'selected' : ''}>Khác</option>
                </select>
            </div>
            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                <input type="text" name="expenses[${expenseIndex}][description]" value="${description}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"
                       placeholder="Chi tiết chi phí...">
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền (<span class="currency-symbol">${document.getElementById('currencySelect') ? (document.getElementById('currencySelect').options[document.getElementById('currencySelect').selectedIndex].dataset.symbol || '₫') : '₫'}</span>)</label>
                <input type="text" name="expenses[${expenseIndex}][amount]" value="${amount}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary price-input expense-amount">
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
    
    // Initialize money input
    const newPriceInput = newRow.querySelector('.price-input');
    setupMoneyInput(newPriceInput);
}

function removeExpenseRow(btn) {
    btn.closest('.expense-item').remove();
}

async function calculateExpenses() {
    // Collect data
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
    const btn = document.querySelector('button[onclick="calculateExpenses()"]');
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
            // Confirm clear existing? No, just append or maybe optional.
            // For now, let's just append new suggestions.
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
        fetchExchangeRate(select.value).then(() => {
            const newRate = parseFloat(document.getElementById('exchangeRateInput').value) || 1;
            recalculateAllPrices(oldRate, newRate);
            currentExchangeRate = newRate;
        });
        return;
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
        }
    } catch (error) {
        console.error('Error fetching exchange rate:', error);
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
    // Initialize dual-price display on page load
    setTimeout(() => {
        const select = document.getElementById('currencySelect');
        if (select) {
            const option = select.options[select.selectedIndex];
            if (option.dataset.isBase !== '1') {
                calculateTotal();
            }
        }
    }, 100);

    // Init contractor tax visibility state synchronously
    window.hasContractorTaxActive = window.initialHasContractorTax;
    updateContractorTaxVisibility(window.hasContractorTaxActive);



    const milestonePresetSelect = document.getElementById('milestonePresetSelect');
    if (milestonePresetSelect) {
        milestonePresetSelect.addEventListener('change', function() {
            onMilestonePresetChange(this.value);
        });
    }

    const milestoneListEl = document.getElementById('milestoneList');
    if (milestoneListEl) {
        milestoneListEl.addEventListener('input', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
                switchMilestonePresetToCustom();
            }
        });
    }

    // Initialize customer debt days from current customer
    const currentCustomerOpt = document.querySelector(`#customerSelect .searchable-option[data-value="{{ $sale->customer_id }}"]`);
    if (currentCustomerOpt) {
        window.selectedCustomerDebtDays = parseInt(currentCustomerOpt.dataset.debtDays) || 0;
    }

    // Load existing milestones if editing
    const existingType = '{{ $sale->payment_term_type }}';
    const presetSelect = document.getElementById('milestonePresetSelect');
    const tableContainer = document.getElementById('milestonesTableContainer');
    const exceptionFileInput = document.getElementById('bodExceptionFileInput');
    
    if (existingType) {
        if (tableContainer) tableContainer.classList.remove('hidden');
        if (presetSelect) {
            if (existingType === 'prepaid_100') {
                const opt = Array.from(presetSelect.options).find(o => o.dataset.code && o.dataset.code.includes('TRUOC_KHI_DAT_HANG'));
                if (opt) presetSelect.value = opt.value;
            } else if (existingType === 'postpaid') {
                const opt = Array.from(presetSelect.options).find(o => o.dataset.code && o.dataset.code.includes('SAU_KHI_GIAO_HANG'));
                if (opt) presetSelect.value = opt.value;
            } else if (existingType === 'bod_exception') {
                const opt = Array.from(presetSelect.options).find(o => o.dataset.code && o.dataset.code.includes('NGOAI_LE'));
                if (opt) presetSelect.value = opt.value;
                if (exceptionFileInput) exceptionFileInput.classList.remove('hidden');
            } else {
                presetSelect.value = 'custom';
            }
        }
        
        const savedMilestones = {!! json_encode($sale->payment_terms ?? []) !!};
        if (savedMilestones && savedMilestones.length > 0) {
            const list = document.getElementById('milestoneList');
            if (list) {
                list.innerHTML = '';
                milestoneIndex = 0;
                savedMilestones.forEach(ms => addPaymentMilestone(ms));
            }
        }
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
        const customerHidden = document.querySelector('input[name="customer_id"]');
        if (customerHidden && customerHidden.value) {
            const customerOpt = document.querySelector(`#customerSelect .searchable-option[data-value="${customerHidden.value}"]`);
            if (customerOpt && customerOpt.dataset.milestones) {
                try {
                    const milestones = JSON.parse(customerOpt.dataset.milestones);
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
            <input type="text" name="payment_terms[${index}][milestone_name]" value="${label}" required
                   list="milestone-names" placeholder="VD: Cọc, Đợt 1,..." class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
        </td>
        <td class="p-2">
            <div class="flex items-center">
                <input type="number" name="payment_terms[${index}][percentage]" value="${percent}" required min="0" max="100" step="any"
                       class="milestone-percent-input w-20 border border-gray-300 rounded px-2 py-1 text-sm text-right">
                <span class="ml-1 text-sm text-gray-500">%</span>
            </div>
        </td>
        <td class="p-2">
            <div class="flex items-center">
                <input type="text" inputmode="numeric" name="payment_terms[${index}][amount]" value="${formattedAmount}" required
                       class="milestone-amount-input w-36 border border-gray-300 rounded px-2 py-1 text-sm text-right font-medium">
                <span class="ml-1 text-sm text-gray-500">₫</span>
            </div>
        </td>
        <td class="p-2">
            <select name="payment_terms[${index}][timing]" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                <option value="after_contract" ${timing === 'after_contract' ? 'selected' : ''}>Sau khi ký HĐMB</option>
                <option value="after_delivery_notice" ${timing === 'after_delivery_notice' ? 'selected' : ''}>Sau khi có thông báo giao hàng</option>
                <option value="before_export" ${timing === 'before_export' ? 'selected' : ''}>Trước khi xuất hàng</option>
                <option value="after_delivery" ${timing === 'after_delivery' ? 'selected' : ''}>Sau khi giao hàng</option>
                <option value="after_invoice" ${timing === 'after_invoice' ? 'selected' : ''}>Sau khi xuất hóa đơn</option>
            </select>
        </td>
        <td class="p-2">
            <select name="payment_terms[${index}][required_before]" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                <option value="before_order" ${requiredBefore === 'before_order' ? 'selected' : ''}>Trước khi đặt hàng</option>
                <option value="before_export" ${requiredBefore === 'before_export' ? 'selected' : ''}>Trước khi xuất hàng</option>
                <option value="after_delivery" ${requiredBefore === 'after_delivery' ? 'selected' : ''}>Sau khi giao hàng</option>
            </select>
        </td>
        <td class="p-2">
            <select name="payment_terms[${index}][is_blocking]" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                <option value="yes" ${isBlocking === 'yes' ? 'selected' : ''}>Có</option>
                <option value="no" ${isBlocking === 'no' ? 'selected' : ''}>Không</option>
            </select>
        </td>
        <td class="p-2">
            <select name="payment_terms[${index}][required_docs]" class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                <option value="unc" ${requiredDocs === 'unc' ? 'selected' : ''}>UNC</option>
                <option value="credit_note" ${requiredDocs === 'credit_note' ? 'selected' : ''}>Giấy báo có</option>
                <option value="other" ${requiredDocs === 'other' ? 'selected' : ''}>Chứng từ khác</option>
                <option value="none" ${requiredDocs === 'none' ? 'selected' : ''}>Không yêu cầu</option>
            </select>
        </td>
        <td class="p-2">
            <div class="flex items-center">
                <input type="number" name="payment_terms[${index}][due_days]" value="${dueDays}" required min="0"
                       class="w-16 border border-gray-300 rounded px-2 py-1 text-sm text-right">
                <span class="ml-1 text-xs text-gray-500">ngày</span>
            </div>
            <input type="hidden" name="payment_terms[${index}][status]" value="${ms.status || 'unpaid'}">
            <input type="hidden" name="payment_terms[${index}][confirmed_by]" value="${ms.confirmed_by || ''}">
            <input type="hidden" name="payment_terms[${index}][confirmed_at]" value="${ms.confirmed_at || ''}">
            <input type="hidden" name="payment_terms[${index}][proof_file_path]" value="${ms.proof_file_path || ''}">
            <input type="hidden" name="payment_terms[${index}][bod_approval_file_path]" value="${ms.bod_approval_file_path || ''}">
            <input type="hidden" name="payment_terms[${index}][delegated_to_id]" value="${ms.delegated_to_id || ''}">
        </td>
        <td class="p-2 text-center">
            <button type="button" onclick="removePaymentMilestone(${index})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-trash-alt"></i>
            </button>
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
</script>

@endpush
