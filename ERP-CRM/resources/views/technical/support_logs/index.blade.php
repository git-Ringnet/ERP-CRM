@extends('layouts.app')

@section('title', 'Nhật ký hỗ trợ kỹ thuật (Report Tech)')
@section('page-title', 'Nhật ký hỗ trợ kỹ thuật (Report Tech)')

@section('content')
@push('styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush

<div x-data="{ 
    openLogModal: false, 
    logEditMode: false, 
    logActionUrl: '', 
    logData: { id: '', technical_ticket_id: '', log_date: '{{ date('Y-m-d') }}', user_id: '{{ Auth::id() }}', serial_number: '', support_content: '', status: 'open', customer_info: '', contact_info: '', notes: '' },
    engineersList: window.technicalEngineers || [],
    customersList: window.technicalCustomers || [],
    supportLogsList: window.technicalSupportLogs || [],
    openEng: false,
    openCust: false,
    engSearch: '',
    custSearch: '',
    editLog(id) {
        var log = this.supportLogsList.find(function(l){ return l.id == id; });
        if (!log) return;
        this.logEditMode = true;
        this.logActionUrl = '/technical/support-logs/' + log.id;
        this.logData = {
            id: log.id,
            technical_ticket_id: log.technical_ticket_id || '',
            log_date: log.log_date ? log.log_date.substring(0, 10) : '{{ date('Y-m-d') }}',
            user_id: log.user_id,
            serial_number: log.serial_number || '',
            support_content: log.support_content || '',
            status: log.status || 'open',
            customer_info: log.customer_info || '',
            contact_info: log.contact_info || '',
            notes: log.notes || ''
        };
        this.openLogModal = true;
        var eng = this.engineersList.find(function(e){ return e.id == log.user_id; });
        this.engSearch = eng ? eng.name : '';
        this.custSearch = log.customer_info || '';
    }
}" class="space-y-6">
    <!-- Header Block -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200 gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Báo cáo công việc kỹ thuật</h2>
            <p class="text-sm text-gray-500">Tra cứu lịch sử nhật ký hỗ trợ kỹ thuật và cập nhật báo cáo công việc hàng ngày</p>
        </div>
        @can('manage_technical_support_logs')
            <button @click="logEditMode = false; logActionUrl = '{{ route('technical.support-logs.store-centralized') }}'; logData = { id: '', technical_ticket_id: '', log_date: '{{ date('Y-m-d') }}', user_id: '{{ Auth::id() }}', serial_number: '', support_content: '', status: 'open', customer_info: '', contact_info: '', notes: '' }; openLogModal = true; let found = engineersList.find(function(e){ return e.id == logData.user_id; }); engSearch = found ? found.name : ''; custSearch = '';" class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> Viết Nhật ký (Report Tech)
            </button>
        @endcan
    </div>

    <!-- Filters Block -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('technical.support-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <label for="date_from" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Từ ngày</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label for="date_to" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Đến ngày</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label for="user_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kỹ sư</label>
                <select name="user_id" id="user_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    @foreach($engineers as $eng)
                        <option value="{{ $eng->id }}" {{ request('user_id') == $eng->id ? 'selected' : '' }}>{{ $eng->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="ticket_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ticket liên quan</label>
                <select name="ticket_id" id="ticket_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    @foreach($tickets as $t)
                        <option value="{{ $t->id }}" {{ request('ticket_id') == $t->id ? 'selected' : '' }}>{{ $t->code }} - {{ Str::limit($t->title, 30) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm">
                    Lọc
                </button>
                <a href="{{ route('technical.support-logs.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-colors border border-gray-200">
                    Reset
                </a>
            </div>
        </form>
        <div class="mt-3">
            <form method="GET" action="{{ route('technical.support-logs.index') }}">
                <!-- Retain other filter inputs in search -->
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                <input type="hidden" name="ticket_id" value="{{ request('ticket_id') }}">
                
                <div class="relative">
                    <input type="text" name="search" placeholder="Tìm kiếm theo S/N, nội dung hỗ trợ, thông tin khách hàng, người liên hệ..." value="{{ request('search') }}" class="w-full pl-10 pr-4 py-2 border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <div class="absolute left-3 top-2.5 text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Central Logs Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                    <tr class="divide-x divide-gray-150 border-b border-gray-200">
                        <th class="px-4 py-2.5 text-center w-12">STT</th>
                        <th class="px-4 py-2.5 w-32 text-center">Ngày ghi nhận</th>
                        <th class="px-4 py-2.5 w-40">Ticket liên quan</th>
                        <th class="px-4 py-2.5 w-44">Kỹ sư thực hiện</th>
                        <th class="px-4 py-2.5 w-[450px]">Nội dung hỗ trợ kỹ thuật</th>
                        <th class="px-4 py-2.5 w-48">Khách hàng / Người liên hệ</th>
                        <th class="px-4 py-2.5 text-center w-32">Trạng thái công việc</th>
                        <th class="px-4 py-2.5 text-center w-28">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($supportLogs as $log)
                        <tr class="hover:bg-gray-50/50 divide-x divide-gray-100">
                            <td class="px-4 py-2.5 text-center text-gray-500">
                                {{ ($supportLogs->currentPage() - 1) * $supportLogs->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-4 py-2.5 text-center font-semibold text-gray-700 whitespace-nowrap">
                                {{ $log->log_date->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-2.5 font-bold text-gray-700 whitespace-nowrap">
                                @if($log->ticket)
                                    <a href="{{ route('technical-tickets.show', $log->ticket->id) }}" class="text-blue-600 hover:underline">
                                        {{ $log->ticket->code }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2.5 font-medium text-gray-800">
                                {{ $log->user->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="text-gray-900 whitespace-pre-line leading-relaxed line-clamp-3 hover:line-clamp-none transition-all duration-200 cursor-pointer break-words" title="Click to expand/collapse">
                                    {{ $log->support_content }}
                                </div>
                                @if($log->serial_number)
                                    <div class="text-xs text-purple-600 font-semibold mt-1"><i class="fas fa-barcode mr-1"></i>S/N: {{ $log->serial_number }}</div>
                                @endif
                                @if($log->notes)
                                    <div class="text-xs text-amber-600 italic mt-0.5"><i class="fas fa-sticky-note mr-1"></i>Note: {{ $log->notes }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-gray-600">
                                <div class="font-semibold text-gray-800">{{ $log->customer_info ?: 'N/A' }}</div>
                                @if($log->contact_info)
                                    <div class="text-xs text-gray-400 mt-0.5"><i class="fas fa-user-circle mr-1"></i>PIC: {{ $log->contact_info }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-{{ $log->status_color }}-100 text-{{ $log->status_color }}-800">
                                    {{ $log->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-2">
                                    @can('manage_technical_support_logs')
                                        <button @click="editLog({{ $log->id }})" class="inline-flex items-center px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded hover:bg-yellow-600 transition-colors">
                                            Sửa
                                        </button>
                                        <form action="/technical/support-logs/{{ $log->id }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhật ký này?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-2 py-1 bg-red-600 text-white text-xs font-bold rounded hover:bg-red-700 transition-colors">
                                                Xóa
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">Không có quyền</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-gray-400 italic">Không tìm thấy nhật ký hỗ trợ nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($supportLogs->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $supportLogs->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    <!-- 4. Support Log Centralized Create/Edit Modal (Alpine.js powered) -->
    <div x-show="openLogModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div @click.away="openLogModal = false" class="bg-white rounded-xl shadow-lg max-w-lg w-full border border-gray-200 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="text-md font-bold text-gray-900" x-text="logEditMode ? 'Sửa Nhật ký hỗ trợ (Report Tech)' : 'Viết Nhật ký hỗ trợ (Report Tech)'"></h3>
                <button @click="openLogModal = false" class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form :action="logActionUrl" method="POST" class="p-6 space-y-4">
                @csrf
                <template x-if="logEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- Ticket Selection (Disabled on Edit Mode for safety, otherwise allowed) -->
                <div>
                    <label for="technical_ticket_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ticket Kỹ Thuật liên quan</label>
                    <select name="technical_ticket_id" id="modal_technical_ticket_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.technical_ticket_id" :disabled="logEditMode">
                        <option value="">-- Không liên kết Ticket / Khác --</option>
                        @foreach($tickets as $t)
                            <option value="{{ $t->id }}">{{ $t->code }} - {{ $t->title }}</option>
                        @endforeach
                    </select>
                    <!-- Keep hidden input on Edit Mode so the form still submits the ID correctly -->
                    <template x-if="logEditMode">
                        <input type="hidden" name="technical_ticket_id" :value="logData.technical_ticket_id">
                    </template>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Date -->
                    <div>
                        <label for="modal_log_date" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ngày hỗ trợ (*)</label>
                        <input type="date" name="log_date" id="modal_log_date" required class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.log_date">
                    </div>

                    <!-- Engineer Searchable Select -->
                    <div class="relative">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kỹ sư thực hiện (*)</label>
                        <div class="relative">
                            <input type="text" 
                                   placeholder="-- Chọn Kỹ sư --" 
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8"
                                   x-model="engSearch"
                                   @focus="openEng = true"
                                   @input="openEng = true"
                                   @click.away="setTimeout(function(){ openEng = false; let found = engineersList.find(function(e){ return e.id == logData.user_id; }); engSearch = found ? found.name : '' }, 200)">
                            <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <div x-show="openEng" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto p-1 space-y-0.5" x-cloak>
                            <template x-for="eng in engineersList" :key="eng.id">
                                <button type="button" 
                                        x-show="engSearch === '' || eng.name.toLowerCase().includes(engSearch.toLowerCase())" 
                                        @click="logData.user_id = eng.id; engSearch = eng.name; openEng = false" 
                                        class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors" 
                                        x-text="eng.name"></button>
                            </template>
                        </div>
                        <input type="hidden" name="user_id" :value="logData.user_id">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Serial Number -->
                    <div>
                        <label for="modal_serial_number" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Số S/N</label>
                        <input type="text" name="serial_number" id="modal_serial_number" placeholder="Số S/N thiết bị..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.serial_number">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="modal_log_status" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Cập nhật Trạng thái Ticket (*)</label>
                        <select name="status" id="modal_log_status" required class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.status">
                            <option value="open">Mới tạo (Open)</option>
                            <option value="assigned">Đã phân công</option>
                            <option value="pending">Tạm ngưng (Pending)</option>
                            <option value="escalate">Cần hỗ trợ thêm (Escalate)</option>
                            <option value="completed">Hoàn thành (Completed)</option>
                            <option value="closed">Đã đóng (Closed)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Customer Searchable Select -->
                    <div class="relative">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Thông tin khách hàng</label>
                        <div class="relative">
                            <input type="text" 
                                   placeholder="-- Chọn Khách hàng --" 
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8"
                                   x-model="custSearch"
                                   @focus="openCust = true"
                                   @input="openCust = true"
                                   @click.away="setTimeout(function(){ openCust = false; custSearch = logData.customer_info || '' }, 200)">
                            <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                        <div x-show="openCust" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto p-1 space-y-0.5" x-cloak>
                            <button type="button" 
                                    @click="logData.customer_info = ''; custSearch = ''; openCust = false" 
                                    class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors italic text-gray-400">
                                -- Chọn Khách hàng --
                            </button>
                            <template x-for="cust in customersList" :key="cust.name">
                                <button type="button" 
                                        x-show="custSearch === '' || cust.name.toLowerCase().includes(custSearch.toLowerCase())" 
                                        @click="logData.customer_info = cust.name; custSearch = cust.name; openCust = false" 
                                        class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors" 
                                        x-text="cust.name"></button>
                            </template>
                        </div>
                        <input type="hidden" name="customer_info" :value="logData.customer_info">
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <label for="modal_contact_info" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Thông tin người liên hệ</label>
                        <input type="text" name="contact_info" id="modal_contact_info" placeholder="Họ tên, SĐT, Chức vụ..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.contact_info">
                    </div>
                </div>

                <!-- Support Content -->
                <div>
                    <label for="modal_support_content" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nội dung hỗ trợ (*)</label>
                    <textarea name="support_content" id="modal_support_content" required rows="8" placeholder="Nhập chi tiết nội dung công việc xử lý..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.support_content"></textarea>
                </div>

                <!-- Notes -->
                <div>
                    <label for="modal_notes" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ghi chú</label>
                    <textarea name="notes" id="modal_notes" rows="4" placeholder="Ghi chú thêm..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.notes"></textarea>
                </div>

                <!-- Modal Actions -->
                <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
                    <button type="button" @click="openLogModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors bg-white">
                        Hủy bỏ
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm" x-text="logEditMode ? 'Lưu thay đổi' : 'Thêm nhật ký'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.technicalEngineers = @json($engineers->map(fn($e) => ['id' => $e->id, 'name' => $e->name]));
    window.technicalCustomers = @json($customers->map(fn($c) => ['name' => $c->name]));
    window.technicalSupportLogs = @json($supportLogs->items());
</script>
@endpush
@endsection
