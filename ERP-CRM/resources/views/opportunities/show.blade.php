@extends('layouts.app')

@section('title', 'Chi tiết Cơ hội: ' . $opportunity->name)

@section('content')
    <div class="h-full flex flex-col space-y-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-b border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900">{{ $opportunity->name }}</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-sm font-medium {{ $opportunity->stage_color }}">
                            {{ $opportunity->stage_label }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500 mt-1 flex gap-4">
                        <span><i class="fas fa-building mr-1"></i> {{ $opportunity->customer->name }}</span>
                        <span class="font-semibold text-green-600">
                            {{ number_format($opportunity->amount) }} {{ $opportunity->currency }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('opportunities.edit', $opportunity) }}"
                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium text-sm">
                        <i class="fas fa-pencil-alt mr-2"></i> Chỉnh sửa
                    </a>
                    <a href="{{ route('quotations.create', ['customer_id' => $opportunity->customer_id, 'title' => $opportunity->name]) }}"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> Tạo Báo giá
                    </a>
                </div>
            </div>

            <!-- Pipeline Progress -->
            <div class="mt-6">
                @php
                    $stages = ['new', 'qualification', 'proposal', 'negotiation', 'won'];
                    $currentStageIndex = array_search($opportunity->stage == 'lost' ? 'new' : $opportunity->stage, $stages);
                    if ($opportunity->stage == 'won')
                        $currentStageIndex = 4;
                @endphp
                <div class="relative">
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200">
                        @foreach($stages as $index => $stage)
                            @php
                                $isActive = $index <= $currentStageIndex;
                                $color = $isActive ? 'bg-green-500' : 'bg-gray-200';
                                if ($opportunity->stage == 'lost')
                                    $color = 'bg-red-500';
                            @endphp
                            <div style="width: 20%"
                                class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $color }}">
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Mới</span>
                        <span>Đánh giá</span>
                        <span>Báo giá</span>
                        <span>Đàm phán</span>
                        <span>Thành công</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4 h-full">
            <!-- Left Column: Activity Stream -->
            <div class="flex-1 bg-white rounded-lg shadow-sm p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-tasks mr-2 text-blue-500"></i>Hoạt động & Công việc
                </h3>

                <!-- Add Activity Form -->
                <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200" x-data="{ type: 'task' }">
                    <div class="flex space-x-2 mb-3 border-b border-gray-200 pb-2">
                        <button @click="type = 'task'"
                            :class="{ 'text-blue-600 border-b-2 border-blue-600 font-semibold': type === 'task', 'text-gray-500 hover:text-gray-700': type !== 'task' }"
                            class="pb-2 px-2 text-sm transition-colors">
                            <i class="fas fa-check-square mr-1"></i> Công việc
                        </button>
                        <button @click="type = 'call'"
                            :class="{ 'text-blue-600 border-b-2 border-blue-600 font-semibold': type === 'call', 'text-gray-500 hover:text-gray-700': type !== 'call' }"
                            class="pb-2 px-2 text-sm transition-colors">
                            <i class="fas fa-phone mr-1"></i> Cuộc gọi
                        </button>
                        <button @click="type = 'meeting'"
                            :class="{ 'text-blue-600 border-b-2 border-blue-600 font-semibold': type === 'meeting', 'text-gray-500 hover:text-gray-700': type !== 'meeting' }"
                            class="pb-2 px-2 text-sm transition-colors">
                            <i class="fas fa-users mr-1"></i> Cuộc gặp
                        </button>
                        <button @click="type = 'note'"
                            :class="{ 'text-blue-600 border-b-2 border-blue-600 font-semibold': type === 'note', 'text-gray-500 hover:text-gray-700': type !== 'note' }"
                            class="pb-2 px-2 text-sm transition-colors">
                            <i class="fas fa-sticky-note mr-1"></i> Ghi chú
                        </button>
                    </div>

                    <form action="{{ route('activities.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="opportunity_id" value="{{ $opportunity->id }}">
                        <input type="hidden" name="type" x-model="type">

                        <div class="space-y-3">
                            <div>
                                <input type="text" name="subject"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
                                    placeholder="Tiêu đề (ví dụ: Gọi lại cho khách, Gửi báo giá...)" required>
                            </div>

                            <div x-show="type !== 'note'" class="flex space-x-2">
                                <div class="w-1/2">
                                    <label class="text-xs text-gray-500 block mb-1">Ngày hết hạn / thực hiện</label>
                                    <input type="date" name="due_date"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
                                </div>
                            </div>

                            <div>
                                <textarea name="description" rows="2"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
                                    placeholder="Mô tả chi tiết..."></textarea>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                                    Lưu hoạt động
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Activity List -->
                <div class="space-y-4">
                    @forelse($opportunity->activities()->latest()->get() as $activity)
                        <div
                            class="flex group relative pl-4 border-l-2 {{ $activity->is_completed ? 'border-green-300' : 'border-gray-200' }}">
                            <!-- Icon -->
                            <div class="absolute -left-[9px] top-0 bg-white">
                                @if($activity->type == 'call') <i class="fas fa-phone text-blue-500 text-sm"></i>
                                @elseif($activity->type == 'meeting') <i class="fas fa-users text-purple-500 text-sm"></i>
                                @elseif($activity->type == 'email') <i class="fas fa-envelope text-yellow-500 text-sm"></i>
                                @elseif($activity->type == 'note') <i class="fas fa-sticky-note text-gray-500 text-sm"></i>
                                @else <i class="fas fa-check-square text-green-500 text-sm"></i>
                                @endif
                            </div>

                            <div class="w-full pb-4 border-b border-gray-100 last:border-0 pl-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div
                                            class="font-medium text-gray-800 {{ $activity->is_completed ? 'line-through text-gray-500' : '' }}">
                                            {{ $activity->subject }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $activity->created_by ? $activity->createdBy->name : 'N/A' }} •
                                            {{ $activity->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if(!$activity->is_completed && $activity->type != 'note')
                                            <form action="{{ route('activities.update', $activity) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="toggle_status" value="1">
                                                <button type="submit"
                                                    class="text-xs bg-white border border-gray-300 text-gray-600 px-2 py-1 rounded hover:bg-green-50 hover:text-green-600 hover:border-green-300 transition-colors"
                                                    title="Đánh dấu đã hoàn thành">
                                                    <i class="fas fa-check mr-1"></i> Xong
                                                </button>
                                            </form>
                                        @endif
                                        @if($activity->is_completed)
                                            <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-1 rounded">
                                                <i class="fas fa-check mr-1"></i> Đã hoàn thành
                                            </span>
                                        @endif
                                        <form action="{{ route('activities.destroy', $activity) }}" method="POST"
                                            onsubmit="return confirm('Xóa hoạt động này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                @if($activity->description)
                                    <div class="mt-2 text-sm text-gray-600 bg-gray-50 p-2 rounded">
                                        {{ $activity->description }}
                                    </div>
                                @endif
                                @if($activity->due_date)
                                    <div
                                        class="mt-1 text-xs font-medium {{ $activity->due_date->isPast() && !$activity->is_completed ? 'text-red-500' : 'text-blue-500' }}">
                                        <i class="far fa-clock mr-1"></i> Hạn: {{ $activity->due_date->format('d/m/Y') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-gray-500">
                            <i class="fas fa-clipboard-list text-3xl mb-2 text-gray-300"></i>
                            <p>Chưa có hoạt động nào được ghi nhận.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right Column: Sidebar -->
            <div class="w-full md:w-80 space-y-4">
                <!-- Customer Info -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 border-b border-gray-100 pb-2">Thông tin khách hàng
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="block text-gray-500 text-xs">Tên khách hàng</span>
                            <a href="{{ route('customers.show', $opportunity->customer_id) }}"
                                class="font-medium text-blue-600 hover:underline">
                                {{ $opportunity->customer->name }}
                            </a>
                        </div>
                        <div>
                            <span class="block text-gray-500 text-xs">Mã khách hàng</span>
                            <span class="text-gray-800">{{ $opportunity->customer->code }}</span>
                        </div>
                        @if($opportunity->customer->phone)
                            <div>
                                <span class="block text-gray-500 text-xs">Điện thoại</span>
                                <span class="text-gray-800">{{ $opportunity->customer->phone }}</span>
                            </div>
                        @endif
                        @if($opportunity->customer->email)
                            <div>
                                <span class="block text-gray-500 text-xs">Email</span>
                                <span class="text-gray-800">{{ $opportunity->customer->email }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Opportunity Info -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 border-b border-gray-100 pb-2">Thông tin hợp đồng
                    </h4>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="block text-gray-500 text-xs">Người phụ trách</span>
                            <span
                                class="font-medium text-gray-800">{{ $opportunity->assignedTo->name ?? 'Chưa phân công' }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-500 text-xs">Xác suất thành công</span>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $opportunity->probability }}%">
                                </div>
                            </div>
                            <span class="text-xs text-gray-600 mt-0.5 block">{{ $opportunity->probability }}%</span>
                        </div>
                        <div>
                            <span class="block text-gray-500 text-xs">Ngày dự kiến chốt</span>
                            <span
                                class="font-medium {{ $opportunity->expected_close_date && $opportunity->expected_close_date->isPast() ? 'text-red-600' : 'text-gray-800' }}">
                                {{ $opportunity->expected_close_date ? $opportunity->expected_close_date->format('d/m/Y') : 'Chưa đặt' }}
                            </span>
                        </div>

                        @if($opportunity->next_action)
                            <div class="pt-2 border-t border-gray-100">
                                <span class="block text-gray-500 text-xs">Kế hoạch tiếp theo (Quick Note)</span>
                                <p class="text-gray-800 italic">{{ $opportunity->next_action }}</p>
                                @if($opportunity->next_action_date)
                                    <span
                                        class="text-xs {{ $opportunity->next_action_date->isPast() ? 'text-red-500' : 'text-gray-500' }}">
                                        {{ $opportunity->next_action_date->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection