@extends('layouts.app')

@section('title', 'Chỉnh sửa dự án')
@section('page-title', 'Chỉnh sửa dự án: ' . $project->code)

@section('content')
    <div class="max-w-8xl">
        <form id="project_form" action="{{ route('projects.update', $project) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Cột trái: 4 Sections -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Section A: Distributor Information -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                            <i class="fas fa-building mr-2 text-blue-600"></i>Distributor Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
                                <select name="vendor_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn Vendor --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('vendor_id', $project->vendor_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Distributor</label>
                                <input type="text" value="Tech Horizon Corporation" disabled
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-gray-600 cursor-not-allowed">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Distributor AM</label>
                                <input type="text" name="distributor_am" value="{{ old('distributor_am', $project->distributor_am) }}" readonly
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tên tiếng Việt <span class="text-red-500">*</span></label>
                                <input type="text" name="eu_name_vi" value="{{ old('eu_name_vi', $project->eu_name_vi) }}" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_name_vi') border-red-500 @enderror">
                                @error('eu_name_vi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tên tiếng Anh <span class="text-red-500">*</span></label>
                                <input type="text" name="eu_name_en" value="{{ old('eu_name_en', $project->eu_name_en) }}" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_name_en') border-red-500 @enderror">
                                @error('eu_name_en') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tên viết tắt</label>
                                <input type="text" name="eu_name_abbr" value="{{ old('eu_name_abbr', $project->eu_name_abbr) }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">MST / Website <span class="text-red-500">*</span></label>
                                <input type="text" name="eu_tax_code" id="eu_tax_code" value="{{ old('eu_tax_code', $project->eu_tax_code) }}" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_tax_code') border-red-500 @enderror">
                                @error('eu_tax_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ <span class="text-red-500">*</span></label>
                                <input type="text" name="address" value="{{ old('address', $project->address) }}" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('address') border-red-500 @enderror">
                                @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tỉnh / Thành phố <span class="text-red-500">*</span></label>
                                <select name="eu_province" id="eu_province" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_province') border-red-500 @enderror">
                                    <option value="">-- Đang tải danh sách... --</option>
                                </select>
                                @error('eu_province') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ngành nghề <span class="text-red-500">*</span></label>
                                @php
                                    $isCustomIndustry = !empty($project->eu_industry) && !array_key_exists($project->eu_industry, $industries);
                                    $selectedIndustryKey = $isCustomIndustry ? 'other' : $project->eu_industry;
                                    $customIndustryValue = $isCustomIndustry ? $project->eu_industry : '';
                                @endphp
                                <select name="eu_industry" id="eu_industry" required
                                    onchange="toggleIndustryOther()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('eu_industry') border-red-500 @enderror">
                                    <option value="">-- Chọn ngành nghề --</option>
                                    @foreach($industries as $key => $label)
                                        <option value="{{ $key }}" {{ old('eu_industry', $selectedIndustryKey) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('eu_industry') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                                <div id="eu_industry_other_container" class="mt-2 {{ old('eu_industry', $selectedIndustryKey) == 'other' ? '' : 'hidden' }}">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">
                                        Nhập ngành nghề khác <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="eu_industry_other" id="eu_industry_other"
                                        value="{{ old('eu_industry_other', $customIndustryValue) }}"
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
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Loại hợp tác <span class="text-red-500">*</span></label>
                                <select name="collaborate_type" id="collaborate_type" required onchange="toggleCollaborateType()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn --</option>
                                    <option value="partner" {{ old('collaborate_type', $project->collaborate_type) == 'partner' ? 'selected' : '' }}>Partner</option>
                                    <option value="end_user" {{ old('collaborate_type', $project->collaborate_type) == 'end_user' ? 'selected' : '' }}>End-user</option>
                                </select>
                            </div>

                            <!-- Partner mode toggle -->
                            <div id="partner_mode_toggle" class="md:col-span-2 {{ old('collaborate_type', $project->collaborate_type) == 'partner' ? '' : 'hidden' }}">
                                <div class="flex items-center gap-4 mb-3">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="partner_input_mode" value="existing"
                                            {{ old('partner_input_mode', $project->collaborate_customer_id ? 'existing' : 'new') === 'existing' ? 'checked' : '' }}
                                            onchange="togglePartnerInputMode('existing')"
                                            class="text-primary focus:ring-primary">
                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                            <i class="fas fa-search mr-1 text-blue-500"></i>Chọn từ danh sách khách hàng
                                        </span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="partner_input_mode" value="new"
                                            {{ old('partner_input_mode', $project->collaborate_customer_id ? 'existing' : 'new') === 'new' ? 'checked' : '' }}
                                            onchange="togglePartnerInputMode('new')"
                                            class="text-primary focus:ring-primary">
                                        <span class="ml-2 text-sm font-medium text-gray-700">
                                            <i class="fas fa-plus mr-1 text-green-500"></i>Tạo mới
                                        </span>
                                    </label>
                                </div>
                                <div id="partner_existing_select" class="{{ !$project->collaborate_customer_id && $project->collaborate_type == 'partner' ? 'hidden' : '' }}">
                                    <select name="collaborate_customer_id" id="collaborate_customer_id" onchange="fillPartnerData()"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">-- Tìm khách hàng (theo tên, MST) --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                data-name="{{ $customer->name }}" data-tax="{{ $customer->tax_code }}"
                                                data-phone="{{ $customer->phone }}" data-email="{{ $customer->email }}"
                                                {{ old('collaborate_customer_id', $project->collaborate_customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }} {{ $customer->tax_code ? '(MST: '.$customer->tax_code.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Chọn khách hàng để tự động điền thông tin bên dưới</p>
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
                                </div>

                                <!-- Inline Create Contact Point Form -->
                                <div id="new_contact_form" class="hidden mt-3 bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-indigo-800 mb-3"><i class="fas fa-user-plus mr-1"></i>Tạo mới Contact Point</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Họ <span class="text-red-500">*</span></label>
                                            <input type="text" id="new_contact_first_name" placeholder="Nguyễn Văn" class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Tên</label>
                                            <input type="text" id="new_contact_last_name" placeholder="A" class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                                            <input type="text" id="new_contact_position" placeholder="VD: Manager, Director..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Chức danh</label>
                                            <input type="text" id="new_contact_title" placeholder="VD: Mr., Mrs...." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">SĐT <span class="text-red-500">*</span></label>
                                            <input type="text" id="new_contact_phone" placeholder="09xxxxxxxx" class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                                            <input type="email" id="new_contact_email" placeholder="email@company.com" class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-300">
                                        </div>
                                    </div>
                                    <div class="flex gap-2 mt-3">
                                        <button type="button" onclick="saveNewContact()" class="px-4 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700 transition-colors"><i class="fas fa-save mr-1"></i>Lưu Contact</button>
                                        <button type="button" onclick="toggleNewContactForm()" class="px-4 py-1.5 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300 transition-colors">Hủy</button>
                                    </div>
                                    <div id="new_contact_error" class="hidden mt-2 text-red-600 text-xs"></div>
                                </div>
                            </div>

                            <!-- End-user notice -->
                            <div id="enduser_notice" class="md:col-span-2 {{ old('collaborate_type', $project->collaborate_type) == 'end_user' ? '' : 'hidden' }}">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                                        <div>
                                            <p class="text-sm font-medium text-blue-800">Chế độ End-user (trực tiếp)</p>
                                            <p class="text-xs text-blue-600 mt-1">
                                                Thông tin End-User ở Section B sẽ được sử dụng làm đối tác hợp tác.
                                                Thông tin EU chỉ lưu trên dự án, không tạo vào database khách hàng.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Partner fields -->
                            <div id="collab_company_wrap" class="{{ old('collaborate_type', $project->collaborate_type) == 'partner' ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tên công ty <span class="text-red-500">*</span></label>
                                <input type="text" name="collaborate_company" id="collaborate_company"
                                    value="{{ old('collaborate_company', $project->collaborate_company) }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_company') border-red-500 @enderror">
                                @error('collaborate_company') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_tax_wrap" class="{{ old('collaborate_type', $project->collaborate_type) == 'partner' ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mã số thuế <span class="text-red-500">*</span></label>
                                <input type="text" name="collaborate_tax_code" id="collaborate_tax_code"
                                    value="{{ old('collaborate_tax_code', $project->collaborate_tax_code) }}"
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
                            <div id="collab_pic_name_wrap" class="{{ old('collaborate_type', $project->collaborate_type) == 'partner' ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tên PIC <span class="text-red-500">*</span></label>
                                <input type="text" name="collaborate_pic_name" id="collaborate_pic_name"
                                    value="{{ old('collaborate_pic_name', $project->collaborate_pic_name) }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_pic_name') border-red-500 @enderror">
                                @error('collaborate_pic_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_title_wrap" class="{{ old('collaborate_type', $project->collaborate_type) == 'partner' ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Chức danh <span class="text-red-500">*</span></label>
                                <input type="text" name="collaborate_pic_title" id="collaborate_pic_title"
                                    value="{{ old('collaborate_pic_title', $project->collaborate_pic_title) }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_pic_title') border-red-500 @enderror">
                                @error('collaborate_pic_title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_phone_wrap" class="{{ old('collaborate_type', $project->collaborate_type) == 'partner' ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">SĐT PIC <span class="text-red-500">*</span></label>
                                <input type="text" name="collaborate_pic_phone" id="collaborate_pic_phone"
                                    value="{{ old('collaborate_pic_phone', $project->collaborate_pic_phone) }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('collaborate_pic_phone') border-red-500 @enderror">
                                @error('collaborate_pic_phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div id="collab_pic_email_wrap" class="{{ old('collaborate_type', $project->collaborate_type) == 'partner' ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email PIC</label>
                                <input type="email" name="collaborate_pic_email" id="collaborate_pic_email"
                                    value="{{ old('collaborate_pic_email', $project->collaborate_pic_email) }}"
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mã dự án <span class="text-red-500">*</span></label>
                                <input type="text" name="code" value="{{ old('code', $project->code) }}" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('code') border-red-500 @enderror">
                                @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tên dự án <span class="text-red-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $project->name) }}" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">BOM (Bill of Materials)</label>
                                @if($project->bom_file && is_array($project->bom_file) && count($project->bom_file) > 0)
                                    <div class="mb-3 space-y-2" id="existing_bom_container">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">File BOM hiện tại:</p>
                                        @foreach($project->bom_file as $file)
                                            @php
                                                $filename = basename($file);
                                                if (preg_match('/^\d+_(.+)$/', $filename, $matches)) {
                                                    $displayFilename = $matches[1];
                                                } else {
                                                    $displayFilename = $filename;
                                                }
                                            @endphp
                                            <div class="flex items-center justify-between p-2 bg-blue-50 rounded-lg border border-blue-100 text-sm text-blue-700 max-w-xl">
                                                <div class="flex items-center gap-2 overflow-hidden">
                                                    <i class="fas fa-paperclip flex-shrink-0"></i>
                                                    <a href="{{ Storage::url($file) }}" target="_blank" class="hover:underline truncate font-medium">{{ $displayFilename }}</a>
                                                    <input type="hidden" name="keep_bom_files[]" value="{{ $file }}">
                                                </div>
                                                <button type="button" onclick="this.closest('.flex').remove()" class="text-red-500 hover:text-red-700 p-1 flex-shrink-0 transition-colors">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <input type="file" name="bom_file[]" multiple accept=".xlsx,.xls,.pdf,.doc,.docx"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <textarea name="bom_data" rows="3" placeholder="Nhập BOM list..."
                                    class="mt-2 w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">{{ old('bom_data', $project->bom_data) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deal Type</label>
                                <select name="deal_type"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn --</option>
                                    <option value="new_buy" {{ old('deal_type', $project->deal_type) == 'new_buy' ? 'selected' : '' }}>New Buy</option>
                                    <option value="trade_up" {{ old('deal_type', $project->deal_type) == 'trade_up' ? 'selected' : '' }}>Trade Up</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Net to Tech Horizon</label>
                                <div class="relative">
                                    <input type="text" id="net_display" oninput="formatCurrency(this, 'net_to_tech_horizon')"
                                        value="{{ old('net_to_tech_horizon', $project->net_to_tech_horizon) ? number_format(old('net_to_tech_horizon', $project->net_to_tech_horizon)) : '' }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary pr-12">
                                    <span class="absolute right-3 top-2 text-gray-400 text-xs">VNĐ</span>
                                </div>
                                <input type="hidden" name="net_to_tech_horizon" id="net_to_tech_horizon" value="{{ old('net_to_tech_horizon', $project->net_to_tech_horizon ?? 0) }}">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                                <textarea name="description" rows="3"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">{{ old('description', $project->description) }}</textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                                <textarea name="note" rows="2"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary">{{ old('note', $project->note) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-tasks mr-2 text-primary"></i>Trạng thái <span class="text-red-500">*</span>
                        </label>
                        <select name="status" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="planning" {{ old('status', $project->status) == 'planning' ? 'selected' : '' }}>Lên kế hoạch</option>
                            <option value="in_progress" {{ old('status', $project->status) == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                            <option value="completed" {{ old('status', $project->status) == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="on_hold" {{ old('status', $project->status) == 'on_hold' ? 'selected' : '' }}>Tạm dừng</option>
                            <option value="cancelled" {{ old('status', $project->status) == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar-alt mr-2 text-primary"></i>Estimated Close Date <span class="text-red-500">*</span>
                        </h3>
                        <p class="text-xs text-gray-500 mb-2">Expired date hiện tại: <strong>{{ $project->end_date?->format('d/m/Y') ?? '-' }}</strong></p>
                        <div class="space-y-2">
                            @foreach([3, 6, 9] as $m)
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition-colors {{ old('estimated_close_months', $project->estimated_close_months) == $m ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                <input type="radio" name="estimated_close_months" value="{{ $m }}"
                                    {{ old('estimated_close_months', $project->estimated_close_months) == $m ? 'checked' : '' }}
                                    class="text-primary focus:ring-primary" onchange="updateExpiredPreview(this)">
                                <span class="ml-3 text-sm font-medium">+{{ $m }} tháng</span>
                                <span class="ml-auto text-xs text-gray-500">{{ ($project->start_date ?? now())->copy()->addMonths($m)->format('d/m/Y') }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('estimated_close_months') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <label class="block text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-layer-group mr-2 text-primary"></i>Stage</label>
                        <input type="text" name="stage" value="{{ old('stage', $project->stage) }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3"><i class="fas fa-money-bill-wave mr-2 text-primary"></i>Dự toán</h3>
                        <div class="relative">
                            <input type="text" id="budget_display" oninput="formatCurrency(this, 'budget')"
                                value="{{ number_format(old('budget', $project->budget)) }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary pr-12">
                            <span class="absolute right-3 top-2 text-gray-400 text-xs">VNĐ</span>
                        </div>
                        <input type="hidden" name="budget" id="budget" value="{{ old('budget', $project->budget) }}">
                    </div>

                    <input type="hidden" name="customer_id" value="{{ old('customer_id', $project->customer_id) }}">
                    <input type="hidden" name="manager_id" value="{{ $project->manager_id ?? auth()->id() }}">

                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <button type="submit"
                            class="w-full px-4 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark transition-all font-semibold text-sm shadow-sm flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i>Cập nhật dự án
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

        const collabFieldIds = [
            'collab_company_wrap', 'collab_tax_wrap',
            'collab_pic_name_wrap', 'collab_pic_title_wrap',
            'collab_pic_phone_wrap', 'collab_pic_email_wrap'
        ];

        // Provinces API
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
            const oldValue = @json(old('eu_province', $project->eu_province ?? ''));
            const cached = localStorage.getItem('vn_provinces');
            const cachedTime = localStorage.getItem('vn_provinces_time');
            if (cached && cachedTime && (Date.now() - parseInt(cachedTime)) < 86400000) {
                populateProvinces(JSON.parse(cached), oldValue);
                return;
            }
            fetch('https://provinces.open-api.vn/api/p/')
                .then(res => res.json())
                .then(data => {
                    const names = data.map(p => p.name).sort();
                    localStorage.setItem('vn_provinces', JSON.stringify(names));
                    localStorage.setItem('vn_provinces_time', Date.now().toString());
                    populateProvinces(names, oldValue);
                })
                .catch(() => populateProvinces(VN_PROVINCES_FALLBACK, oldValue));
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

        function toggleCollaborateType(isInit = false) {
            const type = document.getElementById('collaborate_type').value;
            const partnerToggle = document.getElementById('partner_mode_toggle');
            const enduserNotice = document.getElementById('enduser_notice');

            const companyInput = document.getElementById('collaborate_company');
            const taxInput = document.getElementById('collaborate_tax_code');
            const phoneInput = document.getElementById('collaborate_pic_phone');
            const nameInput = document.getElementById('collaborate_pic_name');
            const titleInput = document.getElementById('collaborate_pic_title');

            if (type === 'partner') {
                partnerToggle.classList.remove('hidden');
                enduserNotice.classList.add('hidden');
                showCollabFields();

                if (companyInput) companyInput.required = true;
                if (taxInput) taxInput.required = true;
                if (phoneInput) phoneInput.required = true;

                const mode = document.querySelector('input[name="partner_input_mode"]:checked')?.value || 'existing';
                togglePartnerInputMode(mode, isInit);
            } else if (type === 'end_user') {
                partnerToggle.classList.add('hidden');
                enduserNotice.classList.remove('hidden');
                hideCollabFields();
                hideContactPointSection();

                if (companyInput) companyInput.required = false;
                if (taxInput) taxInput.required = false;
                if (phoneInput) phoneInput.required = false;
                if (nameInput) nameInput.required = false;
                if (titleInput) titleInput.required = false;
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
            }
        }

        function showCollabFields() {
            collabFieldIds.forEach(id => { const el = document.getElementById(id); if (el) el.classList.remove('hidden'); });
        }

        function hideCollabFields() {
            collabFieldIds.forEach(id => { const el = document.getElementById(id); if (el) el.classList.add('hidden'); });
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
                document.getElementById('collaborate_customer_id').value = '';
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
            const sel = document.getElementById('collaborate_customer_id');
            const opt = sel.options[sel.selectedIndex];
            if (opt && opt.value) {
                document.getElementById('collaborate_company').value = opt.dataset.name || '';
                document.getElementById('collaborate_tax_code').value = opt.dataset.tax || '';
                if (!isInit) {
                    document.getElementById('collaborate_pic_phone').value = '';
                    document.getElementById('collaborate_pic_email').value = '';
                    document.getElementById('collaborate_pic_name').value = '';
                    document.getElementById('collaborate_pic_title').value = '';
                }
                document.getElementById('collaborate_company').readOnly = true;
                document.getElementById('collaborate_company').classList.add('bg-gray-50');
                document.getElementById('collaborate_tax_code').readOnly = true;
                document.getElementById('collaborate_tax_code').classList.add('bg-gray-50');
                loadContactPoints(opt.value);
            } else {
                enableCollabFields();
                hideContactPointSection();
            }
        }

        function enableCollabFields() {
            ['collaborate_company', 'collaborate_tax_code', 'collaborate_pic_name',
             'collaborate_pic_title', 'collaborate_pic_phone', 'collaborate_pic_email'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.readOnly = false; el.classList.remove('bg-gray-50'); }
            });
        }

        function clearCollabFields() {
            ['collaborate_company', 'collaborate_tax_code', 'collaborate_pic_name',
             'collaborate_pic_title', 'collaborate_pic_phone', 'collaborate_pic_email'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        }

        // Contact Point AJAX
        function loadContactPoints(customerId) {
            const section = document.getElementById('contact_point_section');
            const select = document.getElementById('contact_point_select');
            section.classList.remove('hidden');
            select.innerHTML = '<option value="">-- Đang tải... --</option>';
            fetch(`/ajax/customers/${customerId}/contacts`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(contacts => {
                select.innerHTML = '<option value="">-- Chọn người liên hệ --</option>';
                if (contacts.length === 0) select.innerHTML = '<option value="">-- Chưa có contact, hãy tạo mới --</option>';
                
                const currentPicName = document.getElementById('collaborate_pic_name').value.trim();
                const currentPicPhone = document.getElementById('collaborate_pic_phone').value.trim();

                contacts.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.id;
                    o.textContent = `${c.name || c.first_name} — ${c.position || ''} — ${c.phone || ''}`;
                    o.dataset.name = c.name || ''; o.dataset.position = c.position || '';
                    o.dataset.phone = c.phone || ''; o.dataset.email = c.email || '';
                    
                    if ((c.name && c.name === currentPicName) || (c.phone && c.phone === currentPicPhone)) {
                        o.selected = true;
                    }
                    select.appendChild(o);
                });
            })
            .catch(() => { select.innerHTML = '<option value="">-- Lỗi tải contacts --</option>'; });
        }

        function fillContactData() {
            const select = document.getElementById('contact_point_select');
            const opt = select.options[select.selectedIndex];
            if (opt && opt.value) {
                document.getElementById('collaborate_pic_name').value = opt.dataset.name || '';
                document.getElementById('collaborate_pic_title').value = opt.dataset.position || '';
                document.getElementById('collaborate_pic_phone').value = opt.dataset.phone || '';
                document.getElementById('collaborate_pic_email').value = opt.dataset.email || '';
            }
        }

        function hideContactPointSection() {
            const section = document.getElementById('contact_point_section');
            const form = document.getElementById('new_contact_form');
            if (section) section.classList.add('hidden');
            if (form) form.classList.add('hidden');
        }

        function toggleNewContactForm() {
            document.getElementById('new_contact_form').classList.toggle('hidden');
        }

        function saveNewContact() {
            const customerId = document.getElementById('collaborate_customer_id').value;
            if (!customerId) { alert('Vui lòng chọn khách hàng trước.'); return; }
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
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    loadContactPoints(customerId);
                    document.getElementById('collaborate_pic_name').value = result.contact.name || '';
                    document.getElementById('collaborate_pic_title').value = result.contact.position || '';
                    document.getElementById('collaborate_pic_phone').value = result.contact.phone || '';
                    document.getElementById('collaborate_pic_email').value = result.contact.email || '';
                    ['new_contact_first_name','new_contact_last_name','new_contact_position','new_contact_title','new_contact_phone','new_contact_email'].forEach(id => document.getElementById(id).value = '');
                    document.getElementById('new_contact_form').classList.add('hidden');
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Đã tạo contact mới!', timer: 1500, showConfirmButton: false });
                } else if (result.errors) {
                    errorDiv.innerHTML = Object.values(result.errors).flat().join('<br>');
                    errorDiv.classList.remove('hidden');
                }
            })
            .catch(() => { errorDiv.textContent = 'Lỗi kết nối.'; errorDiv.classList.remove('hidden'); });
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
            input.value = value !== '' ? parseInt(value).toLocaleString('en-US') : '';
        }

        let taxCheckTimer = null;
        const currentTaxCode = @json($project->eu_tax_code);
        function checkDuplicateTaxCode(taxCode) {
            const warningDiv = document.getElementById('eu_tax_warning');
            const warningDetail = document.getElementById('eu_tax_warning_detail');
            if (!taxCode || taxCode.length < 3 || taxCode === currentTaxCode) { warningDiv.classList.add('hidden'); return; }
            fetch(`{{ route('projects.check-tax-code') }}?tax_code=${encodeURIComponent(taxCode)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    warningDetail.textContent = `Khách hàng: ${data.customer.name} (MST: ${data.customer.tax_code})${data.customer.address ? ' — ' + data.customer.address : ''}`;
                    warningDiv.classList.remove('hidden');
                } else { warningDiv.classList.add('hidden'); }
            })
            .catch(() => warningDiv.classList.add('hidden'));
        }

        // === AJAX: Check duplicate Partner/Company MST/Tax Code ===
        let collabTaxCheckTimer = null;
        let collabTaxExists = false;
        const projectCollabTaxCode = @json($project->collaborate_tax_code ?? '');
        function checkCollabDuplicateTaxCode(taxCode) {
            const warningDiv = document.getElementById('collab_tax_warning');
            const warningDetail = document.getElementById('collab_tax_warning_detail');

            if (!taxCode || taxCode.length < 3 || (projectCollabTaxCode && taxCode.toLowerCase() === projectCollabTaxCode.toLowerCase())) {
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

        document.addEventListener('DOMContentLoaded', function() {
            loadProvinces();
            toggleIndustryOther();

            const type = document.getElementById('collaborate_type').value;
            if (type) toggleCollaborateType(true);

            const euTaxInput = document.getElementById('eu_tax_code');
            if (euTaxInput) {
                euTaxInput.addEventListener('input', function() {
                    clearTimeout(taxCheckTimer);
                    taxCheckTimer = setTimeout(() => checkDuplicateTaxCode(this.value.trim()), 500);
                });
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
                                title: 'Không thể cập nhật dự án!',
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

            // Auto-load contacts if partner is already selected
            const collabCustomerId = document.getElementById('collaborate_customer_id');
            if (collabCustomerId && collabCustomerId.value) {
                loadContactPoints(collabCustomerId.value);
            }

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
                    setTimeout(() => { firstError.scrollIntoView({ behavior: 'smooth', block: 'center' }); firstError.focus(); }, 500);
                }
            @endif
        });
    </script>
@endsection