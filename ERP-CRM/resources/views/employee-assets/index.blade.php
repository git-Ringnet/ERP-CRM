@extends('layouts.app')

@section('title', 'Tài sản nội bộ')
@section('page-title', 'Quản lý Tài sản / Công cụ Dụng cụ')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    {{-- Header & Filters --}}
    <div class="p-3 sm:p-4 border-b border-gray-200 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            {{-- Search --}}
            <div class="md:col-span-2 relative">
                <form action="{{ route('employee-assets.index') }}" method="GET">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Tìm mã, tên, serial..."
                        class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="hidden" name="category" value="{{ request('category') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="tracking_type" value="{{ request('tracking_type') }}">
                </form>
            </div>

            {{-- Filter Category --}}
            <select onchange="applyFilter('category', this.value)"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả danh mục</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>

            {{-- Filter Status --}}
            <select onchange="applyFilter('status', this.value)"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả trạng thái</option>
                <option value="available"   {{ request('status') == 'available'   ? 'selected' : '' }}>Sẵn sàng</option>
                <option value="assigned"    {{ request('status') == 'assigned'    ? 'selected' : '' }}>Đang cấp phát</option>
                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Đang bảo trì</option>
                <option value="disposed"    {{ request('status') == 'disposed'    ? 'selected' : '' }}>Thanh lý</option>
            </select>

            {{-- Actions --}}
            <div class="flex gap-2">
                <a href="{{ route('employee-assets.export', request()->query()) }}"
                    class="inline-flex items-center justify-center px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm">
                    <i class="fas fa-file-excel mr-1"></i> Excel
                </a>
                <a href="{{ route('employee-assets.create') }}"
                    class="inline-flex items-center justify-center px-3 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm">
                    <i class="fas fa-plus mr-1"></i> Thêm mới
                </a>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('employee-asset-assignments.index') }}"
                class="inline-flex items-center px-3 py-1 text-xs text-blue-700 bg-blue-50 rounded-full hover:bg-blue-100">
                <i class="fas fa-exchange-alt mr-1"></i> Quản lý cấp phát
            </a>
            <a href="{{ route('employee-asset-reports.index') }}"
                class="inline-flex items-center px-3 py-1 text-xs text-purple-700 bg-purple-50 rounded-full hover:bg-purple-100">
                <i class="fas fa-chart-bar mr-1"></i> Báo cáo tổng hợp
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mx-4 mt-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-lg text-sm">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mx-4 mt-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Desktop Table --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã TS</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên tài sản</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Danh mục</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL / Còn</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Serial</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($assets as $asset)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-center text-sm text-gray-500">
                            {{ ($assets->currentPage() - 1) * $assets->perPage() + $loop->iteration }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('employee-assets.show', $asset) }}"
                                class="font-mono font-medium text-primary hover:underline text-sm">
                                {{ $asset->asset_code }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ $asset->name }}</div>
                            @if($asset->brand)
                                <div class="text-xs text-gray-400">{{ $asset->brand }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $asset->category }}</td>
                        <td class="px-4 py-3 text-center text-sm">
                            <span class="{{ $asset->quantity_available == 0 ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                                {{ $asset->quantity_available }}/{{ $asset->quantity_total }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-mono">
                            {{ $asset->serial_number ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                bg-{{ $asset->status_color }}-100 text-{{ $asset->status_color }}-700">
                                {{ $asset->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('employee-assets.show', $asset) }}"
                                    class="p-1.5 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors" title="Xem">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('employee-assets.edit', $asset) }}"
                                    class="p-1.5 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors" title="Sửa">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                @if(!$asset->activeAssignments->count())
                                    <form action="{{ route('employee-assets.destroy', $asset) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Xoá tài sản {{ $asset->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="p-1.5 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors" title="Xoá">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                            <i class="fas fa-box-open text-4xl mb-2 block"></i>
                            Chưa có tài sản nào. <a href="{{ route('employee-assets.create') }}" class="text-primary underline">Thêm ngay</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($assets as $asset)
            <div class="p-4 hover:bg-gray-50">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <a href="{{ route('employee-assets.show', $asset) }}" class="font-mono font-medium text-primary hover:underline">
                            {{ $asset->asset_code }}
                        </a>
                        <div class="text-sm font-medium text-gray-900">{{ $asset->name }}</div>
                        <div class="text-xs text-gray-400">{{ $asset->category }} · {{ $asset->brand }}</div>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $asset->status_color }}-100 text-{{ $asset->status_color }}-700">
                        {{ $asset->status_label }}
                    </span>
                </div>
                <div class="flex gap-2 mt-3">
                    <a href="{{ route('employee-assets.show', $asset) }}"
                        class="flex-1 text-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg text-sm">
                        <i class="fas fa-eye mr-1"></i>Xem
                    </a>
                    <a href="{{ route('employee-assets.edit', $asset) }}"
                        class="flex-1 text-center px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-lg text-sm">
                        <i class="fas fa-edit mr-1"></i>Sửa
                    </a>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-gray-400">Chưa có tài sản nào.</div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($assets->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $assets->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function applyFilter(key, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    window.location.href = url.toString();
}
</script>
@endpush
@endsection
