@extends('layouts.app')

@section('title', 'Toàn bộ lịch sử yêu cầu')
@section('page-title', 'Toàn bộ lịch sử yêu cầu')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-2">
        <a href="{{ route('tickets.index') }}"
            class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-gray-700">
            <i class="fas fa-chevron-left mr-1"></i> Quay lại danh sách
        </a>
    </div>

    <!-- Filter form -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('tickets.all') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            <div>
                <label for="user_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Người yêu cầu</label>
                <select name="user_id" id="user_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    @foreach($ticketUsers as $u)
                        <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full md:w-auto px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                    Lọc dữ liệu
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="text-md font-bold text-gray-800 flex items-center">
                <i class="fas fa-list-alt text-purple-500 mr-2"></i>
                Toàn bộ lịch sử yêu cầu (tất cả nhân viên)
            </h3>
            <span class="bg-purple-100 text-purple-800 text-xs font-bold px-2 py-0.5 rounded-full">
                {{ $allTickets->total() }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                    <tr class="divide-x divide-gray-100 border-b border-gray-200">
                        <th class="px-4 py-3 whitespace-nowrap">Mã ticket</th>
                        <th class="px-4 py-3 whitespace-nowrap">Loại</th>
                        <th class="px-4 py-3 whitespace-nowrap">Người yêu cầu</th>
                        <th class="px-4 py-3 whitespace-nowrap">Nguồn giữ</th>
                        <th class="px-4 py-3">Nội dung</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Trạng thái</th>
                        <th class="px-4 py-3 whitespace-nowrap">Người duyệt</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Ngày tạo</th>
                        <th class="px-4 py-3 text-center w-20 whitespace-nowrap">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($allTickets as $ticket)
                        <tr class="hover:bg-gray-50/50 divide-x divide-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700 whitespace-nowrap">{{ $ticket->code }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $ticket->type === 'preload' ? 'blue' : 'purple' }}-50 text-{{ $ticket->type === 'preload' ? 'blue' : 'purple' }}-700">
                                    {{ $ticket->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 font-medium whitespace-nowrap">{{ $ticket->user->name ?? 'N/A' }}</td>
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
                                        <div>{{ $item->product->code ?? '' }} - {{ $item->product->name ?? '' }} (SL: <strong>{{ $item->quantity }}</strong>)</div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $ticket->approver->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-gray-500 whitespace-nowrap">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="inline-flex items-center text-gray-500 hover:text-primary transition-colors font-medium text-xs">
                                    Chi tiết <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">Chưa có yêu cầu nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($allTickets->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $allTickets->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
