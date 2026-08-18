@extends('layouts.app')

@section('title', 'Chi tiết Ticket Kỹ thuật')
@section('page-title', 'Chi tiết Ticket Kỹ thuật')

@section('content')
@push('styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush

<div x-data="{ 
    activeTab: 'details', 
    openLogModal: false, 
    logEditMode: false, 
    logActionUrl: '', 
    logData: { id: '', log_date: '{{ date('Y-m-d') }}', user_id: '{{ Auth::id() }}', serial_number: '', support_content: '', status: '{{ $ticket->status }}', customer_info: '', contact_info: '', notes: '' },
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
        this.logActionUrl = '/technical-tickets/{{ $ticket->id }}/support-logs/' + log.id;
        this.logData = {
            id: log.id,
            log_date: log.log_date ? log.log_date.substring(0, 10) : '{{ date('Y-m-d') }}',
            user_id: log.user_id,
            serial_number: log.serial_number || '',
            support_content: log.support_content || '',
            status: log.status || '{{ $ticket->status }}',
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
    <!-- Breadcrumb & Actions -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200 gap-4">
        <div class="flex items-center space-x-2">
            <a href="{{ route('technical-tickets.index') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Ticket: {{ $ticket->code }}</h2>
                <p class="text-xs text-gray-500">Tạo bởi: {{ $ticket->creator->name ?? 'N/A' }} | {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-2">
            @can('edit_technical_tickets')
                <a href="{{ route('technical-tickets.edit', $ticket->id) }}" class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white text-sm font-semibold rounded-lg hover:bg-yellow-600 transition-colors shadow-sm">
                    <i class="fas fa-edit mr-2"></i> Chỉnh sửa
                </a>
            @endcan

            @can('delete_technical_tickets')
                <form action="{{ route('technical-tickets.destroy', $ticket->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa ticket này và toàn bộ tài liệu đính kèm?');" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                        <i class="fas fa-trash-alt mr-2"></i> Xóa ticket
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <!-- Ticket Summary Banner -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Trạng thái</span>
                <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-bold bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800">
                    {{ $ticket->status_label }}
                </span>
            </div>
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Độ ưu tiên</span>
                <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-bold bg-{{ $ticket->priority_color }}-100 text-{{ $ticket->priority_color }}-800">
                    {{ $ticket->priority_label }}
                </span>
            </div>
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Kỹ sư phụ trách</span>
                <span class="text-sm font-bold text-gray-800 block mt-2">
                    <i class="fas fa-user-gear text-primary mr-1"></i> {{ $ticket->assignedTo->name ?? 'Chưa phân công' }}
                </span>
            </div>
            <div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Hạn xử lý (SLA)</span>
                <span class="text-sm font-bold block mt-2 {{ $ticket->is_overdue ? 'text-red-600' : 'text-gray-800' }}">
                    <i class="fas fa-clock mr-1"></i> {{ $ticket->sla_deadline ? $ticket->sla_deadline->format('d/m/Y H:i') : 'Không áp dụng' }}
                    @if($ticket->is_overdue)
                        <span class="text-xs font-bold bg-red-100 text-red-800 px-2 py-0.5 rounded-full ml-1">Trễ hạn</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Main Detail Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Panel (Left 2 Columns) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Tabs Menu -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="border-b border-gray-200 flex bg-gray-50">
                    <button @click="activeTab = 'details'" :class="activeTab === 'details' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-4 border-b-2 font-bold text-sm transition-colors focus:outline-none flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> Mô tả & Liên kết
                    </button>
                    <button @click="activeTab = 'documents'" :class="activeTab === 'documents' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-4 border-b-2 font-bold text-sm transition-colors focus:outline-none flex items-center">
                        <i class="fas fa-folder-open mr-2"></i> Tài liệu phát sinh ({{ $ticket->attachments->count() }})
                    </button>
                    <button @click="activeTab = 'reports'" :class="activeTab === 'reports' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-4 border-b-2 font-bold text-sm transition-colors focus:outline-none flex items-center">
                        <i class="fas fa-history mr-2"></i> Nhật ký Hỗ trợ / Report Tech ({{ $ticket->supportLogs->count() }})
                    </button>
                </div>

                <div class="p-6">
                    <!-- Tab 1: Details -->
                    <div x-show="activeTab === 'details'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-100 text-sm">
                            <div>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Loại Ticket</span>
                                <span class="font-semibold text-gray-800 block mt-1"><i class="fas fa-tag text-primary mr-1"></i>{{ $ticket->work_type_label }}</span>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Bộ phận yêu cầu</span>
                                <span class="font-semibold text-gray-800 block mt-1"><i class="fas fa-building-user text-primary mr-1"></i>{{ $ticket->department ?: 'N/A' }}</span>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Yêu cầu chi tiết</h3>
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                                {{ $ticket->description ?: 'Không có mô tả chi tiết.' }}
                            </div>
                        </div>

                        @if($ticket->solution)
                            <div>
                                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Nguyên nhân / Phương án / Cách xử lý</h3>
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-sm text-blue-900 whitespace-pre-line leading-relaxed">
                                    {{ $ticket->solution }}
                                </div>
                            </div>
                        @endif

                        <div class="border-t border-gray-150 pt-4">
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Phụ trách kỹ thuật & Sales</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="text-xs text-gray-400 font-semibold uppercase">Sales Owner</div>
                                    <div class="font-bold text-gray-800 mt-1"><i class="fas fa-user-tie text-blue-500 mr-1.5"></i>{{ $ticket->salesOwner->name ?? 'N/A' }}</div>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="text-xs text-gray-400 font-semibold uppercase">Team Lead</div>
                                    <div class="font-bold text-gray-800 mt-1"><i class="fas fa-user-shield text-indigo-500 mr-1.5"></i>{{ $ticket->teamLead->name ?? 'N/A' }}</div>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="text-xs text-gray-400 font-semibold uppercase">Engineer phụ trách</div>
                                    <div class="font-bold text-gray-800 mt-1"><i class="fas fa-user-gear text-emerald-500 mr-1.5"></i>{{ $ticket->assignedTo->name ?? 'Chưa phân công' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-150 pt-4">
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Liên kết & Thông tin dự án</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                @if($ticket->project_name)
                                    <div class="sm:col-span-2 flex items-center space-x-3 p-3 bg-blue-50/50 rounded-lg border border-blue-100/50">
                                        <i class="fas fa-diagram-project text-blue-500 text-lg w-6 text-center"></i>
                                        <div>
                                            <div class="text-xs text-gray-400 font-semibold">Dự án (Tên dự án/Partner/EU)</div>
                                            <div class="font-bold text-gray-800">{{ $ticket->project_name }}</div>
                                        </div>
                                    </div>
                                @endif
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <i class="fas fa-building text-blue-500 text-lg w-6 text-center"></i>
                                    <div>
                                        <div class="text-xs text-gray-400">Khách hàng</div>
                                        <div class="font-semibold text-gray-800">{{ $ticket->customer->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <i class="fas fa-diagram-project text-purple-500 text-lg w-6 text-center"></i>
                                    <div>
                                        <div class="text-xs text-gray-400">Liên kết Dự án (System)</div>
                                        <div class="font-semibold text-gray-800">{{ $ticket->project->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <i class="fas fa-calendar-check text-indigo-500 text-lg w-6 text-center"></i>
                                    <div>
                                        <div class="text-xs text-gray-400">Cơ hội (Opportunity)</div>
                                        <div class="font-semibold text-gray-800">{{ $ticket->opportunity->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <i class="fas fa-file-invoice text-emerald-500 text-lg w-6 text-center"></i>
                                    <div>
                                        <div class="text-xs text-gray-400">Đơn hàng (Sale Order)</div>
                                        <div class="font-semibold text-gray-800">{{ $ticket->sale->code ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <i class="fas fa-handshake text-orange-500 text-lg w-6 text-center"></i>
                                    <div>
                                        <div class="text-xs text-gray-400">Hãng / Vendor</div>
                                        <div class="font-semibold text-gray-800">{{ $ticket->supplier->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Documents Centralized Management -->
                    <div x-show="activeTab === 'documents'" class="space-y-6">
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-upload text-primary mr-2"></i> Tải lên tài liệu kỹ thuật
                            </h4>
                            <form action="{{ route('technical-tickets.attachments.upload', $ticket->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3">
                                @csrf
                                <div class="flex-1">
                                    <select name="document_type" required class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                        <option value="">-- Chọn loại tài liệu đính kèm --</option>
                                        @foreach($documentTypes as $key => $val)
                                            <option value="{{ $key }}">{{ $val }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="file" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90">
                                </div>
                                <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm">
                                    Đính kèm
                                </button>
                            </form>
                        </div>

                        <!-- Documents list grouped by document type -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-bold text-gray-800 border-b border-gray-100 pb-2">Danh sách tài liệu đã lưu trữ</h4>
                            
                            @php
                                $groupedAttachments = $ticket->attachments->groupBy('document_type');
                            @endphp

                            @forelse($documentTypes as $key => $name)
                                @if($groupedAttachments->has($key))
                                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm space-y-2">
                                        <h5 class="text-xs font-bold text-purple-700 uppercase tracking-wider flex items-center">
                                            <i class="fas fa-file-lines mr-1.5"></i> {{ $name }}
                                        </h5>
                                        <ul class="divide-y divide-gray-100 text-sm">
                                            @foreach($groupedAttachments->get($key) as $attach)
                                                <div class="flex justify-between items-center py-2">
                                                    <div class="flex items-center space-x-2">
                                                        <i class="fas fa-paperclip text-gray-400"></i>
                                                        <span class="font-medium text-gray-800 max-w-xs md:max-w-md truncate">{{ $attach->file_name }}</span>
                                                        <span class="text-xs text-gray-400">({{ round($attach->file_size / 1024, 1) }} KB)</span>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <a href="{{ route('technical-tickets.attachments.download', [$ticket->id, $attach->id]) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-xs flex items-center">
                                                            <i class="fas fa-download mr-1"></i> Tải về
                                                        </a>
                                                        <span class="text-gray-300">|</span>
                                                        <form action="{{ route('technical-tickets.attachments.delete', [$ticket->id, $attach->id]) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài liệu này?');" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-500 hover:text-red-700 font-semibold text-xs">
                                                                <i class="fas fa-trash"></i> Xóa
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @empty
                            @endforelse

                            @if($ticket->attachments->count() === 0)
                                <div class="text-center py-8 text-gray-400 italic text-sm">Không có tài liệu nào được đính kèm cho ticket này.</div>
                            @endif
                        </div>
                    </div>

                    <!-- Tab 3: Support Logs (Report Technical) -->
                    <div x-show="activeTab === 'reports'" class="space-y-6">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                            <h4 class="text-sm font-bold text-gray-800">Nhật ký hỗ trợ kỹ thuật (Report Tech)</h4>
                            @can('manage_technical_support_logs')
                                <button @click="logEditMode = false; logActionUrl = '{{ route('technical-tickets.support-logs.store', $ticket->id) }}'; logData = { id: '', log_date: '{{ date('Y-m-d') }}', user_id: '{{ Auth::id() }}', serial_number: '', support_content: '', status: '{{ $ticket->status }}', customer_info: '{{ $ticket->customer->name ?? '' }}', contact_info: '', notes: '' }; openLogModal = true; let found = engineersList.find(function(e){ return e.id == logData.user_id; }); engSearch = found ? found.name : ''; custSearch = logData.customer_info || '';" class="inline-flex items-center px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded hover:bg-primary/95 transition-colors shadow-sm">
                                    <i class="fas fa-plus mr-1"></i> Viết Nhật ký (Report Tech)
                                </button>
                            @endcan
                        </div>

                        <!-- Chronological logs listing -->
                        <div class="relative pl-6 border-l-2 border-blue-100 space-y-6 mt-4">
                            @forelse($ticket->supportLogs->sortByDesc('log_date') as $log)
                                <div class="relative">
                                    <!-- Timeline Dot -->
                                    <div class="absolute -left-8.5 top-1 bg-white border-2 border-blue-500 rounded-full h-4 w-4 flex items-center justify-center">
                                        <div class="bg-blue-500 rounded-full h-2 w-2"></div>
                                    </div>

                                    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm space-y-2">
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center border-b border-gray-50 pb-2 gap-2">
                                            <div>
                                                <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded mr-2"><i class="fas fa-calendar-alt mr-1"></i>{{ $log->log_date->format('d/m/Y') }}</span>
                                                <span class="text-sm font-bold text-gray-800">Kỹ sư: {{ $log->user->name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-{{ $log->status_color }}-100 text-{{ $log->status_color }}-800">
                                                    {{ $log->status_label }}
                                                </span>
                                                
                                                @can('manage_technical_support_logs')
                                                    <span class="text-gray-300">|</span>
                                                    <button @click="editLog({{ $log->id }})" class="text-blue-500 hover:text-blue-700 text-xs font-semibold">
                                                        Sửa
                                                    </button>
                                                    <span class="text-gray-300">|</span>
                                                    <form action="{{ route('technical-tickets.support-logs.destroy', [$ticket->id, $log->id]) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhật ký này?');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold">
                                                            Xóa
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </div>

                                        <div class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                                            <strong>Nội dung hỗ trợ:</strong><br>
                                            {{ $log->support_content }}
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-gray-50 text-xs text-gray-500">
                                            <div><strong>Số S/N:</strong> {{ $log->serial_number ?: 'N/A' }}</div>
                                            <div><strong>Thông tin khách hàng:</strong> {{ $log->customer_info ?: 'N/A' }}</div>
                                            <div><strong>Thông tin liên hệ:</strong> {{ $log->contact_info ?: 'N/A' }}</div>
                                        </div>

                                        @if($log->notes)
                                            <div class="bg-yellow-50 p-2 rounded text-xs text-yellow-800 border border-yellow-100">
                                                <strong>Ghi chú:</strong> {{ $log->notes }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-6 text-gray-400 italic text-sm -ml-6">Chưa có nhật ký hỗ trợ kỹ thuật nào.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Panel (Right 1 Column) -->
        <div class="space-y-6">
            <!-- Ticket Info Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-800 border-b border-gray-100 pb-2 flex items-center">
                    <i class="fas fa-file-invoice text-primary mr-2"></i> Tiến trình xử lý
                </h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Ngày tạo:</span>
                        <span class="font-medium text-gray-800">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Cập nhật:</span>
                        <span class="font-medium text-gray-800">{{ $ticket->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Hoàn thành:</span>
                        <span class="font-medium text-gray-800">{{ $ticket->resolved_at ? $ticket->resolved_at->format('d/m/Y H:i') : 'Chưa hoàn thành' }}</span>
                    </div>
                    @if($ticket->resolved_at)
                        <div class="flex justify-between border-t border-gray-100 pt-2 text-primary font-semibold">
                            <span>Thời gian xử lý:</span>
                            <span>{{ round($ticket->created_at->diffInMinutes($ticket->resolved_at) / 60, 1) }} giờ</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Customer Contacts Card -->
            @if($ticket->customer)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                    <h3 class="text-sm font-bold text-gray-800 border-b border-gray-100 pb-2 flex items-center">
                        <i class="fas fa-address-book text-primary mr-2"></i> Liên hệ Khách hàng
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="font-semibold text-gray-800">{{ $ticket->customer->name }}</div>
                        @if($ticket->customer->phone)
                            <div><i class="fas fa-phone text-gray-400 mr-2"></i> {{ $ticket->customer->phone }}</div>
                        @endif
                        @if($ticket->customer->email)
                            <div><i class="fas fa-envelope text-gray-400 mr-2"></i> {{ $ticket->customer->email }}</div>
                        @endif
                        @if($ticket->customer->address)
                            <div class="text-xs text-gray-500 leading-relaxed"><i class="fas fa-map-marker-alt text-gray-400 mr-2"></i> {{ $ticket->customer->address }}</div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- 4. Support Log Create/Edit Modal (Alpine.js powered) -->
    <div x-show="openLogModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div @click.away="openLogModal = false" class="bg-white rounded-xl shadow-lg max-w-lg w-full border border-gray-200 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="text-md font-bold text-gray-900" x-text="logEditMode ? 'Sửa Nhật ký hỗ trợ (Report Tech)' : 'Thêm Nhật ký hỗ trợ (Report Tech)'"></h3>
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

                <div class="grid grid-cols-2 gap-4">
                    <!-- Date -->
                    <div>
                        <label for="log_date" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ngày hỗ trợ (*)</label>
                        <input type="date" name="log_date" id="log_date" required class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.log_date">
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
                        <label for="serial_number" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Số S/N</label>
                        <input type="text" name="serial_number" id="serial_number" placeholder="Số S/N thiết bị..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.serial_number">
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="log_status" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Trạng thái công việc (*)</label>
                        <select name="status" id="log_status" required class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.status">
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
                        <label for="contact_info" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Thông tin người liên hệ</label>
                        <input type="text" name="contact_info" id="contact_info" placeholder="Họ tên, SĐT, Chức vụ..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.contact_info">
                    </div>
                </div>

                <!-- Support Content -->
                <div>
                    <label for="support_content" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nội dung hỗ trợ (*)</label>
                    <textarea name="support_content" id="support_content" required rows="8" placeholder="Nhập chi tiết nội dung công việc xử lý..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.support_content"></textarea>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ghi chú</label>
                    <textarea name="notes" id="notes" rows="4" placeholder="Ghi chú thêm..." class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" x-model="logData.notes"></textarea>
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
    window.technicalSupportLogs = @json($ticket->supportLogs);
</script>
@endpush
@endsection
