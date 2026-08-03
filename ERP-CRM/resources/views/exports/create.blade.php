@extends('layouts.app')

@section('title', 'Tạo phiếu xuất kho')
@section('page-title', 'Tạo Phiếu Xuất Kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-arrow-up text-orange-500 mr-2"></i>Thông tin phiếu xuất
        </h2>
        <a href="{{ route('exports.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>
    
    <form action="{{ route('exports.store') }}" method="POST" class="p-4" id="exportForm" onsubmit="return validateForm()">
        @csrf
        
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-circle mr-2 text-lg"></i>
                    <span class="font-bold">Vui lòng kiểm tra lại các lỗi sau:</span>
                </div>
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-800 rounded-lg flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã phiếu xuất</label>
                <input type="text" value="{{ $code }}" readonly
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Ngày xuất <span class="text-red-500">*</span>
                </label>
                <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nhân viên xuất</label>
                <select name="employee_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Chọn nhân viên --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dự án</label>
                <select name="project_id" id="project_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg" onchange="toggleExportType()">
                    <option value="">-- Không chọn dự án --</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->code }} - {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Khách hàng</label>
                <select name="customer_id" id="customer_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Không chọn khách hàng --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" 
                                data-tax-code="{{ $customer->tax_code }}" 
                                data-abv-name="{{ $customer->abv_name }}"
                                {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}{{ $customer->code ? ' (' . $customer->code . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="flex justify-between items-center mb-1 gap-1 whitespace-nowrap overflow-hidden">
                    <label class="text-sm font-medium text-gray-700 truncate">Người phụ trách</label>
                    <button type="button" id="btn-quick-add-contact" class="text-xs text-blue-600 hover:text-blue-800 font-medium whitespace-nowrap flex-shrink-0 hidden">
                        <i class="fas fa-plus mr-1"></i>Thêm mới
                    </button>
                </div>
                <select name="contact_id" id="contact_id" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg" disabled>
                    <option value="">-- Chọn người phụ trách --</option>
                </select>
                <div id="pic_details" class="hidden mt-2 p-2 bg-slate-50 border border-slate-100 rounded-lg text-xs text-gray-600 space-y-1">
                    <p class="font-medium text-gray-700 mb-1"><span id="pic_name"></span></p>
                    <p><i class="fas fa-envelope text-gray-400 mr-1.5 w-4"></i><span id="pic_email"></span></p>
                    <p><i class="fas fa-phone text-gray-400 mr-1.5 w-4"></i><span id="pic_phone"></span></p>
                    <p><i class="fas fa-briefcase text-gray-400 mr-1.5 w-4"></i><span id="pic_position"></span></p>
                </div>
            </div>

            <div class="md:col-span-4">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Chọn <strong>Dự án</strong> nếu xuất cho dự án, hoặc <strong>Khách hàng</strong> nếu xuất bán/giao hàng cho khách hàng. Có thể để trống cả hai.
                </p>
            </div>

            <div class="md:col-span-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                <textarea name="note" rows="1" 
                          class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('note') }}</textarea>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Danh sách sản phẩm xuất</h3>
                <button type="button" onclick="addItem()" 
                        class="px-4 py-2 text-sm bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                    <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                </button>
            </div>

            <div id="itemsContainer" class="space-y-4"></div>
            
            <div class="mt-4 p-4 bg-orange-50 rounded-lg border border-orange-100 flex justify-between items-center shadow-sm">
                <span class="text-sm font-bold text-orange-800 uppercase tracking-wider">Tổng cộng phiếu xuất:</span>
                <span id="grandTotalDisplay" class="text-xl font-black text-orange-600">0 đ</span>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
            <a href="{{ route('exports.index') }}" 
               class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                <i class="fas fa-times mr-1"></i> Hủy
            </a>
            <button type="submit" class="px-4 py-2 text-sm text-white bg-orange-500 rounded-lg hover:bg-orange-600">
                <i class="fas fa-save mr-1"></i> Lưu phiếu xuất
            </button>
        </div>
        </div>
    </form>
</div>

<!-- Quick Add Customer Modal -->
<div id="addCustomerModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="modalOverlay"></div>

        <!-- Trick to center the modal contents -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg leading-6 font-semibold text-gray-900" id="modal-title">
                        <i class="fas fa-user-plus text-blue-500 mr-2"></i> Thêm khách hàng nhanh
                    </h3>
                    <button type="button" id="closeCustomerModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Validation Error Message Block -->
                <div id="modalErrors" class="hidden p-3 mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"></div>

                <form id="customerModalForm" class="space-y-4">
                    @csrf
                    <!-- MST with lookup -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Mã số thuế (MST) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" name="tax_code" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="Nhập MST để tra cứu...">
                            <button type="button" id="btn-modal-search-tax"
                                    class="absolute right-0 top-0 h-full px-3 text-gray-400 hover:text-primary transition-colors focus:outline-none"
                                    title="Tra cứu thông tin doanh nghiệp từ MST">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tên khách hàng/Công ty <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                               placeholder="Nhập tên khách hàng...">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tên viết tắt <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="abv_name" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="VD: ADG, IIJ...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email công ty
                            </label>
                            <input type="email" name="email"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="email@company.com">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Số điện thoại công ty
                            </label>
                            <input type="text" name="phone"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="0123456789">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Địa chỉ
                            </label>
                            <input type="text" name="address"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="Nhập địa chỉ...">
                        </div>
                    </div>

                    <!-- Dynamic Contacts Section -->
                    <div class="border-t pt-3 mt-4">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-semibold text-gray-900">
                                <i class="fas fa-users text-blue-500 mr-1.5"></i> Danh sách người liên hệ <span class="text-red-500">*</span>
                            </h4>
                            <button type="button" id="modalAddContactBtn"
                                    class="inline-flex items-center px-2.5 py-1 border border-transparent text-xs font-medium rounded bg-blue-600 hover:bg-blue-700 text-white focus:outline-none transition-colors">
                                <i class="fas fa-plus mr-1"></i> Thêm người liên hệ
                            </button>
                        </div>
                        <div id="modalContactsContainer" class="space-y-3">
                            <!-- First contact card (always present) -->
                            <div class="modal-contact-card p-3 border border-gray-200 rounded-lg bg-gray-50/50" data-contact-index="0">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-xs font-bold text-gray-500 uppercase contact-label">Người liên hệ #1</span>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="modal_primary_contact" value="0" checked class="form-radio text-primary h-3.5 w-3.5">
                                            <span class="ml-1.5 text-xs text-gray-600">Liên hệ chính</span>
                                        </label>
                                        <button type="button" class="btn-remove-modal-contact text-red-400 hover:text-red-600 transition-colors hidden">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Họ & Tên <span class="text-red-500">*</span></label>
                                        <input type="text" class="contact-name w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nhập họ tên...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                                        <input type="text" class="contact-position w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="VD: Giám đốc...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                                        <input type="text" class="contact-phone w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nhập SĐT...">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                                        <input type="email" class="contact-email w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="email@example.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                <button type="button" id="saveCustomerBtn"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                    <i class="fas fa-save mr-1.5 mt-0.5"></i> Lưu
                </button>
                <button type="button" id="cancelCustomerBtn"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Hủy
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Single Contact Modal -->
<div id="addSingleContactModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="singleContactModalOverlay"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h3 class="text-lg leading-6 font-semibold text-gray-900">
                        <i class="fas fa-user-plus text-blue-500 mr-2"></i> Thêm người phụ trách mới
                    </h3>
                    <button type="button" id="closeSingleContactModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <div id="singleContactModalErrors" class="hidden p-3 mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"></div>

                <form id="singleContactModalForm" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Họ & Tên <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Nhập họ tên...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Chức vụ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="position" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="VD: Giám đốc, Kế toán...">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Số điện thoại <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="phone" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="0123456789">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="email@company.com">
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                <button type="button" id="saveSingleContactBtn" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                    <i class="fas fa-save mr-1.5 mt-0.5"></i> Lưu
                </button>
                <button type="button" id="cancelSingleContactBtn" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                    Hủy
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let isTogglingExport = false;

// Toggle between project and customer selection
function toggleExportType() {
    if (isTogglingExport) return;
    isTogglingExport = true;
    try {
        const projectSelect = document.getElementById('project_id');
        const customerSelect = $('#customer_id');
        const contactSelect = $('#contact_id');
        const btnAddContact = $('#btn-quick-add-contact');
        
        if (projectSelect && customerSelect.length) {
            // If project is selected, disable customer & contact
            if (projectSelect.value) {
                if (customerSelect.val()) {
                    customerSelect.val('').trigger('change.select2');
                }
                customerSelect.prop('disabled', true);
                contactSelect.val('').prop('disabled', true);
                btnAddContact.addClass('hidden');
            } else {
                customerSelect.prop('disabled', false);
            }
            
            // If customer is selected, disable project
            const selectedCustId = customerSelect.val();
            if (selectedCustId) {
                projectSelect.value = '';
                projectSelect.disabled = true;
                projectSelect.classList.add('bg-gray-100');
            } else {
                projectSelect.disabled = false;
                projectSelect.classList.remove('bg-gray-100');
            }
        }
    } finally {
        isTogglingExport = false;
    }
}

let exportsContactsData = [];

function updatePicDetails() {
    const val = $('#contact_id').val();
    const contact = exportsContactsData.find(c => c.id == val);
    if (contact) {
        $('#pic_name').text(contact.name);
        $('#pic_email').text(contact.email || 'N/A');
        $('#pic_phone').text(contact.phone || 'N/A');
        $('#pic_position').text(contact.position || 'N/A');
        $('#pic_details').removeClass('hidden');
    } else {
        $('#pic_details').addClass('hidden');
    }
}

$('#contact_id').on('change', updatePicDetails);

// Function to load contacts for customer
function loadCustomerContacts(customerId, selectedContactId = null) {
    const contactSelect = $('#contact_id');
    const btnAddContact = $('#btn-quick-add-contact');
    
    if (!customerId) {
        contactSelect.html('<option value="">-- Chọn người phụ trách --</option>').prop('disabled', true);
        btnAddContact.addClass('hidden');
        $('#pic_details').addClass('hidden');
        exportsContactsData = [];
        return;
    }

    btnAddContact.removeClass('hidden');
    contactSelect.html('<option value="">Đang tải...</option>').prop('disabled', true);

    fetch(`/ajax/customers/${customerId}/contacts`)
        .then(response => response.json())
        .then(contacts => {
            exportsContactsData = contacts;
            let options = '<option value="">-- Chọn người phụ trách --</option>';
            contacts.forEach(c => {
                const isSel = (selectedContactId && selectedContactId == c.id) || (!selectedContactId && c.is_primary) ? 'selected' : '';
                options += `<option value="${c.id}" ${isSel}>${c.name} ${c.is_primary ? '(Mặc định)' : ''}</option>`;
            });
            contactSelect.html(options).prop('disabled', false);
            updatePicDetails();
        })
        .catch(err => {
            console.error('Error fetching contacts:', err);
            contactSelect.html('<option value="">Không tải được người liên hệ</option>').prop('disabled', false);
            $('#pic_details').addClass('hidden');
        });
}

let itemIndex = 0;
const PRODUCT_SEARCH_URL = '{{ route("products.ajax-search") }}';
const warehouses = @json($warehouses);
let stockCache = {};

function addItem(existingData = null) {
    const container = document.getElementById('itemsContainer');
    const itemDiv = document.createElement('div');
    itemDiv.className = 'item-card bg-gray-50 rounded-lg p-4 border border-gray-200';
    itemDiv.dataset.index = itemIndex;
    
    const warehouseOptions = warehouses.map(w => 
        `<option value="${w.id}" ${existingData && existingData.warehouse_id == w.id ? 'selected' : ''}>${w.name}</option>`
    ).join('');
    
    itemDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-medium text-gray-700">Sản phẩm #${itemIndex + 1}</h4>
            <button type="button" onclick="removeItem(${itemIndex})" 
                    class="px-2 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200">
                <i class="fas fa-trash mr-1"></i>Xóa
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
            <div class="md:col-span-5">
                <label class="block text-xs font-medium text-gray-600 mb-1">Sản phẩm *</label>
                <div class="searchable-select product-searchable" data-index="${itemIndex}">
                    <input type="text" class="searchable-input w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                           placeholder="Gõ để tìm sản phẩm..." autocomplete="off"
                           value="${existingData && existingData.product_code ? existingData.product_code + ' - ' + existingData.product_name : ''}">
                    <input type="hidden" name="items[${itemIndex}][product_id]" required class="product-id-input"
                           value="${existingData ? existingData.product_id : ''}">
                    <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-lg">
                    </div>
                </div>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Kho xuất *</label>
                <select name="items[${itemIndex}][warehouse_id]" required 
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded"
                        onchange="loadStockInfo(${itemIndex})">
                    <option value="">-- Chọn kho --</option>
                    ${warehouseOptions}
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">SL Yêu cầu</label>
                <input type="number" name="items[${itemIndex}][requested_quantity]" value="${existingData ? existingData.requested_quantity || '' : ''}" 
                       min="1" step="1" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" 
                       placeholder="Yêu cầu">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Thực xuất *</label>
                <input type="number" name="items[${itemIndex}][quantity]" value="${existingData ? existingData.quantity : '1'}" 
                       required min="1" step="1" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded quantity-input" 
                       placeholder="1" onchange="updateRowTotal(${itemIndex})">
            </div>
            
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Đơn giá</label>
                <input type="text" value="0" 
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded unit-price-display" 
                       oninput="onPriceInput(this, ${itemIndex})">
                <input type="hidden" name="items[${itemIndex}][unit_price]" value="0" class="unit-price-raw">
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Thành tiền</label>
                <input type="text" value="0" 
                       readonly class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded bg-gray-100 total-display">
                <input type="hidden" name="items[${itemIndex}][total]" value="0" class="total-raw">
            </div>
            <div class="md:col-span-6">
                <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
                <input type="text" name="items[${itemIndex}][comments]" value="${existingData ? existingData.comments || '' : ''}"
                       class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded" placeholder="Ghi chú...">
            </div>
        </div>
        
        <!-- Stock Info -->
        <div id="stockInfo_${itemIndex}" class="mb-3 hidden">
            <div class="flex items-center justify-between p-2 bg-white rounded border border-gray-200">
                <span class="text-xs font-medium text-gray-600">
                    <i class="fas fa-warehouse mr-1"></i>Tồn kho:
                </span>
                <span id="stockSummary_${itemIndex}" class="text-sm font-medium"></span>
            </div>
        </div>
        
        <!-- Serial Selection -->
        <div id="serialSection_${itemIndex}" class="hidden">
            <div class="flex justify-between items-center mb-2">
                <label class="block text-xs font-medium text-gray-600">
                    <i class="fas fa-barcode mr-1"></i>Danh sách Serial xuất * <span class="text-gray-400" id="serialAvailableCount_${itemIndex}"></span>
                </label>
            </div>
            <textarea name="items[${itemIndex}][serial_list]" rows="2"
                      class="serial-textarea w-full px-2 py-1.5 text-sm border border-gray-300 rounded font-mono" 
                      placeholder="Đặt con trỏ vào đây để quét mã Serial, hoặc nhập mỗi mã trên một dòng/dấu phẩy"
                      oninput="validateExportSerials(${itemIndex})"></textarea>
            
            <div class="mt-2">
                <span class="text-xs font-semibold text-gray-600">Serial khả dụng trong kho (Click để chọn nhanh):</span>
                <div id="availableSerialsContainer_${itemIndex}" class="flex flex-wrap gap-1 mt-1 max-h-24 overflow-y-auto p-1.5 bg-gray-100 rounded border border-gray-200">
                </div>
            </div>
            <p id="serialWarning_${itemIndex}" class="text-xs mt-2 hidden"></p>
        </div>
    `;
    
    container.appendChild(itemDiv);
    itemIndex++;
}

function removeItem(index) {
    const item = document.querySelector(`[data-index="${index}"]`);
    if (item) {
        item.remove();
        updateGrandTotal();
    }
}

async function loadStockInfo(itemIdx) {
    const warehouseSelect = document.querySelector(`[name="items[${itemIdx}][warehouse_id]"]`);
    const productSelect = document.querySelector(`[name="items[${itemIdx}][product_id]"]`);
    const warehouseId = warehouseSelect.value;
    const productId = productSelect.value;
    const stockInfoDiv = document.getElementById(`stockInfo_${itemIdx}`);
    const stockSummary = document.getElementById(`stockSummary_${itemIdx}`);
    const serialSection = document.getElementById(`serialSection_${itemIdx}`);
    
    if (!warehouseId || !productId) {
        stockInfoDiv.classList.add('hidden');
        serialSection.classList.add('hidden');
        return;
    }
    
    const cacheKey = `${productId}_${warehouseId}`;
    
    if (!stockCache[cacheKey]) {
        try {
            const response = await fetch(`/exports/available-items?product_id=${productId}&warehouse_id=${warehouseId}`);
            stockCache[cacheKey] = await response.json();
        } catch (e) {
            stockCache[cacheKey] = { items: [], noSkuCount: 0 };
        }
    }
    
    const data = stockCache[cacheKey];
    const serialItems = data.items || [];
    const noSkuCount = data.noSkuCount || 0;
    const totalStock = serialItems.length + noSkuCount;
    
    stockInfoDiv.classList.remove('hidden');
    
    if (totalStock === 0) {
        stockSummary.innerHTML = `<span class="text-red-600">Hết hàng</span>`;
        serialSection.classList.add('hidden');
    } else {
        let summaryHtml = `<span class="text-green-600">${totalStock} sản phẩm</span>`;
        if (serialItems.length > 0) summaryHtml += ` (<span class="text-blue-600">${serialItems.length} có serial</span>`;
        if (noSkuCount > 0) summaryHtml += serialItems.length > 0 ? `, ` : ` (`;
        if (noSkuCount > 0) summaryHtml += `<span class="text-gray-600">${noSkuCount} không serial</span>`;
        if (serialItems.length > 0 || noSkuCount > 0) summaryHtml += `)`;
        
        // Show avg_cost
        const avgCost = data.avg_cost || 0;
        if (avgCost > 0) {
            summaryHtml += ` · <span class="text-amber-700 font-semibold">Đơn giá: ${Number(avgCost).toLocaleString('en-US')} đ</span>`;
            
            // Auto-fill unit price if current value is 0 or empty
            const priceDisplay = document.querySelector(`[data-index="${itemIdx}"] .unit-price-display`);
            const priceRaw = document.querySelector(`[data-index="${itemIdx}"] .unit-price-raw`);
            if (priceRaw && (parseFloat(priceRaw.value) === 0 || !priceRaw.value)) {
                priceRaw.value = avgCost;
                priceDisplay.value = formatNumber(avgCost);
                updateRowTotal(itemIdx);
            }
        }
        
        stockSummary.innerHTML = summaryHtml;
        
        if (serialItems.length > 0) {
            serialSection.classList.remove('hidden');
            updateSerialSection(itemIdx, data);
        } else {
            serialSection.classList.add('hidden');
        }
    }
    
    validateExportSerials(itemIdx);
}

function formatNumber(num) {
    if (isNaN(num)) return '0';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(num);
}
window.formatNumberValue = formatNumber;

function onPriceInput(input, itemIdx) {
    // Remove all non-numeric characters except decimal point
    let value = input.value.replace(/,/g, '');
    if (value === '.') value = '0.';
    
    const numValue = parseFloat(value) || 0;
    
    // Store raw value
    const rawInput = document.querySelector(`[data-index="${itemIdx}"] .unit-price-raw`);
    if (rawInput) rawInput.value = value;
    
    // Update display with formatting (only if not ending with . to allow typing decimals)
    if (!value.endsWith('.')) {
        input.value = formatNumber(numValue);
    }
    
    updateRowTotal(itemIdx);
}

function updateRowTotal(itemIdx) {
    const qtyInput = document.querySelector(`[name="items[${itemIdx}][quantity]"]`);
    const priceRaw = document.querySelector(`[data-index="${itemIdx}"] .unit-price-raw`);
    const totalDisplay = document.querySelector(`[data-index="${itemIdx}"] .total-display`);
    const totalRaw = document.querySelector(`[data-index="${itemIdx}"] .total-raw`);
    
    if (!qtyInput || !priceRaw || !totalDisplay || !totalRaw) return;
    
    const qty = parseFloat(qtyInput.value) || 0;
    const price = parseFloat(priceRaw.value) || 0;
    const total = qty * price;
    
    totalRaw.value = total.toFixed(2);
    totalDisplay.value = formatNumber(total);
    
    updateGrandTotal();
    
    // Trigger serial validation
    validateExportSerials(itemIdx);
}

function updateGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.total-raw').forEach(input => {
        grandTotal += parseFloat(input.value) || 0;
    });
    
    const display = document.getElementById('grandTotalDisplay');
    if (display) {
        display.innerText = formatNumber(grandTotal) + ' đ';
    }
}

function updateSerialSection(itemIdx, data) {
    const serialSection = document.getElementById(`serialSection_${itemIdx}`);
    const availableContainer = document.getElementById(`availableSerialsContainer_${itemIdx}`);
    const availableCountEl = document.getElementById(`serialAvailableCount_${itemIdx}`);
    
    if (!serialSection) return;
    
    const serialItems = data.items || [];
    const noSkuCount = data.noSkuCount || 0;
    
    if (serialItems.length > 0) {
        serialSection.classList.remove('hidden');
        if (availableCountEl) {
            availableCountEl.innerText = `(Tồn kho có serial: ${serialItems.length}, không serial: ${noSkuCount})`;
        }
        
        // Render badges for available serials
        if (availableContainer) {
            availableContainer.innerHTML = '';
            serialItems.forEach(item => {
                const badge = document.createElement('button');
                badge.type = 'button';
                badge.className = 'serial-badge px-2 py-0.5 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded hover:bg-blue-100 font-mono transition-colors';
                badge.innerText = item.sku;
                badge.dataset.sku = item.sku;
                badge.onclick = () => toggleSerialInTextarea(itemIdx, item.sku);
                availableContainer.appendChild(badge);
            });
        }
        
        validateExportSerials(itemIdx);
    } else {
        serialSection.classList.add('hidden');
    }
}

function toggleSerialInTextarea(itemIdx, sku) {
    const textarea = document.querySelector(`[name="items[${itemIdx}][serial_list]"]`);
    if (!textarea) return;
    
    let text = textarea.value.trim();
    let serials = text ? text.split(/[\n,]+/).map(s => s.trim()).filter(s => s) : [];
    
    const index = serials.indexOf(sku);
    if (index > -1) {
        serials.splice(index, 1);
    } else {
        serials.push(sku);
    }
    
    textarea.value = serials.join('\n');
    validateExportSerials(itemIdx);
}

function validateExportSerials(itemIdx) {
    const itemCard = document.querySelector(`[data-index="${itemIdx}"]`);
    if (!itemCard) return;
    
    const qtyInput = itemCard.querySelector('.quantity-input');
    const textarea = itemCard.querySelector('.serial-textarea');
    const warningEl = document.getElementById(`serialWarning_${itemIdx}`);
    const availableContainer = document.getElementById(`availableSerialsContainer_${itemIdx}`);
    
    if (!qtyInput || !textarea || !warningEl) return;
    
    const qty = parseInt(qtyInput.value) || 1;
    const text = textarea.value.trim();
    let enteredSerials = [];
    if (text) {
        enteredSerials = text.split(/[\n,]+/).map(s => s.trim()).filter(s => s);
    }
    
    // Check duplicates in input
    const uniqueEntered = [...new Set(enteredSerials)];
    const hasDupes = uniqueEntered.length < enteredSerials.length;
    
    // Get available serials from cache
    const warehouseSelect = itemCard.querySelector('[name*="[warehouse_id]"]');
    const productSelect = itemCard.querySelector('[name*="[product_id]"]');
    const warehouseId = warehouseSelect ? warehouseSelect.value : '';
    const productId = productSelect ? productSelect.value : '';
    const cacheKey = `${productId}_${warehouseId}`;
    const data = stockCache[cacheKey] || { items: [], noSkuCount: 0 };
    const availableSerials = (data.items || []).map(item => item.sku);
    const noSkuCount = data.noSkuCount || 0;
    
    // Update badge active styling
    if (availableContainer) {
        availableContainer.querySelectorAll('.serial-badge').forEach(badge => {
            const sku = badge.dataset.sku;
            if (enteredSerials.includes(sku)) {
                badge.className = 'serial-badge px-2 py-0.5 text-xs bg-blue-600 text-white border border-blue-600 rounded hover:bg-blue-700 font-mono transition-colors';
            } else {
                badge.className = 'serial-badge px-2 py-0.5 text-xs bg-blue-50 text-blue-700 border border-blue-200 rounded hover:bg-blue-100 font-mono transition-colors';
            }
        });
    }
    
    // Validate individual serials
    let invalidSerials = [];
    enteredSerials.forEach(sku => {
        if (!availableSerials.includes(sku)) {
            invalidSerials.push(sku);
        }
    });
    
    warningEl.classList.remove('hidden');
    
    if (hasDupes) {
        warningEl.className = 'text-xs mt-2 text-red-600';
        warningEl.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>Lỗi: Có số serial bị nhập trùng lặp!`;
        textarea.classList.add('border-red-500');
    } else if (invalidSerials.length > 0) {
        warningEl.className = 'text-xs mt-2 text-red-600';
        warningEl.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>Lỗi: Các serial sau không tồn tại trong kho này: <span class="font-bold">${invalidSerials.join(', ')}</span>`;
        textarea.classList.add('border-red-500');
    } else if (enteredSerials.length > qty) {
        warningEl.className = 'text-xs mt-2 text-red-600';
        warningEl.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>Lỗi: Số serial quét được (${enteredSerials.length}) vượt quá số lượng xuất (${qty})!`;
        textarea.classList.add('border-red-500');
    } else {
        textarea.classList.remove('border-red-500');
        const remainingQty = qty - enteredSerials.length;
        
        if (remainingQty > noSkuCount) {
            const needSerials = remainingQty - noSkuCount;
            warningEl.className = 'text-xs mt-2 text-yellow-600';
            warningEl.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i>Cần nhập thêm ${needSerials} serial (chỉ có ${noSkuCount} sản phẩm không serial)`;
        } else if (enteredSerials.length > 0) {
            warningEl.className = 'text-xs mt-2 text-green-600';
            warningEl.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Đã khớp ${enteredSerials.length} serial${remainingQty > 0 ? ', ' + remainingQty + ' không serial' : ''}, đủ số lượng.`;
        } else if (qty <= noSkuCount) {
            warningEl.className = 'text-xs mt-2 text-green-600';
            warningEl.innerHTML = `<i class="fas fa-check-circle mr-1"></i>Sẽ xuất ${qty} sản phẩm không serial`;
        } else {
            warningEl.classList.add('hidden');
        }
    }
}

function validateForm() {
    const items = document.querySelectorAll('.item-card');
    let hasError = false;
    let errorMessages = [];
    
    items.forEach((item, idx) => {
        const itemIndex = item.dataset.index;
        const warehouseSelect = document.querySelector(`[name="items[${itemIndex}][warehouse_id]"]`);
        const productSelect = document.querySelector(`[name="items[${itemIndex}][product_id]"]`);
        const qtyInput = document.querySelector(`[name="items[${itemIndex}][quantity]"]`);
        const serialTextarea = document.querySelector(`[name="items[${itemIndex}][serial_list]"]`);
        
        const warehouseId = warehouseSelect ? warehouseSelect.value : '';
        const productId = productSelect ? productSelect.value : '';
        const qty = qtyInput ? parseInt(qtyInput.value) || 0 : 0;
        
        if (!productId) {
            hasError = true;
            errorMessages.push(`Sản phẩm #${idx + 1}: Chưa chọn sản phẩm hoặc chưa tìm thấy mã sản phẩm.`);
            return;
        }
        
        if (!warehouseId) {
            hasError = true;
            errorMessages.push(`Sản phẩm #${idx + 1}: Chưa chọn kho xuất.`);
            return;
        }
        
        if (qty <= 0) {
            hasError = true;
            errorMessages.push(`Sản phẩm #${idx + 1}: Số lượng phải lớn hơn 0.`);
            return;
        }
        
        const cacheKey = `${productId}_${warehouseId}`;
        const data = stockCache[cacheKey] || { items: [], noSkuCount: 0 };
        const serialItems = data.items || [];
        const noSkuCount = data.noSkuCount || 0;
        const totalStock = serialItems.length + noSkuCount;
        
        let enteredSerials = [];
        if (serialTextarea && serialTextarea.value.trim()) {
            enteredSerials = serialTextarea.value.trim().split(/[\n,]+/).map(s => s.trim()).filter(s => s);
        }
        
        const selectedCount = enteredSerials.length;
        const remainingQty = qty - selectedCount;
        
        const productSearchInput = item.querySelector('.searchable-input');
        const productName = productSearchInput ? productSearchInput.value : `Sản phẩm #${idx + 1}`;
        
        // Check if quantity exceeds stock
        if (qty > totalStock) {
            hasError = true;
            errorMessages.push(`Sản phẩm "${productName}": Số lượng (${qty}) vượt quá tồn kho (${totalStock})`);
        }
        // Check if need more serials
        else if (remainingQty > noSkuCount && serialItems.length > 0) {
            hasError = true;
            const needSerials = remainingQty - noSkuCount;
            errorMessages.push(`Sản phẩm "${productName}": Cần nhập thêm ${needSerials} serial (chỉ có ${noSkuCount} sản phẩm không serial)`);
        }
        else if (selectedCount > qty) {
            hasError = true;
            errorMessages.push(`Sản phẩm "${productName}": Số serial nhập vào (${selectedCount}) nhiều hơn số lượng xuất (${qty})`);
        }
        
        // Check duplicates
        const uniqueEntered = [...new Set(enteredSerials)];
        if (uniqueEntered.length < enteredSerials.length) {
            hasError = true;
            errorMessages.push(`Sản phẩm "${productName}": Có số serial bị nhập trùng lặp!`);
        }
        
        // Check if entered serials exist in warehouse stock
        const availableSerials = serialItems.map(item => item.sku);
        let invalidSerials = [];
        enteredSerials.forEach(sku => {
            if (!availableSerials.includes(sku)) {
                invalidSerials.push(sku);
            }
        });
        if (invalidSerials.length > 0) {
            hasError = true;
            errorMessages.push(`Sản phẩm "${productName}": Các serial sau không tồn tại trong kho này: ${invalidSerials.join(', ')}`);
        }
    });
    
    if (hasError) {
        alert('Không thể lưu phiếu xuất:\n\n' + errorMessages.join('\n'));
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    addItem();
});

// AJAX Searchable Select for Products
let searchTimers = {};

function initSearchableSelect(container) {
    const input = container.querySelector('.searchable-input');
    const hiddenInput = container.querySelector('input[type="hidden"]');
    const dropdown = container.querySelector('.searchable-dropdown');
    const itemIdx = parseInt(container.dataset.index);
    
    input.addEventListener('input', (e) => {
        clearTimeout(searchTimers[itemIdx]);
        const query = e.target.value.trim();
        
        if (query.length < 1) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            return;
        }
        
        dropdown.innerHTML = '<div class="px-3 py-2 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-1"></i>Đang tìm...</div>';
        dropdown.classList.remove('hidden');
        
        searchTimers[itemIdx] = setTimeout(async () => {
            try {
                const response = await fetch(`${PRODUCT_SEARCH_URL}?q=${encodeURIComponent(query)}`);
                const results = await response.json();
                
                const selectedIds = [];
                document.querySelectorAll('.product-id-input').forEach(inp => {
                    if (inp.value && inp !== hiddenInput) selectedIds.push(inp.value);
                });
                
                const filtered = results.filter(p => !selectedIds.includes(p.id.toString()));
                
                dropdown.innerHTML = '';
                if (filtered.length === 0) {
                    dropdown.innerHTML = '<div class="px-3 py-2 text-gray-500 italic text-sm">Không tìm thấy sản phẩm</div>';
                } else {
                    filtered.forEach(p => {
                        const opt = document.createElement('div');
                        opt.className = 'searchable-option px-3 py-2 hover:bg-blue-50 cursor-pointer text-sm';
                        opt.dataset.value = p.id;
                        opt.dataset.text = `${p.code} - ${p.name}`;
                        opt.dataset.price = p.price || '0';
                        opt.textContent = p.code;
                        opt.addEventListener('click', () => {
                            input.value = p.code;
                            hiddenInput.value = p.id;
                            
                            // Auto-fill price
                            const row = container.closest('.item-row') || container.parentElement.parentElement;
                            const priceDisplay = row.querySelector('.unit-price-display');
                            const priceRaw = row.querySelector('.unit-price-raw');
                            if (priceDisplay && priceRaw) {
                                priceDisplay.value = formatNumber(p.price || 0);
                                priceRaw.value = p.price || 0;
                                updateRowTotal(itemIdx);
                            }
                            
                            dropdown.classList.add('hidden');
                            loadStockInfo(itemIdx);
                        });
                        dropdown.appendChild(opt);
                    });
                }
                dropdown.classList.remove('hidden');
            } catch (err) {
                dropdown.innerHTML = '<div class="px-3 py-2 text-red-500 text-sm">Lỗi tìm kiếm</div>';
            }
        }, 300);
    });
    
    document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
    
    input.addEventListener('keydown', (e) => {
        const options = dropdown.querySelectorAll('.searchable-option');
        const highlighted = dropdown.querySelector('.searchable-option.highlighted');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!highlighted && options.length) {
                options[0].classList.add('highlighted');
            } else if (highlighted) {
                const opts = [...options];
                const idx = opts.indexOf(highlighted);
                if (idx < opts.length - 1) {
                    highlighted.classList.remove('highlighted');
                    opts[idx + 1].classList.add('highlighted');
                    opts[idx + 1].scrollIntoView({ block: 'nearest' });
                }
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (highlighted) {
                const opts = [...options];
                const idx = opts.indexOf(highlighted);
                if (idx > 0) {
                    highlighted.classList.remove('highlighted');
                    opts[idx - 1].classList.add('highlighted');
                    opts[idx - 1].scrollIntoView({ block: 'nearest' });
                }
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlighted) highlighted.click();
        } else if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
        }
    });
}

const originalAddItem = addItem;
addItem = function(existingData = null) {
    originalAddItem(existingData);
    setTimeout(() => {
        const lastItem = document.querySelector(`[data-index="${itemIndex - 1}"]`);
        if (lastItem) {
            const searchable = lastItem.querySelector('.product-searchable');
            if (searchable && !searchable.dataset.initialized) {
                initSearchableSelect(searchable);
                searchable.dataset.initialized = 'true';
            }
        }
    }, 0);
};

// Select2 Initialization and Modal handlers
$(document).ready(function() {
    function matchCustomer(params, data) {
        if ($.trim(params.term) === '') {
            return data;
        }
        if (typeof data.text === 'undefined') {
            return null;
        }
        var term = params.term.toLowerCase();
        var text = data.text.toLowerCase();
        
        var taxCode = '';
        var abvName = '';
        if (data.element) {
            taxCode = $(data.element).data('tax-code') ? $(data.element).data('tax-code').toString().toLowerCase() : '';
            abvName = $(data.element).data('abv-name') ? $(data.element).data('abv-name').toString().toLowerCase() : '';
        }

        if (text.indexOf(term) > -1 || taxCode.indexOf(term) > -1 || abvName.indexOf(term) > -1) {
            return data;
        }
        return null;
    }

    $('select[name="customer_id"]').select2({
        placeholder: "Chọn khách hàng",
        allowClear: true,
        width: '100%',
        matcher: matchCustomer,
        language: {
            noResults: function () {
                return `<div class="p-2 text-center text-gray-500">
                            <div class="mb-1 text-xs">Không tìm thấy khách hàng nào</div>
                            <button type="button" id="btn-quick-add-customer" class="w-full inline-flex justify-center items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded bg-blue-600 hover:bg-blue-700 text-white focus:outline-none transition-colors">
                                <i class="fas fa-plus mr-1"></i> Thêm khách hàng nhanh
                            </button>
                        </div>`;
            }
        },
        escapeMarkup: function (markup) {
            return markup;
        }
    });

    $('select[name="customer_id"]').on('select2:select', function () {
        $(this).select2('close');
    });

    $('select[name="customer_id"]').on('change', function () {
        toggleExportType();
        const custId = $(this).val();
        loadCustomerContacts(custId);
    });

    $('#project_id').on('change', function() {
        toggleExportType();
    });

    // Handle initial state
    toggleExportType();
    const initialCustId = $('select[name="customer_id"]').val();
    if (initialCustId) {
        const oldContactId = '{{ old('contact_id') }}';
        loadCustomerContacts(initialCustId, oldContactId);
    }
});

// --- Quick Add Customer Modal Script ---
let modalContactCount = 1;

function updateModalContactHeaders() {
    const cards = $('#modalContactsContainer .modal-contact-card');
    cards.each(function(idx, el) {
        $(el).find('.contact-label').text(`Người liên hệ #${idx + 1}`);
        if (cards.length > 1) {
            $(el).find('.btn-remove-modal-contact').removeClass('hidden');
        } else {
            $(el).find('.btn-remove-modal-contact').addClass('hidden');
        }
    });
}

$(document).on('click', '#modalAddContactBtn', function(e) {
    e.preventDefault();
    const newIndex = modalContactCount++;
    const contactCardHtml = `
        <div class="modal-contact-card p-3 border border-gray-200 rounded-lg bg-gray-50/50 mt-3" data-contact-index="${newIndex}">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-bold text-gray-500 uppercase contact-label">Người liên hệ #${newIndex + 1}</span>
                <div class="flex items-center gap-3">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="modal_primary_contact" value="${newIndex}" class="form-radio text-primary h-3.5 w-3.5">
                        <span class="ml-1.5 text-xs text-gray-600">Liên hệ chính</span>
                    </label>
                    <button type="button" class="btn-remove-modal-contact text-red-400 hover:text-red-600 transition-colors">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Họ & Tên <span class="text-red-500">*</span></label>
                    <input type="text" class="contact-name w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nhập họ tên...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                    <input type="text" class="contact-position w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="VD: Giám đốc...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                    <input type="text" class="contact-phone w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nhập SĐT...">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" class="contact-email w-full border border-gray-300 rounded-md px-2.5 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="email@example.com">
                </div>
            </div>
        </div>
    `;
    $('#modalContactsContainer').append(contactCardHtml);
    updateModalContactHeaders();
});

$(document).on('click', '.btn-remove-modal-contact', function(e) {
    e.preventDefault();
    const card = $(this).closest('.modal-contact-card');
    const wasChecked = card.find('input[name="modal_primary_contact"]').is(':checked');
    card.remove();
    if (wasChecked) {
        $('#modalContactsContainer .modal-contact-card').first().find('input[name="modal_primary_contact"]').prop('checked', true);
    }
    updateModalContactHeaders();
});

$(document).on('click', '#btn-modal-search-tax', async function(e) {
    e.preventDefault();
    const taxCode = $('#customerModalForm input[name="tax_code"]').val().trim();
    if (!taxCode) {
        Swal.fire({
            icon: 'warning',
            title: 'Thông báo',
            text: 'Vui lòng nhập mã số thuế trước khi tra cứu',
            confirmButtonColor: '#3085d6',
        });
        return;
    }

    const btn = $(this);
    const originalIcon = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin text-primary"></i>').prop('disabled', true);

    try {
        const response = await fetch(`https://api.vietqr.io/v2/business/${taxCode}`);
        const data = await response.json();
        
        if (data.code === '00' && data.data) {
            const biz = data.data;
            if (biz.name) {
                $('#customerModalForm input[name="name"]').val(biz.name);
            }
            if (biz.address) {
                $('#customerModalForm input[name="address"]').val(biz.address);
            }
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: 'Đã lấy được thông tin doanh nghiệp',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            throw new Error(data.desc || 'Không tìm thấy thông tin cho mã số thuế này');
        }
    } catch (error) {
        console.error('Tax lookup error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Lỗi tra cứu',
            text: error.message || 'Có lỗi xảy ra khi tra cứu mã số thuế',
            confirmButtonColor: '#d33',
        });
    } finally {
        btn.html(originalIcon).prop('disabled', false);
    }
});

$(document).on('click', '#btn-quick-add-customer', function(e) {
    e.preventDefault();
    $('select[name="customer_id"]').select2('close');
    $('#addCustomerModal').removeClass('hidden');
    const select2Search = $('.select2-search__field').val() || '';
    if (select2Search) {
        $('#customerModalForm input[name="name"]').val(select2Search);
    }
});

function resetCustomerModal() {
    $('#addCustomerModal').addClass('hidden');
    $('#customerModalForm')[0].reset();
    $('#modalErrors').addClass('hidden').html('');
    const container = $('#modalContactsContainer');
    container.find('.modal-contact-card').slice(1).remove();
    const firstCard = container.find('.modal-contact-card').first();
    firstCard.attr('data-contact-index', '0');
    firstCard.find('input[name="modal_primary_contact"]').val('0').prop('checked', true);
    firstCard.find('.contact-name').val('');
    firstCard.find('.contact-position').val('');
    firstCard.find('.contact-phone').val('');
    firstCard.find('.contact-email').val('');
    modalContactCount = 1;
    updateModalContactHeaders();
}

$('#closeCustomerModal, #cancelCustomerBtn, #modalOverlay').on('click', function() {
    resetCustomerModal();
});

$('#saveCustomerBtn').on('click', async function() {
    const form = $('#customerModalForm');
    const saveBtn = $(this);
    const errorsDiv = $('#modalErrors');

    const name = form.find('input[name="name"]').val().trim();
    const taxCode = form.find('input[name="tax_code"]').val().trim();
    const abvName = form.find('input[name="abv_name"]').val().trim();

    if (!name || !taxCode || !abvName) {
        errorsDiv.removeClass('hidden').html('Vui lòng điền đầy đủ các thông tin bắt buộc của doanh nghiệp (*).');
        return;
    }

    const contacts = [];
    let contactsValid = true;

    $('#modalContactsContainer .modal-contact-card').each(function() {
        const card = $(this);
        const cName = card.find('.contact-name').val().trim();
        const cPosition = card.find('.contact-position').val().trim();
        const cPhone = card.find('.contact-phone').val().trim();
        const cEmail = card.find('.contact-email').val().trim();
        const isPrimary = card.find('input[name="modal_primary_contact"]').is(':checked') ? 1 : 0;

        if (!cName || !cPosition || !cPhone || !cEmail) {
            contactsValid = false;
            return false;
        }

        contacts.push({
            name: cName,
            position: cPosition,
            phone: cPhone,
            email: cEmail,
            is_primary: isPrimary
        });
    });

    if (!contactsValid || contacts.length === 0) {
        errorsDiv.removeClass('hidden').html('Vui lòng điền đầy đủ các trường thông tin bắt buộc (*) của tất cả người liên hệ.');
        return;
    }

    saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1.5"></i> Đang lưu...');
    $('#cancelCustomerBtn').prop('disabled', true);
    errorsDiv.addClass('hidden').html('');

    try {
        const response = await fetch("{{ route('customers.store-ajax') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                name: name,
                tax_code: taxCode,
                abv_name: abvName,
                phone: form.find('input[name="phone"]').val().trim(),
                email: form.find('input[name="email"]').val().trim(),
                address: form.find('input[name="address"]').val().trim(),
                contacts: contacts
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            const customer = result.customer;
            const displayName = customer.name + (customer.code ? ' (' + customer.code + ')' : '');
            const newOption = new Option(displayName, customer.id, true, true);
            newOption.dataset.taxCode = customer.tax_code || '';
            newOption.dataset.abvName = customer.abv_name || '';
            
            $('select[name="customer_id"]').append(newOption).trigger('change');
            resetCustomerModal();
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: 'Đã thêm khách hàng mới thành công!',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            let errorMsg = result.message || 'Có lỗi xảy ra khi tạo khách hàng.';
            if (result.errors) {
                errorMsg = Object.values(result.errors).flat().join('<br>');
            }
            errorsDiv.removeClass('hidden').html(errorMsg);
        }
    } catch (error) {
        console.error('Error adding customer:', error);
        errorsDiv.removeClass('hidden').html('Có lỗi kết nối mạng. Vui lòng thử lại.');
    } finally {
        saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-1.5 mt-0.5"></i> Lưu');
        $('#cancelCustomerBtn').prop('disabled', false);
    }
});

// Single contact modal handlers
$(document).on('click', '#btn-quick-add-contact', function(e) {
    e.preventDefault();
    const customerId = $('select[name="customer_id"]').val();
    if (!customerId) {
        Swal.fire({
            icon: 'warning',
            title: 'Thông báo',
            text: 'Vui lòng chọn Khách hàng trước khi thêm người phụ trách mới',
        });
        return;
    }
    $('#addSingleContactModal').removeClass('hidden');
});

function resetSingleContactModal() {
    $('#addSingleContactModal').addClass('hidden');
    $('#singleContactModalForm')[0].reset();
    $('#singleContactModalErrors').addClass('hidden').html('');
}

$('#closeSingleContactModal, #cancelSingleContactBtn, #singleContactModalOverlay').on('click', function() {
    resetSingleContactModal();
});

$('#saveSingleContactBtn').on('click', async function() {
    const customerId = $('select[name="customer_id"]').val();
    if (!customerId) return;

    const form = $('#singleContactModalForm');
    const saveBtn = $(this);
    const errorsDiv = $('#singleContactModalErrors');

    const name = form.find('input[name="name"]').val().trim();
    const position = form.find('input[name="position"]').val().trim();
    const phone = form.find('input[name="phone"]').val().trim();
    const email = form.find('input[name="email"]').val().trim();

    if (!name || !position || !phone || !email) {
        errorsDiv.removeClass('hidden').html('Vui lòng điền đầy đủ các thông tin bắt buộc (*).');
        return;
    }

    saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1.5"></i> Đang lưu...');
    $('#cancelSingleContactBtn').prop('disabled', true);
    errorsDiv.addClass('hidden').html('');

    try {
        const response = await fetch(`/ajax/customers/${customerId}/contacts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                first_name: name,
                position: position,
                phone: phone,
                email: email
            })
        });

        const result = await response.json();

        if (response.ok && result.success) {
            resetSingleContactModal();
            loadCustomerContacts(customerId, result.contact.id);
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: 'Đã thêm người phụ trách mới thành công!',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            let errorMsg = result.message || 'Có lỗi xảy ra khi tạo người phụ trách.';
            if (result.errors) {
                errorMsg = Object.values(result.errors).flat().join('<br>');
            }
            errorsDiv.removeClass('hidden').html(errorMsg);
        }
    } catch (error) {
        console.error('Error adding contact:', error);
        errorsDiv.removeClass('hidden').html('Có lỗi kết nối mạng. Vui lòng thử lại.');
    } finally {
        saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-1.5 mt-0.5"></i> Lưu');
        $('#cancelSingleContactBtn').prop('disabled', false);
    }
});
</script>
@endpush

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Select2 Height & Styling Customization to match Stock Out Tailwind Inputs */
.select2-container .select2-selection--single {
    height: 38px !important;
    border-color: #d1d5db !important;
    border-radius: 0.5rem !important;
    display: flex !important;
    align-items: center !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
    top: 1px !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
    color: #374151 !important;
    font-size: 0.875rem !important;
    padding-left: 0.75rem !important;
}
.searchable-select {
    position: relative;
}
.searchable-dropdown {
    top: 100%;
    left: 0;
    right: 0;
}
.searchable-option.highlighted {
    background-color: #dbeafe;
}
</style>
@endpush
@endsection
