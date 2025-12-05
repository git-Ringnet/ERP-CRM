@extends('layouts.app')

@section('title', 'Xuất nhập kho')
@section('page-title', 'Quản lý Xuất nhập kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-3 sm:p-4 border-b border-gray-200 space-y-3">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <form action="{{ route('transactions.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm theo mã giao dịch..." 
                           class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </form>
            </div>
            
            <!-- Export Button -->
            <a href="{{ route('transactions.export.csv', request()->all()) }}" class="px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm flex items-center gap-1">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            
            <!-- Filter by Type -->
            <select name="type" onchange="window.location.href='{{ route('transactions.index') }}?type='+this.value+'&warehouse_id={{ request('warehouse_id') }}&status={{ request('status') }}'" 
                    class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả loại</option>
                <option value="import" {{ request('type') == 'import' ? 'selected' : '' }}>Nhập kho</option>
                <option value="export" {{ request('type') == 'export' ? 'selected' : '' }}>Xuất kho</option>
                <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Chuyển kho</option>
            </select>

            <!-- Filter by Warehouse -->
            <select name="warehouse_id" onchange="window.location.href='{{ route('transactions.index') }}?warehouse_id='+this.value+'&type={{ request('type') }}&status={{ request('status') }}'" 
                    class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả kho</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>

            <!-- Filter by Status -->
            <select name="status" onchange="window.location.href='{{ route('transactions.index') }}?status='+this.value+'&type={{ request('type') }}&warehouse_id={{ request('warehouse_id') }}'" 
                    class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
            </select>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('transactions.create', ['type' => 'import']) }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm">
                <i class="fas fa-arrow-down mr-2"></i>Nhập kho
            </a>
            <a href="{{ route('transactions.create', ['type' => 'export']) }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm">
                <i class="fas fa-arrow-up mr-2"></i>Xuất kho
            </a>
            <a href="{{ route('transactions.create', ['type' => 'transfer']) }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors text-sm">
                <i class="fas fa-exchange-alt mr-2"></i>Chuyển kho
            </a>
        </div>
    </div>

    <!-- Table - Desktop View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã GD</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kho</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số lượng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nhân viên</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $transaction->code }}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $transaction->type_color }}-100 text-{{ $transaction->type_color }}-800">
                            {{ $transaction->type_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                        {{ $transaction->warehouse->name }}
                        @if($transaction->type === 'transfer' && $transaction->toWarehouse)
                            <br><i class="fas fa-arrow-right text-xs"></i> {{ $transaction->toWarehouse->name }}
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $transaction->date->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ number_format($transaction->total_qty) }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $transaction->employee?->name ?? '-' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $transaction->status_color }}-100 text-{{ $transaction->status_color }}-800">
                            {{ $transaction->status_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('transactions.show', $transaction->id) }}" 
                               class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($transaction->status === 'pending')
                                <a href="{{ route('transactions.edit', $transaction->id) }}" 
                                   class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" 
                                   title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('transactions.approve', $transaction->id) }}" 
                                      method="POST" class="inline"
                                      onsubmit="return confirm('Bạn có chắc muốn duyệt phiếu này? Sau khi duyệt sẽ không thể chỉnh sửa.')">
                                    @csrf
                                    <button type="submit" 
                                            class="p-2 text-green-600 bg-green-50 rounded-lg hover:bg-green-100 hover:text-green-700 transition-colors" 
                                            title="Duyệt phiếu">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form action="{{ route('transactions.destroy', $transaction->id) }}" 
                                      method="POST" class="inline"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa phiếu này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors" 
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-exchange-alt text-4xl mb-2"></i>
                        <p>Không có dữ liệu giao dịch</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card View - Mobile -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($transactions as $transaction)
        <div class="p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ $transaction->code }}</div>
                    <div class="text-sm text-gray-500">{{ $transaction->date->format('d/m/Y') }}</div>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $transaction->type_color }}-100 text-{{ $transaction->type_color }}-800">
                    {{ $transaction->type_label }}
                </span>
            </div>
            <div class="space-y-1 text-sm text-gray-600 mb-3">
                <div><i class="fas fa-warehouse w-4"></i> {{ $transaction->warehouse->name }}</div>
                @if($transaction->type === 'transfer' && $transaction->toWarehouse)
                    <div><i class="fas fa-arrow-right w-4"></i> {{ $transaction->toWarehouse->name }}</div>
                @endif
                <div><i class="fas fa-boxes w-4"></i> {{ number_format($transaction->total_qty) }} sản phẩm</div>
                @if($transaction->employee)
                    <div><i class="fas fa-user w-4"></i> {{ $transaction->employee->name }}</div>
                @endif
            </div>
            <div class="flex gap-2 items-center">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $transaction->status_color }}-100 text-{{ $transaction->status_color }}-800">
                    {{ $transaction->status_label }}
                </span>
                <div class="ml-auto flex gap-2">
                    <a href="{{ route('transactions.show', $transaction->id) }}" 
                       class="px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-sm">
                        <i class="fas fa-eye mr-1"></i>Xem
                    </a>
                    @if($transaction->status === 'pending')
                        <a href="{{ route('transactions.edit', $transaction->id) }}" 
                           class="px-3 py-2 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition-colors text-sm">
                            <i class="fas fa-edit mr-1"></i>Sửa
                        </a>
                        <form action="{{ route('transactions.approve', $transaction->id) }}" 
                              method="POST" class="inline"
                              onsubmit="return confirm('Bạn có chắc muốn duyệt phiếu này? Sau khi duyệt sẽ không thể chỉnh sửa.')">
                            @csrf
                            <button type="submit" class="px-3 py-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors text-sm">
                                <i class="fas fa-check mr-1"></i>Duyệt
                            </button>
                        </form>
                        <form action="{{ route('transactions.destroy', $transaction->id) }}" 
                              method="POST" class="inline"
                              onsubmit="return confirm('Bạn có chắc muốn xóa phiếu này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition-colors text-sm">
                                <i class="fas fa-trash mr-1"></i>Xóa
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-exchange-alt text-4xl mb-2"></i>
            <p>Không có dữ liệu giao dịch</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($transactions->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $transactions->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
