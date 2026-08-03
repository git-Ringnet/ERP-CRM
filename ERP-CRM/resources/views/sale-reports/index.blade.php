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
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng</label>
                    <select name="customer_id"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Tất cả khách hàng</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                    <div class="relative searchable-select" id="productSearchContainer" data-ajax-url="{{ route('api.products.search') }}">
                        <input type="text" id="productSearchInput" 
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary"
                            placeholder="Chọn hoặc tìm mã SP..." autocomplete="off"
                            value="{{ $selectedProduct ? $selectedProduct->code : '' }}">
                        <input type="hidden" name="product_id" id="productIdHidden" value="{{ $productId }}">
                        
                        <div id="productSearchDropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                            <!-- Results will be loaded here -->
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NV Kinh doanh</label>
                    <select name="user_id"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Tất cả</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ ($userId ?? '') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
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
                    <table class="w-full text-sm border-collapse min-w-[1400px]">
                        <thead>
                            <tr class="bg-[#1a3a5c] text-white text-xs text-center">
                                <th class="px-2 py-2 border border-[#0d2a4a] w-10">STT</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] min-w-[180px]">Tên khách hàng</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] min-w-[120px]">Số Hóa đơn<br/>tài chính</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] min-w-[100px]">Ngày xuất<br/>hóa đơn</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] min-w-[80px]">HÃNG</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] w-16">License</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] min-w-[140px]">Loại hàng</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] min-w-[100px]">Mã Hàng hóa<br/>chính</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] min-w-[110px]">Margin</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] w-20">Margin %</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] min-w-[130px]">NV<br/>Kinh doanh</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] min-w-[140px]">Tổng Tiền KH<br/>đã thanh toán</th>
                                <th class="px-2 py-2 border border-[#0d2a4a] w-24">Tỷ lệ KH đã<br/>thanh toán (%)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($marginReport as $row)
                                <tr class="hover:bg-blue-50 transition-colors text-xs">
                                    <td class="px-2 py-2 text-center border border-gray-200">{{ $row['stt'] }}</td>
                                    <td class="px-2 py-2 border border-gray-200 font-medium">{{ $row['customer_name'] }}</td>
                                    <td class="px-2 py-2 text-center border border-gray-200">
                                        <a href="{{ route('sales.show', $row['sale_id']) }}" class="text-primary hover:underline" title="Xem đơn hàng">
                                            {{ $row['invoice_number'] }}
                                        </a>
                                    </td>
                                    <td class="px-2 py-2 text-center border border-gray-200">{{ $row['invoice_date'] }}</td>
                                    <td class="px-2 py-2 text-center border border-gray-200 text-gray-400 italic"></td>
                                    <td class="px-2 py-2 text-center border border-gray-200 text-gray-400 italic"></td>
                                    <td class="px-2 py-2 text-center border border-gray-200 text-gray-400 italic"></td>
                                    <td class="px-2 py-2 text-center border border-gray-200 font-mono text-xs">{{ $row['main_product_code'] }}</td>
                                    <td class="px-2 py-2 text-right border border-gray-200 font-semibold {{ $row['margin'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                        {{ number_format($row['margin']) }}
                                    </td>
                                    <td class="px-2 py-2 text-center border border-gray-200">
                                        <span class="inline-block px-1.5 py-0.5 text-xs font-medium rounded-full
                                            {{ $row['margin_percent'] >= 15 ? 'bg-green-100 text-green-800' : ($row['margin_percent'] >= 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $row['margin_percent'] }}%
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 border border-gray-200">{{ $row['salesperson'] }}</td>
                                    <td class="px-2 py-2 text-right border border-gray-200">
                                        @if($row['paid_amount'] > 0)
                                            {{ number_format($row['paid_amount']) }}
                                        @else
                                            <span class="text-gray-400">Chưa thanh toán</span>
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
                                    <td colspan="13" class="px-3 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                                        <p>Không có dữ liệu trong khoảng thời gian này</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($marginReport) > 0)
                        <tfoot>
                            <tr class="bg-gray-100 font-bold text-xs">
                                <td colspan="8" class="px-2 py-2 text-right border border-gray-300">TỔNG CỘNG</td>
                                <td class="px-2 py-2 text-right border border-gray-300 {{ collect($marginReport)->sum('margin') >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ number_format(collect($marginReport)->sum('margin')) }}
                                </td>
                                <td class="px-2 py-2 text-center border border-gray-300"></td>
                                <td class="px-2 py-2 border border-gray-300"></td>
                                <td class="px-2 py-2 text-right border border-gray-300">
                                    {{ number_format(collect($marginReport)->sum('paid_amount')) }}
                                </td>
                                <td class="px-2 py-2 border border-gray-300"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
                <div class="mt-3 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Các cột <strong>Hãng</strong>, <strong>License</strong>, <strong>Loại hàng</strong> hiện chưa có dữ liệu — vui lòng điền sau khi xuất Excel.
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
            }

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    switchTab(this.dataset.tab);
                });
            });

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
