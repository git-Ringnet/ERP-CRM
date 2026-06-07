@extends('layouts.app')

@section('title', 'Sửa báo giá - ' . $quotation->code)
@section('page-title', 'Sửa báo giá: ' . $quotation->code)

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 42px !important;
            border-color: #d1d5db !important;
            border-radius: 0.5rem !important;
            padding-top: 5px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px !important;
        }

        .suggestions-list::-webkit-scrollbar {
            width: 6px;
        }
        .suggestions-list::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }
    </style>
@endpush

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <span
                    class="px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">{{ $quotation->code }}</span>
                <span class="text-gray-500">{{ $quotation->customer->name ?? '' }}</span>
            </div>
            <a href="{{ route('quotations.show', $quotation) }}"
                class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded m-4">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('quotations.update', $quotation) }}" method="POST" id="quotationForm">
            @csrf
            @method('PUT')
            <div class="p-6 border-b border-gray-200">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mã báo giá <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $quotation->code) }}" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror">
                        @error('code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng <span
                                class="text-red-500">*</span></label>
                        <select name="customer_id" id="customer_id" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Chọn khách hàng</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" 
                                    data-debt-days="{{ $customer->debt_days }}"
                                    data-payment-terms="{{ json_encode($customer->payment_terms) }}"
                                    {{ old('customer_id', $quotation->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Người phụ trách (P.I.C) <span
                                class="text-red-500">*</span></label>
                        <select name="contact_id" id="contact_id" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('contact_id') border-red-500 @enderror">
                            <option value="">Chọn người phụ trách</option>
                        </select>
                        @error('contact_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror

                        <div id="pic_details" class="hidden mt-2 p-2 bg-slate-50 border border-slate-100 rounded-lg text-xs text-gray-600 space-y-1">
                            <p class="font-medium text-gray-700 mb-1"><span id="pic_name"></span></p>
                            <p><i class="fas fa-envelope text-gray-400 mr-1.5 w-4"></i><span id="pic_email"></span></p>
                            <p><i class="fas fa-phone text-gray-400 mr-1.5 w-4"></i><span id="pic_phone"></span></p>
                            <p><i class="fas fa-briefcase text-gray-400 mr-1.5 w-4"></i><span id="pic_position"></span></p>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề báo giá <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $quotation->title) }}" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày tạo <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', $quotation->date->format('Y-m-d')) }}" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hạn báo giá <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="valid_until"
                            value="{{ old('valid_until', $quotation->valid_until->format('Y-m-d')) }}" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('valid_until') border-red-500 @enderror">
                        @error('valid_until')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Currency Section -->
            @php
                $isForeign = $quotation->currency && !$quotation->currency->is_base;
                $decimals = $isForeign ? ($quotation->currency->decimal_places ?? 2) : 0;
            @endphp
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-900 mb-3">
                    <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Tiền tệ báo giá
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
                                    {{ old('currency_id', $quotation->currency_id ?? $baseCurrencyId) == $currency->id ? 'selected' : '' }}>
                                    {{ $currency->code }} - {{ $currency->name_vi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="exchangeRateGroup" class="{{ ($quotation->currency_id && $quotation->currency_id != $baseCurrencyId) ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tỷ giá (1 ngoại tệ = ? VND)
                            <span id="rateSource" class="text-xs text-blue-500 ml-1"></span>
                        </label>
                        <input type="number" name="exchange_rate" id="exchangeRateInput" step="0.000001" min="0"
                            value="{{ old('exchange_rate', $quotation->exchange_rate ? floatval($quotation->exchange_rate) : 1) }}"
                            onchange="calculateTotal()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-xs text-gray-500 mt-1" id="rateHint">Tỷ giá từ lúc tạo báo giá</p>
                    </div>
                    <div id="dualPricePlaceholder" class="hidden">
                        <!-- Removed dualPriceGroup as per user request -->
                    </div>
                </div>
            </div>

            <!-- Products -->
            @php
                $customColumns = old('custom_columns', $quotation->custom_columns ?? []);
                if (!is_array($customColumns)) {
                    $customColumns = [];
                }
                
                $oldProducts = old('products');
                if ($oldProducts) {
                    $renderedItems = [];
                    foreach ($oldProducts as $idx => $oldItem) {
                        $renderedItems[] = (object)[
                            'id' => null,
                            'product_id' => $oldItem['product_id'] ?? null,
                            'product_name' => $oldItem['product_name'] ?? '',
                            'description' => $oldItem['description'] ?? '',
                            'quantity' => $oldItem['quantity'] ?? 1,
                            'price' => $oldItem['price'] ?? 0,
                            'vat' => $oldItem['vat'] ?? 8,
                            'total' => ($oldItem['quantity'] ?? 1) * ($oldItem['price'] ?? 0),
                            'custom_fields' => $oldItem['custom_fields'] ?? [],
                        ];
                    }
                } else {
                    $renderedItems = $quotation->items;
                }
            @endphp

            <div id="customColumnsInputs">
                @foreach($customColumns as $colName)
                    <input type="hidden" name="custom_columns[]" value="{{ $colName }}" id="hidden-col-{{ $colName }}">
                @endforeach
            </div>

            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Chi tiết sản phẩm</h3>
            </div>

            <div class="p-4">
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed" id="quotationTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider min-w-[280px]">
                                    Sản phẩm / Dịch vụ / Mô tả
                                </th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-[80px]">
                                    SL
                                </th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-[150px]">
                                    Đơn giá (<span class="currency-symbol">₫</span>)
                                </th>
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-[90px]">
                                    VAT (%)
                                </th>
                                @foreach($customColumns as $colName)
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider custom-col-header w-[150px]" data-column-name="{{ $colName }}">
                                        <span class="flex items-center justify-between gap-1">
                                            {{ $colName }}
                                            <button type="button" onclick="removeCustomColumn('{{ $colName }}')" class="text-red-500 hover:text-red-700 text-xs focus:outline-none">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </span>
                                    </th>
                                @endforeach
                                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-[160px] row-total-header">
                                    Thành tiền (<span class="currency-symbol">₫</span>)
                                </th>
                                <th scope="col" class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-[60px]">
                                    <button type="button" onclick="addCustomColumnPrompt()" class="text-primary hover:text-primary-dark" title="Thêm cột tùy chỉnh">
                                        <i class="fas fa-plus-circle text-lg"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="tableBody">
                        @foreach($renderedItems as $index => $item)
                            @php
                                $productIdVal = $item->product_id;
                                $isManual = empty($productIdVal);
                                if ($productIdVal && is_numeric($productIdVal)) {
                                    $productIdVal = 'p-' . $productIdVal;
                                }
                            @endphp
                            <tr class="product-item" data-index="{{ $index }}">
                                <td class="px-3 py-2">
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-[11px] font-medium text-gray-400 product-label">{{ $isManual ? 'Tên dịch vụ' : 'Sản phẩm' }}</label>
                                        <button type="button" class="toggle-mode-btn inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium border transition-colors duration-150 focus:outline-none {{ $isManual ? 'bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 border-blue-200' }}" onclick="toggleRowMode(this)">
                                            <i class="fas {{ $isManual ? 'fa-list' : 'fa-edit' }} mr-1 text-[9px]"></i> {{ $isManual ? 'Chọn từ kho' : 'Nhập ngoài' }}
                                        </button>
                                    </div>
                                    <div class="select2-wrapper {{ $isManual ? 'hidden' : '' }}">
                                        <select name="products[{{ $index }}][product_id]" class="w-full product-select" data-placeholder="Tìm mã hoặc tên sản phẩm...">
                                            @if($productIdVal && !$isManual)
                                                <option value="{{ $productIdVal }}" selected>{{ $item->product_code ?? '' }}</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="manual-wrapper {{ $isManual ? '' : 'hidden' }}">
                                        <input type="text" name="products[{{ $index }}][product_name]" class="manual-name-input w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" 
                                               placeholder="Nhập tên dịch vụ/sản phẩm ngoài..." 
                                               value="{{ $isManual ? $item->product_name : '' }}">
                                    </div>
                                    <div class="mt-2">
                                        <label class="block text-[11px] font-medium text-gray-400 mb-0.5">Mô tả sản phẩm</label>
                                        <input type="text" name="products[{{ $index }}][description]" value="{{ $item->description }}" 
                                               class="description-input w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" 
                                               placeholder="Mô tả chi tiết sản phẩm cho dòng này...">
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <input type="number" name="products[{{ $index }}][quantity]"
                                        value="{{ $item->quantity }}" min="1" required
                                        onchange="calculateRowTotal({{ $index }})"
                                        class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <input type="text" name="products[{{ $index }}][price]"
                                        value="{{ is_numeric($item->price) ? number_format($item->price, $decimals, '.', ',') : $item->price }}" required
                                        onchange="calculateRowTotal({{ $index }})"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary price-input">
                                    <small class="block text-[10px] text-gray-400 mt-1 base-price-reference leading-none">
                                        @if(isset($item->product) && !$isManual)
                                            Giá gốc kho: {{ number_format($item->product->calculated_selling_price ?? $item->product->price, 0, '.', ',') }} ₫
                                        @endif
                                    </small>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <input type="number" name="products[{{ $index }}][vat]"
                                        value="{{ (float)$item->vat }}" min="0" step="0.01"
                                        onchange="calculateRowTotal({{ $index }})"
                                        class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary vat-input">
                                </td>
                                @foreach($customColumns as $colName)
                                    @php
                                        $val = '';
                                        if (isset($item->custom_fields) && is_array($item->custom_fields)) {
                                            $val = $item->custom_fields[$colName] ?? '';
                                        } elseif (isset($item->custom_fields) && is_object($item->custom_fields)) {
                                            $val = $item->custom_fields->$colName ?? '';
                                        }
                                    @endphp
                                    <td class="px-3 py-2 align-top custom-col-cell" data-column-name="{{ $colName }}">
                                        <input type="text" name="products[{{ $index }}][custom_fields][{{ $colName }}]"
                                               value="{{ $val }}"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                               placeholder="Nhập {{ $colName }}...">
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 align-top row-total-cell">
                                    <input type="text" readonly
                                        class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-1.5 text-sm row-total"
                                        value="{{ is_numeric($item->total) ? number_format($item->total, $decimals, '.', ',') : $item->total }}">
                                </td>
                                <td class="px-3 py-2 text-center align-top">
                                    <button type="button" onclick="removeProductRow(this)"
                                        class="px-3 py-1.5 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors text-sm"
                                        title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" onclick="addProductRow()"
                    class="mt-3 inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm hàng hóa/dịch vụ
                </button>
            </div>

            <!-- Totals -->
            <div class="p-6 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-1">
                                <label class="block text-sm font-medium text-gray-700">Điều khoản thanh toán</label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <select id="paymentMilestoneRatioSelect" class="border border-gray-300 rounded px-2 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-primary">
                                        <option value="customer_default">Mặc định khách hàng</option>
                                        <option value="30-70">30% - 70%</option>
                                        <option value="50-50">50% - 50%</option>
                                        <option value="100-prepaid">100% prepaid</option>
                                        <option value="custom" selected>Tùy chỉnh tỷ lệ</option>
                                    </select>
                                    <select id="paymentTermSelect" class="border border-gray-300 rounded px-2 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-primary">
                                        <option value="NET 30">NET 30</option>
                                        <option value="NET 45">NET 45</option>
                                        <option value="prepaid">Thanh toán trước giao hàng</option>
                                        <option value="custom" selected>Tùy chỉnh hạn</option>
                                    </select>
                                </div>
                            </div>
                            <textarea name="payment_terms" id="payment_terms" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('payment_terms', $quotation->payment_terms) }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian giao hàng</label>
                            <input type="text" name="delivery_time"
                                value="{{ old('delivery_time', $quotation->delivery_time) }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        {{-- Ghi chú - Sortable numbered list --}}
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-sticky-note text-yellow-500 mr-1"></i> Ghi chú
                                </label>
                                <button type="button" onclick="addNoteItem('noteList')"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                    <i class="fas fa-plus mr-1"></i> Thêm
                                </button>
                            </div>
                            <div id="noteList" class="space-y-2">
                                @php $editNotes = old('note', $quotation->note_array); @endphp
                                @foreach($editNotes as $i => $noteItem)
                                    <div class="sortable-item flex items-start gap-2 group">
                                        <span class="drag-handle cursor-grab text-gray-300 hover:text-gray-500 mt-2.5 flex-shrink-0" title="Kéo để sắp xếp"><i class="fas fa-grip-vertical"></i></span>
                                        <span class="note-number text-sm font-semibold text-gray-500 mt-2 flex-shrink-0 w-6">({{ $i + 1 }})</span>
                                        <input type="text" name="note[]" value="{{ $noteItem }}"
                                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Nhập ghi chú...">
                                        <button type="button" onclick="removeNoteItem(this)"
                                            class="text-red-400 hover:text-red-600 mt-2 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-times"></i></button>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Cảnh báo / Lưu ý - Sortable numbered list --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i> Cảnh báo / Lưu ý
                                </label>
                                <button type="button" onclick="addNoteItem('disclaimerList')"
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-amber-600 bg-amber-50 rounded-lg hover:bg-amber-100 transition-colors">
                                    <i class="fas fa-plus mr-1"></i> Thêm
                                </button>
                            </div>
                            <div id="disclaimerList" class="space-y-2">
                                @php $editDisclaimer = old('disclaimer', $quotation->disclaimer_array); @endphp
                                @if(empty($editDisclaimer))
                                    @php $editDisclaimer = \App\Models\Quotation::defaultDisclaimer(); @endphp
                                @endif
                                @foreach($editDisclaimer as $i => $discItem)
                                    <div class="sortable-item flex items-start gap-2 group">
                                        <span class="drag-handle cursor-grab text-gray-300 hover:text-gray-500 mt-2.5 flex-shrink-0" title="Kéo để sắp xếp"><i class="fas fa-grip-vertical"></i></span>
                                        <span class="note-number text-sm font-semibold text-gray-500 mt-2 flex-shrink-0 w-6">({{ $i + 1 }})</span>
                                        <input type="text" name="disclaimer[]" value="{{ $discItem }}"
                                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
                                            placeholder="Nhập cảnh báo...">
                                        <button type="button" onclick="removeNoteItem(this)"
                                            class="text-red-400 hover:text-red-600 mt-2 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-times"></i></button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between mb-3">
                            <span class="text-gray-600">Tổng tiền hàng:</span>
                            <span id="subtotal" class="font-medium text-lg">0 ₫</span>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-gray-600">Chiết khấu (%):</span>
                            <div class="flex items-center gap-2">
                                <span id="discountAmount" class="text-gray-500 text-sm">0 đ</span>
                                <input type="number" name="discount" id="discount"
                                    value="{{ old('discount', (float)$quotation->discount) }}" min="0" max="100"
                                    onchange="calculateTotal()"
                                    class="w-20 border border-gray-300 rounded px-2 py-1 text-right focus:ring-1 focus:ring-primary">
                            </div>
                        </div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-gray-600">Thuế VAT:</span>
                            <div class="flex items-center gap-2">
                                <span id="vatAmount" class="font-medium">0 đ</span>
                                <input type="hidden" name="vat" value="0">
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between items-center pt-3 border-t">
                            <span class="text-lg font-semibold">Tổng cộng:</span>
                            <div class="text-right">
                                <span id="total" class="text-2xl font-bold text-primary">0 ₫</span>
                                <small id="totalVndReference" class="block text-sm text-gray-500 mt-1 text-right"></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-4 p-4">
                <a href="{{ route('quotations.show', $quotation) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                    <i class="fas fa-times mr-2"></i> Hủy
                </a>
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 (only for customer) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let rowIndex = {{ old('products') ? count(old('products')) : count($quotation->items) }};
        window.customColumns = @json($customColumns);

        $(document).ready(function () {
            // Initialize Select2 for Customer
            $('select[name="customer_id"]').select2({
                placeholder: "Chọn khách hàng",
                allowClear: true,
                width: '100%'
            });

            // Initialize product selects for existing rows
            $('.product-select').each(function() {
                initProductSelect($(this));
            });

            // Format existing prices on load
            $('.price-input').each(function () {
                formatInput(this);
            });

            // Form submit handler to unformat prices
            $('#quotationForm').on('submit', function () {
                $('.price-input').each(function () {
                    const unformatted = $(this).val().replace(/,/g, '');
                    $(this).val(unformatted);
                });
            });

            calculateTotal();

            // Auto-format price input on typing
            $('#tableBody').on('input', '.price-input', function () {
                formatInput(this);
            });

            $('#tableBody').on('input', '.price-input, .quantity-input, .vat-input', function () {
                const name = $(this).closest('.product-item').find('.quantity-input').attr('name');
                const match = name && name.match(/products\[(\d+)\]/);
                if (match) calculateRowTotal(match[1]);
            });
        });

        function initProductSelect(element) {
            const row = element.closest('.product-item');
            
            element.select2({
                placeholder: "Tìm hoặc nhập tên sản phẩm...",
                allowClear: true,
                tags: true,
                width: '100%',
                ajax: {
                    url: "{{ route('quotations.search-catalog') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { search: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item.text,
                                price: item.price,
                                unit: item.unit,
                                name: item.name,
                                description: item.description
                            }))
                        };
                    },
                    cache: true,
                    error: function(xhr, status, error) {
                        console.error('Select2 AJAX error:', error);
                    }
                },
                createTag: function (params) {
                    const term = $.trim(params.term);
                    if (term === '') return null;
                    return { id: term, text: term, isNew: true };
                }
            }).on('select2:select', function (e) {
                const data = e.params.data;
                const descInput = row.find('.description-input');
                
                // If it's a tag or doesn't have our prefixes (p- or c-)
                const isManual = data.isNew || (typeof data.id === 'string' && !data.id.startsWith('p-') && !data.id.startsWith('c-'));
                
                const name = data.name || data.text;
                descInput.val(data.description || name);

                if (isManual) {
                    row.find('.base-price-reference').text('');
                } else {
                    if (data.price) {
                        const basePriceVnd = parseFloat(data.price);
                        row.find('.base-price-reference').text(`Giá gốc: ${formatMoney(basePriceVnd)} ₫`);
                        
                        const rate = parseFloat($('#exchangeRateInput').val()) || 1;
                        row.find('.price-input').val(formatMoney(basePriceVnd / rate));
                    }
                }
                const qtyInput = row.find('.quantity-input');
                const idxRef = qtyInput.attr('name').match(/products\[(\d+)\]/)[1];
                calculateRowTotal(idxRef);
            }).on('select2:clear', function(e) {
                row.find('.description-input').val('');
                row.find('.base-price-reference').text('');
                const qtyInput = row.find('.quantity-input');
                const idxRef = qtyInput.attr('name').match(/products\[(\d+)\]/)[1];
                calculateRowTotal(idxRef);
            });
        }

        function formatMoney(value) {
            if (value === undefined || value === null || value === '') return '';
            const select = document.getElementById('currencySelect');
            const isVnd = select ? (select.options[select.selectedIndex]?.dataset.isBase === '1') : true;
            const decimals = isVnd ? 0 : 2;
            const num = parseFloat(value.toString().replace(/[^0-9.]/g, ''));
            if (isNaN(num)) return '';
            return num.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        function unformatMoney(value) {
            if (value === undefined || value === null || value === '') return 0;
            return parseFloat(value.toString().replace(/[^0-9.]/g, '')) || 0;
        }

        function formatInput(element) {
            let val = element.value;
            if (val === '') return;

            let cursorPosition = element.selectionStart;
            let originalLength = val.length;

            const select = document.getElementById('currencySelect');
            const isVnd = select ? (select.options[select.selectedIndex]?.dataset.isBase === '1') : true;

            let cleanVal;
            if (isVnd) {
                cleanVal = val.replace(/[^0-9]/g, '');
                cleanVal = cleanVal.substring(0, 15);
            } else {
                cleanVal = val.replace(/[^0-9.]/g, '');
                const dotIndex = cleanVal.indexOf('.');
                if (dotIndex !== -1) {
                    cleanVal = cleanVal.substring(0, dotIndex + 1) + cleanVal.substring(dotIndex + 1).replace(/\./g, '');
                }
                const parts = cleanVal.split('.');
                parts[0] = parts[0].substring(0, 15);
                if (parts.length > 1) {
                    parts[1] = parts[1].substring(0, 2);
                }
                cleanVal = parts.join('.');
            }

            if (cleanVal === '') {
                element.value = '';
                return;
            }

            let formattedVal = '';
            if (isVnd) {
                formattedVal = parseInt(cleanVal, 10).toLocaleString('en-US');
            } else {
                const parts = cleanVal.split('.');
                const integerPart = parseInt(parts[0], 10);
                if (isNaN(integerPart)) {
                    formattedVal = '';
                } else {
                    formattedVal = integerPart.toLocaleString('en-US');
                }
                if (parts.length > 1) {
                    formattedVal += '.' + parts[1];
                }
            }

            element.value = formattedVal;

            let newLength = formattedVal.length;
            cursorPosition = cursorPosition + (newLength - originalLength);
            element.setSelectionRange(cursorPosition, cursorPosition);
        }

        function toggleRowMode(btn) {
            const row = $(btn).closest('.product-item');
            const selectWrapper = row.find('.select2-wrapper');
            const manualWrapper = row.find('.manual-wrapper');
            const toggleBtn = row.find('.toggle-mode-btn');
            const isManualNow = selectWrapper.hasClass('hidden');
            const descInput = row.find('.description-input');
            const manualInput = row.find('.manual-name-input');
            const productSelect = row.find('.product-select');

            const symbol = $('#currencySelect option:selected').data('symbol') || '₫';
            if (isManualNow) {
                // Switch to Select2 mode
                selectWrapper.removeClass('hidden');
                manualWrapper.addClass('hidden');
                toggleBtn.html('<i class="fas fa-edit mr-1 text-[10px]"></i> Nhập dịch vụ ngoài');
                toggleBtn.removeClass('bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200')
                         .addClass('bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200');
                
                row.find('.product-label').html('Sản phẩm');
                row.find('.price-label').html('Đơn giá (<span class="currency-symbol">' + symbol + '</span>)');

                // Clear manual input
                manualInput.val('');
                descInput.val('');
            } else {
                // Switch to Manual mode
                selectWrapper.addClass('hidden');
                manualWrapper.removeClass('hidden');
                toggleBtn.html('<i class="fas fa-list mr-1 text-[10px]"></i> Chọn từ kho');
                toggleBtn.removeClass('bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200')
                         .addClass('bg-slate-50 text-slate-700 hover:bg-slate-100 border-slate-200');
                
                row.find('.product-label').html('Tên dịch vụ');
                row.find('.price-label').html('Giá bán (<span class="currency-symbol">' + symbol + '</span>)');

                // Clear select2 and set to empty
                productSelect.val(null).trigger('change');
                descInput.val('');
                row.find('.base-price-reference').text('');
            }
        }

        function addCustomColumnPrompt() {
            Swal.fire({
                title: 'Thêm cột tùy chỉnh',
                input: 'text',
                inputLabel: 'Tên cột (VD: Màu sắc, Xuất xứ, Thương hiệu...)',
                inputPlaceholder: 'Nhập tên cột...',
                showCancelButton: true,
                confirmButtonText: 'Thêm',
                cancelButtonText: 'Hủy',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Vui lòng nhập tên cột!';
                    }
                    const trimmed = value.trim();
                    if (trimmed.length > 50) {
                        return 'Tên cột không quá 50 ký tự!';
                    }
                    if (window.customColumns.includes(trimmed)) {
                        return 'Cột này đã tồn tại!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    addCustomColumn(result.value.trim());
                }
            });
        }

        function addCustomColumn(colName) {
            window.customColumns.push(colName);
            
            // Add hidden input to form
            $('#customColumnsInputs').append(`<input type="hidden" name="custom_columns[]" value="${colName}" id="hidden-col-${colName}">`);
            
            // Add TH to header before the Row Total header
            const th = `
                <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider custom-col-header w-[150px]" data-column-name="${colName}">
                    <span class="flex items-center justify-between gap-1">
                        ${colName}
                        <button type="button" onclick="removeCustomColumn('${colName}')" class="text-red-500 hover:text-red-700 text-xs focus:outline-none">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </span>
                </th>
            `;
            $(th).insertBefore('.row-total-header');

            // Add TD to each row before the Row Total cell
            $('.product-item').each(function() {
                const row = $(this);
                const idx = row.attr('data-index');
                const td = `
                    <td class="px-3 py-2 align-top custom-col-cell" data-column-name="${colName}">
                        <input type="text" name="products[${idx}][custom_fields][${colName}]" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                               placeholder="Nhập ${colName}...">
                    </td>
                `;
                $(td).insertBefore(row.find('.row-total-cell'));
            });
        }

        function removeCustomColumn(colName) {
            Swal.fire({
                title: 'Xóa cột tùy chỉnh?',
                text: `Bạn có chắc chắn muốn xóa cột "${colName}" cùng dữ liệu của nó?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Remove from array
                    window.customColumns = window.customColumns.filter(c => c !== colName);
                    
                    // Remove hidden input
                    $(`#hidden-col-${colName}`).remove();
                    
                    // Remove TH
                    $(`.custom-col-header[data-column-name="${colName}"]`).remove();
                    
                    // Remove TD from all rows
                    $(`.custom-col-cell[data-column-name="${colName}"]`).remove();
                }
            });
        }

        function addProductRow() {
            const tableBody = document.getElementById('tableBody');
            const newRow = document.createElement('tr');
            newRow.className = 'product-item';
            newRow.setAttribute('data-index', rowIndex);
            
            // Build custom columns TDs
            let customColsHtml = '';
            window.customColumns.forEach(colName => {
                customColsHtml += `
                    <td class="px-3 py-2 align-top custom-col-cell" data-column-name="${colName}">
                        <input type="text" name="products[${rowIndex}][custom_fields][${colName}]" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                               placeholder="Nhập ${colName}...">
                    </td>`;
            });

            newRow.innerHTML = `
                <td class="px-3 py-2">
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-[11px] font-medium text-gray-400 product-label">Sản phẩm</label>
                        <button type="button" class="toggle-mode-btn inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium border bg-blue-50 text-blue-700 hover:bg-blue-100 border-blue-200 transition-colors duration-150 focus:outline-none" onclick="toggleRowMode(this)">
                            <i class="fas fa-edit mr-1 text-[9px]"></i> Nhập ngoài
                        </button>
                    </div>
                    <div class="select2-wrapper">
                        <select name="products[${rowIndex}][product_id]" class="w-full product-select" data-placeholder="Tìm mã hoặc tên sản phẩm...">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="manual-wrapper hidden">
                        <input type="text" name="products[${rowIndex}][product_name]" class="manual-name-input w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" 
                               placeholder="Nhập tên dịch vụ/sản phẩm ngoài...">
                    </div>
                    <div class="mt-2">
                        <label class="block text-[11px] font-medium text-gray-400 mb-0.5">Mô tả sản phẩm</label>
                        <input type="text" name="products[${rowIndex}][description]" class="description-input w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" 
                               placeholder="Mô tả chi tiết sản phẩm cho dòng này...">
                    </div>
                </td>
                <td class="px-3 py-2 align-top">
                    <input type="number" name="products[${rowIndex}][quantity]" value="1" min="1" required
                           onchange="calculateRowTotal(${rowIndex})"
                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary quantity-input">
                </td>
                <td class="px-3 py-2 align-top">
                    <input type="text" name="products[${rowIndex}][price]" value="0" required
                           onchange="calculateRowTotal(${rowIndex})"
                           class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary price-input">
                    <small class="block text-[10px] text-gray-400 mt-1 base-price-reference leading-none"></small>
                </td>
                <td class="px-3 py-2 align-top">
                    <input type="number" name="products[${rowIndex}][vat]" value="8" min="0" step="0.01"
                           onchange="calculateRowTotal(${rowIndex})"
                           class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary vat-input">
                </td>
                ${customColsHtml}
                <td class="px-3 py-2 align-top row-total-cell">
                    <input type="text" readonly
                           class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-1.5 text-sm row-total" value="0">
                </td>
                <td class="px-3 py-2 text-center align-top">
                    <button type="button" onclick="removeProductRow(this)"
                            class="px-3 py-1.5 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors text-sm"
                            title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tableBody.appendChild(newRow);
            initProductSelect($(newRow).find('.product-select'));
            rowIndex++;
        }

        function removeProductRow(btn) {
            const items = document.querySelectorAll('.product-item');
            if (items.length > 1) {
                btn.closest('.product-item').remove();
                calculateTotal();
            }
        }

        function calculateRowTotal(index) {
            const row = $(`.quantity-input[name="products[${index}][quantity]"]`).closest('.product-item');
            const qty = parseFloat(row.find('.quantity-input').val()) || 0;
            const price = unformatMoney(row.find('.price-input').val());
            const total = qty * price;
            row.find('.row-total').val(formatMoney(total));
            calculateTotal();
        }

        function calculateTotal() {
            const option = $('#currencySelect option:selected');
            const symbol = option.data('symbol') || '';
            let subtotal = 0;
            let totalVatAmount = 0;
            const discount = parseFloat($('#discount').val()) || 0;

            $('.product-item').each(function() {
                const row = $(this);
                const qty = parseFloat(row.find('.quantity-input').val()) || 0;
                const price = unformatMoney(row.find('.price-input').val());
                const rowSubtotal = qty * price;
                subtotal += rowSubtotal;

                const vatPercent = parseFloat(row.find('.vat-input').val()) || 0;
                const rowDiscount = rowSubtotal * discount / 100;
                const rowBaseForVat = rowSubtotal - rowDiscount;
                const rowVatAmount = rowBaseForVat * vatPercent / 100;
                totalVatAmount += rowVatAmount;
            });

            const discountAmount = subtotal * discount / 100;
            const total = subtotal - discountAmount + totalVatAmount;

            $('#subtotal').text(formatMoney(subtotal) + ' ' + symbol);
            $('#discountAmount').text(formatMoney(discountAmount) + ' ' + symbol);
            $('#vatAmount').text(formatMoney(totalVatAmount) + ' ' + symbol);
            $('#total').text(formatMoney(total) + ' ' + symbol);

            const vndRef = $('#totalVndReference');
            if (option.data('is-base') === 1) { vndRef.text(''); } else {
                const rate = parseFloat($('#exchangeRateInput').val()) || 1;
                vndRef.text(`= ${formatMoney(total * rate)} ₫`);
            }
            $('.currency-symbol').text(symbol);
        }

        let currentExchangeRate = parseFloat($('#exchangeRateInput').val()) || 1;
        function onCurrencyChange() {
            const option = $('#currencySelect option:selected');
            if (option.data('is-base') === 1) {
                $('#exchangeRateGroup').addClass('hidden');
                $('#exchangeRateInput').val(1);
                updatePricesAfterRateChange(1);
            } else {
                $('#exchangeRateGroup').removeClass('hidden');
                fetchExchangeRate(option.val());
            }
        }

        async function fetchExchangeRate(currencyId) {
            const date = $('input[name="date"]').val();
            try {
                const response = await fetch(`{{ route('api.exchange-rate') }}?currency_id=${currencyId}&date=${date}`);
                const data = await response.json();
                if (data.rate) {
                    $('#exchangeRateInput').val(data.rate);
                    $('#rateSource').text(data.source === 'auto' ? '(Vietcombank)' : '(Thủ công)');
                    $('#rateHint').text(`Ngày: ${data.effective_date || date}`);
                    updatePricesAfterRateChange(data.rate);
                }
            } catch (e) { console.error(e); }
        }

        function updatePricesAfterRateChange(newRate) {
            const oldRate = currentExchangeRate;
            if (oldRate === newRate) return;
            $('.product-item').each(function() {
                const priceInput = $(this).find('.price-input');
                const oldPrice = unformatMoney(priceInput.val());
                const baseVnd = oldPrice * oldRate;
                const newPrice = baseVnd / newRate;
                priceInput.val(formatMoney(newPrice));
                
                const qtyStr = $(this).find('.quantity-input').val();
                const qty = parseFloat(qtyStr) || 0;
                $(this).find('.row-total').val(formatMoney(qty * newPrice));
            });
            currentExchangeRate = newRate;
            calculateTotal();
        }

        $('input[name="date"]').on('change', function() {
            const option = $('#currencySelect option:selected');
            if (option.data('is-base') !== 1) fetchExchangeRate(option.val());
        });
        $('#exchangeRateInput').on('change', function() { updatePricesAfterRateChange(parseFloat($(this).val()) || 1); });

        // PIC Selection logic
        const customerSelect = $('#customer_id');
        const contactSelect = $('#contact_id');
        const picDetails = $('#pic_details');
        const picName = $('#pic_name');
        const picEmail = $('#pic_email');
        const picPhone = $('#pic_phone');
        const picPosition = $('#pic_position');
        
        let contactsData = [];

        async function loadContacts(customerId, selectedContactId = null) {
            if (!customerId) {
                contactSelect.html('<option value="">Chọn người phụ trách</option>');
                picDetails.addClass('hidden');
                contactsData = [];
                return;
            }
            
            try {
                const response = await fetch(`/ajax/customers/${customerId}/contacts`);
                contactsData = await response.json();
                
                let options = '<option value="">Chọn người phụ trách</option>';
                contactsData.forEach(contact => {
                    const isSelected = selectedContactId == contact.id || (!selectedContactId && contact.is_primary) ? 'selected' : '';
                    options += `<option value="${contact.id}" ${isSelected}>${contact.name} ${contact.is_primary ? '(Mặc định)' : ''}</option>`;
                });
                contactSelect.html(options);
                
                // Trigger change to update PIC details
                contactSelect.trigger('change');
            } catch (e) {
                console.error('Error fetching contacts:', e);
            }
        }

        customerSelect.on('change', function() {
            loadContacts($(this).val());
        });

        contactSelect.on('change', function() {
            const val = $(this).val();
            const contact = contactsData.find(c => c.id == val);
            if (contact) {
                picName.text(contact.name);
                picEmail.text(contact.email || 'N/A');
                picPhone.text(contact.phone || 'N/A');
                picPosition.text(contact.position || 'N/A');
                picDetails.removeClass('hidden');
            } else {
                picDetails.addClass('hidden');
            }
        });

        // On document load, pre-populate if customer is preselected
        $(document).ready(function() {
            const initialCustomerId = customerSelect.val();
            const oldContactId = '{{ old('contact_id', $quotation->contact_id) }}';
            if (initialCustomerId) {
                loadContacts(initialCustomerId, oldContactId);
            }
        });

        // Payment Terms Generator Logic
        function updatePaymentTermsText() {
            const milestoneRatio = $('#paymentMilestoneRatioSelect').val();
            const term = $('#paymentTermSelect').val();

            if (milestoneRatio === 'custom' && term === 'custom') {
                return; // Don't overwrite if both are custom
            }

            let milestoneText = '';
            if (milestoneRatio === 'customer_default') {
                const selectedOpt = $('#customer_id option:selected');
                let rawTerms = selectedOpt.attr('data-payment-terms');
                if (rawTerms) {
                    try {
                        const terms = JSON.parse(rawTerms);
                        if (Array.isArray(terms) && terms.length > 0) {
                            milestoneText = terms.map((t, idx) => {
                                return `Đợt ${idx + 1}: ${t.label || 'Thanh toán'} ${t.percent}% trong vòng ${t.days || 0} ngày`;
                            }).join('. ');
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
                if (!milestoneText) {
                    milestoneText = 'Theo điều khoản thanh toán mặc định của khách hàng';
                }
            } else if (milestoneRatio === '30-70') {
                milestoneText = 'Đợt 1: Cọc 30% trong vòng 5 ngày. Đợt 2: Thanh toán 70% còn lại trong vòng 30 ngày';
            } else if (milestoneRatio === '50-50') {
                milestoneText = 'Đợt 1: Cọc 50% trong vòng 5 ngày. Đợt 2: Thanh toán 50% còn lại trong vòng 30 ngày';
            } else if (milestoneRatio === '100-prepaid') {
                milestoneText = 'Thanh toán trả trước 100%';
            }

            let termText = '';
            if (term === 'NET 30') {
                termText = 'Hạn thanh toán: NET 30 (trong vòng 30 ngày)';
            } else if (term === 'NET 45') {
                termText = 'Hạn thanh toán: NET 45 (trong vòng 45 ngày)';
            } else if (term === 'prepaid') {
                termText = 'Hạn thanh toán: Thanh toán trước khi nhận hàng';
            } else if (term === 'custom') {
                termText = 'Hạn thanh toán: Tùy chỉnh theo thỏa thuận';
            }

            let combined = milestoneText;
            if (termText) {
                combined += (combined ? '. ' : '') + termText;
            }

            $('textarea[name="payment_terms"]').val(combined);
        }

        $('#paymentMilestoneRatioSelect, #paymentTermSelect').on('change', updatePaymentTermsText);
        $('#customer_id').on('change', function() {
            if ($('#paymentMilestoneRatioSelect').val() === 'customer_default') {
                updatePaymentTermsText();
            }
        });

        $('textarea[name="payment_terms"]').on('input', function() {
            $('#paymentMilestoneRatioSelect').val('custom');
            $('#paymentTermSelect').val('custom');
        });
    </script>

    {{-- SortableJS for drag-and-drop --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // Initialize Sortable on note and disclaimer lists
        document.addEventListener('DOMContentLoaded', function() {
            ['noteList', 'disclaimerList'].forEach(function(listId) {
                const el = document.getElementById(listId);
                if (el) {
                    new Sortable(el, {
                        handle: '.drag-handle',
                        animation: 200,
                        ghostClass: 'bg-blue-50',
                        onEnd: function() { renumberList(listId); }
                    });
                }
            });
        });

        function addNoteItem(listId) {
            const list = document.getElementById(listId);
            const count = list.querySelectorAll('.sortable-item').length;
            const fieldName = listId === 'noteList' ? 'note[]' : 'disclaimer[]';
            const ringColor = listId === 'noteList' ? 'focus:ring-blue-500' : 'focus:ring-amber-500';

            const div = document.createElement('div');
            div.className = 'sortable-item flex items-start gap-2 group';
            div.innerHTML = `
                <span class="drag-handle cursor-grab text-gray-300 hover:text-gray-500 mt-2.5 flex-shrink-0" title="Kéo để sắp xếp"><i class="fas fa-grip-vertical"></i></span>
                <span class="note-number text-sm font-semibold text-gray-500 mt-2 flex-shrink-0 w-6">(${count + 1})</span>
                <input type="text" name="${fieldName}" value=""
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 ${ringColor}"
                    placeholder="${listId === 'noteList' ? 'Nhập ghi chú...' : 'Nhập cảnh báo...'}">
                <button type="button" onclick="removeNoteItem(this)"
                    class="text-red-400 hover:text-red-600 mt-2 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-times"></i></button>
            `;
            list.appendChild(div);

            // Focus the new input
            div.querySelector('input').focus();
        }

        function removeNoteItem(btn) {
            const item = btn.closest('.sortable-item');
            const list = item.parentElement;
            item.remove();
            renumberList(list.id);
        }

        function renumberList(listId) {
            const list = document.getElementById(listId);
            list.querySelectorAll('.sortable-item').forEach(function(item, index) {
                item.querySelector('.note-number').textContent = '(' + (index + 1) + ')';
            });
        }
    </script>
@endpush