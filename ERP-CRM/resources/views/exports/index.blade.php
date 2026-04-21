@extends('layouts.app')

@section('title', 'Quản lý Xuất kho')
@section('page-title', 'Quản lý Xuất kho')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-arrow-up text-orange-500 mr-2"></i>Danh sách phiếu xuất kho
                </h2>
                <div class="flex gap-2">

                    <a href="{{ route('exports.export', request()->query()) }}"
                        class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                    </a>
                    <a href="{{ route('exports.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Tạo phiếu xuất
                    </a>
                </div>
            </div>
        </div>

        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <form action="{{ route('exports.index') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-x-4 gap-y-3 items-end">
                    <!-- Search -->
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tìm kiếm</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-search text-xs"></i>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo mã phiếu..."
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    <!-- Warehouse -->
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Kho xuất</label>
                        <select name="warehouse_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all appearance-none cursor-pointer bg-white">
                            <option value="">-- Tất cả kho --</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Project -->
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Dự án</label>
                        <select name="project_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all appearance-none cursor-pointer bg-white">
                            <option value="">-- Tất cả dự án --</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                    {{ $project->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Customer -->
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Khách hàng</label>
                        <select name="customer_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all appearance-none cursor-pointer bg-white">
                            <option value="">-- Tất cả khách hàng --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Trạng thái</label>
                        <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all appearance-none cursor-pointer bg-white">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Đã từ chối</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Từ ngày</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-calendar-alt text-xs"></i>
                            </span>
                            <input type="text" id="date_from" name="date_from" value="{{ request('date_from') }}" placeholder="d/m/Y"
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all">
                        </div>
                    </div>

                    <!-- Date To -->
                    <div class="flex flex-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Đến ngày</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-calendar-alt text-xs"></i>
                            </span>
                            <input type="text" id="date_to" name="date_to" value="{{ request('date_to') }}" placeholder="d/m/Y"
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2 h-[38px]">
                        <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm font-medium shadow-sm">
                            <i class="fas fa-filter mr-1"></i> Lọc
                        </button>
                        <a href="{{ route('exports.index') }}" class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm font-medium">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>


        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dự án / Khách hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày xuất</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhân viên</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($exports as $export)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('exports.show', $export) }}"
                                    class="text-orange-600 hover:text-orange-800 font-medium">
                                    {{ $export->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($export->project)
                                    <div class="flex items-center gap-1">
                                        <span class="px-1.5 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 rounded">Dự
                                            án</span>
                                        <a href="{{ route('projects.show', $export->project) }}"
                                            class="text-blue-600 hover:text-blue-800 hover:underline">
                                            {{ $export->project->code }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ Str::limit($export->project->name, 30) }}</div>
                                @elseif($export->customer)
                                    <div class="flex items-center gap-1">
                                        <span
                                            class="px-1.5 py-0.5 text-xs font-semibold bg-green-100 text-green-700 rounded">KH</span>
                                        <a href="{{ route('customers.show', $export->customer) }}"
                                            class="text-green-600 hover:text-green-800 hover:underline">
                                            {{ $export->customer->code }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ Str::limit($export->customer->name, 30) }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                             <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $export->date ? $export->date->format('d/m/Y') : '-' }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-sm font-semibold bg-orange-100 text-orange-800 rounded">
                                    {{ number_format($export->total_qty) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span class="text-sm font-bold text-blue-700">
                                    {{ number_format($export->total_amount, $export->total_amount == floor($export->total_amount) ? 0 : 2, '.', ',') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($export->status === 'pending')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                                @elseif($export->status === 'rejected')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Đã từ chối</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $export->employee->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('exports.show', $export) }}"
                                        class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('exports.export-misa-single', $export) }}"
                                        class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200" title="Xuất Excel">
                                        <i class="fas fa-file-excel"></i>
                                    </a>
                                    @if($export->status === 'pending')
                                        <a href="{{ route('exports.edit', $export) }}"
                                            class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200"
                                            title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button
                                            onclick="confirmApprove('{{ route('exports.approve', $export) }}', 'phiếu xuất kho')"
                                            class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200" title="Duyệt">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="confirmReject('{{ route('exports.reject', $export) }}', 'phiếu xuất kho')"
                                            class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200" title="Từ chối">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @elseif($export->status === 'rejected')
                                        <form action="{{ route('exports.destroy', $export) }}" method="POST" class="inline-block"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu xuất kho này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200"
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
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Chưa có phiếu xuất kho nào.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($exports->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $exports->withQueryString()->links() }}
            </div>
        @endif
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (document.getElementById('date_from')) {
                    flatpickr("#date_from", {
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "d/m/Y",
                        locale: "vn"
                    });
                }
                if (document.getElementById('date_to')) {
                    flatpickr("#date_to", {
                        dateFormat: "Y-m-d",
                        altInput: true,
                        altFormat: "d/m/Y",
                        locale: "vn"
                    });
                }
            });
        </script>
    @endpush
@endsection