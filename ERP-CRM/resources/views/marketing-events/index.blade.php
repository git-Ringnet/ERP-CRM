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
                @can('create_marketing_events')
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
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('marketing-events.show', $event) }}" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition-colors" 
                                   title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @php
                                    $canApprove = false;
                                    if ($mktWorkflow && $event->status === 'pending') {
                                        $pendingHist = $event->approvalHistories->where('action', 'pending')->sortBy('level')->first();
                                        if ($pendingHist) {
                                            $level = $mktWorkflow->levels->where('level', $pendingHist->level)->first();
                                            $canApprove = $level?->canApprove(auth()->user(), (float)$event->budget) ?? false;
                                        }
                                    }
                                @endphp

                                @if($canApprove)
                                <div class="flex items-center gap-1">
                                    <form action="{{ route('marketing-events.approve', $event) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                            onclick="return confirm('Duyệt ngân sách sự kiện này?')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors" 
                                            title="Duyệt nhanh">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('marketing-events.show', $event) }}?reject=1" 
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors" 
                                        title="Từ chối">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                                @endif

                                @if($event->isEditable())
                                <a href="{{ route('marketing-events.edit', $event) }}" 
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors" 
                                   title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif

                                @if($event->isEditable() || $event->status === 'cancelled')
                                <form action="{{ route('marketing-events.destroy', $event) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" 
                                        onclick="return confirm('Xóa sự kiện này?')"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors" 
                                        title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
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
