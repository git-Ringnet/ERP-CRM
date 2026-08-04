<!-- Modal Gửi yêu cầu mới -->
<div id="invoiceRequestModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full transform transition-all overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-indigo-50">
            <h3 class="text-lg font-bold text-indigo-900"><i class="fas fa-file-invoice-dollar mr-2"></i>Yêu cầu xuất
                hóa đơn</h3>
            <button onclick="closeInvoiceRequestModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <form action="{{ route('invoice-requests.store', $sale->id) }}" method="POST"
            class="p-6 overflow-y-auto max-h-[85vh]">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cột trái: Thông tin hóa đơn & Bên bán -->
                <div class="space-y-4">
                    <h4
                        class="text-xs font-bold text-indigo-700 uppercase tracking-wider border-b border-indigo-50 pb-1.5">
                        <i class="fas fa-file-contract mr-1.5"></i>Thông tin hóa đơn & Bên bán</h4>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tên người bán
                            (Sales P.I.C) <span class="text-red-500">*</span></label>
                        <input type="text" name="seller_name" value="{{ auth()->user()->name }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Đơn vị bán
                            hàng <span class="text-red-500">*</span></label>
                        <input type="text" name="seller_company"
                            value="{{ \App\Models\PoCompanyConfig::first()->buyer_name ?? 'TECH HORIZON CORP' }}"
                            required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung xuất
                            hóa đơn (Thiết bị/Dịch vụ)</label>
                        <textarea name="invoice_content_note" rows="3"
                            placeholder="Chi tiết sản phẩm, nội dung đặc biệt khi xuất hóa đơn..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">Danh sách thiết bị theo đơn hàng {{ $sale->code }}</textarea>
                    </div>
                </div>

                <!-- Cột phải: Thông tin giao hàng & Bên mua -->
                <div class="space-y-4">
                    <h4
                        class="text-xs font-bold text-indigo-700 uppercase tracking-wider border-b border-indigo-50 pb-1.5">
                        <i class="fas fa-shipping-fast mr-1.5"></i>Thông tin giao hàng & Bên mua</h4>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tên Công
                            ty/Cá nhân mua hàng <span class="text-red-500">*</span></label>
                        <input type="text" name="tax_name" value="{{ $sale->customer_name }}" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Mã số
                                thuế <span class="text-red-500">*</span></label>
                            <input type="text" name="tax_code" value="{{ $sale->customer->tax_code ?? '' }}" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email
                                nhận HĐ</label>
                            <input type="email" name="billing_email" value="{{ $sale->customer->email ?? '' }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Địa chỉ xuất
                            hóa đơn <span class="text-red-500">*</span></label>
                        <textarea name="tax_address" rows="2" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">{{ $sale->customer->address ?? '' }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Người
                                nhận hàng</label>
                            <input type="text" name="delivery_contact"
                                value="{{ $sale->contact_person ?? $sale->customer->contact_name ?? '' }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">SĐT nhận
                                hàng</label>
                            <input type="text" name="delivery_phone"
                                value="{{ $sale->contact_phone ?? $sale->customer->phone ?? '' }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Địa chỉ giao
                            nhận thực tế</label>
                        <textarea name="delivery_address" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">{{ $sale->shipping_address ?? $sale->customer->address ?? '' }}</textarea>
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
                            @foreach($sale->items as $idx => $item)
                                @php
                                    $pCode = $item->product->code ?? $item->product_name;
                                    $pName = $item->product->name ?? $item->product_name;
                                @endphp
                                <tr>
                                    <td class="p-2.5 text-center font-bold text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-2.5">
                                        <div class="font-bold text-gray-800">{{ $pName }}</div>
                                        <div class="text-[11px] text-indigo-600 font-mono">PN: {{ $pCode }}</div>
                                    </td>
                                    <td class="p-2.5 text-right font-bold text-gray-800">{{ $item->quantity }}</td>
                                    <td class="p-2.5">
                                        <input type="text" name="item_descriptions[{{ $item->id }}]" 
                                            value="{{ $item->product_name ?: $pName }}" 
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
            @php
                $formattedPaymentTerms = '';
                if ($sale->payment_terms && is_array($sale->payment_terms)) {
                    $lines = [];
                    foreach ($sale->payment_terms as $term) {
                        $milestoneName = $term['milestone_name'] ?? 'Đợt thanh toán';
                        $percentage = $term['percentage'] ?? 0;
                        $amount = isset($term['amount']) ? number_format($term['amount']) . ' đ' : '';
                        $timing = '';
                        if (($term['timing'] ?? '') === 'after_contract') {
                            $timing = 'Sau khi ký HĐMB';
                        } elseif (($term['timing'] ?? '') === 'after_delivery_notice') {
                            $timing = 'Sau thông báo giao hàng';
                        } elseif (($term['timing'] ?? '') === 'before_export') {
                            $timing = 'Trước khi xuất hàng';
                        } elseif (($term['timing'] ?? '') === 'after_delivery') {
                            $timing = 'Sau khi giao hàng';
                        } elseif (($term['timing'] ?? '') === 'after_invoice') {
                            $timing = 'Sau khi xuất hóa đơn';
                        }
                        $lines[] = "- {$milestoneName}: {$percentage}% ({$amount}) - {$timing}";
                    }
                    $formattedPaymentTerms = implode("\n", $lines);
                } else {
                    $formattedPaymentTerms = is_string($sale->payment_terms) ? $sale->payment_terms : '';
                }
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 pt-4 border-t border-gray-100">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Điều khoản thanh
                        toán của đơn hàng</label>
                    <textarea name="payment_terms_note" rows="2"
                        placeholder="VD: Thanh toán 100% sau khi nhận hóa đơn..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">{{ $formattedPaymentTerms }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ghi chú thêm gửi
                        Kế toán</label>
                    <textarea name="note" rows="2"
                        placeholder="Yêu cầu tách hóa đơn, ngày xuất đặc biệt hoặc các lưu ý khác..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-8 border-t border-gray-150 pt-4">
                <button type="button" onclick="closeInvoiceRequestModal()"
                    class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 transition-all text-sm">HỦY</button>
                <button type="submit"
                    class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition-all shadow-lg text-sm"><i
                        class="fas fa-paper-plane mr-1.5"></i>GỬI YÊU CẦU XUẤT HĐ</button>
            </div>
        </form>
    </div>
</div>

<script>
    function handleExportSelectionChange(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const exportId = selectElement.value;
        const noteTextarea = document.querySelector('textarea[name="invoice_content_note"]');

        if (exportId) {
            const code = selectedOption.text.split(' ')[0];
            noteTextarea.value = "Xuất hóa đơn cho phiếu xuất " + code + " của đơn hàng {{ $sale->code }}";
        } else {
            noteTextarea.value = "Danh sách thiết bị theo đơn hàng {{ $sale->code }}";
        }
    }
</script>

<!-- Modal Import file hóa đơn (Accountant) -->
<div id="draftModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-blue-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-blue-900">Import file hóa đơn</h3>
            <button onclick="closeDraftModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="draftForm" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Chọn file hóa đơn (PDF, Image, Word) <span class="text-red-500">*</span></label>
                <input type="file" name="draft_file" accept=".pdf,image/*,.doc,.docx" required
                    class="w-full border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center cursor-pointer hover:bg-gray-50 transition-all">
                <p class="text-[10px] text-gray-500 mt-2 italic">* Đính kèm file hóa đơn để Sales kiểm tra và xác nhận.</p>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeDraftModal()"
                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200">HỦY</button>
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">XÁC NHẬN IMPORT</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Xuất hóa đơn chính thức (Finance) -->
<div id="officialModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-green-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-green-900">Xác nhận Hóa đơn chính thức</h3>
            <button onclick="closeOfficialModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="officialForm" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ngày xuất hóa
                            đơn <span class="text-red-500">*</span></label>
                        <input type="date" name="invoice_date" id="official_invoice_date" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Hạn thanh
                            toán <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_due_date" id="official_payment_due_date" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">File hóa đơn chính thức <span class="text-red-500">*</span></label>
                    <input type="file" name="official_file" accept=".pdf,image/*,.doc,.docx" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Biên bản bàn giao (Nếu có)</label>
                    <input type="file" name="delivery_note_file" accept=".pdf,image/*,.doc,.docx"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <p class="text-[10px] text-gray-500 italic">* Đính kèm file hóa đơn chính thức để hoàn tất xuất hóa đơn.</p>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeOfficialModal()"
                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200">HỦY</button>
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 shadow-lg">XÁC
                    NHẬN CHÍNH THỨC</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Từ chối -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-red-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-red-900">Từ chối yêu cầu</h3>
            <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="rejectForm" method="POST" class="p-6">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Lý do từ chối <span
                        class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none"
                    placeholder="VD: Sai thông tin địa chỉ..."></textarea>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeRejectModal()"
                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200">HỦY</button>
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700">XÁC NHẬN TỪ CHỐI</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Chỉnh sửa Nội dung xuất Hóa đơn (Chung & Theo từng Part) -->
<div id="editInvoiceContentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full transform transition-all overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-indigo-50">
            <h3 class="text-lg font-bold text-indigo-900"><i class="fas fa-edit mr-2"></i>Chỉnh sửa Yêu cầu xuất hóa đơn</h3>
            <button onclick="closeEditInvoiceContentModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <form id="editInvoiceContentForm" method="POST" class="p-6 overflow-y-auto max-h-[85vh] space-y-6">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Cột trái: Thông tin hóa đơn & Bên bán -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider border-b border-indigo-50 pb-1.5">
                        <i class="fas fa-file-contract mr-1.5"></i>Thông tin hóa đơn & Bên bán</h4>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tên người bán (Sales P.I.C) <span class="text-red-500">*</span></label>
                        <input type="text" name="seller_name" id="edit_seller_name" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Đơn vị bán hàng <span class="text-red-500">*</span></label>
                        <input type="text" name="seller_company" id="edit_seller_company" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nội dung xuất hóa đơn (Thiết bị/Dịch vụ)</label>
                        <textarea name="invoice_content_note" id="edit_invoice_content_note" rows="3"
                            placeholder="Chi tiết sản phẩm, nội dung đặc biệt khi xuất hóa đơn..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>
                </div>

                <!-- Cột phải: Thông tin giao hàng & Bên mua -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider border-b border-indigo-50 pb-1.5">
                        <i class="fas fa-shipping-fast mr-1.5"></i>Thông tin giao hàng & Bên mua</h4>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tên Công ty/Cá nhân mua hàng <span class="text-red-500">*</span></label>
                        <input type="text" name="tax_name" id="edit_tax_name" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Mã số thuế <span class="text-red-500">*</span></label>
                            <input type="text" name="tax_code" id="edit_tax_code" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email nhận HĐ</label>
                            <input type="email" name="billing_email" id="edit_billing_email"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Địa chỉ xuất hóa đơn <span class="text-red-500">*</span></label>
                        <textarea name="tax_address" id="edit_tax_address" rows="2" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Người nhận hàng</label>
                            <input type="text" name="delivery_contact" id="edit_delivery_contact"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">SĐT nhận hàng</label>
                            <input type="text" name="delivery_phone" id="edit_delivery_phone"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Địa chỉ giao nhận thực tế</label>
                        <textarea name="delivery_address" id="edit_delivery_address" rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
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
                                @endphp
                                <tr>
                                    <td class="p-2.5 text-center font-bold text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="p-2.5">
                                        <div class="font-bold text-gray-800">{{ $pName }}</div>
                                        <div class="text-[11px] text-indigo-600 font-mono">PN: {{ $pCode }}</div>
                                    </td>
                                    <td class="p-2.5 text-right font-bold text-gray-800">{{ $sItem->quantity }}</td>
                                    <td class="p-2.5">
                                        <input type="text" name="item_descriptions[{{ $sItem->id }}]" id="edit_item_desc_{{ $sItem->id }}"
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
                    <textarea name="payment_terms_note" id="edit_payment_terms_note" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ghi chú thêm gửi kế toán</label>
                    <textarea name="note" id="edit_note" rows="2" placeholder="Ghi chú thêm..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                <button type="button" onclick="closeEditInvoiceContentModal()" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg text-xs hover:bg-gray-200">HỦY</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-lg text-xs hover:bg-indigo-700 shadow-md"><i class="fas fa-save mr-1"></i>LƯU THAY ĐỔI</button>
            </div>
        </form>
    </div>
</div>

<script>
    const debtDays = parseInt("{{ $sale->customer->debt_days ?? 30 }}") || 30;

    function openEditInvoiceContentModalFromButton(btn) {
        try {
            const data = JSON.parse(btn.getAttribute('data-request'));
            openEditInvoiceContentModalData(data);
        } catch (e) {
            console.error('Error parsing request data', e);
        }
    }

    function openEditInvoiceContentModalData(requestData) {
        const form = document.getElementById('editInvoiceContentForm');
        form.action = `/invoice-requests/${requestData.id}/update-content`;
        
        document.getElementById('edit_invoice_content_note').value = requestData.invoice_content_note || '';
        document.getElementById('edit_payment_terms_note').value = requestData.payment_terms_note || '';
        document.getElementById('edit_tax_name').value = requestData.tax_name || '';
        document.getElementById('edit_tax_code').value = requestData.tax_code || '';
        document.getElementById('edit_tax_address').value = requestData.tax_address || '';
        document.getElementById('edit_seller_name').value = requestData.seller_name || '';
        document.getElementById('edit_seller_company').value = requestData.seller_company || '';
        document.getElementById('edit_billing_email').value = requestData.billing_email || '';
        document.getElementById('edit_delivery_address').value = requestData.delivery_address || '';
        document.getElementById('edit_delivery_contact').value = requestData.delivery_contact || '';
        document.getElementById('edit_delivery_phone').value = requestData.delivery_phone || '';
        document.getElementById('edit_note').value = requestData.note || '';

        if (requestData.item_descriptions && typeof requestData.item_descriptions === 'object') {
            for (const [key, val] of Object.entries(requestData.item_descriptions)) {
                const input = document.getElementById(`edit_item_desc_${key}`);
                if (input) input.value = val;
            }
        }

        document.getElementById('editInvoiceContentModal').classList.remove('hidden');
    }

    function closeEditInvoiceContentModal() {
        document.getElementById('editInvoiceContentModal').classList.add('hidden');
    }

    function openInvoiceRequestModal() {
        document.getElementById('invoiceRequestModal').classList.remove('hidden');
    }
    function closeInvoiceRequestModal() {
        document.getElementById('invoiceRequestModal').classList.add('hidden');
    }

    function openDraftModal(requestId) {
        const form = document.getElementById('draftForm');
        form.action = `/invoice-requests/${requestId}/issue-draft`;
        document.getElementById('draftModal').classList.remove('hidden');
    }
    function closeDraftModal() {
        document.getElementById('draftModal').classList.add('hidden');
    }

    function openOfficialModal(requestId) {
        const form = document.getElementById('officialForm');
        form.action = `/invoice-requests/${requestId}/issue-official`;

        // Set default invoice date to today
        const today = new Date();
        const formattedToday = formatDate(today);

        const invoiceDateInput = document.getElementById('official_invoice_date');

        if (invoiceDateInput) {
            invoiceDateInput.value = formattedToday;
            updatePaymentDueDate(formattedToday);
        }

        document.getElementById('officialModal').classList.remove('hidden');
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

        const paymentDueDateInput = document.getElementById('official_payment_due_date');
        if (paymentDueDateInput) {
            paymentDueDateInput.value = formatDate(invoiceDate);
        }
    }

    // Add event listener when the DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
        const invoiceDateInput = document.getElementById('official_invoice_date');
        if (invoiceDateInput) {
            invoiceDateInput.addEventListener('change', function () {
                updatePaymentDueDate(this.value);
            });
        }
    });

    function closeOfficialModal() {
        document.getElementById('officialModal').classList.add('hidden');
    }

    function openRejectModal(requestId) {
        const form = document.getElementById('rejectForm');
        form.action = `/invoice-requests/${requestId}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }

    // Close on escape
    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeInvoiceRequestModal();
            closeDraftModal();
            closeOfficialModal();
            closeRejectModal();
            closeEditInvoiceContentModal();
        }
    });
</script>