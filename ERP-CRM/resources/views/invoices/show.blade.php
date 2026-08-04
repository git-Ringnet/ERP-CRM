@extends('layouts.app')

@section('content')
<div class="px-4 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-gray-500 mb-6 uppercase tracking-wider">
        <a href="{{ route('sales.show', $sale->id) }}" class="hover:text-indigo-600 transition-colors">Đơn hàng {{ $sale->code }}</a>
        <i class="fas fa-chevron-right text-[10px]"></i>
        <span class="font-bold text-gray-800">Chi tiết yêu cầu xuất hóa đơn</span>
    </div>

    <!-- Main Header -->
    <div class="bg-white rounded-xl border border-gray-150 p-6 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-extrabold text-gray-900">Yêu cầu xuất hóa đơn #{{ $invoiceRequest->id }}</h1>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase {{ $invoiceRequest->status_color }}">
                    {{ $invoiceRequest->status_label }}
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Yêu cầu bởi: <span class="font-bold text-gray-700">{{ $invoiceRequest->requester->name }}</span> | 
                Ngày gửi: <span class="font-bold text-gray-700">{{ $invoiceRequest->created_at->format('d/m/Y H:i') }}</span>
            </p>
        </div>
        
        <div class="flex items-center gap-2">
            {{-- Accountant: Import file hóa đơn / Import file mới --}}
            @if(auth()->user()->hasAnyRole(['super_admin', 'sales_manager', 'accountant']))
                @if($invoiceRequest->status === 'rejected')
                    <button onclick="openActionModal('draft')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-bold text-sm shadow-sm flex items-center gap-2">
                        <i class="fas fa-file-import"></i> IMPORT LẠI FILE HÓA ĐƠN MỚI
                    </button>
                @elseif($invoiceRequest->status === 'pending')
                    <button onclick="openActionModal('draft')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-bold text-sm shadow-sm flex items-center gap-2">
                        <i class="fas fa-file-import"></i> IMPORT FILE HÓA ĐƠN
                    </button>
                @endif
            @endif

            {{-- Sales: Confirm invoice OR Mark incorrect --}}
            @if($invoiceRequest->status === 'draft_issued' && (auth()->id() === (int)$invoiceRequest->requester_id || auth()->user()->hasAnyRole(['super_admin', 'sales_manager'])))
                <form action="{{ route('invoice-requests.confirm', $invoiceRequest->id) }}" method="POST" onsubmit="return confirm('Bạn đã kiểm tra và xác nhận file hóa đơn hoàn toàn chính xác?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all font-bold text-sm shadow-sm flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> XÁC NHẬN HÓA ĐƠN
                    </button>
                </form>

                <button onclick="openActionModal('reject')" class="px-4 py-2 bg-red-50 text-red-600 border border-red-200 rounded-lg hover:bg-red-100 transition-all font-bold text-sm flex items-center gap-2" title="Phản hồi file hóa đơn chưa chính xác">
                    <i class="fas fa-times-circle"></i> CHƯA CHÍNH XÁC
                </button>
            @endif

            @if(auth()->id() === (int)$invoiceRequest->requester_id || auth()->user()->hasAnyRole(['super_admin', 'sales_manager', 'accountant']))
                <button onclick="openEditContentModal()" class="px-3.5 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-all font-bold text-sm flex items-center gap-1.5" title="Sửa nội dung xuất hóa đơn chung & từng part">
                    <i class="fas fa-pen-to-square"></i> SỬA NỘI DUNG HÓA ĐƠN
                </button>
            @endif

            {{-- Status Official Completed Badge --}}
            @if($invoiceRequest->status === 'official_issued')
                <span class="px-3 py-1.5 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold uppercase flex items-center gap-1.5">
                    <i class="fas fa-check-double"></i> ĐÃ XÁC NHẬN HOÀN TẤT
                </span>
            @endif
        </div>
    </div>

    @if($invoiceRequest->status === 'rejected')
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start">
                <div class="flex-shrink-0 text-red-500">
                    <i class="fas fa-exclamation-triangle text-lg"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800">Sales phản hồi Hóa đơn chưa chính xác / Cần chỉnh sửa</h3>
                    <div class="mt-1 text-xs text-red-700">
                        <strong>Lý do phản hồi:</strong> {{ $invoiceRequest->rejection_reason }}
                    </div>
                    <div class="mt-2 text-[11px] text-red-600">
                        * Kế toán kiểm tra lý do trên, chuẩn bị file hóa đơn mới và bấm nút <strong>"IMPORT LẠI FILE HÓA ĐƠN MỚI"</strong> để cập nhật phiên bản mới.
                    </div>
                </div>
            </div>
            @if(auth()->user()->hasAnyRole(['super_admin', 'sales_manager', 'accountant']))
                <button onclick="openActionModal('draft')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow transition-all flex items-center gap-2 flex-shrink-0">
                    <i class="fas fa-file-import"></i> IMPORT LẠI FILE HÓA ĐƠN MỚI
                </button>
            @endif
        </div>
    @endif

    <!-- Main Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Cột Trái & Giữa: Thông tin yêu cầu -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Timeline & Lịch sử các lần thay đổi file hóa đơn nháp -->
            <div class="bg-white rounded-xl border border-gray-150 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-indigo-900 border-b border-gray-100 pb-3 uppercase tracking-wider flex items-center justify-between mb-4">
                    <span><i class="fas fa-history text-indigo-500 mr-2"></i> Lịch sử các lần thay đổi file & Phản hồi</span>
                    <span class="text-xs text-gray-500 font-normal">Tổng số phiên bản: {{ $invoiceRequest->revisions ? $invoiceRequest->revisions->count() : 0 }}</span>
                </h3>

                @if($invoiceRequest->revisions && $invoiceRequest->revisions->count() > 0)
                    <div class="space-y-3">
                        @foreach($invoiceRequest->revisions as $rev)
                            <div class="p-3.5 rounded-lg border {{ $rev->action === 'draft_rejected' ? 'bg-red-50/60 border-red-200' : ($rev->action === 'reimported' ? 'bg-blue-50/60 border-blue-200' : 'bg-gray-50 border-gray-200') }}">
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $rev->action === 'draft_rejected' ? 'bg-red-100 text-red-800' : ($rev->action === 'reimported' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-800') }}">
                                            v{{ $rev->version }}
                                        </span>
                                        <span class="text-xs font-bold text-gray-800">{{ $rev->formatted_action }}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500">
                                        <i class="far fa-clock mr-1"></i> {{ $rev->created_at->format('d/m/Y H:i') }}
                                        @if($rev->user)
                                            | <span class="font-semibold text-gray-700">{{ $rev->user->name }}</span>
                                        @endif
                                    </div>
                                </div>

                                @if($rev->note)
                                    <div class="text-xs text-gray-700 mt-1 p-2 bg-white/80 rounded border border-gray-150">
                                        <strong class="text-gray-800">Ghi chú / Lý do:</strong> {{ $rev->note }}
                                    </div>
                                @endif

                                @if($rev->draft_path || $rev->official_path || $rev->delivery_note_path)
                                    <div class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-gray-200/60">
                                        @if($rev->draft_path)
                                            <a href="{{ asset('storage/' . $rev->draft_path) }}" target="_blank" class="inline-flex items-center text-[11px] font-bold text-blue-600 hover:text-blue-800 bg-white px-2 py-0.5 rounded border border-blue-200 shadow-sm">
                                                <i class="fas fa-file-pdf mr-1 text-blue-500"></i> Hóa đơn nháp (File v{{ $rev->version }})
                                            </a>
                                        @endif
                                        @if($rev->official_path)
                                            <a href="{{ asset('storage/' . $rev->official_path) }}" target="_blank" class="inline-flex items-center text-[11px] font-bold text-green-600 hover:text-green-800 bg-white px-2 py-0.5 rounded border border-green-200 shadow-sm">
                                                <i class="fas fa-file-invoice mr-1 text-green-500"></i> Hóa đơn chính thức
                                            </a>
                                        @endif
                                        @if($rev->delivery_note_path)
                                            <a href="{{ asset('storage/' . $rev->delivery_note_path) }}" target="_blank" class="inline-flex items-center text-[11px] font-bold text-purple-600 hover:text-purple-800 bg-white px-2 py-0.5 rounded border border-purple-200 shadow-sm">
                                                <i class="fas fa-clipboard-check mr-1 text-purple-500"></i> BB Bàn giao
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-xs text-gray-500 italic p-3 text-center bg-gray-50 rounded">Chưa có lịch sử phiên bản nào.</div>
                @endif
            </div>
            <!-- Thông tin các bên -->
            <div class="bg-white rounded-xl border border-gray-150 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-indigo-900 border-b border-gray-100 pb-3 uppercase tracking-wider flex items-center gap-2 mb-4">
                    <i class="fas fa-info-circle text-indigo-500"></i> Thông tin mua bán & Giao nhận
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Bên bán -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">BÊN BÁN HÀNG</h4>
                        <div>
                            <div class="text-xs text-gray-500">Sales P.I.C</div>
                            <div class="text-sm font-bold text-gray-800">{{ $invoiceRequest->seller_name ?: $invoiceRequest->requester->name }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Đơn vị bán hàng</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $invoiceRequest->seller_company ?: 'TECH HORIZON CORP' }}</div>
                        </div>
                    </div>
                    
                    <!-- Bên mua -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">BÊN MUA HÀNG (THUẾ)</h4>
                        <div>
                            <div class="text-xs text-gray-500">Tên đơn vị xuất hóa đơn</div>
                            <div class="text-sm font-bold text-gray-800">{{ $invoiceRequest->tax_name }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs text-gray-500">Mã số thuế</div>
                                <div class="text-sm font-semibold text-gray-800">{{ $invoiceRequest->tax_code }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Email nhận hóa đơn</div>
                                <div class="text-sm font-semibold text-gray-800">{{ $invoiceRequest->billing_email ?: '-' }}</div>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Địa chỉ xuất hóa đơn</div>
                            <div class="text-sm text-gray-700 font-medium">{{ $invoiceRequest->tax_address }}</div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 mt-6 pt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Địa chỉ giao hàng -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">THÔNG TIN GIAO HÀNG THỰC TẾ</h4>
                        <div>
                            <div class="text-xs text-gray-500">Địa chỉ nhận hàng</div>
                            <div class="text-sm font-semibold text-gray-800">{{ $invoiceRequest->delivery_address ?: '-' }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs text-gray-500">Người nhận hàng</div>
                                <div class="text-sm font-semibold text-gray-800">{{ $invoiceRequest->delivery_contact ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">SĐT nhận hàng</div>
                                <div class="text-sm font-semibold text-gray-800">{{ $invoiceRequest->delivery_phone ?: '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Ghi chú thanh toán & nội dung xuất -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">ĐIỀU KHOẢN THANH TOÁN & GHI CHÚ</h4>
                        <div>
                            <div class="text-xs text-gray-500">Điều khoản thanh toán</div>
                            <div class="text-sm font-medium text-gray-800 italic">"{{ $invoiceRequest->payment_terms_note ?: 'Theo hợp đồng' }}"</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Nội dung xuất hóa đơn</div>
                            <div class="text-sm font-medium text-gray-800">{{ $invoiceRequest->invoice_content_note ?: 'Danh sách thiết bị' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bảng chi tiết sản phẩm hóa đơn -->
            <div class="bg-white rounded-xl border border-gray-150 p-6 shadow-sm overflow-hidden">
                <h3 class="text-sm font-bold text-indigo-900 border-b border-gray-100 pb-3 uppercase tracking-wider flex items-center gap-2 mb-4">
                    <i class="fas fa-boxes text-indigo-500"></i> Nội dung sản phẩm xuất hóa đơn
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <th class="px-4 py-3">STT</th>
                                <th class="px-4 py-3">Sản phẩm (Part Number)</th>
                                @if($sale->items->first() && array_key_exists('custom_fields', $sale->items->first()->toArray()))
                                    <th class="px-4 py-3">Nội dung xuất HĐ (Báo giá)</th>
                                @endif
                                <th class="px-4 py-3 text-right">Số lượng</th>
                                <th class="px-4 py-3 text-right">Giá bán</th>
                                <th class="px-4 py-3 text-center">VAT</th>
                                <th class="px-4 py-3 text-right">Thành tiền (gồm VAT)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php 
                                $totalInvoiceAmount = 0; 
                                $itemsToRender = $invoiceRequest->export ? $invoiceRequest->export->items : $sale->items;
                            @endphp
                            @foreach($itemsToRender as $index => $item)
                                @php
                                    $productId = $item->product_id;
                                    $productCode = $item->product->code ?? $item->product_name;
                                    
                                    // Tìm sale item tương ứng để lấy VAT và giá bán chính xác
                                    $saleItem = $sale->items->where('product_id', $productId)->first();
                                    $qty = $item->quantity;
                                    $price = $saleItem ? $saleItem->price : ($item->unit_price ?? 0);
                                    $vat = $saleItem ? $saleItem->vat : 8.0;
                                    $effectiveVat = $vat < 0 ? 0 : (float)$vat;
                                    $subtotal = $qty * $price;
                                    $itemTotal = $subtotal * (1 + $effectiveVat / 100);
                                    $totalInvoiceAmount += $itemTotal;
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-800">
                                        {{ $productCode }}
                                        @php
                                            $customPartDesc = null;
                                            if (!empty($invoiceRequest->item_descriptions)) {
                                                $sId = $saleItem ? $saleItem->id : $item->id;
                                                $customPartDesc = $invoiceRequest->item_descriptions[$sId] ?? $invoiceRequest->item_descriptions[$productId] ?? null;
                                            }
                                        @endphp
                                        @if($customPartDesc && $customPartDesc !== ($item->product->name ?? $saleItem->product_name ?? $item->product_name))
                                            <div class="text-xs font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 mt-1 inline-block">
                                                <i class="fas fa-file-signature text-[10px] mr-1"></i>Nội dung xuất HĐ: {{ $customPartDesc }}
                                            </div>
                                        @else
                                            <div class="text-xs font-normal text-gray-500 mt-0.5">{{ $item->product->name ?? $saleItem->product_name ?? $item->product_name }}</div>
                                        @endif
                                    </td>
                                    @if($sale->items->first() && array_key_exists('custom_fields', $sale->items->first()->toArray()))
                                        <td class="px-4 py-3 text-xs text-gray-600 italic">
                                            {{ $saleItem && $saleItem->custom_fields && isset($saleItem->custom_fields['invoice_description']) ? $saleItem->custom_fields['invoice_description'] : '-' }}
                                        </td>
                                    @endif
                                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($qty) }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-700">{{ number_format($price) }} đ</td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ $vat == -1 ? 'KCT' : (float)$vat . '%' }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-900">{{ number_format($itemTotal) }} đ</td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-50 font-bold text-gray-900 border-t-2 border-gray-150">
                                <td colspan="3" class="px-4 py-3 text-right">Tổng tiền đề nghị xuất hóa đơn:</td>
                                <td colspan="1" class="px-4 py-3 text-right"></td>
                                <td colspan="2" class="px-4 py-3 text-right"></td>
                                <td class="px-4 py-3 text-right text-indigo-700 text-base">{{ number_format($totalInvoiceAmount) }} đ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột Phải: Kiểm tra chứng từ đính kèm (Admin/Kế toán duyệt) -->
        <div class="space-y-6">
            <!-- Hồ sơ kiểm tra -->
            <div class="bg-white rounded-xl border border-gray-150 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-indigo-900 border-b border-gray-100 pb-3 uppercase tracking-wider flex items-center gap-2 mb-4">
                    <i class="fas fa-file-check text-indigo-500"></i> Hồ sơ / Chứng từ kiểm tra
                </h3>
                
                <div class="space-y-4">
                    <!-- Hợp đồng HĐMB -->
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="text-xs font-bold text-gray-500 mb-2 uppercase">1. Hợp đồng mua bán (HĐMB)</div>
                        @forelse($hdmbFiles as $file)
                            <a href="javascript:void(0)" onclick="openFilePreviewModal('{{ route('sales.attachments.download', ['sale' => $sale->id, 'attachment' => $file->id]) }}', '{{ $file->file_name }}')" 
                                class="flex items-center text-xs font-semibold text-blue-600 hover:underline mb-1">
                                <i class="fas fa-paperclip mr-2 text-gray-400"></i>{{ \Illuminate\Support\Str::limit($file->file_name, 25) }}
                            </a>
                        @empty
                            <span class="text-xs text-red-500 italic"><i class="fas fa-times mr-1"></i>Sales chưa đính kèm HĐMB</span>
                        @endforelse
                    </div>

                    <!-- Bản duyệt P&L -->
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="text-xs font-bold text-gray-500 mb-2 uppercase">2. Phân tích P&L & UNC bổ sung</div>
                        @forelse($pnlFiles as $file)
                            <a href="javascript:void(0)" onclick="openFilePreviewModal('{{ route('sales.pnl-attachments.download', ['sale' => $sale->id, 'attachment' => $file->id]) }}', '{{ $file->file_name }}')" 
                                class="flex items-center text-xs font-semibold text-blue-600 hover:underline mb-1">
                                <i class="fas fa-paperclip mr-2 text-gray-400"></i>{{ \Illuminate\Support\Str::limit($file->file_name, 25) }}
                            </a>
                        @empty
                            <span class="text-xs text-gray-400 italic">Không có tài liệu P&L bổ sung</span>
                        @endforelse
                    </div>

                    <!-- UNC thanh toán từ khách -->
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="text-xs font-bold text-gray-500 mb-2 uppercase">3. Ủy nhiệm chi (UNC) thanh toán</div>
                        @forelse($uncFiles as $file)
                            <a href="{{ asset('storage/' . $file->attachment_path) }}" target="_blank"
                               class="flex items-center text-xs font-semibold text-emerald-600 hover:underline mb-1">
                                <i class="fas fa-file-invoice-dollar mr-2 text-gray-400"></i>Đợt {{ $file->schedule->milestone_name ?? 'N/A' }}
                            </a>
                        @empty
                            <span class="text-xs text-amber-600 italic"><i class="fas fa-info-circle mr-1"></i>Chưa có UNC ghi nhận</span>
                        @endforelse
                    </div>

                    <!-- E-licenses từ nhà cung cấp -->
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="text-xs font-bold text-gray-500 mb-2 uppercase">4. E-License từ PO (Cho hàng license)</div>
                        @forelse($licenseFiles as $lic)
                            <a href="javascript:void(0)" onclick="openFilePreviewModal('{{ $lic['preview_url'] }}', '{{ $lic['file_name'] }}')" 
                               class="flex items-center text-xs font-semibold text-purple-600 hover:underline mb-1.5"
                               title="{{ $lic['product_name'] }}">
                                <i class="fas fa-key mr-2 text-gray-400"></i>{{ \Illuminate\Support\Str::limit($lic['file_name'], 25) }} 
                                <span class="text-[9px] text-gray-400 ml-1">({{ $lic['po_code'] }})</span>
                            </a>
                        @empty
                            <span class="text-xs text-gray-400 italic">Đơn hàng không có E-license</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Email gửi khách nhận hóa đơn -->
            @if($invoiceRequest->status === 'official_issued')
                <div class="bg-white rounded-xl border border-gray-150 p-6 shadow-sm bg-indigo-50/50">
                    <h3 class="text-sm font-bold text-indigo-900 border-b border-indigo-100 pb-3 uppercase tracking-wider flex items-center gap-2 mb-4">
                        <i class="fas fa-envelope text-indigo-500"></i> Gửi hóa đơn qua Email
                    </h3>
                    <p class="text-xs text-gray-600 mb-4">Gửi email thông báo đính kèm hóa đơn chính thức và tài liệu bàn giao đến khách hàng.</p>
                    
                    <form action="{{ route('sales.email', $sale->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Email nhận:</label>
                            <input type="email" name="to_email" value="{{ $invoiceRequest->billing_email ?: $sale->customer->email }}" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Đính kèm hóa đơn chính thức:</label>
                            @if($invoiceRequest->official_path)
                                <div class="text-xs font-semibold text-green-700 flex items-center gap-1.5 bg-green-50 p-1.5 rounded border border-green-200">
                                    <i class="fas fa-file-pdf"></i> {{ basename($invoiceRequest->official_path) }}
                                </div>
                            @else
                                <div class="text-xs font-semibold text-indigo-700 flex items-center gap-1.5 bg-indigo-50 p-1.5 rounded border border-indigo-200">
                                    <i class="fas fa-print"></i> Hóa đơn chính thức (Hệ thống)
                                </div>
                                <span class="text-[10px] text-gray-500 block mt-1 italic">* Khách hàng sẽ nhận email kèm liên kết đến bản in hóa đơn chính thức từ hệ thống.</span>
                            @endif
                        </div>
                        <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-bold shadow transition-all flex items-center justify-center gap-1.5">
                            <i class="fas fa-paper-plane"></i> GỬI MAIL HÓA ĐƠN
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Actions -->
<div id="actionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <!-- Draft Header -->
        <div id="modalHeaderDraft" class="hidden p-6 border-b border-gray-100 flex justify-between items-center bg-blue-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-blue-900">Tải lên / Import lại hóa đơn nháp</h3>
            <button onclick="closeActionModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <!-- Official Header -->
        <div id="modalHeaderOfficial" class="hidden p-6 border-b border-gray-100 flex justify-between items-center bg-green-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-green-900">Xác nhận xuất HĐ chính thức</h3>
            <button onclick="closeActionModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <!-- Reject Header -->
        <div id="modalHeaderReject" class="hidden p-6 border-b border-gray-100 flex justify-between items-center bg-red-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-red-900">Báo Hóa đơn nháp chưa chính xác</h3>
            <button onclick="closeActionModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>

        <form id="actionForm" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            
            <!-- Draft Form Content -->
            <div id="formContentDraft" class="hidden space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Chọn file hóa đơn nháp (PDF, PNG, JPG, DOCX)</label>
                    <input type="file" name="draft_file" accept=".pdf,image/*,.doc,.docx"
                        class="w-full border border-dashed border-gray-300 rounded-lg px-4 py-6 text-center cursor-pointer hover:bg-gray-50 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ghi chú cho phiên bản này (Không bắt buộc)</label>
                    <input type="text" name="note" placeholder="VD: Đã điều chỉnh địa chỉ thuế theo yêu cầu của Sales..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <!-- Official Form Content -->
            <div id="formContentOfficial" class="hidden space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ngày xuất hóa đơn <span class="text-red-500">*</span></label>
                        <input type="date" name="invoice_date" id="action_invoice_date" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Hạn thanh toán <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_due_date" id="action_payment_due_date" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Tải file Hóa đơn chính thức</label>
                    <input type="file" name="official_file" accept=".pdf,image/*,.doc,.docx" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Biên bản giao hàng / Bàn giao thực tế</label>
                    <input type="file" name="delivery_note_file" accept=".pdf,image/*,.doc,.docx" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <!-- Reject Form Content -->
            <div id="formContentReject" class="hidden space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Lý do phản hồi HĐ chưa chính xác <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none" placeholder="VD: Nhầm tên công ty thuế, sai đơn giá sản phẩm, hoặc thiếu thông tin..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6 border-t border-gray-100 pt-4">
                <button type="button" onclick="closeActionModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 text-sm">HỦY</button>
                <button type="submit" id="submitActionBtn" class="flex-1 px-4 py-2 text-white font-bold rounded-lg text-sm shadow">XÁC NHẬN</button>
            </div>
        </form>
    </div>
</div>

<script>
const debtDays = parseInt("{{ $sale->customer->debt_days ?? 30 }}") || 30;

function openActionModal(action) {
    const modal = document.getElementById('actionModal');
    const form = document.getElementById('actionForm');
    const submitBtn = document.getElementById('submitActionBtn');
    
    // Hide all headers & contents
    document.getElementById('modalHeaderDraft').classList.add('hidden');
    document.getElementById('modalHeaderOfficial').classList.add('hidden');
    document.getElementById('modalHeaderReject').classList.add('hidden');
    document.getElementById('formContentDraft').classList.add('hidden');
    document.getElementById('formContentOfficial').classList.add('hidden');
    document.getElementById('formContentReject').classList.add('hidden');
    
    if (action === 'draft') {
        document.getElementById('modalHeaderDraft').classList.remove('hidden');
        document.getElementById('formContentDraft').classList.remove('hidden');
        form.action = "{{ route('invoice-requests.issue-draft', $invoiceRequest->id) }}";
        submitBtn.className = "flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-sm shadow";
        submitBtn.innerText = "XÁC NHẬN IMPORT NHÁP";
    } else if (action === 'official') {
        document.getElementById('modalHeaderOfficial').classList.remove('hidden');
        document.getElementById('formContentOfficial').classList.remove('hidden');
        form.action = "{{ route('invoice-requests.issue-official', $invoiceRequest->id) }}";
        
        // Set dates
        const today = new Date();
        const formattedToday = formatDate(today);
        document.getElementById('action_invoice_date').value = formattedToday;
        updatePaymentDueDate(formattedToday);
        
        submitBtn.className = "flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg text-sm shadow";
        submitBtn.innerText = "XUẤT HÓA ĐƠN CHÍNH THỨC";
    } else if (action === 'reject') {
        document.getElementById('modalHeaderReject').classList.remove('hidden');
        document.getElementById('formContentReject').classList.remove('hidden');
        form.action = "{{ route('invoice-requests.reject', $invoiceRequest->id) }}";
        submitBtn.className = "flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg text-sm shadow";
        submitBtn.innerText = "GỬI BÁO SAI";
    }
    
    modal.classList.remove('hidden');
}

function closeActionModal() {
    document.getElementById('actionModal').classList.add('hidden');
}

function formatDate(date) {
    const d = new Date(date);
    let month = '' + (d.getMonth() + 1);
    let day = '' + d.getDate();
    const year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
}

function updatePaymentDueDate(invoiceDateStr) {
    if (!invoiceDateStr) return;
    const invoiceDate = new Date(invoiceDateStr);
    invoiceDate.setDate(invoiceDate.getDate() + debtDays);
    document.getElementById('action_payment_due_date').value = formatDate(invoiceDate);
}

document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('action_invoice_date');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            updatePaymentDueDate(this.value);
        });
    }
});
</script>

<!-- Modal Chỉnh sửa Nội dung Hóa đơn & từng Part (STT 7) -->
<div id="editContentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full transform transition-all overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-indigo-50">
            <h3 class="text-lg font-bold text-indigo-900"><i class="fas fa-edit mr-2"></i>Chỉnh sửa Yêu cầu xuất hóa đơn</h3>
            <button onclick="closeEditContentModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <form action="{{ route('invoice-requests.update-content', $invoiceRequest->id) }}" method="POST" class="p-6 overflow-y-auto max-h-[85vh] space-y-6">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cột trái: Thông tin hóa đơn & Bên bán -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider border-b border-indigo-50 pb-1.5">
                        <i class="fas fa-file-contract mr-1.5"></i>Thông tin hóa đơn & Bên bán</h4>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tên người bán (Sales P.I.C) <span class="text-red-500">*</span></label>
                        <input type="text" name="seller_name" value="{{ $invoiceRequest->seller_name }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Đơn vị bán hàng <span class="text-red-500">*</span></label>
                        <input type="text" name="seller_company" value="{{ $invoiceRequest->seller_company }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung xuất hóa đơn (Thiết bị/Dịch vụ)</label>
                        <textarea name="invoice_content_note" rows="3"
                            placeholder="Chi tiết sản phẩm, nội dung đặc biệt khi xuất hóa đơn..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">{{ $invoiceRequest->invoice_content_note }}</textarea>
                    </div>
                </div>

                <!-- Cột phải: Thông tin giao hàng & Bên mua -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider border-b border-indigo-50 pb-1.5">
                        <i class="fas fa-shipping-fast mr-1.5"></i>Thông tin giao hàng & Bên mua</h4>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tên Công ty/Cá nhân mua hàng <span class="text-red-500">*</span></label>
                        <input type="text" name="tax_name" value="{{ $invoiceRequest->tax_name }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Mã số thuế <span class="text-red-500">*</span></label>
                            <input type="text" name="tax_code" value="{{ $invoiceRequest->tax_code }}" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email nhận HĐ</label>
                            <input type="email" name="billing_email" value="{{ $invoiceRequest->billing_email }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Địa chỉ xuất hóa đơn <span class="text-red-500">*</span></label>
                        <textarea name="tax_address" rows="2" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">{{ $invoiceRequest->tax_address }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Người nhận hàng</label>
                            <input type="text" name="delivery_contact" value="{{ $invoiceRequest->delivery_contact }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">SĐT nhận hàng</label>
                            <input type="text" name="delivery_phone" value="{{ $invoiceRequest->delivery_phone }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Địa chỉ giao nhận thực tế</label>
                        <textarea name="delivery_address" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">{{ $invoiceRequest->delivery_address }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Nội dung xuất HĐ chi tiết từng sản phẩm / Part (STT 7) -->
            <div class="mt-6 pt-4 border-t border-gray-100">
                <label class="block text-xs font-bold text-indigo-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <i class="fas fa-edit"></i> Nội dung xuất hóa đơn theo từng sản phẩm / Part (Tùy chỉnh nếu cần)
                </label>
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-gray-50 text-gray-600 font-bold uppercase">
                            <tr>
                                <th class="p-2.5 w-12 text-center">STT</th>
                                <th class="p-2.5">Sản phẩm / Part Number</th>
                                <th class="p-2.5 w-24 text-right">Số lượng</th>
                                <th class="p-2.5">Nội dung xuất HĐ tùy chỉnh (cho Part này)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($sale->items as $idx => $sItem)
                                @php
                                    $pCode = $sItem->product->code ?? $sItem->product_name;
                                    $pName = $sItem->product->name ?? $sItem->product_name;
                                    $curCustom = $invoiceRequest->item_descriptions[$sItem->id] ?? $pName;
                                @endphp
                                <tr>
                                    <td class="p-2.5 text-center font-bold text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-2.5">
                                        <div class="font-bold text-gray-800">{{ $pName }}</div>
                                        <div class="text-[11px] text-indigo-600 font-mono">PN: {{ $pCode }}</div>
                                    </td>
                                    <td class="p-2.5 text-right font-bold text-gray-800">{{ $sItem->quantity }}</td>
                                    <td class="p-2.5">
                                        <input type="text" name="item_descriptions[{{ $sItem->id }}]" value="{{ $curCustom }}"
                                            placeholder="Nhập tên/nội dung xuất HĐ thay thế cho part này..."
                                            class="w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dòng dưới cùng: Điều khoản thanh toán & Ghi chú thêm -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-4 border-t border-gray-100">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Điều khoản thanh toán của đơn hàng</label>
                    <textarea name="payment_terms_note" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">{{ $invoiceRequest->payment_terms_note }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ghi chú thêm gửi kế toán</label>
                    <textarea name="note" rows="2" placeholder="Ghi chú thêm..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">{{ $invoiceRequest->note }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                <button type="button" onclick="closeEditContentModal()" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs hover:bg-gray-200">HỦY</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-lg text-xs hover:bg-indigo-700 shadow-md"><i class="fas fa-save mr-1"></i>LƯU THAY ĐỔI</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditContentModal() {
    document.getElementById('editContentModal').classList.remove('hidden');
}
function closeEditContentModal() {
    document.getElementById('editContentModal').classList.add('hidden');
}

// Close on escape
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeActionModal();
        closeEditContentModal();
    }
});
</script>
@endsection
