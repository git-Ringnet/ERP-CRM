@extends('layouts.app')

@section('title', isset($orderRequest) ? 'Chỉnh sửa yêu cầu đặt hàng' : 'Tạo yêu cầu đặt hàng')
@section('page-title', (isset($orderRequest) ? 'Chỉnh sửa yêu cầu: ' . $orderRequest->code : 'Yêu cầu đặt hàng cho đơn: ' . $sale->code))

@section('content')
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    @if ($errors->any())
        <div class="m-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Không thể gửi yêu cầu đặt hàng. Vui lòng kiểm tra:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    <div class="p-4 sm:p-6 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center text-white mr-4">
                <i class="fas {{ isset($orderRequest) ? 'fa-edit' : 'fa-cart-plus' }} text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">
                    {{ isset($orderRequest) ? 'Chỉnh sửa yêu cầu đặt hàng' : 'Khởi tạo yêu cầu đặt hàng' }}
                    @if(isset($orderRequest))
                        <span class="text-sm font-normal text-gray-500 ml-2">#{{ $orderRequest->code }}</span>
                        <span class="ml-2 px-2 py-0.5 rounded text-xs font-bold {{ $orderRequest->status === 'draft' ? 'bg-gray-200 text-gray-700' : 'bg-orange-200 text-orange-700' }}">
                            {{ $orderRequest->status_label }}
                        </span>
                    @endif
                </h3>
                <p class="text-sm text-emerald-700">{{ isset($orderRequest) ? 'Đơn hàng: ' . $sale->code : 'Theo mẫu chuẩn của hệ thống' }}</p>
            </div>
        </div>
        <a href="{{ route('sales.show', $sale->id) }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times text-xl"></i>
        </a>
    </div>

    <form action="{{ isset($orderRequest) ? route('sales.order-request.update', [$sale->id, $orderRequest->id]) : route('sales.order-request.store', $sale->id) }}" method="POST" enctype="multipart/form-data" id="orderRequestForm">
        @csrf
        @if(isset($orderRequest))
            @method('PUT')
        @endif
        <input type="hidden" name="action_type" id="action_type" value="submit">
        <div class="p-4 sm:p-6 space-y-6">
            {{-- Rejection Note Alert Banner if PR was returned --}}
            @if(isset($orderRequest) && $orderRequest->rejection_note)
            <div class="bg-amber-50 border-2 border-amber-400 rounded-xl p-4 shadow-xs">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 bg-amber-500 rounded-lg flex items-center justify-center text-white shrink-0 mt-0.5 shadow-xs">
                        <i class="fas fa-exclamation-triangle text-base"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-amber-950 uppercase tracking-wide">YÊU CẦU ĐẶT HÀNG ĐÃ ĐƯỢC HOÀN TRẢ ĐỂ CHỈNH SỬA / BỔ SUNG</h4>
                        <p class="text-xs text-amber-800 mt-1 font-semibold">Lý do từ người duyệt (PO / Admin):</p>
                        <div class="text-xs text-amber-900 bg-white p-3 rounded-lg border border-amber-200 mt-1.5 italic font-medium font-mono">
                            "{{ $orderRequest->rejection_note }}"
                        </div>
                        <p class="text-[11px] text-amber-700 mt-1.5">
                            <i class="fas fa-info-circle mr-1"></i>Bạn có thể điều chỉnh trực tiếp trên các trường thông tin bên dưới và bấm <strong>"Cập nhật & Gửi duyệt lại"</strong> mà không cần tạo mới.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Info Banner --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                <div class="text-xs text-blue-800">
                    <span class="font-bold">Đơn hàng:</span> {{ $sale->code }} | 
                    <span class="font-bold">Khách hàng:</span> {{ $sale->customer_name }}
                    @if($sale->project)
                        | <span class="font-bold">Dự án:</span> {{ $sale->project->code }} - {{ $sale->project->name }}
                    @endif
                </div>
            </div>
            <div id="tradeUpSerialError" class="hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"></div>

            {{-- Global SI/EU inputs (only need to fill once) --}}
            @php
                $firstOrItem = isset($orderRequest) ? $orderRequest->items->first() : null;
                $savedGlobalSiName = $firstOrItem ? $firstOrItem->si_name : '';
                $savedGlobalPosId = $firstOrItem ? $firstOrItem->pos_id : '';
                $savedGlobalEuName = '';
                $savedGlobalMst = '';
                if ($firstOrItem && $firstOrItem->eu_name_mst) {
                    $parts = explode(' - ', $firstOrItem->eu_name_mst, 2);
                    $savedGlobalEuName = $parts[0] ?? '';
                    $savedGlobalMst = $parts[1] ?? '';
                }
                $savedGlobalAddress = $firstOrItem ? $firstOrItem->address : '';

                // Lấy thông tin Project liên kết với đơn hàng hoặc các item
                $project = $sale->project ?: ($sale->items->first(fn($i) => $i->project)?->project ?: null);
                $partnerCustomer = ($project && $project->collaborate_type === 'partner')
                    ? $project->collaborateCustomer
                    : $sale->customer;

                $defaultEuName = $project ? ($project->eu_name_en ?: ($project->eu_name_vi ?: ($project->eu_name_abbr ?: ''))) : ($sale->customer?->name_en ?: ($sale->customer?->name ?? ''));
                $defaultMst = $project ? ($project->eu_tax_code ?: '') : ($sale->customer?->tax_code ?? '');
                $defaultAddress = $project ? ($project->address ?: ($project->eu_province ?: '')) : ($sale->customer?->address ?? '');

                $defaultSiName = $partnerCustomer?->name_en
                    ?: (($project && $project->collaborate_type === 'partner')
                        ? ($project->collaborate_company ?: ($project->collaborateCustomer?->name ?: $sale->customer_name))
                        : ($sale->customer?->name_en ?: ($sale->customer_name ?: ($sale->customer?->name ?? ''))));

                $defaultPosId = $partnerCustomer?->pos_id ?: '';
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SI Name (Tên tiếng Anh) <span class="text-red-500">*</span></label>
                    <div class="searchable-select" id="globalSiNameSelect">
                        <input type="text" id="global_si_name" name="global_si_name" required
                            class="searchable-input w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white"
                            placeholder="Gõ để tìm đối tác/khách hàng..." autocomplete="off"
                            value="{{ old('global_si_name', $savedGlobalSiName ?: $defaultSiName) }}">
                        <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                            @foreach($customers as $customer)
                                <div class="searchable-option px-3 py-2 hover:bg-emerald-50 cursor-pointer text-sm"
                                     data-value="{{ $customer->id }}"
                                     data-text="{{ ($customer->name_en ? $customer->name_en . ' ' : '') . $customer->name . ' ' . $customer->tax_code }}"
                                     data-name="{{ $customer->name_en ?: $customer->name }}"
                                     data-name-vi="{{ $customer->name }}"
                                     data-pos-id="{{ $customer->pos_id ?: 'New Partner' }}"
                                     data-tax="{{ $customer->tax_code }}"
                                     data-address="{{ $customer->address }}">
                                    <div class="font-medium text-gray-900">{{ $customer->name_en ?: $customer->name }}</div>
                                    <div class="text-xs text-gray-500 flex flex-wrap items-center gap-1.5 mt-0.5">
                                        @if($customer->name_en && $customer->name && $customer->name_en !== $customer->name)
                                            <span>{{ $customer->name }}</span>
                                        @endif
                                        @if($customer->tax_code)
                                            <span class="text-gray-400 font-mono">(MST: {{ $customer->tax_code }})</span>
                                        @endif
                                        @if($customer->pos_id)
                                            <span class="text-indigo-600 font-semibold">[POS: {{ $customer->pos_id }}]</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reseller POS ID <span class="text-red-500">*</span></label>
                    <input type="text" id="global_pos_id" name="global_pos_id" required
                        value="{{ old('global_pos_id', $savedGlobalPosId ?: ($defaultPosId ?: 'New Partner')) }}"
                        placeholder="Nhập POS ID hoặc New Partner"
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">EU Name</label>
                    <input type="text" id="global_eu_name" name="global_eu_name"
                        value="{{ old('global_eu_name', $savedGlobalEuName ?: $defaultEuName) }}"
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">MST</label>
                    <input type="text" id="global_mst" name="global_mst"
                        value="{{ old('global_mst', $savedGlobalMst ?: $defaultMst) }}"
                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" id="global_address" name="global_address"
                        value="{{ old('global_address', $savedGlobalAddress ?: $defaultAddress) }}"
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
                    <div class="flex items-center gap-2">
                        <a href="{{ route('sales.order-request.import-serials-template') }}"
                            class="text-xs px-2.5 py-1.5 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors shadow-xs flex items-center gap-1.5"
                            title="Tải file mẫu Excel chuẩn để điền Part Number và số S/N">
                            <i class="fas fa-download text-emerald-600"></i> Mẫu S/N
                        </a>
                        <button type="button" onclick="openImportSnModal()"
                            class="text-xs px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-xs flex items-center gap-1.5"
                            title="Import danh sách số S/N từ file Excel / CSV hoặc dán trực tiếp">
                            <i class="fas fa-file-excel"></i> Import file S/N
                        </button>
                        <button type="button" onclick="addRow()"
                            class="text-xs px-3 py-1.5 bg-emerald-500 text-white font-medium rounded-lg hover:bg-emerald-600 transition-colors shadow-xs flex items-center gap-1.5">
                            <i class="fas fa-plus"></i> Thêm dòng
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="itemsTable">
                        <thead>
                            <tr class="bg-yellow-200 text-[10px] border-b border-gray-300">
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[140px] align-middle uppercase">Vendor <span class="text-red-500">*</span></th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[90px] align-middle uppercase">Type <span class="text-red-500">*</span></th>
                                <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[70px] align-middle uppercase" title="Chọn cấp CQ cho tất cả sản phẩm">
                                    <div class="flex flex-col items-center gap-1">
                                        <span>CQ</span>
                                        <label class="inline-flex items-center gap-1 cursor-pointer font-normal text-[9px] text-emerald-800" title="Chọn/Bỏ chọn CQ cho tất cả dòng">
                                            <input type="checkbox" id="master_cq_checkbox" onchange="toggleMasterCq(this)" class="w-3.5 h-3.5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                            <span>Tất cả</span>
                                        </label>
                                    </div>
                                </th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[180px] align-middle uppercase">Part Number <span class="text-red-500">*</span></th>
                                <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 border-r border-gray-300 w-16 align-middle uppercase">Qty <span class="text-red-500">*</span></th>
                                <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 border-r border-gray-300 w-16 align-middle uppercase">Unit</th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[110px] align-middle uppercase">
                                    <div class="flex items-center justify-between">
                                        <span>SN</span>
                                        <button type="button" onclick="openImportSnModal()" class="text-blue-600 hover:text-blue-800 p-0.5 rounded hover:bg-yellow-300 transition-colors" title="Import danh sách S/N">
                                            <i class="fas fa-file-import text-xs"></i>
                                        </button>
                                    </div>
                                </th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[110px] align-middle uppercase">Exp date</th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[130px] align-middle uppercase">SI Name <span class="text-red-500">*</span></th>
                                <th rowspan="2" class="px-2 py-2 text-left font-bold text-gray-800 border-r border-gray-300 min-w-[110px] align-middle uppercase">POS ID <span class="text-red-500">*</span></th>
                                <th colspan="3" class="px-2 py-1.5 text-center font-bold text-gray-800 border-b border-r border-gray-300 uppercase">Thông tin CQ (Điền tay)</th>
                                <th rowspan="2" class="px-2 py-2 text-center font-bold text-gray-800 w-10 align-middle"></th>
                            </tr>
                            <tr class="bg-yellow-200 text-[10px] border-b border-gray-300">
                                <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px] uppercase">EU Name</th>
                                <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[100px] uppercase">MST</th>
                                <th class="px-2 py-1.5 text-center font-bold text-gray-800 border-r border-gray-300 min-w-[140px] uppercase">Address</th>
                            </tr>
                        </thead>
                        <tbody id="itemRows">
                            @php
                                // When editing, build a map of saved order request items keyed by sale_item_id
                                $orItemsMap = [];
                                if (isset($orderRequest)) {
                                    foreach ($orderRequest->items as $orItem) {
                                        if ($orItem->sale_item_id) {
                                            $orItemsMap[$orItem->sale_item_id] = $orItem;
                                        }
                                    }
                                }
                            @endphp
                            @foreach($sale->items as $idx => $saleItem)
                            @php
                                $partNumber = $saleItem->product ? $saleItem->product->code : $saleItem->product_name;
                                $pnUpper = strtoupper((string) ($partNumber ?? ''));
                                $pNameUpper = strtoupper((string) ($saleItem->product?->name ?? $saleItem->product_name ?? ''));
                                $pCatUpper = strtoupper((string) ($saleItem->product?->category?->name ?? ''));
                                $isLicenseDetected = str_contains($pnUpper, 'COTERM')
                                    || str_contains($pnUpper, 'CO-TERM')
                                    || str_contains($pNameUpper, 'COTERM')
                                    || str_contains($pNameUpper, 'CO-TERM')
                                    || str_contains($pNameUpper, 'LICENSE')
                                    || str_contains($pNameUpper, 'BẢN QUYỀN')
                                    || str_contains($pNameUpper, 'GIA HẠN')
                                    || str_starts_with($pnUpper, 'FC-')
                                    || str_starts_with($pnUpper, 'LIC-')
                                    || str_contains($pCatUpper, 'LICENSE')
                                    || ($saleItem->product?->type ?? '') === 'service'
                                    || ($saleItem->product?->type ?? '') === 'license';

                                $defaultType = $isLicenseDetected ? 'License' : 'HW';

                                // Use saved order request item data if editing
                                $orItem = $orItemsMap[$saleItem->id] ?? null;
                                $savedVendorId = $orItem ? $orItem->vendor_id : ($saleItem->supplier_id ?: ($saleItem->product?->supplier_id ?? ($project?->vendor_id ?? '')));
                                $savedType = $orItem ? $orItem->type : ($saleItem->type ?? $defaultType);
                                $savedPartNumber = $orItem ? $orItem->part_number : $partNumber;
                                $savedQty = $orItem ? $orItem->quantity : $saleItem->quantity;
                                $savedUnit = $orItem ? $orItem->unit : ($saleItem->product->unit ?? '');
                                $savedSn = $orItem ? $orItem->serial_number : ($saleItem->serial_number ?: ($project?->sn_numbers ?? ''));
                                $savedExpDate = $orItem && $orItem->exp_date ? $orItem->exp_date->format('Y-m-d') : '';
                                $savedSerialExpiryDates = $orItem?->serial_expiry_dates ?? [];
                                // Item specific default if row has item-level project
                                $itemProject = $saleItem->project ?: $project;
                                $itemDefaultEuName = $itemProject ? ($itemProject->eu_name_vi ?: ($itemProject->eu_name_en ?: ($itemProject->eu_name_abbr ?: ''))) : $defaultEuName;
                                $itemDefaultMst = $itemProject ? ($itemProject->eu_tax_code ?: '') : $defaultMst;
                                $itemDefaultAddress = $itemProject ? ($itemProject->address ?: ($itemProject->eu_province ?: '')) : $defaultAddress;
                                $itemPartnerCustomer = ($itemProject && $itemProject->collaborate_type === 'partner')
                                    ? $itemProject->collaborateCustomer
                                    : $partnerCustomer;

                                $itemDefaultSiName = $itemPartnerCustomer?->name_en
                                    ?: (($itemProject && $itemProject->collaborate_type === 'partner')
                                        ? ($itemProject->collaborate_company ?: ($itemProject->collaborateCustomer?->name ?: $defaultSiName))
                                        : $defaultSiName);

                                $itemDefaultPosId = $itemPartnerCustomer?->pos_id ?: ($savedGlobalPosId ?: ($defaultPosId ?: 'New Partner'));

                                $savedSiName = $orItem ? $orItem->si_name : $itemDefaultSiName;
                                $savedPosId = $orItem ? $orItem->pos_id : $itemDefaultPosId;
                                $savedNeedsCq = $orItem ? $orItem->needs_cq : false;
                                $savedAddress = $orItem ? $orItem->address : ($itemDefaultAddress ?: '');
                                // Split eu_name_mst back into eu_name and mst
                                $savedEuName = '';
                                $savedMst = '';
                                if ($orItem && $orItem->eu_name_mst) {
                                    $parts = explode(' - ', $orItem->eu_name_mst, 2);
                                    $savedEuName = $parts[0] ?? '';
                                    $savedMst = $parts[1] ?? '';
                                } else {
                                    $savedEuName = $itemDefaultEuName ?: '';
                                    $savedMst = $itemDefaultMst ?: '';
                                }
                            @endphp
                            <tr class="item-row border-b border-gray-100 hover:bg-gray-50" data-index="{{ $idx }}">
                                <td class="px-1 py-1">
                                    <select name="order_request_items[{{ $idx }}][vendor_id]" required
                                        class="vendor-select w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"
                                        onchange="handleVendorTypeChange(this.closest('.item-row'))">
                                        <option value="">-- Chọn --</option>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}" data-name="{{ $s->name }}" {{ $s->id == $savedVendorId ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-1 py-1">
                                    <select name="order_request_items[{{ $idx }}][type]" required
                                        class="type-select w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"
                                        onchange="handleVendorTypeChange(this.closest('.item-row'))">
                                        <option value="">-- Chọn --</option>
                                        @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                            <option value="{{ $t }}" {{ $savedType == $t ? 'selected' : '' }}>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-1 py-1 text-center cq-checkbox-cell">
                                    <label class="cq-checkbox-label inline-flex items-center gap-1 cursor-pointer" style="display:none;" title="Tick nếu cần cấp CQ riêng cho item này">
                                        <input type="checkbox" name="order_request_items[{{ $idx }}][needs_cq]" value="1"
                                            class="needs-cq-checkbox w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                                            onchange="handleNeedsCqChange(this.closest('.item-row'))"
                                            {{ $savedNeedsCq ? 'checked' : '' }}>
                                        <span class="text-[10px] text-gray-600">CQ</span>
                                    </label>
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][part_number]" required
                                        value="{{ $savedPartNumber }}" placeholder="P/N"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                    <input type="hidden" name="order_request_items[{{ $idx }}][product_id]" value="{{ $saleItem->product_id }}">
                                    <input type="hidden" name="order_request_items[{{ $idx }}][sale_item_id]" value="{{ $saleItem->id }}">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="number" name="order_request_items[{{ $idx }}][quantity]" required step="0.01"
                                        value="{{ $savedQty }}"
                                        class="qty-input w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"
                                        oninput="updateSnInputs(this.closest('.item-row'))">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][unit]"
                                        value="{{ $savedUnit }}" placeholder="Đơn vị"
                                        class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                </td>
                                <td class="px-1 py-1 min-w-[135px]">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[9px] text-gray-400 font-medium">S/N & Hạn</span>
                                        <button type="button" onclick="openImportSnModalForRow(this)" class="text-[10px] text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-0.5" title="Import / Dán S/N cho dòng này">
                                            <i class="fas fa-file-import"></i> Nạp S/N
                                        </button>
                                    </div>
                                    <div class="sn-inputs-container space-y-1" data-name-pattern="order_request_items[{{ $idx }}][serial_number][]">
                                        @php
                                            $qtyCount = max(1, (int)floor((float)$savedQty));
                                            $existingSerials = !empty($savedSn) ? array_map('trim', explode(',', $savedSn)) : [];
                                        @endphp
                                        @for($sIdx = 0; $sIdx < $qtyCount; $sIdx++)
                                            @php $serialValue = $existingSerials[$sIdx] ?? ''; @endphp
                                            <div class="flex gap-1">
                                                <input type="text" name="order_request_items[{{ $idx }}][serial_number][]"
                                                    value="{{ $serialValue }}"
                                                    placeholder="{{ $qtyCount > 1 ? 'SN ' . ($sIdx + 1) : 'SN' }}"
                                                    class="sn-input w-3/5 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400" autocomplete="off">
                                                <input type="date" name="order_request_items[{{ $idx }}][serial_expiry_dates][]"
                                                    value="{{ $savedSerialExpiryDates[$serialValue] ?? $savedExpDate }}"
                                                    class="sn-expiry-input w-2/5 border border-gray-300 rounded px-1 py-1 text-xs" title="Hạn dùng của S/N này">
                                            </div>
                                        @endfor
                                    </div>
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][exp_date]" placeholder="YYYY-MM-DD"
                                        value="{{ $savedExpDate }}"
                                        class="exp-date-picker w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][si_name]" value="{{ $savedSiName }}" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white" placeholder="Tên SI (tiếng Anh)" autocomplete="off">
                                </td>
                                <td class="px-1 py-1">
                                    <input type="text" name="order_request_items[{{ $idx }}][pos_id]" value="{{ $savedPosId }}" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white" placeholder="POS ID (hoặc New Partner)" autocomplete="off">
                                </td>
                                <td class="px-1 py-1 eu-field">
                                    <input type="text" name="order_request_items[{{ $idx }}][eu_name]" value="{{ $savedEuName }}" class="eu-name-input w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập EU Name" autocomplete="off">
                                </td>
                                <td class="px-1 py-1 eu-field">
                                    <input type="text" name="order_request_items[{{ $idx }}][mst]" value="{{ $savedMst }}" class="mst-input w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập MST" autocomplete="off">
                                </td>
                                <td class="px-1 py-1 eu-field">
                                    <input type="text" name="order_request_items[{{ $idx }}][address]" value="{{ $savedAddress }}"
                                        class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập thông tin" autocomplete="off">
                                </td>
                                <td class="px-1 py-1 text-center">
                                    <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                            @if(isset($orderRequest))
                                @php
                                    $renderedSaleItemIds = $sale->items->pluck('id')->toArray();
                                    $extraOrItems = $orderRequest->items->filter(fn($it) => empty($it->sale_item_id) || !in_array($it->sale_item_id, $renderedSaleItemIds));
                                    $extraStartIdx = $sale->items->count();
                                @endphp
                                @foreach($extraOrItems as $extraIdx => $extraItem)
                                    @php
                                        $rowIdx = $extraStartIdx + $extraIdx;
                                        $extraEuName = '';
                                        $extraMst = '';
                                        if ($extraItem->eu_name_mst) {
                                            $parts = explode(' - ', $extraItem->eu_name_mst, 2);
                                            $extraEuName = $parts[0] ?? '';
                                            $extraMst = $parts[1] ?? '';
                                        }
                                        $extraExpDate = $extraItem->exp_date ? $extraItem->exp_date->format('Y-m-d') : '';
                                    @endphp
                                    <tr class="item-row border-b border-gray-100 hover:bg-gray-50" data-index="{{ $rowIdx }}">
                                        <td class="px-1 py-1">
                                            <select name="order_request_items[{{ $rowIdx }}][vendor_id]" required
                                                class="vendor-select w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"
                                                onchange="handleVendorTypeChange(this.closest('.item-row'))">
                                                <option value="">-- Chọn --</option>
                                                @foreach($suppliers as $s)
                                                    <option value="{{ $s->id }}" data-name="{{ $s->name }}" {{ $s->id == $extraItem->vendor_id ? 'selected' : '' }}>{{ $s->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-1 py-1">
                                            <select name="order_request_items[{{ $rowIdx }}][type]" required
                                                class="type-select w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"
                                                onchange="handleVendorTypeChange(this.closest('.item-row'))">
                                                <option value="">-- Chọn --</option>
                                                @foreach(\App\Models\SaleOrderRequest::TYPES as $t)
                                                    <option value="{{ $t }}" {{ $extraItem->type == $t ? 'selected' : '' }}>{{ $t }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-1 py-1 text-center cq-checkbox-cell">
                                            <label class="cq-checkbox-label inline-flex items-center gap-1 cursor-pointer" style="display:none;" title="Tick nếu cần cấp CQ riêng cho item này">
                                                <input type="checkbox" name="order_request_items[{{ $rowIdx }}][needs_cq]" value="1"
                                                    class="needs-cq-checkbox w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                                                    onchange="handleNeedsCqChange(this.closest('.item-row'))"
                                                    {{ $extraItem->needs_cq ? 'checked' : '' }}>
                                                <span class="text-[10px] text-gray-600">CQ</span>
                                            </label>
                                        </td>
                                        <td class="px-1 py-1">
                                            <input type="text" name="order_request_items[{{ $rowIdx }}][part_number]" required
                                                value="{{ $extraItem->part_number }}" placeholder="P/N"
                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                            <input type="hidden" name="order_request_items[{{ $rowIdx }}][product_id]" value="{{ $extraItem->product_id }}">
                                            <input type="hidden" name="order_request_items[{{ $rowIdx }}][sale_item_id]" value="">
                                        </td>
                                        <td class="px-1 py-1">
                                            <input type="number" name="order_request_items[{{ $rowIdx }}][quantity]" required step="0.01"
                                                value="{{ $extraItem->quantity }}"
                                                class="qty-input w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"
                                                oninput="updateSnInputs(this.closest('.item-row'))">
                                        </td>
                                        <td class="px-1 py-1">
                                            <input type="text" name="order_request_items[{{ $rowIdx }}][unit]"
                                                value="{{ $extraItem->unit }}" placeholder="Đơn vị"
                                                class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                                        </td>
                                        <td class="px-1 py-1 min-w-[135px]">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-[9px] text-gray-400 font-medium">S/N & Hạn</span>
                                                <button type="button" onclick="openImportSnModalForRow(this)" class="text-[10px] text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-0.5" title="Import / Dán S/N cho dòng này">
                                                    <i class="fas fa-file-import"></i> Nạp S/N
                                                </button>
                                            </div>
                                            <div class="sn-inputs-container space-y-1" data-name-pattern="order_request_items[{{ $rowIdx }}][serial_number][]">
                                                @php
                                                    $extraQtyCount = max(1, (int)floor((float)$extraItem->quantity));
                                                    $extraSerials = !empty($extraItem->serial_number) ? array_map('trim', explode(',', $extraItem->serial_number)) : [];
                                                    $extraSerialDates = $extraItem->serial_expiry_dates ?? [];
                                                @endphp
                                                @for($esIdx = 0; $esIdx < $extraQtyCount; $esIdx++)
                                                    @php $esValue = $extraSerials[$esIdx] ?? ''; @endphp
                                                    <div class="flex gap-1">
                                                        <input type="text" name="order_request_items[{{ $rowIdx }}][serial_number][]"
                                                            value="{{ $esValue }}"
                                                            placeholder="{{ $extraQtyCount > 1 ? 'SN ' . ($esIdx + 1) : 'SN' }}"
                                                            class="sn-input w-3/5 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400" autocomplete="off">
                                                        <input type="date" name="order_request_items[{{ $rowIdx }}][serial_expiry_dates][]"
                                                            value="{{ $extraSerialDates[$esValue] ?? $extraExpDate }}"
                                                            class="sn-expiry-input w-2/5 border border-gray-300 rounded px-1 py-1 text-xs" title="Hạn dùng của S/N này">
                                                    </div>
                                                @endfor
                                            </div>
                                        </td>
                                        <td class="px-1 py-1">
                                            <input type="text" name="order_request_items[{{ $rowIdx }}][exp_date]" placeholder="YYYY-MM-DD"
                                                value="{{ $extraExpDate }}"
                                                class="exp-date-picker w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400" autocomplete="off">
                                        </td>
                                        <td class="px-1 py-1">
                                            <input type="text" name="order_request_items[{{ $rowIdx }}][si_name]" value="{{ $extraItem->si_name }}" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white" placeholder="Tên SI (tiếng Anh)" autocomplete="off">
                                        </td>
                                        <td class="px-1 py-1">
                                            <input type="text" name="order_request_items[{{ $rowIdx }}][pos_id]" value="{{ $extraItem->pos_id ?: 'New Partner' }}" required class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white" placeholder="POS ID (hoặc New Partner)" autocomplete="off">
                                        </td>
                                        <td class="px-1 py-1 eu-field">
                                            <input type="text" name="order_request_items[{{ $rowIdx }}][eu_name]" value="{{ $extraEuName }}" class="eu-name-input w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập EU Name" autocomplete="off">
                                        </td>
                                        <td class="px-1 py-1 eu-field">
                                            <input type="text" name="order_request_items[{{ $rowIdx }}][mst]" value="{{ $extraMst }}" class="mst-input w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập MST" autocomplete="off">
                                        </td>
                                        <td class="px-1 py-1 eu-field">
                                            <input type="text" name="order_request_items[{{ $rowIdx }}][address]" value="{{ $extraItem->address }}"
                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập thông tin" autocomplete="off">
                                        </td>
                                        <td class="px-1 py-1 text-center">
                                            <button type="button" onclick="removeRow(this)" class="text-red-400 hover:text-red-600">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-2 bg-yellow-50 text-[10px] text-gray-600 border-t border-gray-200">
                    <span class="font-bold text-red-500">(*)</span>: Bắt buộc điền. <span class="bg-gray-100 px-1 border border-gray-200">Vùng màu xám</span>: Sales tự điền tay.
                </div>
            </div>

            <!-- License từ NPP khác (Đặt hàng 2) -->
            <div class="bg-amber-50/70 border border-amber-200 rounded-lg p-3">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_license_from_other_distributor" id="is_license_from_other_distributor" value="1"
                        onchange="toggleOtherDistributorInput(this)"
                        class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500"
                        {{ old('is_license_from_other_distributor', isset($orderRequest) ? $orderRequest->is_license_from_other_distributor : false) ? 'checked' : '' }}>
                    <span class="text-xs font-bold text-gray-800 uppercase">
                        <i class="fas fa-certificate text-amber-600 mr-1"></i> Có đính kèm file License từ NPP khác
                    </span>
                </label>
                <div id="other_distributor_wrapper" class="mt-2 {{ old('is_license_from_other_distributor', isset($orderRequest) ? $orderRequest->is_license_from_other_distributor : false) ? '' : 'hidden' }}">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tên Nhà Phân Phối (NPP) khác đã cấp license:</label>
                    <input type="text" name="other_distributor_name" id="other_distributor_name"
                        value="{{ old('other_distributor_name', isset($orderRequest) ? $orderRequest->other_distributor_name : '') }}"
                        placeholder="Nhập tên NPP cấp license..."
                        class="w-full md:w-1/2 border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:ring-1 focus:ring-amber-400">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Ghi chú cho PO team</label>
                    <textarea name="order_request_note" rows="2" placeholder="Ghi chú thêm nếu có..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">{{ isset($orderRequest) ? $orderRequest->note : '' }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">
                        File đính kèm <span class="text-red-500">* (Bắt buộc)</span>
                    </label>
                    <input type="file" name="order_request_files[]" id="order_request_files" multiple
                        class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-300 rounded-lg p-1">
                    <p class="text-[11px] text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1 text-emerald-600"></i> Cho phép chọn và đính kèm nhiều file cùng lúc (PO, License, BOM...).
                    </p>
                    @if(isset($orderRequest) && $orderRequest->attachments->count() > 0)
                        <div class="mt-2 text-xs text-gray-600">
                            <span class="font-medium text-emerald-700">Đã có {{ $orderRequest->attachments->count() }} file đính kèm:</span>
                            <ul class="list-disc list-inside mt-1 text-[11px]">
                                @foreach($orderRequest->attachments as $att)
                                    <li><a href="{{ Storage::url($att->file_path) }}" target="_blank" class="text-blue-600 underline">{{ $att->file_name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="px-4 py-4 bg-gray-50 border-t flex items-center justify-end gap-3">
            <a href="{{ route('sales.show', $sale->id) }}" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Hủy bỏ
            </a>
            <button type="submit" formnovalidate onclick="document.getElementById('action_type').value='draft';"
                class="px-5 py-2 bg-amber-500 text-white font-bold text-sm rounded-lg hover:bg-amber-600 shadow-md transition-colors">
                <i class="fas fa-save mr-2"></i> Lưu nháp
            </button>
            <button type="button" onclick="document.getElementById('action_type').value='submit'; showConfirmModal();"
                class="px-8 py-2 bg-emerald-600 text-white font-bold text-sm rounded-lg hover:bg-emerald-700 shadow-md transition-colors">
                <i class="fas fa-paper-plane mr-2"></i> {{ isset($orderRequest) && $orderRequest->status !== 'draft' ? 'Cập nhật & Gửi duyệt lại' : 'Gửi yêu cầu đặt hàng' }}
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
                <h3 class="text-lg font-bold text-gray-900 mb-2">
                    {{ isset($orderRequest) && $orderRequest->status !== 'draft' ? 'Xác nhận cập nhật & gửi duyệt lại' : 'Xác nhận gửi yêu cầu đặt hàng' }}
                </h3>
                <p class="text-sm text-gray-500 mb-6">
                    {{ isset($orderRequest) && $orderRequest->status !== 'draft' ? 'Yêu cầu đặt hàng sẽ được cập nhật và gửi lại cho PO Team / Admin xử lý tiếp.' : 'Bạn có chắc chắn muốn gửi yêu cầu đặt hàng này cho PO Team?' }}
                </p>
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

{{-- Modal Import S/N từ File hoặc Danh sách --}}
<div id="importSnModal" class="fixed inset-0 z-[210] hidden">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" id="importSnModalBg" onclick="closeImportSnModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[92vh] flex flex-col transform transition-all overflow-hidden" id="importSnModalContent">
            {{-- Modal Header --}}
            <div class="p-4 sm:p-5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center shadow-inner">
                        <i class="fas fa-barcode text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold">Import danh sách số Serial Number (S/N)</h3>
                        <p class="text-xs text-emerald-100">Hỗ trợ file Excel (.xlsx, .xls), CSV, Text hoặc dán trực tiếp danh sách S/N</p>
                    </div>
                </div>
                <button type="button" onclick="closeImportSnModal()" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-5 overflow-y-auto space-y-4 text-xs flex-1">
                {{-- Cấu hình đích đến & tùy chọn --}}
                <div class="bg-emerald-50/60 border border-emerald-200 rounded-xl p-3.5 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">
                                <i class="fas fa-bullseye text-emerald-600 mr-1"></i> Phạm vi áp dụng S/N:
                            </label>
                            <select id="snModalTargetMode" onchange="handleSnTargetModeChange(this.value)" class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs bg-white focus:ring-1 focus:ring-emerald-500">
                                <option value="auto">Tự động phân bổ theo Part Number (Khuyên dùng)</option>
                                <option value="specific">Áp dụng cho dòng sản phẩm cụ thể</option>
                            </select>
                        </div>
                        <div id="snModalSpecificRowWrapper" class="hidden">
                            <label class="block font-bold text-gray-700 mb-1">
                                <i class="fas fa-crosshairs text-blue-600 mr-1"></i> Chọn dòng sản phẩm đích:
                            </label>
                            <select id="snModalTargetRowSelect" class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs bg-white focus:ring-1 focus:ring-emerald-500">
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-emerald-200/70 flex flex-wrap gap-4 text-gray-700">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" id="snAutoExpandQty" checked class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                            <span class="font-medium">Tự động tăng Số lượng (Qty) nếu số lượng S/N nhiều hơn Qty hiện tại</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" id="snOverwriteExisting" checked class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                            <span class="font-medium">Ghi đè số S/N hiện có</span>
                        </label>
                    </div>
                </div>

                {{-- Tabs chọn phương thức nhập --}}
                <div>
                    <div class="flex border-b border-gray-200 gap-2 mb-3">
                        <button type="button" id="snTabBtnFile" onclick="switchSnTab('file')"
                            class="px-3.5 py-2 font-bold text-xs border-b-2 border-emerald-600 text-emerald-700 transition-colors flex items-center gap-1.5">
                            <i class="fas fa-file-excel"></i> Nhập từ File (.xlsx, .xls, .csv, .txt)
                        </button>
                        <button type="button" id="snTabBtnPaste" onclick="switchSnTab('paste')"
                            class="px-3.5 py-2 font-bold text-xs border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1.5">
                            <i class="fas fa-paste"></i> Dán danh sách trực tiếp (Copy - Paste)
                        </button>
                    </div>

                    {{-- Tab 1: Upload File --}}
                    <div id="snTabPaneFile" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Chọn file từ máy tính của bạn:</span>
                            <a href="{{ route('sales.order-request.import-serials-template') }}"
                                class="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-800 font-semibold text-xs underline">
                                <i class="fas fa-download"></i> Tải file mẫu Excel chuẩn (.xlsx)
                            </a>
                        </div>

                        <div id="snDropzone" class="border-2 border-dashed border-gray-300 hover:border-emerald-500 rounded-xl p-5 text-center transition-colors cursor-pointer bg-gray-50 hover:bg-emerald-50/30"
                            onclick="document.getElementById('snFileInput').click()">
                            <input type="file" id="snFileInput" accept=".xlsx,.xls,.csv,.txt" class="hidden" onchange="handleSnFileChosen(this)">
                            <div class="w-12 h-12 mx-auto mb-2 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600">
                                <i class="fas fa-cloud-upload-alt text-2xl"></i>
                            </div>
                            <p class="font-semibold text-gray-700 text-sm" id="snFileDisplayName">Kéo thả file vào đây hoặc bấm để chọn file</p>
                            <p class="text-gray-400 text-[11px] mt-1">Định dạng hỗ trợ: Excel (.xlsx, .xls), CSV (.csv), Text (.txt) - Tối đa 10MB</p>
                        </div>
                    </div>

                    {{-- Tab 2: Dán trực tiếp --}}
                    <div id="snTabPanePaste" class="hidden space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-gray-600 font-medium">Dán danh sách S/N vào khung dưới đây:</label>
                            <span class="text-[11px] text-gray-400">1 dòng = 1 S/N</span>
                        </div>
                        <textarea id="snPasteTextarea" rows="6"
                            placeholder="Ví dụ dán mỗi dòng 1 S/N:&#10;FGT60E1234567890&#10;FGT60E1234567891&#10;&#10;Hoặc định dạng [Part Number] [dấu phẩy hoặc tab] [Serial Number] [Hạn dùng]:&#10;FG-60E, FGT60E1234567890, 2026-12-31&#10;FG-60E, FGT60E1234567891, 2026-12-31"
                            class="w-full border border-gray-300 rounded-lg p-3 text-xs font-mono focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                    </div>
                </div>

                {{-- Nút trigger parse --}}
                <div class="flex items-center justify-between pt-1">
                    <button type="button" id="btnParseSn" onclick="triggerParseSn()"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition-colors shadow-sm inline-flex items-center gap-2">
                        <i class="fas fa-search" id="btnParseSnIcon"></i>
                        <span id="btnParseSnText">Đọc & Xem trước dữ liệu S/N</span>
                    </button>
                    <span id="snParseStatusText" class="text-gray-500 text-xs italic"></span>
                </div>

                {{-- Preview Section --}}
                <div id="snPreviewSection" class="hidden border border-gray-200 rounded-xl overflow-hidden bg-white shadow-xs space-y-0">
                    <div class="bg-gray-100 px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
                        <span class="font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-check-circle text-emerald-600"></i>
                            Kết quả đọc dữ liệu: <span id="snPreviewTotalCount" class="text-emerald-700 font-extrabold text-sm">0</span> số S/N
                        </span>
                        <span id="snPreviewSourceLabel" class="text-[11px] text-gray-500"></span>
                    </div>

                    <div id="snPreviewUnassignedAlert" class="hidden p-3 bg-amber-50 border-b border-amber-200 text-amber-800 text-xs flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle text-amber-500 text-sm"></i>
                        <span>Phát hiện <strong id="snPreviewUnassignedCount">0</strong> S/N không kèm Part Number. Các S/N này sẽ được áp dụng vào dòng đích được chọn.</span>
                    </div>

                    <div class="max-h-56 overflow-y-auto p-3">
                        <table class="w-full text-left text-xs" id="snPreviewTable">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-600">
                                    <th class="py-1.5 px-2 font-bold">Part Number (P/N)</th>
                                    <th class="py-1.5 px-2 font-bold text-center">Số lượng S/N</th>
                                    <th class="py-1.5 px-2 font-bold">Danh sách số S/N (Mẫu)</th>
                                    <th class="py-1.5 px-2 font-bold">Khớp trên bảng</th>
                                </tr>
                            </thead>
                            <tbody id="snPreviewTbody" class="divide-y divide-gray-100">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="p-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-3 shrink-0">
                <button type="button" onclick="closeImportSnModal()"
                    class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors">
                    Hủy bỏ
                </button>
                <button type="button" id="btnApplySnToTable" onclick="applyParsedSnToTable()" disabled
                    class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 disabled:cursor-not-allowed rounded-lg shadow-sm transition-colors inline-flex items-center gap-1.5">
                    <i class="fas fa-check-double"></i> Xác nhận & Áp dụng vào bảng
                </button>
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
                if (opt.dataset.posId) {
                    const posInput = document.getElementById('global_pos_id');
                    if (posInput) {
                        posInput.value = opt.dataset.posId;
                    }
                }
                if (opt.dataset.tax) {
                    const mstInput = document.getElementById('global_mst');
                    if (mstInput && !mstInput.value) {
                        mstInput.value = opt.dataset.tax;
                    }
                }
                if (opt.dataset.address) {
                    const addrInput = document.getElementById('global_address');
                    if (addrInput && !addrInput.value) {
                        addrInput.value = opt.dataset.address;
                    }
                }
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
        
        document.querySelectorAll('.item-row').forEach(row => {
            const siInput = row.querySelector('input[name$="[si_name]"]');
            if (siInput) siInput.value = si;
            
            const posInput = row.querySelector('input[name$="[pos_id]"]');
            if (posInput) posInput.value = pos;
            
            const euInput = row.querySelector('.eu-name-input');
            const mstInput = row.querySelector('.mst-input');
            const addrInput = row.querySelector('input[name$="[address]"]');
            
            if (euInput) euInput.value = eu;
            if (mstInput) mstInput.value = mst;
            if (addrInput) addrInput.value = addr;
        });
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

        // Trigger handleVendorTypeChange for all rows to show/hide CQ checkbox
        document.querySelectorAll('.item-row').forEach(row => {
            handleVendorTypeChange(row);
        });
    }

    function addRow() {
        const tbody = document.getElementById('itemRows');
        const tr = document.createElement('tr');
        tr.className = 'item-row border-b border-gray-100 hover:bg-gray-50';
        
        const globalVendor = document.getElementById('global_vendor_id').value;
        const globalType = document.getElementById('global_type').value;

        let supplierOptions = '<option value="">-- Chọn --</option>';
        suppliers.forEach(s => {
            supplierOptions += `<option value="${s.id}" data-name="${s.name}" ${globalVendor == s.id ? 'selected' : ''}>${s.name}</option>`;
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
                    class="vendor-select w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"
                    onchange="handleVendorTypeChange(this.closest('.item-row'))">
                    ${supplierOptions}
                </select>
            </td>
            <td class="px-1 py-1">
                <select name="order_request_items[${rowIdx}][type]" required
                    class="type-select w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"
                    onchange="handleVendorTypeChange(this.closest('.item-row'))">
                    ${typeOptions}
                </select>
            </td>
            <td class="px-1 py-1 text-center cq-checkbox-cell">
                <label class="cq-checkbox-label inline-flex items-center gap-1 cursor-pointer" style="display:none;" title="Tick nếu cần cấp CQ riêng cho item này">
                    <input type="checkbox" name="order_request_items[${rowIdx}][needs_cq]" value="1"
                        class="needs-cq-checkbox w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                        onchange="handleNeedsCqChange(this.closest('.item-row'))">
                    <span class="text-[10px] text-gray-600">CQ</span>
                </label>
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][part_number]" required placeholder="P/N"
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                <input type="hidden" name="order_request_items[${rowIdx}][product_id]" value="">
                <input type="hidden" name="order_request_items[${rowIdx}][sale_item_id]" value="">
            </td>
            <td class="px-1 py-1">
                <input type="number" name="order_request_items[${rowIdx}][quantity]" required step="0.01" value="1"
                    class="qty-input w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400"
                    oninput="updateSnInputs(this.closest('.item-row'))">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][unit]" placeholder="Đơn vị"
                    class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
            </td>
            <td class="px-1 py-1 min-w-[135px]">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[9px] text-gray-400 font-medium">S/N & Hạn</span>
                    <button type="button" onclick="openImportSnModalForRow(this)" class="text-[10px] text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-0.5" title="Import / Dán S/N cho dòng này">
                        <i class="fas fa-file-import"></i> Nạp S/N
                    </button>
                </div>
                <div class="sn-inputs-container space-y-1" data-name-pattern="order_request_items[${rowIdx}][serial_number][]">
                    <div class="flex gap-1">
                        <input type="text" name="order_request_items[${rowIdx}][serial_number][]" placeholder="SN"
                            class="sn-input w-3/5 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400" autocomplete="off">
                        <input type="date" name="order_request_items[${rowIdx}][serial_expiry_dates][]"
                            class="sn-expiry-input w-2/5 border border-gray-300 rounded px-1 py-1 text-xs" title="Hạn dùng của S/N này">
                    </div>
                </div>
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][exp_date]" placeholder="YYYY-MM-DD"
                    class="exp-date-picker w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][si_name]" value="${siGlobal}" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white" placeholder="Tên SI (tiếng Anh)">
            </td>
            <td class="px-1 py-1">
                <input type="text" name="order_request_items[${rowIdx}][pos_id]" value="${posGlobal || 'New Partner'}" required
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-white" placeholder="POS ID (hoặc New Partner)">
            </td>
            <td class="px-1 py-1 eu-field">
                <input type="text" name="order_request_items[${rowIdx}][eu_name]" value="${euGlobal}"
                    class="eu-name-input w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập EU Name">
            </td>
            <td class="px-1 py-1 eu-field">
                <input type="text" name="order_request_items[${rowIdx}][mst]" value="${mstGlobal}"
                    class="mst-input w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400 bg-gray-50" placeholder="Nhập MST">
            </td>
            <td class="px-1 py-1 eu-field">
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
        handleVendorTypeChange(tr);
        rowIdx++;
    }

    function updateSnInputs(row) {
        const qtyInput = row.querySelector('.qty-input') || row.querySelector('input[name*="[quantity]"]');
        const container = row.querySelector('.sn-inputs-container');
        if (!qtyInput || !container) return;

        const rawQty = parseFloat(qtyInput.value);
        const targetCount = isNaN(rawQty) || rawQty <= 0 ? 1 : Math.max(1, Math.min(500, Math.floor(rawQty)));
        
        const existingInputs = container.querySelectorAll('.sn-input');
        const currentValues = Array.from(existingInputs).map(inp => inp.value);
        const currentExpiryDates = Array.from(container.querySelectorAll('.sn-expiry-input')).map(inp => inp.value);

        let namePattern = container.dataset.namePattern;
        if (!namePattern) {
            const sampleInput = container.querySelector('input');
            namePattern = sampleInput ? sampleInput.getAttribute('name') : '';
            if (namePattern && !namePattern.endsWith('[]')) {
                namePattern += '[]';
            }
            container.dataset.namePattern = namePattern;
        }

        container.innerHTML = '';
        for (let i = 0; i < targetCount; i++) {
            const pair = document.createElement('div');
            pair.className = 'flex gap-1 mt-1 first:mt-0';
            const input = document.createElement('input');
            input.type = 'text';
            input.name = namePattern;
            input.placeholder = targetCount > 1 ? `SN ${i + 1}` : 'SN';
            input.className = 'sn-input w-3/5 border border-gray-300 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400';
            input.autocomplete = 'off';
            input.value = currentValues[i] || '';
            const expiry = document.createElement('input');
            expiry.type = 'date';
            expiry.name = namePattern.replace('[serial_number][]', '[serial_expiry_dates][]');
            expiry.className = 'sn-expiry-input w-2/5 border border-gray-300 rounded px-1 py-1 text-xs';
            expiry.title = 'Hạn dùng của S/N này';
            expiry.value = currentExpiryDates[i] || '';
            pair.append(input, expiry);
            container.appendChild(pair);
        }
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
        
        // Initialize CQ checkbox visibility and SN inputs for all existing rows FIRST
        document.querySelectorAll('.item-row').forEach(row => {
            handleVendorTypeChange(row);
            updateSnInputs(row);
        });
        
        // Then sync global values
        syncGlobalToRows();
        
        initExpDatePicker(".exp-date-picker");
    });

    /**
     * Check if the selected vendor is Fortinet
     */
    function isFortinetVendor(row) {
        const vendorSelect = row.querySelector('.vendor-select');
        if (!vendorSelect || !vendorSelect.value) return false;
        const selectedOption = vendorSelect.options[vendorSelect.selectedIndex];
        const vendorName = selectedOption ? (selectedOption.getAttribute('data-name') || selectedOption.textContent) : '';
        return vendorName.toLowerCase().includes('fortinet');
    }

    /**
     * Handle vendor or type dropdown change:
     * - If vendor=Fortinet AND type=HW → show CQ checkbox, hide EU fields (unless CQ is checked)
     * - Otherwise → hide CQ checkbox, show EU fields as required
     */
    function handleVendorTypeChange(row) {
        const typeSelect = row.querySelector('.type-select');
        const cqLabel = row.querySelector('.cq-checkbox-label');
        const cqCheckbox = row.querySelector('.needs-cq-checkbox');
        const euFields = row.querySelectorAll('.eu-field');
        const euNameInput = row.querySelector('.eu-name-input');
        const mstInput = row.querySelector('.mst-input');
        const addrInput = row.querySelector('input[name$="[address]"]');

        if (!cqLabel || !typeSelect) return;

        // Auto set unit based on type
        const unitInput = row.querySelector('input[name$="[unit]"]');
        if (unitInput) {
            if (typeSelect.value === 'HW') {
                unitInput.value = 'Cái';
            } else if (typeSelect.value && typeSelect.value.toLowerCase().startsWith('lic')) {
                unitInput.value = 'Bộ';
            }
        }

        const isFTN = isFortinetVendor(row);
        const isHW = typeSelect.value === 'HW';

        if (isFTN && isHW) {
            // Show CQ checkbox label (so user can check CQ if needed for HW)
            cqLabel.style.display = '';
        } else {
            // Non-Fortinet or non-HW: hide CQ checkbox label
            cqLabel.style.display = 'none';
            cqCheckbox.checked = false;
        }

        // EU fields ALWAYS stay visible & active for all items
        euFields.forEach(td => {
            td.style.opacity = '1';
            const inputs = td.querySelectorAll('input');
            inputs.forEach(inp => inp.removeAttribute('tabindex'));
        });
    }

    /**
     * Handle CQ checkbox change:
     * - EU fields always stay visible & active, auto-filled from global EU info
     */
    function handleNeedsCqChange(row) {
        const euFields = row.querySelectorAll('.eu-field');
        const euNameInput = row.querySelector('.eu-name-input');
        const mstInput = row.querySelector('.mst-input');
        const addrInput = row.querySelector('input[name$="[address]"]');

        // EU fields ALWAYS stay visible & active
        euFields.forEach(td => {
            td.style.opacity = '1';
            const inputs = td.querySelectorAll('input');
            inputs.forEach(inp => inp.removeAttribute('tabindex'));
        });

        // Auto fill from global inputs if empty
        const globalEu = document.getElementById('global_eu_name') ? document.getElementById('global_eu_name').value : '';
        const globalMst = document.getElementById('global_mst') ? document.getElementById('global_mst').value : '';
        const globalAddr = document.getElementById('global_address') ? document.getElementById('global_address').value : '';
        
        if (euNameInput && !euNameInput.value) euNameInput.value = globalEu;
        if (mstInput && !mstInput.value) mstInput.value = globalMst;
        if (addrInput && !addrInput.value) addrInput.value = globalAddr;
    }

    // === Double-Enter to submit with confirmation modal ===
    let lastEnterTime = 0;
    const DOUBLE_ENTER_THRESHOLD = 500; // ms

    const confirmModal = document.getElementById('confirmSubmitModal');
    const confirmModalContent = document.getElementById('confirmModalContent');
    const confirmModalBg = document.getElementById('confirmModalBg');
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    const cancelSubmitBtn = document.getElementById('cancelSubmitBtn');
    const orderForm = document.getElementById('orderRequestForm');
    const isTradeUpOrder = @json($sale->trade_up_matrix !== 'none');

    function validateTradeUpSerialsBeforeSubmit() {
        const errorBox = document.getElementById('tradeUpSerialError');
        errorBox.classList.add('hidden');
        errorBox.textContent = '';
        document.querySelectorAll('.sn-input').forEach(input => input.classList.remove('border-red-500', 'ring-1', 'ring-red-400'));

        if (!isTradeUpOrder) return true;

        for (const row of document.querySelectorAll('.item-row')) {
            const type = (row.querySelector('.type-select')?.value || '').toUpperCase();
            if (type !== 'HW') continue;

            const quantity = Math.floor(Number(row.querySelector('.qty-input')?.value || 0));
            const serialInputs = Array.from(row.querySelectorAll('.sn-input'));
            const enteredCount = serialInputs.filter(input => input.value.trim() !== '').length;
            if (quantity < 1 || enteredCount !== quantity) {
                const partNumber = row.querySelector('input[name*="[part_number]"]')?.value || 'dòng hàng này';
                serialInputs.forEach(input => input.classList.add('border-red-500', 'ring-1', 'ring-red-400'));
                errorBox.textContent = `Đơn Trade up yêu cầu nhập đủ ${quantity} S/N cho dòng HW ${partNumber}. Hiện đã nhập ${enteredCount}/${quantity} S/N.`;
                errorBox.classList.remove('hidden');
                serialInputs.find(input => !input.value.trim())?.focus();
                return false;
            }
        }
        return true;
    }

    function showConfirmModal() {
        if (!validateTradeUpSerialsBeforeSubmit()) return;
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
        if (!validateTradeUpSerialsBeforeSubmit()) return;
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

    window.toggleMasterCq = function(masterCb) {
        const isChecked = masterCb.checked;
        document.querySelectorAll('.item-row').forEach(row => {
            const cqLabel = row.querySelector('.cq-checkbox-label');
            const cqCheckbox = row.querySelector('.needs-cq-checkbox');
            if (!cqCheckbox) return;

            if (isChecked) {
                if (cqLabel) cqLabel.style.display = '';
                cqCheckbox.checked = true;
            } else {
                cqCheckbox.checked = false;
            }
            if (typeof handleNeedsCqChange === 'function') {
                handleNeedsCqChange(row);
            }
        });
    };

    // ==========================================
    // S/N IMPORT LOGIC (File & Paste)
    // ==========================================
    let currentParsedSnData = null;
    let activeSnTab = 'file';

    window.openImportSnModal = function(preselectedTarget = null) {
        // Reset state
        currentParsedSnData = null;
        const applyBtn = document.getElementById('btnApplySnToTable');
        if (applyBtn) applyBtn.disabled = true;
        document.getElementById('snPreviewSection')?.classList.add('hidden');
        const statusText = document.getElementById('snParseStatusText');
        if (statusText) statusText.textContent = '';
        const fileInput = document.getElementById('snFileInput');
        if (fileInput) fileInput.value = '';
        const fileDisplay = document.getElementById('snFileDisplayName');
        if (fileDisplay) fileDisplay.textContent = 'Kéo thả file vào đây hoặc bấm để chọn file';
        const pasteArea = document.getElementById('snPasteTextarea');
        if (pasteArea) pasteArea.value = '';

        // Populate Target Row select
        const rowSelect = document.getElementById('snModalTargetRowSelect');
        if (rowSelect) {
            rowSelect.innerHTML = '';
            const rows = document.querySelectorAll('#itemRows .item-row');
            
            let targetRowIndexToSelect = null;
            rows.forEach((row, idx) => {
                const pnInput = row.querySelector('input[name*="[part_number]"]');
                const qtyInput = row.querySelector('.qty-input') || row.querySelector('input[name*="[quantity]"]');
                const pn = pnInput ? pnInput.value.trim() : '';
                const qty = qtyInput ? qtyInput.value.trim() : '1';

                const opt = document.createElement('option');
                opt.value = idx;
                opt.dataset.partNumber = pn.toUpperCase();
                opt.textContent = `Dòng ${idx + 1}: ${pn ? 'P/N ' + pn : '(Chưa có P/N)'} [Số lượng: ${qty}]`;
                rowSelect.appendChild(opt);

                if (preselectedTarget !== null) {
                    if (typeof preselectedTarget === 'number' && preselectedTarget === idx) {
                        targetRowIndexToSelect = idx;
                    } else if (typeof preselectedTarget === 'string' && (pn.toUpperCase() === preselectedTarget.toUpperCase() || preselectedTarget === 'ROW_' + idx)) {
                        targetRowIndexToSelect = idx;
                    }
                }
            });

            const targetMode = document.getElementById('snModalTargetMode');
            const specificWrapper = document.getElementById('snModalSpecificRowWrapper');

            if (targetRowIndexToSelect !== null) {
                if (targetMode) targetMode.value = 'specific';
                specificWrapper?.classList.remove('hidden');
                rowSelect.value = targetRowIndexToSelect;
            } else {
                if (targetMode) targetMode.value = 'auto';
                specificWrapper?.classList.add('hidden');
            }
        }

        switchSnTab('file');
        document.getElementById('importSnModal')?.classList.remove('hidden');
    };

    window.openImportSnModalForRow = function(btn) {
        const row = btn.closest('.item-row');
        const rows = Array.from(document.querySelectorAll('#itemRows .item-row'));
        const idx = rows.indexOf(row);
        const pnInput = row.querySelector('input[name*="[part_number]"]');
        const pn = pnInput ? pnInput.value.trim() : '';
        openImportSnModal(pn || (idx >= 0 ? idx : null));
    };

    window.closeImportSnModal = function() {
        document.getElementById('importSnModal')?.classList.add('hidden');
    };

    window.handleSnTargetModeChange = function(mode) {
        const wrapper = document.getElementById('snModalSpecificRowWrapper');
        if (mode === 'specific') {
            wrapper?.classList.remove('hidden');
        } else {
            wrapper?.classList.add('hidden');
        }
        if (currentParsedSnData) {
            renderSnPreview(currentParsedSnData);
        }
    };

    window.switchSnTab = function(tab) {
        activeSnTab = tab;
        const btnFile = document.getElementById('snTabBtnFile');
        const btnPaste = document.getElementById('snTabBtnPaste');
        const paneFile = document.getElementById('snTabPaneFile');
        const panePaste = document.getElementById('snTabPanePaste');

        if (tab === 'file') {
            if (btnFile) btnFile.className = 'px-3.5 py-2 font-bold text-xs border-b-2 border-emerald-600 text-emerald-700 transition-colors flex items-center gap-1.5';
            if (btnPaste) btnPaste.className = 'px-3.5 py-2 font-bold text-xs border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1.5';
            paneFile?.classList.remove('hidden');
            panePaste?.classList.add('hidden');
        } else {
            if (btnPaste) btnPaste.className = 'px-3.5 py-2 font-bold text-xs border-b-2 border-emerald-600 text-emerald-700 transition-colors flex items-center gap-1.5';
            if (btnFile) btnFile.className = 'px-3.5 py-2 font-bold text-xs border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1.5';
            panePaste?.classList.remove('hidden');
            paneFile?.classList.add('hidden');
        }
    };

    window.handleSnFileChosen = function(input) {
        if (input.files && input.files[0]) {
            const f = input.files[0];
            const display = document.getElementById('snFileDisplayName');
            if (display) {
                display.innerHTML = `<span class="text-emerald-700 font-bold">${f.name}</span> (${(f.size / 1024).toFixed(1)} KB)`;
            }
            // Auto trigger parse
            triggerParseSn();
        }
    };

    window.triggerParseSn = async function() {
        const fileInput = document.getElementById('snFileInput');
        const pasteText = document.getElementById('snPasteTextarea')?.value.trim() || '';

        if (activeSnTab === 'file' && (!fileInput || !fileInput.files || !fileInput.files[0])) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: 'Chưa chọn file', text: 'Vui lòng chọn hoặc kéo thả file Excel, CSV, hoặc TXT.', icon: 'warning' });
            } else {
                alert('Vui lòng chọn hoặc kéo thả file Excel, CSV, hoặc TXT.');
            }
            return;
        }

        if (activeSnTab === 'paste' && !pasteText) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: 'Chưa có nội dung', text: 'Vui lòng dán danh sách số S/N vào khung văn bản.', icon: 'warning' });
            } else {
                alert('Vui lòng dán danh sách số S/N vào khung văn bản.');
            }
            return;
        }

        const btn = document.getElementById('btnParseSn');
        const btnText = document.getElementById('btnParseSnText');
        const btnIcon = document.getElementById('btnParseSnIcon');
        const statusText = document.getElementById('snParseStatusText');

        if (btn) btn.disabled = true;
        if (btnIcon) btnIcon.className = 'fas fa-spinner fa-spin';
        if (btnText) btnText.textContent = 'Đang đọc dữ liệu...';
        if (statusText) statusText.textContent = 'Đang phân tích file / danh sách S/N...';

        const formData = new FormData();
        const csrfToken = document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}';
        formData.append('_token', csrfToken);

        const targetMode = document.getElementById('snModalTargetMode')?.value || 'auto';
        if (targetMode === 'specific') {
            const rowSelect = document.getElementById('snModalTargetRowSelect');
            const selectedOpt = rowSelect ? rowSelect.options[rowSelect.selectedIndex] : null;
            if (selectedOpt && selectedOpt.dataset.partNumber) {
                formData.append('target_part_number', selectedOpt.dataset.partNumber);
            }
        }

        if (activeSnTab === 'file' && fileInput && fileInput.files[0]) {
            formData.append('file', fileInput.files[0]);
        } else if (activeSnTab === 'paste') {
            formData.append('serials_text', pasteText);
        }

        try {
            const resp = await fetch("{{ route('sales.order-request.parse-serials') }}", {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await resp.json();

            if (resp.ok && result.success) {
                currentParsedSnData = result;
                renderSnPreview(result);
                const applyBtn = document.getElementById('btnApplySnToTable');
                if (applyBtn) applyBtn.disabled = false;
                if (statusText) statusText.textContent = `Đọc thành công ${result.total} số S/N!`;
            } else {
                throw new Error(result.message || 'Không thể đọc dữ liệu S/N.');
            }
        } catch (err) {
            document.getElementById('snPreviewSection')?.classList.add('hidden');
            const applyBtn = document.getElementById('btnApplySnToTable');
            if (applyBtn) applyBtn.disabled = true;
            if (statusText) statusText.textContent = 'Lỗi khi đọc file/dữ liệu!';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Lỗi đọc dữ liệu',
                    text: err.message || 'Có lỗi xảy ra khi phân tích file/danh sách S/N.',
                    icon: 'error'
                });
            } else {
                alert(err.message || 'Có lỗi xảy ra khi phân tích file/danh sách S/N.');
            }
        } finally {
            if (btn) btn.disabled = false;
            if (btnIcon) btnIcon.className = 'fas fa-search';
            if (btnText) btnText.textContent = 'Đọc & Xem trước dữ liệu S/N';
        }
    };

    function renderSnPreview(data) {
        const previewSec = document.getElementById('snPreviewSection');
        const countSpan = document.getElementById('snPreviewTotalCount');
        const srcLabel = document.getElementById('snPreviewSourceLabel');
        const tbody = document.getElementById('snPreviewTbody');
        const unassignedAlert = document.getElementById('snPreviewUnassignedAlert');
        const unassignedCountSpan = document.getElementById('snPreviewUnassignedCount');

        if (!previewSec || !tbody) return;

        previewSec.classList.remove('hidden');
        if (countSpan) countSpan.textContent = data.total;
        if (srcLabel) srcLabel.textContent = data.filename ? `Tệp: ${data.filename}` : 'Nguồn: Dán trực tiếp';

        const unassignedList = data.unassigned || [];
        if (unassignedList.length > 0) {
            unassignedAlert?.classList.remove('hidden');
            if (unassignedCountSpan) unassignedCountSpan.textContent = unassignedList.length;
        } else {
            unassignedAlert?.classList.add('hidden');
        }

        tbody.innerHTML = '';

        // Thu thập các P/N hiện có trên bảng
        const existingRowParts = new Map();
        document.querySelectorAll('#itemRows .item-row').forEach((row, idx) => {
            const pn = row.querySelector('input[name*="[part_number]"]')?.value.trim().toUpperCase() || '';
            if (pn) {
                if (!existingRowParts.has(pn)) existingRowParts.set(pn, []);
                existingRowParts.get(pn).push(idx + 1);
            }
        });

        const targetMode = document.getElementById('snModalTargetMode')?.value || 'auto';
        const specificRowSelect = document.getElementById('snModalTargetRowSelect');
        const specificIdx = specificRowSelect ? parseInt(specificRowSelect.value, 10) : 0;

        if (targetMode === 'specific') {
            const tr = document.createElement('tr');
            const samplePills = (data.raw_items || []).slice(0, 6).map(it => 
                `<span class="inline-block bg-gray-100 text-gray-800 text-[10px] px-1.5 py-0.5 rounded font-mono border border-gray-200">${it.serial}${it.exp_date ? ' (' + it.exp_date + ')' : ''}</span>`
            ).join(' ');
            const moreCount = (data.raw_items || []).length > 6 ? ` <span class="text-gray-400 text-[10px]">+${data.raw_items.length - 6} nữa</span>` : '';

            tr.innerHTML = `
                <td class="py-2 px-2 font-bold text-gray-800">
                    <span class="text-blue-700">Tất cả (${data.total} S/N)</span>
                </td>
                <td class="py-2 px-2 text-center font-bold text-emerald-700">${data.total}</td>
                <td class="py-2 px-2">${samplePills}${moreCount}</td>
                <td class="py-2 px-2">
                    <span class="inline-flex items-center gap-1 text-blue-700 font-semibold bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                        <i class="fas fa-arrow-right"></i> Dòng ${specificIdx + 1}
                    </span>
                </td>
            `;
            tbody.appendChild(tr);
            return;
        }

        // Chế độ Auto theo Part Number
        const byPart = data.by_part || {};
        const partKeys = Object.keys(byPart);

        if (partKeys.length === 0 && unassignedList.length > 0) {
            const tr = document.createElement('tr');
            const samplePills = unassignedList.slice(0, 6).map(it => 
                `<span class="inline-block bg-gray-100 text-gray-800 text-[10px] px-1.5 py-0.5 rounded font-mono border border-gray-200">${it.serial}</span>`
            ).join(' ');
            tr.innerHTML = `
                <td class="py-2 px-2 italic text-gray-500">(Không xác định P/N)</td>
                <td class="py-2 px-2 text-center font-bold text-emerald-700">${unassignedList.length}</td>
                <td class="py-2 px-2">${samplePills}</td>
                <td class="py-2 px-2">
                    <span class="text-amber-700 font-medium bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        Áp dụng vào dòng 1
                    </span>
                </td>
            `;
            tbody.appendChild(tr);
            return;
        }

        partKeys.forEach(pn => {
            const items = byPart[pn];
            const tr = document.createElement('tr');
            const samplePills = items.slice(0, 5).map(it => 
                `<span class="inline-block bg-gray-100 text-gray-800 text-[10px] px-1.5 py-0.5 rounded font-mono border border-gray-200">${it.serial}${it.exp_date ? ' (' + it.exp_date + ')' : ''}</span>`
            ).join(' ');
            const moreCount = items.length > 5 ? ` <span class="text-gray-400 text-[10px]">+${items.length - 5} nữa</span>` : '';

            let matchBadge = '';
            if (existingRowParts.has(pn)) {
                const rowNums = existingRowParts.get(pn).join(', ');
                matchBadge = `<span class="text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 inline-flex items-center gap-1"><i class="fas fa-check"></i> Khớp Dòng ${rowNums}</span>`;
            } else {
                matchBadge = `<span class="text-orange-700 font-semibold bg-orange-50 px-2 py-0.5 rounded border border-orange-200 inline-flex items-center gap-1"><i class="fas fa-plus-circle"></i> Sẽ thêm dòng mới</span>`;
            }

            tr.innerHTML = `
                <td class="py-2 px-2 font-bold text-emerald-800">${pn}</td>
                <td class="py-2 px-2 text-center font-bold text-emerald-700">${items.length}</td>
                <td class="py-2 px-2">${samplePills}${moreCount}</td>
                <td class="py-2 px-2">${matchBadge}</td>
            `;
            tbody.appendChild(tr);
        });

        if (unassignedList.length > 0) {
            const tr = document.createElement('tr');
            const samplePills = unassignedList.slice(0, 5).map(it => 
                `<span class="inline-block bg-gray-100 text-gray-800 text-[10px] px-1.5 py-0.5 rounded font-mono border border-gray-200">${it.serial}</span>`
            ).join(' ');
            tr.innerHTML = `
                <td class="py-2 px-2 italic text-gray-500">(Chưa có P/N)</td>
                <td class="py-2 px-2 text-center font-bold text-amber-600">${unassignedList.length}</td>
                <td class="py-2 px-2">${samplePills}</td>
                <td class="py-2 px-2">
                    <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Áp dụng dòng đầu tiên</span>
                </td>
            `;
            tbody.appendChild(tr);
        }
    }

    window.applyParsedSnToTable = function() {
        if (!currentParsedSnData || !currentParsedSnData.total) {
            return;
        }

        const autoExpand = document.getElementById('snAutoExpandQty')?.checked ?? true;
        const overwrite = document.getElementById('snOverwriteExisting')?.checked ?? true;
        const targetMode = document.getElementById('snModalTargetMode')?.value || 'auto';
        let appliedCount = 0;

        function fillSerialsIntoRow(row, serialItems) {
            if (!row || !serialItems || serialItems.length === 0) return 0;

            const qtyInput = row.querySelector('.qty-input') || row.querySelector('input[name*="[quantity]"]');
            let currentQty = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;
            const container = row.querySelector('.sn-inputs-container');
            if (!container) return 0;

            let existingInputs = container.querySelectorAll('.sn-input');
            let startIndex = 0;

            if (!overwrite) {
                for (let i = 0; i < existingInputs.length; i++) {
                    if (existingInputs[i].value.trim() !== '') {
                        startIndex = i + 1;
                    } else {
                        startIndex = i;
                        break;
                    }
                }
            }

            const neededSlots = startIndex + serialItems.length;
            if (autoExpand && neededSlots > currentQty) {
                if (qtyInput) {
                    qtyInput.value = neededSlots;
                    updateSnInputs(row);
                }
            }

            const updatedSnInputs = container.querySelectorAll('.sn-input');
            const updatedExpInputs = container.querySelectorAll('.sn-expiry-input');
            const rowExpPicker = row.querySelector('.exp-date-picker');

            let filled = 0;
            serialItems.forEach((item, sIdx) => {
                const targetSlot = startIndex + sIdx;
                if (targetSlot < updatedSnInputs.length) {
                    updatedSnInputs[targetSlot].value = item.serial || '';
                    if (item.exp_date && updatedExpInputs[targetSlot]) {
                        updatedExpInputs[targetSlot].value = item.exp_date;
                    }
                    if (item.exp_date && rowExpPicker && !rowExpPicker.value) {
                        rowExpPicker.value = item.exp_date;
                    }
                    filled++;
                }
            });

            return filled;
        }

        if (targetMode === 'specific') {
            const specificRowSelect = document.getElementById('snModalTargetRowSelect');
            const specificIdx = specificRowSelect ? parseInt(specificRowSelect.value, 10) : 0;
            const allRows = document.querySelectorAll('#itemRows .item-row');
            const targetRow = allRows[specificIdx];

            if (targetRow) {
                appliedCount = fillSerialsIntoRow(targetRow, currentParsedSnData.raw_items || []);
            }
        } else {
            // Auto theo Part Number
            const byPart = currentParsedSnData.by_part || {};
            const unassigned = currentParsedSnData.unassigned || [];
            const rows = Array.from(document.querySelectorAll('#itemRows .item-row'));

            const rowMap = new Map();
            rows.forEach(r => {
                const pn = r.querySelector('input[name*="[part_number]"]')?.value.trim().toUpperCase() || '';
                if (pn) {
                    if (!rowMap.has(pn)) rowMap.set(pn, []);
                    rowMap.get(pn).push(r);
                }
            });

            Object.keys(byPart).forEach(pn => {
                const items = byPart[pn];
                if (rowMap.has(pn) && rowMap.get(pn).length > 0) {
                    const row = rowMap.get(pn)[0];
                    appliedCount += fillSerialsIntoRow(row, items);
                } else {
                    let emptyRow = rows.find(r => !r.querySelector('input[name*="[part_number]"]')?.value.trim());
                    if (emptyRow) {
                        const pnInput = emptyRow.querySelector('input[name*="[part_number]"]');
                        if (pnInput) pnInput.value = pn;
                        appliedCount += fillSerialsIntoRow(emptyRow, items);
                    } else {
                        addRow();
                        const newRow = document.querySelector('#itemRows .item-row:last-child');
                        if (newRow) {
                            const pnInput = newRow.querySelector('input[name*="[part_number]"]');
                            if (pnInput) pnInput.value = pn;
                            appliedCount += fillSerialsIntoRow(newRow, items);
                        }
                    }
                }
            });

            if (unassigned.length > 0) {
                const firstRow = document.querySelector('#itemRows .item-row');
                if (firstRow) {
                    appliedCount += fillSerialsIntoRow(firstRow, unassigned);
                }
            }
        }

        closeImportSnModal();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Đã nạp số S/N!',
                text: `Đã nạp thành công ${appliedCount} số S/N vào bảng chi tiết đặt hàng.`,
                icon: 'success',
                timer: 2500,
                showConfirmButton: false
            });
        } else {
            alert(`Đã nạp thành công ${appliedCount} số S/N vào bảng chi tiết đặt hàng.`);
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const dropzone = document.getElementById('snDropzone');
        if (dropzone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('border-emerald-600', 'bg-emerald-50');
                }, false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('border-emerald-600', 'bg-emerald-50');
                }, false);
            });
            dropzone.addEventListener('drop', e => {
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files[0]) {
                    const fileInp = document.getElementById('snFileInput');
                    if (fileInp) {
                        fileInp.files = dt.files;
                        handleSnFileChosen(fileInp);
                    }
                }
            }, false);
        }
    });

    window.toggleOtherDistributorInput = function(cb) {
        const wrapper = document.getElementById('other_distributor_wrapper');
        const input = document.getElementById('other_distributor_name');
        if (cb && cb.checked) {
            wrapper?.classList.remove('hidden');
            input?.focus();
        } else {
            wrapper?.classList.add('hidden');
        }
    };
</script>
@endpush
@endsection
