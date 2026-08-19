@extends('layouts.app')

@section('title', 'Chỉnh sửa Ticket Kỹ thuật')
@section('page-title', 'Chỉnh sửa Ticket Kỹ thuật')

@section('content')
    @push('styles')
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>
    @endpush

    <div class="" x-data="{ 
            workType: '{{ old('work_type', $ticket->work_type) }}',
            openSales: false, salesSearch: '', salesOwnerId: '{{ old('sales_owner_id', $ticket->sales_owner_id) }}', salesOwnerName: '', salesTyping: false,
            openLead: false, leadSearch: '', teamLeadId: '{{ old('team_lead_id', $ticket->team_lead_id) }}', teamLeadName: '', leadTyping: false,
            openEng: false, engSearch: '', assignedTo: '{{ is_array(old('assigned_to', $ticket->assigned_to)) ? (collect(old('assigned_to', $ticket->assigned_to))->first() ?? '') : old('assigned_to', $ticket->assigned_to) }}', assignedToName: '', engTyping: false,

            usersList: window.editTicketUsers || [],
            engineersList: window.editTicketEngineers || [],

            init() {
                var user = this.usersList.find(function(u){ return u.id == this.salesOwnerId; }.bind(this));
                this.salesOwnerName = user ? user.name : '';
                this.salesSearch = this.salesOwnerName;

                var lead = this.usersList.find(function(u){ return u.id == this.teamLeadId; }.bind(this));
                this.teamLeadName = lead ? lead.name : '';
                this.leadSearch = this.teamLeadName;

                var eng = this.usersList.find(function(u){ return u.id == this.assignedTo; }.bind(this));
                this.assignedToName = eng ? eng.name : '';
                this.engSearch = this.assignedToName;
            }
        }">
        <div class="flex items-center space-x-2">
            <a href="{{ route('technical-tickets.show', $ticket->id) }}"
                class="text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <h2 class="text-lg font-bold text-gray-900">Chỉnh sửa Ticket: {{ $ticket->code }}</h2>
        </div>

        <form method="POST" action="{{ route('technical-tickets.update', $ticket->id) }}" enctype="multipart/form-data"
            class="space-y-6">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start space-x-3 shadow-sm">
                    <span class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0 mt-0.5">
                        <i class="fas fa-exclamation-circle text-xs"></i>
                    </span>
                    <div>
                        <h4 class="text-sm font-bold text-red-800 mb-1">Vui lòng kiểm tra lại các thông tin:</h4>
                        <ul class="list-disc list-inside text-xs text-red-700 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- CARD 1: THÔNG TIN CHUNG (COMMON INFO) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 rounded-t-xl flex items-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mr-2"></span>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Thông tin chung</h3>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Ticket Title -->
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">Tiêu đề / Tên công
                                việc <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="title" required
                                placeholder="Ví dụ: Triển khai tường lửa Fortigate cho Khách hàng A"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary @error('title') border-red-500 @enderror"
                                value="{{ old('title', $ticket->title) }}">
                            @error('title')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Work Type (Loại ticket) -->
                        <div>
                            <label for="work_type" class="block text-sm font-semibold text-gray-700 mb-1">Loại Ticket
                                <span class="text-red-500">*</span></label>
                            <select name="work_type" id="work_type" x-model="workType" required
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary @error('work_type') border-red-500 @enderror">
                                <option value="">-- Chọn loại ticket --</option>
                                <option value="survey" {{ old('work_type', $ticket->work_type) === 'survey' ? 'selected' : '' }}>Khảo sát / Tư vấn / Thiết kế</option>
                                <option value="BOM" {{ old('work_type', $ticket->work_type) === 'BOM' ? 'selected' : '' }}>BOM
                                    Support</option>
                                <option value="documentation" {{ old('work_type', $ticket->work_type) === 'documentation' ? 'selected' : '' }}>Technical Documents</option>
                                <option value="POC" {{ old('work_type', $ticket->work_type) === 'POC' ? 'selected' : '' }}>POC
                                    / Demo</option>
                                <option value="deployment" {{ old('work_type', $ticket->work_type) === 'deployment' ? 'selected' : '' }}>Deployment</option>
                                <option value="after_sales" {{ old('work_type', $ticket->work_type) === 'after_sales' ? 'selected' : '' }}>After-sales support</option>
                                <option value="training" {{ old('work_type', $ticket->work_type) === 'training' ? 'selected' : '' }}>Training / Update</option>
                                <option value="event" {{ old('work_type', $ticket->work_type) === 'event' ? 'selected' : '' }}>Event / Speaker</option>
                                <option value="other" {{ old('work_type', $ticket->work_type) === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('work_type')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Priority -->
                        <div>
                            <label for="priority" class="block text-sm font-semibold text-gray-700 mb-1">Priority
                                <span class="text-red-500">*</span></label>
                            <select name="priority" id="priority" required
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary @error('priority') border-red-500 @enderror">
                                <option value="medium" {{ old('priority', $ticket->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority', $ticket->priority) === 'high' ? 'selected' : '' }}>
                                    High</option>
                                <option value="low" {{ old('priority', $ticket->priority) === 'low' ? 'selected' : '' }}>Low
                                </option>
                                <option value="urgent" {{ old('priority', $ticket->priority) === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                            @error('priority')
                                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Requester info -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Người yêu cầu</label>
                            <input type="text" readonly
                                class="w-full border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500 cursor-not-allowed"
                                value="{{ $ticket->creator->name ?? 'N/A' }}">
                        </div>

                        <!-- Department -->
                        <div>
                            <label for="department" class="block text-sm font-semibold text-gray-700 mb-1">Bộ phận yêu
                                cầu</label>
                            <input type="text" name="department" id="department" list="department_list"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('department', $ticket->department) }}">
                            <datalist id="department_list">
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">
                                @endforeach
                            </datalist>
                        </div>

                        <!-- Sales Owner (Searchable Select) -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Sales Owner <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" placeholder="-- Chọn Sales Owner --"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8"
                                    x-model="salesSearch" @focus="openSales = true" @click="openSales = true"
                                    @input="openSales = true; salesTyping = true"
                                    @click.away="openSales = false; salesTyping = false; const found = usersList.find(u => u.id == salesOwnerId); salesSearch = found ? found.name : ''">
                                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                            <div x-show="openSales"
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto p-1 space-y-0.5"
                                x-cloak>
                                <template x-for="user in usersList" :key="user.id">
                                    <button type="button"
                                        x-show="!salesTyping || user.name.toLowerCase().includes(salesSearch.toLowerCase())"
                                        @mousedown.prevent="salesOwnerId = user.id; salesSearch = user.name; openSales = false; salesTyping = false"
                                        class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors"
                                        x-text="user.name"></button>
                                </template>
                            </div>
                            <input type="hidden" name="sales_owner_id" :value="salesOwnerId">
                        </div>

                        <!-- Vendor Relation -->
                        <div>
                            <label for="supplier_id" class="block text-sm font-semibold text-gray-700 mb-1">Vendor (Hãng
                                liên quan)</label>
                            <select name="supplier_id" id="supplier_id"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="">-- Không chọn / Không có --</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ old('supplier_id', $ticket->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>


                        <!-- Project / Partner / EU Name -->
                        <div>
                            <label for="project_name" class="block text-sm font-semibold text-gray-700 mb-1">Dự án (Tên dự
                                án/Partner/EU)</label>
                            <input type="text" name="project_name" id="project_name"
                                placeholder="Nhập tên dự án, đối tác hoặc người dùng cuối..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('project_name', $ticket->project_name) }}">
                        </div>

                        <!-- Due Date (Thời gian yêu cầu xử lý) -->
                        <div>
                            <label for="sla_deadline" class="block text-sm font-semibold text-gray-700 mb-1">Due Date (Hạn
                                xử lý yêu cầu)</label>
                            <input type="datetime-local" name="sla_deadline" id="sla_deadline"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('sla_deadline', $ticket->sla_deadline ? $ticket->sla_deadline->format('Y-m-d\TH:i') : '') }}">
                        </div>
                    </div>

                    <!-- Group: Liên kết CRM (Grouped for neatness) -->
                    <div class="border-t border-gray-150 pt-4 space-y-4"
                        x-show="workType && workType !== 'event' && workType !== 'other'" x-cloak>
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Liên kết nhanh CRM</h4>
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Project Link -->
                            <div>
                                <label for="project_id" class="block text-xs font-semibold text-gray-600 mb-1">Liên kết Dự
                                    án (System Project)</label>
                                <select name="project_id" id="project_id"
                                    class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                    <option value="">-- Không chọn / Không có --</option>
                                    @foreach($projects as $proj)
                                        <option value="{{ $proj->id }}" {{ old('project_id', $ticket->project_id) == $proj->id ? 'selected' : '' }}>{{ $proj->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 1.5: DYNAMIC FIELDS FOR TICKET TYPE -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" x-show="workType" x-cloak>
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <div class="flex items-center">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-2"></span>
                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Thông tin riêng cho loại Ticket
                        </h3>
                    </div>
                    <span class="text-xs font-semibold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full"
                        x-text="'Loại: ' + workType"></span>
                </div>

                <div class="p-6 space-y-6">
                    <!-- a) Ticket Khảo sát/Tư vấn/Thiết kế -->
                    <div x-show="workType === 'survey'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Hình thức họp</label>
                            <select name="ticket_details[meeting_type]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="Online" {{ (old('ticket_details.meeting_type', $ticket->ticket_details['meeting_type'] ?? '') === 'Online') ? 'selected' : '' }}>Họp
                                    Online</option>
                                <option value="Offline" {{ (old('ticket_details.meeting_type', $ticket->ticket_details['meeting_type'] ?? '') === 'Offline') ? 'selected' : '' }}>Họp
                                    Offline</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Thời gian họp</label>
                            <input type="datetime-local" name="ticket_details[meeting_time]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.meeting_time', $ticket->ticket_details['meeting_time'] ?? '') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ (nếu Offline)</label>
                            <input type="text" name="ticket_details[meeting_address]" placeholder="Địa điểm họp cụ thể..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.meeting_address', $ticket->ticket_details['meeting_address'] ?? '') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nội dung / mục tiêu</label>
                            <textarea name="ticket_details[meeting_goal]" rows="3"
                                placeholder="Mục tiêu cuộc họp, nội dung khảo sát..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('ticket_details.meeting_goal', $ticket->ticket_details['meeting_goal'] ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- b) Ticket Yêu cầu BOM -->
                    <div x-show="workType === 'BOM'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Yêu cầu kỹ thuật / Spec</label>
                            <textarea name="ticket_details[spec_requirements]" rows="4"
                                placeholder="Mô tả các yêu cầu kỹ thuật, thông số Spec cần kiểm tra..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('ticket_details.spec_requirements', $ticket->ticket_details['spec_requirements'] ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- c) Ticket Technical Document -->
                    <div x-show="workType === 'documentation'" class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả yêu cầu tài liệu</label>
                            <textarea name="ticket_details[doc_description]" rows="3"
                                placeholder="Yêu cầu Spec, Datasheet, hồ sơ thầu, proposal..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('ticket_details.doc_description', $ticket->ticket_details['doc_description'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Bản chào giá / BOM tham
                                chiếu</label>
                            <input type="text" name="ticket_details[doc_bom]"
                                placeholder="Thông tin BOM hoặc cấu hình tham chiếu..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.doc_bom', $ticket->ticket_details['doc_bom'] ?? '') }}">
                        </div>
                    </div>

                    <!-- d) Ticket POC/Demo -->
                    <div x-show="workType === 'POC'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Thiết bị / Model</label>
                            <input type="text" name="ticket_details[poc_model]"
                                placeholder="Ví dụ: Sophos XGS 2100, Fortigate 100F..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.poc_model', $ticket->ticket_details['poc_model'] ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng mượn</label>
                            <input type="number" name="ticket_details[poc_quantity]" min="1" placeholder="Số lượng..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.poc_quantity', $ticket->ticket_details['poc_quantity'] ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Yêu cầu kế hoạch/phương án
                                PoC</label>
                            <select name="ticket_details[poc_require_plan]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="No" {{ (old('ticket_details.poc_require_plan', $ticket->ticket_details['poc_require_plan'] ?? '') === 'No') ? 'selected' : '' }}>Không
                                    (No)</option>
                                <option value="Yes" {{ (old('ticket_details.poc_require_plan', $ticket->ticket_details['poc_require_plan'] ?? '') === 'Yes') ? 'selected' : '' }}>Có
                                    (Yes)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Ngày mượn thiết bị</label>
                            <input type="date" name="ticket_details[poc_borrow_date]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.poc_borrow_date', $ticket->ticket_details['poc_borrow_date'] ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Ngày trả thiết bị</label>
                            <input type="date" name="ticket_details[poc_return_date]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.poc_return_date', $ticket->ticket_details['poc_return_date'] ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Địa điểm triển khai POC</label>
                            <input type="text" name="ticket_details[poc_location]"
                                placeholder="Địa chỉ Onsite triển khai..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.poc_location', $ticket->ticket_details['poc_location'] ?? '') }}">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Mục tiêu POC</label>
                            <textarea name="ticket_details[poc_goal]" rows="3"
                                placeholder="Các tính năng kỹ thuật cần chứng minh, tiêu chí đạt..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('ticket_details.poc_goal', $ticket->ticket_details['poc_goal'] ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- e) Ticket Hỗ trợ triển khai -->
                    <div x-show="workType === 'deployment'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Hình thức triển khai</label>
                            <select name="ticket_details[deploy_type]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="Onsite" {{ (old('ticket_details.deploy_type', $ticket->ticket_details['deploy_type'] ?? '') === 'Onsite') ? 'selected' : '' }}>Onsite
                                </option>
                                <option value="Remote" {{ (old('ticket_details.deploy_type', $ticket->ticket_details['deploy_type'] ?? '') === 'Remote') ? 'selected' : '' }}>Remote
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Thời gian triển khai</label>
                            <input type="datetime-local" name="ticket_details[deploy_time]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.deploy_time', $ticket->ticket_details['deploy_time'] ?? '') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Địa chỉ triển khai</label>
                            <input type="text" name="ticket_details[deploy_address]"
                                placeholder="Địa chỉ Onsite (nếu có)..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.deploy_address', $ticket->ticket_details['deploy_address'] ?? '') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Phạm vi công việc (Scope of Work -
                                SoW)</label>
                            <textarea name="ticket_details[deploy_sow]" rows="3"
                                placeholder="Mô tả phạm vi công việc cần cấu hình, cài đặt..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('ticket_details.deploy_sow', $ticket->ticket_details['deploy_sow'] ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- f) Ticket After-sales support -->
                    <div x-show="workType === 'after_sales'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Contact liên hệ
                                (Email/SĐT)</label>
                            <input type="text" name="ticket_details[after_sales_contact]"
                                placeholder="Họ tên, SĐT hoặc Email khách hàng..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.after_sales_contact', $ticket->ticket_details['after_sales_contact'] ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">S/N thiết bị lỗi</label>
                            <input type="text" name="ticket_details[after_sales_serial]"
                                placeholder="Serial Number của thiết bị..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.after_sales_serial', $ticket->ticket_details['after_sales_serial'] ?? '') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả vấn đề / Sự cố</label>
                            <textarea name="ticket_details[after_sales_problem]" rows="4"
                                placeholder="Mô tả chi tiết lỗi phát sinh, hiện tượng sự cố kỹ thuật..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('ticket_details.after_sales_problem', $ticket->ticket_details['after_sales_problem'] ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- g) Ticket Event -->
                    <div x-show="workType === 'event'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tên sự kiện (Event Name)</label>
                            <input type="text" name="ticket_details[event_name]" placeholder="Nhập tên sự kiện, hội thảo..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.event_name', $ticket->ticket_details['event_name'] ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Thời gian tổ chức</label>
                            <input type="datetime-local" name="ticket_details[event_time]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.event_time', $ticket->ticket_details['event_time'] ?? '') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Địa điểm tổ chức</label>
                            <input type="text" name="ticket_details[event_location]"
                                placeholder="Địa chỉ tổ chức sự kiện..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.event_location', $ticket->ticket_details['event_location'] ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Đối tượng tham gia</label>
                            <input type="text" name="ticket_details[event_attendees]"
                                placeholder="Partner, Customer, End-User..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.event_attendees', $ticket->ticket_details['event_attendees'] ?? '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Cử Speaker tham gia?</label>
                            <select name="ticket_details[event_speaker]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="No" {{ (old('ticket_details.event_speaker', $ticket->ticket_details['event_speaker'] ?? '') === 'No') ? 'selected' : '' }}>Không (No)
                                </option>
                                <option value="Yes" {{ (old('ticket_details.event_speaker', $ticket->ticket_details['event_speaker'] ?? '') === 'Yes') ? 'selected' : '' }}>Có (Yes)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Chuẩn bị Slide trình bày?</label>
                            <select name="ticket_details[event_slide]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="No" {{ (old('ticket_details.event_slide', $ticket->ticket_details['event_slide'] ?? '') === 'No') ? 'selected' : '' }}>Không (No)
                                </option>
                                <option value="Yes" {{ (old('ticket_details.event_slide', $ticket->ticket_details['event_slide'] ?? '') === 'Yes') ? 'selected' : '' }}>Có (Yes)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Triển khai Demo trực tiếp?</label>
                            <select name="ticket_details[event_demo]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="No" {{ (old('ticket_details.event_demo', $ticket->ticket_details['event_demo'] ?? '') === 'No') ? 'selected' : '' }}>Không (No)
                                </option>
                                <option value="Yes" {{ (old('ticket_details.event_demo', $ticket->ticket_details['event_demo'] ?? '') === 'Yes') ? 'selected' : '' }}>Có (Yes)
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Yêu cầu khác</label>
                            <textarea name="ticket_details[event_notes]" rows="3"
                                placeholder="Các yêu cầu chuẩn bị thiết bị, banner, quà tặng..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('ticket_details.event_notes', $ticket->ticket_details['event_notes'] ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- h) Ticket Training/Update -->
                    <div x-show="workType === 'training'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Đối tượng đào tạo</label>
                            <select name="ticket_details[training_audience]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="Internal" {{ (old('ticket_details.training_audience', $ticket->ticket_details['training_audience'] ?? '') === 'Internal') ? 'selected' : '' }}>
                                    Nội bộ (Internal)</option>
                                <option value="Partner" {{ (old('ticket_details.training_audience', $ticket->ticket_details['training_audience'] ?? '') === 'Partner') ? 'selected' : '' }}>
                                    Đối tác (Partner)</option>
                                <option value="Customer" {{ (old('ticket_details.training_audience', $ticket->ticket_details['training_audience'] ?? '') === 'Customer') ? 'selected' : '' }}>
                                    Khách hàng (Customer)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Hình thức</label>
                            <select name="ticket_details[training_format]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="Online" {{ (old('ticket_details.training_format', $ticket->ticket_details['training_format'] ?? '') === 'Online') ? 'selected' : '' }}>
                                    Online</option>
                                <option value="Offline" {{ (old('ticket_details.training_format', $ticket->ticket_details['training_format'] ?? '') === 'Offline') ? 'selected' : '' }}>
                                    Offline</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Thời gian đào tạo</label>
                            <input type="datetime-local" name="ticket_details[training_time]"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.training_time', $ticket->ticket_details['training_time'] ?? '') }}">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Địa điểm đào tạo (nếu
                                Offline)</label>
                            <input type="text" name="ticket_details[training_location]"
                                placeholder="Địa chỉ phòng Lab, văn phòng..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('ticket_details.training_location', $ticket->ticket_details['training_location'] ?? '') }}">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nội dung / Mục tiêu đề
                                xuất</label>
                            <textarea name="ticket_details[training_goal]" rows="3"
                                placeholder="Các bài Lab, nội dung sản phẩm cần đào tạo..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('ticket_details.training_goal', $ticket->ticket_details['training_goal'] ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- i) & j) IT support / Khác -->
                    <div x-show="workType === 'other'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả yêu cầu</label>
                            <textarea name="ticket_details[other_description]" rows="4"
                                placeholder="Mô tả cụ thể yêu cầu hỗ trợ khác..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('ticket_details.other_description', $ticket->ticket_details['other_description'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 2: PHÂN CÔNG (TECHNICAL PICKUP) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 rounded-t-xl flex items-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 mr-2"></span>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Thông tin Technical Pickup</h3>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Engineer Searchable Select -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Kỹ sư phụ trách <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" placeholder="-- Chọn Kỹ sư --"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8"
                                    x-model="engSearch" @focus="openEng = true" @click="openEng = true"
                                    @input="openEng = true; engTyping = true"
                                    @click.away="openEng = false; engTyping = false; const found = usersList.find(u => u.id == assignedTo); engSearch = found ? found.name : ''">
                                <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                            <div x-show="openEng"
                                class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto p-1 space-y-0.5"
                                x-cloak>
                                <button type="button"
                                    @mousedown.prevent="assignedTo = ''; engSearch = ''; openEng = false; engTyping = false"
                                    class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors italic text-gray-400">
                                    -- Chưa phân công --
                                </button>
                                <template x-for="user in engineersList" :key="user.id">
                                    <button type="button"
                                        x-show="!engTyping || user.name.toLowerCase().includes(engSearch.toLowerCase())"
                                        @mousedown.prevent="assignedTo = user.id; engSearch = user.name; openEng = false; engTyping = false"
                                        class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors"
                                        x-text="user.name"></button>
                                </template>
                            </div>
                            <input type="hidden" name="assigned_to" :value="assignedTo">
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 3: CHI TIẾT & ĐÁNH GIÁ (DESCRIPTION & EVALUATION) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-purple-500 mr-2"></span>
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">c) & d) Mô tả & Đánh giá phương án
                    </h3>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Yêu cầu chi
                            tiết</label>
                        <textarea name="description" id="description" rows="6"
                            class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('description', $ticket->description) }}</textarea>
                    </div>

                    <!-- Solution / Evaluation -->
                    <div>
                        <label for="solution" class="block text-sm font-semibold text-gray-700 mb-1">Nguyên nhân / Phương án
                            / Cách xử lý</label>
                        <textarea name="solution" id="solution" rows="4"
                            placeholder="Kỹ sư hoặc Lead điền nguyên nhân, phương án xử lý, cấu hình chi tiết..."
                            class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('solution', $ticket->solution) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 flex justify-end space-x-3 shadow-sm">
                <a href="{{ route('technical-tickets.show', $ticket->id) }}"
                    class="px-4 py-2 border border-gray-300 bg-white rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                    Hủy bỏ
                </a>
                <button type="submit"
                    class="px-5 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            window.editTicketUsers = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]));
            window.editTicketEngineers = @json($engineers->map(fn($e) => ['id' => $e->id, 'name' => $e->name]));
        </script>
    @endpush
@endsection