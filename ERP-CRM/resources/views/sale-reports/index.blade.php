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
                        <p class="text-2xl font-bold">{{ number_format($stats['total_revenue'] / 1000000, 1) }}tr</p>
                        <p class="text-xs opacity-70">{{ number_format($stats['total_orders']) }} đơn hàng</p>
                    </div>
                    <i class="fas fa-coins text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-green-500 text-white rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Lợi nhuận gộp</p>
                        <p class="text-2xl font-bold">{{ number_format($stats['total_profit'] / 1000000, 1) }}tr</p>
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
                        <p class="text-2xl font-bold">{{ number_format($stats['total_cost'] / 1000000, 1) }}tr</p>
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
                        data-tab="monthly">
                        <i class="fas fa-calendar-alt mr-1"></i>Theo Tháng
                    </button>
                    <button type="button"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="profit">
                        <i class="fas fa-chart-pie mr-1"></i>Phân tích Lợi nhuận
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
                                    <td class="px-3 py-2 text-right font-bold text-primary">{{ number_format($row['total_revenue'], 0, ',', '.') }}đ</td>
                                    <td class="px-3 py-2 text-right text-green-600 font-medium">
                                        {{ number_format($row['total_profit'], 0, ',', '.') }}đ
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
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Sản phẩm</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Số lượng bán</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Doanh thu</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Lợi nhuận gộp</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Tỷ suất LN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($productReport as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $row['product'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ number_format($row['total_quantity']) }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-primary">{{ number_format($row['total_revenue'], 0, ',', '.') }}đ</td>
                                    <td class="px-3 py-2 text-right text-green-600 font-medium">
                                        {{ number_format($row['total_profit'], 0, ',', '.') }}đ
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

            <!-- Monthly Report -->
            <div class="tab-content p-4 hidden" id="tab-monthly">
                <h3 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-calendar-alt mr-2 text-primary"></i>Diễn biến doanh thu theo tháng</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Tháng</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Số đơn hàng</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Doanh thu</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Lợi nhuận</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-700">Tỷ suất LN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($monthlyReport as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium">{{ $row['month'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $row['order_count'] }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-primary">{{ number_format($row['total_revenue'], 0, ',', '.') }}đ</td>
                                    <td class="px-3 py-2 text-right text-green-600 font-medium">
                                        {{ number_format($row['total_profit'], 0, ',', '.') }}đ
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

            <!-- Profit Analysis -->
            <div class="tab-content p-4 hidden" id="tab-profit">
                <h3 class="text-base font-semibold text-gray-800 mb-4"><i class="fas fa-chart-pie mr-2 text-primary"></i>Cơ cấu doanh thu & Chi phí</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Text Summary -->
                    <div>
                        <div class="bg-gray-50 rounded-lg p-5">
                            <h4 class="text-sm font-bold text-gray-500 uppercase mb-4">Tổng quan tài chính</h4>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                    <span class="text-gray-700 font-medium">Doanh thu thuần</span>
                                    <span class="text-lg font-bold text-primary">{{ number_format($profitAnalysis['revenue'], 0, ',', '.') }}đ</span>
                                </div>
                                <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                    <span class="text-gray-600">(-) Giá vốn hàng bán (COGS)</span>
                                    <span class="font-medium">{{ number_format($profitAnalysis['cogs'], 0, ',', '.') }}đ</span>
                                </div>
                                <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                                    <span class="text-gray-600">(-) Chi phí bán hàng</span>
                                    <span class="font-medium">{{ number_format($profitAnalysis['expenses'], 0, ',', '.') }}đ</span>
                                </div>
                                <div class="flex justify-between items-center pt-2">
                                    <span class="text-gray-800 font-bold">(=) Lợi nhuận ròng</span>
                                    <span class="text-xl font-bold text-green-600">{{ number_format($profitAnalysis['profit'], 0, ',', '.') }}đ</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown Visual -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-500 uppercase mb-4">Phân bổ doanh thu</h4>
                        <div class="space-y-4">
                            @foreach($profitAnalysis['breakdown'] as $item)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="font-medium {{ $item['color'] }}">{{ $item['name'] }}</span>
                                        <span class="text-gray-600">{{ number_format($item['value'], 0, ',', '.') }}đ ({{ $item['rate'] }}%)</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="h-2.5 rounded-full {{ str_replace('text-', 'bg-', $item['color']) }}" style="width: {{ $item['rate'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
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
                                        {{ number_format($row['margin'], 0, ',', '.') }}
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
                                            {{ number_format($row['paid_amount'], 0, ',', '.') }}
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
                                    {{ number_format(collect($marginReport)->sum('margin'), 0, ',', '.') }}
                                </td>
                                <td class="px-2 py-2 text-center border border-gray-300"></td>
                                <td class="px-2 py-2 border border-gray-300"></td>
                                <td class="px-2 py-2 text-right border border-gray-300">
                                    {{ number_format(collect($marginReport)->sum('paid_amount'), 0, ',', '.') }}
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
