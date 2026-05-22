@extends('layouts.app')

@section('title', 'Chi tiết dự án')
@section('page-title', "Chi tiết dự án: {$project->code}")

@section('content')
<div class="space-y-6">
    <!-- Actions -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('projects.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium text-sm">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
        <a href="{{ route('projects.edit', $project->id) }}" 
           class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors font-medium text-sm shadow-sm">
            <i class="fas fa-edit mr-2"></i> Sửa
        </a>
        <a href="{{ route('sales.create', ['project_id' => $project->id]) }}" 
           class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-medium text-sm shadow-sm">
            <i class="fas fa-plus mr-2"></i> Tạo đơn hàng
        </a>
    </div>

    <!-- Statistics Cards Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Revenue -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Doanh thu</p>
                <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-coins text-blue-500 text-sm"></i>
                </div>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ number_format($salesStats['total_revenue']) }} đ</p>
            <p class="text-xs text-gray-400 mt-1">{{ $salesStats['total_orders'] }} đơn hàng</p>
        </div>

        <!-- Cost -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Giá vốn</p>
                <div class="w-9 h-9 bg-orange-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-orange-500 text-sm"></i>
                </div>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ number_format($salesStats['total_cost']) }} đ</p>
        </div>

        <!-- Profit -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Lợi nhuận</p>
                <div class="w-9 h-9 {{ $salesStats['profit'] >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line {{ $salesStats['profit'] >= 0 ? 'text-green-500' : 'text-red-500' }} text-sm"></i>
                </div>
            </div>
            <p class="text-xl font-bold {{ $salesStats['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($salesStats['profit']) }} đ</p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($salesStats['profit_percent'], 2) }}% margin</p>
        </div>

        <!-- Budget Progress or Debt -->
        @if($project->budget > 0)
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Dự toán vs Thực tế</p>
            </div>
            @php
                $budgetPercent = min(($salesStats['total_revenue'] / $project->budget) * 100, 100);
            @endphp
            <p class="text-xl font-bold text-gray-900">{{ number_format($budgetPercent, 1) }}%</p>
            <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                <div class="h-2 rounded-full {{ $budgetPercent >= 100 ? 'bg-green-500' : 'bg-blue-500' }} transition-all" 
                     style="width: {{ $budgetPercent }}%"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-400 mt-1.5">
                <span>{{ number_format($salesStats['total_revenue']) }} đ</span>
                <span>{{ number_format($project->budget) }} đ</span>
            </div>
        </div>
        @else
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Công nợ</p>
                <div class="w-9 h-9 {{ $salesStats['total_debt'] > 0 ? 'bg-red-50' : 'bg-green-50' }} rounded-lg flex items-center justify-center">
                    <i class="fas {{ $salesStats['total_debt'] > 0 ? 'fa-file-invoice-dollar text-red-500' : 'fa-check-circle text-green-500' }} text-sm"></i>
                </div>
            </div>
            <p class="text-xl font-bold {{ $salesStats['total_debt'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($salesStats['total_debt']) }} đ</p>
            @if($salesStats['total_debt'] == 0)
                <p class="text-xs text-green-500 mt-1">Không có công nợ</p>
            @endif
        </div>
        @endif
    </div>

    <!-- Project Information Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Section A: Distributor Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-building mr-2 text-blue-500"></i> Thông tin Nhà phân phối
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3.5">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Vendor</span>
                        <span class="text-sm font-medium text-gray-800">{{ $project->vendor?->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Distributor</span>
                        <span class="text-sm font-medium text-gray-800">Tech Horizon Corporation</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-gray-500">Distributor AM</span>
                        <span class="text-sm font-medium text-gray-800">{{ $project->distributor_am ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section B: End-User Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-user-tie mr-2 text-green-500"></i> Thông tin End-User
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tên tiếng Việt</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->eu_name_vi ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tên tiếng Anh</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->eu_name_en ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tên viết tắt</p>
                        <p class="text-sm text-gray-700">{{ $project->eu_name_abbr ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">MST / Website</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->eu_tax_code ?? '-' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400 mb-1">Địa chỉ</p>
                        <p class="text-sm text-gray-700">{{ $project->address ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tỉnh / Thành phố</p>
                        <p class="text-sm text-gray-700">{{ $project->eu_province ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Ngành nghề</p>
                        <p class="text-sm text-gray-700">{{ $project->eu_industry ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section C: Collaboration -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-handshake mr-2 text-purple-500"></i> Thông tin Hợp tác
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Loại hợp tác</p>
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ $project->collaborate_type == 'partner' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $project->collaborate_type == 'partner' ? 'Partner' : 'End-user' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tên công ty</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->collaborate_company ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Mã số thuế</p>
                        <p class="text-sm text-gray-700">{{ $project->collaborate_tax_code ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Người liên hệ (PIC)</p>
                        <p class="text-sm text-gray-700">
                            @if($project->collaborate_pic_name)
                                <span class="font-medium">{{ $project->collaborate_pic_name }}</span>
                                @if($project->collaborate_pic_title)
                                    <span class="text-gray-400 mx-1">|</span>{{ $project->collaborate_pic_title }}
                                @endif
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">SĐT PIC</p>
                        <p class="text-sm text-gray-700">
                            @if($project->collaborate_pic_phone)
                                <a href="tel:{{ $project->collaborate_pic_phone }}" class="text-blue-600 hover:underline">{{ $project->collaborate_pic_phone }}</a>
                            @else - @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Email PIC</p>
                        <p class="text-sm text-gray-700">
                            @if($project->collaborate_pic_email)
                                <a href="mailto:{{ $project->collaborate_pic_email }}" class="text-blue-600 hover:underline">{{ $project->collaborate_pic_email }}</a>
                            @else - @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section D: Project Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-project-diagram mr-2 text-orange-500"></i> Thông tin Dự án
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Mã dự án</p>
                        <p class="text-sm font-bold text-gray-900">{{ $project->code }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Trạng thái</p>
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ $project->status_color }}">
                            {{ $project->status_label }}
                        </span>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400 mb-1">Tên dự án</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Ngày hết hạn (Expired Date)</p>
                        <p class="text-sm text-gray-700">
                            {{ $project->end_date?->format('d/m/Y') ?? '-' }}
                            @if($project->estimated_close_months)
                                <span class="text-xs text-gray-400 ml-1">(+{{ $project->estimated_close_months }}M)</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Loại Deal</p>
                        @if($project->deal_type)
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-700">
                                {{ $project->deal_type == 'new_buy' ? 'New Buy' : 'Trade Up' }}
                            </span>
                        @else
                            <p class="text-sm text-gray-400">-</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Net to Tech Horizon</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->net_to_tech_horizon ? number_format($project->net_to_tech_horizon) . ' đ' : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Dự toán / Ngân sách</p>
                        <p class="text-sm font-medium text-gray-800">{{ number_format($project->budget) }} đ</p>
                    </div>
                    @if($project->stage)
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400 mb-1">Giai đoạn (Stage)</p>
                        <p class="text-sm text-gray-700">{{ $project->stage }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- BOM Section -->
    @if($project->bom_file || $project->bom_data)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                <i class="fas fa-file-alt mr-2 text-blue-500"></i> BOM (Bill of Materials)
            </h3>
            @if($project->bom_file)
                <a href="{{ Storage::url($project->bom_file) }}" target="_blank"
                    class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-download mr-1.5"></i> Tải file BOM
                </a>
            @endif
        </div>
        @if($project->bom_data)
        <div class="p-6">
            <div class="text-sm bg-gray-50 p-4 rounded-lg border border-gray-200 whitespace-pre-line text-gray-700">{{ $project->bom_data }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Description & Note -->
    @if($project->description || $project->note)
    <div class="grid grid-cols-1 {{ $project->description && $project->note ? 'lg:grid-cols-2' : '' }} gap-6">
        @if($project->description)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center mb-3">
                <i class="fas fa-align-left mr-2 text-gray-400"></i> Mô tả
            </h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $project->description }}</p>
        </div>
        @endif
        @if($project->note)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center mb-3">
                <i class="fas fa-sticky-note mr-2 text-gray-400"></i> Ghi chú
            </h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $project->note }}</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Export Materials Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Vật tư đã xuất cho dự án</h3>
                <p class="text-sm text-gray-500 mt-0.5">
                    Tổng giá trị: <span class="font-semibold text-orange-600">{{ number_format($exportStats['total_export_value']) }} đ</span>
                    <span class="text-gray-300 mx-2">|</span>
                    {{ $exportStats['total_exports'] }} phiếu xuất
                </p>
            </div>
            <a href="{{ route('exports.index', ['project_id' => $project->id]) }}" class="text-sm text-primary hover:underline font-medium">
                Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-8"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày xuất</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho xuất</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentExports as $index => $export)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <button type="button" onclick="toggleExportDetails({{ $index }})" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-chevron-right transition-transform" id="icon-{{ $index }}"></i>
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('exports.show', $export->id) }}" class="font-medium text-orange-600 hover:underline">
                                {{ $export->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $export->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $export->warehouse->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-sm font-semibold bg-orange-100 text-orange-800 rounded">
                                {{ number_format($export->total_qty) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($export->status === 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('exports.show', $export->id) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <tr id="details-{{ $index }}" class="hidden bg-gray-50">
                        <td colspan="7" class="px-4 py-4">
                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-boxes text-orange-500 mr-2"></i>Chi tiết sản phẩm
                                </h4>
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mã SP</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($export->items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2">
                                                <span class="font-mono text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                                    {{ $item->product->code ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ $item->product->name ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 text-xs font-bold bg-orange-100 text-orange-800 rounded-full">
                                                    {{ number_format($item->quantity) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-500">{{ $item->comments ?: '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Chưa có phiếu xuất vật tư nào cho dự án này</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function toggleExportDetails(index) {
        const detailsRow = document.getElementById('details-' + index);
        const icon = document.getElementById('icon-' + index);
        if (detailsRow.classList.contains('hidden')) {
            detailsRow.classList.remove('hidden');
            icon.classList.add('rotate-90');
        } else {
            detailsRow.classList.add('hidden');
            icon.classList.remove('rotate-90');
        }
    }
    </script>

    <!-- Recent Sales -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-base font-semibold text-gray-900">Đơn hàng của dự án</h3>
            <a href="{{ route('sales.index', ['project_id' => $project->id]) }}" class="text-sm text-primary hover:underline font-medium">
                Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Công nợ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentSales as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('sales.show', $sale->id) }}" class="font-medium text-primary hover:underline">
                                {{ $sale->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $sale->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($sale->total) }} đ</td>
                        <td class="px-4 py-3 text-sm text-right {{ $sale->debt_amount > 0 ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                            {{ number_format($sale->debt_amount) }} đ
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                                {{ $sale->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('sales.show', $sale->id) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            Chưa có đơn hàng nào cho dự án này
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
