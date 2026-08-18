@extends('layouts.app')

@section('title', 'Danh sách Ticket Kỹ thuật')
@section('page-title', 'Danh sách Ticket Kỹ thuật')

@section('content')
<div class="space-y-6">
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
                        <th class="px-3 py-2.5">Tiêu đề / Công việc</th>
                        <th class="px-3 py-2.5 w-44">Loại công việc</th>
                        <th class="px-3 py-2.5 w-40">Khách hàng</th>
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
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-900 leading-tight">{{ $ticket->title }}</div>
                                @if($ticket->project)
                                    <div class="text-xs text-purple-600 font-semibold mt-0.5"><i class="fas fa-diagram-project mr-1"></i> Dự án: {{ $ticket->project->name }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600 leading-snug">
                                {{ $ticket->work_type_label }}
                            </td>
                            <td class="px-3 py-2 text-gray-600 leading-snug">
                                {{ $ticket->customer->name ?? 'N/A' }}
                            </td>
                            <td class="px-3 py-2 text-gray-700 font-medium leading-snug">
                                {{ $ticket->assignedTo->name ?? 'Chưa phân công' }}
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
                                <a href="{{ route('technical-tickets.show', $ticket->id) }}" class="inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-eye mr-1"></i> Chi tiết
                                </a>
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
</div>
@endsection
