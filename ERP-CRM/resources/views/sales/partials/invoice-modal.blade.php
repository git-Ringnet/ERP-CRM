<!-- Modal Gửi yêu cầu mới -->
<div id="invoiceRequestModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full transform transition-all">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-indigo-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-indigo-900">Yêu cầu xuất hóa đơn</h3>
            <button onclick="closeInvoiceRequestModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('invoice-requests.store', $sale->id) }}" method="POST" class="p-6">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tên Công ty/Cá nhân (Xuất HĐ) <span class="text-red-500">*</span></label>
                    <input type="text" name="tax_name" value="{{ $sale->customer_name }}" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Mã số thuế <span class="text-red-500">*</span></label>
                        <input type="text" name="tax_code" value="{{ $sale->customer->tax_code ?? '' }}" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email nhận HĐ</label>
                        <input type="email" name="billing_email" value="{{ $sale->customer->email ?? '' }}"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Địa chỉ thuế <span class="text-red-500">*</span></label>
                    <textarea name="tax_address" rows="2" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">{{ $sale->customer->address ?? '' }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ghi chú thêm</label>
                    <textarea name="note" rows="2"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" placeholder="Yêu cầu tách hóa đơn, ngày xuất..."></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-8">
                <button type="button" onclick="closeInvoiceRequestModal()" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 transition-all">HỦY</button>
                <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">GỬI YÊU CẦU</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Xuất hóa đơn nháp (Admin) -->
<div id="draftModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-blue-50 rounded-t-xl">
            <h3 class="text-lg font-bold text-blue-900">Tải lên Hóa đơn nháp</h3>
            <button onclick="closeDraftModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="draftForm" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tải lên hóa đơn nháp (Nếu có) <span class="text-blue-500 italic">(Không bắt buộc)</span></label>
                <input type="file" name="draft_file" accept=".pdf,image/*,.doc,.docx"
                    class="w-full border border-dashed border-gray-300 rounded-lg px-4 py-8 text-center cursor-pointer hover:bg-gray-50 transition-all">
                <p class="text-[10px] text-gray-500 mt-2 italic">* Nếu không tải file, hệ thống sẽ sử dụng mẫu in mặc định làm bản nháp.</p>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeDraftModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200">HỦY</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">XÁC NHẬN NHÁP</button>
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
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ngày xuất hóa đơn <span class="text-red-500">*</span></label>
                        <input type="date" name="invoice_date" id="official_invoice_date" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Hạn thanh toán <span class="text-red-500">*</span></label>
                        <input type="date" name="payment_due_date" id="official_payment_due_date" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">File hóa đơn chính thức <span class="text-blue-500 italic">(Không bắt buộc)</span></label>
                    <input type="file" name="official_file" accept=".pdf,image/*,.doc,.docx"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Biên bản bàn giao (Nếu có)</label>
                    <input type="file" name="delivery_note_file" accept=".pdf,image/*,.doc,.docx"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <p class="text-[10px] text-gray-500 italic">* Nếu không tải file, hệ thống sẽ chỉ cập nhật trạng thái "Đã xuất hóa đơn chính thức".</p>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeOfficialModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200">HỦY</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 shadow-lg">XÁC NHẬN CHÍNH THỨC</button>
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
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Lý do từ chối <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none" placeholder="VD: Sai thông tin địa chỉ..."></textarea>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeRejectModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200">HỦY</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700">XÁC NHẬN TỪ CHỐI</button>
            </div>
        </form>
    </div>
</div>

<script>
const debtDays = parseInt("{{ $sale->customer->debt_days ?? 30 }}") || 30;

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
document.addEventListener('DOMContentLoaded', function() {
    const invoiceDateInput = document.getElementById('official_invoice_date');
    if (invoiceDateInput) {
        invoiceDateInput.addEventListener('change', function() {
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
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeInvoiceRequestModal();
        closeDraftModal();
        closeOfficialModal();
        closeRejectModal();
    }
});
</script>
