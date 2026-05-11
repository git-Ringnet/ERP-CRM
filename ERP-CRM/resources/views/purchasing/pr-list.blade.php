@extends('layouts.app')

@section('content')
<div class="">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Danh sách Yêu cầu đặt hàng (PR)</h1>
            <p class="text-sm text-gray-600">Quản lý các yêu cầu từ bộ phận Sales</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('purchase-requests.needs-ordering') }}" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center shadow-md">
                <i class="fas fa-layer-group mr-2"></i> Gom đơn đặt hàng (CORE)
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('purchase-requests.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Trạng thái</label>
                <select name="status" class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                    <option value="">Tất cả đang xử lý</option>
                    @foreach($statusLabels as $val => $label)
                        @if(in_array($val, [\App\Models\SaleOrderRequest::STATUS_SUBMITTED, \App\Models\SaleOrderRequest::STATUS_PROCESSING]))
                            <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition-colors text-sm">
                Lọc dữ liệu
            </button>
        </form>
    </div>

    <!-- PR Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Mã PR</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Đơn hàng (SO)</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Người yêu cầu</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Ngày gửi</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Số lượng Item</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Trạng thái</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $request)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-bold text-teal-700">#{{ $request->code }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('sales.show', $request->sale_id) }}" class="text-blue-600 hover:underline">
                            {{ $request->sale->code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $request->creator->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $request->sent_at ? $request->sent_at->format('d/m/Y H:i') : 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $request->items->count() }} mặt hàng</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-{{ $request->status_color }}-100 text-{{ $request->status_color }}-700 uppercase">
                            {{ $request->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            @if($request->status === \App\Models\SaleOrderRequest::STATUS_SUBMITTED)
                            <form action="{{ route('purchase-requests.verify', $request->id) }}" method="POST" onsubmit="return confirm('Duyệt yêu cầu này?')">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="text-green-600 hover:text-green-800 p-1" title="Duyệt">
                                    <i class="fas fa-check-circle text-lg"></i>
                                </button>
                            </form>
                            <button type="button" onclick="showRejectModal('{{ $request->id }}', '{{ $request->code }}')" class="text-red-600 hover:text-red-800 p-1" title="Trả về">
                                <i class="fas fa-times-circle text-lg"></i>
                            </button>
                            @endif
                            <button type="button" onclick="toggleDetails('{{ $request->id }}')" class="text-gray-400 hover:text-gray-600 p-1">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <!-- Details Row (Hidden by default) -->
                <tr id="details-{{ $request->id }}" class="hidden bg-gray-50">
                    <td colspan="7" class="px-6 py-4">
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <h4 class="font-bold text-sm mb-3 text-gray-700">Chi tiết sản phẩm yêu cầu:</h4>
                            <table class="w-full text-xs border-collapse">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border p-2">Hãng</th>
                                        <th class="border p-2">Sản phẩm / P/N</th>
                                        <th class="border p-2 text-center">Yêu cầu</th>
                                        <th class="border p-2 text-center">Đã đặt</th>
                                        <th class="border p-2 text-center">Còn lại</th>
                                        <th class="border p-2">SI Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($request->items as $item)
                                    <tr>
                                        <td class="border p-2">{{ $item->vendor }}</td>
                                        <td class="border p-2 font-medium">{{ $item->part_number }}</td>
                                        <td class="border p-2 text-center">{{ $item->quantity }}</td>
                                        <td class="border p-2 text-center text-blue-600">{{ $item->ordered_quantity_total }}</td>
                                        <td class="border p-2 text-center font-bold {{ $item->remaining_order_quantity > 0 ? 'text-red-500' : 'text-green-600' }}">
                                            {{ $item->remaining_order_quantity }}
                                        </td>
                                        <td class="border p-2 text-gray-500">{{ $item->si_name }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if($request->note)
                            <div class="mt-3 p-2 bg-yellow-50 rounded border border-yellow-100 text-xs text-yellow-800">
                                <strong>Ghi chú từ Sales:</strong> {{ $request->note }}
                            </div>
                            @endif
                            @if($request->rejection_note)
                            <div class="mt-3 p-2 bg-red-50 rounded border border-red-100 text-xs text-red-800">
                                <strong>Lý do trả về:</strong> {{ $request->rejection_note }}
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p>Không có yêu cầu đặt hàng nào cần xử lý.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-4">Trả về yêu cầu <span id="rejectPrCode"></span></h3>
        <form id="rejectForm" method="POST">
            @csrf
            <input type="hidden" name="action" value="reject">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Lý do (Yêu cầu Sales bổ sung thông tin gì?)</label>
                <textarea name="rejection_note" required rows="4" class="w-full border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Ví dụ: Thiếu thông tin SI Name chính xác..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold">Xác nhận trả về</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function toggleDetails(id) {
        const row = document.getElementById('details-' + id);
        row.classList.toggle('hidden');
    }

    function showRejectModal(id, code) {
        document.getElementById('rejectPrCode').innerText = '#' + code;
        document.getElementById('rejectForm').action = `/purchase-requests/${id}/verify`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
