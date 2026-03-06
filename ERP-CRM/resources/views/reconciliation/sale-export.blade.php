@extends('layouts.app')

@section('title', 'Đối soát Bán hàng ↔ Xuất kho')
@section('page-title', 'Đối soát Bán hàng ↔ Xuất kho')

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb & Header --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-2">
                    <a href="{{ route('reconciliation.index') }}" class="hover:text-blue-600">Đối soát</a>
                    <i class="fas fa-chevron-right mx-2 text-xs"></i>
                    <span class="text-gray-800">Bán hàng ↔ Xuất kho</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-shopping-cart text-blue-600 mr-2"></i>
                    Đối soát Bán hàng ↔ Xuất kho
                </h2>
            </div>
            <a href="{{ route('reconciliation.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>

        {{-- Filter Form --}}
        <form method="GET" class="mt-4 flex items-end gap-4 flex-wrap">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-filter mr-2"></i> Lọc
            </button>
            <a href="{{ route('reconciliation.sale-export') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-redo mr-2"></i> Xóa lọc
            </a>
        </form>
    </div>

    {{-- Missing Exports --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between transition-all duration-300">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                Đơn bán đã duyệt nhưng chưa hoàn thành xuất kho
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['missing_exports']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ count($results['missing_exports']) }}
                </span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Đơn hàng đã được phê duyệt nhưng chưa có phiếu xuất kho ở trạng thái "Hoàn thành". Có thể do phiếu xuất chưa được tạo, hoặc mới chỉ ở trạng thái "Chờ xử lý" (Pending).</p>
        </div>
        @if(count($results['missing_exports']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SL sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vấn đề</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['missing_exports'] as $item)
                    <tr class="hover:bg-red-50">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('sales.show', $item['sale_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['sale_code'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['customer_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['date'] }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $item['status_label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($item['total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $item['total_items'] }}</td>
                        <td class="px-4 py-3 text-sm text-red-600">{{ $item['issue'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i>
            <p>Tất cả đơn bán đã duyệt đều có phiếu xuất kho</p>
        </div>
        @endif
    </div>

    {{-- Quantity Mismatches --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-not-equal text-orange-500 mr-2"></i>
                Số lượng xuất kho không khớp với đơn bán
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['quantity_mismatches']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ count($results['quantity_mismatches']) }}
                </span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Tổng số lượng trên tất cả các phiếu xuất liên kết (dù là đang xử lý hay hoàn thành) không khớp với số lượng sản phẩm được đặt trong đơn hàng bán.</p>
        </div>
        @if(count($results['quantity_mismatches']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chi tiết lệch</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vấn đề</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['quantity_mismatches'] as $item)
                    <tr class="hover:bg-orange-50">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('sales.show', $item['sale_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['sale_code'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['customer_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['date'] }}</td>
                        <td class="px-4 py-3 text-sm">
                            @foreach($item['product_mismatches'] as $pm)
                            <div class="text-xs mb-1">
                                <span class="font-medium">SP #{{ $pm['product_id'] }}:</span>
                                Bán <span class="text-blue-600">{{ $pm['sale_qty'] }}</span> /
                                Xuất <span class="text-orange-600">{{ $pm['export_qty'] }}</span>
                                (lệch <span class="text-red-600 font-semibold">{{ $pm['difference'] > 0 ? '+' : '' }}{{ $pm['difference'] }}</span>)
                            </div>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-sm text-red-600">{{ $item['issue'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i>
            <p>Tất cả số lượng xuất kho đều khớp với đơn bán</p>
        </div>
        @endif
    </div>

    {{-- Orphan Exports --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-unlink text-red-500 mr-2"></i>
                Phiếu xuất kho bất thường (Lệch trạng thái)
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['mismatched_exports']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ count($results['mismatched_exports']) }}
                </span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Các trường hợp phiếu xuất và đơn hàng không đồng bộ trạng thái:
                <ul class="list-disc ml-5 mt-1">
                    <li>Phiếu xuất còn (Chờ xử lý/Hoàn thành) nhưng đơn hàng đã bị <strong>Hủy</strong> (Phiếu dư).</li>
                    <li>Phiếu xuất bị <strong>Từ chối/Hủy</strong> nhưng đơn hàng vẫn đang ở trạng thái hoạt động (Duyệt/Giao hàng).</li>
                </ul>
            </p>
        </div>
        @if(count($results['mismatched_exports']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PX</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn bán gốc</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng SL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vấn đề</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['mismatched_exports'] as $item)
                    <tr class="hover:bg-red-50">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('exports.show', $item['export_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['export_code'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['sale_code'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['warehouse_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['date'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $item['total_qty'] }}</td>
                        <td class="px-4 py-3 text-sm text-red-600">{{ $item['issue'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i>
            <p>Không có phiếu xuất kho bất thường</p>
        </div>
        @endif
    </div>
</div>
@endsection
