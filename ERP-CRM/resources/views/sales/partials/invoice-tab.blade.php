<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Lịch sử yêu cầu hóa đơn</h3>
            <p class="text-xs text-gray-500">Theo dõi tiến độ xuất hóa đơn nháp và chính thức</p>
        </div>
        
        @if(
            ($sale->type === 'retail' && in_array($sale->status, ['approved', 'shipping', 'completed'])) ||
            ($sale->type !== 'retail' && in_array($sale->dashboard_status, ['received', 'invoiced', 'completed']))
        )
            <button onclick="openInvoiceRequestModal()" class="px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition-all shadow-sm">
                <i class="fas fa-plus-circle mr-2"></i> GỬI YÊU CẦU MỚI
            </button>
        @elseif($sale->type !== 'retail' && in_array($sale->dashboard_status, ['ordered', 'in_transit', 'hold']))
            <div class="text-[11px] bg-amber-50 text-amber-700 border border-amber-100 px-3 py-1.5 rounded-lg flex items-center">
                <i class="fas fa-info-circle mr-2"></i> Vui lòng chờ hàng về đủ để yêu cầu hóa đơn
            </div>
        @endif
    </div>

    <div class="p-0">
        @if($sale->invoiceRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Ngày yêu cầu</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Thông tin thuế</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Trạng thái</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Tài liệu</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($sale->invoiceRequests->sortByDesc('created_at') as $request)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-4">
                                    <div class="text-sm font-bold text-gray-900">{{ $request->created_at->format('d/m/Y') }}</div>
                                    <div class="text-[10px] text-gray-500">{{ $request->created_at->format('H:i') }}</div>
                                    <div class="text-[10px] mt-1 text-indigo-600 italic">Bởi: {{ $request->requester->name }}</div>
                                    <div class="mt-2">
                                        <a href="{{ route('invoice-requests.show', $request->id) }}" class="inline-flex items-center text-[10px] bg-teal-50 text-teal-600 border border-teal-200 px-2 py-0.5 rounded hover:bg-teal-100 transition-all font-bold">
                                            <i class="fas fa-external-link-alt mr-1"></i>CHI TIẾT YÊU CẦU
                                        </a>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm font-bold text-gray-800">{{ $request->tax_name }}</div>
                                    <div class="text-xs text-gray-600 mt-1"><i class="fas fa-id-card mr-1 text-gray-400"></i>MST: {{ $request->tax_code }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs" title="{{ $request->tax_address }}"><i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>{{ $request->tax_address }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase {{ $request->status_color }}">
                                        {{ $request->status_label }}
                                    </span>
                                    @if($request->status === 'rejected')
                                        <div class="mt-1 text-[10px] text-red-600 italic">Lý do: {{ $request->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col gap-1.5">
                                        @if($request->draft_path)
                                            <a href="{{ asset('storage/' . $request->draft_path) }}" target="_blank" class="inline-flex items-center text-[11px] font-bold text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-file-pdf mr-1 text-blue-500"></i> File HĐ đính kèm ({{ $request->revisions->count() > 0 ? 'v' . $request->revisions->max('version') : 'v1' }})
                                            </a>
                                        @else
                                            <span class="text-[10px] text-gray-400 italic">Chưa đính kèm file</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                        @if(auth()->id() === (int)$request->requester_id || auth()->user()->hasAnyRole(['super_admin', 'sales_manager', 'accountant']))
                                            <button type="button" 
                                                data-request="{{ json_encode($request) }}"
                                                onclick="openEditInvoiceContentModalFromButton(this)" 
                                                class="px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold rounded hover:bg-indigo-100 flex items-center gap-1" 
                                                title="Chỉnh sửa nội dung xuất HĐ chung & từng part">
                                                <i class="fas fa-pen-to-square"></i> SỬA NỘI DUNG
                                            </button>
                                        @endif

                                        {{-- 1. Status: pending -> Accountant imports file --}}
                                        @if($request->status === 'pending')
                                            @if(auth()->user()->hasAnyRole(['super_admin', 'sales_manager', 'accountant']))
                                                <button onclick="openDraftModal({{ $request->id }})" class="px-3 py-1 bg-blue-600 text-white text-[10px] font-bold rounded hover:bg-blue-700 shadow-sm flex items-center gap-1" title="Import file hóa đơn">
                                                    <i class="fas fa-file-import"></i> IMPORT FILE HĐ
                                                </button>
                                            @endif
                                            
                                            @if(auth()->id() === (int)$request->requester_id || auth()->user()->hasAnyRole(['super_admin', 'sales_manager']))
                                                <form action="{{ route('invoice-requests.cancel', $request->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu này?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="p-1 bg-gray-50 text-gray-500 rounded hover:bg-gray-100" title="Hủy yêu cầu">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            @endif

                                        {{-- 2. Status: rejected -> ONLY show Import file mới button --}}
                                        @elseif($request->status === 'rejected')
                                            @if(auth()->user()->hasAnyRole(['super_admin', 'sales_manager', 'accountant']))
                                                <button onclick="openDraftModal({{ $request->id }})" class="px-3 py-1 bg-blue-600 text-white text-[10px] font-bold rounded hover:bg-blue-700 shadow-sm flex items-center gap-1" title="Import lại file hóa đơn mới">
                                                    <i class="fas fa-file-import"></i> IMPORT FILE MỚI
                                                </button>
                                            @endif

                                        {{-- 3. Status: draft_issued -> Show ONLY XÁC NHẬN and CHƯA CHÍNH XÁC --}}
                                        @elseif($request->status === 'draft_issued')
                                            @if(auth()->id() === (int)$request->requester_id || auth()->user()->hasAnyRole(['super_admin', 'sales_manager']))
                                                <form action="{{ route('invoice-requests.confirm', $request->id) }}" method="POST" onsubmit="return confirm('Bạn đã kiểm tra file hóa đơn chính xác?')">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 bg-emerald-600 text-white text-[10px] font-bold rounded hover:bg-emerald-700 shadow-sm flex items-center gap-1" title="Xác nhận file hóa đơn chính xác">
                                                        <i class="fas fa-check-circle"></i> XÁC NHẬN
                                                    </button>
                                                </form>
                                                <button onclick="openRejectModal({{ $request->id }})" class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 text-[10px] font-bold rounded hover:bg-red-100 font-semibold" title="Phản hồi chưa chính xác">
                                                    CHƯA CHÍNH XÁC
                                                </button>
                                            @endif

                                        {{-- 4. Status: official_issued -> Completed --}}
                                        @elseif($request->status === 'official_issued')
                                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-1 rounded border border-emerald-200">
                                                <i class="fas fa-check-double mr-1"></i> HOÀN TẤT
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-invoice-dollar text-2xl text-gray-400"></i>
                </div>
                <h4 class="text-gray-900 font-bold">Chưa có yêu cầu hóa đơn</h4>
                <p class="text-sm text-gray-500 mt-1">Khi hàng về đủ, bạn có thể gửi yêu cầu xuất hóa đơn cho bộ phận kế toán.</p>
                @if(
                    ($sale->type === 'retail' && in_array($sale->status, ['approved', 'shipping', 'completed'])) ||
                    ($sale->type !== 'retail' && in_array($sale->dashboard_status, ['received', 'invoiced', 'completed']))
                )
                    <button onclick="openInvoiceRequestModal()" class="mt-4 px-6 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition-all">
                        Gửi yêu cầu ngay
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>

@include('sales.partials.invoice-modal')
