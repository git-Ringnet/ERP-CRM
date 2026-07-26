@extends('layouts.app')

@section('title', 'Đăng ký dự án mới')
@section('page-title', 'Đăng ký dự án mới')

@section('content')
    <div class="max-w-8xl">
        <form id="project_form" action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="marketing_event_id" value="{{ old('marketing_event_id', $preFill['marketing_event_id'] ?? '') }}">
            <input type="hidden" name="opportunity_id" value="{{ old('opportunity_id', $preFill['opportunity_id'] ?? '') }}">

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
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Vendor <span class="text-red-500">*</span>
                                </label>
                                <select name="vendor_id" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn Vendor --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('vendor_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Distributor -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Distributor</label>
                                <input type="text" name="distributor" value="{{ old('distributor', 'Tech Horizon Corporation') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <!-- Distributor AM (Auto from login) -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Distributor AM <span class="text-red-500">*</span>
                                </label>
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
                                    End-user (Vietnamese name) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="eu_name_vi" value="{{ old('eu_name_vi', $preFill['eu_name_vi'] ?? '') }}" required
                                    placeholder="Tên tiếng Việt của khách hàng"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_name_vi') border-red-500 @enderror">
                                @error('eu_name_vi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <!-- EU English name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    End-user (English name) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="eu_name_en" value="{{ old('eu_name_en', $preFill['eu_name_en'] ?? '') }}" required
                                    placeholder="English name"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_name_en') border-red-500 @enderror">
                                @error('eu_name_en') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <!-- Website/Tax Code -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Website/Tax Code <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="eu_tax_code" id="eu_tax_code" value="{{ old('eu_tax_code', $preFill['eu_tax_code'] ?? '') }}" required
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
                                    Address
                                </label>
                                <input type="text" name="address" value="{{ old('address', $preFill['address'] ?? '') }}"
                                    placeholder="Địa chỉ End-User"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('address') border-red-500 @enderror">
                                @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <!-- Industry -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Industry
                                </label>
                                <select name="eu_industry" id="eu_industry"
                                    onchange="toggleIndustryOther()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_industry') border-red-500 @enderror">
                                    <option value="">-- Chọn ngành nghề --</option>
                                    @foreach($industries as $key => $label)
                                        <option value="{{ $key }}" {{ old('eu_industry') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('eu_industry') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                                <div id="eu_industry_other_container" class="mt-2 {{ old('eu_industry') == 'other' ? '' : 'hidden' }}">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">
                                        Nhập ngành nghề khác
                                    </label>
                                    <input type="text" name="eu_industry_other" id="eu_industry_other"
                                        value="{{ old('eu_industry_other') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_industry_other') border-red-500 @enderror"
                                        placeholder="Nhập tên ngành nghề...">
                                    @error('eu_industry_other') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
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
                                    Collaborate <span class="text-red-500">*</span>
                                </label>
                                <select name="collaborate_type" id="collaborate_type" required
                                    onchange="toggleCollaborateType()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn loại hợp tác --</option>
                                    <option value="partner" {{ old('collaborate_type') == 'partner' ? 'selected' : '' }}>Partner</option>
                                    <option value="end_user" {{ old('collaborate_type') == 'end_user' ? 'selected' : '' }}>EU</option>
                                </select>
                            </div>

                            <!-- Partner mode: Toggle between "Chọn từ danh sách" and "Tạo mới" -->
                            <div id="partner_mode_toggle" class="md:col-span-2 {{ old('collaborate_type') == 'partner' ? '' : 'hidden' }}">
                                <div class="flex items-center gap-4 mb-3">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="partner_input_mode" value="existing" {{ old('partner_input_mode', 'existing') === 'existing' ? 'checked' : '' }}
                                            onchange="togglePartnerInputMode('existing')"
                                            class="text-primary focus:ring-primary">
                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                            <i class="fas fa-search mr-1 text-blue-500"></i>Chọn từ danh sách khách hàng
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="partner_input_mode" value="new" {{ old('partner_input_mode') === 'new' ? 'checked' : '' }}
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

                                <!-- Contact Point Dropdown (loaded via AJAX) -->
                                <div id="contact_point_section" class="hidden mt-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        <i class="fas fa-user mr-1 text-indigo-500"></i>Chọn Contact Point
                                    </label>
                                    <div class="flex gap-2">
                                        <select id="contact_point_select" onchange="fillContactData()"
                                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                            <option value="">-- Chọn người liên hệ --</option>
                                        </select>
                                        <button type="button" onclick="toggleNewContactForm()"
                                            class="px-3 py-2 bg-green-50 text-green-700 border border-green-300 rounded-lg hover:bg-green-100 transition-colors text-sm whitespace-nowrap">
                                            <i class="fas fa-plus mr-1"></i>Tạo mới
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <i class="fas fa-info-circle mr-1"></i>Chọn contact point để autofill thông tin PIC bên dưới
                                    </p>
                                </div>

                                <!-- Inline Create Contact Point Form -->
                                <div id="new_contact_form" class="hidden mt-3 bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-indigo-800 mb-3">
                                        <i class="fas fa-user-plus mr-1"></i>Tạo mới Contact Point
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Họ <span class="text-red-500">*</span></label>
                                            <input type="text" id="new_contact_first_name" placeholder="Nguyễn Văn"
                                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Tên</label>
                                            <input type="text" id="new_contact_last_name" placeholder="A"
                                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                                            <input type="text" id="new_contact_position" placeholder="VD: Manager, Director..."
                                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Chức danh</label>
                                            <input type="text" id="new_contact_title" placeholder="VD: Mr., Mrs...."
                                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">SĐT <span class="text-red-500">*</span></label>
                                            <input type="text" id="new_contact_phone" placeholder="09xxxxxxxx"
                                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                                            <input type="email" id="new_contact_email" placeholder="email@company.com"
                                                class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" onclick="saveNewContact()"
                                            class="px-4 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 transition-colors">
                                            <i class="fas fa-save mr-1"></i>Lưu Contact
                                        </button>
                                        <button type="button" onclick="toggleNewContactForm()"
                                            class="px-4 py-1.5 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300 transition-colors">
                                            Hủy
                                        </button>
                                    </div>
                                    <div id="new_contact_error" class="hidden mt-2 text-red-600 text-xs"></div>
                                </div>
                            </div>
                            <!-- End-user notice (shown when type=end_user) -->
                            <div id="enduser_notice" class="md:col-span-2 hidden">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                                        <div>
                                            <p class="text-sm font-medium text-blue-800">Chế độ EU (Sao chép thông tin End-user)</p>
                                            <p class="text-xs text-blue-600 mt-1">
                                                Thông tin End-User ở Section B sẽ được tự động sao chép làm đối tác hợp tác. Vui lòng điền thêm người liên lạc chính (PIC) bên dưới.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Partner fields (hidden when type=end_user) -->
                            <div id="collab_company_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Company name <span class="text-red-500">*</span>
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
                                <!-- Collab duplicate warning -->
                                <div id="collab_tax_warning" class="hidden mt-2 bg-yellow-50 border border-yellow-300 rounded-lg p-3">
                                    <div class="flex items-start gap-2">
                                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
                                        <div class="text-sm">
                                            <p class="font-semibold text-yellow-800">MST đã tồn tại trong hệ thống, vui lòng kiểm tra lại hoặc sử dụng Company có sẵn.</p>
                                            <p class="text-yellow-700 mt-1" id="collab_tax_warning_detail"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="collab_pic_name_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    PIC Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="collaborate_pic_name" id="collaborate_pic_name"
                                    value="{{ old('collaborate_pic_name') }}"
                                    placeholder="Họ và tên PIC"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_pic_name') border-red-500 @enderror">
                                @error('collaborate_pic_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_title_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    PIC Job Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="collaborate_pic_title" id="collaborate_pic_title"
                                    value="{{ old('collaborate_pic_title') }}"
                                    placeholder="VD: Manager, Director..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_pic_title') border-red-500 @enderror">
                                @error('collaborate_pic_title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_phone_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    PIC Phone
                                </label>
                                <input type="text" name="collaborate_pic_phone" id="collaborate_pic_phone"
                                    value="{{ old('collaborate_pic_phone') }}"
                                    placeholder="0901234567"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_pic_phone') border-red-500 @enderror">
                                @error('collaborate_pic_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_email_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    PIC Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="collaborate_pic_email" id="collaborate_pic_email" required
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
                            <!-- Hidden Mã dự án -->
                            <input type="hidden" name="code" value="{{ old('code', $code) }}">

                            <!-- Project Name -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Project Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $preFill['name'] ?? '') }}" required
                                    placeholder="Nhập tên dự án..."
                                    onblur="checkDuplicateProject()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Real-time Duplicate Alert Box -->
                            <div id="duplicate_project_warning" class="hidden md:col-span-2 bg-red-50 border border-red-300 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-lg mt-0.5"></i>
                                    <div>
                                        <h4 class="font-bold text-red-800 text-sm">CẢNH BÁO TRÙNG DỰ ÁN!</h4>
                                        <p class="text-xs text-red-700 mt-1" id="duplicate_project_msg"></p>
                                        <div id="duplicate_same_sales_actions" class="hidden mt-2">
                                            <p class="text-xs font-semibold text-red-800">Bạn đã đăng ký dự án này trước đó. Vui lòng cập nhật dự án cũ thay vì tạo dự án mới!</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BOM / YCKT Upload -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    BOM (Bill of Materials) hoặc File Yêu Cầu Kỹ Thuật (YCKT) <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2">
                                    <input type="file" name="bom_file[]" multiple accept=".xlsx,.xls,.pdf,.doc,.docx"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="text-xs text-gray-500">Đính kèm file BOM hoặc YCKT từ khách hàng nếu chưa có BOM (lúc này Hãng sẽ là người cung cấp BOM)</p>
                                    <textarea name="bom_data" rows="2" placeholder="Hoặc nhập danh sách sản phẩm / Part Numbers..."
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('bom_data') }}</textarea>
                                </div>
                            </div>

                            <!-- Deal Type (Fortinet Dealreg Only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Deal Type (Fortinet Dealreg Only)
                                </label>
                                <select name="deal_type" id="deal_type_select" onchange="toggleFortinetSN()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn loại Deal --</option>
                                    <option value="new_buy" {{ old('deal_type') == 'new_buy' ? 'selected' : '' }}>Newbuy (Mua mới)</option>
                                    <option value="trade_up" {{ old('deal_type') == 'trade_up' ? 'selected' : '' }}>Trade up (Nâng cấp / Đổi thiết bị cũ)</option>
                                </select>
                            </div>

                            <!-- Note (Fortinet Dealreg Only) when Trade up selected -->
                            <div id="sn_numbers_container" class="hidden md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Note (Fortinet Dealreg Only) <span class="text-red-500">*</span>
                                </label>
                                <textarea name="sn_numbers" rows="2" placeholder="Nếu chọn Trade up thì cần nhập số S/N vào đây..."
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">{{ old('sn_numbers') }}</textarea>
                                <p class="text-xs text-gray-500 mt-1">Bắt buộc nhập số S/N khi chọn Trade up Fortinet</p>
                            </div>

                            <!-- Yêu cầu thêm (Special Request) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Yêu cầu thêm</label>
                                <select name="special_request_type" id="special_request_type" onchange="toggleSpecialRequestNote()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Không có yêu cầu thêm --</option>
                                    <option value="bom_project" {{ old('special_request_type') == 'bom_project' ? 'selected' : '' }}>Bom dự án (Nhờ Hãng tạo BOM)</option>
                                    <option value="urgent_price" {{ old('special_request_type') == 'urgent_price' ? 'selected' : '' }}>Cần giá gấp</option>
                                </select>
                            </div>

                            <!-- Note bắt buộc cho Yêu cầu thêm -->
                            <div id="special_request_note_container" class="hidden md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Ghi chú yêu cầu thêm <span class="text-red-500">*</span>
                                </label>
                                <textarea name="special_request_note" rows="2" placeholder="Nhập lý do / nội dung diễn giải cho yêu cầu thêm..."
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">{{ old('special_request_note') }}</textarea>
                            </div>

                            <!-- Ghi chú dự án (General Note) -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Note <span class="text-red-500">*</span>
                                </label>
                                <textarea name="note" rows="3" required placeholder="Nhập ghi chú chi tiết cho dự án..."
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary @error('note') border-red-500 @enderror">{{ old('note') }}</textarea>
                                @error('note') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Removed Net to Tech Horizon and Description -->
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Sidebar -->
                <div class="space-y-6">
                    <!-- Hidden Status (Auto set to in_progress / submitted) -->
                    <input type="hidden" name="status" value="in_progress">
                    <input type="hidden" name="registration_status" value="submitted">

                    <!-- Estimated Close Date -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar-alt mr-2 text-primary"></i>Estimated Close Date <span class="text-red-500">*</span>
                        </h3>
                        <p class="text-xs text-gray-500 mb-3">Chọn ngày cụ thể hoặc sử dụng các nút chọn nhanh dưới đây.</p>
                        
                        <!-- Date Input -->
                        <div class="relative mb-4">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar text-gray-400"></i>
                            </div>
                            <input type="text" name="end_date" id="end_date" required
                                value="{{ old('end_date', now()->addMonths(3)->format('Y-m-d')) }}"
                                class="w-full border border-gray-300 rounded-lg pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary @error('end_date') border-red-500 @enderror"
                                placeholder="Chọn ngày hết hạn dự kiến...">
                        </div>
                        
                        <!-- Preset Buttons -->
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" id="btn_preset_3" onclick="setPresetEndDate(3)"
                                class="px-3 py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                +3M (3 Tháng)
                            </button>
                            <button type="button" id="btn_preset_6" onclick="setPresetEndDate(6)"
                                class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-200 hover:border-blue-500 hover:bg-blue-50 text-gray-700 transition-colors">
                                +6M (6 Tháng)
                            </button>
                            <button type="button" id="btn_preset_12" onclick="setPresetEndDate(12)"
                                class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-200 hover:border-blue-500 hover:bg-blue-50 text-gray-700 transition-colors">
                                +12M (12 Tháng)
                            </button>
                        </div>
                        
                        @error('end_date') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
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
        function toggleIndustryOther() {
            const select = document.getElementById('eu_industry');
            const otherContainer = document.getElementById('eu_industry_other_container');
            const otherInput = document.getElementById('eu_industry_other');
            if (select && otherContainer) {
                if (select.value === 'other') {
                    otherContainer.classList.remove('hidden');
                    if (otherInput) otherInput.required = true;
                } else {
                    otherContainer.classList.add('hidden');
                    if (otherInput) {
                        otherInput.required = false;
                        otherInput.value = '';
                    }
                }
            }
        }

        function toggleFortinetSN() {
            const select = document.getElementById('deal_type_select');
            const container = document.getElementById('sn_numbers_container');
            const textarea = container ? container.querySelector('textarea') : null;
            if (select && container) {
                if (select.value === 'trade_up') {
                    container.classList.remove('hidden');
                    if (textarea) textarea.required = true;
                } else {
                    container.classList.add('hidden');
                    if (textarea) {
                        textarea.required = false;
                        textarea.value = '';
                    }
                }
            }
        }

        function toggleSpecialRequestNote() {
            const select = document.getElementById('special_request_type');
            const container = document.getElementById('special_request_note_container');
            const textarea = container ? container.querySelector('textarea') : null;
            if (select && container) {
                if (select.value === 'bom_project' || select.value === 'urgent_price') {
                    container.classList.remove('hidden');
                    if (textarea) textarea.required = true;
                } else {
                    container.classList.add('hidden');
                    if (textarea) {
                        textarea.required = false;
                        textarea.value = '';
                    }
                }
            }
        }

        const collabFieldIds = [
            'collab_company_wrap', 'collab_tax_wrap',
            'collab_pic_name_wrap', 'collab_pic_title_wrap',
            'collab_pic_phone_wrap', 'collab_pic_email_wrap'
        ];

        // ===================================================================
        // Provinces API — Load + Cache + Fallback
        // ===================================================================
        const VN_PROVINCES_FALLBACK = [
            "Thành phố Hà Nội","Thành phố Hồ Chí Minh","Thành phố Đà Nẵng","Thành phố Hải Phòng","Thành phố Cần Thơ",
            "Tỉnh An Giang","Tỉnh Bà Rịa - Vũng Tàu","Tỉnh Bạc Liêu","Tỉnh Bắc Giang","Tỉnh Bắc Kạn",
            "Tỉnh Bắc Ninh","Tỉnh Bến Tre","Tỉnh Bình Dương","Tỉnh Bình Định","Tỉnh Bình Phước",
            "Tỉnh Bình Thuận","Tỉnh Cà Mau","Tỉnh Cao Bằng","Tỉnh Đắk Lắk","Tỉnh Đắk Nông",
            "Tỉnh Điện Biên","Tỉnh Đồng Nai","Tỉnh Đồng Tháp","Tỉnh Gia Lai","Tỉnh Hà Giang",
            "Tỉnh Hà Nam","Tỉnh Hà Tĩnh","Tỉnh Hải Dương","Tỉnh Hậu Giang","Tỉnh Hòa Bình",
            "Tỉnh Hưng Yên","Tỉnh Khánh Hòa","Tỉnh Kiên Giang","Tỉnh Kon Tum","Tỉnh Lai Châu",
            "Tỉnh Lâm Đồng","Tỉnh Lạng Sơn","Tỉnh Lào Cai","Tỉnh Long An","Tỉnh Nam Định",
            "Tỉnh Nghệ An","Tỉnh Ninh Bình","Tỉnh Ninh Thuận","Tỉnh Phú Thọ","Tỉnh Phú Yên",
            "Tỉnh Quảng Bình","Tỉnh Quảng Nam","Tỉnh Quảng Ngãi","Tỉnh Quảng Ninh","Tỉnh Quảng Trị",
            "Tỉnh Sóc Trăng","Tỉnh Sơn La","Tỉnh Tây Ninh","Tỉnh Thái Bình","Tỉnh Thái Nguyên",
            "Tỉnh Thanh Hóa","Tỉnh Thừa Thiên Huế","Tỉnh Tiền Giang","Tỉnh Trà Vinh","Tỉnh Tuyên Quang",
            "Tỉnh Vĩnh Long","Tỉnh Vĩnh Phúc","Tỉnh Yên Bái"
        ];

        function loadProvinces() {
            const select = document.getElementById('eu_province');
            if (!select) return;

            const oldValue = @json(old('eu_province', ''));

            // Try localStorage cache first (24h TTL)
            const cached = localStorage.getItem('vn_provinces');
            const cachedTime = localStorage.getItem('vn_provinces_time');
            if (cached && cachedTime && (Date.now() - parseInt(cachedTime)) < 86400000) {
                populateProvinces(JSON.parse(cached), oldValue);
                return;
            }

            // Fetch from API
            fetch('https://provinces.open-api.vn/api/p/')
                .then(res => res.json())
                .then(data => {
                    const names = data.map(p => p.name).sort();
                    localStorage.setItem('vn_provinces', JSON.stringify(names));
                    localStorage.setItem('vn_provinces_time', Date.now().toString());
                    populateProvinces(names, oldValue);
                })
                .catch(() => {
                    // Fallback to hardcoded list
                    populateProvinces(VN_PROVINCES_FALLBACK, oldValue);
                });
        }

        function populateProvinces(provinces, selectedValue) {
            const select = document.getElementById('eu_province');
            select.innerHTML = '<option value="">-- Chọn Tỉnh / Thành phố --</option>';
            provinces.forEach(name => {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                if (name === selectedValue) opt.selected = true;
                select.appendChild(opt);
            });
        }

        // ===================================================================
        // Collaboration Type Toggle
        // ===================================================================
        function toggleCollaborateType(isInit = false) {
            const type = document.getElementById('collaborate_type').value;
            const partnerToggle = document.getElementById('partner_mode_toggle');
            const enduserNotice = document.getElementById('enduser_notice');

            const companyInput = document.getElementById('collaborate_company');
            const taxInput = document.getElementById('collaborate_tax_code');
            const phoneInput = document.getElementById('collaborate_pic_phone');
            const nameInput = document.getElementById('collaborate_pic_name');
            const titleInput = document.getElementById('collaborate_pic_title');
            const emailInput = document.getElementById('collaborate_pic_email');

            if (type === 'partner') {
                partnerToggle.classList.remove('hidden');
                enduserNotice.classList.add('hidden');
                showCollabFields();

                if (companyInput) companyInput.required = true;
                if (taxInput) taxInput.required = true;
                if (phoneInput) phoneInput.required = false;
                if (nameInput) nameInput.required = true;
                if (titleInput) titleInput.required = true;
                if (emailInput) emailInput.required = true;

                const activeMode = document.querySelector('input[name="partner_input_mode"]:checked')?.value || 'existing';
                togglePartnerInputMode(activeMode, isInit);
            } else if (type === 'end_user') {
                partnerToggle.classList.add('hidden');
                enduserNotice.classList.remove('hidden');
                
                // Show PIC fields only, hide company name and tax code
                document.getElementById('collab_company_wrap').classList.add('hidden');
                document.getElementById('collab_tax_wrap').classList.add('hidden');
                document.getElementById('collab_pic_name_wrap').classList.remove('hidden');
                document.getElementById('collab_pic_title_wrap').classList.remove('hidden');
                document.getElementById('collab_pic_phone_wrap').classList.remove('hidden');
                document.getElementById('collab_pic_email_wrap').classList.remove('hidden');
                
                hideContactPointSection();

                if (companyInput) companyInput.required = false;
                if (taxInput) taxInput.required = false;
                if (phoneInput) phoneInput.required = false;
                if (nameInput) nameInput.required = true;
                if (titleInput) titleInput.required = true;
                if (emailInput) emailInput.required = true;
            } else {
                partnerToggle.classList.add('hidden');
                enduserNotice.classList.add('hidden');
                hideCollabFields();
                hideContactPointSection();

                if (companyInput) companyInput.required = false;
                if (taxInput) taxInput.required = false;
                if (phoneInput) phoneInput.required = false;
                if (nameInput) nameInput.required = false;
                if (titleInput) titleInput.required = false;
                if (emailInput) emailInput.required = false;
            }
        }

        function togglePartnerInputMode(mode, isInit = false) {
            const existingSelect = document.getElementById('partner_existing_select');
            const collabWarningDiv = document.getElementById('collab_tax_warning');
            const nameInput = document.getElementById('collaborate_pic_name');
            const titleInput = document.getElementById('collaborate_pic_title');
            const phoneInput = document.getElementById('collaborate_pic_phone');

            // Always show all PIC wraps in partner mode
            document.getElementById('collab_pic_name_wrap').classList.remove('hidden');
            document.getElementById('collab_pic_title_wrap').classList.remove('hidden');
            document.getElementById('collab_pic_phone_wrap').classList.remove('hidden');
            document.getElementById('collab_pic_email_wrap').classList.remove('hidden');

            if (nameInput) nameInput.required = true;
            if (titleInput) titleInput.required = true;
            if (phoneInput) phoneInput.required = true;

            if (mode === 'existing') {
                existingSelect.classList.remove('hidden');
                if (collabWarningDiv) collabWarningDiv.classList.add('hidden');
                collabTaxExists = false;
                fillPartnerData(isInit);
            } else {
                existingSelect.classList.add('hidden');
                const select = document.getElementById('collaborate_customer_id');
                if (select) {
                    select.value = '';
                    if (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
                        $(select).val('').trigger('change.select2');
                    }
                }
                if (collabWarningDiv) collabWarningDiv.classList.add('hidden');
                collabTaxExists = false;

                clearCollabFields();
                enableCollabFields();
                hideContactPointSection();
                
                const currentTax = document.getElementById('collaborate_tax_code').value.trim();
                if (currentTax.length >= 3) {
                    checkCollabDuplicateTaxCode(currentTax);
                }
            }
        }

        function fillPartnerData(isInit = false) {
            const select = document.getElementById('collaborate_customer_id');
            if (!select) return;

            let name = '';
            let tax = '';
            let customerId = select.value;

            // Try to get from select2 if initialized
            if (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
                const selectedData = $(select).select2('data')[0];
                if (selectedData && selectedData.id) {
                    name = selectedData.name || '';
                    tax = selectedData.tax_code || '';
                }
            }

            // Fallback to option dataset attributes
            if (!name && select.selectedIndex >= 0) {
                const option = select.options[select.selectedIndex];
                if (option && option.value) {
                    name = option.dataset.name || '';
                    tax = option.dataset.tax || '';
                }
            }

            if (customerId && (name || tax)) {
                setField('collaborate_company', name);
                setField('collaborate_tax_code', tax);
                if (!isInit) {
                    setField('collaborate_pic_phone', '');
                    setField('collaborate_pic_email', '');
                    document.getElementById('collaborate_pic_name').value = '';
                    document.getElementById('collaborate_pic_title').value = '';
                }
                document.getElementById('collaborate_company').readOnly = true;
                document.getElementById('collaborate_company').classList.add('bg-gray-50');
                document.getElementById('collaborate_tax_code').readOnly = true;
                document.getElementById('collaborate_tax_code').classList.add('bg-gray-50');

                // Load contacts for this customer
                loadContactPoints(customerId);
            } else {
                clearCollabFields();
                enableCollabFields();
                hideContactPointSection();
            }
        }

        // ===================================================================
        // Contact Point — AJAX Load + Fill + Inline Create
        // ===================================================================
        function loadContactPoints(customerId) {
            const section = document.getElementById('contact_point_section');
            const select = document.getElementById('contact_point_select');
            section.classList.remove('hidden');
            select.innerHTML = '<option value="">-- Đang tải... --</option>';

            fetch(`/ajax/customers/${customerId}/contacts`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(contacts => {
                select.innerHTML = '<option value="">-- Chọn người liên hệ --</option>';
                if (contacts.length === 0) {
                    select.innerHTML = '<option value="">-- Chưa có contact, hãy tạo mới --</option>';
                }
                
                const currentPicName = document.getElementById('collaborate_pic_name').value.trim();
                const currentPicPhone = document.getElementById('collaborate_pic_phone').value.trim();

                contacts.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = `${c.name || c.first_name} — ${c.position || ''} — ${c.phone || ''}`;
                    opt.dataset.name = c.name || (c.first_name + ' ' + (c.last_name || ''));
                    opt.dataset.title = c.title || '';
                    opt.dataset.position = c.position || '';
                    opt.dataset.phone = c.phone || '';
                    opt.dataset.email = c.email || '';
                    
                    if ((c.name && c.name === currentPicName) || (c.phone && c.phone === currentPicPhone)) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                });
            })
            .catch(() => {
                select.innerHTML = '<option value="">-- Lỗi tải contacts --</option>';
            });
        }

        function fillContactData() {
            const select = document.getElementById('contact_point_select');
            const option = select.options[select.selectedIndex];
            if (option && option.value) {
                setField('collaborate_pic_name', option.dataset.name || '');
                setField('collaborate_pic_title', option.dataset.position || '');
                setField('collaborate_pic_phone', option.dataset.phone || '');
                setField('collaborate_pic_email', option.dataset.email || '');
            }
        }

        function hideContactPointSection() {
            const section = document.getElementById('contact_point_section');
            const form = document.getElementById('new_contact_form');
            if (section) section.classList.add('hidden');
            if (form) form.classList.add('hidden');
        }

        function toggleNewContactForm() {
            const form = document.getElementById('new_contact_form');
            form.classList.toggle('hidden');
        }

        function saveNewContact() {
            const customerId = document.getElementById('collaborate_customer_id').value;
            if (!customerId) {
                alert('Vui lòng chọn khách hàng trước khi tạo contact.');
                return;
            }

            const data = {
                first_name: document.getElementById('new_contact_first_name').value,
                last_name: document.getElementById('new_contact_last_name').value,
                position: document.getElementById('new_contact_position').value,
                title: document.getElementById('new_contact_title').value,
                phone: document.getElementById('new_contact_phone').value,
                email: document.getElementById('new_contact_email').value,
            };

            const errorDiv = document.getElementById('new_contact_error');
            errorDiv.classList.add('hidden');

            fetch(`/ajax/customers/${customerId}/contacts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    // Reload contacts and auto-select the new one
                    loadContactPoints(customerId);
                    // Autofill PIC fields
                    setField('collaborate_pic_name', result.contact.name || '');
                    setField('collaborate_pic_title', result.contact.position || '');
                    setField('collaborate_pic_phone', result.contact.phone || '');
                    setField('collaborate_pic_email', result.contact.email || '');
                    // Clear and hide form
                    ['new_contact_first_name','new_contact_last_name','new_contact_position',
                     'new_contact_title','new_contact_phone','new_contact_email'].forEach(id => {
                        document.getElementById(id).value = '';
                    });
                    document.getElementById('new_contact_form').classList.add('hidden');
                    // Show success toast
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'Đã tạo contact mới!', timer: 1500, showConfirmButton: false });
                    }
                } else if (result.errors) {
                    const msgs = Object.values(result.errors).flat().join('<br>');
                    errorDiv.innerHTML = msgs;
                    errorDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                errorDiv.textContent = 'Lỗi kết nối, vui lòng thử lại.';
                errorDiv.classList.remove('hidden');
            });
        }

        // ===================================================================
        // Utility Functions
        // ===================================================================
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

        // === AJAX: Check duplicate Project (Hãng + EU/MST + Tên dự án + Sản phẩm/BOM) ===
        let projectCheckTimer = null;
        function checkDuplicateProject() {
            const vendorId = document.getElementById('vendor_id')?.value || '';
            const taxCode = document.getElementById('eu_tax_code')?.value || '';
            const name = document.getElementById('name')?.value || '';
            const bomData = document.querySelector('[name="bom_data"]')?.value || '';

            const warningDiv = document.getElementById('duplicate_project_warning');
            const warningMsg = document.getElementById('duplicate_project_msg');
            const sameSalesActions = document.getElementById('duplicate_same_sales_actions');
            const submitBtn = document.querySelector('button[type="submit"]');

            if (!taxCode || !name) {
                if (warningDiv) warningDiv.classList.add('hidden');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                return;
            }

            const url = `{{ route('projects.check-duplicate') }}?vendor_id=${encodeURIComponent(vendorId)}&tax_code=${encodeURIComponent(taxCode)}&name=${encodeURIComponent(name)}&bom_data=${encodeURIComponent(bomData)}`;

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.duplicate) {
                    if (warningDiv) warningDiv.classList.remove('hidden');
                    
                    if (data.is_same_user) {
                        if (warningMsg) {
                            warningMsg.innerHTML = `Dự án <strong>${data.project_name}</strong> (${data.project_code}) đã được bạn đăng ký vào ngày ${data.created_at}.`;
                        }
                        if (sameSalesActions) {
                            sameSalesActions.classList.remove('hidden');
                            let editLink = sameSalesActions.querySelector('a');
                            if (!editLink) {
                                editLink = document.createElement('a');
                                editLink.className = 'mt-2 inline-block px-3 py-1.5 bg-red-600 text-white rounded text-xs font-semibold hover:bg-red-700 transition-colors';
                                editLink.textContent = 'Chỉnh sửa dự án cũ';
                                sameSalesActions.appendChild(editLink);
                            }
                            editLink.href = `/projects/${data.project_id}/edit`;
                        }
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                    } else {
                        if (warningMsg) {
                            warningMsg.innerHTML = `<strong>Cảnh báo:</strong> Dự án này đã trùng thông tin đăng ký với hãng và khách hàng của Sales khác. Hệ thống đã tự động cảnh báo cho PM.`;
                        }
                        if (sameSalesActions) sameSalesActions.classList.add('hidden');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    }
                } else {
                    if (warningDiv) warningDiv.classList.add('hidden');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }
            })
            .catch(() => {
                if (warningDiv) warningDiv.classList.add('hidden');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            });
        }

        // === AJAX: Check duplicate Partner/Company MST/Tax Code ===
        let collabTaxCheckTimer = null;
        let collabTaxExists = false;
        function checkCollabDuplicateTaxCode(taxCode) {
            const warningDiv = document.getElementById('collab_tax_warning');
            const warningDetail = document.getElementById('collab_tax_warning_detail');

            if (!taxCode || taxCode.length < 3) {
                warningDiv.classList.add('hidden');
                collabTaxExists = false;
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
                    collabTaxExists = true;
                } else {
                    warningDiv.classList.add('hidden');
                    collabTaxExists = false;
                }
            })
            .catch(() => {
                warningDiv.classList.add('hidden');
                collabTaxExists = false;
            });
        }

        // ===================================================================
        // Initialize on page load
        // ===================================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Load provinces dropdown from API
            loadProvinces();

            // Toggle industry other input if selected
            toggleIndustryOther();

            // Bind listener to deal type select
            const dealTypeSelect = document.getElementById('deal_type_select');
            if (dealTypeSelect) {
                dealTypeSelect.addEventListener('change', toggleFortinetSN);
                toggleFortinetSN();
            }

            // Bind listener to special request type select
            const specialRequestSelect = document.getElementById('special_request_type');
            if (specialRequestSelect) {
                specialRequestSelect.addEventListener('change', toggleSpecialRequestNote);
                toggleSpecialRequestNote();
            }

            // Support pre-filling from Opportunity (SI/EU)
            const preFillCustomerType = @json($preFill['customer_type'] ?? '');
            const preFillCustomerId = @json($preFill['customer_id'] ?? '');
            const preFillContactId = @json($preFill['contact_id'] ?? '');

            if (preFillCustomerType) {
                const colTypeSelect = document.getElementById('collaborate_type');
                if (colTypeSelect) {
                    colTypeSelect.value = preFillCustomerType === 'si' ? 'partner' : 'end_user';
                    toggleCollaborateType();

                    if (preFillCustomerType === 'si' && preFillCustomerId) {
                        const colCustSelect = document.getElementById('collaborate_customer_id');
                        if (colCustSelect) {
                            colCustSelect.value = preFillCustomerId;
                            if (typeof $ !== 'undefined' && $(colCustSelect).hasClass('select2-hidden-accessible')) {
                                $(colCustSelect).trigger('change.select2');
                            }
                            fillPartnerData();

                            // Wait for contacts to load via AJAX and select contact point
                            setTimeout(() => {
                                const cpSelect = document.getElementById('contact_point_select');
                                if (cpSelect && preFillContactId) {
                                    cpSelect.value = preFillContactId;
                                    fillContactData();
                                }
                            }, 1000);
                        }
                    }
                }
            } else {
                const type = document.getElementById('collaborate_type').value;
                if (type) toggleCollaborateType(true);
            }

            // Bind debounced check to eu_tax_code input
            const euTaxInput = document.getElementById('eu_tax_code');
            if (euTaxInput) {
                euTaxInput.addEventListener('input', function() {
                    clearTimeout(taxCheckTimer);
                    taxCheckTimer = setTimeout(() => checkDuplicateTaxCode(this.value.trim()), 500);
                });
                if (euTaxInput.value.trim().length >= 3) {
                    checkDuplicateTaxCode(euTaxInput.value.trim());
                }
            }

            // Bind debounced check to collaborate_tax_code input
            const collabTaxInput = document.getElementById('collaborate_tax_code');
            if (collabTaxInput) {
                collabTaxInput.addEventListener('input', function() {
                    clearTimeout(collabTaxCheckTimer);
                    const collabType = document.getElementById('collaborate_type').value;
                    const partnerMode = document.querySelector('input[name="partner_input_mode"]:checked')?.value;
                    if (collabType === 'partner' && partnerMode === 'new') {
                        collabTaxCheckTimer = setTimeout(() => checkCollabDuplicateTaxCode(this.value.trim()), 500);
                    } else {
                        document.getElementById('collab_tax_warning').classList.add('hidden');
                        collabTaxExists = false;
                    }
                });
                if (collabTaxInput.value.trim().length >= 3) {
                    const collabType = document.getElementById('collaborate_type').value;
                    const partnerMode = document.querySelector('input[name="partner_input_mode"]:checked')?.value;
                    if (collabType === 'partner' && partnerMode === 'new') {
                        checkCollabDuplicateTaxCode(collabTaxInput.value.trim());
                    }
                }
            }

            // Prevent form submit if duplicate collaborate tax code exists
            const projectForm = document.getElementById('project_form');
            if (projectForm) {
                projectForm.addEventListener('submit', function(e) {
                    const collabType = document.getElementById('collaborate_type').value;
                    const partnerMode = document.querySelector('input[name="partner_input_mode"]:checked')?.value;
                    if (collabType === 'partner' && partnerMode === 'new' && collabTaxExists) {
                        e.preventDefault();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Không thể đăng ký dự án!',
                                text: 'MST đã tồn tại trong hệ thống, vui lòng kiểm tra lại hoặc sử dụng Company có sẵn.',
                                confirmButtonText: 'Đã hiểu',
                                confirmButtonColor: '#3B82F6'
                            });
                        } else {
                            alert('MST đã tồn tại trong hệ thống, vui lòng kiểm tra lại hoặc sử dụng Company có sẵn.');
                        }
                    }
                });
            }

            // Real-time project duplicate check listeners
            const nameInput = document.getElementById('name');
            const vendorInput = document.getElementById('vendor_id');
            const bomDataInput = document.querySelector('[name="bom_data"]');
            const taxInput = document.getElementById('eu_tax_code');

            const triggerProjectCheck = () => {
                clearTimeout(projectCheckTimer);
                projectCheckTimer = setTimeout(checkDuplicateProject, 500);
            };

            [nameInput, vendorInput, bomDataInput, taxInput].forEach(input => {
                if (input) {
                    input.addEventListener('input', triggerProjectCheck);
                    input.addEventListener('change', triggerProjectCheck);
                }
            });

            // Initial check on load
            triggerProjectCheck();

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

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container .select2-selection--single {
                height: 42px !important;
                border-color: #D1D5DB !important;
                border-radius: 0.5rem !important;
                padding-top: 6px !important;
                padding-bottom: 6px !important;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 40px !important;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 28px !important;
                color: #1F2937 !important;
                font-size: 0.875rem !important;
            }
            .select2-container {
                width: 100% !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                const selectEl = $('#collaborate_customer_id');
                selectEl.select2({
                    placeholder: "-- Tìm khách hàng (theo tên, MST) --",
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url: "{{ route('customers.ajax-search') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return {
                                results: data.map(item => ({
                                    id: item.id,
                                    text: item.name + (item.tax_code ? ' (MST: ' + item.tax_code + ')' : ''),
                                    name: item.name,
                                    tax_code: item.tax_code,
                                    phone: item.phone,
                                    email: item.email
                                }))
                            };
                        },
                        cache: true
                    }
                });

                // Auto-trigger fillPartnerData when Select2 changes
                selectEl.on('select2:select select2:clear select2:unselect change', function() {
                    fillPartnerData();
                });

                // --- Flatpickr & Preset Counting logic ---
                const registrationDate = new Date();
                
                const endPicker = flatpickr("#end_date", {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    locale: 'vn',
                    minDate: 'today',
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            const selected = selectedDates[0];
                            let foundPreset = false;
                            [3, 6, 12].forEach(m => {
                                const checkDate = new Date(registrationDate);
                                checkDate.setMonth(checkDate.getMonth() + m);
                                if (checkDate.getFullYear() === selected.getFullYear() &&
                                    checkDate.getMonth() === selected.getMonth() &&
                                    checkDate.getDate() === selected.getDate()) {
                                    updateActivePresetButton(m);
                                    foundPreset = true;
                                }
                            });
                            if (!foundPreset) {
                                updateActivePresetButton(null);
                            }
                        }
                    }
                });

                window.setPresetEndDate = function(months) {
                    const d = new Date(registrationDate);
                    d.setMonth(d.getMonth() + months);
                    const yyyy = d.getFullYear();
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const dd = String(d.getDate()).padStart(2, '0');
                    const dateStr = `${yyyy}-${mm}-${dd}`;
                    endPicker.setDate(dateStr);
                    updateActivePresetButton(months);
                };

                function updateActivePresetButton(months) {
                    const presets = [3, 6, 12];
                    presets.forEach(m => {
                        const btn = document.getElementById(`btn_preset_${m}`);
                        if (btn) {
                            if (m === months) {
                                btn.className = "px-3 py-2 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors";
                            } else {
                                btn.className = "px-3 py-2 text-xs font-semibold rounded-lg border border-gray-200 hover:border-blue-500 hover:bg-blue-50 text-gray-700 transition-colors";
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection