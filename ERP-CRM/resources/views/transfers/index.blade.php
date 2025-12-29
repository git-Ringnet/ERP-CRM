@extends('layouts.app')

@section('title', 'Quản lý Chuyển kho')
@section('page-title', 'Quản lý Chuyển kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-exchange-alt text-purple-500 mr-2"></i>Danh sách phiếu chuyển kho
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('transfers.export', request()->query()) }}" 
                   class="inline-flex items-center px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </a>
                <a href="{{ route('transfers.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Tạo phiếu chuyển
                </a>
            </div>
        </div>
    </div>

    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <form action="{{ route('transfers.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Tìm theo mã phiếu..." 
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
            </div>
            <div>
                <select name="warehouse_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Tất cả kho --</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                </select>
            </div>
            <div>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
            </div>
            <div class="flex gap-2">
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho nguồn</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho đích</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày chuyển</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transfers as $transfer)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('transfers.show', $transfer) }}" class="text-purple-600 hover:text-purple-800 font-medium">
                            {{ $transfer->code }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $transfer->warehouse->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $transfer->toWarehouse->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $transfer->date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 text-sm font-semibold bg-purple-100 text-purple-800 rounded">
                            {{ number_format($transfer->total_qty) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($transfer->status === 'pending')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('transfers.show', $transfer) }}" 
                               class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($transfer->status === 'pending')
                                <a href="{{ route('transfers.edit', $transfer) }}" 
                                   class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmApprove('{{ route('transfers.approve', $transfer) }}', 'phiếu chuyển kho')" 
                                        class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200" title="Duyệt">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="confirmReject('{{ route('transfers.reject', $transfer) }}', 'phiếu chuyển kho')" 
                                        class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200" title="Từ chối">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Chưa có phiếu chuyển kho nào.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transfers->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $transfers->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
