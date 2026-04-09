@extends('layouts.app')
@section('title', 'Marketing Events')
@section('page-title', 'Quản lý sự kiện Marketing')

@section('content')
<div class="space-y-4">
    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-calendar-alt text-purple-500 mr-2"></i>Danh sách sự kiện Marketing
            </h2>
            <div class="flex flex-wrap gap-2">
                <form action="{{ route('marketing-events.index') }}" method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm..."
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                    <select name="status" onchange="this.form.submit()"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                        <option value="">Tất cả trạng thái</option>
                        <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Nháp</option>
                        <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Chờ duyệt</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Từ chối</option>
                    </select>
                </form>
                @can('create', App\Models\MarketingEvent::class)
                <a href="{{ route('marketing-events.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i> Tạo sự kiện
                </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sự kiện</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Địa điểm</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">NS dự toán</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">KH mời</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Người tạo</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($events as $event)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('marketing-events.show', $event) }}" class="font-medium text-purple-600 hover:underline">
                                {{ $event->title }}
                            </a>
                            @if($event->description)
                            <div class="text-xs text-gray-500 truncate max-w-xs">{{ $event->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $event->event_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $event->location ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($event->budget) }} đ</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $event->customers_count ?? $event->customers->count() }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $event->status_color }}">
                                {{ $event->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-center text-gray-600">{{ $event->creator->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('marketing-events.show', $event) }}" class="text-purple-600 hover:text-purple-800 mx-1" title="Xem"><i class="fas fa-eye"></i></a>
                            @if($event->isEditable())
                            <a href="{{ route('marketing-events.edit', $event) }}" class="text-yellow-500 hover:text-yellow-700 mx-1" title="Sửa"><i class="fas fa-edit"></i></a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Chưa có sự kiện nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $events->links() }}</div>
    </div>
</div>
@endsection
