@extends('layouts.app')

@section('title', 'Đăng ký dự án mới')
@section('page-title', 'Đăng ký dự án mới')

@section('content')
    <div class="max-w-8xl">
        <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="marketing_event_id" value="{{ old('marketing_event_id', $preFill['marketing_event_id'] ?? '') }}">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Cột trái: 4 Sections -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Section A: Distributor Information -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                            <i class="fas fa-building mr-2 text-blue-600"></i>Distributor Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Vendor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
                                <select name="vendor_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn Vendor --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('vendor_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Distributor (Read-only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Distributor</label>
                                <input type="text" value="Tech Horizon Corporation" disabled
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-600 cursor-not-allowed">
                            </div>
                            <!-- Distributor AM (Auto from login) -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Distributor AM</label>
                                <input type="text" name="distributor_am" value="{{ old('distributor_am', $distributorAm) }}" readonly
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-600 cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    <!-- Section B: End-User Information -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                            <i class="fas fa-user-tie mr-2 text-green-600"></i>End-User Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- EU Vietnamese name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tên tiếng Việt <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="eu_name_vi" value="{{ old('eu_name_vi') }}" required
                                    placeholder="Tên tiếng Việt của khách hàng"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_name_vi') border-red-500 @enderror">
                                @error('eu_name_vi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <!-- EU English name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tên tiếng Anh <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="eu_name_en" value="{{ old('eu_name_en') }}" required
                                    placeholder="English name"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_name_en') border-red-500 @enderror">
                                @error('eu_name_en') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <!-- EU Abbreviated name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tên viết tắt</label>
                                <input type="text" name="eu_name_abbr" value="{{ old('eu_name_abbr') }}"
                                    placeholder="VD: FPT, VNG..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <!-- Website/Tax Code -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    MST / Website <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="eu_tax_code" id="eu_tax_code" value="{{ old('eu_tax_code') }}" required
                                    placeholder="Mã số thuế hoặc website"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_tax_code') border-red-500 @enderror">
                                @error('eu_tax_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                <!-- Duplicate warning -->
                                <div id="eu_tax_warning" class="hidden mt-2 bg-yellow-50 border border-yellow-300 rounded-lg p-3">
                                    <div class="flex items-start gap-2">
                                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
                                        <div class="text-sm">
                                            <p class="font-semibold text-yellow-800">MST này đã tồn tại trong hệ thống!</p>
                                            <p class="text-yellow-700 mt-1" id="eu_tax_warning_detail"></p>
                                            <p class="text-yellow-600 text-xs mt-1">Nếu tiếp tục, thông tin khách hàng sẽ được cập nhật theo dữ liệu mới.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Địa chỉ <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="address" value="{{ old('address') }}" required
                                    placeholder="Địa chỉ End-User"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('address') border-red-500 @enderror">
                                @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <!-- Province -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tỉnh / Thành phố <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="eu_province" value="{{ old('eu_province') }}" required
                                    placeholder="VD: Hồ Chí Minh, Đà Nẵng..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_province') border-red-500 @enderror">
                                @error('eu_province') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <!-- Industry -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ngành nghề</label>
                                <input type="text" name="eu_industry" value="{{ old('eu_industry') }}"
                                    placeholder="VD: CNTT, Sản xuất, Tài chính..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                        </div>
                    </div>

                    <!-- Section C: Collaboration -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                            <i class="fas fa-handshake mr-2 text-purple-600"></i>Collaboration
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Collaborate Type -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Loại hợp tác <span class="text-red-500">*</span>
                                </label>
                                <select name="collaborate_type" id="collaborate_type" required
                                    onchange="toggleCollaborateType()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn loại hợp tác --</option>
                                    <option value="partner" {{ old('collaborate_type') == 'partner' ? 'selected' : '' }}>Partner</option>
                                    <option value="end_user" {{ old('collaborate_type') == 'end_user' ? 'selected' : '' }}>End-user</option>
                                </select>
                            </div>

                            <!-- Partner mode: Toggle between "Chọn từ danh sách" and "Tạo mới" -->
                            <div id="partner_mode_toggle" class="md:col-span-2 {{ old('collaborate_type') == 'partner' ? '' : 'hidden' }}">
                                <div class="flex items-center gap-4 mb-3">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="partner_input_mode" value="existing" checked
                                            onchange="togglePartnerInputMode('existing')"
                                            class="text-primary focus:ring-primary">
                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                            <i class="fas fa-search mr-1 text-blue-500"></i>Chọn từ danh sách khách hàng
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="partner_input_mode" value="new"
                                            onchange="togglePartnerInputMode('new')"
                                            class="text-primary focus:ring-primary">
                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                            <i class="fas fa-plus mr-1 text-green-500"></i>Tạo mới
                                        </span>
                                    </label>
                                </div>

                                <!-- Customer dropdown (for existing) -->
                                <div id="partner_existing_select">
                                    <select name="collaborate_customer_id" id="collaborate_customer_id"
                                        onchange="fillPartnerData()"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">-- Tìm khách hàng (theo tên, MST) --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                data-name="{{ $customer->name }}"
                                                data-tax="{{ $customer->tax_code }}"
                                                data-phone="{{ $customer->phone }}"
                                                data-email="{{ $customer->email }}"
                                                {{ old('collaborate_customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }} {{ $customer->tax_code ? '(MST: '.$customer->tax_code.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>Chọn khách hàng để tự động điền thông tin bên dưới
                                    </p>
                                </div>
                            </div>
                            <!-- End-user notice (shown when type=end_user) -->
                            <div id="enduser_notice" class="md:col-span-2 hidden">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                                        <div>
                                            <p class="text-sm font-medium text-blue-800">Chế độ End-user (trực tiếp)</p>
                                            <p class="text-xs text-blue-600 mt-1">
                                                Thông tin End-User ở Section B sẽ được sử dụng làm đối tác hợp tác.
                                                Khách hàng sẽ tự động được tạo/cập nhật trong hệ thống từ MST đã nhập.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Partner fields (hidden when type=end_user) -->
                            <div id="collab_company_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tên công ty <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="collaborate_company" id="collaborate_company"
                                    value="{{ old('collaborate_company') }}"
                                    placeholder="Tên công ty hợp tác"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_company') border-red-500 @enderror">
                                @error('collaborate_company') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_tax_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Mã số thuế <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="collaborate_tax_code" id="collaborate_tax_code"
                                    value="{{ old('collaborate_tax_code') }}"
                                    placeholder="MST công ty hợp tác"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_tax_code') border-red-500 @enderror">
                                @error('collaborate_tax_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_name_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tên người liên hệ (PIC) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="collaborate_pic_name" id="collaborate_pic_name"
                                    value="{{ old('collaborate_pic_name') }}"
                                    placeholder="Họ và tên PIC"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_pic_name') border-red-500 @enderror">
                                @error('collaborate_pic_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_title_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Chức danh <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="collaborate_pic_title" id="collaborate_pic_title"
                                    value="{{ old('collaborate_pic_title') }}"
                                    placeholder="VD: Manager, Director..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_pic_title') border-red-500 @enderror">
                                @error('collaborate_pic_title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_phone_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Số điện thoại PIC <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="collaborate_pic_phone" id="collaborate_pic_phone"
                                    value="{{ old('collaborate_pic_phone') }}"
                                    placeholder="0901234567"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_pic_phone') border-red-500 @enderror">
                                @error('collaborate_pic_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_email_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email PIC</label>
                                <input type="email" name="collaborate_pic_email" id="collaborate_pic_email"
                                    value="{{ old('collaborate_pic_email') }}"
                                    placeholder="pic@company.com"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                        </div>
                    </div>

                    <!-- Section D: Project Information -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                            <i class="fas fa-project-diagram mr-2 text-orange-600"></i>Project Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Mã dự án -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Mã dự án <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="code" value="{{ old('code', $code) }}" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror">
                                @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <!-- Project name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tên dự án <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name', $preFill['name'] ?? '') }}" required
                                    placeholder="Tên dự án"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <!-- BOM -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    BOM (Bill of Materials) <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2">
                                    <input type="file" name="bom_file" accept=".xlsx,.xls,.pdf,.doc,.docx"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="text-xs text-gray-500">Upload file BOM (xlsx, pdf, doc) hoặc nhập danh sách bên dưới</p>
                                    <textarea name="bom_data" rows="3" placeholder="Nhập danh sách BOM nếu không upload file..."
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('bom_data') }}</textarea>
                                </div>
                            </div>
                            <!-- Deal Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deal Type</label>
                                <select name="deal_type"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn --</option>
                                    <option value="new_buy" {{ old('deal_type') == 'new_buy' ? 'selected' : '' }}>New Buy</option>
                                    <option value="trade_up" {{ old('deal_type') == 'trade_up' ? 'selected' : '' }}>Trade Up</option>
                                </select>
                            </div>
                            <!-- Net to Tech Horizon -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Net to Tech Horizon</label>
                                <div class="relative">
                                    <input type="text" id="net_display" oninput="formatCurrency(this, 'net_to_tech_horizon')"
                                        value="{{ old('net_to_tech_horizon') ? number_format(old('net_to_tech_horizon')) : '' }}"
                                        placeholder="0"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary pr-12">
                                    <span class="absolute right-3 top-2 text-gray-400 text-xs">VNĐ</span>
                                </div>
                                <input type="hidden" name="net_to_tech_horizon" id="net_to_tech_horizon" value="{{ old('net_to_tech_horizon', 0) }}">
                            </div>
                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả / Diễn giải</label>
                                <textarea name="description" rows="3" placeholder="Mô tả chi tiết về dự án..."
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description', $preFill['description'] ?? '') }}</textarea>
                            </div>
                            <!-- Note -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                                <textarea name="note" rows="2" placeholder="S/N: ..."
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Sidebar -->
                <div class="space-y-6">
                    <!-- Trạng thái -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-tasks mr-2 text-primary"></i>Trạng thái <span class="text-red-500">*</span>
                        </label>
                        <select name="status" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="planning" {{ old('status') == 'planning' ? 'selected' : '' }}>Lên kế hoạch</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="on_hold" {{ old('status') == 'on_hold' ? 'selected' : '' }}>Tạm dừng</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>

                    <!-- Estimated Close Date -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar-alt mr-2 text-primary"></i>Estimated Close Date <span class="text-red-500">*</span>
                        </h3>
                        <p class="text-xs text-gray-500 mb-3">Cộng thời gian dựa trên ngày tạo project để tính expired date.</p>
                        <div class="space-y-2">
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition-colors {{ old('estimated_close_months') == '3' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                <input type="radio" name="estimated_close_months" value="3" {{ old('estimated_close_months') == '3' ? 'checked' : '' }}
                                    class="text-primary focus:ring-primary" onchange="updateExpiredPreview(this)">
                                <span class="ml-3 text-sm font-medium">+3 tháng</span>
                                <span class="ml-auto text-xs text-gray-500" id="preview_3m">{{ now()->addMonths(3)->format('d/m/Y') }}</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition-colors {{ old('estimated_close_months') == '6' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                <input type="radio" name="estimated_close_months" value="6" {{ old('estimated_close_months') == '6' ? 'checked' : '' }}
                                    class="text-primary focus:ring-primary" onchange="updateExpiredPreview(this)">
                                <span class="ml-3 text-sm font-medium">+6 tháng</span>
                                <span class="ml-auto text-xs text-gray-500" id="preview_6m">{{ now()->addMonths(6)->format('d/m/Y') }}</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition-colors {{ old('estimated_close_months') == '9' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                <input type="radio" name="estimated_close_months" value="9" {{ old('estimated_close_months') == '9' ? 'checked' : '' }}
                                    class="text-primary focus:ring-primary" onchange="updateExpiredPreview(this)">
                                <span class="ml-3 text-sm font-medium">+9 tháng</span>
                                <span class="ml-auto text-xs text-gray-500" id="preview_9m">{{ now()->addMonths(9)->format('d/m/Y') }}</span>
                            </label>
                        </div>
                        @error('estimated_close_months') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                    </div>

                    <!-- Stage -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-layer-group mr-2 text-primary"></i>Stage
                        </label>
                        <input type="text" name="stage" value="{{ old('stage') }}" placeholder="Giai đoạn dự án"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <!-- Ngân sách (Budget) -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-money-bill-wave mr-2 text-primary"></i>Dự toán
                        </h3>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1 uppercase tracking-wider">Ngân sách dự toán (VNĐ)</label>
                            <div class="relative">
                                <input type="text" id="budget_display" oninput="formatCurrency(this, 'budget')"
                                    value="{{ number_format(old('budget', $preFill['budget'] ?? 0)) }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary pr-12">
                                <span class="absolute right-3 top-2 text-gray-400 text-xs">VNĐ</span>
                            </div>
                            <input type="hidden" name="budget" id="budget" value="{{ old('budget', $preFill['budget'] ?? 0) }}">
                        </div>
                    </div>

                    <!-- Hidden fields -->
                    <input type="hidden" name="customer_id" value="{{ old('customer_id', $preFill['customer_id'] ?? '') }}">
                    <input type="hidden" name="manager_id" value="{{ auth()->id() }}">

                    <!-- Actions -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <button type="submit"
                            class="w-full px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark transition-all font-semibold text-sm shadow-sm flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i>Đăng ký dự án
                        </button>
                        <a href="{{ route('projects.index') }}"
                            class="mt-3 w-full inline-block text-center px-4 py-2 text-gray-500 hover:text-gray-700 transition-colors text-sm font-medium">
                            Hủy bỏ
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        const collabFieldIds = [
            'collab_company_wrap', 'collab_tax_wrap',
            'collab_pic_name_wrap', 'collab_pic_title_wrap',
            'collab_pic_phone_wrap', 'collab_pic_email_wrap'
        ];

        function toggleCollaborateType() {
            const type = document.getElementById('collaborate_type').value;
            const partnerToggle = document.getElementById('partner_mode_toggle');
            const enduserNotice = document.getElementById('enduser_notice');

            if (type === 'partner') {
                // Show partner mode toggle + fields, hide end-user notice
                partnerToggle.classList.remove('hidden');
                enduserNotice.classList.add('hidden');
                showCollabFields();
                // Reset to "existing" mode
                const existingRadio = document.querySelector('input[name="partner_input_mode"][value="existing"]');
                if (existingRadio) existingRadio.checked = true;
                togglePartnerInputMode('existing');
            } else if (type === 'end_user') {
                // Hide partner toggle + partner fields, show end-user notice
                partnerToggle.classList.add('hidden');
                enduserNotice.classList.remove('hidden');
                hideCollabFields();
            } else {
                // Nothing selected - hide everything
                partnerToggle.classList.add('hidden');
                enduserNotice.classList.add('hidden');
                hideCollabFields();
            }
        }

        function togglePartnerInputMode(mode) {
            const existingSelect = document.getElementById('partner_existing_select');

            if (mode === 'existing') {
                // Show customer dropdown
                existingSelect.classList.remove('hidden');
                // If a customer was already selected, fill data
                fillPartnerData();
            } else {
                // Hide dropdown, clear customer_id, enable manual input
                existingSelect.classList.add('hidden');
                document.getElementById('collaborate_customer_id').value = '';
                clearCollabFields();
                enableCollabFields();
            }
        }

        function fillPartnerData() {
            const select = document.getElementById('collaborate_customer_id');
            const option = select.options[select.selectedIndex];

            if (option && option.value) {
                // Auto-fill from customer data
                setField('collaborate_company', option.dataset.name || '');
                setField('collaborate_tax_code', option.dataset.tax || '');
                setField('collaborate_pic_phone', option.dataset.phone || '');
                setField('collaborate_pic_email', option.dataset.email || '');
                // PIC name and title need manual input (from contacts or new)
                document.getElementById('collaborate_pic_name').value = '';
                document.getElementById('collaborate_pic_title').value = '';
                // Make auto-filled fields read-only (company + tax from customer)
                document.getElementById('collaborate_company').readOnly = true;
                document.getElementById('collaborate_company').classList.add('bg-gray-50');
                document.getElementById('collaborate_tax_code').readOnly = true;
                document.getElementById('collaborate_tax_code').classList.add('bg-gray-50');
            } else {
                // No customer selected, enable all fields
                clearCollabFields();
                enableCollabFields();
            }
        }

        function setField(id, value) {
            const el = document.getElementById(id);
            if (el) el.value = value;
        }

        function clearCollabFields() {
            ['collaborate_company', 'collaborate_tax_code', 'collaborate_pic_name',
             'collaborate_pic_title', 'collaborate_pic_phone', 'collaborate_pic_email'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        }

        function enableCollabFields() {
            ['collaborate_company', 'collaborate_tax_code', 'collaborate_pic_name',
             'collaborate_pic_title', 'collaborate_pic_phone', 'collaborate_pic_email'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.readOnly = false;
                    el.classList.remove('bg-gray-50');
                }
            });
        }

        function showCollabFields() {
            collabFieldIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('hidden');
            });
        }

        function hideCollabFields() {
            collabFieldIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            });
        }

        function updateExpiredPreview(radio) {
            document.querySelectorAll('input[name="estimated_close_months"]').forEach(r => {
                r.closest('label').classList.remove('border-blue-500', 'bg-blue-50');
                r.closest('label').classList.add('border-gray-200');
            });
            radio.closest('label').classList.add('border-blue-500', 'bg-blue-50');
            radio.closest('label').classList.remove('border-gray-200');
        }

        function formatCurrency(input, hiddenId) {
            let value = input.value.replace(/\D/g, '');
            document.getElementById(hiddenId).value = value;
            if (value !== '') {
                value = parseInt(value).toLocaleString('en-US');
                input.value = value;
            } else {
                input.value = '';
            }
        }

        // === AJAX: Check duplicate MST/Tax Code ===
        let taxCheckTimer = null;
        function checkDuplicateTaxCode(taxCode) {
            const warningDiv = document.getElementById('eu_tax_warning');
            const warningDetail = document.getElementById('eu_tax_warning_detail');

            if (!taxCode || taxCode.length < 3) {
                warningDiv.classList.add('hidden');
                return;
            }

            fetch(`{{ route('projects.check-tax-code') }}?tax_code=${encodeURIComponent(taxCode)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    warningDetail.textContent = `Khách hàng: ${data.customer.name} (MST: ${data.customer.tax_code})${data.customer.address ? ' — ' + data.customer.address : ''}`;
                    warningDiv.classList.remove('hidden');
                } else {
                    warningDiv.classList.add('hidden');
                }
            })
            .catch(() => warningDiv.classList.add('hidden'));
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const type = document.getElementById('collaborate_type').value;
            if (type) toggleCollaborateType();

            // Bind debounced check to eu_tax_code input
            const euTaxInput = document.getElementById('eu_tax_code');
            if (euTaxInput) {
                euTaxInput.addEventListener('input', function() {
                    clearTimeout(taxCheckTimer);
                    taxCheckTimer = setTimeout(() => checkDuplicateTaxCode(this.value.trim()), 500);
                });
                // Check on load if old value exists
                if (euTaxInput.value.trim().length >= 3) {
                    checkDuplicateTaxCode(euTaxInput.value.trim());
                }
            }

            // Show SweetAlert2 toast when there are validation errors
            @if($errors->any())
                const errorMessages = @json($errors->all());
                const errorList = errorMessages.map(msg => `<li class="text-left text-sm">${msg}</li>`).join('');
                Swal.fire({
                    icon: 'error',
                    title: 'Vui lòng kiểm tra lại thông tin!',
                    html: `<ul class="list-disc pl-5 space-y-1 max-h-60 overflow-y-auto">${errorList}</ul>`,
                    confirmButtonText: 'Đã hiểu',
                    confirmButtonColor: '#3B82F6',
                    customClass: { popup: 'text-sm' }
                });

                // Auto-scroll to first error field
                const firstError = document.querySelector('.border-red-500');
                if (firstError) {
                    setTimeout(() => {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }, 500);
                }
            @endif
        });
    </script>
@endsection