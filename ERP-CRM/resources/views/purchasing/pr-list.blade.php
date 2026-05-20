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
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Mã PR</label>
                <input type="text" name="code" value="{{ request('code') }}" placeholder="SOR..." class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Mã SO</label>
                <input type="text" name="sale_code" value="{{ request('sale_code') }}" placeholder="SO..." class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Note / P.I.C</label>
                <input type="text" name="note" value="{{ request('note') }}" placeholder="Tìm trong ghi chú..." class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
            </div>
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
            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors text-sm shadow-sm">
                <i class="fas fa-search mr-1"></i> Lọc dữ liệu
            </button>
            <a href="{{ route('purchase-requests.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                Xóa lọc
            </a>
            <a href="{{ route('purchase-requests.deleted') }}" class="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-lg hover:bg-red-100 transition-colors text-sm flex items-center shadow-sm ml-auto">
                <i class="fas fa-trash-alt mr-2"></i> PR đã xóa
            </a>
        </form>
    </div>

    <!-- PR Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Mã PR</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Đơn hàng (mã SO)</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Hãng</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Người yêu cầu (Sales)</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Ngày gửi</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Note</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Trạng thái</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $request)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-bold text-teal-700">#{{ $request->code }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('sales.show', $request->sale_id) }}" class="text-blue-600 hover:underline font-medium">
                            {{ $request->sale->code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1">
                            @php
                                $uniqueVendors = $request->items->pluck('vendor')->unique()->filter();
                                if ($uniqueVendors->isEmpty()) {
                                    $uniqueVendors = $request->items->map(fn($i) => $i->vendor->name ?? null)->unique()->filter();
                                }
                            @endphp
                            <div class="text-sm font-medium text-gray-800">
                                {{ $uniqueVendors->implode(', ') ?: 'N/A' }}
                            </div>
                            @if($request->attachments->count() > 0)
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($request->attachments as $attachment)
                                <a href="javascript:void(0)" 
                                   onclick="openFilePreviewModal('{{ route('sales.order-request.attachment.preview', ['sale' => $request->sale_id, 'attachment' => $attachment->id]) }}', '{{ $attachment->file_name }}')"
                                   class="inline-flex items-center text-[10px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded hover:bg-blue-100 transition-colors"
                                   title="{{ $attachment->file_name }}">
                                    <i class="fas fa-eye mr-1"></i> {{ \Illuminate\Support\Str::limit($attachment->file_name, 15) }}
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $request->creator->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $request->sent_at ? $request->sent_at->format('d/m/Y H:i') : 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm max-w-xs">
                        <div class="flex items-center group">
                            <div class="truncate text-gray-600 mr-2" title="{{ $request->note }}">
                                {{ $request->note ?: '-' }}
                            </div>
                            <button type="button" onclick="showNoteModal('{{ $request->id }}', '{{ $request->code }}', '{{ addslashes($request->note) }}')" 
                                    class="text-teal-500 hover:text-teal-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
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

                            @can('delete', $request)
                                @if(in_array($request->status, [\App\Models\SaleOrderRequest::STATUS_DRAFT, \App\Models\SaleOrderRequest::STATUS_SUBMITTED, \App\Models\SaleOrderRequest::STATUS_NEED_INFO]))
                                <form action="{{ route('purchase-requests.destroy', $request->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDeleteWithReason(this.form, 'yêu cầu đặt hàng')" class="text-red-600 hover:text-red-800 p-1" title="Xóa PR">
                                        <i class="fas fa-trash-alt text-lg"></i>
                                    </button>
                                </form>
                                @endif
                            @endcan

                            <button type="button" onclick="toggleDetails('{{ $request->id }}')" class="text-gray-400 hover:text-gray-600 p-1">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <!-- Details Row (Hidden by default) -->
                <tr id="details-{{ $request->id }}" class="hidden bg-gray-50">
                    <td colspan="8" class="px-6 py-4">
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <h4 class="font-bold text-sm mb-3 text-gray-700">Chi tiết sản phẩm yêu cầu:</h4>
                            <table class="w-full text-[10px] border-collapse border border-gray-200">
                                <thead class="bg-yellow-100">
                                    <tr class="border-b border-gray-300">
                                        <th class="border-r border-gray-300 p-2 text-left">Hãng</th>
                                        <th class="border-r border-gray-300 p-2 text-left">Sản phẩm</th>
                                        <th class="border-r border-gray-300 p-2 text-center">Số lượng</th>
                                        <th class="border-r border-gray-300 p-2 text-center">% Lợi nhuận</th>
                                        <th class="border-r border-gray-300 p-2 text-left">S/N (Nếu có)</th>
                                        <th class="border-r border-gray-300 p-2 text-left">Ngày Exp (Nếu có)</th>
                                        <th class="border-r border-gray-300 p-2 text-left">SI Name</th>
                                        <th class="border-r border-gray-300 p-2 text-left">EU Name</th>
                                        <th class="p-2 text-left">Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($request->items as $item)
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="border-r border-gray-200 p-2">{{ $item->vendor->name ?? $item->vendor }}</td>
                                        <td class="border-r border-gray-200 p-2 font-medium text-teal-700">
                                            {{ $item->part_number }}
                                            @if($item->is_cancelled)
                                                <span class="ml-1 px-1.5 py-0.5 text-[8px] bg-red-100 text-red-600 rounded font-bold">ĐÃ HỦY</span>
                                            @endif
                                        </td>
                                        <td class="border-r border-gray-200 p-2 text-center font-bold">{{ $item->quantity + 0 }}</td>
                                        <td class="border-r border-gray-200 p-2 text-center text-blue-600">
                                            {{ number_format($item->saleItem->profit_percent ?? 0, 2) }}%
                                        </td>
                                        <td class="border-r border-gray-200 p-2 text-gray-500">{{ $item->serial_number ?: '-' }}</td>
                                        <td class="border-r border-gray-200 p-2 text-gray-500">{{ $item->exp_date ? $item->exp_date->format('d/m/Y') : '-' }}</td>
                                        <td class="border-r border-gray-200 p-2 text-gray-700">{{ $item->si_name }}</td>
                                        <td class="border-r border-gray-200 p-2 text-gray-600">{{ $item->eu_name_mst }}</td>
                                        <td class="p-2 text-gray-500">{{ $item->note ?: '-' }}</td>
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
                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
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

<!-- Note/PIC Modal -->
<div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Cập nhật Ghi chú / P.I.C <span id="notePrCode" class="text-teal-600"></span></h3>
            <button type="button" onclick="closeNoteModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="noteForm" method="POST">
            @csrf
            @method('PATCH')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Thông tin Ghi chú / P.I.C</label>
                <textarea name="note" id="noteTextarea" rows="4" class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500" placeholder="Nhập thông tin người phụ trách hoặc ghi chú khác..."></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeNoteModal()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-bold shadow-md">Lưu thay đổi</button>
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

    function showNoteModal(id, code, note) {
        document.getElementById('notePrCode').innerText = '#' + code;
        document.getElementById('noteTextarea').value = note;
        document.getElementById('noteForm').action = `/purchase-requests/${id}/update-note`;
        document.getElementById('noteModal').classList.remove('hidden');
    }

    function closeNoteModal() {
        document.getElementById('noteModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
