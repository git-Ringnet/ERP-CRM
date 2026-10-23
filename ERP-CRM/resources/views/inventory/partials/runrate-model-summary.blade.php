@if(request()->filled('sales_id') || request()->filled('project_id'))
    <div class="mx-4 mt-3 px-3 py-2 rounded-lg border border-amber-200 bg-amber-50 text-xs text-amber-800">
        <i class="fas fa-info-circle mr-1"></i>
        Bộ lọc Sales / Dự án chỉ áp dụng cho chế độ "Theo PO". Chế độ "Theo model" hiển thị toàn bộ tồn Kho runrate.
    </div>
@endif

<div class="overflow-x-auto mt-3">
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr class="divide-x divide-gray-200">
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase w-12">STT</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Mã SP</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase min-w-[220px]">Tên thiết bị</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Tổng tồn</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Đã giữ</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase whitespace-nowrap" title="Hàng runrate yêu cầu CQ đã đặt riêng cho đơn bán: chỉ xuất cho đúng đơn đó, không giữ được">Khoá CQ</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Khả dụng</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase whitespace-nowrap">Số PO</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase w-48">Thao tác</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($rows as $row)
                <tr class="divide-x divide-gray-100 hover:bg-gray-50/60">
                    <td class="px-3 py-2.5 text-center text-gray-500">{{ ($rows->currentPage() - 1) * $rows->perPage() + $loop->iteration }}</td>
                    <td class="px-3 py-2.5 font-mono text-gray-800 whitespace-nowrap">{{ $row->product_code }}</td>
                    <td class="px-3 py-2.5 text-gray-700">{{ $row->product_name }}</td>
                    <td class="px-3 py-2.5 text-center font-bold text-gray-900">{{ $row->total_qty }}</td>
                    <td class="px-3 py-2.5 text-center font-semibold {{ $row->held_qty > 0 ? 'text-orange-700' : 'text-gray-400' }}">{{ $row->held_qty }}</td>
                    <td class="px-3 py-2.5 text-center font-semibold {{ $row->cq_qty > 0 ? 'text-cyan-700' : 'text-gray-400' }}">{{ $row->cq_qty }}</td>
                    <td class="px-3 py-2.5 text-center font-bold {{ $row->available_qty > 0 ? 'text-teal-700' : 'text-red-600' }}">{{ $row->available_qty }}</td>
                    <td class="px-3 py-2.5 text-center text-gray-600">{{ $row->po_count }}</td>
                    <td class="px-3 py-2.5 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-2 text-xs">
                            <button type="button" onclick="document.getElementById('runrate_model_detail_{{ $row->product_id }}').classList.toggle('hidden')"
                                class="text-primary hover:underline font-medium">
                                <i class="fas fa-list mr-0.5"></i>Chi tiết
                            </button>
                            @if($canManageWarehouse)
                                <a href="{{ route('tickets.hold-logs', ['product_id' => $row->product_id]) }}" class="text-purple-700 hover:underline font-medium">
                                    <i class="fas fa-history mr-0.5"></i>Lịch sử giữ
                                </a>
                            @endif
                            <a href="{{ route('tickets.create', ['product_id' => $row->product_id]) }}" class="text-emerald-700 hover:underline font-medium">
                                <i class="fas fa-hand-holding mr-0.5"></i>Giữ hàng
                            </a>
                        </div>
                    </td>
                </tr>
                <tr id="runrate_model_detail_{{ $row->product_id }}" class="hidden bg-gray-50/70">
                    <td colspan="9" class="px-4 py-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-bold text-gray-600 uppercase mb-1.5">Theo PO nhập</div>
                                <table class="w-full text-xs bg-white border border-gray-200 rounded">
                                    <thead class="bg-gray-100 text-gray-500">
                                        <tr>
                                            <th class="px-2 py-1.5 text-left">Số PO</th>
                                            <th class="px-2 py-1.5 text-center">Tồn</th>
                                            <th class="px-2 py-1.5 text-center">Đã giữ</th>
                                            <th class="px-2 py-1.5 text-center">Khoá CQ</th>
                                            <th class="px-2 py-1.5 text-center">Khả dụng</th>
                                            <th class="px-2 py-1.5 text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($row->po_breakdown as $po)
                                            <tr>
                                                <td class="px-2 py-1.5 font-mono">{{ $po->po_code }}</td>
                                                <td class="px-2 py-1.5 text-center">{{ (int) $po->total_qty }}</td>
                                                <td class="px-2 py-1.5 text-center text-orange-700">{{ (int) $po->held_qty }}</td>
                                                <td class="px-2 py-1.5 text-center text-cyan-700">{{ (int) $po->cq_qty }}</td>
                                                <td class="px-2 py-1.5 text-center text-teal-700">{{ (int) $po->total_qty - (int) $po->held_qty - (int) $po->cq_qty }}</td>
                                                <td class="px-2 py-1.5 text-center">
                                                    @if($po->po_code !== 'CHƯA GẮN PO')
                                                        <a href="{{ route('inventory.index', ['tab' => 'runrate', 'runrate_view' => 'po', 'search' => $row->product_code, 'po_code' => $po->po_code]) }}"
                                                           class="text-primary hover:underline">Xem S/N</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <p class="mt-1 text-[11px] text-gray-400 italic">
                                    "Đã giữ" theo PO chỉ để tham khảo: quyền giữ hàng tính theo model, khi xuất kho có thể lấy thiết bị ở PO bất kỳ.
                                </p>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-600 uppercase mb-1.5">Người đang giữ</div>
                                @if($row->holders->isEmpty())
                                    <div class="text-xs text-gray-400 italic">Chưa có ai giữ</div>
                                @else
                                    <table class="w-full text-xs bg-white border border-gray-200 rounded">
                                        <thead class="bg-gray-100 text-gray-500">
                                            <tr>
                                                <th class="px-2 py-1.5 text-left">Người giữ</th>
                                                <th class="px-2 py-1.5 text-center">Số lượng</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($row->holders as $holder)
                                                <tr>
                                                    <td class="px-2 py-1.5">{{ $holder->borrower }}</td>
                                                    <td class="px-2 py-1.5 text-center font-semibold text-orange-700">{{ (int) $holder->qty }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">Không có hàng runrate phù hợp</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($rows->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $rows->links() }}
    </div>
@endif
