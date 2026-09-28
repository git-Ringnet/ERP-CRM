@extends('layouts.app')

@section('title', 'Chỉnh sửa Ticket Kỹ thuật')
@section('page-title', 'Chỉnh sửa Ticket Kỹ thuật')

@section('content')
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
        <style>
            [x-cloak] {
                display: none !important;
            }
            .ts-wrapper .ts-control {
                border-radius: 0.5rem;
                border-color: #e5e7eb;
                font-size: 0.875rem;
                padding: 0.5rem 0.75rem;
                min-height: 42px;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            }
            .ts-wrapper.focus .ts-control {
                border-color: var(--primary, #3b82f6);
                box-shadow: 0 0 0 1px var(--primary, #3b82f6);
            }
            .ts-dropdown {
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                border-color: #e5e7eb;
                font-size: 0.875rem;
                z-index: 60;
            }
            .ts-dropdown .option {
                padding: 8px 12px;
            }
            .ts-dropdown .option.active {
                background-color: #eff6ff;
                color: #1e40af;
            }
            .ts-wrapper.multi .ts-control .item {
                background-color: #3b82f6;
                color: white;
                border-radius: 4px;
                padding: 2px 8px;
            }
        </style>
    @endpush

@php
    $existingPocDevices = old('ticket_details.poc_devices');
    if (!$existingPocDevices) {
        if (!empty($ticket->ticket_details['poc_devices']) && is_array($ticket->ticket_details['poc_devices'])) {
            $existingPocDevices = $ticket->ticket_details['poc_devices'];
        } elseif (!empty($ticket->ticket_details['poc_model'])) {
            $existingPocDevices = [[
                'name' => $ticket->ticket_details['poc_model'],
                'quantity' => $ticket->ticket_details['poc_quantity'] ?? 1,
                'note' => '',
            ]];
        } else {
            $existingPocDevices = [['name' => '', 'quantity' => 1, 'note' => '']];
        }
    }
@endphp

    <div class="" x-data="{ 
            workType: '{{ old('work_type', $ticket->work_type) }}',
            openSales: false, salesSearch: '', salesOwnerId: '{{ old('sales_owner_id', $ticket->sales_owner_id) }}', salesOwnerName: '', salesTyping: false,
            openLead: false, leadSearch: '', teamLeadId: '{{ old('team_lead_id', $ticket->team_lead_id) }}', teamLeadName: '', leadTyping: false,
            openEng: false, engSearch: '', assignedTo: '{{ is_array(old('assigned_to', $ticket->assigned_to)) ? (collect(old('assigned_to', $ticket->assigned_to))->first() ?? '') : old('assigned_to', $ticket->assigned_to) }}', assignedToName: '', engTyping: false,

            usersList: window.editTicketUsers || [],
            engineersList: window.editTicketEngineers || [],

            pocDevices: {{ json_encode($existingPocDevices) }},
            addPocDevice() {
                this.pocDevices.push({ name: '', quantity: 1, note: '' });
            },
            removePocDevice(index) {
                if (this.pocDevices.length > 1) {
                    this.pocDevices.splice(index, 1);
                } else {
                    this.pocDevices[0] = { name: '', quantity: 1, note: '' };
                }
            },

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

                        <!-- Vendor Relation (Searchable) -->
                        <div>
                            <label for="supplier_id" class="block text-sm font-semibold text-gray-700 mb-1">
                                <i class="fas fa-building text-gray-400 mr-1"></i> Vendor (Hãng liên quan)
                            </label>
                            <select name="supplier_id" id="supplier_id" class="w-full">
                                <option value="">-- Không chọn / Không có --</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ old('supplier_id', $ticket->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- System Project Link (Searchable with duplicate detection) -->
                        <div>
                            <label for="project_id" class="block text-sm font-semibold text-gray-700 mb-1">
                                <i class="fas fa-diagram-project text-blue-500 mr-1"></i> Dự án Hệ thống (ERP/CRM Project)
                            </label>
                            <select name="project_id" id="project_id" class="w-full">
                                <option value="">-- Chọn hoặc tìm kiếm dự án hệ thống --</option>
                                @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}" {{ old('project_id', $ticket->project_id) == $proj->id ? 'selected' : '' }}>
                                        {{ $proj->code ? '[' . $proj->code . '] ' : '' }}{{ $proj->name }}{{ $proj->customer_name ? ' (' . $proj->customer_name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Project / Partner / EU Name -->
                        <div>
                            <label for="project_name" class="block text-sm font-semibold text-gray-700 mb-1">
                                <i class="fas fa-tag text-gray-400 mr-1"></i> Dự án (Tên dự án/Partner/EU)
                            </label>
                            <input type="text" name="project_name" id="project_name"
                                placeholder="Nhập tên dự án, đối tác hoặc người dùng cuối..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('project_name', $ticket->project_name) }}"
                                oninput="debouncedCheckDuplicate()">
                        </div>

                        <!-- Due Date (Thời gian yêu cầu xử lý) -->
                        <div>
                            <label for="sla_deadline" class="block text-sm font-semibold text-gray-700 mb-1">Due Date (Hạn xử lý yêu cầu)</label>
                            <input type="datetime-local" name="sla_deadline" id="sla_deadline"
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                value="{{ old('sla_deadline', $ticket->sla_deadline ? $ticket->sla_deadline->format('Y-m-d\TH:i') : '') }}">
                        </div>

                        <!-- Duplicate Ticket Warning Container -->
                        <div id="duplicateWarningContainer" class="hidden md:col-span-2"></div>
                    </div>

                    <!-- CARD 1.2: PHÂN NHÓM & LEAD PHỤ TRÁCH -->
                    <div class="border-t border-gray-150 pt-5 mt-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Phân nhóm & Team Lead Phụ Trách</h4>
                            </div>
                        </div>

                        @if($isTechnicalLead)
                            <!-- Dành cho Trưởng nhóm kỹ thuật / Quản lý / Quản trị viên -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <!-- Team Lead chính -->
                                <div class="md:col-span-1">
                                    <label for="team_lead_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i class="fas fa-user-tie text-purple-600"></i> Trưởng nhóm (Lead chính) <span class="text-red-500 font-bold">*</span>
                                    </label>
                                    <select name="team_lead_id" id="team_lead_id" required
                                        class="w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary shadow-2xs @error('team_lead_id') border-red-500 @enderror"
                                        onchange="handlePrimaryLeadChange()">
                                        <option value="">-- Bắt buộc chọn Lead chính * --</option>
                                        @foreach($leads as $ld)
                                            <option value="{{ $ld->id }}" {{ old('team_lead_id', $ticket->team_lead_id) == $ld->id ? 'selected' : '' }}>
                                                {{ $ld->name }} ({{ $ld->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('team_lead_id')
                                        <p class="text-[11px] text-red-500 mt-1 font-semibold">{{ $message }}</p>
                                    @enderror

                                    <!-- Exclamation Note Banner -->
                                    <div class="mt-2.5 p-2 rounded-lg bg-purple-50 border border-purple-150 flex items-start gap-2 text-[11px] text-purple-900 leading-snug">
                                        <i class="fas fa-exclamation-circle text-purple-600 mt-0.5 shrink-0 text-xs"></i>
                                        <span>Lead chính phụ trách tiếp nhận, trực tiếp phân công và kiểm soát ticket này.</span>
                                    </div>
                                </div>

                                <!-- Co-Leads (Lead phối hợp nhóm khác - Checkbox Cards) -->
                                <div class="md:col-span-2">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                                <i class="fas fa-handshake text-teal-600"></i> Lead phối hợp (Nhóm khác)
                                            </label>
                                            <span id="coLeadsCountBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                                0 đã chọn
                                            </span>
                                        </div>
                                        <div class="relative">
                                            <i class="fas fa-search absolute left-2 top-1.5 text-gray-400 text-[10px]"></i>
                                            <input type="text" id="coLeadSearchInput" oninput="filterCoLeadsList()" placeholder="Tìm lead..."
                                                   class="pl-6 pr-2 py-0.5 bg-white border border-gray-300 rounded text-xs focus:ring-1 focus:ring-primary focus:border-primary w-32 sm:w-40 outline-none">
                                        </div>
                                    </div>

                                    <div id="coLeadsGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-1 bg-gray-50/70 rounded-lg border border-gray-200">
                                        @php
                                            $currentCoLeadIds = old('co_lead_ids', $ticket->co_lead_ids ?? []);
                                            if (!is_array($currentCoLeadIds)) $currentCoLeadIds = [];
                                        @endphp
                                        @foreach($leads as $ld)
                                            @php
                                                $isCoLead = in_array($ld->id, $currentCoLeadIds);
                                                $isPrimary = old('team_lead_id', $ticket->team_lead_id) == $ld->id;
                                            @endphp
                                            <div class="co-lead-card p-2 rounded-lg border transition-all cursor-pointer flex items-start gap-2.5 bg-white hover:border-teal-400 {{ $isCoLead ? 'border-teal-500 bg-teal-50/50 shadow-2xs' : 'border-gray-200' }} {{ $isPrimary ? 'opacity-40 pointer-events-none' : '' }}"
                                                 id="co_lead_card_{{ $ld->id }}"
                                                 onclick="toggleCoLeadCheckbox('{{ $ld->id }}', event)">
                                                <input type="checkbox" 
                                                       name="co_lead_ids[]" 
                                                       value="{{ $ld->id }}" 
                                                       id="co_lead_cb_{{ $ld->id }}"
                                                       {{ $isCoLead ? 'checked' : '' }}
                                                       {{ $isPrimary ? 'disabled' : '' }}
                                                       onchange="updateCoLeadCardStyle(this)"
                                                       class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 mt-0.5 co-lead-checkbox pointer-events-auto">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between gap-1">
                                                        <span class="font-bold text-xs text-gray-900 truncate" title="{{ $ld->name }}">{{ $ld->name }}</span>
                                                        <span class="text-[9px] font-extrabold px-1.5 py-0.2 bg-teal-100 text-teal-700 rounded flex-shrink-0">Lead</span>
                                                    </div>
                                                    <div class="text-[11px] text-gray-500 truncate" title="{{ $ld->email }}">{{ $ld->email }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Multi-assignee Engineers Section -->
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <!-- Control bar above checkbox cards -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 bg-gray-50 p-2.5 rounded-lg border border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                            <i class="fas fa-user-gear text-blue-600"></i> Phân công Kỹ sư thực hiện
                                        </label>
                                        <span id="selectedCountBadge" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                            0 đã chọn
                                        </span>
                                    </div>

                                    <!-- Quick tools: Search & Selection buttons -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="relative">
                                            <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                                            <input type="text" id="engineerSearchInput" oninput="filterEngineersList()" placeholder="Tìm tên, email..."
                                                   class="pl-7 pr-3 py-1 bg-white border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-primary focus:border-primary w-40 sm:w-48 outline-none">
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <button type="button" onclick="selectAllVisibleEngineers()" class="px-2.5 py-1 text-[11px] font-semibold bg-white text-gray-700 border border-gray-300 rounded hover:bg-gray-100 transition-colors shadow-2xs">
                                                <i class="fas fa-check-double text-blue-500 mr-1"></i> Chọn tất cả
                                            </button>
                                            <button type="button" onclick="deselectAllEngineers()" class="px-2.5 py-1 text-[11px] font-semibold bg-white text-gray-700 border border-gray-300 rounded hover:bg-gray-100 transition-colors shadow-2xs">
                                                <i class="fas fa-times text-gray-400 mr-1"></i> Bỏ chọn
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filter status notice banner (if filtered by lead/group) -->
                                <div id="filterNoticeBanner" class="hidden mb-2.5 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800 flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-filter text-blue-600"></i>
                                        <span id="filterNoticeText">Đang lọc kỹ sư theo Lead / Nhóm đã chọn</span>
                                    </div>
                                    <button type="button" onclick="toggleShowAllEngineers()" id="toggleAllBtn" class="text-xs font-bold text-blue-700 hover:underline">
                                        Hiển thị tất cả kỹ sư
                                    </button>
                                </div>

                                <!-- Grid of Checkbox Cards -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 max-h-60 overflow-y-auto p-2.5 border border-gray-200 rounded-lg bg-gray-50/50" id="engineerListContainer">
                                    @php
                                        $assignedUserIds = old('assigned_to', $ticket->assignedEngineers->pluck('id')->toArray());
                                        if (!is_array($assignedUserIds)) $assignedUserIds = [$assignedUserIds];
                                    @endphp
                                    @foreach($engineers as $eng)
                                        @php
                                            $isAssigned = in_array($eng->id, $assignedUserIds);
                                            $groupNames = $eng->userGroups->pluck('name')->toArray();
                                            $groupIds = $eng->userGroups->pluck('id')->toArray();
                                            $leadIds = $eng->userGroups->pluck('leader_id')->filter()->toArray();
                                        @endphp
                                        <div class="eng-card relative flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer transition-all duration-150 {{ $isAssigned ? 'border-primary bg-indigo-50/40 ring-1 ring-primary shadow-xs' : 'border-gray-200 bg-white hover:border-indigo-300 hover:bg-gray-50/70' }}"
                                             data-eng-id="{{ $eng->id }}"
                                             data-name="{{ mb_strtolower($eng->name) }}"
                                             data-email="{{ mb_strtolower($eng->email) }}"
                                             data-groups="{{ implode(',', $groupIds) }}"
                                             data-leads="{{ implode(',', $leadIds) }}"
                                             onclick="toggleEngineerCard(this, event)">
                                            
                                            <input type="checkbox" name="assigned_to[]" value="{{ $eng->id }}"
                                                   {{ $isAssigned ? 'checked' : '' }}
                                                   onchange="updateEngineerCardStyle(this)"
                                                   class="rounded border-gray-300 text-primary focus:ring-primary mt-1 eng-checkbox pointer-events-auto">
                                            
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="font-bold text-xs text-gray-900 truncate" title="{{ $eng->name }}">
                                                        {{ $eng->name }}
                                                    </span>
                                                    @if($eng->hasRole('technical_lead'))
                                                        <span class="text-[9px] font-extrabold px-1.5 py-0.2 bg-purple-100 text-purple-700 rounded flex-shrink-0">
                                                            Lead
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-gray-500 truncate" title="{{ $eng->email }}">
                                                    {{ $eng->email }}
                                                </div>
                                                @if(!empty($groupNames))
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        @foreach($groupNames as $gName)
                                                            <span class="text-[9px] px-1.5 py-0.2 bg-gray-100 text-gray-600 rounded border border-gray-200 truncate max-w-[120px]">
                                                                <i class="fas fa-users text-[8px] mr-0.5 text-gray-400"></i>{{ $gName }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- Giao diện dành cho Người tạo ticket / Nhân viên không phải Lead -->
                            <div class="space-y-4">
                                <div class="max-w-2xl bg-gray-50/70 p-4 rounded-xl border border-gray-200 space-y-3">
                                    <div>
                                        <label for="team_lead_id" class="block text-xs font-bold text-gray-800 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                            <i class="fas fa-user-tie text-purple-600"></i> Trưởng nhóm (Lead chính) <span class="text-red-500 font-bold">*</span>
                                        </label>
                                        <select name="team_lead_id" id="team_lead_id" required
                                            class="w-full border-gray-300 rounded-lg text-xs sm:text-sm focus:border-primary focus:ring-primary bg-white shadow-2xs @error('team_lead_id') border-red-500 @enderror">
                                            <option value="">-- Bắt buộc chọn Trưởng nhóm (Lead chính) phụ trách * --</option>
                                            @foreach($leads as $ld)
                                                <option value="{{ $ld->id }}" {{ old('team_lead_id', $ticket->team_lead_id) == $ld->id ? 'selected' : '' }}>
                                                    {{ $ld->name }} ({{ $ld->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('team_lead_id')
                                            <p class="text-[11px] text-red-500 mt-1 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Exclamation Alert Note -->
                                    <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 flex items-start gap-2.5 text-xs text-amber-900">
                                        <span class="w-5 h-5 rounded-full bg-amber-200/80 flex items-center justify-center text-amber-700 font-bold shrink-0 mt-0.5">
                                            <i class="fas fa-exclamation text-xs"></i>
                                        </span>
                                        <div class="space-y-1">
                                            <div class="font-bold text-amber-950">
                                                Bắt buộc chỉ định Trưởng nhóm (Lead chính) phụ trách
                                            </div>
                                            <p class="text-[11px] text-amber-800 leading-relaxed">
                                                Trưởng nhóm kỹ thuật phụ trách sẽ kiểm soát và điều phối ticket này. Quyền phân công kỹ sư và lead phối hợp thuộc về Trưởng nhóm kỹ thuật.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Read-only summary of Co-Leads and Assigned Engineers -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50/50 p-3.5 rounded-xl border border-gray-200 text-xs">
                                    <div>
                                        <span class="font-bold text-gray-600 uppercase tracking-wider block mb-1.5 flex items-center gap-1.5">
                                            <i class="fas fa-handshake text-teal-600"></i> Lead phối hợp:
                                        </span>
                                        @php
                                            $coLeads = !empty($ticket->co_lead_ids) ? \App\Models\User::whereIn('id', (array)$ticket->co_lead_ids)->get() : collect();
                                        @endphp
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse($coLeads as $cLead)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-teal-100 text-teal-800 font-medium text-[11px] border border-teal-200">
                                                    {{ $cLead->name }}
                                                </span>
                                            @empty
                                                <span class="text-gray-400 italic">Chưa có lead phối hợp</span>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div>
                                        <span class="font-bold text-gray-600 uppercase tracking-wider block mb-1.5 flex items-center gap-1.5">
                                            <i class="fas fa-user-gear text-blue-600"></i> Kỹ sư thực hiện:
                                        </span>
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse($ticket->assignedEngineers as $eng)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 font-semibold text-[11px] border border-blue-200">
                                                    <i class="fas fa-user-gear text-[10px] mr-1"></i> {{ $eng->name }}
                                                </span>
                                            @empty
                                                <span class="text-gray-400 italic">Chờ Lead phân công kỹ sư</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
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
                    <div x-show="workType === 'POC'" class="space-y-6">
                        <!-- Danh sách thiết bị mượn PoC/Demo -->
                        <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-sm font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-server text-indigo-600 mr-2"></i> Danh sách Thiết bị / Model Mượn POC
                                </label>
                                <button type="button" @click="addPocDevice()"
                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg text-xs font-semibold transition-colors shadow-sm">
                                    <i class="fas fa-plus mr-1.5"></i> Thêm thiết bị
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(device, idx) in pocDevices" :key="idx">
                                    <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 bg-white p-3 rounded-lg border border-gray-200 shadow-sm transition-all hover:border-indigo-300">
                                        <div class="flex-1">
                                            <label class="block text-xs font-semibold text-gray-600 mb-1" x-text="'Thiết bị / Model #' + (idx + 1)"></label>
                                            <input type="text" :name="'ticket_details[poc_devices][' + idx + '][name]'" x-model="device.name"
                                                placeholder="Ví dụ: Sophos XGS 2100, SonicWall TZ470..."
                                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                        </div>
                                        <div class="w-full md:w-36">
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Số lượng</label>
                                            <input type="number" :name="'ticket_details[poc_devices][' + idx + '][quantity]'" x-model="device.quantity" min="1" placeholder="Số lượng..."
                                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Ghi chú / Serial (nếu có)</label>
                                            <input type="text" :name="'ticket_details[poc_devices][' + idx + '][note]'" x-model="device.note"
                                                placeholder="Ghi chú cấu hình, phụ kiện..."
                                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                        </div>
                                        <div class="flex items-end pb-1 md:self-end">
                                            <button type="button" @click="removePocDevice(idx)"
                                                class="text-gray-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 transition-colors"
                                                title="Xóa thiết bị này">
                                                <i class="fas fa-trash-alt text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Các trường thông tin chi tiết POC khác -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Yêu cầu kế hoạch/phương án PoC</label>
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
                            <div class="md:col-span-3">
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

            let currentAllowedUserIds = null;
            let forceShowAll = false;

            function updateSelectedCount() {
                const checkboxes = document.querySelectorAll('.eng-checkbox:checked');
                const badge = document.getElementById('selectedCountBadge');
                if (badge) {
                    badge.innerText = `${checkboxes.length} đã chọn`;
                    if (checkboxes.length > 0) {
                        badge.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200';
                    } else {
                        badge.className = 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200';
                    }
                }
            }

            function toggleEngineerCard(cardEl, event) {
                if (event && event.target && event.target.type === 'checkbox') {
                    return;
                }
                const checkbox = cardEl.querySelector('.eng-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    updateEngineerCardStyle(checkbox);
                }
            }

            function updateEngineerCardStyle(checkbox) {
                const card = checkbox.closest('.eng-card');
                if (card) {
                    if (checkbox.checked) {
                        card.classList.add('border-primary', 'bg-indigo-50/40', 'ring-1', 'ring-primary', 'shadow-xs');
                        card.classList.remove('border-gray-200', 'bg-white');
                    } else {
                        card.classList.remove('border-primary', 'bg-indigo-50/40', 'ring-1', 'ring-primary', 'shadow-xs');
                        card.classList.add('border-gray-200', 'bg-white');
                    }
                }
                updateSelectedCount();
            }

            function selectAllVisibleEngineers() {
                document.querySelectorAll('.eng-card').forEach(card => {
                    if (card.style.display !== 'none') {
                        const chk = card.querySelector('.eng-checkbox');
                        if (chk && !chk.checked) {
                            chk.checked = true;
                            updateEngineerCardStyle(chk);
                        }
                    }
                });
            }

            function deselectAllEngineers() {
                document.querySelectorAll('.eng-checkbox').forEach(chk => {
                    if (chk.checked) {
                        chk.checked = false;
                        updateEngineerCardStyle(chk);
                    }
                });
            }

            function filterEngineersList() {
                const search = (document.getElementById('engineerSearchInput')?.value || '').trim().toLowerCase();
                
                document.querySelectorAll('.eng-card').forEach(card => {
                    const engId = card.getAttribute('data-eng-id');
                    const name = card.getAttribute('data-name') || '';
                    const email = card.getAttribute('data-email') || '';
                    const chk = card.querySelector('.eng-checkbox');
                    const isChecked = chk && chk.checked;

                    const matchesSearch = !search || name.includes(search) || email.includes(search);
                    
                    let matchesGroupOrLead = true;
                    if (!forceShowAll && currentAllowedUserIds !== null && currentAllowedUserIds.length > 0) {
                        matchesGroupOrLead = currentAllowedUserIds.includes(engId) || isChecked;
                    }

                    if (matchesSearch && matchesGroupOrLead) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            function toggleShowAllEngineers() {
                forceShowAll = !forceShowAll;
                const btn = document.getElementById('toggleAllBtn');
                const noticeText = document.getElementById('filterNoticeText');
                if (btn) {
                    btn.innerText = forceShowAll ? 'Chỉ hiện theo Lead' : 'Hiển thị tất cả kỹ sư';
                }
                if (noticeText) {
                    noticeText.innerText = forceShowAll ? 'Đang hiển thị toàn bộ kỹ sư' : 'Đang lọc kỹ sư theo Lead đã chọn';
                }
                filterEngineersList();
            }

            function handleLeadChange() {
                fetchAndFilterMembers();
            }

            function handlePrimaryLeadChange() {
                const leadSelect = document.getElementById('team_lead_id');
                const selectedLeadId = leadSelect ? leadSelect.value : '';

                // Disable / uncheck if selected as primary lead
                document.querySelectorAll('.co-lead-checkbox').forEach(cb => {
                    const card = document.getElementById('co_lead_card_' + cb.value);
                    if (cb.value === selectedLeadId && selectedLeadId !== '') {
                        cb.checked = false;
                        cb.disabled = true;
                        if (card) {
                            card.classList.add('opacity-40', 'pointer-events-none');
                            card.classList.remove('border-teal-500', 'bg-teal-50/50', 'shadow-2xs');
                            card.classList.add('border-gray-200');
                        }
                    } else {
                        cb.disabled = false;
                        if (card) {
                            card.classList.remove('opacity-40', 'pointer-events-none');
                        }
                    }
                });
                updateCoLeadsCount();
                handleLeadChange();
            }

            function toggleCoLeadCheckbox(id, event) {
                if (event.target.tagName === 'INPUT') return;
                const cb = document.getElementById('co_lead_cb_' + id);
                if (cb && !cb.disabled) {
                    cb.checked = !cb.checked;
                    updateCoLeadCardStyle(cb);
                }
            }

            function updateCoLeadCardStyle(cb) {
                const card = document.getElementById('co_lead_card_' + cb.value);
                if (card) {
                    if (cb.checked) {
                        card.classList.add('border-teal-500', 'bg-teal-50/50', 'shadow-2xs');
                        card.classList.remove('border-gray-200');
                    } else {
                        card.classList.remove('border-teal-500', 'bg-teal-50/50', 'shadow-2xs');
                        card.classList.add('border-gray-200');
                    }
                }
                updateCoLeadsCount();
                handleLeadChange();
            }

            function updateCoLeadsCount() {
                const checked = document.querySelectorAll('.co-lead-checkbox:checked');
                const badge = document.getElementById('coLeadsCountBadge');
                if (badge) {
                    badge.innerText = `${checked.length} đã chọn`;
                }
            }

            function filterCoLeadsList() {
                const query = (document.getElementById('coLeadSearchInput')?.value || '').toLowerCase().trim();
                const cards = document.querySelectorAll('.co-lead-card');
                cards.forEach(card => {
                    const text = card.innerText.toLowerCase();
                    if (!query || text.includes(query)) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                });
            }

            async function fetchAndFilterMembers() {
                const leadSelect = document.getElementById('team_lead_id');
                const primaryLeadId = leadSelect ? leadSelect.value : '';
                const selectedCoLeadIds = Array.from(document.querySelectorAll('.co-lead-checkbox:checked')).map(cb => cb.value);

                const leadIds = [];
                if (primaryLeadId) leadIds.push(primaryLeadId);
                selectedCoLeadIds.forEach(id => { if (id && !leadIds.includes(id)) leadIds.push(id); });

                if (leadIds.length === 0) {
                    currentAllowedUserIds = null;
                    updateNoticeBanner(false);
                    filterEngineersList();
                    return;
                }

                try {
                    const params = new URLSearchParams();
                    leadIds.forEach(id => params.append('lead_ids[]', id));

                    const res = await fetch(`{{ route('api.user-groups.members-by-lead') }}?${params.toString()}`);
                    const data = await res.json();
                    
                    if (data.success) {
                        if (data.allowed_user_ids && data.allowed_user_ids.length > 0) {
                            currentAllowedUserIds = data.allowed_user_ids.map(id => id.toString());
                        } else {
                            currentAllowedUserIds = null;
                        }
                        
                        forceShowAll = false;
                        updateNoticeBanner(true);
                        filterEngineersList();
                    }
                } catch (e) {
                    console.error('Error fetching group members:', e);
                    currentAllowedUserIds = null;
                    updateNoticeBanner(false);
                    filterEngineersList();
                }
            }

            function updateNoticeBanner(hasLeadFilter) {
                const banner = document.getElementById('filterNoticeBanner');
                const noticeText = document.getElementById('filterNoticeText');
                const btn = document.getElementById('toggleAllBtn');

                if (!banner) return;

                if (hasLeadFilter) {
                    banner.classList.remove('hidden');
                    if (noticeText) {
                        noticeText.innerText = 'Đã tự động tải danh sách kỹ sư theo Lead được chọn';
                    }
                    if (btn) {
                        btn.innerText = 'Hiển thị tất cả kỹ sư';
                    }
                } else {
                    banner.classList.add('hidden');
                }
            }

            // Duplicate Ticket Checking Logic
            let debounceTimer = null;

            function debouncedCheckDuplicate() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    checkProjectDuplicate();
                }, 400);
            }

            function checkProjectDuplicate() {
                const projectId = document.getElementById('project_id') ? document.getElementById('project_id').value : '';
                const projectName = document.getElementById('project_name') ? document.getElementById('project_name').value.trim() : '';
                const excludeId = '{{ $ticket->id }}';

                if (!projectId && projectName.length < 2) {
                    hideDuplicateWarning();
                    return;
                }

                const url = new URL('{{ route("technical-tickets.check-duplicate") }}', window.location.origin);
                if (projectId) url.searchParams.append('project_id', projectId);
                if (projectName) url.searchParams.append('project_name', projectName);
                if (excludeId) url.searchParams.append('exclude_id', excludeId);

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (data.has_duplicate && data.duplicates && data.duplicates.length > 0) {
                            renderDuplicateWarning(data);
                        } else {
                            hideDuplicateWarning();
                        }
                    })
                    .catch(err => {
                        console.error('Error checking duplicate ticket:', err);
                    });
            }

            function hideDuplicateWarning() {
                const container = document.getElementById('duplicateWarningContainer');
                if (container) {
                    container.classList.add('hidden');
                    container.innerHTML = '';
                }
            }

            function renderDuplicateWarning(data) {
                const container = document.getElementById('duplicateWarningContainer');
                if (!container) return;

                let itemsHtml = data.duplicates.map(t => {
                    let statusBadge = `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-${t.status_color}-100 text-${t.status_color}-800 border border-${t.status_color}-200">${t.status_label}</span>`;
                    return `
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-2.5 bg-white rounded-lg border border-amber-200 hover:border-amber-300 shadow-2xs gap-2 transition-all">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-xs text-amber-900 font-mono">${t.code}</span>
                                    ${statusBadge}
                                    <span class="text-xs font-semibold text-gray-800">${t.title}</span>
                                </div>
                                <div class="text-[11px] text-gray-500 flex items-center gap-3 flex-wrap">
                                    <span><i class="far fa-user text-gray-400 mr-1"></i>Tạo bởi: <b>${t.creator_name}</b></span>
                                    <span><i class="fas fa-user-gear text-gray-400 mr-1"></i>Kỹ sư: <b>${t.engineers}</b></span>
                                    <span><i class="far fa-clock text-gray-400 mr-1"></i>${t.created_at} (${t.created_at_humans})</span>
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center">
                                <a href="${t.show_url}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white rounded transition-colors shadow-2xs">
                                    <span>Xem ticket</span>
                                    <i class="fas fa-arrow-up-right-from-square text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    `;
                }).join('');

                container.innerHTML = `
                    <div class="bg-amber-50 border-2 border-amber-300 rounded-xl p-4 shadow-sm space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-100 border border-amber-300 flex items-center justify-center text-amber-600 shrink-0 mt-0.5">
                                <i class="fas fa-triangle-exclamation text-sm animate-pulse"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wide">
                                        Cảnh báo: Phát hiện ${data.count} Ticket khác liên quan đến Dự án này!
                                    </h4>
                                    <span class="text-[11px] font-semibold text-amber-800 bg-amber-200/70 px-2 py-0.5 rounded-full">
                                        ${data.active_count} ticket đang hoạt động / xử lý
                                    </span>
                                </div>
                                <p class="text-xs text-amber-800 mt-1">
                                    Dự án này đã có ticket kỹ thuật được tạo trước đó. Vui lòng kiểm tra danh sách dưới đây để tránh trùng lặp công việc:
                                </p>
                            </div>
                        </div>

                        <div class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
                            ${itemsHtml}
                        </div>

                        <div class="text-[11px] text-amber-700 italic flex items-center gap-1.5 pt-1 border-t border-amber-200/60">
                            <i class="fas fa-info-circle"></i>
                            <span>Mẹo: Bạn có thể bấm vào ticket đã có để đối chiếu thông tin hoặc cập nhật trực tiếp.</span>
                        </div>
                    </div>
                `;

                container.classList.remove('hidden');
            }

            document.addEventListener('DOMContentLoaded', function() {
                updateSelectedCount();
                updateCoLeadsCount();

                // Initialize TomSelect for project_id
                if (document.getElementById('project_id')) {
                    new TomSelect('#project_id', {
                        placeholder: '-- Tìm kiếm và chọn dự án hệ thống --',
                        allowEmptyOption: true,
                        maxOptions: 100,
                        create: false,
                        onChange: function(value) {
                            if (value) {
                                const projNameInput = document.getElementById('project_name');
                                const item = this.options[value];
                                if (projNameInput && (!projNameInput.value || projNameInput.value.trim() === '')) {
                                    if (item && item.text) {
                                        projNameInput.value = item.text.trim();
                                    }
                                }
                            }
                            checkProjectDuplicate();
                        }
                    });
                }

                // Initialize TomSelect for supplier_id
                if (document.getElementById('supplier_id')) {
                    new TomSelect('#supplier_id', {
                        placeholder: '-- Tìm kiếm Vendor / Hãng --',
                        allowEmptyOption: true,
                        maxOptions: 100,
                        create: false
                    });
                }

                // Initialize TomSelect for team_lead_id (searchable select)
                if (document.getElementById('team_lead_id')) {
                    new TomSelect('#team_lead_id', {
                        placeholder: '-- Tìm kiếm và chọn Lead chính phụ trách * --',
                        allowEmptyOption: true,
                        maxOptions: 100,
                        create: false,
                        onChange: function(value) {
                            handlePrimaryLeadChange();
                        }
                    });
                }

                const leadSelect = document.getElementById('team_lead_id');
                const coLeadsChecked = document.querySelectorAll('.co-lead-checkbox:checked');
                if ((leadSelect && leadSelect.value) || coLeadsChecked.length > 0) {
                    fetchAndFilterMembers();
                }

                // Initial duplicate check if pre-filled
                checkProjectDuplicate();
            });

            document.addEventListener('click', function(e) {
                if (e.target && (e.target.type === 'datetime-local' || e.target.type === 'date')) {
                    try {
                        e.target.showPicker();
                    } catch (err) {}
                }
            });
            document.addEventListener('focusin', function(e) {
                if (e.target && (e.target.type === 'datetime-local' || e.target.type === 'date')) {
                    try {
                        e.target.showPicker();
                    } catch (err) {}
                }
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    @endpush
@endsection