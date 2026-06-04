@extends('layouts.app')

@section('title', 'Danh sách hoạt động cơ hội')
@section('page-title', 'Danh sách hoạt động cơ hội')

@section('content')
    <div class="max-w-8xl space-y-4">
        <!-- Header Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-list text-primary mr-2"></i>Danh sách hoạt động cơ hội kinh doanh
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Lên lịch và theo dõi các hoạt động tư vấn, họp mặt và demo sản phẩm với khách hàng.</p>
                </div>
                
                <div class="flex gap-2">
                    <a href="{{ route('opportunities.index', ['view' => 'calendar'] + request()->except('view')) }}"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-semibold">
                        <i class="fas fa-calendar-alt mr-2 text-gray-500"></i>Dạng lịch
                    </a>
                    <a href="{{ route('opportunities.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors text-sm font-semibold shadow-sm">
                        <i class="fas fa-plus mr-2"></i>Thêm hoạt động mới
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
            <form action="{{ route('opportunities.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                <input type="hidden" name="view" value="list">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Khách hàng</label>
                    <input type="text" name="customer_name" value="{{ request('customer_name') }}" placeholder="Tên công ty, EU..."
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary py-2 px-3">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Loại hoạt động</label>
                    <select name="activity_type"
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary bg-white py-2 px-3">
                        <option value="">-- Tất cả --</option>
                        @foreach($activityTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('activity_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Trạng thái</label>
                    <select name="status"
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary bg-white py-2 px-3">
                        <option value="">-- Tất cả --</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sales phụ trách</label>
                    <select name="assigned_to"
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary bg-white py-2 px-3">
                        <option value="">-- Tất cả --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kỹ thuật phụ trách</label>
                    <select name="technical_user"
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary bg-white py-2 px-3">
                        <option value="">-- Tất cả --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('technical_user') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors text-sm font-semibold flex items-center justify-center">
                        <i class="fas fa-filter mr-1.5"></i> Lọc
                    </button>
                    <a href="{{ route('opportunities.index', ['view' => 'list']) }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-sm font-medium text-center flex items-center justify-center">
                        Xóa lọc
                    </a>
                </div>
            </form>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="bg-gray-50 text-xs text-gray-700 uppercase border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Chủ đề hoạt động</th>
                            <th class="px-6 py-4 font-semibold">Khách hàng</th>
                            <th class="px-6 py-4 font-semibold">Loại hoạt động</th>
                            <th class="px-6 py-4 font-semibold">Thời gian</th>
                            <th class="px-6 py-4 font-semibold text-center">Trạng thái</th>
                            <th class="px-6 py-4 font-semibold">Phối hợp kỹ thuật</th>
                            <th class="px-6 py-4 font-semibold">PIC (Sales)</th>
                            <th class="px-6 py-4 font-semibold text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($opportunities as $opportunity)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <a href="{{ route('opportunities.show', $opportunity->id) }}" class="hover:text-primary-dark transition-colors font-semibold">
                                        {{ $opportunity->name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800">{{ $opportunity->customer_display_name }}</div>
                                    <span class="text-xs text-gray-400 capitalize">{{ $opportunity->customer_type_label }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-600 font-semibold">{{ $opportunity->activity_type_label }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-800">{{ $opportunity->activity_date->format('d/m/Y') }}</div>
                                    <span class="text-xs text-gray-400">{{ $opportunity->start_time ?: 'N/A' }} - {{ $opportunity->end_time ?: 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $opportunity->status_color }}">
                                        {{ $opportunity->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($opportunity->needs_technical && $opportunity->technicalUser)
                                        <div class="flex items-center gap-1.5 text-gray-800">
                                            <i class="fas fa-cogs text-indigo-500 text-xs"></i>
                                            <span>{{ $opportunity->technicalUser->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-800">{{ $opportunity->assignedTo?->name ?: 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('opportunities.show', $opportunity->id) }}"
                                            class="p-2 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-lg transition-colors" title="Xem chi tiết">
                                            <i class="fas fa-eye text-xs"></i>
                                        </a>
                                        <a href="{{ route('opportunities.edit', $opportunity->id) }}"
                                            class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg transition-colors" title="Chỉnh sửa">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>
                                        <form action="{{ route('opportunities.destroy', $opportunity->id) }}" method="POST" class="inline-block"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa hoạt động này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-colors" title="Xóa">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                    <i class="far fa-calendar-times text-4xl mb-2 text-gray-300"></i>
                                    <p class="text-sm">Không tìm thấy hoạt động cơ hội nào phù hợp.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($opportunities->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $opportunities->appends(request()->except('page'))->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection