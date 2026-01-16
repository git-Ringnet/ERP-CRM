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
                <a href="{{ route('purchase-reports.export') }}"
                    class="inline-flex hidden items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded-md hover:bg-green-600">
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sản phẩm</label>
                    <select name="product_id"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Tất cả sản phẩm</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ $productId == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
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
                        <p class="text-sm opacity-80">Đơn mua hàng</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_orders']) }}</p>
                    </div>
                    <i class="fas fa-shopping-cart text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-green-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Tổng giá trị</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_amount'] / 1000000, 1) }}tr</p>
                    </div>
                    <i class="fas fa-money-bill-wave text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-yellow-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Tổng chiết khấu</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_discount'] / 1000000, 1) }}tr</p>
                    </div>
                    <i class="fas fa-percent text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-red-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">CP vận chuyển</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_shipping'] / 1000000, 1) }}tr</p>
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

            <!-- Supplier Report -->
            <div class="tab-content p-4" id="tab-supplier">
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
                                    <td class="px-3 py-2 text-right">{{ number_format($row['total_subtotal'], 0, ',', '.') }}đ
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
                                    <td class="px-3 py-2 text-right">{{ number_format($row['total_subtotal'], 0, ',', '.') }}đ
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
                        <p class="text-sm text-gray-600">Chi phí phục vụ</p>
                        <p class="text-xl font-bold text-blue-600">
                            {{ number_format($costAnalysis['service_cost'] / 1000000, 1) }}tr
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