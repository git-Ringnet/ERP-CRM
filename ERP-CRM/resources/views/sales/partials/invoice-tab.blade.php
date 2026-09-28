<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <!-- Top Header & Overall Progress Bars -->
    <div class="p-5 border-b bg-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-indigo-600"></i> Quản lý Hóa đơn & Yêu cầu Xuất hàng
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">Theo dõi tiến độ xuất hóa đơn từng phần (Partial Invoicing), thông báo Kế toán MISA & tạo phiếu xuất kho</p>
            
            <!-- Tiến độ xuất HĐ & Xuất kho -->
            <div class="flex flex-wrap items-center gap-6 mt-3">
                <!-- Tiến độ HĐ -->
                <div class="flex items-center gap-2 min-w-[200px]">
                    <div class="text-[11px] font-bold text-indigo-900 w-24">Tiến độ Xuất HĐ:</div>
                    <div class="flex-1 bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $sale->total_invoiced_percent }}%"></div>
                    </div>
                    <span class="text-[11px] font-bold {{ $sale->total_invoiced_percent >= 100 ? 'text-emerald-600' : 'text-indigo-600' }}">
                        {{ $sale->total_invoiced_percent }}%
                    </span>
                </div>

                <!-- Tiến độ Xuất kho -->
                <div class="flex items-center gap-2 min-w-[200px]">
                    <div class="text-[11px] font-bold text-teal-900 w-24">Tiến độ Xuất kho:</div>
                    <div class="flex-1 bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-teal-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $sale->total_exported_percent }}%"></div>
                    </div>
                    <span class="text-[11px] font-bold {{ $sale->total_exported_percent >= 100 ? 'text-emerald-600' : 'text-teal-600' }}">
                        {{ $sale->total_exported_percent }}%
                    </span>
                    <span class="text-[10px] text-gray-400 italic">(Vật lý)</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            @if($sale->can_create_invoice_request)
                <button onclick="openInvoiceRequestModal()" class="px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700 transition-all shadow-sm flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> GỬI YÊU CẦU XUẤT HĐ MỚI
                </button>
            @else
                <div class="text-[11px] bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1.5 rounded-lg flex items-center font-bold">
                    <i class="fas fa-check-double mr-1.5 text-emerald-600"></i> Đã gửi yêu cầu xuất HĐ đủ 100% sản phẩm
                </div>
            @endif
        </div>
    </div>

    <div class="p-0">
        @if($sale->invoiceRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Ngày yêu cầu & Đợt</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Mặt hàng & SL xuất đợt này</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Thông tin thuế</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Quy trình HĐ & Trạng thái</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Tiến độ Kế toán</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase">Phiếu kho & Tài liệu</th>
                            <th class="px-4 py-3 text-xs font-bold text-gray-600 uppercase text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($sale->invoiceRequests->sortByDesc('created_at') as $request)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <!-- Cột 1: Ngày yêu cầu -->
                                <td class="px-4 py-4 align-top">
                                    <div class="text-sm font-bold text-gray-900">#{{ $request->id }} - {{ $request->created_at->format('d/m/Y') }}</div>
                                    <div class="text-[10px] text-gray-500">{{ $request->created_at->format('H:i') }}</div>
                                    <div class="text-[10px] mt-1 text-indigo-600 italic">Bởi: {{ $request->requester->name }}</div>
                                    <div class="mt-2">
                                        <a href="{{ route('invoice-requests.show', $request->id) }}" class="inline-flex items-center text-[10px] bg-teal-50 text-teal-600 border border-teal-200 px-2 py-0.5 rounded hover:bg-teal-100 transition-all font-bold">
                                            <i class="fas fa-external-link-alt mr-1"></i>CHI TIẾT ĐỢT
                                        </a>
                                    </div>
                                </td>

                                <!-- Cột 2: Mặt hàng & Số lượng xuất trong đợt này -->
                                <td class="px-4 py-4 align-top max-w-xs">
                                    @if(!empty($request->requested_items) && is_array($request->requested_items))
                                        <div class="space-y-1">
                                            @foreach($request->requested_items as $rItem)
                                                <div class="flex items-center justify-between text-xs bg-gray-50 px-2 py-1 rounded border border-gray-150">
                                                    <span class="font-medium text-gray-800 truncate mr-2" title="{{ $rItem['product_name'] ?? '' }}">
                                                        {{ $rItem['product_name'] ?? 'Sản phẩm' }}
                                                    </span>
                                                    <span class="font-bold text-indigo-700 flex-shrink-0">
                                                        SL: {{ $rItem['quantity'] ?? 0 }}
                                                        @if(!empty($rItem['is_service']))
                                                            <span class="text-[9px] text-purple-600 font-normal">(DV)</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-600 italic">Toàn bộ mặt hàng theo đơn</div>
                                    @endif
                                </td>

                                <!-- Cột 3: Thông tin thuế -->
                                <td class="px-4 py-4 align-top">
                                    <div class="text-sm font-bold text-gray-800">{{ $request->tax_name }}</div>
                                    <div class="text-xs text-gray-600 mt-1"><i class="fas fa-id-card mr-1 text-gray-400"></i>MST: {{ $request->tax_code }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs" title="{{ $request->tax_address }}"><i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>{{ $request->tax_address }}</div>
                                </td>

                                <!-- Cột 4: Quy trình HĐ & Trạng thái -->
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-1.5">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase {{ $request->status_color }} block w-max">
                                            {{ $request->status_label }}
                                        </span>
                                        @if($request->needs_draft)
                                            <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                                <i class="fas fa-file-alt mr-1"></i> Cần HĐ nháp
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                                <i class="fas fa-bolt mr-1"></i> Xuất trực tiếp
                                            </span>
                                        @endif
                                        @if($request->status === 'rejected')
                                            <div class="mt-1 text-[10px] text-red-600 italic">Lý do: {{ $request->rejection_reason }}</div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Cột 5: Tiến độ Kế toán -->
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-1 text-xs">
                                        @if($request->is_invoiced || $request->status === 'official_issued')
                                            <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-[11px] font-bold rounded-md">
                                                <i class="fas fa-check-circle mr-1 text-green-600"></i> Đã xuất HĐ
                                            </span>
                                            @if($request->invoice_number)
                                                <div class="text-[11px] font-mono font-bold text-gray-800">Số: {{ $request->invoice_number }}</div>
                                            @endif
                                            @if($request->invoiced_at)
                                                <div class="text-[10px] text-gray-500">Ngày: {{ \Carbon\Carbon::parse($request->invoiced_at)->format('d/m/Y') }}</div>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-600 text-[11px] font-bold rounded-md">
                                                <i class="fas fa-hourglass-half mr-1 text-gray-400"></i> Chưa xuất HĐ
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Cột 6: Phiếu kho & Tài liệu đính kèm -->
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-col gap-1.5">
                                        {{-- Linked Export Slip --}}
                                        @if($request->export)
                                            <a href="{{ route('exports.show', $request->export->id) }}" class="inline-flex items-center text-[11px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200 hover:bg-teal-100">
                                                <i class="fas fa-boxes-packing mr-1 text-teal-600"></i> Phiếu kho: {{ $request->export->code }}
                                                <span class="ml-1 text-[9px] text-gray-500">({{ $request->export->status_label }})</span>
                                            </a>
                                        @endif

                                        {{-- Official Invoice --}}
                                        @if($request->official_path)
                                            <a href="{{ asset('storage/' . $request->official_path) }}" target="_blank" class="inline-flex items-center text-[11px] font-bold text-emerald-600 hover:text-emerald-800">
                                                <i class="fas fa-file-invoice mr-1 text-emerald-500"></i> HĐ chính thức
                                            </a>
                                        @endif

                                        {{-- Draft Invoice File --}}
                                        @if($request->draft_path)
                                            <a href="{{ asset('storage/' . $request->draft_path) }}" target="_blank" class="inline-flex items-center text-[11px] font-bold text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-file-pdf mr-1 text-blue-500"></i> File HĐ nháp ({{ $request->revisions->count() > 0 ? 'v' . $request->revisions->max('version') : 'v1' }})
                                            </a>
                                        @endif

                                        @if(!$request->draft_path && !$request->official_path && !$request->export)
                                            <span class="text-[10px] text-gray-400 italic">Chưa có tài liệu</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Cột 7: Thao tác -->
                                <td class="px-4 py-4 align-top">
                                    @php
                                        $isSalesOwner = (auth()->id() === (int)$request->requester_id || auth()->id() === (int)($sale->user_id ?? 0));
                                        $isAdminRole = auth()->user()->hasAnyRole(['super_admin', 'admin', 'director', 'sales_manager', 'accountant']);
                                    @endphp

                                    <div class="flex flex-col gap-1.5 min-w-[150px]">
                                        {{-- ================= TRƯỜNG HỢP 1: CÓ HÓA ĐƠN NHÁP (needs_draft = 1) ================= --}}
                                        @if($request->needs_draft)

                                            {{-- Giai đoạn 1.1: Chờ Admin nhận file MISA & upload bản nháp (status: pending) --}}
                                            @if($request->status === 'pending')
                                                @if($isAdminRole)
                                                    <button onclick="openDraftModal({{ $request->id }})" class="w-full px-2.5 py-1.5 bg-blue-600 text-white text-[10px] font-bold rounded-lg hover:bg-blue-700 shadow-sm flex items-center justify-center gap-1.5" title="Upload file hóa đơn nháp từ MISA">
                                                        <i class="fas fa-upload"></i> UPLOAD HĐ NHÁP
                                                    </button>
                                                @else
                                                    <div class="text-[10px] text-amber-700 bg-amber-50 p-1.5 rounded border border-amber-200 text-center font-medium">
                                                        <i class="fas fa-hourglass-half mr-1"></i> Chờ Admin gửi HĐ nháp
                                                    </div>
                                                @endif

                                                @if($isSalesOwner || $isAdminRole)
                                                    <form action="{{ route('invoice-requests.cancel', $request->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu này?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="w-full py-1 text-gray-500 hover:text-red-600 text-[10px] text-center" title="Hủy yêu cầu">
                                                            <i class="fas fa-trash-alt mr-1"></i> Hủy yêu cầu
                                                        </button>
                                                    </form>
                                                @endif

                                            {{-- Giai đoạn 1.2: Admin đã upload file nháp -> Chờ Sales duyệt (status: draft_issued) --}}
                                            @elseif($request->status === 'draft_issued')
                                                @if($isSalesOwner || auth()->user()->hasAnyRole(['super_admin', 'sales_manager']))
                                                    <form action="{{ route('invoice-requests.confirm', $request->id) }}" method="POST" onsubmit="return confirm('Bạn đã kiểm tra và xác nhận file hóa đơn hoàn toàn chính xác?')">
                                                        @csrf
                                                        <button type="submit" class="w-full px-2.5 py-1.5 bg-emerald-600 text-white text-[10px] font-bold rounded-lg hover:bg-emerald-700 shadow-sm flex items-center justify-center gap-1.5" title="Xác nhận HĐ nháp đúng">
                                                            <i class="fas fa-check-circle"></i> XÁC NHẬN HĐ ĐÚNG
                                                        </button>
                                                    </form>
                                                    <button onclick="openRejectModal({{ $request->id }})" class="w-full px-2.5 py-1 bg-red-50 text-red-600 border border-red-200 text-[10px] font-bold rounded-lg hover:bg-red-100 flex items-center justify-center gap-1" title="Báo HĐ nháp chưa chính xác để sửa lại">
                                                        <i class="fas fa-times"></i> BÁO CHƯA ĐÚNG
                                                    </button>
                                                @endif

                                                @if($isAdminRole && !$isSalesOwner)
                                                    <div class="text-[10px] text-blue-700 bg-blue-50 p-1.5 rounded border border-blue-200 text-center font-medium">
                                                        <i class="fas fa-user-clock mr-1"></i> Chờ Sales duyệt HĐ nháp
                                                    </div>
                                                    <button onclick="openDraftModal({{ $request->id }})" class="w-full py-1 text-[10px] text-blue-600 hover:underline text-center">
                                                        <i class="fas fa-sync-alt mr-1"></i> Upload đè bản khác
                                                    </button>
                                                @endif

                                            {{-- Giai đoạn 1.3: Sales báo chưa chính xác (status: rejected) --}}
                                            @elseif($request->status === 'rejected')
                                                @if($isAdminRole)
                                                    <button onclick="openDraftModal({{ $request->id }})" class="w-full px-2.5 py-1.5 bg-blue-600 text-white text-[10px] font-bold rounded-lg hover:bg-blue-700 shadow-sm flex items-center justify-center gap-1.5" title="Upload lại file hóa đơn đã sửa">
                                                        <i class="fas fa-upload"></i> UPLOAD LẠI HĐ MỚI
                                                    </button>
                                                @else
                                                    <div class="text-[10px] text-red-700 bg-red-50 p-1.5 rounded border border-red-200 text-center font-medium">
                                                        <i class="fas fa-exclamation-circle mr-1"></i> Đã báo sai - Chờ sửa
                                                    </div>
                                                @endif

                                            {{-- Giai đoạn 1.4: Sales đã duyệt HĐ nháp -> Admin ghi nhận đã xuất HĐ & Tạo YC xuất hàng --}}
                                            @elseif($request->status === 'sales_confirmed' || $request->status === 'official_issued')
                                                @if($isAdminRole)
                                                    {{-- Nút 1: Ghi nhận đã xuất HĐ (1-click) --}}
                                                    @if(!$request->is_invoiced && $request->status !== 'official_issued')
                                                        <form action="{{ route('invoice-requests.mark-invoiced', $request->id) }}" method="POST" class="w-full">
                                                            @csrf
                                                            <input type="hidden" name="is_invoiced" value="1">
                                                            <button type="submit" class="w-full px-2.5 py-1.5 bg-emerald-600 text-white text-[10px] font-bold rounded-lg hover:bg-emerald-700 shadow-sm flex items-center justify-center gap-1.5" title="Ghi nhận kế toán đã xuất HĐ">
                                                                <i class="fas fa-file-signature"></i> GHI NHẬN ĐÃ XUẤT HĐ
                                                            </button>
                                                        </form>
                                                    @else
                                                        <div class="text-[10px] text-emerald-700 bg-emerald-50 p-1 rounded border border-emerald-200 text-center font-bold flex items-center justify-center gap-1">
                                                            <i class="fas fa-check-circle text-emerald-600"></i> ĐÃ XUẤT HÓA ĐƠN
                                                        </div>
                                                    @endif

                                                    {{-- Nút 2: TẠO YC XUẤT HÀNG - CHỈ HIỆN KHI ĐÃ GHI NHẬN XUẤT HĐ VÀ CHƯA CÓ PHIẾU XUẤT --}}
                                                    @if(($request->is_invoiced || $request->status === 'official_issued') && !$request->export_id)
                                                        <button type="button" onclick="openCreateExportModal({{ $request->id }})" class="w-full px-2.5 py-1.5 bg-teal-600 text-white text-[10px] font-bold rounded-lg hover:bg-teal-700 shadow-sm flex items-center justify-center gap-1.5" title="Tạo phiếu xuất kho và gửi thông báo kho">
                                                            <i class="fas fa-truck-loading"></i> TẠO YC XUẤT HÀNG
                                                        </button>
                                                    @endif
                                                @else
                                                    <div class="text-[10px] text-emerald-700 bg-emerald-50 p-1.5 rounded border border-emerald-200 text-center font-bold">
                                                        <i class="fas fa-check-double mr-1"></i> Đã duyệt HĐ nháp
                                                    </div>
                                                @endif
                                            @endif

                                        {{-- ================= TRƯỜNG HỢP 2: XUẤT TRỰC TIẾP (needs_draft = 0) ================= --}}
                                        @else

                                            @if($isAdminRole)
                                                {{-- Nút 1: Ghi nhận đã xuất HĐ (1-click) --}}
                                                @if(!$request->is_invoiced && $request->status !== 'official_issued')
                                                    <form action="{{ route('invoice-requests.mark-invoiced', $request->id) }}" method="POST" class="w-full">
                                                        @csrf
                                                        <input type="hidden" name="is_invoiced" value="1">
                                                        <button type="submit" class="w-full px-2.5 py-1.5 bg-emerald-600 text-white text-[10px] font-bold rounded-lg hover:bg-emerald-700 shadow-sm flex items-center justify-center gap-1.5" title="Ghi nhận kế toán đã xuất HĐ">
                                                            <i class="fas fa-file-signature"></i> GHI NHẬN ĐÃ XUẤT HĐ
                                                        </button>
                                                    </form>
                                                @else
                                                    <div class="text-[10px] text-emerald-700 bg-emerald-50 p-1 rounded border border-emerald-200 text-center font-bold flex items-center justify-center gap-1">
                                                        <i class="fas fa-check-circle text-emerald-600"></i> ĐÃ XUẤT HÓA ĐƠN
                                                    </div>
                                                @endif

                                                {{-- Nút 2: TẠO YC XUẤT HÀNG - CHỈ HIỆN KHI ĐÃ GHI NHẬN XUẤT HĐ VÀ CHƯA CÓ PHIẾU XUẤT --}}
                                                @if(($request->is_invoiced || $request->status === 'official_issued') && !$request->export_id)
                                                    <button type="button" onclick="openCreateExportModal({{ $request->id }})" class="w-full px-2.5 py-1.5 bg-teal-600 text-white text-[10px] font-bold rounded-lg hover:bg-teal-700 shadow-sm flex items-center justify-center gap-1.5" title="Tạo phiếu xuất kho và gửi thông báo kho">
                                                        <i class="fas fa-truck-loading"></i> TẠO YC XUẤT HÀNG
                                                    </button>
                                                @endif
                                            @else
                                                <div class="text-[10px] text-indigo-700 bg-indigo-50 p-1.5 rounded border border-indigo-200 text-center font-medium">
                                                    <i class="fas fa-bolt mr-1"></i> Xuất trực tiếp (Đang xử lý)
                                                </div>
                                            @endif

                                            @if(($isSalesOwner || $isAdminRole) && $request->status === 'pending' && !$request->export_id && !$request->is_invoiced)
                                                <form action="{{ route('invoice-requests.cancel', $request->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu này?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="w-full py-1 text-gray-500 hover:text-red-600 text-[10px] text-center" title="Hủy yêu cầu">
                                                        <i class="fas fa-trash-alt mr-1"></i> Hủy yêu cầu
                                                    </button>
                                                </form>
                                            @endif

                                        @endif

                                        {{-- Nút sửa nội dung thông tin thuế (Cho cả Sales & Admin khi chưa xuất HĐ) --}}
                                        @if(!$request->is_invoiced && ($isSalesOwner || $isAdminRole))
                                            <button type="button" 
                                                data-request="{{ json_encode($request) }}"
                                                onclick="openEditInvoiceContentModalFromButton(this)" 
                                                class="w-full py-1 text-gray-600 hover:text-indigo-600 text-[10px] text-center flex items-center justify-center gap-1" 
                                                title="Sửa lại thông tin xuất HĐ">
                                                <i class="fas fa-pen text-[9px]"></i> Sửa thông tin HĐ
                                            </button>
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
                <p class="text-sm text-gray-500 mt-1">Khi hàng về hoặc có hàng sẵn kho/dịch vụ, bạn có thể gửi yêu cầu xuất hóa đơn cho bộ phận kế toán.</p>
                @if($sale->can_create_invoice_request)
                    <button onclick="openInvoiceRequestModal()" class="mt-4 px-6 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition-all shadow">
                        <i class="fas fa-plus-circle mr-1.5"></i> Gửi yêu cầu ngay
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>

@php
    $allActiveWarehouses = \App\Models\Warehouse::where('status', 'active')->get();
    if ($allActiveWarehouses->isEmpty()) {
        $allActiveWarehouses = \App\Models\Warehouse::all();
    }
@endphp

@foreach($sale->invoiceRequests as $request)
    @if(($request->is_invoiced || $request->status === 'official_issued') && !$request->export_id)
        <!-- Modal Tạo YC Xuất hàng cho đợt #{{ $request->id }} -->
        <div id="createExportModal_{{ $request->id }}" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full transform transition-all overflow-hidden flex flex-col max-h-[90vh]">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-teal-50">
                    <h3 class="text-base font-bold text-teal-900 flex items-center gap-2">
                        <i class="fas fa-truck-loading text-teal-600"></i> Tạo yêu cầu xuất hàng (Đợt HĐ #{{ $request->id }})
                    </h3>
                    <button onclick="closeCreateExportModal({{ $request->id }})" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="{{ route('invoice-requests.notify-warehouse', $request->id) }}" method="POST" class="p-6 space-y-4 overflow-y-auto flex-1">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Kho xuất hàng <span class="text-red-500">*</span></label>
                        <select name="warehouse_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                            @foreach($allActiveWarehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>Danh sách hàng hóa đồng bộ từ Hóa đơn</span>
                            <span class="text-[11px] font-normal text-teal-700 lowercase">(Bạn có thể điều chỉnh số lượng xuất kho nếu cần)</span>
                        </label>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="bg-gray-50 text-gray-700 font-bold border-b border-gray-200">
                                    <tr>
                                        <th class="p-2.5">Sản phẩm / Part Number</th>
                                        <th class="p-2.5 text-center w-28">SL trên HĐ</th>
                                        <th class="p-2.5 text-center w-36">SL Xuất kho</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-150 bg-white">
                                    @php
                                        $physicalItems = [];
                                        if (!empty($request->requested_items) && is_array($request->requested_items)) {
                                            foreach ($request->requested_items as $rItem) {
                                                if (empty($rItem['is_service'])) {
                                                    $physicalItems[] = $rItem;
                                                }
                                            }
                                        } else {
                                            foreach ($sale->items as $sItem) {
                                                if (!$sItem->is_service) {
                                                    $physicalItems[] = [
                                                        'product_id' => $sItem->product_id,
                                                        'product_name' => $sItem->product_name ?: ($sItem->product->name ?? ''),
                                                        'quantity' => $sItem->quantity,
                                                    ];
                                                }
                                            }
                                        }
                                    @endphp
                                    @forelse($physicalItems as $item)
                                        @php
                                            $pId = $item['product_id'] ?? 0;
                                            $pName = $item['product_name'] ?? 'Sản phẩm';
                                            $pQty = (int)($item['quantity'] ?? 1);
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-2.5">
                                                <div class="font-bold text-gray-900">{{ $pName }}</div>
                                            </td>
                                            <td class="p-2.5 text-center font-bold text-indigo-700">
                                                {{ $pQty }}
                                            </td>
                                            <td class="p-2.5 text-center">
                                                <input type="number" name="items[{{ $pId }}][quantity]" value="{{ $pQty }}" min="0" required
                                                       class="w-24 border border-gray-300 rounded px-2 py-1 text-center text-sm font-bold text-teal-800 focus:ring-2 focus:ring-teal-500 outline-none">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="p-4 text-center text-gray-500 italic">
                                                Đợt này không có sản phẩm vật lý nào cần xuất kho (toàn bộ là hàng dịch vụ).
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ghi chú gửi bộ phận Kho</label>
                        <textarea name="note" rows="2" placeholder="Ghi chú thêm về quy cách đóng gói, địa điểm hoặc lưu ý giao hàng..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">Xuất kho theo yêu cầu hóa đơn #{{ $request->id }} của đơn hàng {{ $sale->code }}</textarea>
                    </div>

                    <div class="flex gap-3 pt-3 border-t border-gray-100">
                        <button type="button" onclick="closeCreateExportModal({{ $request->id }})" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 text-xs">HỦY</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700 text-xs shadow-md">
                            <i class="fas fa-check-circle mr-1"></i> XÁC NHẬN TẠO PHIẾU XUẤT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

<script>
    function openCreateExportModal(id) {
        const modal = document.getElementById('createExportModal_' + id);
        if (modal) modal.classList.remove('hidden');
    }

    function closeCreateExportModal(id) {
        const modal = document.getElementById('createExportModal_' + id);
        if (modal) modal.classList.add('hidden');
    }
</script>

@include('sales.partials.invoice-modal')

