@extends('layouts.app')

@section('title', 'Danh sách Công việc')

@section('content')
    <div class="h-full flex flex-col">
        <!-- Header Section -->
        <div class="bg-white rounded-lg shadow-sm mb-4 shrink-0">
            <div class="p-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-tasks text-blue-500 mr-2"></i>Danh sách Công việc
                    </h2>
                    
                    <div class="flex flex-wrap items-center gap-2">
                        <form action="{{ route('activities.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
                            <select name="status" class="border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[160px]" onchange="this.form.submit()">
                                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Tất cả</option>
                                <option value="pending" {{ request('status', 'pending') == 'pending' ? 'selected' : '' }}>Chưa hoàn thành</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                            </select>
                            
                            <select name="type" class="border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[160px]" onchange="this.form.submit()">
                                <option value="">Tất cả loại</option>
                                <option value="call" {{ request('type') == 'call' ? 'selected' : '' }}>Cuộc gọi</option>
                                <option value="meeting" {{ request('type') == 'meeting' ? 'selected' : '' }}>Cuộc gặp</option>
                                <option value="email" {{ request('type') == 'email' ? 'selected' : '' }}>Email</option>
                                <option value="task" {{ request('type') == 'task' ? 'selected' : '' }}>Công việc</option>
                            </select>
                        </form>
                        <button onclick="document.getElementById('addActivityModal').classList.remove('hidden')" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                            <i class="fas fa-plus mr-2"></i> Thêm việc
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task List -->
        <div class="bg-white rounded-lg shadow-sm flex-1 p-4">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hoàn thành</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Công việc</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hạn chót</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Liên quan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($activities as $activity)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 w-10">
                                    @if(!$activity->is_completed)
                                        <button onclick="openCompleteModal('{{ $activity->id }}', '{{ $activity->subject }}')" class="text-gray-400 hover:text-green-500">
                                            <i class="far fa-square fa-lg"></i>
                                        </button>
                                    @else
                                        <button disabled class="text-green-500 cursor-default">
                                            <i class="fas fa-check-square fa-lg"></i>
                                        </button>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $activity->subject }}</div>
                                    @if($activity->description)
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ $activity->description }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($activity->due_date)
                                        <span
                                            class="text-sm {{ $activity->due_date->isPast() ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                            {{ $activity->due_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($activity->opportunity)
                                        <a href="{{ route('opportunities.show', $activity->opportunity) }}"
                                            class="text-blue-600 hover:underline">
                                            <i class="fas fa-funnel-dollar mr-1"></i> {{ $activity->opportunity->name }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $activity->customer->name ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-check-circle text-4xl mb-3 text-gray-300"></i>
                                    <p>Tuyệt vời! Bạn không còn công việc nào tồn đọng.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $activities->links() }}
            </div>
        </div>
    </div>
    <!-- Create Activity Modal -->
    <div id="addActivityModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4" x-data="{ type: 'task' }">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Thêm công việc mới</h3>
                <button onclick="document.getElementById('addActivityModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="{{ route('activities.store') }}" method="POST">
                @csrf
                <div class="p-4 space-y-4">
                    <!-- Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Loại công việc</label>
                        <div class="flex space-x-2 border-b border-gray-200 pb-2">
                            <button type="button" @click="type = 'call'" :class="{ 'text-blue-600 border-b-2 border-blue-600 font-semibold': type === 'call', 'text-gray-500 hover:text-gray-700': type !== 'call' }" class="pb-2 px-2 text-sm transition-colors"><i class="fas fa-phone mr-1"></i> Gọi</button>
                            <button type="button" @click="type = 'meeting'" :class="{ 'text-blue-600 border-b-2 border-blue-600 font-semibold': type === 'meeting', 'text-gray-500 hover:text-gray-700': type !== 'meeting' }" class="pb-2 px-2 text-sm transition-colors"><i class="fas fa-users mr-1"></i> Gặp</button>
                            <button type="button" @click="type = 'task'" :class="{ 'text-blue-600 border-b-2 border-blue-600 font-semibold': type === 'task', 'text-gray-500 hover:text-gray-700': type !== 'task' }" class="pb-2 px-2 text-sm transition-colors"><i class="fas fa-check-square mr-1"></i> Việc</button>
                            <button type="button" @click="type = 'email'" :class="{ 'text-blue-600 border-b-2 border-blue-600 font-semibold': type === 'email', 'text-gray-500 hover:text-gray-700': type !== 'email' }" class="pb-2 px-2 text-sm transition-colors"><i class="fas fa-envelope mr-1"></i> Email</button>
                        </div>
                        <input type="hidden" name="type" x-model="type">
                    </div>

                    <!-- Subject -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span class="text-red-500">*</span></label>
                        <input type="text" name="subject" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Ví dụ: Gửi báo giá, Gọi lại khách hàng...">
                    </div>

                    <!-- Due Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hạn hoàn thành</label>
                        <input type="date" name="due_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Chi tiết công việc..."></textarea>
                    </div>
                </div>
                
                <div class="p-4 border-t border-gray-200 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addActivityModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">Hủy</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">Lưu công việc</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Complete Activity Modal -->
    <div id="completeActivityModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4" x-data="{ hasNextAction: false }">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Hoàn thành công việc</h3>
                <button onclick="closeCompleteModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="completeActivityForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="complete_with_result" value="1">
                
                <div class="p-4 space-y-4">
                    <p class="text-sm text-gray-600">Bạn đang hoàn thành công việc: <span id="activitySubject" class="font-semibold text-gray-800"></span></p>
                    
                    <!-- Result Note -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kết quả công việc <span class="text-red-500">*</span></label>
                        <textarea name="result_note" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="Ghi chú kết quả (VD: Khách hàng đồng ý, Đã gửi email...)"></textarea>
                    </div>

                    <!-- Next Action Toggle -->
                    <div class="flex items-center">
                        <input type="checkbox" id="hasNextAction" name="has_next_action" value="1" x-model="hasNextAction" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="hasNextAction" class="ml-2 block text-sm text-gray-900 font-medium">
                            Lên kế hoạch tiếp theo?
                        </label>
                    </div>

                    <!-- Next Action Fields -->
                    <div x-show="hasNextAction" x-transition class="space-y-4 border-l-2 border-blue-100 pl-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Công việc tiếp theo <span class="text-red-500">*</span></label>
                            <input type="text" name="next_action_subject" :required="hasNextAction" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" placeholder="VD: Gọi lại chốt đơn...">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày <span class="text-red-500">*</span></label>
                                <input type="date" name="next_action_date" :required="hasNextAction" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Loại <span class="text-red-500">*</span></label>
                                <select name="next_action_type" :required="hasNextAction" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                    <option value="call">Gọi điện</option>
                                    <option value="meeting">Gặp mặt</option>
                                    <option value="email">Email</option>
                                    <option value="task">Công việc</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 border-t border-gray-200 flex justify-end gap-2">
                    <button type="button" onclick="closeCompleteModal()" class="px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">Hủy</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">Hoàn thành & Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCompleteModal(id, subject) {
            document.getElementById('completeActivityModal').classList.remove('hidden');
            document.getElementById('activitySubject').textContent = subject;
            // Set form action dynamically
            document.getElementById('completeActivityForm').action = "/activities/" + id;
        }

        function closeCompleteModal() {
            document.getElementById('completeActivityModal').classList.add('hidden');
            document.getElementById('completeActivityForm').reset();
        }
    </script>
@endsection