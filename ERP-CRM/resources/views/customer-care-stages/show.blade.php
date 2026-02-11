@extends('layouts.app')

@section('title', 'Chi tiết giai đoạn chăm sóc')
@section('page-title', 'Chi tiết giai đoạn chăm sóc')

@section('content')
<div class="space-y-6">
    <!-- Care Stage Info Card -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-start mb-6">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ $customerCareStage->customer->name }}</h2>
                <p class="text-gray-600 mb-2">{{ $customerCareStage->customer->code }}</p>
                
                <!-- Quick Contact Info -->
                <div class="flex flex-wrap gap-4 mt-3">
                    @if($customerCareStage->customer->phone)
                        <a href="tel:{{ $customerCareStage->customer->phone }}" 
                           class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                            <i class="fas fa-phone-alt mr-2"></i>
                            <span class="font-medium">{{ $customerCareStage->customer->phone }}</span>
                        </a>
                    @endif
                    @if($customerCareStage->customer->email)
                        <a href="mailto:{{ $customerCareStage->customer->email }}" 
                           class="inline-flex items-center px-3 py-1.5 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors">
                            <i class="fas fa-envelope mr-2"></i>
                            <span class="font-medium">{{ $customerCareStage->customer->email }}</span>
                        </a>
                    @endif
                    @if($customerCareStage->customer->contact_person)
                        <div class="inline-flex items-center px-3 py-1.5 bg-purple-50 text-purple-700 rounded-lg">
                            <i class="fas fa-user mr-2"></i>
                            <span class="font-medium">{{ $customerCareStage->customer->contact_person }}</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('customer-care-stages.edit', $customerCareStage) }}" 
                   class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                    <i class="fas fa-edit mr-1"></i> Sửa
                </a>
                <a href="{{ route('customer-care-stages.index') }}" 
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-600">Giai đoạn</p>
                <p class="font-semibold">{{ $customerCareStage->stage_label }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Trạng thái</p>
                <p class="font-semibold">{{ $customerCareStage->status_label }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Mức độ ưu tiên</p>
                <p class="font-semibold">{{ $customerCareStage->priority_label }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Người phụ trách</p>
                <p class="font-semibold">{{ $customerCareStage->assignedTo->name ?? 'Chưa phân công' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Ngày bắt đầu</p>
                <p class="font-semibold">{{ $customerCareStage->start_date->format('d/m/Y') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Ngày hoàn thành dự kiến</p>
                <p class="font-semibold">{{ $customerCareStage->target_completion_date?->format('d/m/Y') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Ngày hoàn thành thực tế</p>
                <p class="font-semibold">{{ $customerCareStage->actual_completion_date?->format('d/m/Y') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Người tạo</p>
                <p class="font-semibold">{{ $customerCareStage->createdBy->name }}</p>
            </div>
        </div>

        @if($customerCareStage->notes)
        <div class="mt-4 pt-4 border-t">
            <p class="text-sm text-gray-600 mb-1">Ghi chú</p>
            <p class="text-gray-900">{{ $customerCareStage->notes }}</p>
        </div>
        @endif
    </div>

    <!-- Progress Card -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Tiến độ</h3>
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <i class="fas fa-magic text-blue-600"></i>
                <span>Tự động tính từ milestones</span>
            </div>
        </div>
        
        <div class="flex items-center mb-4">
            <div class="flex-1 bg-gray-200 rounded-full h-4 mr-4">
                <div class="bg-primary h-4 rounded-full transition-all" style="width: {{ $customerCareStage->completion_percentage }}%"></div>
            </div>
            <span class="text-2xl font-bold text-primary">{{ $customerCareStage->completion_percentage }}%</span>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <div class="flex items-start gap-2">
                <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                <div class="text-sm text-blue-800">
                    <p class="font-medium mb-1">Tiến độ được tính tự động</p>
                    <p class="text-blue-700">
                        @php
                            $totalMilestones = $customerCareStage->milestones->count();
                            $completedMilestones = $customerCareStage->milestones->where('is_completed', true)->count();
                        @endphp
                        Đã hoàn thành <span class="font-semibold">{{ $completedMilestones }}/{{ $totalMilestones }}</span> milestones
                        @if($totalMilestones > 0)
                            ({{ round(($completedMilestones / $totalMilestones) * 100) }}%)
                        @endif
                    </p>
                    <p class="text-xs text-blue-600 mt-1">
                        💡 Đánh dấu milestones hoàn thành để tự động cập nhật tiến độ
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Action Card -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
        <h3 class="text-lg font-semibold mb-4 text-blue-900">
            <i class="fas fa-tasks mr-2"></i>Hành động tiếp theo
        </h3>
        
        @if($customerCareStage->next_action && !$customerCareStage->next_action_completed)
            <div class="bg-white rounded-lg p-4 mb-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-gray-900 font-medium mb-2">{{ $customerCareStage->next_action }}</p>
                        @if($customerCareStage->next_action_due_at)
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-clock mr-1"></i>
                                Đến hạn: {{ $customerCareStage->next_action_due_at->format('d/m/Y H:i') }}
                                @if($customerCareStage->next_action_due_at->isPast())
                                    <span class="ml-2 text-red-600 font-semibold">(Quá hạn)</span>
                                @endif
                            </p>
                        @endif
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <!-- Complete with Outcome Dropdown -->
                        <div class="relative inline-block" x-data="{ open: false }">
                            <button @click="open = !open" type="button" 
                                    class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 inline-flex items-center">
                                <i class="fas fa-check mr-1"></i>Ghi nhận kết quả
                                <i class="fas fa-chevron-down ml-2 text-xs"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-20"
                                 style="display: none;">
                                <div class="p-2">
                                    <p class="text-xs text-gray-600 px-3 py-2 font-semibold uppercase">Kết quả hành động</p>
                                    <form action="{{ route('customer-care-stages.complete-action', $customerCareStage) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="outcome" value="success">
                                        <button type="submit" class="w-full text-left px-3 py-2 hover:bg-green-50 rounded flex items-center gap-2">
                                            <i class="fas fa-check-circle text-green-600"></i>
                                            <div>
                                                <div class="font-medium text-sm">Thành công</div>
                                                <div class="text-xs text-gray-500">Đã liên hệ và hoàn tất</div>
                                            </div>
                                        </button>
                                    </form>
                                    <form action="{{ route('customer-care-stages.complete-action', $customerCareStage) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="outcome" value="no_answer">
                                        <button type="submit" class="w-full text-left px-3 py-2 hover:bg-yellow-50 rounded flex items-center gap-2">
                                            <i class="fas fa-phone-slash text-yellow-600"></i>
                                            <div>
                                                <div class="font-medium text-sm">Không bắt máy</div>
                                                <div class="text-xs text-gray-500">Gọi lại sau</div>
                                            </div>
                                        </button>
                                    </form>
                                    <form action="{{ route('customer-care-stages.complete-action', $customerCareStage) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="outcome" value="reschedule">
                                        <button type="submit" class="w-full text-left px-3 py-2 hover:bg-blue-50 rounded flex items-center gap-2">
                                            <i class="fas fa-calendar-alt text-blue-600"></i>
                                            <div>
                                                <div class="font-medium text-sm">Hẹn lại</div>
                                                <div class="text-xs text-gray-500">Khách yêu cầu gọi lại</div>
                                            </div>
                                        </button>
                                    </form>
                                    <form action="{{ route('customer-care-stages.complete-action', $customerCareStage) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="outcome" value="not_interested">
                                        <button type="submit" class="w-full text-left px-3 py-2 hover:bg-red-50 rounded flex items-center gap-2">
                                            <i class="fas fa-times-circle text-red-600"></i>
                                            <div>
                                                <div class="font-medium text-sm">Không quan tâm</div>
                                                <div class="text-xs text-gray-500">Khách từ chối</div>
                                            </div>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Snooze Dropdown -->
                        <div class="relative inline-block" x-data="{ open: false }">
                            <button @click="open = !open" type="button" 
                                    class="px-3 py-1 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">
                                <i class="fas fa-clock mr-1"></i>Dời lịch
                            </button>
                            <div x-show="open" @click.away="open = false" 
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-20"
                                 style="display: none;">
                                <div class="p-2">
                                    <form action="{{ route('customer-care-stages.snooze-action', $customerCareStage) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="snooze" value="1h">
                                        <button type="submit" class="w-full text-left px-3 py-2 hover:bg-gray-50 rounded text-sm">
                                            <i class="fas fa-plus-circle text-blue-600 mr-2"></i>+1 giờ nữa
                                        </button>
                                    </form>
                                    <form action="{{ route('customer-care-stages.snooze-action', $customerCareStage) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="snooze" value="tomorrow_morning">
                                        <button type="submit" class="w-full text-left px-3 py-2 hover:bg-gray-50 rounded text-sm">
                                            <i class="fas fa-sun text-yellow-600 mr-2"></i>Sáng mai (9h)
                                        </button>
                                    </form>
                                    <form action="{{ route('customer-care-stages.snooze-action', $customerCareStage) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="snooze" value="next_monday">
                                        <button type="submit" class="w-full text-left px-3 py-2 hover:bg-gray-50 rounded text-sm">
                                            <i class="fas fa-calendar-week text-green-600 mr-2"></i>Thứ 2 tuần sau (9h)
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <p class="text-gray-600 mb-4">
                @if($customerCareStage->next_action_completed)
                    <i class="fas fa-check-circle text-green-600 mr-1"></i>Đã hoàn thành hành động trước đó!
                @else
                    Chưa có hành động tiếp theo.
                @endif
            </p>
        @endif

        <!-- Quick Action Buttons -->
        <div class="flex flex-wrap gap-2">
            <button onclick="setNextAction('Gọi điện thoại cho khách hàng')" class="px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                <i class="fas fa-phone mr-1"></i>Gọi điện
            </button>
            <button onclick="setNextAction('Gửi email báo giá')" class="px-3 py-2 bg-purple-600 text-white text-sm rounded hover:bg-purple-700">
                <i class="fas fa-envelope mr-1"></i>Gửi email
            </button>
            <button onclick="setNextAction('Sắp xếp cuộc họp')" class="px-3 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                <i class="fas fa-calendar mr-1"></i>Họp
            </button>
            <button onclick="showCustomActionForm()" class="px-3 py-2 bg-gray-600 text-white text-sm rounded hover:bg-gray-700">
                <i class="fas fa-plus mr-1"></i>Tùy chỉnh
            </button>
        </div>
        
        <!-- Custom Action Form (hidden) -->
        <form id="customActionForm" action="{{ route('customer-care-stages.update', $customerCareStage) }}" method="POST" class="mt-4 hidden">
            @csrf
            @method('PUT')
            <div class="bg-white rounded-lg p-4">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hành động</label>
                    <input type="text" name="next_action" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thời hạn</label>
                    <input type="datetime-local" name="next_action_due_at" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <input type="hidden" name="next_action_completed" value="0">
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-1"></i>Lưu
                    </button>
                    <button type="button" onclick="hideCustomActionForm()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                        Hủy
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Communication Logs -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">
                <i class="fas fa-comments mr-2"></i>Lịch sử giao tiếp
            </h3>
            <button onclick="showAddCommunicationForm()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                <i class="fas fa-plus mr-1"></i>Ghi nhận giao tiếp
            </button>
        </div>

        <!-- Add Communication Form (Hidden) -->
        <form id="addCommunicationForm" action="{{ route('communications.store', $customerCareStage) }}" method="POST" class="mb-6 p-4 bg-gray-50 rounded-lg hidden">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loại giao tiếp *</label>
                    <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        <option value="">-- Chọn --</option>
                        <option value="call">Cuộc gọi</option>
                        <option value="email">Email</option>
                        <option value="meeting">Họp</option>
                        <option value="sms">SMS</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="zalo">Zalo</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề *</label>
                    <input type="text" name="subject" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung</label>
                    <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cảm xúc khách hàng</label>
                    <select name="sentiment" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">-- Chọn --</option>
                        <option value="positive">😊 Tích cực</option>
                        <option value="neutral">😐 Bình thường</option>
                        <option value="negative">😞 Tiêu cực</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thời lượng (phút)</label>
                    <input type="number" name="duration_minutes" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian *</label>
                    <input type="datetime-local" name="occurred_at" value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                        <i class="fas fa-save mr-1"></i>Lưu
                    </button>
                    <button type="button" onclick="hideAddCommunicationForm()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                        Hủy
                    </button>
                </div>
            </div>
        </form>

        <!-- Communication Timeline -->
        @if($customerCareStage->communicationLogs->count() > 0)
            <div class="space-y-4">
                @foreach($customerCareStage->communicationLogs as $log)
                    <div class="flex gap-4 p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-{{ $log->sentiment_color ?? 'gray' }}-100 flex items-center justify-center">
                                <i class="fas fa-{{ $log->type == 'call' ? 'phone' : ($log->type == 'email' ? 'envelope' : ($log->type == 'meeting' ? 'calendar' : 'comment')) }} text-{{ $log->sentiment_color ?? 'gray' }}-600"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ $log->subject }}</h4>
                                    <p class="text-sm text-gray-600">{{ $log->type_label }} • {{ $log->user->name }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">{{ $log->occurred_at->format('d/m/Y H:i') }}</p>
                                    @if($log->sentiment)
                                        <span class="inline-block mt-1 px-2 py-1 text-xs rounded-full bg-{{ $log->sentiment_color }}-100 text-{{ $log->sentiment_color }}-800">
                                            {{ $log->sentiment_label }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            @if($log->description)
                                <p class="mt-2 text-gray-700 text-sm">{{ $log->description }}</p>
                            @endif
                            @if($log->duration_minutes)
                                <p class="mt-1 text-xs text-gray-500">
                                    <i class="fas fa-clock mr-1"></i>Thời lượng: {{ $log->duration_minutes }} phút
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-8">
                <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i><br>
                Chưa có ghi nhận giao tiếp nào.
            </p>
        @endif
    </div>

    <!-- Milestones -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Mốc quan trọng</h3>
            <button onclick="showAddMilestoneForm()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                <i class="fas fa-plus mr-1"></i> Thêm mốc
            </button>
        </div>

        <!-- Add Milestone Form (Hidden by default) -->
        <div id="addMilestoneForm" class="hidden mb-4 p-4 bg-gray-50 rounded-lg">
            <form action="{{ route('care-milestones.store', $customerCareStage) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <input type="text" name="title" placeholder="Tiêu đề mốc quan trọng" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <textarea name="description" rows="2" placeholder="Mô tả (tùy chọn)"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                    </div>
                    <div>
                        <input type="date" name="due_date" placeholder="Ngày đến hạn"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                            <i class="fas fa-save mr-1"></i> Lưu
                        </button>
                        <button type="button" onclick="hideAddMilestoneForm()" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            Hủy
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Milestones List -->
        <div class="space-y-3">
            @forelse($customerCareStage->milestones as $milestone)
            <div class="flex items-start gap-3 p-3 border rounded-lg {{ $milestone->is_completed ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200' }}">
                <form action="{{ route('care-milestones.toggle-complete', $milestone) }}" method="POST">
                    @csrf
                    <button type="submit" class="mt-1">
                        <i class="fas {{ $milestone->is_completed ? 'fa-check-circle text-green-600' : 'fa-circle text-gray-300' }} text-xl"></i>
                    </button>
                </form>
                <div class="flex-1">
                    <h4 class="font-semibold {{ $milestone->is_completed ? 'line-through text-gray-500' : 'text-gray-900' }}">
                        {{ $milestone->title }}
                    </h4>
                    @if($milestone->description)
                        <p class="text-sm text-gray-600 mt-1">{{ $milestone->description }}</p>
                    @endif
                    <div class="flex gap-4 mt-2 text-xs text-gray-500">
                        @if($milestone->due_date)
                            <span class="{{ $milestone->is_overdue ? 'text-red-600 font-semibold' : '' }}">
                                <i class="fas fa-calendar mr-1"></i>{{ $milestone->due_date->format('d/m/Y') }}
                            </span>
                        @endif
                        @if($milestone->is_completed)
                            <span class="text-green-600">
                                <i class="fas fa-check mr-1"></i>Hoàn thành {{ $milestone->completed_at->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>
                </div>
                <form action="{{ route('care-milestones.destroy', $milestone) }}" method="POST" class="delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
            @empty
            <p class="text-center text-gray-500 py-4">Chưa có mốc quan trọng nào</p>
            @endforelse
        </div>
    </div>

    <!-- Related Activities -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4">Hoạt động liên quan</h3>
        <div class="space-y-3">
            @forelse($customerCareStage->activities as $activity)
            <div class="flex gap-3 p-3 border rounded-lg">
                <div class="flex-shrink-0">
                    <i class="fas fa-{{ $activity->type == 'call' ? 'phone' : ($activity->type == 'meeting' ? 'users' : ($activity->type == 'email' ? 'envelope' : 'tasks')) }} text-blue-600"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold">{{ $activity->subject }}</h4>
                    @if($activity->description)
                        <p class="text-sm text-gray-600 mt-1">{{ $activity->description }}</p>
                    @endif
                    <div class="flex gap-4 mt-2 text-xs text-gray-500">
                        <span><i class="fas fa-user mr-1"></i>{{ $activity->user->name }}</span>
                        @if($activity->due_date)
                            <span><i class="fas fa-calendar mr-1"></i>{{ $activity->due_date->format('d/m/Y') }}</span>
                        @endif
                        @if($activity->is_completed)
                            <span class="text-green-600"><i class="fas fa-check mr-1"></i>Hoàn thành</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <p class="text-center text-gray-500 py-4">Chưa có hoạt động nào</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function showAddMilestoneForm() {
    document.getElementById('addMilestoneForm').classList.remove('hidden');
}

function hideAddMilestoneForm() {
    document.getElementById('addMilestoneForm').classList.add('hidden');
}

// Communication Log Functions
function showAddCommunicationForm() {
    document.getElementById('addCommunicationForm').classList.remove('hidden');
}

function hideAddCommunicationForm() {
    document.getElementById('addCommunicationForm').classList.add('hidden');
}

// Next Action Functions
function showCustomActionForm() {
    document.getElementById('customActionForm').classList.remove('hidden');
}

function hideCustomActionForm() {
    document.getElementById('customActionForm').classList.add('hidden');
}

function setNextAction(action) {
    const form = document.getElementById('customActionForm');
    form.classList.remove('hidden');
    form.querySelector('input[name="next_action"]').value = action;
    form.querySelector('input[name="next_action"]').focus();
}
</script>
@endpush
@endsection
