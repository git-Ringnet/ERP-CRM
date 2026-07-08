@extends('layouts.app')

@section('content')
    <div class="">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Danh sách Yêu cầu đặt hàng (PR)</h1>
                <p class="text-sm text-gray-600">Quản lý các yêu cầu từ bộ phận Sales</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('purchase-requests.needs-ordering') }}"
                    class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors flex items-center shadow-md">
                    <i class="fas fa-layer-group mr-2"></i> Gom đơn đặt hàng (CORE)
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
            <form action="{{ route('purchase-requests.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Mã SO</label>
                    <input type="text" name="sale_code" value="{{ request('sale_code') }}" placeholder="SO..."
                        class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Note / P.I.C</label>
                    <input type="text" name="note" value="{{ request('note') }}" placeholder="Tìm trong ghi chú..."
                        class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1 uppercase">Trạng thái</label>
                    <select name="status"
                        class="border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Tất cả</option>
                        @foreach($statusLabels as $val => $label)
                            @if($val !== \App\Models\SaleOrderRequest::STATUS_DRAFT)
                                <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors text-sm shadow-sm">
                    <i class="fas fa-search mr-1"></i> Lọc dữ liệu
                </button>
                <a href="{{ route('purchase-requests.index') }}"
                    class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                    Xóa lọc
                </a>
                <a href="{{ route('purchase-requests.deleted') }}"
                    class="bg-red-50 text-red-600 border border-red-200 px-4 py-2 rounded-lg hover:bg-red-100 transition-colors text-sm flex items-center shadow-sm ml-auto">
                    <i class="fas fa-trash-alt mr-2"></i> PR đã xóa
                </a>
            </form>
        </div>

        <!-- PR Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Đơn hàng (mã SO)</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Hãng</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Người yêu cầu (Sales)
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Ngày gửi</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Note</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Trạng
                            thái</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right sticky right-0 bg-gray-50 z-20 shadow-[-8px_0_10px_-8px_rgba(0,0,0,0.15)]">
                            Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($requests as $request)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4">
                                <a href="{{ route('sales.show', $request->sale_id) }}"
                                    class="text-blue-600 hover:underline font-medium">
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
                                                    <i class="fas fa-eye mr-1"></i>
                                                    {{ \Illuminate\Support\Str::limit($attachment->file_name, 15) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $request->creator->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $request->sent_at ? $request->sent_at->format('d/m/Y H:i') : 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm max-w-xs">
                                <div class="flex items-center group">
                                    <div class="truncate text-gray-600 mr-2" title="{{ $request->note }}">
                                        {{ $request->note ?: '-' }}
                                    </div>
                                    <button type="button"
                                        onclick="showNoteModal('{{ $request->id }}', '{{ $request->code }}', '{{ addslashes($request->note) }}')"
                                        class="text-teal-500 hover:text-teal-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="px-2.5 py-1 rounded-full text-xs font-bold bg-{{ $request->status_color }}-100 text-{{ $request->status_color }}-700 uppercase">
                                    {{ $request->status_label }}
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 text-right sticky right-0 bg-white group-hover:bg-gray-50 z-10 shadow-[-8px_0_10px_-8px_rgba(0,0,0,0.15)] transition-colors">
                                <div class="flex justify-end gap-2">
                                    @php
                                        $currentUser = auth()->user();
                                        $canApproveAdmin = $currentUser && ($currentUser->hasRole('admin') || $currentUser->hasRole('super_admin') || $currentUser->hasRole('purchase_manager'));
                                    @endphp

                                    @if($request->status === \App\Models\SaleOrderRequest::STATUS_PENDING_ADMIN && $canApproveAdmin)
                                        <form action="{{ route('sales.order-request.admin-approve', [$request->sale_id, $request->id]) }}" method="POST"
                                            onsubmit="return confirm('Xác nhận duyệt yêu cầu đặt hàng này?')">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800 p-1" title="Duyệt PR">
                                                <i class="fas fa-check-circle text-lg"></i>
                                            </button>
                                        </form>
                                        <button type="button"
                                            onclick="showAdminRejectModal('{{ $request->id }}', '{{ $request->code }}', '{{ $request->sale_id }}')"
                                            class="text-red-600 hover:text-red-800 p-1" title="Trả về Sales">
                                            <i class="fas fa-times-circle text-lg"></i>
                                        </button>
                                    @endif

                                    @if($request->status === \App\Models\SaleOrderRequest::STATUS_SUBMITTED)
                                        <form action="{{ route('purchase-requests.verify', $request->id) }}" method="POST"
                                            onsubmit="return confirm('Duyệt yêu cầu này?')">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="text-green-600 hover:text-green-800 p-1" title="Duyệt">
                                                <i class="fas fa-check-circle text-lg"></i>
                                            </button>
                                        </form>
                                        <button type="button"
                                            onclick="showRejectModal('{{ $request->id }}', '{{ $request->code }}')"
                                            class="text-red-600 hover:text-red-800 p-1" title="Trả về">
                                            <i class="fas fa-times-circle text-lg"></i>
                                        </button>
                                    @endif

                                    @can('delete', $request)
                                        @if(in_array($request->status, [\App\Models\SaleOrderRequest::STATUS_DRAFT, \App\Models\SaleOrderRequest::STATUS_SUBMITTED, \App\Models\SaleOrderRequest::STATUS_NEED_INFO]))
                                            <form action="{{ route('purchase-requests.destroy', $request->id) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" onclick="confirmDeleteWithReason(this.form, 'yêu cầu đặt hàng')"
                                                    class="text-red-600 hover:text-red-800 p-1" title="Xóa PR">
                                                    <i class="fas fa-trash-alt text-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan

                                    <button type="button" onclick="toggleDetails('{{ $request->id }}')"
                                        class="text-gray-400 hover:text-gray-600 p-1">
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
                                                    <td class="border-r border-gray-200 p-2">
                                                        {{ $item->vendor->name ?? $item->vendor }}</td>
                                                    <td class="border-r border-gray-200 p-2 font-medium text-teal-700">
                                                        {{ $item->part_number }}
                                                        @if($item->is_cancelled)
                                                            <span
                                                                class="ml-1 px-1.5 py-0.5 text-[8px] bg-red-100 text-red-600 rounded font-bold">ĐÃ
                                                                HỦY</span>
                                                        @endif
                                                    </td>
                                                    <td class="border-r border-gray-200 p-2 text-center font-bold">
                                                        {{ $item->quantity + 0 }}</td>
                                                    <td class="border-r border-gray-200 p-2 text-center text-blue-600">
                                                        {{ number_format($item->saleItem->profit_percent ?? 0, 2) }}%
                                                    </td>
                                                    <td class="border-r border-gray-200 p-2 text-gray-500">
                                                        {{ $item->serial_number ?: '-' }}</td>
                                                    <td class="border-r border-gray-200 p-2 text-gray-500">
                                                        {{ $item->exp_date ? $item->exp_date->format('d/m/Y') : '-' }}</td>
                                                    <td class="border-r border-gray-200 p-2 text-gray-700">{{ $item->si_name }}</td>
                                                    <td class="border-r border-gray-200 p-2 text-gray-600">{{ $item->eu_name_mst }}
                                                    </td>
                                                    <td class="p-2 text-gray-500">{{ $item->note ?: '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    @if($request->sale)
                                        @php
                                            $payStatus = $request->sale->getPaymentConditionStatus();
                                        @endphp
                                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50/50 p-3 rounded-lg border border-gray-200/80 text-xs">
                                            <div>
                                                <h5 class="font-bold text-gray-700 mb-2 flex items-center gap-1">
                                                    <i class="fas fa-file-invoice-dollar text-teal-600"></i> Điều khoản & Trạng thái thanh toán (SO: {{ $request->sale->code }})
                                                </h5>
                                                <div class="space-y-1 text-gray-600">
                                                    <div>Tổng tiền đơn hàng: <span class="font-semibold text-gray-800">{{ number_format($request->sale->total, 0) }}đ</span></div>
                                                    <div>Đã thanh toán: <span class="font-semibold text-green-600">{{ number_format($request->sale->paid_amount, 0) }}đ</span></div>
                                                    <div>Hình thức: <span class="font-semibold text-gray-700">
                                                        {{ $request->sale->payment_term_type === 'prepaid_100' ? 'Thanh toán trước 100%' : ($request->sale->payment_term_type === 'postpaid' ? 'Thanh toán sau giao hàng' : ($request->sale->payment_term_type === 'milestones' ? 'Thanh toán từng đợt' : ($request->sale->payment_term_type === 'bod_exception' ? 'Ngoại lệ duyệt BOD' : $request->sale->payment_term_type))) }}
                                                    </span></div>
                                                    <div class="flex items-center gap-1.5 mt-1.5">
                                                        <span>Điều kiện đặt hàng:</span>
                                                        @if($payStatus['eligible_for_order'])
                                                            <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-800 font-bold uppercase text-[9px]"><i class="fas fa-check mr-1"></i>ĐỦ ĐIỀU KIỆN</span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-800 font-bold uppercase text-[9px]"><i class="fas fa-ban mr-1"></i>CHƯA ĐỦ ĐIỀU KIỆN</span>
                                                        @endif
                                                        @if($payStatus['has_exception'])
                                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold uppercase text-[9px]"><i class="fas fa-exclamation-circle mr-1"></i>NGOẠI LỆ BOD</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <h5 class="font-bold text-gray-700 mb-2">Chi tiết các đợt thanh toán:</h5>
                                                @if(empty($payStatus['milestones']))
                                                    <p class="text-gray-400 italic">Không có đợt thanh toán nào được cấu hình.</p>
                                                @else
                                                    <div class="space-y-2 max-h-36 overflow-y-auto pr-1">
                                                        @foreach($payStatus['milestones'] as $ms)
                                                            <div class="flex items-start justify-between border-b border-gray-100 pb-1.5">
                                                                <div>
                                                                    <div class="font-medium text-gray-800">{{ $ms['milestone_name'] }} ({{ $ms['percentage'] }}% - {{ number_format($ms['amount'], 0) }}đ)</div>
                                                                    <div class="text-[10px] text-gray-500">
                                                                        Chặn: <span class="font-medium text-red-600">
                                                                            {{ $ms['required_before'] === 'before_order' ? 'Trước khi đặt hàng' : ($ms['required_before'] === 'before_export' ? 'Trước khi xuất kho' : ($ms['required_before'] === 'after_delivery' ? 'Sau khi giao hàng' : $ms['required_before'])) }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="text-right">
                                                                    @php
                                                                        $colorMap = [
                                                                            'paid' => 'bg-green-100 text-green-800',
                                                                            'approved_preload' => 'bg-amber-100 text-amber-800',
                                                                            'approved_export_before_payment' => 'bg-purple-100 text-purple-800',
                                                                            'overdue' => 'bg-red-100 text-red-800',
                                                                            'due' => 'bg-orange-100 text-orange-800',
                                                                            'not_yet_due' => 'bg-gray-100 text-gray-800',
                                                                            'unpaid' => 'bg-gray-100 text-gray-600',
                                                                        ];
                                                                        $labelMap = [
                                                                            'paid' => 'Đã thu',
                                                                            'approved_preload' => 'BOD Ngoại lệ',
                                                                            'approved_export_before_payment' => 'BOD Cho xuất',
                                                                            'overdue' => 'Quá hạn',
                                                                            'due' => 'Đến hạn',
                                                                            'not_yet_due' => 'Chưa đến hạn',
                                                                            'unpaid' => 'Chưa thu',
                                                                        ];
                                                                        $badgeClass = $colorMap[$ms['status']] ?? 'bg-gray-100 text-gray-600';
                                                                        $badgeLabel = $labelMap[$ms['status']] ?? $ms['status'];
                                                                    @endphp
                                                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                                                    @if(isset($ms['proof_file_path']) && $ms['proof_file_path'])
                                                                        <div class="mt-1">
                                                                            <a href="{{ asset('storage/' . $ms['proof_file_path']) }}" target="_blank" class="text-[10px] text-blue-600 hover:underline"><i class="fas fa-file-download mr-0.5"></i> UNC</a>
                                                                        </div>
                                                                    @endif
                                                                    @if(isset($ms['bod_approval_file_path']) && $ms['bod_approval_file_path'])
                                                                        <div class="mt-1">
                                                                            <a href="{{ asset('storage/' . $ms['bod_approval_file_path']) }}" target="_blank" class="text-[10px] text-amber-600 hover:underline"><i class="fas fa-file-download mr-0.5"></i> Phê duyệt BOD</a>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lý do (Yêu cầu Sales bổ sung thông tin
                        gì?)</label>
                    <textarea name="rejection_note" required rows="4"
                        class="w-full border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                        placeholder="Ví dụ: Thiếu thông tin SI Name chính xác..."></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Hủy</button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold">Xác nhận trả
                        về</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Note/PIC Modal -->
    <div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Cập nhật Ghi chú / P.I.C <span id="notePrCode" class="text-teal-600"></span>
                </h3>
                <button type="button" onclick="closeNoteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="noteForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Thông tin Ghi chú / P.I.C</label>
                    <textarea name="note" id="noteTextarea" rows="4"
                        class="w-full border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500"
                        placeholder="Nhập thông tin người phụ trách hoặc ghi chú khác..."></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeNoteModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Hủy</button>
                    <button type="submit"
                        class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-bold shadow-md">Lưu
                        thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Reject Modal -->
    <div id="adminRejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold mb-4">Trả yêu cầu <span id="adminRejectPrCode"></span> về cho Sales</h3>
            <form id="adminRejectForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lý do trả về (Yêu cầu Sales bổ sung gì?)</label>
                    <textarea name="rejection_note" required rows="4"
                        class="w-full border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"
                        placeholder="Ví dụ: Điều khoản thanh toán chưa hợp lý, cần bổ sung UNC..."></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeAdminRejectModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Hủy</button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold">Xác nhận trả về</button>
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

            function showAdminRejectModal(id, code, saleId) {
                document.getElementById('adminRejectPrCode').innerText = '#' + code;
                document.getElementById('adminRejectForm').action = `/sales/${saleId}/order-request/${id}/admin-reject`;
                document.getElementById('adminRejectModal').classList.remove('hidden');
            }

            function closeAdminRejectModal() {
                document.getElementById('adminRejectModal').classList.add('hidden');
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