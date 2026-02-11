@extends('layouts.app')

@section('title', 'Theo dõi chăm sóc khách hàng')
@section('page-title', 'Theo dõi tiến độ chăm sóc khách hàng')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200">
        <form action="{{ route('customer-care-stages.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                <!-- Search -->
                <div class="relative lg:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm theo tên khách hàng, mã KH..." 
                           class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                
                <!-- Filter by Stage -->
                <select name="stage" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả giai đoạn</option>
                    <option value="new" {{ request('stage') == 'new' ? 'selected' : '' }}>Khách hàng mới</option>
                    <option value="onboarding" {{ request('stage') == 'onboarding' ? 'selected' : '' }}>Đang tiếp nhận</option>
                    <option value="active" {{ request('stage') == 'active' ? 'selected' : '' }}>Chăm sóc tích cực</option>
                    <option value="follow_up" {{ request('stage') == 'follow_up' ? 'selected' : '' }}>Theo dõi</option>
                    <option value="retention" {{ request('stage') == 'retention' ? 'selected' : '' }}>Duy trì</option>
                    <option value="at_risk" {{ request('stage') == 'at_risk' ? 'selected' : '' }}>Có nguy cơ</option>
                    <option value="inactive" {{ request('stage') == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                </select>

                <!-- Filter by Status -->
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả trạng thái</option>
                    <option value="not_started" {{ request('status') == 'not_started' ? 'selected' : '' }}>Chưa bắt đầu</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>Tạm dừng</option>
                </select>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <!-- Filter by Priority -->
                <select name="priority" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả mức độ</option>
                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Thấp</option>
                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Trung bình</option>
                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Cao</option>
                    <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Khẩn cấp</option>
                </select>

                <!-- Filter by Assigned User -->
                <select name="assigned_to" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả người phụ trách</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Overdue Filter -->
                <label class="inline-flex items-center">
                    <input type="checkbox" name="overdue" value="yes" {{ request('overdue') == 'yes' ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary focus:ring-primary">
                    <span class="ml-2 text-sm text-gray-700">Chỉ quá hạn</span>
                </label>

                <!-- Buttons -->
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm whitespace-nowrap">
                        <i class="fas fa-filter mr-1"></i>Lọc
                    </button>
                    <a href="{{ route('customer-care-stages.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm whitespace-nowrap">
                        <i class="fas fa-redo mr-1"></i>Đặt lại
                    </a>
                </div>

                <!-- Dashboard & Add buttons -->
                <div class="flex gap-2 ml-auto">
                    <a href="{{ route('customer-care-stages.dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm whitespace-nowrap">
                        <i class="fas fa-chart-line mr-2"></i>Dashboard
                    </a>
                    <a href="{{ route('customer-care-stages.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm whitespace-nowrap">
                        <i class="fas fa-plus mr-2"></i>Thêm
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Khách hàng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Giai đoạn</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Trạng thái</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Mức độ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Người phụ trách</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Tiến độ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Ngày hoàn thành dự kiến</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($careStages as $careStage)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ ($careStages->currentPage() - 1) * $careStages->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $careStage->customer->name }}</div>
                        <div class="text-xs text-gray-500">{{ $careStage->customer->code }}</div>
                        @if($careStage->customer->phone)
                            <div class="text-xs text-blue-600 mt-1">
                                <i class="fas fa-phone-alt mr-1"></i>{{ $careStage->customer->phone }}
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            @if($careStage->stage == 'new') bg-blue-100 text-blue-800
                            @elseif($careStage->stage == 'onboarding') bg-purple-100 text-purple-800
                            @elseif($careStage->stage == 'active') bg-green-100 text-green-800
                            @elseif($careStage->stage == 'follow_up') bg-yellow-100 text-yellow-800
                            @elseif($careStage->stage == 'retention') bg-indigo-100 text-indigo-800
                            @elseif($careStage->stage == 'at_risk') bg-orange-100 text-orange-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $careStage->stage_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            @if($careStage->status == 'not_started') bg-gray-100 text-gray-800
                            @elseif($careStage->status == 'in_progress') bg-blue-100 text-blue-800
                            @elseif($careStage->status == 'completed') bg-green-100 text-green-800
                            @else bg-yellow-100 text-yellow-800
                            @endif">
                            {{ $careStage->status_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            @if($careStage->priority == 'low') bg-gray-100 text-gray-800
                            @elseif($careStage->priority == 'medium') bg-blue-100 text-blue-800
                            @elseif($careStage->priority == 'high') bg-orange-100 text-orange-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ $careStage->priority_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ $careStage->assignedTo->name ?? 'Chưa phân công' }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-primary h-2 rounded-full" style="width: {{ $careStage->completion_percentage }}%"></div>
                            </div>
                            <span class="text-sm text-gray-700 whitespace-nowrap">{{ $careStage->completion_percentage }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                        @if($careStage->target_completion_date)
                            @if($careStage->is_overdue && $careStage->status != 'completed')
                                <div class="inline-flex items-center px-2 py-1 rounded bg-red-100 border border-red-300">
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-1"></i>
                                    <span class="text-red-700 font-semibold">{{ $careStage->target_completion_date->format('d/m/Y') }}</span>
                                </div>
                                <div class="text-xs text-red-600 mt-1">
                                    Quá {{ now()->diffInDays($careStage->target_completion_date) }} ngày
                                </div>
                            @else
                                <span class="text-gray-700">{{ $careStage->target_completion_date->format('d/m/Y') }}</span>
                                @if($careStage->status == 'completed')
                                    <i class="fas fa-check-circle text-green-600 ml-1" title="Đã hoàn thành"></i>
                                @endif
                            @endif
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('customer-care-stages.show', $careStage->id) }}" 
                               class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('customer-care-stages.edit', $careStage->id) }}" 
                               class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" 
                               title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('customer-care-stages.destroy', $careStage->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors delete-btn" 
                                        data-name="{{ $careStage->customer->name }}" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Chưa có dữ liệu chăm sóc khách hàng</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($careStages->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $careStages->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
