@extends('layouts.app')

@section('title', 'Địa điểm làm việc')
@section('page-title', 'Quản lý Địa điểm làm việc')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex flex-wrap gap-4 items-center justify-between">
        <form action="{{ route('work-locations.index') }}" method="GET" class="flex gap-2 items-center flex-wrap">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm địa điểm..." class="pl-3 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary w-64">
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả trạng thái</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Ngừng hoạt động</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm whitespace-nowrap"><i class="fas fa-search mr-1"></i>Tìm</button>
            <a href="{{ route('work-locations.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm whitespace-nowrap"><i class="fas fa-redo"></i></a>
        </form>
        <a href="{{ route('work-locations.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm whitespace-nowrap">
            <i class="fas fa-plus mr-2"></i>Thêm địa điểm
        </a>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên địa điểm</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tọa độ (Lat, Lng)</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Bán kính (m)</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($workLocations as $location)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-center text-sm text-gray-500">{{ ($workLocations->currentPage() - 1) * $workLocations->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $location->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $location->latitude }}, {{ $location->longitude }}</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-500">{{ $location->radius }} m</td>
                    <td class="px-4 py-3 text-center">
                        @if($location->is_active)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt động</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Ngừng</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('work-locations.edit', $location->id) }}" class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('work-locations.destroy', $location->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors delete-btn" data-name="{{ $location->name }}" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-map-marker-alt text-4xl mb-2 text-gray-400"></i>
                        <p>Chưa có địa điểm làm việc nào</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($workLocations->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $workLocations->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
