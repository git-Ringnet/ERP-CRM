@extends('layouts.app')

@section('title', 'Đối soát Mua hàng ↔ Nhập kho')
@section('page-title', 'Đối soát Mua hàng ↔ Nhập kho')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-2">
                    <a href="{{ route('reconciliation.index') }}" class="hover:text-blue-600">Đối soát</a>
                    <i class="fas fa-chevron-right mx-2 text-xs"></i>
                    <span class="text-gray-800">Mua hàng ↔ Nhập kho</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-file-contract text-orange-600 mr-2"></i>
                    Đối soát Mua hàng ↔ Nhập kho
                </h2>
            </div>
            <a href="{{ route('reconciliation.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>

        <form method="GET" class="mt-4 flex items-end gap-4 flex-wrap">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                <i class="fas fa-filter mr-2"></i> Lọc
            </button>
            <a href="{{ route('reconciliation.purchase-import') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-redo mr-2"></i> Xóa lọc
            </a>
        </form>
    </div>

    {{-- Missing Imports --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                PO đã nhận hàng nhưng chưa hoàn thành nhập kho
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['missing_imports']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ count($results['missing_imports']) }}
                </span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Đơn mua (PO) đã ở trạng thái "Đã nhận hàng" nhưng hệ thống chưa thấy phiếu nhập kho tương ứng ở trạng thái "Hoàn thành". Có thể do phiếu nhập chưa được tạo, hoặc mới chỉ ở trạng thái "Chờ xử lý".</p>
        </div>
        @if(count($results['missing_imports']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhà cung cấp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SL sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vấn đề</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['missing_imports'] as $item)
                    <tr class="hover:bg-red-50">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('purchase-orders.show', $item['po_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['po_code'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['supplier_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['date'] }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $item['status_label'] }}</span>
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
            <p>Tất cả PO đã nhận đều có phiếu nhập kho</p>
        </div>
        @endif
    </div>

    {{-- Quantity Mismatches --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-not-equal text-orange-500 mr-2"></i>
                Số lượng nhập kho không khớp với PO
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['quantity_mismatches']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ count($results['quantity_mismatches']) }}
                </span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Tổng số lượng trên tất cả các phiếu nhập kho liên kết không khớp với số lượng sản phẩm được đặt trong đơn mua hàng (PO).</p>
        </div>
        @if(count($results['quantity_mismatches']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NCC</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chi tiết lệch</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vấn đề</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['quantity_mismatches'] as $item)
                    <tr class="hover:bg-orange-50">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('purchase-orders.show', $item['po_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['po_code'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['supplier_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['date'] }}</td>
                        <td class="px-4 py-3 text-sm">
                            @foreach($item['product_mismatches'] as $pm)
                            <div class="text-xs mb-1">
                                <span class="font-medium">SP #{{ $pm['product_id'] }}:</span>
                                Đặt <span class="text-blue-600">{{ $pm['po_qty'] }}</span> /
                                Nhập <span class="text-orange-600">{{ $pm['import_qty'] }}</span>
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
            <p>Tất cả số lượng nhập kho đều khớp với PO</p>
        </div>
        @endif
    </div>

    {{-- Orphan Imports --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-unlink text-red-500 mr-2"></i>
                Phiếu nhập kho bất thường (Lệch trạng thái)
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['mismatched_imports']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ count($results['mismatched_imports']) }}
                </span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Các trường hợp phiếu nhập và PO không đồng bộ trạng thái:
                <ul class="list-disc ml-5 mt-1">
                    <li>Phiếu nhập còn (Chờ xử lý/Hoàn thành) nhưng PO đã bị <strong>Hủy</strong>.</li>
                    <li>Phiếu nhập bị <strong>Từ chối/Hủy</strong> nhưng PO vẫn đang ở trạng thái đã nhận hàng.</li>
                </ul>
            </p>
        </div>
        @if(count($results['mismatched_imports']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PN</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO gốc</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng SL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vấn đề</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['mismatched_imports'] as $item)
                    <tr class="hover:bg-red-50">
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('imports.show', $item['import_id']) }}" class="text-blue-600 hover:underline font-medium">{{ $item['import_code'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $item['po_code'] }}</td>
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
            <p>Không có phiếu nhập kho bất thường</p>
        </div>
        @endif
    </div>
</div>
@endsection
