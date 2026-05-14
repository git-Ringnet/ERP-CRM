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
                <button onclick="window.location.reload()"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                    <i class="fas fa-sync mr-2"></i>Làm mới
                </button>
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
                    <select name="supplier_id"
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
                            searchQuery: '{{ $products->first() ? $products->first()->code . " - " . Str::limit($products->first()->name, 30) : "" }}',
                            selectedProductId: '{{ $productId }}',
                            searchResults: [],
                            showDropdown: false,
                            isSearching: false,
                            
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            <div class="bg-yellow-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Tổng chiết khấu</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_discount'] / 1000000, 1) }}tr</p>
                        <p class="text-xs opacity-90 mt-1">Tính theo giá trị quy đổi VND</p>
                    </div>
                    <i class="fas fa-percent text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-red-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">CP vận chuyển</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_shipping'] / 1000000, 1) }}tr</p>
                        <p class="text-xs opacity-90 mt-1">Cảng + Nội địa</p>
                    </div>
                    <i class="fas fa-truck text-3xl opacity-50"></i>
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
                    <button type="button"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="cost">
                        <i class="fas fa-chart-pie mr-1"></i>Phân tích CP
                    </button>
                    <button type="button"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="discount">
                        <i class="fas fa-tags mr-1"></i>Phân tích CK
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
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Tổng giá trị</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Chiết khấu</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CP vận chuyển</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Thực trả</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Tỷ lệ CK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($supplierReport as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $row['supplier'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $row['order_count'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['total_amount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right text-green-600">
                                        {{ number_format($row['total_discount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['total_shipping'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold">
                                        {{ number_format($row['total_paid'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-center"><span
                                            class="inline-block px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">{{ $row['discount_rate'] }}%</span>
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
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Sản phẩm</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">SL nhập</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Giá TB</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Tổng giá trị</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Giá kho TB</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CP phục vụ</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Số NCC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($productReport as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $row['product'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ number_format($row['total_quantity']) }}</td>
                                    <td class="px-3 py-2 text-right">
                                        {{ number_format($row['avg_purchase_price'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['total_value'], 0, ',', '.') }}đ</td>
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
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Tổng giá trị</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Chiết khấu</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CP vận chuyển</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Thực trả</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">So với tháng trước</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($monthlyReport as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $row['month'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $row['order_count'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['total_amount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right text-green-600">
                                        {{ number_format($row['total_discount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['total_shipping'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold">
                                        {{ number_format($row['total_paid'], 0, ',', '.') }}đ
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

            <!-- Cost Analysis -->
            <div class="tab-content p-4 hidden" id="tab-cost">
                <h3 class="text-base font-semibold text-gray-800 mb-4"><i
                        class="fas fa-chart-pie mr-2 text-primary"></i>Phân tích chi phí</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">Giá trị hàng hóa</p>
                        <p class="text-xl font-bold text-primary">
                            {{ number_format($costAnalysis['goods_value'] / 1000000, 1) }}tr
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">Chi phí vận chuyển</p>
                        <p class="text-xl font-bold text-yellow-600">
                            {{ number_format($costAnalysis['shipping_cost'] / 1000000, 1) }}tr
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">Chi phí khác</p>
                        <p class="text-xl font-bold text-blue-600">
                            {{ number_format($costAnalysis['other_cost'] / 1000000, 1) }}tr
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">VAT</p>
                        <p class="text-xl font-bold text-red-600">
                            {{ number_format($costAnalysis['vat_amount'] / 1000000, 1) }}tr
                        </p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Loại chi phí</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Giá trị</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($costAnalysis['breakdown'] as $cost)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $cost['name'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($cost['value'], 0, ',', '.') }}đ</td>
                                    <td class="px-3 py-2 text-center">{{ $cost['rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Discount Analysis -->
            <div class="tab-content p-4 hidden" id="tab-discount">
                <h3 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-tags mr-2 text-primary"></i>Phân
                    tích chiết khấu</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">CK cơ bản</p>
                        <p class="text-xl font-bold text-green-600">
                            {{ number_format($discountAnalysis['totals']['base'] / 1000000, 1) }}tr
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">CK số lượng</p>
                        <p class="text-xl font-bold text-blue-600">
                            {{ number_format($discountAnalysis['totals']['volume'] / 1000000, 1) }}tr
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">CK thanh toán sớm</p>
                        <p class="text-xl font-bold text-yellow-600">
                            {{ number_format($discountAnalysis['totals']['early'] / 1000000, 1) }}tr
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">CK đặc biệt</p>
                        <p class="text-xl font-bold text-red-600">
                            {{ number_format($discountAnalysis['totals']['special'] / 1000000, 1) }}tr
                        </p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Nhà cung cấp</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CK cơ bản</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CK số lượng</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CK TT sớm</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">CK đặc biệt</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Tổng CK</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Tỷ lệ CK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($discountAnalysis['by_supplier'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $row['supplier'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['base_discount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['volume_discount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['early_discount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ number_format($row['special_discount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-right text-green-600 font-bold">
                                        {{ number_format($row['total_discount'], 0, ',', '.') }}đ
                                    </td>
                                    <td class="px-3 py-2 text-center"><span
                                            class="inline-block px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">{{ $row['discount_rate'] }}%</span>
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
        });
    </script>
@endsection