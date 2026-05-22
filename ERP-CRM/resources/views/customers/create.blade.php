@extends('layouts.app')

@section('title', 'Thêm khách hàng')
@section('page-title', 'Thêm khách hàng mới')

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('customers.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <form action="{{ route('customers.store') }}" method="POST" x-data="{ 
        contacts: [{ name: '', first_name: '', last_name: '', title: '', position: '', phone: '', email: '', note: '', is_primary: true }],
        addContact() {
            this.contacts.push({ name: '', first_name: '', last_name: '', title: '', position: '', phone: '', email: '', note: '', is_primary: false });
        },
        removeContact(index) {
            if (this.contacts.length > 1) {
                this.contacts.splice(index, 1);
            }
        },
        setPrimary(index) {
            this.contacts.forEach((c, i) => c.is_primary = (i === index));
        },
        updateFullName(index) {
            this.contacts[index].name = (this.contacts[index].first_name + ' ' + (this.contacts[index].last_name || '')).trim();
        },
        milestones: {{ json_encode([['label' => 'Cọc', 'percent' => 30, 'days' => 5], ['label' => 'Thanh toán đợt 1', 'percent' => 70, 'days' => 30]]) }},
        addMilestone() {
            this.milestones.push({ label: '', percent: 0, days: 0 });
        },
        removeMilestone(index) {
            if (this.milestones.length > 0) {
                this.milestones.splice(index, 1);
            }
        },
        debtLimitType: '{{ old('debt_limit_type', 'amount') }}',
        errors: {{ json_encode($errors->toArray()) }}
    }">
        @csrf
        <div class="">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Thông tin cơ bản -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800">
                            <i class="fas fa-building mr-2 text-primary"></i>Thông tin doanh nghiệp
                        </h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-1">
                                <label for="tax_code" class="block text-sm font-medium text-gray-700 mb-1">Mã số thuế <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="text" name="tax_code" id="tax_code" value="{{ old('tax_code') }}" required placeholder="Nhập MST để tìm"
                                           class="w-full px-3 py-1.5 pr-10 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('tax_code') border-red-500 @enderror">
                                    <button type="button" id="btn-search-tax" 
                                            class="absolute right-0 top-0 h-full px-3 text-gray-400 hover:text-primary transition-colors focus:outline-none"
                                            title="Tìm kiếm thông tin từ mã số thuế">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                @error('tax_code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div class="md:col-span-1">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tên khách hàng (Công ty) <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Nhập tên khách hàng"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror">
                                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div class="md:col-span-1">
                                <label for="name_en" class="block text-sm font-medium text-gray-700 mb-1">Tên tiếng Anh</label>
                                <input type="text" name="name_en" id="name_en" value="{{ old('name_en') }}" placeholder="English name"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('name_en') border-red-500 @enderror">
                                @error('name_en')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div class="md:col-span-1">
                                <label for="abv_name" class="block text-sm font-medium text-gray-700 mb-1">Tên viết tắt (Abv Name) <span class="text-red-500">*</span></label>
                                <input type="text" name="abv_name" id="abv_name" value="{{ old('abv_name') }}" required placeholder="VD: ADG, IIJ..."
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('abv_name') border-red-500 @enderror">
                                @error('abv_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email công ty</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="email@example.com"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('email') border-red-500 @enderror">
                                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Điện thoại công ty</label>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" placeholder="0123456789" pattern="[0-9]+" title="Chỉ được nhập số"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('phone') border-red-500 @enderror">
                                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ</label>
                                <input type="text" name="address" id="address" value="{{ old('address') }}" placeholder="Nhập địa chỉ"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="md:col-span-2">
                                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                <input type="url" name="website" id="website" value="{{ old('website') }}" placeholder="https://example.com"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Người liên hệ (Folder style) -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <h2 class="text-base font-semibold text-gray-800">
                            <i class="fas fa-users mr-2 text-primary"></i>Danh sách người liên hệ
                        </h2>
                        <button type="button" @click="addContact()" class="px-3 py-1 bg-primary text-white rounded-md hover:bg-primary-dark text-xs transition-colors">
                            <i class="fas fa-plus mr-1"></i>Thêm người liên hệ
                        </button>
                    </div>
                    <div class="p-4 space-y-4">
                        <template x-for="(contact, index) in contacts" :key="index">
                            <div class="p-4 border border-gray-200 rounded-lg relative bg-gray-50/50">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-sm font-bold text-gray-500 uppercase" x-text="`Người liên hệ #${index + 1}`"></span>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" :name="`primary_contact_indicator`" :checked="contact.is_primary" @change="setPrimary(index)" class="form-radio text-primary h-4 w-4">
                                            <span class="ml-2 text-xs text-gray-600">Liên hệ chính</span>
                                            <input type="hidden" :name="`contacts[${index}][is_primary]`" :value="contact.is_primary ? 1 : 0">
                                        </label>
                                        <button type="button" @click="removeContact(index)" x-show="contacts.length > 1" class="text-red-500 hover:text-red-700 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Xưng hô (Mr/Ms/Mrs)</label>
                                        <input type="text" :name="`contacts[${index}][title]`" x-model="contact.title" placeholder="Mr, Ms..."
                                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Tên <span class="text-red-500">*</span></label>
                                            <input type="text" :name="`contacts[${index}][first_name]`" x-model="contact.first_name" @input="updateFullName(index)" required placeholder="Tên"
                                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Họ</label>
                                            <input type="text" :name="`contacts[${index}][last_name]`" x-model="contact.last_name" @input="updateFullName(index)" placeholder="Họ và tên đệm"
                                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                        </div>
                                        <input type="hidden" :name="`contacts[${index}][name]`" x-model="contact.name">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                                        <input type="text" :name="`contacts[${index}][position]`" x-model="contact.position" required placeholder="VD: Giám đốc, Kế toán..."
                                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                        <template x-if="errors[`contacts.${index}.position`]">
                                            <p class="mt-1 text-[10px] text-red-500" x-text="errors[`contacts.${index}.position`][0]"></p>
                                        </template>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                                        <input type="tel" :name="`contacts[${index}][phone]`" x-model="contact.phone" required placeholder="Nhập SĐT"
                                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                        <input type="email" :name="`contacts[${index}][email]`" x-model="contact.email" required placeholder="example@gmail.com"
                                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                        <template x-if="errors[`contacts.${index}.email`]">
                                            <p class="mt-1 text-[10px] text-red-500" x-text="errors[`contacts.${index}.email`][0]"></p>
                                        </template>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú</label>
                                        <input type="text" :name="`contacts[${index}][note]`" x-model="contact.note" placeholder="Thông tin thêm..."
                                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                    </div>
                </div>

                <!-- Điều khoản thanh toán mặc định -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <h2 class="text-base font-semibold text-gray-800">
                            <i class="fas fa-hand-holding-usd mr-2 text-primary"></i>Điều khoản thanh toán mặc định
                        </h2>
                        <button type="button" @click="addMilestone()" class="px-3 py-1 bg-primary text-white rounded-md hover:bg-primary-dark text-xs transition-colors">
                            <i class="fas fa-plus mr-1"></i>Thêm đợt
                        </button>
                    </div>
                    <div class="p-4">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2">Đợt thanh toán</th>
                                        <th class="px-3 py-2 w-24">%</th>
                                        <th class="px-3 py-2 w-32">Thời hạn (ngày)</th>
                                        <th class="px-3 py-2 w-16 text-center">Xóa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(ms, index) in milestones" :key="index">
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="px-3 py-2">
                                                <input type="text" :name="`payment_terms[${index}][label]`" x-model="ms.label" required
                                                       placeholder="VD: Cọc, Đợt 1..."
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-primary focus:border-primary">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" :name="`payment_terms[${index}][percent]`" x-model="ms.percent" min="0" max="100" required
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-primary focus:border-primary">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" :name="`payment_terms[${index}][days]`" x-model="ms.days" min="0" required
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-primary focus:border-primary">
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" @click="removeMilestone(index)" class="text-red-500 hover:text-red-700">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 font-semibold text-gray-900">
                                        <td class="px-3 py-2">Tổng cộng</td>
                                        <td class="px-3 py-2" x-text="milestones.reduce((acc, ms) => acc + parseInt(ms.percent || 0), 0) + '%'"></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 italic">
                            * Các thiết lập này sẽ tự động gợi ý khi tạo đơn hàng mới cho khách hàng này.
                        </p>
                    </div>
                </div>

                <!-- Ghi chú chung -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú chung</h2>
                    </div>
                    <div class="p-4">
                        <textarea name="note" id="note" rows="3" placeholder="Nhập ghi chú về doanh nghiệp nếu có..."
                                  class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-4">
                <!-- Phân loại -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-tag mr-2 text-primary"></i>Phân loại</h2>
                    </div>
                    <div class="p-4">
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Loại khách hàng <span class="text-red-500">*</span></label>
                        <select name="type" id="type" required class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="normal" {{ old('type', 'normal') == 'normal' ? 'selected' : '' }}>Thường</option>
                            <option value="vip" {{ old('type') == 'vip' ? 'selected' : '' }}>VIP</option>
                        </select>
                    </div>
                </div>

                <!-- Công nợ -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-credit-card mr-2 text-primary"></i>Công nợ</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loại hạn mức</label>
                            <div class="flex gap-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="debt_limit_type" value="amount" x-model="debtLimitType" class="form-radio text-primary">
                                    <span class="ml-2 text-sm text-gray-600">Số tiền (VNĐ)</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="debt_limit_type" value="percent" x-model="debtLimitType" class="form-radio text-primary">
                                    <span class="ml-2 text-sm text-gray-600">Phần trăm (%)</span>
                                </label>
                            </div>
                        </div>

                        <div x-show="debtLimitType === 'amount'">
                            <label for="debt_limit_val_amount" class="block text-sm font-medium text-gray-700 mb-1">Hạn mức nợ (VNĐ)</label>
                            <input type="text" name="debt_limit_value_amount" id="debt_limit_val_amount" value="{{ old('debt_limit_value', 0) }}"
                                   placeholder="VD: 50,000,000"
                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary debt-format-input">
                        </div>

                        <div x-show="debtLimitType === 'percent'">
                            <label for="debt_limit_val_percent" class="block text-sm font-medium text-gray-700 mb-1">Hạn mức nợ (%)</label>
                            <div class="relative">
                                <input type="number" name="debt_limit_value_percent" id="debt_limit_val_percent" step="0.1" min="0" max="1000"
                                       value="{{ old('debt_limit_value') }}"
                                       placeholder="VD: 10"
                                       class="w-full px-3 py-1.5 pr-8 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                            </div>
                            <p class="mt-1 text-[10px] text-gray-500 italic">Tính trên tổng doanh số đã duyệt.</p>
                        </div>


                        <div>
                            <label for="debt_days" class="block text-sm font-medium text-gray-700 mb-1">Số ngày nợ cho phép</label>
                            <input type="number" name="debt_days" id="debt_days" value="{{ old('debt_days', 0) }}" min="0"
                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="am" class="block text-sm font-medium text-gray-700 mb-1">Account Manager (AM)</label>
                            <input type="text" name="am" id="am" value="{{ old('am') }}" placeholder="Tên AM"
                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                        <i class="fas fa-save mr-2"></i>Lưu khách hàng
                    </button>
                    <a href="{{ route('customers.index') }}" class="mt-2 w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                        Hủy bỏ
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initial format if value exists
        const debtLimitValAmount = document.getElementById('debt_limit_val_amount');
        if (debtLimitValAmount && debtLimitValAmount.value) {
            formatDebtLimit(debtLimitValAmount);
        }

        // Format on input
        document.querySelectorAll('.debt-format-input').forEach(el => {
            el.addEventListener('input', function() {
                formatDebtLimit(this);
            });
        });

        function formatDebtLimit(input) {
            let value = input.value.replace(/\D/g, '');
            if (value === '') {
                input.value = '';
                return;
            }
            input.value = new Intl.NumberFormat('en-US').format(parseInt(value));
        }

        // Tax ID Search Logic
        const btnSearchTax = document.getElementById('btn-search-tax');
        if (btnSearchTax) {
            btnSearchTax.addEventListener('click', async function() {
                const taxCode = document.getElementById('tax_code').value.trim();
                if (!taxCode) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thông báo',
                        text: 'Vui lòng nhập mã số thuế trước khi tìm kiếm',
                        confirmButtonColor: '#3085d6',
                    });
                    return;
                }

                const btn = this;
                const originalIcon = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;

                try {
                    const response = await axios.get(`https://api.vietqr.io/v2/business/${taxCode}`);
                    
                    if (response.data.code === '00' && response.data.data) {
                        const data = response.data.data;
                        
                        // Populate fields
                        if (data.name) {
                            document.getElementById('name').value = data.name;
                        }
                        if (data.address) {
                            document.getElementById('address').value = data.address;
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: 'Đã lấy được thông tin doanh nghiệp',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(response.data.desc || 'Không tìm thấy thông tin cho mã số thuế này');
                    }
                } catch (error) {
                    console.error('Tax lookup error:', error);
                    let errorMessage = 'Có lỗi xảy ra khi tra cứu mã số thuế';
                    if (error.response && error.response.data && error.response.data.desc) {
                        errorMessage = error.response.data.desc;
                    } else if (error.message) {
                        errorMessage = error.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi tra cứu',
                        text: errorMessage,
                        confirmButtonColor: '#d33',
                    });
                } finally {
                    btn.innerHTML = originalIcon;
                    btn.disabled = false;
                }
            });
        }
    });
</script>
@endpush
