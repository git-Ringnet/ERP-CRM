@extends('layouts.app')

@section('title', 'Chỉnh sửa Ticket Kỹ thuật')
@section('page-title', 'Chỉnh sửa Ticket Kỹ thuật')

@section('content')
@push('styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush

<div class="max-w-5xl mx-auto space-y-6" x-data="{ 
    openCust: false, custSearch: '', customerId: '{{ old('customer_id', $ticket->customer_id) }}', customerName: '',
    openSales: false, salesSearch: '', salesOwnerId: '{{ old('sales_owner_id', $ticket->sales_owner_id) }}', salesOwnerName: '',
    openLead: false, leadSearch: '', teamLeadId: '{{ old('team_lead_id', $ticket->team_lead_id) }}', teamLeadName: '',
    openEng: false, engSearch: '', assignedTo: '{{ old('assigned_to', $ticket->assigned_to) }}', assignedToName: '',
    
    usersList: window.editTicketUsers || [],
    customersList: window.editTicketCustomers || [],
    
    init() {
        var user = this.usersList.find(function(u){ return u.id == this.salesOwnerId; }.bind(this));
        this.salesOwnerName = user ? user.name : '';
        
        var lead = this.usersList.find(function(u){ return u.id == this.teamLeadId; }.bind(this));
        this.teamLeadName = lead ? lead.name : '';
        
        var eng = this.usersList.find(function(u){ return u.id == this.assignedTo; }.bind(this));
        this.assignedToName = eng ? eng.name : '';
        
        var cust = this.customersList.find(function(c){ return c.id == this.customerId; }.bind(this));
        this.customerName = cust ? cust.name : '';
    }
}">
    <div class="flex items-center space-x-2">
        <a href="{{ route('technical-tickets.show', $ticket->id) }}" class="text-gray-500 hover:text-gray-700 transition-colors">
            <i class="fas fa-arrow-left text-lg"></i>
        </a>
        <h2 class="text-lg font-bold text-gray-900">Chỉnh sửa Ticket: {{ $ticket->code }}</h2>
    </div>

    <form method="POST" action="{{ route('technical-tickets.update', $ticket->id) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- CARD 1: THÔNG TIN CHUNG (COMMON INFO) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mr-2"></span>
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">a) Thông tin chung</h3>
            </div>
            
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Ticket Title -->
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">Tiêu đề / Tên công việc (*)</label>
                        <input type="text" name="title" id="title" required placeholder="Ví dụ: Triển khai tường lửa Fortigate cho Khách hàng A" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary @error('title') border-red-500 @enderror" value="{{ old('title', $ticket->title) }}">
                        @error('title')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Work Type (Loại ticket) -->
                    <div>
                        <label for="work_type" class="block text-sm font-semibold text-gray-700 mb-1">Loại Ticket (*)</label>
                        <select name="work_type" id="work_type" required class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary @error('work_type') border-red-500 @enderror">
                            <option value="">-- Chọn loại ticket --</option>
                            <option value="survey" {{ old('work_type', $ticket->work_type) === 'survey' ? 'selected' : '' }}>Khảo sát / Tư vấn / Thiết kế</option>
                            <option value="BOM" {{ old('work_type', $ticket->work_type) === 'BOM' ? 'selected' : '' }}>BOM Support</option>
                            <option value="documentation" {{ old('work_type', $ticket->work_type) === 'documentation' ? 'selected' : '' }}>Technical Documents</option>
                            <option value="POC" {{ old('work_type', $ticket->work_type) === 'POC' ? 'selected' : '' }}>POC / Demo</option>
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
                        <label for="priority" class="block text-sm font-semibold text-gray-700 mb-1">Priority (*)</label>
                        <select name="priority" id="priority" required class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary @error('priority') border-red-500 @enderror">
                            <option value="medium" {{ old('priority', $ticket->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ old('priority', $ticket->priority) === 'high' ? 'selected' : '' }}>High</option>
                            <option value="low" {{ old('priority', $ticket->priority) === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="urgent" {{ old('priority', $ticket->priority) === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                        @error('priority')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Requester info -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Người yêu cầu</label>
                        <input type="text" readonly class="w-full border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500 cursor-not-allowed" value="{{ $ticket->creator->name ?? 'N/A' }}">
                    </div>

                    <!-- Department -->
                    <div>
                        <label for="department" class="block text-sm font-semibold text-gray-700 mb-1">Bộ phận yêu cầu</label>
                        <input type="text" name="department" id="department" list="department_list" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" value="{{ old('department', $ticket->department) }}">
                        <datalist id="department_list">
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}">
                            @endforeach
                        </datalist>
                    </div>

                    <!-- Sales Owner (Searchable Select) -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Sales Owner (*)</label>
                        <div class="relative">
                            <input type="text" 
                                   placeholder="-- Chọn Sales Owner --" 
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8"
                                   x-model="salesSearch"
                                   @focus="openSales = true"
                                   @input="openSales = true"
                                   @click.away="setTimeout(function(){ openSales = false; var found = usersList.find(function(u){ return u.id == salesOwnerId; }); salesSearch = found ? found.name : '' }, 200)">
                            <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <div x-show="openSales" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto p-1 space-y-0.5" x-cloak>
                            <template x-for="user in usersList" :key="user.id">
                                <button type="button" 
                                        x-show="salesSearch === '' || user.name.toLowerCase().includes(salesSearch.toLowerCase())" 
                                        @click="salesOwnerId = user.id; salesSearch = user.name; openSales = false" 
                                        class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors" 
                                        x-text="user.name"></button>
                            </template>
                        </div>
                        <input type="hidden" name="sales_owner_id" :value="salesOwnerId">
                    </div>

                    <!-- Vendor Relation -->
                    <div>
                        <label for="supplier_id" class="block text-sm font-semibold text-gray-700 mb-1">Vendor (Hãng liên quan)</label>
                        <select name="supplier_id" id="supplier_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                            <option value="">-- Không chọn / Không có --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('supplier_id', $ticket->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Customer Searchable Select -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Khách hàng liên quan</label>
                        <div class="relative">
                            <input type="text" 
                                   placeholder="-- Chọn Khách hàng --" 
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8"
                                   x-model="custSearch"
                                   @focus="openCust = true"
                                   @input="openCust = true"
                                   @click.away="setTimeout(function(){ openCust = false; var found = customersList.find(function(c){ return c.id == customerId; }); custSearch = found ? found.name : '' }, 200)">
                            <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <div x-show="openCust" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto p-1 space-y-0.5" x-cloak>
                            <button type="button" 
                                    @click="customerId = ''; custSearch = ''; openCust = false" 
                                    class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors italic text-gray-400">
                                -- Không chọn / Không có --
                            </button>
                            <template x-for="cust in customersList" :key="cust.id">
                                <button type="button" 
                                        x-show="custSearch === '' || cust.name.toLowerCase().includes(custSearch.toLowerCase())" 
                                        @click="customerId = cust.id; custSearch = cust.name; openCust = false" 
                                        class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors" 
                                        x-text="cust.name"></button>
                            </template>
                        </div>
                        <input type="hidden" name="customer_id" :value="customerId">
                    </div>

                    <!-- Project / Partner / EU Name -->
                    <div>
                        <label for="project_name" class="block text-sm font-semibold text-gray-700 mb-1">Dự án (Tên dự án/Partner/EU)</label>
                        <input type="text" name="project_name" id="project_name" placeholder="Nhập tên dự án, đối tác hoặc người dùng cuối..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" value="{{ old('project_name', $ticket->project_name) }}">
                    </div>

                    <!-- Due Date (Thời gian yêu cầu xử lý) -->
                    <div>
                        <label for="sla_deadline" class="block text-sm font-semibold text-gray-700 mb-1">Due Date (Hạn xử lý yêu cầu)</label>
                        <input type="datetime-local" name="sla_deadline" id="sla_deadline" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" value="{{ old('sla_deadline', $ticket->sla_deadline ? $ticket->sla_deadline->format('Y-m-d\TH:i') : '') }}">
                    </div>
                </div>

                <!-- Group: Liên kết CRM (Grouped for neatness) -->
                <div class="border-t border-gray-150 pt-4 space-y-4">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Liên kết nhanh CRM</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Project Link -->
                        <div>
                            <label for="project_id" class="block text-xs font-semibold text-gray-600 mb-1">Liên kết Dự án (System Project)</label>
                            <select name="project_id" id="project_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="">-- Không chọn / Không có --</option>
                                @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}" {{ old('project_id', $ticket->project_id) == $proj->id ? 'selected' : '' }}>{{ $proj->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Opportunity Link -->
                        <div>
                            <label for="opportunity_id" class="block text-xs font-semibold text-gray-600 mb-1">Liên kết Cơ hội (Opportunity)</label>
                            <select name="opportunity_id" id="opportunity_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="">-- Không chọn / Không có --</option>
                                @foreach($opportunities as $opp)
                                    <option value="{{ $opp->id }}" {{ old('opportunity_id', $ticket->opportunity_id) == $opp->id ? 'selected' : '' }}>{{ $opp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Sales Link -->
                        <div>
                            <label for="sale_id" class="block text-xs font-semibold text-gray-600 mb-1">Liên kết Đơn hàng (Sales Order)</label>
                            <select name="sale_id" id="sale_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                <option value="">-- Không chọn / Không có --</option>
                                @foreach($sales as $s)
                                    <option value="{{ $s->id }}" {{ old('sale_id', $ticket->sale_id) == $s->id ? 'selected' : '' }}>{{ $s->code }} - {{ $s->customer_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: PHÂN CÔNG & TRẠNG THÁI (TECHNICAL PICKUP) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 mr-2"></span>
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">b) Thông tin Technical Pickup</h3>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Team Lead Searchable Select -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Team Lead</label>
                        <div class="relative">
                            <input type="text" 
                                   placeholder="-- Chọn Team Lead --" 
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8"
                                   x-model="leadSearch"
                                   @focus="openLead = true"
                                   @input="openLead = true"
                                   @click.away="setTimeout(function(){ openLead = false; var found = usersList.find(function(u){ return u.id == teamLeadId; }); leadSearch = found ? found.name : '' }, 200)">
                            <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <div x-show="openLead" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto p-1 space-y-0.5" x-cloak>
                            <button type="button" 
                                    @click="teamLeadId = ''; leadSearch = ''; openLead = false" 
                                    class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors italic text-gray-400">
                                -- Không chọn / Không có --
                            </button>
                            <template x-for="user in usersList" :key="user.id">
                                <button type="button" 
                                        x-show="leadSearch === '' || user.name.toLowerCase().includes(leadSearch.toLowerCase())" 
                                        @click="teamLeadId = user.id; leadSearch = user.name; openLead = false" 
                                        class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors" 
                                        x-text="user.name"></button>
                            </template>
                        </div>
                        <input type="hidden" name="team_lead_id" :value="teamLeadId">
                    </div>

                    <!-- Engineer Searchable Select -->
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Engineer (Kỹ sư thực hiện)</label>
                        <div class="relative">
                            <input type="text" 
                                   placeholder="-- Chọn Kỹ sư --" 
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8"
                                   x-model="engSearch"
                                   @focus="openEng = true"
                                   @input="openEng = true"
                                   @click.away="setTimeout(function(){ openEng = false; var found = usersList.find(function(u){ return u.id == assignedTo; }); engSearch = found ? found.name : '' }, 200)">
                            <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <div x-show="openEng" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto p-1 space-y-0.5" x-cloak>
                            <button type="button" 
                                    @click="assignedTo = ''; engSearch = ''; openEng = false" 
                                    class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors italic text-gray-400">
                                -- Chưa phân công --
                            </button>
                            <template x-for="user in usersList" :key="user.id">
                                <button type="button" 
                                        x-show="engSearch === '' || user.name.toLowerCase().includes(engSearch.toLowerCase())" 
                                        @click="assignedTo = user.id; engSearch = user.name; openEng = false" 
                                        class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors" 
                                        x-text="user.name"></button>
                            </template>
                        </div>
                        <input type="hidden" name="assigned_to" :value="assignedTo">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-1">Trạng thái Ticket</label>
                        <select name="status" id="status" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                            <option value="open" {{ old('status', $ticket->status) === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="assigned" {{ old('status', $ticket->status) === 'assigned' ? 'selected' : '' }}>Assigned</option>
                            <option value="in_progress" {{ old('status', $ticket->status) === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="waiting" {{ old('status', $ticket->status) === 'waiting' ? 'selected' : '' }}>Waiting (Customer/Partner/Vendor)</option>
                            <option value="completed" {{ old('status', $ticket->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="closed" {{ old('status', $ticket->status) === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: CHI TIẾT & ĐÁNH GIÁ (DESCRIPTION & EVALUATION) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                <span class="w-2.5 h-2.5 rounded-full bg-purple-500 mr-2"></span>
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">c) & d) Mô tả & Đánh giá phương án</h3>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Yêu cầu chi tiết</label>
                    <textarea name="description" id="description" rows="6" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('description', $ticket->description) }}</textarea>
                </div>

                <!-- Solution / Evaluation -->
                <div>
                    <label for="solution" class="block text-sm font-semibold text-gray-700 mb-1">Nguyên nhân / Phương án / Cách xử lý</label>
                    <textarea name="solution" id="solution" rows="4" placeholder="Kỹ sư hoặc Lead điền nguyên nhân, phương án xử lý, cấu hình chi tiết..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ old('solution', $ticket->solution) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 flex justify-end space-x-3 shadow-sm">
            <a href="{{ route('technical-tickets.show', $ticket->id) }}" class="px-4 py-2 border border-gray-300 bg-white rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                Hủy bỏ
            </a>
            <button type="submit" class="px-5 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                Lưu thay đổi
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    window.editTicketUsers = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]));
    window.editTicketCustomers = @json($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name]));
</script>
@endpush
@endsection
