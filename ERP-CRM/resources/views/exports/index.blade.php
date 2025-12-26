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
            <a href="{{ route('exports.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                <i class="fas fa-plus mr-2"></i>Tạo phiếu xuất
            </a>
        </div>
    </div>

    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <form action="{{ route('exports.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
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
                <select name="project_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Tất cả dự án --</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->code }} - {{ $project->name }}
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
                       placeholder="Từ ngày"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
            </div>
            <div class="flex gap-2">
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       placeholder="Đến ngày"
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
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dự án</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày xuất</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhân viên</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($exports as $export)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('exports.show', $export) }}" class="text-orange-600 hover:text-orange-800 font-medium">
                            {{ $export->code }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @if($export->project)
                            <a href="{{ route('projects.show', $export->project) }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $export->project->code }}
                            </a>
                            <div class="text-xs text-gray-500">{{ Str::limit($export->project->name, 30) }}</div>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $export->date->format('d/m/Y') }}</td>
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
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $export->employee->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('exports.show', $export) }}" 
                               class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($export->status === 'pending')
                                <a href="{{ route('exports.edit', $export) }}" 
                                   class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmApprove('{{ route('exports.approve', $export) }}', 'phiếu xuất kho')" 
                                        class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200" title="Duyệt">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="confirmReject('{{ route('exports.reject', $export) }}', 'phiếu xuất kho')" 
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
@endsection
