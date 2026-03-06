@extends('layouts.app')

@section('title', 'Đối soát Tồn kho')
@section('page-title', 'Đối soát Tồn kho')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-2">
                    <a href="{{ route('reconciliation.index') }}" class="hover:text-blue-600">Đối soát</a>
                    <i class="fas fa-chevron-right mx-2 text-xs"></i>
                    <span class="text-gray-800">Tồn kho</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-boxes text-purple-600 mr-2"></i>Đối soát Tồn kho</h2>
            </div>
            <a href="{{ route('reconciliation.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"><i class="fas fa-arrow-left mr-2"></i> Quay lại</a>
        </div>
        <form method="GET" class="mt-4 flex items-end gap-4 flex-wrap">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kho hàng</label>
                <select name="warehouse_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-[200px]">
                    <option value="">-- Tất cả kho --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ ($filters['warehouse_id'] ?? '') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"><i class="fas fa-filter mr-2"></i> Lọc</button>
            <a href="{{ route('reconciliation.inventory') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"><i class="fas fa-redo mr-2"></i> Xóa lọc</a>
        </form>
    </div>

    {{-- Stock vs Product Items --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-cubes text-purple-500 mr-2"></i>Lệch tồn kho và chi tiết mã vạch/Serial
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['stock_vs_items']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ count($results['stock_vs_items']) }}</span>
                <i class="fas fa-info-circle text-blue-400 ml-2 cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Con số "Tồn kho tổng" của sản phẩm không bằng tổng số lượng các "Mã vạch/Serial" đang có trong kho. Cần kiểm tra xem có mã vạch nào bị xóa nhầm hoặc chưa được đăng ký không.</p>
        </div>
        @if(count($results['stock_vs_items']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ghi nhận</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thực tế</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lệch</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['stock_vs_items'] as $item)
                    <tr class="hover:bg-purple-50">
                        <td class="px-4 py-3 text-sm font-medium">{{ $item['product_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['warehouse_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $item['recorded_stock'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-600">{{ $item['actual_stock'] }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">{{ $item['difference'] > 0 ? '+' : '' }}{{ $item['difference'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500"><i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i><p>OK</p></div>
        @endif
    </div>

    {{-- Stock vs Transactions --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ showHelp: false }">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-exchange-alt text-indigo-500 mr-2"></i>Tồn kho vs Nhập/Xuất/Chuyển
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ count($results['stock_vs_transactions']) > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ count($results['stock_vs_transactions']) }}</span>
                <i class="fas fa-info-circle text-blue-400 ml-2 Poi cursor-pointer hover:text-blue-600 transition-colors" @click="showHelp = !showHelp"></i>
            </h3>
        </div>
        <div x-show="showHelp" x-transition class="p-4 bg-blue-50 border-b border-blue-100 text-sm text-blue-800">
            <p><strong>Hướng dẫn:</strong> Con số tồn kho hiện tại không khớp với phép tính: <code>Tồn đầu kỳ + Nhập - Xuất ± Chuyển kho</code>. Điều này thường do một số phiếu kho chưa được hạch toán đúng vào bảng tồn kho.</p>
        </div>
        @if(count($results['stock_vs_transactions']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ghi nhận</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tính toán</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nhập</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Xuất</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Vào</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ra</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lệch</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results['stock_vs_transactions'] as $item)
                    <tr class="hover:bg-indigo-50">
                        <td class="px-4 py-3 text-sm font-medium">{{ $item['product_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $item['warehouse_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $item['recorded_stock'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-blue-600">{{ $item['calculated_stock'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600">+{{ $item['total_imported'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-red-600">-{{ $item['total_exported'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600">+{{ $item['total_transfer_in'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-red-600">-{{ $item['total_transfer_out'] }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">{{ $item['difference'] > 0 ? '+' : '' }}{{ $item['difference'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500"><i class="fas fa-check-circle text-green-400 text-3xl mb-2"></i><p>OK</p></div>
        @endif
    </div>
</div>
@endsection
