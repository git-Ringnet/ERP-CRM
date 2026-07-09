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
            @if($invoiceRequest->status === 'pending' && auth()->user()->hasAnyRole(['super_admin', 'sales_manager']))
                <button onclick="openActionModal('draft')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-bold text-sm shadow-sm flex items-center gap-2">
                    <i class="fas fa-check"></i> DUYỆT NHÁP
                </button>
                <button onclick="openActionModal('reject')" class="px-4 py-2 bg-red-50 text-red-600 border border-red-200 rounded-lg hover:bg-red-100 transition-all font-bold text-sm flex items-center gap-2">
                    <i class="fas fa-times"></i> TỪ CHỐI
                </button>
            @endif

            @if($invoiceRequest->status === 'draft_issued' && auth()->user()->hasAnyRole(['super_admin', 'accountant']))
                <button onclick="openActionModal('official')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all font-bold text-sm shadow-sm flex items-center gap-2">
                    <i class="fas fa-file-invoice"></i> XÁC NHẬN CHÍNH THỨC
                </button>
            @endif
        </div>
    </div>

    <!-- Main Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Cột Trái & Giữa: Thông tin yêu cầu -->
        <div class="lg:col-span-2 space-y-6">
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
                                        <div class="text-xs font-normal text-gray-500 mt-0.5">{{ $item->product->name ?? $saleItem->product_name ?? $item->product_name }}</div>
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
            <h3 class="text-lg font-bold text-blue-900">Duyệt & Tải lên hóa đơn nháp</h3>
            <button onclick="closeActionModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <!-- Official Header -->
        <div id="modalHeaderOfficial" class="hidden p-6 border-b border-gray-100 flex justify-between items-center bg-green-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-green-900">Xác nhận xuất HĐ chính thức</h3>
            <button onclick="closeActionModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <!-- Reject Header -->
        <div id="modalHeaderReject" class="hidden p-6 border-b border-gray-100 flex justify-between items-center bg-red-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-red-900">Từ chối yêu cầu xuất hóa đơn</h3>
            <button onclick="closeActionModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>

        <form id="actionForm" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            
            <!-- Draft Form Content -->
            <div id="formContentDraft" class="hidden space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-2">Chọn file hóa đơn nháp (PDF, PNG, JPG)</label>
                    <input type="file" name="draft_file" accept=".pdf,image/*,.doc,.docx"
                        class="w-full border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center cursor-pointer hover:bg-gray-50 transition-all">
                </div>
                <p class="text-[10px] text-gray-500 mt-2 italic">* Nếu không đính kèm file, hệ thống sẽ sử dụng bản in mặc định của SO làm bản nháp.</p>
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
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Lý do từ chối xuất HĐ <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 outline-none" placeholder="VD: Thiếu UNC hoặc sai thông tin thuế..."></textarea>
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
        submitBtn.innerText = "XÁC NHẬN NHÁP";
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
        submitBtn.innerText = "XUẤT HÓA ĐƠN";
    } else if (action === 'reject') {
        document.getElementById('modalHeaderReject').classList.remove('hidden');
        document.getElementById('formContentReject').classList.remove('hidden');
        form.action = "{{ route('invoice-requests.reject', $invoiceRequest->id) }}";
        submitBtn.className = "flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg text-sm shadow";
        submitBtn.innerText = "TỪ CHỐI";
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

// Close on escape
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeActionModal();
    }
});
</script>
@endsection
