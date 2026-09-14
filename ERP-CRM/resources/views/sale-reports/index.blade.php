@extends('layouts.app')

@section('title', 'Báo cáo bán hàng')
@section('page-title', 'Báo cáo bán hàng')

@section('content')
    <div class="space-y-4">
        <!-- Header Actions -->
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>Phân tích chi tiết hoạt động bán hàng: doanh thu, lợi nhuận, hiệu quả
            </div>
            <div class="flex gap-2">
                <button onclick="window.location.reload()"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                    <i class="fas fa-sync mr-2"></i>Làm mới
                </button>
                <a href="{{ route('sale-reports.export', request()->query()) }}"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded-md hover:bg-green-600">
                    <i class="fas fa-file-export mr-2"></i>Xuất Excel
                </a>
                <button onclick="window.print()"
                    class="inline-flex hidden items-center px-3 py-1.5 text-sm bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    <i class="fas fa-print mr-2"></i>In
                </button>
            </div>
        </div>

        <!-- Filters -->
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-xs p-4 border border-gray-200">
            <form method="GET" id="saleReportFilterForm" class="space-y-3">
                <input type="hidden" name="tab" id="activeTabInput" value="{{ request('tab', 'margin') }}">
                
                <!-- Row 1: Date range, Search, Customer, Vendor -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="far fa-calendar-alt text-gray-400 mr-1"></i>Từ ngày
                        </label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="far fa-calendar-alt text-gray-400 mr-1"></i>Đến ngày
                        </label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-search text-gray-400 mr-1"></i>Tìm kiếm chung
                        </label>
                        <input type="text" name="search" value="{{ $search ?? '' }}"
                            placeholder="Mã SO, Số HĐ, Tên KH..."
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-building text-gray-400 mr-1"></i>Khách hàng
                        </label>
                        <select name="customer_id"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Tất cả khách hàng</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-tag text-gray-400 mr-1"></i>Hãng / Vendor
                        </label>
                        <select name="vendor_id"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Tất cả hãng</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ ($vendorId ?? '') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 2: Product, Product Type, Salesperson, Margin % Range -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3 pt-1 border-t border-gray-100">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-box text-gray-400 mr-1"></i>Mã sản phẩm chính
                        </label>
                        <div class="relative searchable-select" id="productSearchContainer" data-ajax-url="{{ route('api.products.search') }}">
                            <input type="text" id="productSearchInput" 
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Chọn hoặc tìm mã SP..." autocomplete="off"
                                value="{{ $selectedProduct ? $selectedProduct->code : '' }}">
                            <input type="hidden" name="product_id" id="productIdHidden" value="{{ $productId }}">
                            
                            <div id="productSearchDropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <!-- Results will be loaded here -->
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-layer-group text-gray-400 mr-1"></i>Loại hàng
                        </label>
                        <select name="product_type"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Tất cả loại hàng</option>
                            <option value="HW" {{ ($productType ?? '') === 'HW' ? 'selected' : '' }}>HW (Phần cứng)</option>
                            <option value="License" {{ ($productType ?? '') === 'License' ? 'selected' : '' }}>License (Bản quyền)</option>
                            <option value="Service" {{ ($productType ?? '') === 'Service' ? 'selected' : '' }}>Service (Dịch vụ)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-user-tie text-gray-400 mr-1"></i>NV Kinh doanh
                        </label>
                        <select name="user_id"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Tất cả NV</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ ($userId ?? '') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-percentage text-gray-400 mr-1"></i>Tỷ lệ Margin từ (%)
                        </label>
                        <input type="number" name="margin_percent_min" step="0.1"
                            value="{{ $marginPercentMin ?? '' }}" placeholder="VD: 5"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-percentage text-gray-400 mr-1"></i>Tỷ lệ Margin đến (%)
                        </label>
                        <input type="number" name="margin_percent_max" step="0.1"
                            value="{{ $marginPercentMax ?? '' }}" placeholder="VD: 50"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>

                @php
                    $activeFinanceFiltersCount = 0;
                    if (!empty($revenueMin) || !empty($revenueMax)) $activeFinanceFiltersCount++;
                    if (!empty($totalMin) || !empty($totalMax)) $activeFinanceFiltersCount++;
                    if (!empty($costMin) || !empty($costMax)) $activeFinanceFiltersCount++;
                    if (!empty($marginMin) || !empty($marginMax)) $activeFinanceFiltersCount++;
                    if (!empty($hasVat) && $hasVat !== 'all') $activeFinanceFiltersCount++;
                    if (!empty($hasImplementationCost) && $hasImplementationCost !== 'all') $activeFinanceFiltersCount++;
                    if (!empty($hasContractorTax) && $hasContractorTax !== 'all') $activeFinanceFiltersCount++;
                    if (!empty($hasFinanceCost) && $hasFinanceCost !== 'all') $activeFinanceFiltersCount++;
                    if (!empty($hasManagementCost) && $hasManagementCost !== 'all') $activeFinanceFiltersCount++;
                    if (!empty($hasSupport247) && $hasSupport247 !== 'all') $activeFinanceFiltersCount++;
                    if (!empty($hasOtherSupport) && $hasOtherSupport !== 'all') $activeFinanceFiltersCount++;
                    $isFinanceFilterOpen = $activeFinanceFiltersCount > 0;
                @endphp

                <!-- Row 3: Payment status, Payment % Range, Advanced Filter Toggle, Action Buttons -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3 pt-1 border-t border-gray-100 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-money-check-alt text-gray-400 mr-1"></i>Thanh toán
                        </label>
                        <select name="payment_state" class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">Tất cả trạng thái TT</option>
                            <option value="unpaid" {{ $paymentState === 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
                            <option value="partial" {{ $paymentState === 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                            <option value="paid" {{ $paymentState === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tỷ lệ TT từ (%)</label>
                        <input type="number" name="payment_percent_min" min="0" max="100" step="0.01"
                            value="{{ $paymentPercentMin }}" placeholder="0"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tỷ lệ TT đến (%)</label>
                        <input type="number" name="payment_percent_max" min="0" max="100" step="0.01"
                            value="{{ $paymentPercentMax }}" placeholder="100"
                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Lọc cột tài chính / chi phí</label>
                        <button type="button" id="toggleFinancialFiltersBtn"
                            class="w-full px-3 py-1.5 text-xs border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-semibold flex items-center justify-between transition-colors shadow-2xs">
                            <span class="flex items-center gap-1.5">
                                <i class="fas fa-coins text-blue-600"></i>
                                <span>Bộ lọc tài chính</span>
                            </span>
                            @if($activeFinanceFiltersCount > 0)
                                <span class="bg-blue-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $activeFinanceFiltersCount }}</span>
                            @else
                                <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" id="financialFilterChevron"></i>
                            @endif
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1.5">
                            <i class="fas fa-filter"></i> Áp dụng
                        </button>
                        <a href="{{ route('sale-reports.index', ['tab' => request('tab', 'margin')]) }}"
                            class="px-3 py-2 bg-gray-100 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-200 text-xs font-semibold transition-colors flex items-center justify-center gap-1" title="Xóa tất cả bộ lọc">
                            <i class="fas fa-redo text-gray-400"></i> Xóa
                        </a>
                    </div>
                </div>

                <!-- Collapsible Financial & Cost Filters Section -->
                <div id="financialFiltersPanel" class="{{ $isFinanceFilterOpen ? '' : 'hidden' }} mt-3 pt-3 border-t-2 border-blue-100 bg-gradient-to-r from-blue-50/40 via-white to-indigo-50/30 p-3 rounded-xl border space-y-3">
                    <div class="flex items-center justify-between pb-1 border-b border-blue-100">
                        <div class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                            <i class="fas fa-sliders-h text-blue-600"></i>
                            <span>BỘ LỌC CHI TIẾT TÀI CHÍNH & CÁC KHOẢN CHI PHÍ / THUẾ</span>
                        </div>
                        <span class="text-[11px] text-gray-500 italic">Áp dụng chính xác cho Báo cáo Margin và Xuất Excel</span>
                    </div>

                    <!-- Financial Amounts Ranges (Row 1) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Tiền hàng (chưa gồm VAT) -->
                        <div class="bg-white p-2.5 rounded-lg border border-gray-200 shadow-2xs">
                            <label class="block text-xs font-bold text-gray-800 mb-1">
                                <i class="fas fa-money-bill-wave text-green-600 mr-1"></i>Tiền hàng (chưa VAT)
                            </label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <input type="number" name="revenue_min" value="{{ $revenueMin ?? '' }}" placeholder="Từ (VNĐ)"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary">
                                <input type="number" name="revenue_max" value="{{ $revenueMax ?? '' }}" placeholder="Đến (VNĐ)"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary">
                            </div>
                        </div>

                        <!-- Tổng tiền (gồm VAT) -->
                        <div class="bg-white p-2.5 rounded-lg border border-gray-200 shadow-2xs">
                            <label class="block text-xs font-bold text-gray-800 mb-1">
                                <i class="fas fa-file-invoice-dollar text-blue-600 mr-1"></i>Tổng tiền (gồm VAT)
                            </label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <input type="number" name="total_min" value="{{ $totalMin ?? '' }}" placeholder="Từ (VNĐ)"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary">
                                <input type="number" name="total_max" value="{{ $totalMax ?? '' }}" placeholder="Đến (VNĐ)"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary">
                            </div>
                        </div>

                        <!-- Giá vốn hàng hóa -->
                        <div class="bg-white p-2.5 rounded-lg border border-gray-200 shadow-2xs">
                            <label class="block text-xs font-bold text-gray-800 mb-1">
                                <i class="fas fa-boxes text-amber-600 mr-1"></i>Giá vốn hàng hóa
                            </label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <input type="number" name="cost_min" value="{{ $costMin ?? '' }}" placeholder="Từ (VNĐ)"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary">
                                <input type="number" name="cost_max" value="{{ $costMax ?? '' }}" placeholder="Đến (VNĐ)"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary">
                            </div>
                        </div>

                        <!-- Tiền Margin VNĐ -->
                        <div class="bg-white p-2.5 rounded-lg border border-gray-200 shadow-2xs">
                            <label class="block text-xs font-bold text-gray-800 mb-1">
                                <i class="fas fa-chart-line text-purple-600 mr-1"></i>Lợi nhuận Margin (VNĐ)
                            </label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <input type="number" name="margin_min" value="{{ $marginMin ?? '' }}" placeholder="Từ (VNĐ)"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary">
                                <input type="number" name="margin_max" value="{{ $marginMax ?? '' }}" placeholder="Đến (VNĐ)"
                                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-primary">
                            </div>
                        </div>
                    </div>

                    <!-- Cost & Tax Status Filters (Row 2) -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-2.5 pt-1">
                        <!-- Tiền thuế VAT -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-700 mb-1 truncate" title="Tiền thuế (VAT)">
                                <i class="fas fa-receipt text-gray-400 mr-0.5"></i>Thuế VAT
                            </label>
                            <select name="has_vat" class="w-full px-2 py-1 text-xs border border-gray-300 rounded bg-white focus:ring-1 focus:ring-primary">
                                <option value="all" {{ ($hasVat ?? 'all') === 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="yes" {{ ($hasVat ?? '') === 'yes' ? 'selected' : '' }}>Có VAT (>0)</option>
                                <option value="no" {{ ($hasVat ?? '') === 'no' ? 'selected' : '' }}>Không VAT (=0)</option>
                            </select>
                        </div>

                        <!-- Chi phí triển khai HĐ -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-700 mb-1 truncate" title="Chi phí triển khai HĐ (Tiếp khách, cài đặt, v.v)">
                                <i class="fas fa-tools text-gray-400 mr-0.5"></i>CP triển khai HĐ
                            </label>
                            <select name="has_implementation_cost" class="w-full px-2 py-1 text-xs border border-gray-300 rounded bg-white focus:ring-1 focus:ring-primary">
                                <option value="all" {{ ($hasImplementationCost ?? 'all') === 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="yes" {{ ($hasImplementationCost ?? '') === 'yes' ? 'selected' : '' }}>Có phát sinh (>0)</option>
                                <option value="no" {{ ($hasImplementationCost ?? '') === 'no' ? 'selected' : '' }}>Không phát sinh (=0)</option>
                            </select>
                        </div>

                        <!-- Thuế nhà thầu -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-700 mb-1 truncate" title="Thuế nhà thầu">
                                <i class="fas fa-landmark text-gray-400 mr-0.5"></i>Thuế nhà thầu
                            </label>
                            <select name="has_contractor_tax" class="w-full px-2 py-1 text-xs border border-gray-300 rounded bg-white focus:ring-1 focus:ring-primary">
                                <option value="all" {{ ($hasContractorTax ?? 'all') === 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="yes" {{ ($hasContractorTax ?? '') === 'yes' ? 'selected' : '' }}>Có thuế NT (>0)</option>
                                <option value="no" {{ ($hasContractorTax ?? '') === 'no' ? 'selected' : '' }}>Không phát sinh (=0)</option>
                            </select>
                        </div>

                        <!-- Chi phí tài chính 1% -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-700 mb-1 truncate" title="Chi phí tài chính 1%">
                                <i class="fas fa-hand-holding-usd text-gray-400 mr-0.5"></i>CP tài chính 1%
                            </label>
                            <select name="has_finance_cost" class="w-full px-2 py-1 text-xs border border-gray-300 rounded bg-white focus:ring-1 focus:ring-primary">
                                <option value="all" {{ ($hasFinanceCost ?? 'all') === 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="yes" {{ ($hasFinanceCost ?? '') === 'yes' ? 'selected' : '' }}>Có phát sinh (>0)</option>
                                <option value="no" {{ ($hasFinanceCost ?? '') === 'no' ? 'selected' : '' }}>Không phát sinh (=0)</option>
                            </select>
                        </div>

                        <!-- Chi phí Quản lí, Back Office & kỹ thuật -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-700 mb-1 truncate" title="Chi phí Quản lí, Back Office & kỹ thuật">
                                <i class="fas fa-user-shield text-gray-400 mr-0.5"></i>CP Quản lý & BO
                            </label>
                            <select name="has_management_cost" class="w-full px-2 py-1 text-xs border border-gray-300 rounded bg-white focus:ring-1 focus:ring-primary">
                                <option value="all" {{ ($hasManagementCost ?? 'all') === 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="yes" {{ ($hasManagementCost ?? '') === 'yes' ? 'selected' : '' }}>Có phát sinh (>0)</option>
                                <option value="no" {{ ($hasManagementCost ?? '') === 'no' ? 'selected' : '' }}>Không phát sinh (=0)</option>
                            </select>
                        </div>

                        <!-- 24x7 (0.5%) -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-700 mb-1 truncate" title="24x7 (0.5%)">
                                <i class="fas fa-headset text-gray-400 mr-0.5"></i>24x7 (0.5%)
                            </label>
                            <select name="has_support_247" class="w-full px-2 py-1 text-xs border border-gray-300 rounded bg-white focus:ring-1 focus:ring-primary">
                                <option value="all" {{ ($hasSupport247 ?? 'all') === 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="yes" {{ ($hasSupport247 ?? '') === 'yes' ? 'selected' : '' }}>Có phát sinh (>0)</option>
                                <option value="no" {{ ($hasSupport247 ?? '') === 'no' ? 'selected' : '' }}>Không phát sinh (=0)</option>
                            </select>
                        </div>

                        <!-- Other Support (Zyxel) -->
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-700 mb-1 truncate" title="Other Support (Zyxel)">
                                <i class="fas fa-life-ring text-gray-400 mr-0.5"></i>Other Support
                            </label>
                            <select name="has_other_support" class="w-full px-2 py-1 text-xs border border-gray-300 rounded bg-white focus:ring-1 focus:ring-primary">
                                <option value="all" {{ ($hasOtherSupport ?? 'all') === 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="yes" {{ ($hasOtherSupport ?? '') === 'yes' ? 'selected' : '' }}>Có phát sinh (>0)</option>
                                <option value="no" {{ ($hasOtherSupport ?? '') === 'no' ? 'selected' : '' }}>Không phát sinh (=0)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-primary text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Doanh thu</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_revenue']) }}đ</p>
                        <p class="text-xs opacity-70">{{ number_format($stats['total_orders']) }} đơn hàng</p>
                    </div>
                    <i class="fas fa-coins text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-green-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Lợi nhuận gộp</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_profit']) }}đ</p>
                    </div>
                    <i class="fas fa-chart-line text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-blue-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Tỷ suất lợi nhuận</p>
                        <p class="text-2xl font-bold">{{ $stats['margin_percent'] }}%</p>
                    </div>
                    <i class="fas fa-percentage text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-red-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Tổng chi phí</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_cost']) }}đ</p>
                        <p class="text-xs opacity-70">Giá vốn + CP bán hàng</p>
                    </div>
                    <i class="fas fa-wallet text-3xl opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Report Tabs -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px" id="reportTabs">
                    <button type="button"
                        class="tab-btn active px-4 py-3 text-sm font-medium border-b-2 border-primary text-primary"
                        data-tab="customer">
                        <i class="fas fa-users mr-1"></i>Theo Khách hàng
                    </button>
                    <button type="button"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="product">
                        <i class="fas fa-box mr-1"></i>Theo Sản phẩm
                    </button>
                    <button type="button"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="margin">
                        <i class="fas fa-file-invoice-dollar mr-1"></i>Báo cáo Margin
                    </button>
                </nav>
            </div>

            <!-- Customer Report -->
            <div class="tab-content p-4" id="tab-customer">
                <h3 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-users mr-2 text-primary"></i>Hiệu quả kinh doanh theo khách hàng</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Khách hàng</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Số đơn</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Doanh thu</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Lợi nhuận</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Tỷ suất LN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($customerReport as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $row['customer'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $row['order_count'] }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-primary">{{ number_format($row['total_revenue']) }}đ</td>
                                    <td class="px-3 py-2 text-right text-green-600 font-medium">
                                        {{ number_format($row['total_profit']) }}đ
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium {{ $row['margin_percent'] >= 15 ? 'bg-green-100 text-green-800' : ($row['margin_percent'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }} rounded-full">
                                            {{ $row['margin_percent'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-gray-500">Không có dữ liệu</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Product Report -->
            <div class="tab-content p-4 hidden" id="tab-product">
                <h3 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-box mr-2 text-primary"></i>Hiệu quả kinh doanh theo sản phẩm</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Mã sản phẩm</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Số lượng bán</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Doanh thu</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Lợi nhuận gộp</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Tỷ suất LN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                             @forelse($productReport as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium" title="{{ $row['product_name'] }}">{{ $row['product_code'] ?: 'N/A' }}</td>
                                    <td class="px-3 py-2 text-center">{{ number_format($row['total_quantity']) }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-primary">{{ number_format($row['total_revenue']) }}đ</td>
                                    <td class="px-3 py-2 text-right text-green-600 font-medium">
                                        {{ number_format($row['total_profit']) }}đ
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-block px-2 py-0.5 text-xs font-medium {{ $row['margin_percent'] >= 15 ? 'bg-green-100 text-green-800' : ($row['margin_percent'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }} rounded-full">
                                            {{ $row['margin_percent'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-gray-500">Không có dữ liệu</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>



            <!-- Margin Report -->
            <div class="tab-content p-4 hidden" id="tab-margin">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800">
                        <i class="fas fa-file-invoice-dollar mr-2 text-primary"></i>
                        Báo cáo Lãi/Lỗ (Margin) theo đơn hàng
                        <span class="text-sm font-normal text-gray-500">
                            (Từ {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }})
                        </span>
                    </h3>
                    <a href="{{ route('sale-reports.export-margin', request()->query()) }}"
                        class="inline-flex items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded-md hover:bg-green-600">
                        <i class="fas fa-file-excel mr-2"></i>Xuất Excel Margin
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse min-w-[2200px]">
                        <thead>
                            <tr class="text-xs text-center">
                                <!-- Dark blue headers (Left group) -->
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] w-10">STT</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[180px]">Tên khách hàng</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[140px]">Số Hóa đơn tài chính<br/><span class="text-[10px] font-normal opacity-80">(hoặc Số đơn hàng khởi tạo theo PM)</span></th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[100px]">Ngày xuất<br/>hóa đơn</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[90px]">HÃNG</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] w-16">License</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[140px]">Loại hàng</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[110px]">Mã hàng<br/>hóa chính</th>

                                <!-- Light blue headers (Red-box Financial columns) -->
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[125px]">Tiền hàng<br/>(chưa gồm VAT)</th>
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[110px]">Tiền thuế<br/>(VAT)</th>
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[125px]">Tổng tiền<br/>(gồm VAT)</th>
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[120px]">Giá vốn<br/>hàng hóa</th>
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[160px]">Chi phí triển khai HĐ<br/><span class="text-[10px] font-normal opacity-90">(Tiếp khách, cài đặt, v.v)</span></th>
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[105px]">Thuế<br/>nhà thầu</th>
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[110px]">Chi phí<br/>tài chính 1%</th>
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[140px]">Chi phí Quản lí,<br/>Back Office & kỹ thuật</th>
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[100px]">24x7<br/>(0.5%)</th>
                                <th class="px-2 py-3 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[110px]">Other Support<br/>(Zyxel)</th>

                                <!-- Dark blue headers (Right group) -->
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[110px]">Margin</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] w-20">Margin %</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[130px]">NV<br/>Kinh doanh</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[130px]">Tổng Tiền KH<br/>đã thanh toán</th>
                                <th class="px-2 py-3 bg-[#1a3a5c] text-white border border-[#0d2a4a] w-24">Tỷ lệ KH đã<br/>thanh toán (%)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($marginReport as $row)
                                <tr class="hover:bg-blue-50/70 transition-colors text-xs">
                                    <td class="px-2 py-2 text-center border border-gray-200">{{ $row['stt'] }}</td>
                                    <td class="px-2 py-2 border border-gray-200 font-medium">{{ $row['customer_name'] }}</td>
                                    <td class="px-2 py-2 text-center border border-gray-200">
                                        <a href="{{ route('sales.show', $row['sale_id']) }}" class="text-primary font-semibold hover:underline" title="Xem chi tiết đơn hàng">
                                            {{ $row['invoice_number'] }}
                                        </a>
                                    </td>
                                    <td class="px-2 py-2 text-center border border-gray-200">{{ $row['invoice_date'] }}</td>
                                    <td class="px-2 py-2 text-center border border-gray-200">{{ $row['brand'] ?: '-' }}</td>
                                    <td class="px-2 py-2 text-center border border-gray-200">{{ $row['license'] ?: '-' }}</td>
                                    <td class="px-2 py-2 text-center border border-gray-200">{{ $row['product_type'] ?: '-' }}</td>
                                    <td class="px-2 py-2 text-center border border-gray-200 font-mono text-xs">{{ $row['main_product_code'] ?: '-' }}</td>

                                    <!-- Red box financial cells -->
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono bg-blue-50/30">
                                        {{ number_format($row['revenue_before_vat']) }}
                                    </td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono bg-blue-50/30">
                                        {{ $row['vat_amount'] > 0 ? number_format($row['vat_amount']) : '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono font-medium text-gray-900 bg-blue-50/30">
                                        {{ number_format($row['total_amount_incl_vat']) }}
                                    </td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono bg-blue-50/30">
                                        {{ $row['goods_cost'] > 0 ? number_format($row['goods_cost']) : '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono bg-blue-50/30">
                                        {{ $row['implementation_cost'] > 0 ? number_format($row['implementation_cost']) : '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono bg-blue-50/30">
                                        {{ $row['contractor_tax'] > 0 ? number_format($row['contractor_tax']) : '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono bg-blue-50/30">
                                        {{ $row['finance_cost'] > 0 ? number_format($row['finance_cost']) : '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono bg-blue-50/30">
                                        {{ $row['management_cost'] > 0 ? number_format($row['management_cost']) : '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono bg-blue-50/30">
                                        {{ $row['support_247_cost'] > 0 ? number_format($row['support_247_cost']) : '-' }}
                                    </td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono bg-blue-50/30">
                                        {{ $row['other_support_cost'] > 0 ? number_format($row['other_support_cost']) : '-' }}
                                    </td>

                                    <!-- Margin & Salesperson -->
                                    <td class="px-2 py-2 text-right border border-gray-200 font-semibold font-mono {{ $row['margin'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                        {{ number_format($row['margin']) }}
                                    </td>
                                    <td class="px-2 py-2 text-center border border-gray-200">
                                        <span class="inline-block px-1.5 py-0.5 text-xs font-medium rounded-full
                                            {{ $row['margin_percent'] >= 15 ? 'bg-green-100 text-green-800' : ($row['margin_percent'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $row['margin_percent'] }}%
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 border border-gray-200">{{ $row['salesperson'] }}</td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-mono">
                                        @if($row['paid_amount'] > 0)
                                            {{ number_format($row['paid_amount']) }}
                                        @else
                                            <span class="text-gray-400">Chưa TT</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-center border border-gray-200">
                                        <span class="inline-block px-1.5 py-0.5 text-xs font-medium rounded-full
                                            {{ $row['payment_percent'] >= 100 ? 'bg-green-100 text-green-800' : ($row['payment_percent'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $row['payment_percent'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="23" class="px-3 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                                        <p>Không có dữ liệu trong khoảng thời gian này</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($marginReport) > 0)
                        <tfoot>
                            <tr class="bg-gray-100 font-bold text-xs">
                                <td colspan="8" class="px-2 py-2.5 text-right border border-gray-300 uppercase text-gray-700">TỔNG CỘNG</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">{{ number_format(collect($marginReport)->sum('revenue_before_vat')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">{{ number_format(collect($marginReport)->sum('vat_amount')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono text-gray-900">{{ number_format(collect($marginReport)->sum('total_amount_incl_vat')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">{{ number_format(collect($marginReport)->sum('goods_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">{{ number_format(collect($marginReport)->sum('implementation_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">{{ number_format(collect($marginReport)->sum('contractor_tax')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">{{ number_format(collect($marginReport)->sum('finance_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">{{ number_format(collect($marginReport)->sum('management_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">{{ number_format(collect($marginReport)->sum('support_247_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">{{ number_format(collect($marginReport)->sum('other_support_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono {{ collect($marginReport)->sum('margin') >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ number_format(collect($marginReport)->sum('margin')) }}
                                </td>
                                <td class="px-2 py-2.5 text-center border border-gray-300">
                                    @php
                                        $totalNet = collect($marginReport)->sum('revenue_before_vat');
                                        $totalMarginSum = collect($marginReport)->sum('margin');
                                        $avgMarginPercent = $totalNet > 0 ? round(($totalMarginSum / $totalNet) * 100, 1) : 0;
                                    @endphp
                                    <span class="inline-block px-1.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 text-blue-800">
                                        {{ $avgMarginPercent }}%
                                    </span>
                                </td>
                                <td class="px-2 py-2.5 border border-gray-300"></td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono">
                                    {{ number_format(collect($marginReport)->sum('paid_amount')) }}
                                </td>
                                <td class="px-2 py-2.5 text-center border border-gray-300"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            function switchTab(tabId) {
                // Update buttons
                tabBtns.forEach(b => {
                    if (b.dataset.tab === tabId) {
                        b.classList.add('active', 'border-primary', 'text-primary');
                        b.classList.remove('border-transparent', 'text-gray-500');
                    } else {
                        b.classList.remove('active', 'border-primary', 'text-primary');
                        b.classList.add('border-transparent', 'text-gray-500');
                    }
                });

                // Update content
                tabContents.forEach(c => {
                    if (c.id === 'tab-' + tabId) {
                        c.classList.remove('hidden');
                    } else {
                        c.classList.add('hidden');
                    }
                });

                // Update hidden input for filter form
                const tabInput = document.getElementById('activeTabInput');
                if (tabInput) {
                    tabInput.value = tabId;
                }
            }

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    switchTab(this.dataset.tab);
                });
            });

            // Financial & Cost Filters Toggle
            const toggleFinancialFiltersBtn = document.getElementById('toggleFinancialFiltersBtn');
            const financialFiltersPanel = document.getElementById('financialFiltersPanel');
            const financialFilterChevron = document.getElementById('financialFilterChevron');
            if (toggleFinancialFiltersBtn && financialFiltersPanel) {
                toggleFinancialFiltersBtn.addEventListener('click', function () {
                    const isHidden = financialFiltersPanel.classList.toggle('hidden');
                    if (financialFilterChevron) {
                        financialFilterChevron.classList.toggle('rotate-180', !isHidden);
                    }
                });
            }

            // Product Search Filter Logic
            const productSearchContainer = document.getElementById('productSearchContainer');
            const productSearchInput = document.getElementById('productSearchInput');
            const productSearchDropdown = document.getElementById('productSearchDropdown');
            const productIdHidden = document.getElementById('productIdHidden');
            const ajaxUrl = productSearchContainer.dataset.ajaxUrl;
            let debounceTimer;
            let currentPage = 1;
            let isFetching = false;
            let hasMore = true;

            async function fetchProducts(query = '', page = 1, append = false) {
                if (isFetching || (!hasMore && page > 1)) return;
                
                isFetching = true;
                if (!append) {
                    productSearchDropdown.innerHTML = '<div class="px-3 py-2 text-xs text-gray-500 italic">Đang tải...</div>';
                    currentPage = 1;
                    hasMore = true;
                } else {
                    const loader = document.createElement('div');
                    loader.id = 'dropdown-loader';
                    loader.className = 'px-3 py-2 text-xs text-gray-500 italic';
                    loader.textContent = 'Đang tải thêm...';
                    productSearchDropdown.appendChild(loader);
                }
                
                productSearchDropdown.classList.remove('hidden');

                try {
                    const response = await fetch(`${ajaxUrl}?q=${encodeURIComponent(query)}&page=${page}`);
                    const products = await response.json();
                    
                    document.getElementById('dropdown-loader')?.remove();
                    
                    if (products.length === 0) {
                        hasMore = false;
                        if (!append) {
                            renderProducts([], false);
                        }
                    } else {
                        renderProducts(products, append);
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    productSearchDropdown.innerHTML = '<div class="px-3 py-2 text-xs text-red-500">Lỗi khi tải sản phẩm</div>';
                } finally {
                    isFetching = false;
                }
            }

            function renderProducts(products, append = false) {
                if (!append) {
                    productSearchDropdown.innerHTML = '';
                    // Add "Tất cả sản phẩm" option only on first load
                    const allOpt = document.createElement('div');
                    allOpt.className = 'px-3 py-2 text-xs hover:bg-blue-50 cursor-pointer border-b border-gray-100 font-bold text-primary';
                    allOpt.textContent = '-- Tất cả sản phẩm --';
                    allOpt.onclick = () => selectProduct('', 'Tất cả sản phẩm');
                    productSearchDropdown.appendChild(allOpt);
                }

                if (products.length === 0 && !append) {
                    const noResult = document.createElement('div');
                    noResult.className = 'px-3 py-2 text-xs text-gray-500 italic';
                    noResult.textContent = 'Không tìm thấy sản phẩm';
                    productSearchDropdown.appendChild(noResult);
                    return;
                }

                products.forEach(p => {
                    const opt = document.createElement('div');
                    opt.className = 'px-3 py-2 text-xs hover:bg-blue-50 cursor-pointer border-b border-gray-100';
                    // User requested: only show product code, no name
                    opt.innerHTML = `<span class="font-bold">${p.code}</span>`;
                    opt.title = p.name; // Keep name as title for hover
                    opt.onclick = () => selectProduct(p.id, p.code);
                    productSearchDropdown.appendChild(opt);
                });
            }

            function selectProduct(id, code) {
                productIdHidden.value = id;
                productSearchInput.value = id ? code : '';
                productSearchDropdown.classList.add('hidden');
            }

            productSearchInput.addEventListener('focus', () => {
                if (productSearchDropdown.classList.contains('hidden')) {
                    fetchProducts(productSearchInput.value, 1, false);
                }
            });

            productSearchInput.addEventListener('input', (e) => {
                const query = e.target.value;
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    fetchProducts(query, 1, false);
                }, 300);
            });

            // Infinite Scroll Listener
            productSearchDropdown.addEventListener('scroll', () => {
                const { scrollTop, scrollHeight, clientHeight } = productSearchDropdown;
                if (scrollTop + clientHeight >= scrollHeight - 10) {
                    if (hasMore && !isFetching) {
                        currentPage++;
                        fetchProducts(productSearchInput.value, currentPage, true);
                    }
                }
            });

            document.addEventListener('click', (e) => {
                if (!productSearchContainer.contains(e.target)) {
                    productSearchDropdown.classList.add('hidden');
                }
            });

            // Handle initial tab from URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab && document.getElementById('tab-' + activeTab)) {
                switchTab(activeTab);
            }
        });
    </script>
@endsection
