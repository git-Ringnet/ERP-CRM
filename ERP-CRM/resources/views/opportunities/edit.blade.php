@extends('layouts.app')

@section('title', 'Chỉnh sửa hoạt động cơ hội')
@section('page-title', 'Chỉnh sửa hoạt động cơ hội')

@section('content')
    <div class="max-w-8xl">
        <form action="{{ route('opportunities.update', $opportunity->id) }}" method="POST" id="opportunity_form" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Cột trái: Form Fields -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- SECTION 1: Thông tin khách hàng -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100 flex items-center">
                            <i class="fas fa-user-tie mr-2 text-blue-600"></i>Thông tin khách hàng
                        </h2>

                        <!-- Radio SI / EU -->
                        <div class="flex items-center gap-6 mb-6">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="customer_type" value="si" {{ old('customer_type', $opportunity->customer_type) === 'si' ? 'checked' : '' }}
                                    onchange="toggleCustomerType('si')"
                                    class="text-primary focus:ring-primary h-4 w-4">
                                <span class="ml-2 text-sm font-medium text-gray-700">SI (System Integrator)</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="customer_type" value="eu" {{ old('customer_type', $opportunity->customer_type) === 'eu' ? 'checked' : '' }}
                                    onchange="toggleCustomerType('eu')"
                                    class="text-primary focus:ring-primary h-4 w-4">
                                <span class="ml-2 text-sm font-medium text-gray-700">EU (End User)</span>
                            </label>
                        </div>

                        <!-- SI Mode Fields -->
                        <div id="si_fields" class="{{ old('customer_type', $opportunity->customer_type) === 'si' ? '' : 'hidden' }} space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Company Select -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Chọn công ty <span class="text-red-500">*</span>
                                        </label>
                                        <button type="button" onclick="openNewCompanyModal()"
                                            class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors text-xs flex items-center gap-1 font-medium whitespace-nowrap">
                                            <i class="fas fa-plus"></i>Tạo mới Company
                                        </button>
                                    </div>
                                    <select name="customer_id" id="customer_id" onchange="loadContacts()"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                        <option value="">-- Chọn Company --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id', $opportunity->customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>

                                <!-- Contact Point Select -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-sm font-medium text-gray-700">
                                            Chọn Contact Point <span class="text-red-500">*</span>
                                        </label>
                                        <button type="button" id="btn_new_contact" onclick="openNewContactModal()"
                                            class="px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100 transition-colors text-xs flex items-center gap-1 font-medium whitespace-nowrap">
                                            <i class="fas fa-plus"></i>Tạo Contact
                                        </button>
                                    </div>
                                    <select name="contact_id" id="contact_id" onchange="fillContactInfo()"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                        <option value="">-- Chọn Contact Point --</option>
                                    </select>
                                    @error('contact_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <!-- Read-only Contact Details for SI -->
                            <div id="contact_details_box" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-4 mt-3">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Thông tin người liên hệ (Contact Point)</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-700">
                                    <div><span class="text-gray-400 block text-xs">Họ tên:</span> <strong id="si_c_name">-</strong></div>
                                    <div><span class="text-gray-400 block text-xs">Chức danh:</span> <strong id="si_c_position">-</strong></div>
                                    <div><span class="text-gray-400 block text-xs">SĐT:</span> <strong id="si_c_phone">-</strong></div>
                                    <div><span class="text-gray-400 block text-xs">Email:</span> <strong id="si_c_email">-</strong></div>
                                </div>
                            </div>
                        </div>

                        <!-- EU Mode Fields -->
                        <div id="eu_fields" class="{{ old('customer_type', $opportunity->customer_type) === 'eu' ? '' : 'hidden' }} space-y-4">
                            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm mb-4">
                                <i class="fas fa-info-circle mr-2"></i><strong>Lưu ý:</strong> Thông tin End User (EU) chỉ được lưu trữ trong hoạt động cơ hội này và phục vụ việc theo dõi hoạt động hiện tại. Hệ thống sẽ KHÔNG tự động tạo bản ghi mới vào master database Partner/Customer.
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên công ty EU <span class="text-red-500">*</span></label>
                                    <input type="text" name="eu_company_name" id="eu_company_name" value="{{ old('eu_company_name', $opportunity->eu_company_name) }}"
                                        placeholder="Nhập tên công ty End User"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                    @error('eu_company_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên người liên hệ EU</label>
                                    <input type="text" name="eu_contact_name" id="eu_contact_name" value="{{ old('eu_contact_name', $opportunity->eu_contact_name) }}"
                                        placeholder="Họ và tên người liên hệ"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Chức vụ EU</label>
                                    <input type="text" name="eu_position" id="eu_position" value="{{ old('eu_position', $opportunity->eu_position) }}"
                                        placeholder="VD: IT Manager, CTO..."
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại EU</label>
                                    <input type="text" name="eu_phone" id="eu_phone" value="{{ old('eu_phone', $opportunity->eu_phone) }}"
                                        placeholder="09xxxxxxxx"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email EU</label>
                                    <input type="email" name="eu_email" id="eu_email" value="{{ old('eu_email', $opportunity->eu_email) }}"
                                        placeholder="email@company.com"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- SECTION 2: Thông tin hoạt động -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-orange-600"></i>Thông tin hoạt động
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Chủ đề / Tên hoạt động -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Chủ đề / Tên hoạt động <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name', $opportunity->name) }}" required
                                    placeholder="Ví dụ: Demo tường lửa Sophos cho TechHorizon..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Loại hoạt động -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Loại hoạt động <span class="text-red-500">*</span>
                                </label>
                                <select name="activity_type" id="activity_type" onchange="toggleActivityTypeOther(); toggleFilesAsterisk();" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                    <option value="">-- Chọn loại hoạt động --</option>
                                    @foreach($activityTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('activity_type', $opportunity->activity_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('activity_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Loại hoạt động khác (hidden) -->
                            <div id="activity_type_other_wrap" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Mô tả loại hoạt động khác <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="activity_type_other" id="activity_type_other" value="{{ old('activity_type_other', $opportunity->activity_type_other) }}"
                                    placeholder="Nhập loại hoạt động khác..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                @error('activity_type_other') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Ngày diễn ra -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Ngày diễn ra <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="activity_date" value="{{ old('activity_date', $opportunity->activity_date ? $opportunity->activity_date->format('Y-m-d') : '') }}" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                @error('activity_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Thời gian bắt đầu -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Thời gian bắt đầu <span class="text-red-500">*</span>
                                </label>
                                <input type="time" name="start_time" id="start_time" value="{{ old('start_time', $opportunity->start_time) }}" required
                                    onchange="calculateDuration()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                @error('start_time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Thời gian kết thúc -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Thời gian kết thúc <span class="text-red-500">*</span>
                                </label>
                                <input type="time" name="end_time" id="end_time" value="{{ old('end_time', $opportunity->end_time) }}" required
                                    onchange="calculateDuration()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                @error('end_time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Duration (Calculated, Read-only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Thời lượng (Phút)</label>
                                <input type="text" id="duration_display" value="60 phút" readonly
                                    class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-600 focus:outline-none cursor-not-allowed">
                            </div>

                            <!-- Chi tiết hoạt động -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung chi tiết hoạt động</label>
                                <textarea name="description" rows="3" placeholder="Mục đích buổi làm việc, nội dung trao đổi..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('description', $opportunity->description) }}</textarea>
                            </div>

                            <!-- Ghi chú -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú nội bộ</label>
                                <textarea name="notes" rows="2" placeholder="Ghi chú thêm về khách hàng hoặc thông tin bên lề..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('notes', $opportunity->notes) }}</textarea>
                            </div>

                            <!-- Yêu cầu chuẩn bị materials -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Yêu cầu chuẩn bị (Materials/Documents)</label>
                                <textarea name="materials_required" rows="2" placeholder="VD: Slide giải pháp, máy demo firewall..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('materials_required', $opportunity->materials_required) }}</textarea>
                            </div>

                            <!-- Quà tặng / giveaway -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quà tặng / Giveaway cho khách hàng</label>
                                <textarea name="giveaway" rows="2" placeholder="VD: Lịch công ty, sổ tay Horizon..."
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('giveaway', $opportunity->giveaway) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Phối hợp kỹ thuật -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100 flex items-center">
                            <i class="fas fa-cogs mr-2 text-indigo-600"></i>Phối hợp kỹ thuật
                        </h2>

                        <div class="space-y-4">
                            <!-- Toggle switch -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-medium text-gray-800">Cần kỹ thuật (Presales/Technical) phối hợp đi cùng?</span>
                                    <p class="text-xs text-gray-500">Bật lên để chỉ định Technical Manager hỗ trợ hoạt động này.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="needs_technical" id="needs_technical" value="1"
                                        {{ old('needs_technical', $opportunity->needs_technical) ? 'checked' : '' }}
                                        onchange="toggleTechnicalSelect()" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            <!-- Technical User Select (hidden by default) -->
                            <div id="technical_user_wrap" class="{{ old('needs_technical', $opportunity->needs_technical) ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Chọn người phối hợp kỹ thuật <span class="text-red-500">*</span>
                                </label>
                                <select name="technical_user_id" id="technical_user_id"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                    <option value="">-- Chọn kỹ sư phối hợp --</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('technical_user_id', $opportunity->technical_user_id) == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('technical_user_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: Tài liệu đính kèm -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100 flex items-center">
                            <i class="fas fa-paperclip mr-2 text-gray-600"></i>Tài liệu đính kèm
                        </h2>
                        
                        <div class="space-y-4">
                            <!-- List existing files -->
                            @if($opportunity->attachments->count() > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tài liệu đã đính kèm:</label>
                                    <div class="space-y-2" id="existing_attachments_container">
                                        @foreach($opportunity->attachments as $attachment)
                                            <div class="flex items-center justify-between text-xs p-2 bg-gray-50 border border-gray-200 rounded" id="edit_attachment_row_{{ $attachment->id }}">
                                                <span class="font-semibold text-gray-700 truncate max-w-xs flex items-center">
                                                    <i class="{{ $attachment->file_icon }} mr-1.5 text-gray-500"></i>
                                                    {{ $attachment->file_name }}
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-gray-400 text-[10px]">{{ $attachment->file_size_formatted }}</span>
                                                    <button type="button" onclick="deleteEditAttachment({{ $attachment->id }})" class="text-red-500 hover:text-red-700 p-1">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tải thêm tệp tin đính kèm <span id="files_required_asterisk" class="text-red-500 hidden">*</span></label>
                                <div id="edit_dropzone" class="border-2 border-dashed border-gray-300 rounded-lg p-5 text-center hover:bg-gray-50/50 hover:border-primary transition-colors cursor-pointer relative">
                                    <input type="file" name="files[]" id="edit_file_input" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-1"></i>
                                    <p class="text-sm font-semibold text-gray-700">Kéo thả file vào đây hoặc nhấp để chọn</p>
                                    <p class="text-xs text-gray-500 mt-1">Hỗ trợ PDF, Excel, Word, Hình ảnh (JPG, PNG) tối đa 10MB/file</p>
                                </div>
                                <div id="edit_file_list_preview" class="space-y-2 mt-3 hidden">
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase text-gray-400">Tập tin chuẩn bị tải lên:</h4>
                                    <div id="edit_preview_items" class="space-y-1.5"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Cột phải: Sidebar Trạng thái & Submit -->
                <div class="space-y-6">
                    <!-- Trạng thái -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-tasks mr-2 text-blue-600"></i>Trạng thái hoạt động <span class="text-red-500">*</span>
                        </label>
                        <select name="status" id="status_select" required onchange="toggleCancelReason()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                            <option value="draft" {{ old('status', $opportunity->status) === 'draft' ? 'selected' : '' }}>Nháp (Draft)</option>
                            <option value="planned" {{ old('status', $opportunity->status) === 'planned' ? 'selected' : '' }}>Đã lên lịch (Planned)</option>
                            <option value="confirmed" {{ old('status', $opportunity->status) === 'confirmed' ? 'selected' : '' }}>Đã xác nhận (Confirmed)</option>
                            <option value="in_progress" {{ old('status', $opportunity->status) === 'in_progress' ? 'selected' : '' }}>Đang thực hiện (In Progress)</option>
                            <option value="completed" {{ old('status', $opportunity->status) === 'completed' ? 'selected' : '' }}>Đã hoàn thành (Completed)</option>
                            <option value="cancelled" {{ old('status', $opportunity->status) === 'cancelled' ? 'selected' : '' }}>Đã hủy (Cancelled)</option>
                            <option value="postponed" {{ old('status', $opportunity->status) === 'postponed' ? 'selected' : '' }}>Đã hoãn (Postponed)</option>
                        </select>
                        @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                        <!-- Lý do hủy -->
                        <div id="cancel_reason_box" class="mt-4 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Lý do hủy <span class="text-red-500">*</span>
                            </label>
                            <textarea name="cancel_reason" id="cancel_reason" rows="2"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Nhập lý do hủy hoạt động...">{{ old('cancel_reason', $opportunity->cancel_reason) }}</textarea>
                            @error('cancel_reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Người phụ trách (PIC) -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            <i class="fas fa-user mr-2 text-blue-600"></i>Người phụ trách <span class="text-red-500">*</span>
                        </label>
                        <select name="assigned_to" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_to', $opportunity->assigned_to) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Actions -->
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <button type="submit"
                            class="w-full px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg transition-all font-semibold text-sm shadow-sm flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i>Cập nhật cơ hội
                        </button>
                        <a href="{{ route('opportunities.show', $opportunity->id) }}"
                            class="mt-3 w-full inline-block text-center px-4 py-2 text-gray-500 hover:text-gray-700 transition-colors text-sm font-medium">
                            Hủy bỏ
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ===================================================================
         MODALS (Company & Contact)
         =================================================================== -->

    <!-- Modal tạo Company mới -->
    <div id="new_company_modal" class="fixed inset-0 z-50 overflow-y-auto hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 w-full max-w-lg overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-base font-bold text-gray-800"><i class="fas fa-building mr-2 text-blue-600"></i>Tạo mới Company</h3>
                <button type="button" onclick="closeNewCompanyModal()" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên công ty <span class="text-red-500">*</span></label>
                    <input type="text" id="modal_comp_name" placeholder="Tên công ty chính thức..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã số thuế / MST <span class="text-red-500">*</span></label>
                    <input type="text" id="modal_comp_tax" placeholder="Mã số thuế doanh nghiệp..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                        <input type="text" id="modal_comp_phone" placeholder="SĐT công ty..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="modal_comp_email" placeholder="company@domain.com"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ trụ sở</label>
                    <input type="text" id="modal_comp_address" placeholder="Địa chỉ chi tiết..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div id="company_modal_error" class="hidden text-red-500 text-xs mt-2"></div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" onclick="closeNewCompanyModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300 transition-colors">Hủy</button>
                <button type="button" onclick="saveNewCompany()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors">Lưu Company</button>
            </div>
        </div>
    </div>

    <!-- Modal tạo Contact mới -->
    <div id="new_contact_modal" class="fixed inset-0 z-50 overflow-y-auto hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 w-full max-w-lg overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-base font-bold text-gray-800"><i class="fas fa-user-plus mr-2 text-green-600"></i>Thêm mới Contact Point</h3>
                <button type="button" onclick="closeNewContactModal()" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Họ & tên đệm <span class="text-red-500">*</span></label>
                        <input type="text" id="modal_c_first_name" placeholder="VD: Nguyễn Văn"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tên riêng</label>
                        <input type="text" id="modal_c_last_name" placeholder="VD: A"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chức vụ <span class="text-red-500">*</span></label>
                        <input type="text" id="modal_c_position" placeholder="VD: IT Admin, Director..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chức danh xưng hô</label>
                        <input type="text" id="modal_c_title" placeholder="VD: Mr, Ms..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                        <input type="text" id="modal_c_phone" placeholder="SĐT..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" id="modal_c_email" placeholder="example@company.com"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>
                <div id="contact_modal_error" class="hidden text-red-500 text-xs mt-2"></div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" onclick="closeNewContactModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300 transition-colors">Hủy</button>
                <button type="button" onclick="saveNewContact()" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition-colors">Lưu Contact</button>
            </div>
        </div>
    </div>

    <!-- ===================================================================
         JAVASCRIPT
         =================================================================== -->
    <script>
        // Toggle SI / EU views
        function toggleCustomerType(type) {
            const siFields = document.getElementById('si_fields');
            const euFields = document.getElementById('eu_fields');
            
            const customerSelect = document.getElementById('customer_id');
            const contactSelect = document.getElementById('contact_id');
            
            const euCompanyInput = document.getElementById('eu_company_name');

            if (type === 'si') {
                siFields.classList.remove('hidden');
                euFields.classList.add('hidden');
                
                customerSelect.setAttribute('required', 'required');
                contactSelect.setAttribute('required', 'required');
                euCompanyInput.removeAttribute('required');
            } else {
                siFields.classList.add('hidden');
                euFields.classList.remove('hidden');
                
                customerSelect.removeAttribute('required');
                contactSelect.removeAttribute('required');
                euCompanyInput.setAttribute('required', 'required');
            }
        }

        // Load contact points via AJAX
        function loadContacts(selectedContactId = null) {
            const customerId = document.getElementById('customer_id').value;
            const contactSelect = document.getElementById('contact_id');
            const btnNewContact = document.getElementById('btn_new_contact');
            const contactDetailsBox = document.getElementById('contact_details_box');

            if (!customerId) {
                contactSelect.innerHTML = '<option value="">-- Vui lòng chọn Company trước --</option>';
                contactSelect.setAttribute('disabled', 'disabled');
                contactSelect.classList.add('bg-gray-50', 'cursor-not-allowed');
                btnNewContact.setAttribute('disabled', 'disabled');
                btnNewContact.classList.add('opacity-50', 'cursor-not-allowed');
                contactDetailsBox.classList.add('hidden');
                return;
            }

            contactSelect.innerHTML = '<option value="">-- Đang tải... --</option>';
            contactSelect.removeAttribute('disabled');
            contactSelect.classList.remove('bg-gray-50', 'cursor-not-allowed');
            btnNewContact.removeAttribute('disabled');
            btnNewContact.classList.remove('opacity-50', 'cursor-not-allowed');

            fetch(`/ajax/customers/${customerId}/contacts`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(contacts => {
                contactSelect.innerHTML = '<option value="">-- Chọn Contact Point --</option>';
                
                contacts.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = `${c.name || (c.first_name + ' ' + (c.last_name || ''))} - ${c.position || ''} (${c.phone || ''})`;
                    opt.dataset.name = c.name || (c.first_name + ' ' + (c.last_name || ''));
                    opt.dataset.position = c.position || '';
                    opt.dataset.phone = c.phone || '';
                    opt.dataset.email = c.email || '';
                    
                    if (selectedContactId && c.id == selectedContactId) {
                        opt.selected = true;
                    }
                    contactSelect.appendChild(opt);
                });

                if (selectedContactId) {
                    fillContactInfo();
                } else {
                    contactDetailsBox.classList.add('hidden');
                }
            })
            .catch(() => {
                contactSelect.innerHTML = '<option value="">-- Lỗi tải contacts --</option>';
            });
        }

        // Fill selected contact info into the read-only box
        function fillContactInfo() {
            const select = document.getElementById('contact_id');
            const box = document.getElementById('contact_details_box');
            const option = select.options[select.selectedIndex];

            if (option && option.value) {
                document.getElementById('si_c_name').textContent = option.dataset.name || '-';
                document.getElementById('si_c_position').textContent = option.dataset.position || '-';
                document.getElementById('si_c_phone').textContent = option.dataset.phone || '-';
                document.getElementById('si_c_email').textContent = option.dataset.email || '-';
                box.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
            }
        }

        // Toggle "Hoạt động khác" input
        function toggleActivityTypeOther() {
            const typeSelect = document.getElementById('activity_type');
            const otherWrap = document.getElementById('activity_type_other_wrap');
            const otherInput = document.getElementById('activity_type_other');

            if (typeSelect.value === 'other') {
                otherWrap.classList.remove('hidden');
                otherInput.setAttribute('required', 'required');
            } else {
                otherWrap.classList.add('hidden');
                otherInput.removeAttribute('required');
            }
        }

        // Calculate time duration
        function calculateDuration() {
            const startStr = document.getElementById('start_time').value;
            const endStr = document.getElementById('end_time').value;
            const display = document.getElementById('duration_display');

            if (!startStr || !endStr) return;

            const [startH, startM] = startStr.split(':').map(Number);
            const [endH, endM] = endStr.split(':').map(Number);

            let diffMinutes = (endH * 60 + endM) - (startH * 60 + startM);
            
            if (diffMinutes < 0) {
                display.value = "Giờ kết thúc phải lớn hơn giờ bắt đầu";
                display.classList.add('text-red-500');
            } else {
                display.value = `${diffMinutes} phút`;
                display.classList.remove('text-red-500');
            }
        }

        // Toggle technical select
        function toggleTechnicalSelect() {
            const checked = document.getElementById('needs_technical').checked;
            const wrap = document.getElementById('technical_user_wrap');
            const select = document.getElementById('technical_user_id');

            if (checked) {
                wrap.classList.remove('hidden');
                select.setAttribute('required', 'required');
                
                // Tự động assign sang Technical Manager nếu chưa chọn ai
                const techManagerId = "{{ $technicalManagerId ?? '' }}";
                if (techManagerId && !select.value) {
                    select.value = techManagerId;
                }
            } else {
                wrap.classList.add('hidden');
                select.removeAttribute('required');
            }
        }

        // ===================================================================
        // Modals Management (Company & Contact)
        // ===================================================================
        function openNewCompanyModal() {
            document.getElementById('new_company_modal').classList.remove('hidden');
        }
        function closeNewCompanyModal() {
            document.getElementById('new_company_modal').classList.add('hidden');
            document.getElementById('company_modal_error').classList.add('hidden');
        }

        function openNewContactModal() {
            document.getElementById('new_contact_modal').classList.remove('hidden');
        }
        function closeNewContactModal() {
            document.getElementById('new_contact_modal').classList.add('hidden');
            document.getElementById('contact_modal_error').classList.add('hidden');
        }

        // Save inline quick Company
        function saveNewCompany() {
            const name = document.getElementById('modal_comp_name').value.trim();
            const tax_code = document.getElementById('modal_comp_tax').value.trim();
            const phone = document.getElementById('modal_comp_phone').value.trim();
            const email = document.getElementById('modal_comp_email').value.trim();
            const address = document.getElementById('modal_comp_address').value.trim();
            const errorDiv = document.getElementById('company_modal_error');

            if (!name || !tax_code) {
                errorDiv.textContent = "Vui lòng nhập tên công ty và mã số thuế.";
                errorDiv.classList.remove('hidden');
                return;
            }

            errorDiv.classList.add('hidden');

            fetch(`/ajax/customers`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name, tax_code, phone, email, address })
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    const select = document.getElementById('customer_id');
                    const opt = document.createElement('option');
                    opt.value = result.customer.id;
                    opt.textContent = result.customer.name;
                    select.appendChild(opt);
                    select.value = result.customer.id;
                    
                    ['modal_comp_name', 'modal_comp_tax', 'modal_comp_phone', 'modal_comp_email', 'modal_comp_address'].forEach(id => {
                        document.getElementById(id).value = '';
                    });
                    closeNewCompanyModal();
                    
                    loadContacts();
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'Đã tạo Company mới!', timer: 1500, showConfirmButton: false });
                    }
                } else {
                    errorDiv.textContent = result.message || 'Lỗi khi tạo Company.';
                    errorDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                errorDiv.textContent = 'Lỗi kết nối hoặc Mã số thuế đã tồn tại trong hệ thống.';
                errorDiv.classList.remove('hidden');
            });
        }

        // Save inline Contact
        function saveNewContact() {
            const customerId = document.getElementById('customer_id').value;
            const errorDiv = document.getElementById('contact_modal_error');

            if (!customerId) {
                alert('Vui lòng chọn Company trước!');
                return;
            }

            const first_name = document.getElementById('modal_c_first_name').value.trim();
            const last_name = document.getElementById('modal_c_last_name').value.trim();
            const position = document.getElementById('modal_c_position').value.trim();
            const title = document.getElementById('modal_c_title').value.trim();
            const phone = document.getElementById('modal_c_phone').value.trim();
            const email = document.getElementById('modal_c_email').value.trim();

            if (!first_name || !position || !phone || !email) {
                errorDiv.textContent = "Vui lòng nhập Họ, Chức vụ, SĐT và Email của contact.";
                errorDiv.classList.remove('hidden');
                return;
            }

            errorDiv.classList.add('hidden');

            fetch(`/ajax/customers/${customerId}/contacts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ first_name, last_name, position, title, phone, email })
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    loadContacts(result.contact.id);
                    
                    ['modal_c_first_name', 'modal_c_last_name', 'modal_c_position', 'modal_c_title', 'modal_c_phone', 'modal_c_email'].forEach(id => {
                        document.getElementById(id).value = '';
                    });
                    closeNewContactModal();
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'Đã tạo Contact mới!', timer: 1500, showConfirmButton: false });
                    }
                } else {
                    errorDiv.textContent = result.message || 'Lỗi khi tạo Contact.';
                    errorDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                errorDiv.textContent = 'Lỗi kết nối, vui lòng thử lại.';
                errorDiv.classList.remove('hidden');
            });
        }

        function toggleCancelReason() {
            const statusSelect = document.getElementById('status_select');
            const cancelBox = document.getElementById('cancel_reason_box');
            const cancelInput = document.getElementById('cancel_reason');

            if (statusSelect.value === 'cancelled') {
                cancelBox.classList.remove('hidden');
                cancelInput.setAttribute('required', 'required');
            } else {
                cancelBox.classList.add('hidden');
                cancelInput.removeAttribute('required');
            }
        }

        function toggleFilesAsterisk() {
            const typeSelect = document.getElementById('activity_type');
            const asterisk = document.getElementById('files_required_asterisk');
            if (asterisk) {
                if (typeSelect.value === 'project_meeting') {
                    asterisk.classList.remove('hidden');
                } else {
                    asterisk.classList.add('hidden');
                }
            }
        }

        // File selection preview for Edit view
        const editFileInput = document.getElementById('edit_file_input');
        const editPreviewDiv = document.getElementById('edit_file_list_preview');
        const editPreviewItems = document.getElementById('edit_preview_items');

        if (editFileInput) {
            editFileInput.addEventListener('change', function() {
                const files = this.files;
                editPreviewItems.innerHTML = '';
                
                if (files.length > 0) {
                    editPreviewDiv.classList.remove('hidden');
                    Array.from(files).forEach(file => {
                        const size = (file.size / 1024).toFixed(1);
                        const item = document.createElement('div');
                        item.className = 'flex items-center justify-between text-xs p-2 bg-gray-50 border border-gray-200 rounded';
                        item.innerHTML = `
                            <span class="font-semibold text-gray-700 truncate max-w-xs"><i class="far fa-file mr-1 text-gray-400"></i>${file.name}</span>
                            <span class="text-gray-400">${size >= 1024 ? (size / 1024).toFixed(1) + ' MB' : size + ' KB'}</span>
                        `;
                        editPreviewItems.appendChild(item);
                    });
                } else {
                    editPreviewDiv.classList.add('hidden');
                }
            });
        }

        // Delete attachment via AJAX in Edit view
        function deleteEditAttachment(id) {
            if (!confirm('Bạn có chắc chắn muốn xóa file đính kèm này?')) return;

            fetch(`/opportunity-attachments/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    const row = document.getElementById(`edit_attachment_row_${id}`);
                    if (row) row.remove();
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: result.message, timer: 1500, showConfirmButton: false });
                    }
                } else {
                    alert(result.message || 'Lỗi khi xóa file.');
                }
            })
            .catch(() => alert('Lỗi kết nối khi xóa file.'));
        }

        document.getElementById('opportunity_form').addEventListener('submit', function(e) {
            const activityType = document.getElementById('activity_type').value;
            if (activityType === 'project_meeting') {
                const fileInput = document.getElementById('edit_file_input');
                const existingContainer = document.getElementById('existing_attachments_container');
                const existingCount = existingContainer ? existingContainer.children.length : 0;
                const newCount = fileInput ? fileInput.files.length : 0;

                if (existingCount + newCount === 0) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Thiếu tài liệu đính kèm',
                            text: 'Đối với hoạt động "Meeting liên quan đến dự án", bạn bắt buộc phải đính kèm ít nhất một hình ảnh, biên bản meeting hoặc proposal ở phần Tài liệu đính kèm.',
                            confirmButtonColor: '#3085d6'
                        });
                    } else {
                        alert('Đối với hoạt động "Meeting liên quan đến dự án", bạn bắt buộc phải đính kèm ít nhất một hình ảnh, biên bản meeting hoặc proposal ở phần Tài liệu đính kèm.');
                    }
                    return false;
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            calculateDuration();
            toggleActivityTypeOther();
            toggleCancelReason();
            toggleFilesAsterisk();
            
            const currentCustType = "{{ old('customer_type', $opportunity->customer_type) }}";
            toggleCustomerType(currentCustType);

            // Pre-load and select contact point
            const prefillCustId = "{{ old('customer_id', $opportunity->customer_id) }}";
            const prefillContactId = "{{ old('contact_id', $opportunity->contact_id) }}";
            if (prefillCustId) {
                loadContacts(prefillContactId);
            }
        });
    </script>
@endsection