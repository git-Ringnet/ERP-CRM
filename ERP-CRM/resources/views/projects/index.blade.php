@extends('layouts.app')

@section('title', 'Quản lý dự án')
@section('page-title', 'Quản lý dự án')

@section('content')
<div class="space-y-4">
    <!-- Header Actions -->
    <div class="flex flex-wrap gap-2 justify-between items-center">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('projects.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus mr-2"></i> Thêm dự án
            </a>
            <a href="{{ route('projects.report') }}" 
               class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-chart-bar mr-2"></i> Báo cáo
            </a>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form method="GET" class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Tìm mã, tên dự án, khách hàng..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="w-40">
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả trạng thái</option>
                    <option value="planning" {{ request('status') == 'planning' ? 'selected' : '' }}>Lên kế hoạch</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>Tạm dừng</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>
            <div class="w-48">
                <select name="customer_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả khách hàng</option>
                    @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-search mr-1"></i> Tìm
            </button>
            <a href="{{ route('projects.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                <i class="fas fa-redo mr-1"></i> Reset
            </a>
        </form>
    </div>

    <!-- Projects Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã dự án</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên dự án</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thời gian</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dự toán</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Doanh thu</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($projects as $project)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('projects.show', $project->id) }}" class="font-medium text-primary hover:underline">
                                {{ $project->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $project->name }}</div>
                            @if($project->address)
                            <div class="text-xs text-gray-500">{{ Str::limit($project->address, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $project->customer_name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm">
                            @if($project->start_date)
                            <div>{{ $project->start_date->format('d/m/Y') }}</div>
                            @if($project->end_date)
                            <div class="text-xs text-gray-500">→ {{ $project->end_date->format('d/m/Y') }}</div>
                            @endif
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-medium">
                            {{ number_format($project->budget) }} đ
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="text-sm font-medium text-blue-600">{{ number_format($project->total_revenue) }} đ</div>
                            @if($project->profit != 0)
                            <div class="text-xs {{ $project->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                LN: {{ number_format($project->profit) }} đ
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $project->status_color }}">
                                {{ $project->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-1">
                                <a href="{{ route('projects.show', $project->id) }}" 
                                   class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('projects.edit', $project->id) }}" 
                                   class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('projects.destroy', $project->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa dự án này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-folder-open text-4xl mb-2"></i>
                            <p>Chưa có dự án nào</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($projects->hasPages())
        <div class="px-4 py-3 border-t">
            {{ $projects->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
