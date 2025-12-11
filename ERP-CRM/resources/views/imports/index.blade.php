@extends('layouts.app')

@section('title', 'Quản lý Nhập kho')
@section('page-title', 'Quản lý Nhập kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-arrow-down text-blue-500 mr-2"></i>Danh sách phiếu nhập kho
            </h2>
            <a href="{{ route('imports.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                <i class="fas fa-plus mr-2"></i>Tạo phiếu nhập
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <form action="{{ route('imports.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Tìm theo mã phiếu..." 
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
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
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" placeholder="Từ ngày">
            </div>
            <div class="flex gap-2">
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg" placeholder="Đến ngày">
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho nhập</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày nhập</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhân viên</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($imports as $import)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('imports.show', $import) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            {{ $import->code }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $import->warehouse->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $import->date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 text-sm font-semibold bg-blue-100 text-blue-800 rounded">
                            {{ number_format($import->total_qty) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($import->status === 'pending')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $import->employee->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('imports.show', $import) }}" 
                               class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($import->status === 'pending')
                                <a href="{{ route('imports.edit', $import) }}" 
                                   class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('imports.approve', $import) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Bạn có chắc muốn duyệt phiếu này?')">
                                    @csrf
                                    <button type="submit" class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200" title="Duyệt">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form action="{{ route('imports.destroy', $import) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa phiếu này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Chưa có phiếu nhập kho nào.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($imports->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $imports->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
