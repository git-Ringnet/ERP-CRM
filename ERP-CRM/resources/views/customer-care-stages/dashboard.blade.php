@extends('layouts.app')

@section('title', 'Dashboard Chăm sóc khách hàng')
@section('page-title', 'Dashboard Chăm sóc khách hàng')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Đang thực hiện</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $totalActive }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-tasks text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Hoàn thành</p>
                    <p class="text-3xl font-bold text-green-600">{{ $totalCompleted }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Quá hạn</p>
                    <p class="text-3xl font-bold text-red-600">{{ $totalOverdue }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Tỷ lệ hoàn thành</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $completionRate }}%</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-chart-pie text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Reminder Cards (Phase A) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Overdue Actions - RED -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 border-l-4 border-red-600 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-red-900 flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Quá hạn
                    </h3>
                    <p class="text-sm text-red-700">Cần xử lý ngay</p>
                </div>
                <div class="bg-red-600 text-white rounded-full w-12 h-12 flex items-center justify-center text-xl font-bold">
                    {{ $overdueActions->count() }}
                </div>
            </div>
            @if($overdueActions->count() > 0)
                <div class="space-y-2">
                    @foreach($overdueActions as $action)
                        <a href="{{ route('customer-care-stages.show', $action) }}" 
                           class="block bg-white hover:bg-red-50 rounded-lg p-3 border border-red-200 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-sm">{{ $action->customer->name }}</p>
                                    <p class="text-xs text-gray-600 mt-1">{{ Str::limit($action->next_action, 40) }}</p>
                                    <p class="text-xs text-red-600 mt-1">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $action->next_action_due_at->diffForHumans() }}
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 ml-2"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-center text-red-700 py-4 text-sm">
                    <i class="fas fa-check-circle text-2xl mb-2"></i><br>
                    Không có hành động quá hạn
                </p>
            @endif
        </div>

        <!-- Due Today - YELLOW -->
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 border-l-4 border-yellow-600 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-yellow-900 flex items-center">
                        <i class="fas fa-bell mr-2"></i>
                        Hôm nay
                    </h3>
                    <p class="text-sm text-yellow-700">Cần hoàn thành</p>
                </div>
                <div class="bg-yellow-600 text-white rounded-full w-12 h-12 flex items-center justify-center text-xl font-bold">
                    {{ $dueTodayActions->count() }}
                </div>
            </div>
            @if($dueTodayActions->count() > 0)
                <div class="space-y-2">
                    @foreach($dueTodayActions as $action)
                        <a href="{{ route('customer-care-stages.show', $action) }}" 
                           class="block bg-white hover:bg-yellow-50 rounded-lg p-3 border border-yellow-200 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-sm">{{ $action->customer->name }}</p>
                                    <p class="text-xs text-gray-600 mt-1">{{ Str::limit($action->next_action, 40) }}</p>
                                    <p class="text-xs text-yellow-600 mt-1">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $action->next_action_due_at->format('H:i') }}
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 ml-2"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-center text-yellow-700 py-4 text-sm">
                    <i class="fas fa-calendar-check text-2xl mb-2"></i><br>
                    Không có hành động hôm nay
                </p>
            @endif
        </div>

        <!-- Upcoming - BLUE -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border-l-4 border-blue-600 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-blue-900 flex items-center">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Sắp tới
                    </h3>
                    <p class="text-sm text-blue-700">7 ngày tới</p>
                </div>
                <div class="bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center text-xl font-bold">
                    {{ $upcomingActions->count() }}
                </div>
            </div>
            @if($upcomingActions->count() > 0)
                <div class="space-y-2">
                    @foreach($upcomingActions as $action)
                        <a href="{{ route('customer-care-stages.show', $action) }}" 
                           class="block bg-white hover:bg-blue-50 rounded-lg p-3 border border-blue-200 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-sm">{{ $action->customer->name }}</p>
                                    <p class="text-xs text-gray-600 mt-1">{{ Str::limit($action->next_action, 40) }}</p>
                                    <p class="text-xs text-blue-600 mt-1">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $action->next_action_due_at->format('d/m H:i') }}
                                    </p>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400 ml-2"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-center text-blue-700 py-4 text-sm">
                    <i class="fas fa-inbox text-2xl mb-2"></i><br>
                    Không có hành động sắp tới
                </p>
            @endif
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- By Stage -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Theo giai đoạn</h3>
            <div class="space-y-3">
                @foreach($byStage as $item)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">
                            @if($item->stage == 'new') Khách hàng mới
                            @elseif($item->stage == 'onboarding') Đang tiếp nhận
                            @elseif($item->stage == 'active') Chăm sóc tích cực
                            @elseif($item->stage == 'follow_up') Theo dõi
                            @elseif($item->stage == 'retention') Duy trì
                            @elseif($item->stage == 'at_risk') Có nguy cơ
                            @else Không hoạt động
                            @endif
                        </span>
                        <span class="font-semibold">{{ $item->count }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($item->count / max($byStage->sum('count'), 1)) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- By Status -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Theo trạng thái</h3>
            <div class="space-y-3">
                @foreach($byStatus as $item)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">
                            @if($item->status == 'not_started') Chưa bắt đầu
                            @elseif($item->status == 'in_progress') Đang thực hiện
                            @elseif($item->status == 'completed') Hoàn thành
                            @else Tạm dừng
                            @endif
                        </span>
                        <span class="font-semibold">{{ $item->count }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ ($item->count / max($byStatus->sum('count'), 1)) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- By Priority -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Theo mức độ ưu tiên</h3>
            <div class="space-y-3">
                @foreach($byPriority as $item)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">
                            @if($item->priority == 'low') Thấp
                            @elseif($item->priority == 'medium') Trung bình
                            @elseif($item->priority == 'high') Cao
                            @else Khẩn cấp
                            @endif
                        </span>
                        <span class="font-semibold">{{ $item->count }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-orange-600 h-2 rounded-full" style="width: {{ ($item->count / max($byPriority->sum('count'), 1)) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Care Stages & Overdue -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Care Stages -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Giai đoạn chăm sóc gần đây</h3>
            <div class="space-y-3">
                @forelse($recentCareStages as $stage)
                <a href="{{ route('customer-care-stages.show', $stage) }}" class="block p-3 border rounded-lg hover:bg-gray-50">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-semibold">{{ $stage->customer->name }}</h4>
                            <p class="text-sm text-gray-600">{{ $stage->stage_label }} - {{ $stage->status_label }}</p>
                        </div>
                        <span class="text-sm font-semibold text-primary">{{ $stage->completion_percentage }}%</span>
                    </div>
                </a>
                @empty
                <p class="text-center text-gray-500 py-4">Chưa có dữ liệu</p>
                @endforelse
            </div>
        </div>

        <!-- Overdue Care Stages -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4 text-red-600">
                <i class="fas fa-exclamation-triangle mr-2"></i>Quá hạn
            </h3>
            <div class="space-y-3">
                @forelse($overdueCareStages as $stage)
                <a href="{{ route('customer-care-stages.show', $stage) }}" class="block p-3 border border-red-200 bg-red-50 rounded-lg hover:bg-red-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-semibold text-red-900">{{ $stage->customer->name }}</h4>
                            <p class="text-sm text-red-700">{{ $stage->stage_label }}</p>
                            <p class="text-xs text-red-600 mt-1">
                                <i class="fas fa-calendar mr-1"></i>Hạn: {{ $stage->target_completion_date->format('d/m/Y') }}
                            </p>
                        </div>
                        <span class="text-sm font-semibold text-red-600">{{ $stage->completion_percentage }}%</span>
                    </div>
                </a>
                @empty
                <p class="text-center text-gray-500 py-4">Không có giai đoạn quá hạn</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex justify-center gap-4">
        <a href="{{ route('customer-care-stages.index') }}" 
           class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark">
            <i class="fas fa-list mr-2"></i>Xem tất cả
        </a>
        <a href="{{ route('customer-care-stages.create') }}" 
           class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-plus mr-2"></i>Tạo mới
        </a>
    </div>
</div>
@endsection
