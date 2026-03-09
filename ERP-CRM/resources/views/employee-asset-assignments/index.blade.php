@extends('layouts.app')

@section('title', 'Phiếu cấp phát tài sản')
@section('page-title', 'Quản lý Cấp phát / Thu hồi Tài sản')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-3 sm:p-4 border-b border-gray-200 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            {{-- Search --}}
            <div class="md:col-span-2 relative">
                <form action="{{ route('employee-asset-assignments.index') }}" method="GET">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Tìm tên tài sản, mã hoặc nhân viên..."
                        class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="hidden" name="status" value="{{ request('status') }}">
                </form>
            </div>

            {{-- Filter Status --}}
            <select onchange="window.location.href='{{ route('employee-asset-assignments.index') }}?status='+this.value+'&search={{ request('search') }}'"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả trạng thái</option>
                <option value="active"   {{ request('status') == 'active'   ? 'selected' : '' }}>Đang cấp phát</option>
                <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Đã thu hồi</option>
                <option value="overdue"  {{ request('status') == 'overdue'  ? 'selected' : '' }}>Quá hạn</option>
            </select>

            <a href="{{ route('employee-asset-assignments.create') }}"
                class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm">
                <i class="fas fa-plus mr-2"></i>Cấp phát mới
            </a>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('employee-assets.index') }}"
                class="inline-flex items-center px-3 py-1 text-xs text-gray-700 bg-gray-100 rounded-full hover:bg-gray-200">
                <i class="fas fa-box mr-1"></i> Danh mục tài sản
            </a>
            <a href="{{ route('employee-asset-reports.index') }}"
                class="inline-flex items-center px-3 py-1 text-xs text-purple-700 bg-purple-50 rounded-full hover:bg-purple-100">
                <i class="fas fa-chart-bar mr-1"></i> Báo cáo
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mx-4 mt-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-lg text-sm">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        </div>
    @endif

    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tài sản</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhân viên</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày cấp</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dự kiến trả</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày trả</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($assignments as $a)
                    <tr class="hover:bg-gray-50 {{ $a->status === 'overdue' ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-3 text-center text-sm text-gray-500">
                            {{ ($assignments->currentPage() - 1) * $assignments->perPage() + $loop->iteration }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ optional($a->asset)->name }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ optional($a->asset)->asset_code }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">{{ optional($a->employee)->name }}</div>
                            <div class="text-xs text-gray-400">{{ optional($a->employee)->department }}</div>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">{{ $a->quantity }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $a->assigned_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm {{ $a->status === 'overdue' ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                            {{ $a->expected_return_date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $a->returned_date?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-{{ $a->status_color }}-100 text-{{ $a->status_color }}-700">
                                {{ $a->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('employee-asset-assignments.show', $a) }}"
                                class="p-1.5 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 inline-flex" title="Xem">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-400">
                            <i class="fas fa-exchange-alt text-4xl mb-2 block"></i>
                            Chưa có phiếu cấp phát nào.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assignments->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $assignments->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
