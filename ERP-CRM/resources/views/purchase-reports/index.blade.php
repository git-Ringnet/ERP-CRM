@extends('layouts.app')

@section('title', 'Báo cáo mua hàng nâng cao')
@section('page-title', 'Báo cáo mua hàng nâng cao')

@section('content')
    <div class="space-y-4">
        <!-- Header Actions -->
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>Phân tích chi tiết hoạt động mua hàng: theo NCC, sản phẩm, thời gian
            </div>
            <div class="flex gap-2">
                <a href="{{ route('purchase-reports.index') }}"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                    <i class="fas fa-sync mr-2"></i>Làm mới
                </a>
                <a href="{{ route('purchase-reports.export', ['report_type' => 'tracking'] + request()->all()) }}"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded-md hover:bg-green-600">
                    <i class="fas fa-file-export mr-2"></i>Xuất Excel
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nhà cung cấp</label>
                    <select name="supplier_id" onchange="this.form.submit()"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Tất cả NCC</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div x-data="productSearchHandler()" class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm (Part# hoặc Tên)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-xs"></i>
                        </div>
                        <input type="text" 
                            name="product_search"
                            x-model="searchQuery" 
                            @input.debounce.300ms="performSearch()"
                            @focus="showDropdown = true"
                            placeholder="Nhập Part# hoặc tên sp..."
                            class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        
                        <input type="hidden" name="product_id" x-model="selectedProductId">
                        
                        <!-- Clear button -->
                        <button type="button" x-show="selectedProductId || searchQuery" @click="clearSelection()" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>

                    <!-- Search Results Dropdown -->
                    <div x-show="showDropdown && searchResults.length > 0" 
                        @click.away="showDropdown = false"
                        class="absolute z-[100] w-full mt-1 bg-white border border-gray-200 rounded-md shadow-xl max-h-64 overflow-y-auto"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100">
                        
                        <template x-for="product in searchResults" :key="product.id">
                            <div @click="selectProduct(product)" 
                                class="px-3 py-2 hover:bg-indigo-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                <div class="flex flex-col">
                                    <span class="font-bold text-indigo-700 text-xs" x-text="product.code"></span>
                                    <span class="text-gray-800 text-[11px] leading-tight truncate" x-text="product.name"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Loading Indicator -->
                    <div x-show="isSearching" class="absolute right-10 top-9">
                        <i class="fas fa-spinner fa-spin text-primary text-xs"></i>
                    </div>
                </div>

                <script>
                    function productSearchHandler() {
                        return {
                            searchQuery: '{{ $products->first() ? $products->first()->code . " - " . Str::limit($products->first()->name, 30) : ($productSearch ?? "") }}',
                            selectedProductId: '{{ $productId }}',
                            searchResults: [],
                            showDropdown: false,
                            isSearching: false,
                            
                            init() {
                                this.$watch('searchQuery', value => {
                                    if (!value.includes(' - ')) {
                                        this.selectedProductId = '';
                                    }
                                });
                            },
                            
                            performSearch() {
                                if (this.searchQuery.length < 2) {
                                    this.searchResults = [];
                                    this.showDropdown = false;
                                    return;
                                }
                                
                                this.isSearching = true;
                                fetch(`/ajax/products/search?q=${encodeURIComponent(this.searchQuery)}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        this.searchResults = data.slice(0, 15); // Limit to 15 results for performance
                                        this.showDropdown = true;
                                        this.isSearching = false;
                                    })
                                    .catch(err => {
                                        console.error('Search error:', err);
                                        this.isSearching = false;
                                    });
                            },
                            
                            selectProduct(product) {
                                this.selectedProductId = product.id;
                                this.searchQuery = product.code + ' - ' + product.name;
                                this.showDropdown = false;
                                // Automatically submit form on selection if you want, 
                                // but here we'll let user click the Filter button
                            },
                            
                            clearSelection() {
                                this.selectedProductId = '';
                                this.searchQuery = '';
                                this.searchResults = [];
                                this.showDropdown = false;
                            }
                        }
                    }
                </script>
                <div class="flex items-end">
                    <button type="submit"
                        class="w-full px-4 py-1.5 bg-primary text-white rounded-md hover:bg-primary-dark text-sm font-medium">
                        <i class="fas fa-filter mr-2"></i>Lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-primary text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Đơn mua hàng</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_orders']) }}</p>
                    </div>
                    <i class="fas fa-shopping-cart text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-green-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Tổng giá trị (USD)</p>
                        <p class="text-2xl font-bold">${{ number_format($stats['total_amount_usd'], 0) }}</p>
                        <p class="text-xs opacity-90 mt-1 font-medium">~ {{ number_format($stats['total_amount'] / 1000000, 1) }}tr VND</p>
                    </div>
                    <i class="fas fa-money-bill-wave text-3xl opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Report Tabs -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px" id="reportTabs">
                    <button type="button"
                        class="tab-btn active px-4 py-3 text-sm font-medium border-b-2 border-primary text-primary"
                        data-tab="tracking">
                        <i class="fas fa-boxes mr-1"></i>Theo dõi hàng về
                    </button>
                    <button type="button"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="supplier">
                        <i class="fas fa-building mr-1"></i>Theo NCC
                    </button>
                    <button type="button"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="product">
                        <i class="fas fa-box mr-1"></i>Theo sản phẩm
                    </button>
                    <button type="button"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="monthly">
                        <i class="fas fa-calendar-alt mr-1"></i>Theo tháng
                    </button>
                </nav>
            </div>

            <!-- Tracking Report (Incoming Goods) -->
            <div class="tab-content p-4" id="tab-tracking">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-base font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-boxes mr-2 text-primary"></i>Theo dõi hàng về – Group theo Sale Order + Sản phẩm
                    </h3>
                    <div class="text-xs text-gray-500">Dữ liệu được tổng hợp từ tất cả PR & PO</div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Sale Order</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Sản phẩm (Part Number)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Giá kho (USD)</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">SL Yêu cầu</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">SL Đã đặt</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">SL Đã về</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Còn thiếu</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Thành tiền (USD)</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Trạng thái</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Đơn mua (PO)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($trackingReport as $row)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 py-3">
                                        <div class="font-bold text-blue-600 mb-1">
                                            <i class="fas fa-file-invoice mr-1"></i>{{ $row['sale_code'] ?? 'N/A' }}
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($row['pr_codes'] ?? [] as $prCode)
                                                <span class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">PR: {{ $prCode }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="font-medium text-gray-900">{{ $row['part_number'] ?? 'N/A' }}</div>
                                        <div class="text-[11px] text-gray-500">{{ $row['vendor_name'] ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-right text-indigo-600 font-medium">
                                        ${{ number_format($row['unit_price_usd'] ?? 0, 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="font-bold text-gray-700">{{ $row['requested'] ?? 0 }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ ($row['ordered'] ?? 0) >= ($row['requested'] ?? 0) ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $row['ordered'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ ($row['received'] ?? 0) >= ($row['requested'] ?? 0) ? 'bg-green-100 text-green-800' : (($row['received'] ?? 0) > 0 ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-400') }}">
                                            {{ $row['received'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ ($row['remaining'] ?? 0) > 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-400' }}">
                                            {{ $row['remaining'] ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right font-bold text-gray-900">
                                        ${{ number_format($row['total_usd'] ?? 0, 2) }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $row['status_color'] ?? 'bg-gray-100' }}">
                                            <i class="{{ $row['status_icon'] ?? 'fas fa-info-circle' }} mr-1"></i> {{ $row['status_label'] ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($row['po_links'] ?? [] as $po)
                                                <a href="{{ route('purchase-orders.show', $po['id']) }}" 
                                                    class="px-1.5 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] hover:bg-indigo-100 border border-indigo-200"
                                                    title="{{ $po['status_label'] ?? '' }}">
                                                    {{ $po['code'] ?? '' }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-3 py-10 text-center text-gray-500">
                                        <i class="fas fa-box-open text-4xl mb-2 opacity-20"></i>
                                        <p>Không có dữ liệu theo dõi hàng về.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Supplier Report -->
            <div class="tab-content p-4 hidden" id="tab-supplier">
                <h3 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-building mr-2 text-primary"></i>Báo
                    cáo theo nhà cung cấp</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Nhà cung cấp</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Số đơn</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Tổng giá trị (USD)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Chiết khấu (USD)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CP vận chuyển (VND)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Thực trả (USD)</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Tỷ lệ CK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($supplierReport as $row)
                                <tr class="hover:bg-gray-50 cursor-pointer supplier-row transition-all duration-150" data-supplier-id="{{ $row['supplier_id'] }}">
                                    <td class="px-3 py-3 font-medium">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 transition-colors duration-200 toggle-btn-wrapper">
                                                <i class="fas fa-chevron-right text-[10px] transition-transform duration-200 toggle-icon" id="icon-{{ $row['supplier_id'] }}"></i>
                                            </span>
                                            <span class="text-gray-900 font-bold hover:text-indigo-600">{{ $row['supplier'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            {{ $row['order_count'] }} đơn
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="font-bold text-indigo-600">${{ number_format($row['total_amount_usd'] ?? 0, 2) }}</div>
                                        <div class="text-[10px] text-gray-400 font-medium">{{ number_format($row['total_amount'], 0, ',', '.') }}đ</div>
                                    </td>
                                    <td class="px-3 py-3 text-right text-green-600">
                                        <div class="font-bold">${{ number_format(($row['total_discount'] / 25000), 2) }}</div> <!-- Approximate USD for discount if not calculated -->
                                        <div class="text-[10px] opacity-70 font-medium">{{ number_format($row['total_discount'], 0, ',', '.') }}đ</div>
                                    </td>
                                    <td class="px-3 py-3 text-right text-gray-700 font-semibold">
                                        {{ number_format($row['total_shipping'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="font-bold text-gray-900">${{ number_format($row['total_paid_usd'] ?? 0, 2) }}</div>
                                        <div class="text-[10px] text-gray-400 font-medium">{{ number_format($row['total_paid'], 0, ',', '.') }}đ</div>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="inline-block px-2.5 py-0.5 text-xs font-bold bg-green-100 text-green-800 rounded-full">{{ $row['discount_rate'] }}%</span>
                                    </td>
                                </tr>
                                <!-- Sub row for PO orders details -->
                                <tr id="orders-{{ $row['supplier_id'] }}" class="hidden bg-gray-50/40">
                                    <td colspan="7" class="px-4 py-3">
                                        <div class="border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm p-4">
                                            <div class="flex justify-between items-center mb-3">
                                                <h4 class="text-xs font-bold text-indigo-800 uppercase tracking-wider flex items-center">
                                                    <i class="fas fa-list-alt mr-2 text-indigo-500 text-sm"></i>
                                                    Danh sách đơn mua hàng (PO) – {{ $row['supplier'] }}
                                                </h4>
                                                <span class="text-[11px] font-bold bg-indigo-50 text-indigo-700 px-2.5 py-0.5 rounded border border-indigo-100">
                                                    Tổng cộng: {{ count($row['orders']) }} đơn
                                                </span>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-150 text-xs">
                                                    <thead class="bg-gray-50/70 text-gray-500 uppercase font-bold tracking-wider">
                                                        <tr>
                                                            <th class="px-3 py-2.5 text-left text-[10px]">Mã PO</th>
                                                            <th class="px-3 py-2.5 text-left text-[10px]">Ngày đặt</th>
                                                            <th class="px-3 py-2.5 text-left text-[10px]">Mã SO liên quan</th>
                                                            <th class="px-3 py-2.5 text-left text-[10px]">Salesperson</th>
                                                            <th class="px-3 py-2.5 text-right text-[10px]">Tổng tiền (USD)</th>
                                                            <th class="px-3 py-2.5 text-right text-[10px]">Tổng tiền (VND)</th>
                                                            <th class="px-3 py-2.5 text-right text-[10px]">Chiết khấu</th>
                                                            <th class="px-3 py-2.5 text-right text-[10px]">CP Vận chuyển</th>
                                                            <th class="px-3 py-2.5 text-right text-[10px]">Thực trả (VND)</th>
                                                            <th class="px-3 py-2.5 text-center text-[10px]">Trạng thái PO</th>
                                                            <th class="px-3 py-2.5 text-center text-[10px]">Thanh toán</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 text-gray-700 bg-white">
                                                        @forelse($row['orders'] as $po)
                                                            <tr class="hover:bg-indigo-50/30 transition-colors">
                                                                <td class="px-3 py-3 font-bold text-blue-600">
                                                                    <a href="{{ route('purchase-orders.show', $po['id']) }}" class="hover:underline flex items-center gap-1.5" target="_blank">
                                                                        <i class="fas fa-external-link-alt text-[9px] opacity-60"></i>
                                                                        {{ $po['code'] }}
                                                                    </a>
                                                                </td>
                                                                <td class="px-3 py-3 text-gray-500 font-medium">{{ $po['order_date'] }}</td>
                                                                <td class="px-3 py-3 font-bold text-gray-700">{{ $po['linked_so_codes'] }}</td>
                                                                <td class="px-3 py-3 text-gray-600 font-semibold">{{ $po['linked_salesperson_names'] }}</td>
                                                                <td class="px-3 py-3 text-right font-bold text-indigo-600">${{ $po['total_usd'] }}</td>
                                                                <td class="px-3 py-3 text-right text-gray-500 font-medium">{{ $po['total_vnd'] }}đ</td>
                                                                <td class="px-3 py-3 text-right text-emerald-600 font-bold">{{ $po['discount_vnd'] }}đ</td>
                                                                <td class="px-3 py-3 text-right text-gray-500 font-medium">{{ $po['shipping_cost_vnd'] }}đ</td>
                                                                <td class="px-3 py-3 text-right font-bold text-gray-900">{{ $po['paid_vnd'] }}đ</td>
                                                                <td class="px-3 py-3 text-center">
                                                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full {{ $po['status_color'] }} border border-transparent">
                                                                        {{ $po['status_label'] }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-3 py-3 text-center">
                                                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full {{ $po['payment_status_color'] }} border border-transparent">
                                                                        {{ $po['payment_status_label'] }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="11" class="px-4 py-6 text-center text-gray-400">
                                                                    <i class="fas fa-folder-open text-2xl mb-2 opacity-35"></i>
                                                                    <p>Không có đơn mua hàng nào trong khoảng thời gian này.</p>
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">Không có dữ liệu</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Product Report -->
            <div class="tab-content p-4 hidden" id="tab-product">
                <h3 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-box mr-2 text-primary"></i>Báo cáo
                    theo sản phẩm</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Sản phẩm (Part #)</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">SL nhập</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Giá TB (USD)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Tổng giá trị (USD)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Giá kho TB (VND)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CP phục vụ (VND)</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Số NCC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($productReport as $row)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 py-3 font-medium">
                                        <div class="font-bold text-gray-900" title="{{ $row['product_name'] }}">{{ $row['product_code'] }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ number_format($row['total_quantity']) }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="font-bold text-indigo-600">${{ number_format($row['avg_purchase_price_usd'] ?? 0, 2) }}</div>
                                        <div class="text-[10px] text-gray-400">{{ number_format($row['avg_purchase_price'], 0, ',', '.') }}đ</div>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="font-bold">${{ number_format($row['total_value_usd'] ?? 0, 2) }}</div>
                                        <div class="text-[10px] text-gray-400">{{ number_format($row['total_value'], 0, ',', '.') }}đ</div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-green-600 font-bold">
                                        {{ number_format($row['avg_warehouse_price'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        {{ number_format($row['total_service_cost'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ $row['supplier_count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">Không có dữ liệu</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Monthly Report -->
            <div class="tab-content p-4 hidden" id="tab-monthly">
                <h3 class="text-base font-semibold text-gray-800 mb-4"><i
                        class="fas fa-calendar-alt mr-2 text-primary"></i>Báo cáo theo tháng</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Tháng</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Số đơn</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Tổng giá trị (USD)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Chiết khấu (VND)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CP vận chuyển (VND)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Thực trả (USD)</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">So với tháng trước</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($monthlyReport as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $row['month'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $row['order_count'] }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="font-bold text-indigo-600">${{ number_format($row['total_amount_usd'] ?? 0, 2) }}</div>
                                        <div class="text-[10px] text-gray-400">{{ number_format($row['total_amount'], 0, ',', '.') }}đ</div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-green-600">
                                        {{ number_format($row['total_discount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['total_shipping'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="font-bold text-gray-900">${{ number_format($row['total_paid_usd'] ?? 0, 2) }}</div>
                                        <div class="text-[10px] text-gray-400">{{ number_format($row['total_paid'], 0, ',', '.') }}đ</div>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if($row['change'] !== null)
                                            <span class="{{ $row['change'] >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                                {{ $row['change'] >= 0 ? '+' : '' }}{{ $row['change'] }}%
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">Không có dữ liệu</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const tabId = this.dataset.tab;

                    // Update buttons
                    tabBtns.forEach(b => {
                        b.classList.remove('active', 'border-primary', 'text-primary');
                        b.classList.add('border-transparent', 'text-gray-500');
                    });
                    this.classList.add('active', 'border-primary', 'text-primary');
                    this.classList.remove('border-transparent', 'text-gray-500');

                    // Update content
                    tabContents.forEach(c => c.classList.add('hidden'));
                    document.getElementById('tab-' + tabId).classList.remove('hidden');
                });
            });

            // Toggle supplier orders sub-row
            const supplierRows = document.querySelectorAll('.supplier-row');
            supplierRows.forEach(row => {
                row.addEventListener('click', function () {
                    const supplierId = this.dataset.supplierId;
                    const subRow = document.getElementById('orders-' + supplierId);
                    const icon = document.getElementById('icon-' + supplierId);
                    const btnWrapper = this.querySelector('.toggle-btn-wrapper');
                    
                    if (subRow.classList.contains('hidden')) {
                        subRow.classList.remove('hidden');
                        icon.classList.add('rotate-90');
                        btnWrapper.classList.remove('bg-indigo-50', 'text-indigo-600');
                        btnWrapper.classList.add('bg-indigo-600', 'text-white');
                    } else {
                        subRow.classList.add('hidden');
                        icon.classList.remove('rotate-90');
                        btnWrapper.classList.remove('bg-indigo-600', 'text-white');
                        btnWrapper.classList.add('bg-indigo-50', 'text-indigo-600');
                    }
                });
            });
        });
    </script>
@endsection