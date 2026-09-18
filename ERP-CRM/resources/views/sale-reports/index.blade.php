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
                <a href="{{ route('sale-reports.export-margin', request()->query()) }}"
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded-md hover:bg-green-600 shadow-xs">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel Margin
                </a>
            </div>
        </div>

        <!-- Clean Date Filter Bar -->
        <div class="bg-white rounded-xl shadow-xs p-3.5 border border-gray-200">
            <form method="GET" id="saleReportFilterForm" class="flex flex-wrap items-center justify-between gap-3">
                <input type="hidden" name="tab" id="activeTabInput" value="{{ request('tab', 'margin') }}">
                
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <label class="text-xs font-bold text-gray-700 whitespace-nowrap">
                            <i class="far fa-calendar-alt text-primary mr-1"></i>Từ ngày:
                        </label>
                        <input type="date" name="date_from" id="filter_date_from" value="{{ $dateFrom }}"
                            class="px-2.5 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary font-medium">
                    </div>

                    <div class="flex items-center gap-1.5">
                        <label class="text-xs font-bold text-gray-700 whitespace-nowrap">
                            <i class="far fa-calendar-alt text-primary mr-1"></i>Đến ngày:
                        </label>
                        <input type="date" name="date_to" id="filter_date_to" value="{{ $dateTo }}"
                            class="px-2.5 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary font-medium">
                    </div>

                    <!-- Quick Date Presets -->
                    <div class="flex items-center gap-1 bg-gray-50 p-1 rounded-lg border border-gray-200">
                        <button type="button" class="date-preset-btn px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-white hover:text-primary hover:shadow-2xs rounded-md transition-all" data-preset="30days">30 ngày qua</button>
                        <button type="button" class="date-preset-btn px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-white hover:text-primary hover:shadow-2xs rounded-md transition-all" data-preset="thisMonth">Tháng này</button>
                        <button type="button" class="date-preset-btn px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-white hover:text-primary hover:shadow-2xs rounded-md transition-all" data-preset="lastMonth">Tháng trước</button>
                        <button type="button" class="date-preset-btn px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-white hover:text-primary hover:shadow-2xs rounded-md transition-all" data-preset="thisQuarter">Quý này</button>
                        <button type="button" class="date-preset-btn px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-white hover:text-primary hover:shadow-2xs rounded-md transition-all" data-preset="thisYear">Năm nay</button>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                        class="px-4 py-1.5 bg-primary text-white rounded-lg hover:bg-primary-dark text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                        <i class="fas fa-search"></i> Lấy dữ liệu
                    </button>
                    <a href="{{ route('sale-reports.index', ['tab' => request('tab', 'margin')]) }}"
                        class="px-3 py-1.5 bg-gray-100 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-200 text-xs font-semibold transition-colors flex items-center gap-1" title="Đặt lại khoảng ngày">
                        <i class="fas fa-redo text-gray-400"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>

        <!-- Summary Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-primary text-white rounded-lg shadow-sm p-4 transition-all">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Doanh thu</p>
                        <p class="text-2xl font-bold" id="stat_total_revenue">{{ number_format($stats['total_revenue']) }}đ</p>
                        <p class="text-xs opacity-70" id="stat_total_orders">{{ number_format($stats['total_orders']) }} đơn hàng</p>
                    </div>
                    <i class="fas fa-coins text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-green-500 text-white rounded-lg shadow-sm p-4 transition-all">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Lợi nhuận gộp</p>
                        <p class="text-2xl font-bold" id="stat_total_profit">{{ number_format($stats['total_profit']) }}đ</p>
                        <p class="text-xs opacity-70">Lợi nhuận gộp thực tế</p>
                    </div>
                    <i class="fas fa-chart-line text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-blue-500 text-white rounded-lg shadow-sm p-4 transition-all">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Tỷ suất lợi nhuận</p>
                        <p class="text-2xl font-bold" id="stat_margin_percent">{{ $stats['margin_percent'] }}%</p>
                        <p class="text-xs opacity-70">Tỷ suất Margin trung bình</p>
                    </div>
                    <i class="fas fa-percentage text-3xl opacity-50"></i>
                </div>
            </div>
            <div class="bg-red-500 text-white rounded-lg shadow-sm p-4 transition-all">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm opacity-80">Tổng chi phí</p>
                        <p class="text-2xl font-bold" id="stat_total_cost">{{ number_format($stats['total_cost']) }}đ</p>
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
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="customer">
                        <i class="fas fa-users mr-1"></i>Theo Khách hàng
                    </button>
                    <button type="button"
                        class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700"
                        data-tab="product">
                        <i class="fas fa-box mr-1"></i>Theo Sản phẩm
                    </button>
                    <button type="button"
                        class="tab-btn active px-4 py-3 text-sm font-medium border-b-2 border-primary text-primary"
                        data-tab="margin">
                        <i class="fas fa-file-invoice-dollar mr-1"></i>Báo cáo Margin
                    </button>
                </nav>
            </div>

            <!-- Customer Report Tab -->
            <div class="tab-content p-4 hidden" id="tab-customer">
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

            <!-- Product Report Tab -->
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

            <!-- Margin Report Tab with Excel-style Table Filters -->
            <div class="tab-content p-4" id="tab-margin">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-primary"></i>
                            Báo cáo Lãi/Lỗ (Margin) theo đơn hàng
                            <span class="text-xs font-normal text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                                {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                            </span>
                        </h3>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <a href="{{ route('sale-reports.export-margin', request()->query()) }}"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-xs transition-colors">
                            <i class="fas fa-file-excel mr-1.5"></i>Xuất Excel Margin
                        </a>
                    </div>
                </div>

                <!-- Excel Column Filters Active Bar -->
                <div class="flex flex-wrap items-center justify-between gap-2 p-2.5 mb-3 bg-blue-50/70 border border-blue-200 rounded-lg text-xs">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-gray-700 font-medium">
                            <i class="fas fa-table text-blue-600 mr-1"></i>Hiển thị: 
                            <span class="font-bold text-primary" id="marginDisplayCount">{{ count($marginReport) }}</span> / 
                            <span class="font-bold text-gray-800" id="marginTotalCount">{{ count($marginReport) }}</span> đơn hàng
                        </span>
                        
                        <div id="activeFilterBadgesContainer" class="flex items-center gap-1.5 flex-wrap">
                            <!-- Filter pills will be rendered here -->
                        </div>
                    </div>

                    <button type="button" id="clearAllColumnFiltersBtn" class="hidden items-center gap-1 px-2.5 py-1 text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-md transition-colors shadow-2xs">
                        <i class="fas fa-filter-circle-xmark"></i> Xóa tất cả bộ lọc cột
                    </button>
                </div>

                <!-- Margin Report Excel Table -->
                <div class="overflow-x-auto border border-gray-300 rounded-lg shadow-2xs relative" id="marginTableContainer">
                    <table class="w-full text-sm border-collapse min-w-[2400px]" id="marginReportTable">
                        <thead>
                            <tr class="text-xs text-center select-none">
                                <!-- Dark blue headers (Left group) -->
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] w-12 sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span>STT</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="stt" data-col-title="STT" data-col-type="number" title="Lọc và sắp xếp STT">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[200px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-left font-semibold">Tên khách hàng</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="customer_name" data-col-title="Tên khách hàng" data-col-type="text" title="Lọc và sắp xếp Tên khách hàng">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[150px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <div class="text-left">
                                            <span>Số HĐ tài chính</span><br/>
                                            <span class="text-[10px] font-normal opacity-80">(hoặc Số ĐH PM)</span>
                                        </div>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="invoice_number" data-col-title="Số Hóa đơn / Mã SO" data-col-type="text" title="Lọc và sắp xếp Số Hóa đơn">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[110px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span>Ngày xuất HĐ</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="invoice_date" data-col-title="Ngày xuất hóa đơn" data-col-type="text" title="Lọc và sắp xếp Ngày xuất HĐ">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[100px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span>HÃNG</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="brand" data-col-title="HÃNG" data-col-type="text" title="Lọc và sắp xếp Hãng">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] w-20 sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span>License</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="license" data-col-title="License" data-col-type="text" title="Lọc và sắp xếp License">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[130px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span>Loại hàng</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="product_type" data-col-title="Loại hàng" data-col-type="text" title="Lọc và sắp xếp Loại hàng">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[120px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span>Mã hàng chính</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="main_product_code" data-col-title="Mã hàng hóa chính" data-col-type="text" title="Lọc và sắp xếp Mã hàng hóa chính">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>

                                <!-- Light blue headers (Financial columns) -->
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[130px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">Tiền hàng<br/>(chưa VAT)</span>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="revenue_before_vat" data-col-title="Tiền hàng (chưa VAT)" data-col-type="number" title="Lọc và sắp xếp Tiền hàng (chưa VAT)">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[115px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">Tiền thuế<br/>(VAT)</span>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="vat_amount" data-col-title="Tiền thuế (VAT)" data-col-type="number" title="Lọc và sắp xếp Tiền thuế (VAT)">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[130px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">Tổng tiền<br/>(gồm VAT)</span>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="total_amount_incl_vat" data-col-title="Tổng tiền (gồm VAT)" data-col-type="number" title="Lọc và sắp xếp Tổng tiền (gồm VAT)">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[125px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">Giá vốn<br/>hàng hóa</span>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="goods_cost" data-col-title="Giá vốn hàng hóa" data-col-type="number" title="Lọc và sắp xếp Giá vốn">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[160px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <div class="text-right flex-1">
                                            <span>CP triển khai HĐ</span><br/>
                                            <span class="text-[10px] font-normal opacity-90">(Tiếp khách, cài đặt)</span>
                                        </div>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="implementation_cost" data-col-title="Chi phí triển khai HĐ" data-col-type="number" title="Lọc và sắp xếp CP triển khai">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[110px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">Thuế<br/>nhà thầu</span>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="contractor_tax" data-col-title="Thuế nhà thầu" data-col-type="number" title="Lọc và sắp xếp Thuế nhà thầu">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[115px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">CP tài chính<br/>(1%)</span>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="finance_cost" data-col-title="Chi phí tài chính (1%)" data-col-type="number" title="Lọc và sắp xếp CP tài chính">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[145px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <div class="text-right flex-1">
                                            <span>CP Quản lý,</span><br/>
                                            <span class="text-[10px] font-normal opacity-90">BO & Kỹ thuật</span>
                                        </div>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="management_cost" data-col-title="CP Quản lý & BO" data-col-type="number" title="Lọc và sắp xếp CP Quản lý">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[105px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">24x7<br/>(0.5%)</span>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="support_247_cost" data-col-title="24x7 (0.5%)" data-col-type="number" title="Lọc và sắp xếp 24x7">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#6ba4d8] text-[#0d2a4a] font-bold border border-[#4d86ba] min-w-[115px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">Other Support<br/>(Zyxel)</span>
                                        <button type="button" class="excel-col-filter-btn text-[#0d2a4a]/70 hover:text-[#0d2a4a] p-1 rounded hover:bg-black/10 transition-colors" data-col="other_support_cost" data-col-title="Other Support" data-col-type="number" title="Lọc và sắp xếp Other Support">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>

                                <!-- Dark blue headers (Right group) -->
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[115px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">Margin</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="margin" data-col-title="Margin (VNĐ)" data-col-type="number" title="Lọc và sắp xếp Margin">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] w-24 sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-center flex-1">Margin %</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="margin_percent" data-col-title="Tỷ lệ Margin (%)" data-col-type="number" title="Lọc và sắp xếp Margin %">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[135px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-left flex-1">NV Kinh doanh</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="salesperson" data-col-title="NV Kinh doanh" data-col-type="text" title="Lọc và sắp xếp NV Kinh doanh">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] min-w-[135px] sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-right flex-1">Đã thanh toán</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="paid_amount" data-col-title="Tiền đã thanh toán" data-col-type="number" title="Lọc và sắp xếp Tiền đã TT">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                                <th class="px-2 py-2.5 bg-[#1a3a5c] text-white border border-[#0d2a4a] w-24 sticky top-0 z-10">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-center flex-1">Tỷ lệ TT (%)</span>
                                        <button type="button" class="excel-col-filter-btn text-white/70 hover:text-white p-1 rounded hover:bg-white/10 transition-colors" data-col="payment_percent" data-col-title="Tỷ lệ thanh toán (%)" data-col-type="number" title="Lọc và sắp xếp Tỷ lệ TT">
                                            <i class="fas fa-filter text-[10px]"></i>
                                        </button>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($marginReport as $row)
                                <tr class="margin-row hover:bg-blue-50/70 transition-colors text-xs"
                                    data-stt="{{ $row['stt'] }}"
                                    data-customer_name="{{ $row['customer_name'] }}"
                                    data-invoice_number="{{ $row['invoice_number'] }}"
                                    data-invoice_date="{{ $row['invoice_date'] }}"
                                    data-brand="{{ $row['brand'] ?: 'N/A' }}"
                                    data-license="{{ $row['license'] ?: 'N/A' }}"
                                    data-product_type="{{ $row['product_type'] ?: 'N/A' }}"
                                    data-main_product_code="{{ $row['main_product_code'] ?: 'N/A' }}"
                                    data-revenue_before_vat="{{ $row['revenue_before_vat'] }}"
                                    data-vat_amount="{{ $row['vat_amount'] }}"
                                    data-total_amount_incl_vat="{{ $row['total_amount_incl_vat'] }}"
                                    data-goods_cost="{{ $row['goods_cost'] }}"
                                    data-implementation_cost="{{ $row['implementation_cost'] }}"
                                    data-contractor_tax="{{ $row['contractor_tax'] }}"
                                    data-finance_cost="{{ $row['finance_cost'] }}"
                                    data-management_cost="{{ $row['management_cost'] }}"
                                    data-support_247_cost="{{ $row['support_247_cost'] }}"
                                    data-other_support_cost="{{ $row['other_support_cost'] }}"
                                    data-margin="{{ $row['margin'] }}"
                                    data-margin_percent="{{ $row['margin_percent'] }}"
                                    data-salesperson="{{ $row['salesperson'] }}"
                                    data-paid_amount="{{ $row['paid_amount'] }}"
                                    data-payment_percent="{{ $row['payment_percent'] }}"
                                >
                                    <td class="px-2 py-2 text-center border border-gray-200 cell-stt">{{ $row['stt'] }}</td>
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

                                    <!-- Financial cells -->
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
                                <tr id="noMarginDataRow">
                                    <td colspan="23" class="px-3 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                                        <p>Không có dữ liệu trong khoảng thời gian này</p>
                                    </td>
                                </tr>
                            @endforelse
                            <tr id="emptyFilterResultRow" class="hidden">
                                <td colspan="23" class="px-3 py-8 text-center text-gray-500 bg-gray-50">
                                    <i class="fas fa-filter-circle-xmark text-3xl text-gray-300 mb-2"></i>
                                    <p class="font-medium text-gray-700">Không có đơn hàng nào khớp với bộ lọc cột hiện tại</p>
                                    <button type="button" class="mt-2 px-3 py-1 bg-primary text-white text-xs font-semibold rounded hover:bg-primary-dark transition-colors" onclick="document.getElementById('clearAllColumnFiltersBtn').click()">
                                        <i class="fas fa-redo mr-1"></i>Xóa bộ lọc cột
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        @if(count($marginReport) > 0)
                        <tfoot id="marginTableFoot">
                            <tr class="bg-gray-100 font-bold text-xs">
                                <td colspan="8" class="px-2 py-2.5 text-right border border-gray-300 uppercase text-gray-700">TỔNG CỘNG</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_revenue_before_vat">{{ number_format(collect($marginReport)->sum('revenue_before_vat')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_vat_amount">{{ number_format(collect($marginReport)->sum('vat_amount')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono text-gray-900" id="foot_total_amount_incl_vat">{{ number_format(collect($marginReport)->sum('total_amount_incl_vat')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_goods_cost">{{ number_format(collect($marginReport)->sum('goods_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_implementation_cost">{{ number_format(collect($marginReport)->sum('implementation_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_contractor_tax">{{ number_format(collect($marginReport)->sum('contractor_tax')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_finance_cost">{{ number_format(collect($marginReport)->sum('finance_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_management_cost">{{ number_format(collect($marginReport)->sum('management_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_support_247_cost">{{ number_format(collect($marginReport)->sum('support_247_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_other_support_cost">{{ number_format(collect($marginReport)->sum('other_support_cost')) }}</td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono {{ collect($marginReport)->sum('margin') >= 0 ? 'text-green-700' : 'text-red-700' }}" id="foot_margin">
                                    {{ number_format(collect($marginReport)->sum('margin')) }}
                                </td>
                                <td class="px-2 py-2.5 text-center border border-gray-300">
                                    @php
                                        $totalNet = collect($marginReport)->sum('revenue_before_vat');
                                        $totalMarginSum = collect($marginReport)->sum('margin');
                                        $avgMarginPercent = $totalNet > 0 ? round(($totalMarginSum / $totalNet) * 100, 1) : 0;
                                    @endphp
                                    <span class="inline-block px-1.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 text-blue-800" id="foot_margin_percent">
                                        {{ $avgMarginPercent }}%
                                    </span>
                                </td>
                                <td class="px-2 py-2.5 border border-gray-300"></td>
                                <td class="px-2 py-2.5 text-right border border-gray-300 font-mono" id="foot_paid_amount">
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

    <!-- Excel Column Filter Popover (Singleton Floating Box) -->
    <div id="excelColumnFilterPopover" class="hidden fixed z-50 bg-white border border-gray-300 rounded-xl shadow-2xl w-80 text-xs font-sans select-none animate-fadeIn">
        <!-- Popover Header -->
        <div class="px-3 py-2 bg-gradient-to-r from-[#1a3a5c] to-[#2a5584] text-white rounded-t-xl flex items-center justify-between">
            <span class="font-bold flex items-center gap-1.5 truncate" id="popoverColTitle">
                <i class="fas fa-filter text-yellow-300 text-[11px]"></i>
                <span id="popoverColNameText">Lọc cột</span>
            </span>
            <button type="button" id="closeFilterPopoverBtn" class="text-white/80 hover:text-white p-0.5 rounded hover:bg-white/20">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Sort Section -->
        <div class="p-2 border-b border-gray-200 bg-gray-50/70 space-y-1">
            <button type="button" id="btnSortAsc" class="w-full text-left px-2.5 py-1.5 rounded hover:bg-blue-50 hover:text-primary flex items-center gap-2 font-medium text-gray-700 transition-colors">
                <i class="fas fa-arrow-down-a-z text-blue-600 w-4"></i>
                <span id="sortAscLabel">Sắp xếp tăng dần (A → Z)</span>
            </button>
            <button type="button" id="btnSortDesc" class="w-full text-left px-2.5 py-1.5 rounded hover:bg-blue-50 hover:text-primary flex items-center gap-2 font-medium text-gray-700 transition-colors">
                <i class="fas fa-arrow-down-z-a text-blue-600 w-4"></i>
                <span id="sortDescLabel">Sắp xếp giảm dần (Z → A)</span>
            </button>
        </div>

        <!-- Filter Subtabs -->
        <div class="flex border-b border-gray-200 bg-gray-100 text-gray-600 font-semibold text-[11px]">
            <button type="button" id="tabBtnFilterValue" class="flex-1 py-1.5 text-center border-b-2 border-primary text-primary bg-white">
                Lọc theo giá trị
            </button>
            <button type="button" id="tabBtnFilterCondition" class="flex-1 py-1.5 text-center border-b-2 border-transparent hover:text-gray-900">
                Lọc theo điều kiện
            </button>
        </div>

        <!-- Tab 1: Value Checkbox List -->
        <div id="filterTabValueContent" class="p-2.5 space-y-2">
            <!-- Search Inside Values -->
            <div class="relative">
                <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-[10px]"></i>
                <input type="text" id="filterValueSearchInput" placeholder="Tìm kiếm trong danh sách..."
                    class="w-full pl-7 pr-2.5 py-1 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-primary focus:border-primary">
            </div>

            <!-- Selection Shortcuts -->
            <div class="flex items-center justify-between text-[11px] text-blue-600 font-medium px-1">
                <button type="button" id="btnSelectAllValues" class="hover:underline">Chọn tất cả</button>
                <span class="text-gray-300">|</span>
                <button type="button" id="btnInvertSelectValues" class="hover:underline">Đảo chọn</button>
                <span class="text-gray-300">|</span>
                <button type="button" id="btnClearSelectValues" class="hover:underline text-gray-500">Bỏ chọn</button>
            </div>

            <!-- Scrollable Value Checkboxes List -->
            <div id="filterValueListContainer" class="max-h-48 overflow-y-auto border border-gray-200 rounded-md p-1.5 space-y-1 bg-gray-50/50">
                <!-- Checkboxes populated by JS -->
            </div>
        </div>

        <!-- Tab 2: Condition / Number / Text Filter -->
        <div id="filterTabConditionContent" class="p-2.5 space-y-2.5 hidden">
            <div>
                <label class="block text-[11px] font-semibold text-gray-700 mb-1">Loại điều kiện:</label>
                <select id="filterConditionOperator" class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-primary">
                    <!-- Populated dynamically based on column type -->
                </select>
            </div>

            <div id="filterConditionInput1Wrapper">
                <label class="block text-[11px] font-semibold text-gray-700 mb-1" id="filterConditionLabel1">Giá trị:</label>
                <input type="text" id="filterConditionValue1" placeholder="Nhập giá trị lọc..."
                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-primary">
            </div>

            <div id="filterConditionInput2Wrapper" class="hidden">
                <label class="block text-[11px] font-semibold text-gray-700 mb-1">Đến giá trị:</label>
                <input type="text" id="filterConditionValue2" placeholder="Nhập giá trị đến..."
                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:ring-1 focus:ring-primary">
            </div>
        </div>

        <!-- Popover Footer Actions -->
        <div class="px-3 py-2 bg-gray-100 rounded-b-xl border-t border-gray-200 flex items-center justify-between gap-2">
            <button type="button" id="btnClearColumnFilter" class="px-2.5 py-1 text-gray-600 hover:text-red-700 hover:bg-white rounded border border-transparent hover:border-gray-300 font-semibold transition-colors">
                <i class="fas fa-trash-alt mr-1"></i>Xóa lọc
            </button>
            <div class="flex items-center gap-1.5">
                <button type="button" id="btnCancelFilter" class="px-3 py-1 bg-white border border-gray-300 text-gray-700 rounded hover:bg-gray-50 font-semibold transition-colors">
                    Hủy
                </button>
                <button type="button" id="btnApplyFilter" class="px-3.5 py-1 bg-primary text-white rounded hover:bg-primary-dark font-bold shadow-xs transition-colors">
                    Áp dụng (OK)
                </button>
            </div>
        </div>
    </div>

    <style>
        .excel-col-filter-btn.is-filtered {
            background-color: #f59e0b !important;
            color: #0f172a !important;
            font-weight: bold !important;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.8) !important;
            border-radius: 4px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.15s ease-out forwards;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Tab switching logic
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            function switchTab(tabId) {
                tabBtns.forEach(b => {
                    if (b.dataset.tab === tabId) {
                        b.classList.add('active', 'border-primary', 'text-primary');
                        b.classList.remove('border-transparent', 'text-gray-500');
                    } else {
                        b.classList.remove('active', 'border-primary', 'text-primary');
                        b.classList.add('border-transparent', 'text-gray-500');
                    }
                });

                tabContents.forEach(c => {
                    if (c.id === 'tab-' + tabId) {
                        c.classList.remove('hidden');
                    } else {
                        c.classList.add('hidden');
                    }
                });

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

            // Date Preset Shortcuts
            const dateFromInput = document.getElementById('filter_date_from');
            const dateToInput = document.getElementById('filter_date_to');
            const filterForm = document.getElementById('saleReportFilterForm');

            function formatDate(d) {
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            document.querySelectorAll('.date-preset-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const preset = this.dataset.preset;
                    const today = new Date();
                    let fromDate = new Date();
                    let toDate = new Date();

                    if (preset === '30days') {
                        fromDate.setDate(today.getDate() - 30);
                    } else if (preset === 'thisMonth') {
                        fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        toDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    } else if (preset === 'lastMonth') {
                        fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        toDate = new Date(today.getFullYear(), today.getMonth(), 0);
                    } else if (preset === 'thisQuarter') {
                        const quarterMonth = Math.floor(today.getMonth() / 3) * 3;
                        fromDate = new Date(today.getFullYear(), quarterMonth, 1);
                        toDate = new Date(today.getFullYear(), quarterMonth + 3, 0);
                    } else if (preset === 'thisYear') {
                        fromDate = new Date(today.getFullYear(), 0, 1);
                        toDate = new Date(today.getFullYear(), 11, 31);
                    }

                    dateFromInput.value = formatDate(fromDate);
                    dateToInput.value = formatDate(toDate);
                    filterForm.submit();
                });
            });

            // Handle initial tab from URL
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab && document.getElementById('tab-' + activeTab)) {
                switchTab(activeTab);
            }

            // ==========================================
            // EXCEL COLUMN FILTER & SORT SYSTEM
            // ==========================================
            const table = document.getElementById('marginReportTable');
            if (!table) return;

            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr.margin-row'));
            const totalRowCount = rows.length;
            const popover = document.getElementById('excelColumnFilterPopover');

            // Store parsed row dataset for high performance
            const rowData = rows.map((tr, index) => {
                const data = {};
                for (let attr of tr.attributes) {
                    if (attr.name.startsWith('data-')) {
                        const key = attr.name.replace('data-', '');
                        data[key] = attr.value;
                    }
                }
                data._tr = tr;
                data._index = index;
                return data;
            });

            // Global filter & sort state
            // Structure: { [colKey]: { type: 'value'|'condition', values: Set(), condition: { op, val1, val2 } } }
            const activeFilters = {};
            let activeSort = null; // { colKey: '...', dir: 'asc'|'desc' }
            let currentFilterCol = null; // Currently open column in popover
            let currentFilterType = 'text'; // 'text'|'number'|'date'
            let currentFilterTitle = '';

            // Popover Elements
            const popoverColNameText = document.getElementById('popoverColNameText');
            const sortAscLabel = document.getElementById('sortAscLabel');
            const sortDescLabel = document.getElementById('sortDescLabel');
            const btnSortAsc = document.getElementById('btnSortAsc');
            const btnSortDesc = document.getElementById('btnSortDesc');
            const tabBtnFilterValue = document.getElementById('tabBtnFilterValue');
            const tabBtnFilterCondition = document.getElementById('tabBtnFilterCondition');
            const filterTabValueContent = document.getElementById('filterTabValueContent');
            const filterTabConditionContent = document.getElementById('filterTabConditionContent');
            const filterValueSearchInput = document.getElementById('filterValueSearchInput');
            const filterValueListContainer = document.getElementById('filterValueListContainer');
            const filterConditionOperator = document.getElementById('filterConditionOperator');
            const filterConditionValue1 = document.getElementById('filterConditionValue1');
            const filterConditionValue2 = document.getElementById('filterConditionValue2');
            const filterConditionInput2Wrapper = document.getElementById('filterConditionInput2Wrapper');
            const btnSelectAllValues = document.getElementById('btnSelectAllValues');
            const btnInvertSelectValues = document.getElementById('btnInvertSelectValues');
            const btnClearSelectValues = document.getElementById('btnClearSelectValues');
            const btnClearColumnFilter = document.getElementById('btnClearColumnFilter');
            const btnApplyFilter = document.getElementById('btnApplyFilter');
            const btnCancelFilter = document.getElementById('btnCancelFilter');
            const closeFilterPopoverBtn = document.getElementById('closeFilterPopoverBtn');
            const clearAllColumnFiltersBtn = document.getElementById('clearAllColumnFiltersBtn');
            const activeFilterBadgesContainer = document.getElementById('activeFilterBadgesContainer');
            const marginDisplayCount = document.getElementById('marginDisplayCount');
            const emptyFilterResultRow = document.getElementById('emptyFilterResultRow');

            // Format number helper
            function formatVND(num) {
                return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + 'đ';
            }
            function formatNumberOnly(num) {
                return new Intl.NumberFormat('vi-VN').format(Math.round(num));
            }

            // Open filter popover on header button click
            document.querySelectorAll('.excel-col-filter-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const colKey = this.dataset.col;
                    const colTitle = this.dataset.colTitle;
                    const colType = this.dataset.colType || 'text';

                    if (popover.classList.contains('hidden') || currentFilterCol !== colKey) {
                        openFilterPopover(this, colKey, colTitle, colType);
                    } else {
                        closeFilterPopover();
                    }
                });
            });

            function openFilterPopover(triggerBtn, colKey, colTitle, colType) {
                currentFilterCol = colKey;
                currentFilterType = colType;
                currentFilterTitle = colTitle;

                popoverColNameText.textContent = colTitle;

                // Configure Sort Labels
                if (colType === 'number') {
                    sortAscLabel.textContent = 'Sắp xếp nhỏ → lớn (0 → 9)';
                    sortDescLabel.textContent = 'Sắp xếp lớn → nhỏ (9 → 0)';
                } else if (colType === 'date') {
                    sortAscLabel.textContent = 'Sắp xếp cũ nhất → mới nhất';
                    sortDescLabel.textContent = 'Sắp xếp mới nhất → cũ nhất';
                } else {
                    sortAscLabel.textContent = 'Sắp xếp tăng dần (A → Z)';
                    sortDescLabel.textContent = 'Sắp xếp giảm dần (Z → A)';
                }

                // Switch default to Value Tab
                switchFilterSubtab('value');

                // Populate Values Checkbox List
                populateValueCheckboxes(colKey);

                // Populate Condition Operators
                populateConditionOperators(colType, colKey);

                // Position the Popover relative to Trigger Button
                const rect = triggerBtn.getBoundingClientRect();
                const popoverWidth = 320;
                let left = rect.left;
                if (left + popoverWidth > window.innerWidth - 10) {
                    left = window.innerWidth - popoverWidth - 10;
                }
                if (left < 10) left = 10;

                let top = rect.bottom + 6;
                if (top + 420 > window.innerHeight) {
                    top = Math.max(10, rect.top - 420);
                }

                popover.style.left = `${left}px`;
                popover.style.top = `${top}px`;
                popover.classList.remove('hidden');

                filterValueSearchInput.value = '';
                filterValueSearchInput.focus();
            }

            function closeFilterPopover() {
                popover.classList.add('hidden');
                currentFilterCol = null;
            }

            closeFilterPopoverBtn.addEventListener('click', closeFilterPopover);
            btnCancelFilter.addEventListener('click', closeFilterPopover);

            // Close popover when clicking outside
            document.addEventListener('click', function (e) {
                if (!popover.contains(e.target) && !e.target.closest('.excel-col-filter-btn')) {
                    closeFilterPopover();
                }
            });

            // Tab Switching inside Popover
            function switchFilterSubtab(tab) {
                if (tab === 'value') {
                    tabBtnFilterValue.classList.add('border-primary', 'text-primary', 'bg-white');
                    tabBtnFilterValue.classList.remove('border-transparent', 'text-gray-600');
                    tabBtnFilterCondition.classList.remove('border-primary', 'text-primary', 'bg-white');
                    tabBtnFilterCondition.classList.add('border-transparent', 'text-gray-600');
                    filterTabValueContent.classList.remove('hidden');
                    filterTabConditionContent.classList.add('hidden');
                } else {
                    tabBtnFilterCondition.classList.add('border-primary', 'text-primary', 'bg-white');
                    tabBtnFilterCondition.classList.remove('border-transparent', 'text-gray-600');
                    tabBtnFilterValue.classList.remove('border-primary', 'text-primary', 'bg-white');
                    tabBtnFilterValue.classList.add('border-transparent', 'text-gray-600');
                    filterTabConditionContent.classList.remove('hidden');
                    filterTabValueContent.classList.add('hidden');
                }
            }

            tabBtnFilterValue.addEventListener('click', () => switchFilterSubtab('value'));
            tabBtnFilterCondition.addEventListener('click', () => switchFilterSubtab('condition'));

            // Populate Distinct Values
            function populateValueCheckboxes(colKey) {
                filterValueListContainer.innerHTML = '';

                // Calculate value counts from ALL dataset
                const counts = {};
                rowData.forEach(row => {
                    const rawVal = row[colKey] !== undefined && row[colKey] !== null ? String(row[colKey]).trim() : '';
                    const displayVal = rawVal === '' ? '(Trống / N/A)' : rawVal;
                    counts[displayVal] = (counts[displayVal] || 0) + 1;
                });

                const sortedValues = Object.keys(counts).sort((a, b) => {
                    if (currentFilterType === 'number') {
                        const numA = parseFloat(a) || 0;
                        const numB = parseFloat(b) || 0;
                        return numA - numB;
                    }
                    return a.localeCompare(b, 'vi');
                });

                const currentSavedFilter = activeFilters[colKey];
                const selectedValuesSet = (currentSavedFilter && currentSavedFilter.type === 'value') 
                    ? currentSavedFilter.values 
                    : null;

                sortedValues.forEach(val => {
                    const count = counts[val];
                    const isChecked = selectedValuesSet === null || selectedValuesSet.has(val);
                    
                    let formattedLabel = val;
                    if (currentFilterType === 'number' && val !== '(Trống / N/A)') {
                        const num = parseFloat(val);
                        if (!isNaN(num)) {
                            formattedLabel = (colKey === 'margin_percent' || colKey === 'payment_percent') 
                                ? num + '%' 
                                : (colKey === 'stt' ? num : formatNumberOnly(num));
                        }
                    }

                    const label = document.createElement('label');
                    label.className = 'flex items-center justify-between p-1 hover:bg-blue-50 rounded cursor-pointer text-gray-800 value-item';
                    label.dataset.value = val.toLowerCase();
                    label.innerHTML = `
                        <div class="flex items-center gap-2 truncate pr-2">
                            <input type="checkbox" value="${encodeURIComponent(val)}" class="val-checkbox rounded text-primary focus:ring-primary h-3.5 w-3.5" ${isChecked ? 'checked' : ''}>
                            <span class="truncate" title="${val}">${formattedLabel}</span>
                        </div>
                        <span class="text-[10px] text-gray-400 font-mono">(${count})</span>
                    `;
                    filterValueListContainer.appendChild(label);
                });
            }

            // Search inside Value Checkboxes
            filterValueSearchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const items = filterValueListContainer.querySelectorAll('.value-item');
                items.forEach(item => {
                    const text = item.dataset.value;
                    if (text.includes(query)) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });
            });

            // Value Selection Buttons
            btnSelectAllValues.addEventListener('click', () => {
                filterValueListContainer.querySelectorAll('.val-checkbox').forEach(cb => {
                    if (!cb.closest('.value-item').classList.contains('hidden')) cb.checked = true;
                });
            });
            btnClearSelectValues.addEventListener('click', () => {
                filterValueListContainer.querySelectorAll('.val-checkbox').forEach(cb => {
                    if (!cb.closest('.value-item').classList.contains('hidden')) cb.checked = false;
                });
            });
            btnInvertSelectValues.addEventListener('click', () => {
                filterValueListContainer.querySelectorAll('.val-checkbox').forEach(cb => {
                    if (!cb.closest('.value-item').classList.contains('hidden')) cb.checked = !cb.checked;
                });
            });

            // Populate Condition Operators
            function populateConditionOperators(colType, colKey) {
                filterConditionOperator.innerHTML = '';
                filterConditionValue1.value = '';
                filterConditionValue2.value = '';

                let operators = [];
                if (colType === 'number') {
                    operators = [
                        { val: 'all', text: '-- Chọn điều kiện số --' },
                        { val: 'gt_0', text: 'Có phát sinh (> 0)' },
                        { val: 'eq_0', text: 'Bằng 0 (= 0)' },
                        { val: 'eq', text: 'Bằng (=)' },
                        { val: 'neq', text: 'Không bằng (≠)' },
                        { val: 'gt', text: 'Lớn hơn (>)' },
                        { val: 'gte', text: 'Lớn hơn hoặc bằng (≥)' },
                        { val: 'lt', text: 'Nhỏ hơn (<)' },
                        { val: 'lte', text: 'Nhỏ hơn hoặc bằng (≤)' },
                        { val: 'between', text: 'Trong khoảng (Từ ... Đến ...)' },
                    ];
                } else {
                    operators = [
                        { val: 'all', text: '-- Chọn điều kiện tìm kiếm --' },
                        { val: 'contains', text: 'Chứa (Contains)' },
                        { val: 'not_contains', text: 'Không chứa' },
                        { val: 'equals', text: 'Bằng chính xác (=)' },
                        { val: 'starts_with', text: 'Bắt đầu bằng' },
                        { val: 'ends_with', text: 'Kết thúc bằng' },
                        { val: 'is_empty', text: 'Trống / Không có' },
                        { val: 'is_not_empty', text: 'Có dữ liệu (Không trống)' },
                    ];
                }

                operators.forEach(op => {
                    const opt = document.createElement('option');
                    opt.value = op.val;
                    opt.textContent = op.text;
                    filterConditionOperator.appendChild(opt);
                });

                // Load existing condition filter if active
                const savedFilter = activeFilters[colKey];
                if (savedFilter && savedFilter.type === 'condition') {
                    switchFilterSubtab('condition');
                    filterConditionOperator.value = savedFilter.condition.op;
                    filterConditionValue1.value = savedFilter.condition.val1 || '';
                    filterConditionValue2.value = savedFilter.condition.val2 || '';
                }

                updateConditionInputsVisibility();
            }

            function updateConditionInputsVisibility() {
                const op = filterConditionOperator.value;
                const hideInput1 = (op === 'all' || op === 'gt_0' || op === 'eq_0' || op === 'is_empty' || op === 'is_not_empty');
                const showInput2 = (op === 'between');

                document.getElementById('filterConditionInput1Wrapper').classList.toggle('hidden', hideInput1);
                filterConditionInput2Wrapper.classList.toggle('hidden', !showInput2);
            }

            filterConditionOperator.addEventListener('change', updateConditionInputsVisibility);

            // Sorting Handlers
            btnSortAsc.addEventListener('click', () => {
                applySort(currentFilterCol, 'asc');
                closeFilterPopover();
            });
            btnSortDesc.addEventListener('click', () => {
                applySort(currentFilterCol, 'desc');
                closeFilterPopover();
            });

            function applySort(colKey, direction) {
                activeSort = { colKey, direction };
                
                rowData.sort((a, b) => {
                    let valA = a[colKey];
                    let valB = b[colKey];

                    if (currentFilterType === 'number') {
                        const numA = parseFloat(valA) || 0;
                        const numB = parseFloat(valB) || 0;
                        return direction === 'asc' ? numA - numB : numB - numA;
                    }

                    valA = (valA || '').toString().toLowerCase();
                    valB = (valB || '').toString().toLowerCase();
                    return direction === 'asc' ? valA.localeCompare(valB, 'vi') : valB.localeCompare(valA, 'vi');
                });

                // Re-append sorted rows to tbody
                rowData.forEach(r => tbody.appendChild(r._tr));
                applyAllFilters();
            }

            // Apply Filter Button
            btnApplyFilter.addEventListener('click', () => {
                if (!currentFilterCol) return;

                const isConditionTab = !filterTabConditionContent.classList.contains('hidden');
                
                if (isConditionTab) {
                    const op = filterConditionOperator.value;
                    if (op === 'all') {
                        delete activeFilters[currentFilterCol];
                    } else {
                        activeFilters[currentFilterCol] = {
                            type: 'condition',
                            title: currentFilterTitle,
                            colType: currentFilterType,
                            condition: {
                                op: op,
                                val1: filterConditionValue1.value.trim(),
                                val2: filterConditionValue2.value.trim()
                            }
                        };
                    }
                } else {
                    const checkedBoxes = Array.from(filterValueListContainer.querySelectorAll('.val-checkbox:checked'));
                    const allBoxes = Array.from(filterValueListContainer.querySelectorAll('.val-checkbox'));

                    if (checkedBoxes.length === allBoxes.length || checkedBoxes.length === 0) {
                        // If all checked (or none), clear filter
                        delete activeFilters[currentFilterCol];
                    } else {
                        const selectedValues = new Set(checkedBoxes.map(cb => decodeURIComponent(cb.value)));
                        activeFilters[currentFilterCol] = {
                            type: 'value',
                            title: currentFilterTitle,
                            colType: currentFilterType,
                            values: selectedValues
                        };
                    }
                }

                closeFilterPopover();
                applyAllFilters();
            });

            // Clear Filter for Current Column
            btnClearColumnFilter.addEventListener('click', () => {
                if (currentFilterCol) {
                    delete activeFilters[currentFilterCol];
                    closeFilterPopover();
                    applyAllFilters();
                }
            });

            // Clear All Filters
            clearAllColumnFiltersBtn.addEventListener('click', () => {
                Object.keys(activeFilters).forEach(key => delete activeFilters[key]);
                activeSort = null;
                applyAllFilters();
            });

            // Main Filter Execution Engine
            function applyAllFilters() {
                let visibleCount = 0;
                
                // Accumulators for visible rows totals
                let sumRevenue = 0;
                let sumVat = 0;
                let sumTotal = 0;
                let sumGoodsCost = 0;
                let sumImplCost = 0;
                let sumContractorTax = 0;
                let sumFinanceCost = 0;
                let sumMgmtCost = 0;
                let sumSupport247 = 0;
                let sumOtherSupport = 0;
                let sumMargin = 0;
                let sumPaid = 0;

                rowData.forEach(row => {
                    let matches = true;

                    for (const [colKey, filter] of Object.entries(activeFilters)) {
                        const rawVal = row[colKey] !== undefined && row[colKey] !== null ? String(row[colKey]).trim() : '';
                        const displayVal = rawVal === '' ? '(Trống / N/A)' : rawVal;

                        if (filter.type === 'value') {
                            if (!filter.values.has(displayVal)) {
                                matches = false;
                                break;
                            }
                        } else if (filter.type === 'condition') {
                            const { op, val1, val2 } = filter.condition;
                            const numVal = parseFloat(rawVal) || 0;
                            const num1 = parseFloat(val1) || 0;
                            const num2 = parseFloat(val2) || 0;
                            const strVal = rawVal.toLowerCase();
                            const str1 = (val1 || '').toLowerCase();

                            if (op === 'gt_0' && numVal <= 0) matches = false;
                            else if (op === 'eq_0' && numVal !== 0) matches = false;
                            else if (op === 'eq' && numVal !== num1) matches = false;
                            else if (op === 'neq' && numVal === num1) matches = false;
                            else if (op === 'gt' && numVal <= num1) matches = false;
                            else if (op === 'gte' && numVal < num1) matches = false;
                            else if (op === 'lt' && numVal >= num1) matches = false;
                            else if (op === 'lte' && numVal > num1) matches = false;
                            else if (op === 'between' && (numVal < num1 || numVal > num2)) matches = false;
                            else if (op === 'contains' && !strVal.includes(str1)) matches = false;
                            else if (op === 'not_contains' && strVal.includes(str1)) matches = false;
                            else if (op === 'equals' && strVal !== str1) matches = false;
                            else if (op === 'starts_with' && !strVal.startsWith(str1)) matches = false;
                            else if (op === 'ends_with' && !strVal.endsWith(str1)) matches = false;
                            else if (op === 'is_empty' && rawVal !== '') matches = false;
                            else if (op === 'is_not_empty' && rawVal === '') matches = false;

                            if (!matches) break;
                        }
                    }

                    if (matches) {
                        row._tr.classList.remove('hidden');
                        visibleCount++;

                        // Sum totals
                        sumRevenue += parseFloat(row.revenue_before_vat) || 0;
                        sumVat += parseFloat(row.vat_amount) || 0;
                        sumTotal += parseFloat(row.total_amount_incl_vat) || 0;
                        sumGoodsCost += parseFloat(row.goods_cost) || 0;
                        sumImplCost += parseFloat(row.implementation_cost) || 0;
                        sumContractorTax += parseFloat(row.contractor_tax) || 0;
                        sumFinanceCost += parseFloat(row.finance_cost) || 0;
                        sumMgmtCost += parseFloat(row.management_cost) || 0;
                        sumSupport247 += parseFloat(row.support_247_cost) || 0;
                        sumOtherSupport += parseFloat(row.other_support_cost) || 0;
                        sumMargin += parseFloat(row.margin) || 0;
                        sumPaid += parseFloat(row.paid_amount) || 0;
                    } else {
                        row._tr.classList.add('hidden');
                    }
                });

                // Update Row Count
                marginDisplayCount.textContent = visibleCount;
                if (emptyFilterResultRow) {
                    emptyFilterResultRow.classList.toggle('hidden', visibleCount > 0 || totalRowCount === 0);
                }

                // Update Header Filter Button Active Highlights
                document.querySelectorAll('.excel-col-filter-btn').forEach(btn => {
                    const colKey = btn.dataset.col;
                    const isFiltered = !!activeFilters[colKey];
                    btn.classList.toggle('is-filtered', isFiltered);
                    if (isFiltered) {
                        btn.innerHTML = '<i class="fas fa-filter text-[11px] text-slate-900"></i>';
                    } else {
                        btn.innerHTML = '<i class="fas fa-filter text-[10px]"></i>';
                    }
                });

                // Render Filter Badges
                renderActiveFilterBadges();

                // Calculate Totals & Update Footer Row
                const marginPercent = sumRevenue > 0 ? (sumMargin / sumRevenue * 100).toFixed(1) : '0';
                const totalCost = sumGoodsCost + sumImplCost + sumContractorTax + sumFinanceCost + sumMgmtCost + sumSupport247 + sumOtherSupport;

                const footRevenue = document.getElementById('foot_revenue_before_vat');
                if (footRevenue) {
                    footRevenue.textContent = formatNumberOnly(sumRevenue);
                    document.getElementById('foot_vat_amount').textContent = formatNumberOnly(sumVat);
                    document.getElementById('foot_total_amount_incl_vat').textContent = formatNumberOnly(sumTotal);
                    document.getElementById('foot_goods_cost').textContent = formatNumberOnly(sumGoodsCost);
                    document.getElementById('foot_implementation_cost').textContent = formatNumberOnly(sumImplCost);
                    document.getElementById('foot_contractor_tax').textContent = formatNumberOnly(sumContractorTax);
                    document.getElementById('foot_finance_cost').textContent = formatNumberOnly(sumFinanceCost);
                    document.getElementById('foot_management_cost').textContent = formatNumberOnly(sumMgmtCost);
                    document.getElementById('foot_support_247_cost').textContent = formatNumberOnly(sumSupport247);
                    document.getElementById('foot_other_support_cost').textContent = formatNumberOnly(sumOtherSupport);
                    
                    const footMargin = document.getElementById('foot_margin');
                    footMargin.textContent = formatNumberOnly(sumMargin);
                    footMargin.className = `px-2 py-2.5 text-right border border-gray-300 font-mono ${sumMargin >= 0 ? 'text-green-700' : 'text-red-700'}`;

                    document.getElementById('foot_margin_percent').textContent = marginPercent + '%';
                    document.getElementById('foot_paid_amount').textContent = formatNumberOnly(sumPaid);
                }

                // Update Summary Stats Cards at Top
                const statRevenue = document.getElementById('stat_total_revenue');
                if (statRevenue) {
                    statRevenue.textContent = formatVND(sumRevenue);
                    document.getElementById('stat_total_orders').textContent = `${visibleCount} đơn hàng`;
                    document.getElementById('stat_total_profit').textContent = formatVND(sumMargin);
                    document.getElementById('stat_margin_percent').textContent = `${marginPercent}%`;
                    document.getElementById('stat_total_cost').textContent = formatVND(totalCost);
                }
            }

            function renderActiveFilterBadges() {
                activeFilterBadgesContainer.innerHTML = '';
                const filterKeys = Object.keys(activeFilters);

                clearAllColumnFiltersBtn.classList.toggle('hidden', filterKeys.length === 0);
                clearAllColumnFiltersBtn.classList.toggle('inline-flex', filterKeys.length > 0);

                filterKeys.forEach(colKey => {
                    const filter = activeFilters[colKey];
                    let labelText = '';

                    if (filter.type === 'value') {
                        const vals = Array.from(filter.values);
                        if (vals.length <= 2) {
                            labelText = vals.join(', ');
                        } else {
                            labelText = `${vals[0]}, +${vals.length - 1} mục`;
                        }
                    } else if (filter.type === 'condition') {
                        const { op, val1, val2 } = filter.condition;
                        if (op === 'gt_0') labelText = '> 0';
                        else if (op === 'eq_0') labelText = '= 0';
                        else if (op === 'between') labelText = `${val1} - ${val2}`;
                        else if (op === 'contains') labelText = `chứa "${val1}"`;
                        else labelText = `${op} ${val1}`;
                    }

                    const badge = document.createElement('span');
                    badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-900 border border-blue-300 shadow-2xs';
                    badge.innerHTML = `
                        <span><b>${filter.title}:</b> ${labelText}</span>
                        <button type="button" class="text-blue-700 hover:text-red-700 ml-0.5 focus:outline-none" title="Xóa lọc cột này">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    `;
                    badge.querySelector('button').onclick = () => {
                        delete activeFilters[colKey];
                        applyAllFilters();
                    };
                    activeFilterBadgesContainer.appendChild(badge);
                });
            }
        });
    </script>
@endsection
