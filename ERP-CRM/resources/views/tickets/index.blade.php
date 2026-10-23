@extends('layouts.app')

@section('title', 'Danh sách Yêu cầu (Ticket)')
@section('page-title', 'Danh sách Yêu cầu (Ticket)')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Quản lý Yêu cầu (Ticket)</h2>
            <p class="text-sm text-gray-500">Đặt hàng preload hoặc giữ hàng từ kho/Sales khác</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($isManagerOrAdmin)
                <a href="{{ route('tickets.all') }}" class="inline-flex items-center px-4 py-2 bg-white text-gray-700 border border-gray-200 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                    <i class="fas fa-list-alt mr-2 text-purple-500"></i> Toàn bộ lịch sử yêu cầu
                </a>
                <a href="{{ route('tickets.hold-logs') }}" class="inline-flex items-center px-4 py-2 bg-white text-gray-700 border border-gray-200 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                    <i class="fas fa-history mr-2 text-orange-500"></i> Lịch sử giữ hàng
                </a>
            @endif
            <a href="{{ route('tickets.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> Tạo yêu cầu mới
            </a>
        </div>
    </div>

    <!-- Filter form -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('tickets.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="type" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Loại yêu cầu</label>
                <select name="type" id="type" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="preload" {{ request('type') === 'preload' ? 'selected' : '' }}>Đặt hàng preload</option>
                    <option value="borrow" {{ request('type') === 'borrow' ? 'selected' : '' }}>Giữ hàng</option>
                </select>
            </div>
            <div>
                <label for="status" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Trạng thái</label>
                <select name="status" id="status" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Bị từ chối</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full md:w-auto px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                    Lọc dữ liệu
                </button>
            </div>
        </form>
    </div>

    <!-- Group 1: Pending approvals (For Target sales or managers) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="text-md font-bold text-gray-800 flex items-center">
                <i class="fas fa-clipboard-check text-blue-500 mr-2"></i>
                Yêu cầu cần tôi phê duyệt
            </h3>
            <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-0.5 rounded-full">
                {{ $pendingApprovals->total() }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                    <tr class="divide-x divide-gray-100 border-b border-gray-200">
                        <th class="px-4 py-3 text-center w-12 whitespace-nowrap">STT</th>
                        <th class="px-4 py-3 whitespace-nowrap">Loại</th>
                        <th class="px-4 py-3 whitespace-nowrap">Người yêu cầu</th>
                        <th class="px-4 py-3">Nội dung giữ</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Ngày tạo</th>
                        <th class="px-4 py-3 text-center w-28 whitespace-nowrap">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($pendingApprovals as $ticket)
                        <tr class="hover:bg-gray-50/50 divide-x divide-gray-50">
                            <td class="px-4 py-3 text-center text-gray-500 whitespace-nowrap">
                                {{ ($pendingApprovals->currentPage() - 1) * $pendingApprovals->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->type === 'preload' ? 'blue' : 'purple' }}-50 text-{{ $ticket->type === 'preload' ? 'blue' : 'purple' }}-700">
                                    {{ $ticket->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 font-medium whitespace-nowrap">
                                {{ $ticket->user->name }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-xs md:max-w-md break-words">
                                <div class="space-y-1">
                                    @foreach($ticket->items as $item)
                                        <div>{{ $item->product->name }} (SL: <strong>{{ $item->quantity }}</strong>)</div>
                                    @endforeach
                                    @if($ticket->note)
                                        <div class="text-xs text-gray-400 italic mt-1">Note: "{{ $ticket->note }}"</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500 whitespace-nowrap">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="inline-flex items-center px-2.5 py-1 bg-blue-600 text-white text-xs font-bold rounded hover:bg-blue-700 transition-colors">
                                    Xử lý <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-check-circle text-3xl mb-2 text-green-400"></i>
                                <p>Không có yêu cầu nào đang chờ bạn duyệt</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pendingApprovals->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $pendingApprovals->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    <!-- Group 2: My tickets -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="text-md font-bold text-gray-800 flex items-center">
                <i class="fas fa-history text-gray-500 mr-2"></i>
                Lịch sử yêu cầu của tôi
            </h3>
            <span class="bg-gray-200 text-gray-700 text-xs font-bold px-2 py-0.5 rounded-full">
                {{ $myTickets->total() }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                    <tr class="divide-x divide-gray-100 border-b border-gray-200">
                        <th class="px-4 py-3 text-center w-12 whitespace-nowrap">STT</th>
                        <th class="px-4 py-3 whitespace-nowrap">Loại</th>
                        <th class="px-4 py-3 whitespace-nowrap">Nguồn giữ</th>
                        <th class="px-4 py-3">Nội dung</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Trạng thái</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Ngày tạo</th>
                        <th class="px-4 py-3 text-center w-24 whitespace-nowrap">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($myTickets as $ticket)
                        <tr class="hover:bg-gray-50/50 divide-x divide-gray-50">
                            <td class="px-4 py-3 text-center text-gray-500 whitespace-nowrap">
                                {{ ($myTickets->currentPage() - 1) * $myTickets->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->type === 'preload' ? 'blue' : 'purple' }}-50 text-{{ $ticket->type === 'preload' ? 'blue' : 'purple' }}-700">
                                    {{ $ticket->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                @if($ticket->type === 'borrow')
                                    @if($ticket->source === 'warehouse')
                                        <span class="text-xs bg-teal-50 text-teal-700 px-2 py-0.5 rounded font-medium">Giữ từ kho</span>
                                    @else
                                        <span class="text-xs bg-orange-50 text-orange-700 px-2 py-0.5 rounded font-medium">Giữ từ: {{ $ticket->target_user->name ?? 'N/A' }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-xs md:max-w-md break-words">
                                <div class="space-y-1">
                                    @foreach($ticket->items as $item)
                                        <div>{{ $item->product->name }} (SL: <strong>{{ $item->quantity }}</strong>)</div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500 whitespace-nowrap">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('tickets.show', $ticket->id) }}" class="inline-flex items-center text-gray-500 hover:text-primary transition-colors font-medium whitespace-nowrap text-xs">
                                        Chi tiết <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                    </a>
                                    @if($ticket->type === 'borrow' && $ticket->status === 'approved')
                                        <a href="{{ route('sales.create', ['from_ticket_id' => $ticket->id]) }}" class="inline-flex items-center px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-xs transition-colors shadow-xs" title="Tạo đơn hàng bán">
                                            <i class="fas fa-file-invoice-dollar mr-1"></i> Tạo đơn
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-folder-open text-3xl mb-2 text-gray-300"></i>
                                <p>Bạn chưa gửi yêu cầu nào</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($myTickets->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $myTickets->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    @if($isManagerOrAdmin)
        <!-- Admin / Kho: Tổng hợp hàng runrate đang giữ -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-wrap gap-3 justify-between items-center">
                <h3 class="text-md font-bold text-gray-800 flex items-center">
                    <i class="fas fa-people-carry text-orange-500 mr-2"></i>
                    Tổng hợp hàng runrate đang giữ
                </h3>
                <form method="GET" action="{{ route('tickets.index') }}" class="flex gap-2">
                    @foreach(request()->except(['hold_q']) as $k => $v)
                        @if(!is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <input type="text" name="hold_q" value="{{ request('hold_q') }}" placeholder="Tìm người giữ / mã SP..."
                        class="border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <button type="submit" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200">Tìm</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                        <tr class="divide-x divide-gray-100 border-b border-gray-200">
                            <th class="px-4 py-3 text-center w-12 whitespace-nowrap">STT</th>
                            <th class="px-4 py-3 whitespace-nowrap">Người giữ</th>
                            <th class="px-4 py-3 whitespace-nowrap">Mã SP</th>
                            <th class="px-4 py-3">Tên SP</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap">Đang giữ</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap" title="Phần sẽ được trả về kho khi reset lịch sử giữ hàng">Qua ticket giữ hàng</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap" title="Giữ từ ticket Preload / ghi người giữ lúc nhập kho, không bị reset">Preload / nhập kho</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($holdSummary as $row)
                            <tr class="hover:bg-gray-50/50 divide-x divide-gray-50">
                                <td class="px-4 py-3 text-center text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-medium text-gray-700 whitespace-nowrap">{{ $row->borrower }}</td>
                                <td class="px-4 py-3 font-mono text-gray-700 whitespace-nowrap">{{ $row->product_code }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $row->product_name }}</td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900">{{ (int) $row->qty }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-orange-700">{{ (int) $row->qty_from_tickets }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ (int) $row->qty - (int) $row->qty_from_tickets }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">Không có hàng runrate nào đang được giữ</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($holdSummary->isNotEmpty())
                        <tfoot class="bg-gray-50 font-bold text-gray-800">
                            <tr class="divide-x divide-gray-100 border-t border-gray-200">
                                <td colspan="4" class="px-4 py-3 text-right">Tổng cộng</td>
                                <td class="px-4 py-3 text-center">{{ $holdSummary->sum('qty') }}</td>
                                <td class="px-4 py-3 text-center text-orange-700">{{ $holdSummary->sum('qty_from_tickets') }}</td>
                                <td class="px-4 py-3 text-center">{{ $holdSummary->sum('qty') - $holdSummary->sum('qty_from_tickets') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

    @endif
</div>
@endsection
