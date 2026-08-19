@extends('layouts.app')

@section('title', 'Danh sách Ticket Kỹ thuật')
@section('page-title', 'Danh sách Ticket Kỹ thuật')

@section('content')
<div class="space-y-6" x-data="{ 
    openProgressModal: false, 
    progressActionUrl: '', 
    currentStatus: 'open', 
    currentSolution: '' 
}">
    <!-- Header Block -->
    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Quản lý Ticket Kỹ thuật</h2>
            <p class="text-sm text-gray-500">Xem danh sách, phân công kỹ sư và theo dõi xử lý ticket kỹ thuật</p>
        </div>
        @can('create_technical_tickets')
            <a href="{{ route('technical-tickets.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> Tạo Ticket kỹ thuật
            </a>
        @endcan
    </div>

    <!-- Filters Block -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('technical-tickets.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
                <label for="search" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tìm kiếm</label>
                <input type="text" name="search" id="search" placeholder="Mã hoặc tiêu đề ticket..." value="{{ request('search') }}" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
            </div>

            <div>
                <label for="status" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Trạng thái</label>
                <select name="status" id="status" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Mới tạo</option>
                    <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Đã phân công</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Tạm ngưng</option>
                    <option value="escalate" {{ request('status') === 'escalate' ? 'selected' : '' }}>Cần hỗ trợ</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Đã đóng</option>
                </select>
            </div>

            <div>
                <label for="work_type" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Loại việc</label>
                <select name="work_type" id="work_type" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="survey" {{ request('work_type') === 'survey' ? 'selected' : '' }}>Khảo sát / Tư vấn / Thiết kế</option>
                    <option value="BOM" {{ request('work_type') === 'BOM' ? 'selected' : '' }}>BOM Support</option>
                    <option value="documentation" {{ request('work_type') === 'documentation' ? 'selected' : '' }}>Technical Documents</option>
                    <option value="POC" {{ request('work_type') === 'POC' ? 'selected' : '' }}>POC / Demo</option>
                    <option value="deployment" {{ request('work_type') === 'deployment' ? 'selected' : '' }}>Deployment</option>
                    <option value="after_sales" {{ request('work_type') === 'after_sales' ? 'selected' : '' }}>After-sales support</option>
                    <option value="training" {{ request('work_type') === 'training' ? 'selected' : '' }}>Training / Update</option>
                    <option value="event" {{ request('work_type') === 'event' ? 'selected' : '' }}>Event / Speaker</option>
                    <option value="other" {{ request('work_type') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div>
                <label for="assigned_to" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kỹ sư</label>
                <select name="assigned_to" id="assigned_to" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    @foreach($engineers as $eng)
                        <option value="{{ $eng->id }}" {{ request('assigned_to') == $eng->id ? 'selected' : '' }}>{{ $eng->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="sla_status" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Trạng thái SLA</label>
                <select name="sla_status" id="sla_status" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="ontime" {{ request('sla_status') === 'ontime' ? 'selected' : '' }}>Kịp hạn (On time)</option>
                    <option value="overdue" {{ request('sla_status') === 'overdue' ? 'selected' : '' }}>Trễ hạn (Overdue)</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm">
                    Lọc
                </button>
                <a href="{{ route('technical-tickets.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-colors border border-gray-200">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table Block -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                    <tr class="divide-x divide-gray-150 border-b border-gray-200">
                        <th class="px-3 py-2.5 text-center w-12">STT</th>
                        <th class="px-3 py-2.5 w-32">Mã Ticket</th>
                        <th class="px-3 py-2.5 max-w-xs md:max-w-md">Tiêu đề / Công việc</th>
                        <th class="px-3 py-2.5 w-44">Loại công việc</th>
                        <th class="px-3 py-2.5 w-40">Kỹ sư phụ trách</th>
                        <th class="px-3 py-2.5 text-center w-28">Độ ưu tiên</th>
                        <th class="px-3 py-2.5 text-center w-36">Hạn SLA</th>
                        <th class="px-3 py-2.5 text-center w-32">Trạng thái</th>
                        <th class="px-3 py-2.5 text-center w-24">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-gray-50/50 divide-x divide-gray-100 {{ $ticket->is_overdue ? 'bg-red-50/20' : '' }}">
                            <td class="px-3 py-2 text-center text-gray-500">
                                {{ ($tickets->currentPage() - 1) * $tickets->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-3 py-2 font-bold text-gray-700 whitespace-nowrap">
                                {{ $ticket->code }}
                            </td>
                            <td class="px-3 py-2 max-w-xs md:max-w-md break-words">
                                <div class="font-semibold text-gray-900 leading-tight break-all">{{ $ticket->title }}</div>
                                @if($ticket->project)
                                    <div class="text-xs text-purple-600 font-semibold mt-0.5"><i class="fas fa-diagram-project mr-1"></i> Dự án: {{ $ticket->project->name }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600 leading-snug">
                                {{ $ticket->work_type_label }}
                            </td>
                            <td class="px-3 py-2 text-gray-700 font-medium leading-snug">
                                {{ $ticket->assignedEngineers->pluck('name')->join(', ') ?: 'Chưa phân công' }}
                            </td>
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $ticket->priority_color }}-100 text-{{ $ticket->priority_color }}-800">
                                    {{ $ticket->priority_label }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center text-gray-500 whitespace-nowrap {{ $ticket->is_overdue ? 'text-red-600 font-bold' : '' }}">
                                {{ $ticket->sla_deadline ? $ticket->sla_deadline->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800 whitespace-nowrap">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1">
                                    <!-- Chi tiết -->
                                    <a href="{{ route('technical-tickets.show', $ticket->id) }}" 
                                        title="Chi tiết" 
                                        class="inline-flex items-center p-1.5 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <!-- Tự nhận (Pickup) -->
                                    @if(empty($ticket->assigned_to))
                                        @php
                                            $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
                                            $isTechLeadRole = auth()->user()->hasRole('technical_lead');
                                            $isTeamLead = $isTechLeadRole || $isManagerOrAdmin;
                                            $isTechStaff = auth()->user()->hasAnyRole(['technical_lead', 'technical_engineer', 'super_admin']);
                                            $canPickup = $isTechStaff && ($isTeamLead || !in_array($ticket->work_type, ['BOM', 'documentation', 'after_sales']));
                                        @endphp
                                        @if($canPickup)
                                            <form action="{{ route('technical-tickets.pickup', $ticket->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn nhận ticket này?');">
                                                @csrf
                                                <button type="submit" 
                                                    title="Tự nhận (Pickup)"
                                                    class="inline-flex items-center p-1.5 bg-indigo-600 text-white text-xs font-bold rounded hover:bg-indigo-700 transition-colors">
                                                    <i class="fas fa-hand-holding-hand"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif

                                    <!-- Cập nhật tiến độ -->
                                    @if(!in_array($ticket->status, ['open', 'completed', 'closed']))
                                        @php
                                            $canUpdateProgress = $ticket->assignedEngineers()->where('users.id', auth()->id())->exists()
                                                || auth()->user()->hasRole('technical_lead')
                                                || auth()->user()->hasAnyRole(['super_admin', 'director']);
                                        @endphp
                                        @if($canUpdateProgress)
                                            <button @click="progressActionUrl = '{{ route('technical-tickets.update-progress', $ticket->id) }}'; currentStatus = '{{ $ticket->status }}'; currentSolution = {{ json_encode($ticket->solution) }}; openProgressModal = true" 
                                                title="Cập nhật tiến độ"
                                                class="inline-flex items-center p-1.5 bg-emerald-600 text-white text-xs font-bold rounded hover:bg-emerald-700 transition-colors">
                                                <i class="fas fa-tasks"></i>
                                            </button>
                                        @endif
                                    @endif

                                    @if($ticket->status !== 'closed')
                                        <!-- Chỉnh sửa -->
                                        @can('edit_technical_tickets')
                                            @if(!auth()->user()->hasRole('technical_engineer'))
                                                <a href="{{ route('technical-tickets.edit', $ticket->id) }}" 
                                                    title="Chỉnh sửa"
                                                    class="inline-flex items-center p-1.5 bg-yellow-500 text-white text-xs font-bold rounded hover:bg-yellow-600 transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                        @endcan

                                        <!-- Xóa ticket -->
                                        @can('delete_technical_tickets')
                                            <form action="{{ route('technical-tickets.destroy', $ticket->id) }}" method="POST"
                                                onsubmit="return confirm('Bạn có chắc chắn muốn xóa ticket này và toàn bộ tài liệu đính kèm?');"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                    title="Xóa ticket"
                                                    class="inline-flex items-center p-1.5 bg-red-600 text-white text-xs font-bold rounded hover:bg-red-700 transition-colors">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-10 text-center text-gray-400 italic">Không tìm thấy ticket kỹ thuật nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $tickets->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    <!-- Progress Modal -->
    <div x-show="openProgressModal"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div @click.away="openProgressModal = false"
            class="bg-white rounded-xl shadow-lg max-w-lg w-full border border-gray-200 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="text-md font-bold text-gray-900">
                    Cập nhật tiến độ & Phương án xử lý
                </h3>
                <button @click="openProgressModal = false"
                    class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="progressActionUrl" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <!-- Action flag -->
                <input type="hidden" name="action" value="update_solution">

                <!-- Status Checkboxes -->
                <div x-show="currentStatus !== 'completed' && currentStatus !== 'closed'" class="space-y-2">
                    <div class="flex items-center space-x-2 bg-emerald-50 border border-emerald-100 p-3 rounded-lg">
                        <input type="checkbox" name="is_completed" id="progress_is_completed" value="1"
                            class="h-4 w-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                        <label for="progress_is_completed" class="text-sm font-semibold text-emerald-800 cursor-pointer">Đã hoàn thành công việc kỹ thuật (Completed)</label>
                    </div>
                    <div class="flex items-center space-x-2 bg-purple-50 border border-purple-100 p-3 rounded-lg">
                        <input type="checkbox" name="is_waiting" id="progress_is_waiting_index" value="1"
                            :checked="currentStatus === 'waiting'"
                            class="h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <label for="progress_is_waiting_index" class="text-sm font-semibold text-purple-800 cursor-pointer">Chờ phản hồi từ Khách hàng / Đối tác / Nhà cung cấp</label>
                    </div>
                </div>

                <!-- Solution / Evaluation -->
                <div>
                    <label for="progress_solution" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nguyên nhân / Phương án / Cách xử lý</label>
                    <textarea name="solution" id="progress_solution" rows="6" x-model="currentSolution"
                        placeholder="Nhập nguyên nhân lỗi, phương án khắc phục, cấu hình chi tiết..."
                        class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"></textarea>
                </div>

                <!-- Modal Actions -->
                <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
                    <button type="button" @click="openProgressModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors bg-white">
                        Hủy bỏ
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm">
                        Lưu cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
