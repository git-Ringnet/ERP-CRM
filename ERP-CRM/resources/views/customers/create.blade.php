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

    <form action="{{ route('customers.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Thông tin cơ bản -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800">
                            <i class="fas fa-user mr-2 text-primary"></i>Thông tin cơ bản
                        </h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Mã khách hàng <span class="text-red-500">*</span></label>
                                <input type="text" name="code" id="code" value="{{ old('code', $nextCode) }}" required placeholder="VD: KH0001"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('code') border-red-500 @enderror">
                                @error('code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tên khách hàng <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Nhập tên khách hàng"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror">
                                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="email@example.com"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('email') border-red-500 @enderror">
                                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Điện thoại <span class="text-red-500">*</span></label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required placeholder="0123456789"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('phone') border-red-500 @enderror">
                                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Người liên hệ</label>
                                <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person') }}" placeholder="Tên người liên hệ"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="tax_code" class="block text-sm font-medium text-gray-700 mb-1">Mã số thuế</label>
                                <div class="relative">
                                    <input type="text" name="tax_code" id="tax_code" value="{{ old('tax_code') }}" placeholder="Nhập MST để tìm"
                                           class="w-full px-3 py-1.5 pr-10 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                                    <button type="button" id="btn-search-tax" 
                                            class="absolute right-0 top-0 h-full px-3 text-gray-400 hover:text-primary transition-colors focus:outline-none"
                                            title="Tìm kiếm thông tin từ mã số thuế">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
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

                <!-- Ghi chú -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú</h2>
                    </div>
                    <div class="p-4">
                        <textarea name="note" id="note" rows="3" placeholder="Nhập ghi chú nếu có..."
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
                    <div class="p-4 space-y-3">
                        <div>
                            <label for="debt_limit" class="block text-sm font-medium text-gray-700 mb-1">Hạn mức nợ (VNĐ)</label>
                            <input type="text" name="debt_limit" id="debt_limit" value="{{ old('debt_limit', 0) }}"
                                   class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="debt_days" class="block text-sm font-medium text-gray-700 mb-1">Số ngày nợ cho phép</label>
                            <input type="number" name="debt_days" id="debt_days" value="{{ old('debt_days', 0) }}" min="0"
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
        const debtLimitInput = document.getElementById('debt_limit');
        if (!debtLimitInput) return;
        
        const form = debtLimitInput.closest('form');

        // Initial format if value exists
        if (debtLimitInput.value) {
            formatDebtLimit(debtLimitInput);
        }

        // Format on input
        debtLimitInput.addEventListener('input', function() {
            formatDebtLimit(this);
        });

        // Strip commas before form submisssion
        if (form) {
            form.addEventListener('submit', function() {
                let val = debtLimitInput.value.replace(/,/g, '');
                debtLimitInput.value = val === '' ? '0' : val;
            });
        }

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
