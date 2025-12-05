@extends('layouts.app')

@section('title', 'Báo cáo xuất nhập')
@section('page-title', 'Báo Cáo Xuất Nhập Kho')

@section('content')
<div class="space-y-4">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" action="{{ route('reports.transaction-report') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại giao dịch</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="import" {{ request('type') == 'import' ? 'selected' : '' }}>Nhập kho</option>
                    <option value="export" {{ request('type') == 'export' ? 'selected' : '' }}>Xuất kho</option>
                    <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Chuyển kho</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kho</label>
                <select name="warehouse_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm">
                    <i class="fas fa-search mr-1"></i> Lọc
                </button>
                <a href="{{ route('reports.transaction-report') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                    <i class="fas fa-redo mr-1"></i> Đặt lại
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Tổng Giao Dịch</div>
            <div class="text-2xl font-bold">{{ number_format($totalTransactions) }}</div>
        </div>
        <div class="bg-green-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Nhập Kho</div>
            <div class="text-2xl font-bold">{{ number_format($importCount) }}</div>
        </div>
        <div class="bg-red-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Xuất Kho</div>
            <div class="text-2xl font-bold">{{ number_format($exportCount) }}</div>
        </div>
        <div class="bg-purple-500 text-white rounded-lg p-4">
            <div class="text-sm opacity-80">Chuyển Kho</div>
            <div class="text-2xl font-bold">{{ number_format($transferCount) }}</div>
        </div>
    </div>

    <!-- By Type -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Thống Kê Theo Loại</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số Giao Dịch</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng Số Lượng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($byType as $type => $data)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                @if($type === 'import')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Nhập kho</span>
                                @elseif($type === 'export')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Xuất kho</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Chuyển kho</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($data['count']) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($data['total_qty'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed Transactions -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Chi Tiết Giao Dịch</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhân viên</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('transactions.show', $transaction) }}" class="text-primary hover:underline font-medium">
                                    {{ $transaction->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @if($transaction->type === 'import')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $transaction->type_label }}</span>
                                @elseif($transaction->type === 'export')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">{{ $transaction->type_label }}</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">{{ $transaction->type_label }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $transaction->warehouse->name }}
                                @if($transaction->type === 'transfer')
                                    <span class="text-gray-400">→</span> {{ $transaction->toWarehouse->name }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $transaction->date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ number_format($transaction->total_qty, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $transaction->employee->name }}</td>
                            <td class="px-4 py-3">
                                @if($transaction->status === 'completed')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ $transaction->status_label }}</span>
                                @elseif($transaction->status === 'pending')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $transaction->status_label }}</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">{{ $transaction->status_label }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Không có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
