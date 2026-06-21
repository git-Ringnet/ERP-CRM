@extends('layouts.app')

@section('title', 'Cấu hình Biểu mẫu PO Công ty')
@section('page-title', 'Cấu hình Biểu mẫu PO Công ty')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('settings.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại Cài đặt
        </a>
    </div>

    @if ($errors->any())
    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm text-sm text-red-700">
        <h4 class="font-bold mb-1">Có lỗi xảy ra:</h4>
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('settings.po-company.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- 1. HEADER LOGO & GENERAL INFO --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h3 class="text-base font-bold text-gray-900 flex items-center">
                    <i class="fas fa-image mr-2 text-primary"></i> Logo & Thông tin liên hệ (Form SALE CONTRACT)
                </h3>
                <p class="text-xs text-gray-500 mt-1">Logo và địa chỉ dùng để hiển thị phần Header của biểu mẫu Sale Contract (Generic)</p>
            </div>
            <div class="p-6 space-y-6">
                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        Logo biểu mẫu (SALE CONTRACT)
                    </label>
                    <div class="flex items-start gap-6">
                        <div class="flex-shrink-0">
                            @if($config->header_logo_path && file_exists(public_path($config->header_logo_path)))
                            <img id="logo-preview"
                                 src="{{ asset($config->header_logo_path) }}"
                                 alt="Logo PO"
                                 class="h-20 object-contain border border-gray-200 rounded-lg p-2 bg-white shadow-sm">
                            @else
                            <div id="logo-placeholder"
                                 class="w-36 h-20 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center text-gray-400 text-xs bg-white">
                                <i class="fas fa-image text-2xl mb-1"></i>
                                <span>Chưa có logo</span>
                            </div>
                            <img id="logo-preview"
                                 src=""
                                 alt="Logo preview"
                                 class="h-20 object-contain border border-gray-200 rounded-lg p-2 bg-white shadow-sm hidden">
                            @endif
                        </div>
                        <div class="flex-1">
                            <input type="file"
                                   id="logo-input"
                                   name="header_logo"
                                   accept="image/png,image/jpeg,image/jpg,image/webp,image/gif,image/svg+xml"
                                   class="block w-full text-sm text-gray-600
                                          file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0
                                          file:text-sm file:font-semibold file:bg-primary file:text-white
                                          hover:file:bg-primary-dark cursor-pointer mb-2 transition-all">
                            <p class="text-xs text-gray-500">Định dạng PNG, JPG. Tối đa 2MB. Logo hiển thị ở góc trên bên trái form chung.</p>
                            <p id="logo-filename" class="text-xs text-purple-600 font-medium mt-1 hidden">
                                <i class="fas fa-check-circle mr-1"></i><span></span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Header Banner Image (full-width top header) --}}
                <div class="p-4 bg-blue-50 rounded-xl border border-blue-200">
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-panorama mr-1 text-blue-600"></i> Ảnh Header (Banner trên cùng)
                    </label>
                    <p class="text-xs text-gray-500 mb-3">Đây là ảnh banner hiển thị ở phần đầu trang biểu mẫu Sale Contract (gồm logo + thông tin công ty trong 1 ảnh). Khi upload ảnh này, phần header sẽ hiển thị ảnh thay vì text.</p>
                    <div class="flex items-start gap-6">
                        <div class="flex-shrink-0">
                            @if($config->header_banner_path && file_exists(public_path($config->header_banner_path)))
                            <img id="banner-preview"
                                 src="{{ asset($config->header_banner_path) }}"
                                 alt="Banner PO"
                                 class="h-20 object-contain border border-blue-200 rounded-lg p-2 bg-white shadow-sm">
                            @else
                            <div id="banner-placeholder"
                                 class="w-48 h-20 border-2 border-dashed border-blue-300 rounded-lg flex flex-col items-center justify-center text-blue-400 text-xs bg-white">
                                <i class="fas fa-panorama text-2xl mb-1"></i>
                                <span>Chưa có banner</span>
                            </div>
                            <img id="banner-preview"
                                 src=""
                                 alt="Banner preview"
                                 class="h-20 object-contain border border-blue-200 rounded-lg p-2 bg-white shadow-sm hidden">
                            @endif
                        </div>
                        <div class="flex-1">
                            <input type="file"
                                   id="banner-input"
                                   name="header_banner"
                                   accept="image/png,image/jpeg,image/jpg,image/webp,image/gif,image/svg+xml"
                                   class="block w-full text-sm text-gray-600
                                          file:mr-3 file:py-1.5 file:px-4 file:rounded-lg file:border-0
                                          file:text-sm file:font-semibold file:bg-blue-600 file:text-white
                                          hover:file:bg-blue-700 cursor-pointer mb-2 transition-all">
                            <p class="text-xs text-gray-500">Định dạng PNG, JPG. Tối đa 4MB. Ảnh này sẽ chiếm toàn bộ phần header trên cùng của biểu mẫu Sale Contract.</p>
                            <p id="banner-filename" class="text-xs text-blue-600 font-medium mt-1 hidden">
                                <i class="fas fa-check-circle mr-1"></i><span></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên đầy đủ công ty (Header)</label>
                        <input type="text" name="company_full_name" value="{{ old('company_full_name', $config->company_full_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ HCM (Header)</label>
                        <input type="text" name="hcmc_address" value="{{ old('hcmc_address', $config->hcmc_address) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ Hà Nội (Header)</label>
                        <input type="text" name="hanoi_address" value="{{ old('hanoi_address', $config->hanoi_address) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Website</label>
                        <input type="text" name="website" value="{{ old('website', $config->website) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $config->email) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số điện thoại liên hệ</label>
                        <input type="text" name="phone" value="{{ old('phone', $config->phone) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. BUYER (TECH HORIZON CORP) --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h3 class="text-base font-bold text-gray-900 flex items-center">
                    <i class="fas fa-shopping-cart mr-2 text-primary"></i> Thông tin Người mua (THE BUYER)
                </h3>
                <p class="text-xs text-gray-500 mt-1">Thông tin chi tiết hiển thị ở cột BUYER trong biểu mẫu PO</p>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên Buyer</label>
                        <input type="text" name="buyer_name" value="{{ old('buyer_name', $config->buyer_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ (Dòng 1)</label>
                        <input type="text" name="buyer_address_line1" value="{{ old('buyer_address_line1', $config->buyer_address_line1) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ (Dòng 2)</label>
                        <input type="text" name="buyer_address_line2" value="{{ old('buyer_address_line2', $config->buyer_address_line2) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Điện thoại</label>
                        <input type="text" name="buyer_tel" value="{{ old('buyer_tel', $config->buyer_tel) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Fax</label>
                        <input type="text" name="buyer_fax" value="{{ old('buyer_fax', $config->buyer_fax) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Người liên hệ (Contact)</label>
                        <input type="text" name="buyer_contact" value="{{ old('buyer_contact', $config->buyer_contact) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-5 mt-5">
                    <h4 class="text-sm font-bold text-gray-800 mb-3"><i class="fas fa-university mr-1 text-gray-600"></i> Thông tin Ngân hàng của Buyer</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tên tài khoản / Số tài khoản</label>
                            <input type="text" name="buyer_bank_account" value="{{ old('buyer_bank_account', $config->buyer_bank_account) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tên ngân hàng</label>
                            <input type="text" name="buyer_bank_name" value="{{ old('buyer_bank_name', $config->buyer_bank_name) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ ngân hàng (Dòng 1)</label>
                            <input type="text" name="buyer_bank_address_line1" value="{{ old('buyer_bank_address_line1', $config->buyer_bank_address_line1) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ ngân hàng (Dòng 2)</label>
                            <input type="text" name="buyer_bank_address_line2" value="{{ old('buyer_bank_address_line2', $config->buyer_bank_address_line2) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">SWIFT Code</label>
                            <input type="text" name="buyer_swift_code" value="{{ old('buyer_swift_code', $config->buyer_swift_code) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. SHIP TO & INVOICE TO --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- SHIP TO --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <h3 class="text-base font-bold text-gray-900 flex items-center">
                        <i class="fas fa-truck mr-2 text-primary"></i> Thông tin Giao nhận (SHIP TO)
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên đơn vị</label>
                        <input type="text" name="ship_to_name" value="{{ old('ship_to_name', $config->ship_to_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ giao hàng (Dòng 1)</label>
                        <input type="text" name="ship_to_address_line1" value="{{ old('ship_to_address_line1', $config->ship_to_address_line1) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ giao hàng (Dòng 2)</label>
                        <input type="text" name="ship_to_address_line2" value="{{ old('ship_to_address_line2', $config->ship_to_address_line2) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Người nhận (Attn)</label>
                        <input type="text" name="ship_to_attn" value="{{ old('ship_to_attn', $config->ship_to_attn) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- INVOICE TO --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <h3 class="text-base font-bold text-gray-900 flex items-center">
                        <i class="fas fa-file-invoice-dollar mr-2 text-primary"></i> Thông tin Xuất hóa đơn (INVOICE TO)
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên đơn vị xuất HĐ</label>
                        <input type="text" name="invoice_to_name" value="{{ old('invoice_to_name', $config->invoice_to_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ xuất hóa đơn (Dòng 1)</label>
                        <input type="text" name="invoice_to_address_line1" value="{{ old('invoice_to_address_line1', $config->invoice_to_address_line1) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ xuất hóa đơn (Dòng 2)</label>
                        <input type="text" name="invoice_to_address_line2" value="{{ old('invoice_to_address_line2', $config->invoice_to_address_line2) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Thông tin bổ sung (Attn / MST...)</label>
                        <input type="text" name="invoice_to_attn" value="{{ old('invoice_to_attn', $config->invoice_to_attn) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. SIGNATURE --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h3 class="text-base font-bold text-gray-900 flex items-center">
                    <i class="fas fa-signature mr-2 text-primary"></i> Thông tin Chữ ký người phê duyệt (Đại diện bên Mua)
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên người ký</label>
                        <input type="text" name="signer_name" value="{{ old('signer_name', $config->signer_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                               placeholder="VD: TRAN QUOC TRUNG">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Chức danh</label>
                        <input type="text" name="signer_title" value="{{ old('signer_title', $config->signer_title) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                               placeholder="VD: Product Director">
                    </div>
                </div>
            </div>
        </div>

        {{-- SUBMIT BUTTONS --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('settings.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                Hủy bỏ
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                <i class="fas fa-save mr-2"></i> Lưu cài đặt PO công ty
            </button>
        </div>
    </form>
</div>

<script>
    // Live logo preview script
    document.getElementById('logo-input').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const preview   = document.getElementById('logo-preview');
        const placeholder = document.getElementById('logo-placeholder');
        const fileLabel = document.getElementById('logo-filename');

        const reader = new FileReader();
        reader.onload = function (ev) {
            if (preview) {
                preview.src = ev.target.result;
                preview.classList.remove('hidden');
            }
            if (placeholder) placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);

        // Show filename
        if (fileLabel) {
            fileLabel.classList.remove('hidden');
            fileLabel.querySelector('span').textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
        }
    });

    // Live banner preview script
    document.getElementById('banner-input').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const preview    = document.getElementById('banner-preview');
        const placeholder = document.getElementById('banner-placeholder');
        const fileLabel  = document.getElementById('banner-filename');

        const reader = new FileReader();
        reader.onload = function (ev) {
            if (preview) {
                preview.src = ev.target.result;
                preview.classList.remove('hidden');
            }
            if (placeholder) placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);

        // Show filename
        if (fileLabel) {
            fileLabel.classList.remove('hidden');
            fileLabel.querySelector('span').textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
        }
    });
</script>
@endsection
