@extends('layouts.app')

@section('title', 'Chi tiết Ticket Kỹ thuật')
@section('page-title', 'Chi tiết Ticket Kỹ thuật')

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
        .whitespace-pre-line,
        .font-semibold {
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
        }
    </style>
@endpush

@section('content')

    <script>
        function ticketShowData() {
            return {
                activeTab: 'details',
                currentUserId: '{{ Auth::id() }}',
                currentUserName: @json(Auth::user()->name),
                openLogModal: false,
                openProgressModal: false,
                openHandoverModal: false,
                logEditMode: false,
                logActionUrl: '',
                logData: {
                    id: '',
                    log_date: '{{ date('Y-m-d') }}',
                    user_id: '{{ Auth::id() }}',
                    serial_number: '',
                    support_content: '',
                    status: '{{ $ticket->status }}',
                    customer_info: @json($ticket->customer->name ?? ''),
                    contact_info: '',
                    notes: ''
                },
                engineersList: @json($engineers->map(fn($e) => ['id' => $e->id, 'name' => $e->name])),
                customersList: @json($customers->map(fn($c) => ['name' => $c->name])),
                supportLogsList: @json($ticket->supportLogs),
                openEng: false,
                openCust: false,
                engSearch: @json(Auth::user()->name),
                custSearch: @json($ticket->customer->name ?? ''),
                engTyping: false,
                custTyping: false,
                handoverSearch: '',
                selectedHandoverEngineers: [],
                openCreateLogModal() {
                    this.logEditMode = false;
                    this.logActionUrl = '{{ route('technical-tickets.support-logs.store', $ticket->id) }}';
                    this.logData = {
                        id: '',
                        log_date: '{{ date('Y-m-d') }}',
                        user_id: this.currentUserId,
                        serial_number: '',
                        support_content: '',
                        status: '{{ $ticket->status }}',
                        customer_info: @json($ticket->customer->name ?? ''),
                        contact_info: '',
                        notes: ''
                    };
                    this.engSearch = this.currentUserName;
                    this.custSearch = @json($ticket->customer->name ?? '');
                    this.openLogModal = true;
                },
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
            };
        }
    </script>

    <div x-data="ticketShowData()" class="space-y-6">
        <!-- Read-Only Notice for General Technical Staff -->
        @if($isReadOnly)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 flex items-center justify-between text-xs text-amber-800 shadow-xs">
                <div class="flex items-center space-x-2">
                    <span class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 font-bold shrink-0">
                        <i class="fas fa-eye"></i>
                    </span>
                    <span>Bạn đang xem ticket này ở chế độ <strong>Chỉ đọc (View-Only)</strong> do chưa được phân công xử lý hoặc không thuộc Lead phụ trách.</span>
                </div>
                <span class="px-2 py-0.5 rounded bg-amber-200 text-amber-900 font-bold uppercase tracking-wider text-[10px]">Chỉ xem</span>
            </div>
        @endif

        <!-- Breadcrumb & Actions -->
        <div
            class="flex flex-col md:flex-row md:justify-between md:items-center bg-white p-4 rounded-xl shadow-sm border border-gray-200 gap-4">
            <div class="flex items-center space-x-2">
                <a href="{{ route('technical-tickets.index') }}"
                    class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Ticket: {{ $ticket->code }}</h2>
                    <p class="text-xs text-gray-500">Tạo bởi: {{ $ticket->creator->name ?? 'N/A' }} |
                        {{ $ticket->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            @php
                $canPickup = $ticket->canUserPickup(auth()->user());
            @endphp

            <div class="flex items-center flex-wrap gap-2">
                @if (empty($ticket->assigned_to) && $ticket->activeEngineers->isEmpty())
                    @if ($canPickup)
                        <form action="{{ route('technical-tickets.pickup', $ticket->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                <i class="fas fa-hand-holding-hand mr-2"></i> Tự nhận (Pickup)
                            </button>
                        </form>
                    @endif
                @endif

                <!-- Handover Button -->
                @if($canHandover && !in_array($ticket->status, ['completed', 'closed']))
                    <button type="button" @click="openHandoverModal = true"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                        <i class="fas fa-exchange-alt mr-2"></i> Bàn giao (Handover)
                    </button>
                @endif

                @if(!in_array($ticket->status, ['open', 'completed', 'closed']))
                    @if($canUpdateProgress)
                        <button @click="openProgressModal = true"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                            <i class="fas fa-tasks mr-2"></i> Cập nhật tiến độ
                        </button>
                    @endif
                @endif

                @if(in_array($ticket->status, ['assigned', 'in_progress', 'waiting']))
                    <!-- Requester confirms completion (Sales Manager cannot complete) -->
                    @if(($ticket->created_by === auth()->id() || auth()->user()->hasAnyRole(['super_admin', 'director'])) && (!auth()->user()->hasRole('sales_manager') || auth()->user()->hasAnyRole(['super_admin', 'director'])))
                        <form action="{{ route('technical-tickets.update-progress', $ticket->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xác nhận hoàn tất ticket này?');">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="confirm_complete">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                                <i class="fas fa-circle-check mr-2"></i> Xác nhận hoàn tất
                            </button>
                        </form>
                    @endif
                @endif

                <!-- Workflow Buttons for Completed Status (Đã hoàn tất, chờ đóng) -->
                @if($ticket->status === 'completed')
                    <!-- Tech Lead/Admin Close action -->
                    @if(auth()->user()->hasRole('technical_lead') || auth()->user()->hasAnyRole(['super_admin', 'director']))
                        <form action="{{ route('technical-tickets.update-progress', $ticket->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn Đóng ticket này?');">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="close_ticket">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors shadow-sm">
                                <i class="fas fa-folder-closed mr-2"></i> Đóng Ticket
                            </button>
                        </form>
                    @endif
                @endif

                @if(in_array($ticket->status, ['open', 'assigned']))
                    @can('edit_technical_tickets')
                        @if(!auth()->user()->hasRole('technical_engineer'))
                            <a href="{{ route('technical-tickets.edit', $ticket->id) }}"
                                class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white text-sm font-semibold rounded-lg hover:bg-yellow-600 transition-colors shadow-sm">
                                <i class="fas fa-edit mr-2"></i> Chỉnh sửa
                            </a>
                        @endif
                    @endcan

                    @can('delete_technical_tickets')
                        <form action="{{ route('technical-tickets.destroy', $ticket->id) }}" method="POST"
                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa ticket này và toàn bộ tài liệu đính kèm?');"
                            class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                                <i class="fas fa-trash-alt mr-2"></i> Xóa ticket
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start space-x-3 shadow-sm">
                <span class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0 mt-0.5">
                    <i class="fas fa-exclamation-circle text-xs"></i>
                </span>
                <div>
                    <h4 class="text-sm font-bold text-red-800 mb-1">Cảnh báo quy trình Technical:</h4>
                    <ul class="list-disc list-inside text-xs text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Ticket Summary Banner -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Trạng thái</span>
                    <span
                        class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-bold bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800">
                        {{ $ticket->status_label }}
                    </span>
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Độ ưu tiên</span>
                    <span
                        class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-bold bg-{{ $ticket->priority_color }}-100 text-{{ $ticket->priority_color }}-800">
                        {{ $ticket->priority_label }}
                    </span>
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Kỹ sư phụ trách</span>
                    <div class="flex flex-wrap gap-1 mt-2">
                        @forelse($ticket->activeEngineers as $eng)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold border border-blue-200">
                                <i class="fas fa-user-gear text-[10px] mr-1"></i> {{ $eng->name }}
                            </span>
                        @empty
                            @if($ticket->assignedTo)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold border border-blue-200">
                                    <i class="fas fa-user-gear text-[10px] mr-1"></i> {{ $ticket->assignedTo->name }}
                                </span>
                            @else
                                <span class="text-sm font-semibold text-gray-400 italic">Chưa phân công</span>
                            @endif
                        @endforelse

                        @foreach($ticket->formerEngineers as $fEng)
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-[11px] font-medium border border-gray-200 line-through"
                                title="Đã bàn giao ngày {{ $fEng->pivot->handed_over_at ? \Carbon\Carbon::parse($fEng->pivot->handed_over_at)->format('d/m/Y H:i') : '' }}">
                                <i class="fas fa-history text-[9px] mr-1"></i> {{ $fEng->name }} (Bàn giao)
                            </span>
                        @endforeach
                    </div>
                </div>
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Hạn xử lý (SLA)</span>
                    <span class="text-sm font-bold block mt-2 {{ $ticket->is_overdue ? 'text-red-600' : 'text-gray-800' }}">
                        <i class="fas fa-clock mr-1"></i>
                        {{ $ticket->sla_deadline ? $ticket->sla_deadline->format('d/m/Y H:i') : 'Không áp dụng' }}
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
                        <button @click="activeTab = 'details'"
                            :class="activeTab === 'details' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 border-b-2 font-bold text-sm transition-colors focus:outline-none flex items-center">
                            <i class="fas fa-info-circle mr-2"></i> Mô tả & Liên kết
                        </button>
                        <button @click="activeTab = 'documents'"
                            :class="activeTab === 'documents' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 border-b-2 font-bold text-sm transition-colors focus:outline-none flex items-center">
                            <i class="fas fa-folder-open mr-2"></i> Tài liệu ({{ $ticket->attachments->count() }})
                        </button>
                        <button @click="activeTab = 'reports'"
                            :class="activeTab === 'reports' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 border-b-2 font-bold text-sm transition-colors focus:outline-none flex items-center">
                            <i class="fas fa-history mr-2"></i> Nhật ký Hỗ trợ
                            ({{ $ticket->supportLogs->count() }})
                        </button>
                        <button @click="activeTab = 'comments'"
                            :class="activeTab === 'comments' ? 'border-primary text-primary bg-white' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 border-b-2 font-bold text-sm transition-colors focus:outline-none flex items-center">
                            <i class="fas fa-comments mr-2"></i> Trao đổi ({{ $ticket->comments->count() }})
                        </button>
                    </div>

                    <div class="p-6">
                        <!-- Tab 1: Details -->
                        <div x-show="activeTab === 'details'" class="space-y-6">
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-100 text-sm">
                                <div>
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Loại
                                        Ticket</span>
                                    <span class="font-semibold text-gray-800 block mt-1"><i
                                            class="fas fa-tag text-primary mr-1"></i>{{ $ticket->work_type_label }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Trưởng nhóm (Lead chính)</span>
                                    <span class="font-semibold text-gray-800 block mt-1">
                                        <i class="fas fa-user-tie text-purple-500 mr-1"></i>{{ $ticket->teamLead->name ?? 'Chưa chọn Lead' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Lead phối hợp</span>
                                    @php
                                        $coLeads = !empty($ticket->co_lead_ids) ? \App\Models\User::whereIn('id', (array)$ticket->co_lead_ids)->get() : collect();
                                    @endphp
                                    <div class="mt-1">
                                        @forelse($coLeads as $cLead)
                                            <span class="inline-block bg-teal-50 text-teal-800 text-[11px] font-semibold px-2 py-0.5 rounded border border-teal-200 mr-1 mb-1">
                                                {{ $cLead->name }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400 text-xs italic">Không có</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            @if($ticket->description)
                            <div>
                                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Yêu cầu chi tiết
                                </h3>
                                <div
                                    class="bg-gray-50 p-4 rounded-lg border border-gray-100 text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                                    {{ $ticket->description }}
                                </div>
                            </div>
                            @endif

                            @if($ticket->solution)
                                <div>
                                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2">Nguyên nhân /
                                        Phương án / Cách xử lý</h3>
                                    <div
                                        class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-sm text-blue-900 whitespace-pre-line leading-relaxed">
                                        {{ $ticket->solution }}
                                    </div>
                                </div>
                            @endif

                            <!-- Dynamic Ticket Details -->
                            @if($ticket->ticket_details && count($ticket->ticket_details) > 0)
                                <div class="border-t border-gray-150 pt-4">
                                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Chi tiết bổ sung
                                        (theo loại Ticket)</h3>
                                    <div
                                        class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                                        @if($ticket->work_type === 'survey')
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Hình thức họp</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['meeting_type'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Thời gian họp</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ !empty($ticket->ticket_details['meeting_time']) ? date('d/m/Y H:i', strtotime($ticket->ticket_details['meeting_time'])) : 'N/A' }}</span>
                                            </div>
                                            @if(($ticket->ticket_details['meeting_type'] ?? '') === 'Offline')
                                                <div class="md:col-span-2">
                                                    <span class="text-xs font-bold text-gray-400 block">Địa chỉ họp Offline</span>
                                                    <span
                                                        class="font-semibold text-gray-800">{{ $ticket->ticket_details['meeting_address'] ?? 'N/A' }}</span>
                                                </div>
                                            @endif
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Nội dung / mục tiêu</span>
                                                <span
                                                    class="font-semibold text-gray-800 whitespace-pre-line">{{ $ticket->ticket_details['meeting_goal'] ?? 'N/A' }}</span>
                                            </div>
                                        @elseif($ticket->work_type === 'BOM')
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Yêu cầu kỹ thuật / Spec</span>
                                                <span
                                                    class="font-semibold text-gray-800 whitespace-pre-line">{{ $ticket->ticket_details['spec_requirements'] ?? 'N/A' }}</span>
                                            </div>
                                        @elseif($ticket->work_type === 'documentation')
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Mô tả yêu cầu tài liệu</span>
                                                <span
                                                    class="font-semibold text-gray-800 whitespace-pre-line">{{ $ticket->ticket_details['doc_description'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Bản chào giá / BOM tham
                                                    chiếu</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['doc_bom'] ?? 'N/A' }}</span>
                                            </div>
                                        @elseif($ticket->work_type === 'POC')
                                            @php
                                                $pocDevices = $ticket->ticket_details['poc_devices'] ?? [];
                                                if (empty($pocDevices) && !empty($ticket->ticket_details['poc_model'])) {
                                                    $pocDevices = [[
                                                        'name' => $ticket->ticket_details['poc_model'],
                                                        'quantity' => $ticket->ticket_details['poc_quantity'] ?? 1,
                                                        'note' => '',
                                                    ]];
                                                }
                                            @endphp

                                            <div class="col-span-full bg-indigo-50/40 rounded-xl p-4 border border-indigo-100 mb-2">
                                                <span class="text-xs font-bold text-indigo-900 uppercase tracking-wider block mb-2">
                                                    <i class="fas fa-server mr-1 text-indigo-600"></i> Danh sách thiết bị mượn PoC / Demo
                                                </span>
                                                @if(!empty($pocDevices) && is_array($pocDevices) && count($pocDevices) > 0)
                                                    <div class="overflow-x-auto">
                                                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                                                            <thead>
                                                                <tr class="text-left text-gray-500 font-semibold bg-white/70">
                                                                    <th class="py-2 px-3">STT</th>
                                                                    <th class="py-2 px-3">Thiết bị / Model</th>
                                                                    <th class="py-2 px-3 text-center">Số lượng mượn</th>
                                                                    <th class="py-2 px-3">Ghi chú / Serial</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                                @foreach($pocDevices as $pIdx => $pDev)
                                                                    @if(!empty($pDev['name']))
                                                                        <tr>
                                                                            <td class="py-2 px-3 text-gray-400 font-medium">{{ $pIdx + 1 }}</td>
                                                                            <td class="py-2 px-3 font-bold text-gray-900">{{ $pDev['name'] }}</td>
                                                                            <td class="py-2 px-3 text-center">
                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                                                                    {{ $pDev['quantity'] ?? 1 }}
                                                                                </span>
                                                                            </td>
                                                                            <td class="py-2 px-3 text-gray-600 italic">{{ $pDev['note'] ?? '-' }}</td>
                                                                        </tr>
                                                                    @endif
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-400 italic">Chưa có thông tin thiết bị mượn</span>
                                                @endif
                                            </div>

                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Yêu cầu kế hoạch/phương án PoC</span>
                                                <span class="font-semibold text-gray-800">{{ $ticket->ticket_details['poc_require_plan'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Ngày mượn thiết bị</span>
                                                <span class="font-semibold text-gray-800">{{ !empty($ticket->ticket_details['poc_borrow_date']) ? date('d/m/Y', strtotime($ticket->ticket_details['poc_borrow_date'])) : 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Ngày trả thiết bị</span>
                                                <span class="font-semibold text-gray-800">{{ !empty($ticket->ticket_details['poc_return_date']) ? date('d/m/Y', strtotime($ticket->ticket_details['poc_return_date'])) : 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-3">
                                                <span class="text-xs font-bold text-gray-400 block">Địa điểm triển khai POC</span>
                                                <span class="font-semibold text-gray-800">{{ $ticket->ticket_details['poc_location'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-3">
                                                <span class="text-xs font-bold text-gray-400 block">Mục tiêu POC</span>
                                                <span class="font-semibold text-gray-800 whitespace-pre-line">{{ $ticket->ticket_details['poc_goal'] ?? 'N/A' }}</span>
                                            </div>
                                        @elseif($ticket->work_type === 'deployment')
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Hình thức triển khai</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['deploy_type'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Thời gian triển khai</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ !empty($ticket->ticket_details['deploy_time']) ? date('d/m/Y H:i', strtotime($ticket->ticket_details['deploy_time'])) : 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Địa chỉ triển khai</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['deploy_address'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Phạm vi công việc (Scope of Work
                                                    - SoW)</span>
                                                <span
                                                    class="font-semibold text-gray-800 whitespace-pre-line">{{ $ticket->ticket_details['deploy_sow'] ?? 'N/A' }}</span>
                                            </div>
                                        @elseif($ticket->work_type === 'after_sales')
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Contact liên hệ</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['after_sales_contact'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">S/N thiết bị lỗi</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['after_sales_serial'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Mô tả vấn đề / Sự cố</span>
                                                <span
                                                    class="font-semibold text-gray-800 whitespace-pre-line">{{ $ticket->ticket_details['after_sales_problem'] ?? 'N/A' }}</span>
                                            </div>
                                        @elseif($ticket->work_type === 'event')
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Tên sự kiện (Event Name)</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['event_name'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Thời gian tổ chức</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ !empty($ticket->ticket_details['event_time']) ? date('d/m/Y H:i', strtotime($ticket->ticket_details['event_time'])) : 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Đối tượng tham gia</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['event_attendees'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Địa điểm tổ chức</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['event_location'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Cử Speaker tham gia?</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['event_speaker'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Chuẩn bị Slide trình bày?</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['event_slide'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Triển khai Demo trực
                                                    tiếp?</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['event_demo'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Yêu cầu khác</span>
                                                <span
                                                    class="font-semibold text-gray-800 whitespace-pre-line">{{ $ticket->ticket_details['event_notes'] ?? 'N/A' }}</span>
                                            </div>
                                        @elseif($ticket->work_type === 'training')
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Đối tượng đào tạo</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['training_audience'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Hình thức</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['training_format'] ?? 'N/A' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-400 block">Thời gian đào tạo</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ !empty($ticket->ticket_details['training_time']) ? date('d/m/Y H:i', strtotime($ticket->ticket_details['training_time'])) : 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Địa điểm đào tạo (nếu
                                                    Offline)</span>
                                                <span
                                                    class="font-semibold text-gray-800">{{ $ticket->ticket_details['training_location'] ?? 'N/A' }}</span>
                                            </div>
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Nội dung / Mục tiêu đề
                                                    xuất</span>
                                                <span
                                                    class="font-semibold text-gray-800 whitespace-pre-line">{{ $ticket->ticket_details['training_goal'] ?? 'N/A' }}</span>
                                            </div>
                                        @elseif($ticket->work_type === 'other')
                                            <div class="md:col-span-2">
                                                <span class="text-xs font-bold text-gray-400 block">Mô tả yêu cầu</span>
                                                <span
                                                    class="font-semibold text-gray-800 whitespace-pre-line">{{ $ticket->ticket_details['other_description'] ?? 'N/A' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="border-t border-gray-150 pt-4">
                                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Phụ trách kỹ thuật
                                    & Sales</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <div class="text-xs text-gray-400 font-semibold uppercase">Sales Owner</div>
                                        <div class="font-bold text-gray-800 mt-1"><i
                                                class="fas fa-user-tie text-blue-500 mr-1.5"></i>{{ $ticket->salesOwner->name ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <div class="text-xs text-gray-400 font-semibold uppercase">Team Lead</div>
                                        <div class="font-bold text-gray-800 mt-1"><i
                                                class="fas fa-user-shield text-indigo-500 mr-1.5"></i>{{ $ticket->teamLead->name ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <div class="text-xs text-gray-400 font-semibold uppercase">Engineer phụ trách</div>
                                        <div class="font-bold text-gray-800 mt-1"><i
                                                class="fas fa-user-gear text-emerald-500 mr-1.5"></i>{{ $ticket->assignedTo->name ?? 'Chưa phân công' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-150 pt-4">
                                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Liên kết & Thông
                                    tin dự án</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    @if($ticket->project_name)
                                        <div
                                            class="sm:col-span-2 flex items-center space-x-3 p-3 bg-blue-50/50 rounded-lg border border-blue-100/50">
                                            <i class="fas fa-diagram-project text-blue-500 text-lg w-6 text-center"></i>
                                            <div>
                                                <div class="text-xs text-gray-400 font-semibold">Dự án (Tên dự án/Partner/EU)
                                                </div>
                                                <div class="font-bold text-gray-800">{{ $ticket->project_name }}</div>
                                            </div>
                                        </div>
                                    @endif
                                    @if(!in_array($ticket->work_type, ['event', 'other']))
                                        @if($ticket->customer)
                                            <div
                                                class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                                <i class="fas fa-building text-blue-500 text-lg w-6 text-center"></i>
                                                <div>
                                                    <div class="text-xs text-gray-400">Khách hàng</div>
                                                    <div class="font-semibold text-gray-800">{{ $ticket->customer->name }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        <div
                                            class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                            <i class="fas fa-diagram-project text-purple-500 text-lg w-6 text-center"></i>
                                            <div>
                                                <div class="text-xs text-gray-400">Liên kết Dự án (System)</div>
                                                <div class="font-semibold text-gray-800">{{ $ticket->project->name ?? 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @if($ticket->supplier)
                                        <div
                                            class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                            <i class="fas fa-handshake text-orange-500 text-lg w-6 text-center"></i>
                                            <div>
                                                <div class="text-xs text-gray-400">Vendor / Đối tác</div>
                                                <div class="font-semibold text-gray-800">{{ $ticket->supplier->name }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Documents Centralized Management -->
                        <div x-show="activeTab === 'documents'" class="space-y-6">
                            @if($canAttachFile)
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-upload text-primary mr-2"></i> Tải lên tài liệu kỹ thuật
                                    </h4>
                                    <form action="{{ route('technical-tickets.attachments.upload', $ticket->id) }}"
                                        method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3">
                                        @csrf
                                        <div class="flex-1">
                                            <select name="document_type" required
                                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                                                <option value="">-- Chọn loại tài liệu đính kèm --</option>
                                                @foreach($documentTypes as $key => $val)
                                                    <option value="{{ $key }}">{{ $val }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" name="files[]" multiple required
                                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-primary/90">
                                        </div>
                                        <button type="submit"
                                            class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm">
                                            Đính kèm
                                        </button>
                                    </form>
                                </div>
                            @endif

                            <!-- Documents list grouped by document type -->
                            <div class="space-y-4">
                                <h4 class="text-sm font-bold text-gray-800 border-b border-gray-100 pb-2">Danh sách tài liệu đã lưu trữ</h4>

                                @php
                                    $groupedAttachments = $ticket->attachments->groupBy('document_type');
                                @endphp

                                @forelse($documentTypes as $key => $name)
                                    @if($groupedAttachments->has($key))
                                        <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm space-y-2">
                                            <h5
                                                class="text-xs font-bold text-purple-700 uppercase tracking-wider flex items-center">
                                                <i class="fas fa-file-lines mr-1.5"></i> {{ $name }}
                                            </h5>
                                            <ul class="divide-y divide-gray-100 text-sm">
                                                @foreach($groupedAttachments->get($key) as $attach)
                                                    <div class="flex justify-between items-center py-2">
                                                        <div class="flex items-center space-x-2">
                                                            <i class="fas fa-paperclip text-gray-400"></i>
                                                            <span
                                                                class="font-medium text-gray-800 max-w-xs md:max-w-md truncate">{{ $attach->file_name }}</span>
                                                            <span
                                                                class="text-xs text-gray-400">({{ round($attach->file_size / 1024, 1) }}
                                                                KB)</span>
                                                            @if($attach->is_initial)
                                                                <span class="text-[10px] text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.2 rounded font-medium">
                                                                    <i class="fas fa-file-signature text-[9px] mr-0.5"></i> Yêu cầu gốc
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <a href="{{ route('technical-tickets.attachments.download', [$ticket->id, $attach->id]) }}"
                                                                class="text-blue-600 hover:text-blue-800 font-semibold text-xs flex items-center">
                                                                <i class="fas fa-download mr-1"></i> Tải về
                                                            </a>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                @empty
                                @endforelse

                                @if($ticket->attachments->count() === 0)
                                    <div class="text-center py-8 text-gray-400 italic text-sm">Không có tài liệu nào được đính
                                        kèm cho ticket này.</div>
                                @endif
                            </div>
                        </div>

                        <!-- Tab 3: Support Logs (Report Technical) -->
                        <div x-show="activeTab === 'reports'" class="space-y-6">
                            <div class="flex justify-between items-center pb-2 border-b border-gray-100">
                                <h4 class="text-sm font-bold text-gray-800">Nhật ký hỗ trợ kỹ thuật (Report Tech)</h4>
                                @if($canUpdateProgress)
                                    @can('manage_technical_support_logs')
                                        <button
                                            @click="openCreateLogModal()"
                                            class="inline-flex items-center px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded hover:bg-primary/95 transition-colors shadow-sm">
                                            <i class="fas fa-plus mr-1"></i> Viết Nhật ký (Report Tech)
                                        </button>
                                    @endcan
                                @endif
                            </div>

                            <!-- Chronological logs listing -->
                            <div class="relative pl-6 border-l-2 border-blue-100 space-y-6 mt-4">
                                @forelse($ticket->supportLogs->sortByDesc('log_date') as $log)
                                    <div class="relative">
                                        <!-- Timeline Dot -->
                                        <div
                                            class="absolute -left-8.5 top-1 bg-white border-2 border-blue-500 rounded-full h-4 w-4 flex items-center justify-center">
                                            <div class="bg-blue-500 rounded-full h-2 w-2"></div>
                                        </div>

                                        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm space-y-2">
                                            <div
                                                class="flex flex-col sm:flex-row sm:justify-between sm:items-center border-b border-gray-50 pb-2 gap-2">
                                                <div>
                                                    <span
                                                        class="text-xs font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded mr-2"><i
                                                             class="fas fa-calendar-alt mr-1"></i>{{ $log->log_date->format('d/m/Y') }}</span>
                                                    <span class="text-sm font-bold text-gray-800">Kỹ sư:
                                                        {{ $log->user->name ?? 'N/A' }}</span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span
                                                        class="px-2 py-0.5 rounded-full text-xs font-bold bg-{{ $log->status_color }}-100 text-{{ $log->status_color }}-800">
                                                        {{ $log->status_label }}
                                                    </span>

                                                    @if($canUpdateProgress && ($log->user_id === auth()->id() || auth()->user()->hasAnyRole(['super_admin', 'director'])))
                                                        @can('manage_technical_support_logs')
                                                            <span class="text-gray-300">|</span>
                                                            <button @click="editLog({{ $log->id }})"
                                                                class="text-blue-500 hover:text-blue-700 text-xs font-semibold">
                                                                Sửa
                                                            </button>
                                                            <span class="text-gray-300">|</span>
                                                            <form
                                                                action="{{ route('technical-tickets.support-logs.destroy', [$ticket->id, $log->id]) }}"
                                                                method="POST"
                                                                onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhật ký này?');"
                                                                class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="text-red-500 hover:text-red-700 text-xs font-semibold">
                                                                    Xóa
                                                                </button>
                                                            </form>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="text-sm text-gray-700 whitespace-pre-line leading-relaxed break-all" style="word-break: break-word; overflow-wrap: anywhere;">
                                                <strong>Nội dung hỗ trợ:</strong><br>
                                                {{ $log->support_content }}
                                            </div>

                                            <div
                                                class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-gray-50 text-xs text-gray-500">
                                                <div><strong>Số S/N:</strong> <span style="word-break: break-word; overflow-wrap: anywhere;">{{ $log->serial_number ?: 'N/A' }}</span></div>
                                                <div><strong>Thông tin khách hàng:</strong> <span style="word-break: break-word; overflow-wrap: anywhere;">{{ $log->customer_info ?: 'N/A' }}</span>
                                                </div>
                                                <div><strong>Thông tin liên hệ:</strong> <span style="word-break: break-word; overflow-wrap: anywhere;">{{ $log->contact_info ?: 'N/A' }}</span></div>
                                            </div>

                                            @if($log->notes)
                                                <div
                                                    class="bg-yellow-50 p-2 rounded text-xs text-yellow-800 border border-yellow-100 break-all" style="word-break: break-word; overflow-wrap: anywhere;">
                                                    <strong>Ghi chú:</strong> {{ $log->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-6 text-gray-400 italic text-sm -ml-6">Chưa có nhật ký hỗ trợ kỹ
                                        thuật nào.</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Tab 4: Trao đổi / Thảo luận (Discussion) -->
                        <div x-show="activeTab === 'comments'" class="space-y-6">
                            @if($canComment)
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-paper-plane text-primary mr-2"></i> Gửi nội dung trao đổi
                                    </h4>
                                    <form action="{{ route('technical-tickets.comments.store', $ticket->id) }}" method="POST" class="space-y-3">
                                        @csrf
                                        <div>
                                            <textarea name="comment" required rows="3"
                                                placeholder="Nhập nội dung câu hỏi, phản hồi hoặc trao đổi trực tiếp trên ticket này..."
                                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"></textarea>
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit"
                                                class="px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm flex items-center">
                                                <i class="fas fa-paper-plane mr-1.5"></i> Gửi trao đổi
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @else
                                <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 text-xs text-gray-500 flex items-center gap-2">
                                    <i class="fas fa-info-circle text-blue-500 text-sm"></i>
                                    <span>Bạn đang xem ticket ở chế độ chỉ đọc. Chỉ người yêu cầu, Leads và Kỹ sư được phân công mới có thể gửi nội dung trao đổi.</span>
                                </div>
                            @endif

                            <div class="space-y-4">
                                <h4 class="text-sm font-bold text-gray-800 border-b border-gray-100 pb-2">Lịch sử trao đổi</h4>
                                
                                <div class="space-y-4">
                                    @forelse($ticket->comments as $comment)
                                        <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex items-start space-x-3">
                                            <!-- User Avatar / Initials -->
                                            <div class="w-10 h-10 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-primary font-bold shrink-0">
                                                {{ substr($comment->user->name ?? 'U', 0, 1) }}
                                            </div>
                                            <div class="flex-1 space-y-1">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-bold text-gray-800">{{ $comment->user->name ?? 'Người dùng' }}</span>
                                                    <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }} ({{ $comment->created_at->format('d/m/Y H:i') }})</span>
                                                </div>
                                                <div class="text-sm text-gray-600 whitespace-pre-line leading-relaxed break-all" style="word-break: break-word; overflow-wrap: anywhere;">
                                                    {{ $comment->comment }}
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-8 text-gray-400 italic text-sm">Chưa có ý kiến trao đổi nào trên ticket này.</div>
                                    @endforelse
                                </div>
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
                            <span
                                class="font-medium text-gray-800">{{ $ticket->resolved_at ? $ticket->resolved_at->format('d/m/Y H:i') : 'Chưa hoàn thành' }}</span>
                        </div>
                        @if($ticket->resolved_at)
                            <div class="flex justify-between border-t border-gray-100 pt-2 text-primary font-semibold">
                                <span>Thời gian xử lý:</span>
                                <span>{{ round($ticket->created_at->diffInMinutes($ticket->resolved_at) / 60, 1) }} giờ</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Support Log Create/Edit Modal (Alpine.js powered) -->
        <div x-show="openLogModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-2 sm:p-4 bg-black/60 backdrop-blur-xs" x-cloak>
            <div @click.away="openLogModal = false"
                class="bg-white rounded-2xl shadow-2xl max-w-xl w-full max-h-[90vh] flex flex-col border border-gray-200 overflow-hidden transform transition-all my-auto">
                <!-- Modal Header (Fixed at top) -->
                <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50/90 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-primary flex items-center justify-center text-sm font-bold">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900"
                            x-text="logEditMode ? 'Sửa Nhật ký hỗ trợ (Report Tech)' : 'Thêm Nhật ký hỗ trợ (Report Tech)'">
                        </h3>
                    </div>
                    <button type="button" @click="openLogModal = false"
                        class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center transition-colors focus:outline-none">
                        <i class="fas fa-times text-base"></i>
                    </button>
                </div>

                <!-- Modal Form (Scrollable body + Fixed footer) -->
                <form :action="logActionUrl" method="POST" class="flex flex-col flex-1 overflow-hidden">
                    @csrf
                    <template x-if="logEditMode">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <!-- Scrollable Body -->
                    <div class="p-5 space-y-3.5 overflow-y-auto flex-1">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- Date -->
                            <div>
                                <label for="log_date" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ngày hỗ trợ (*)</label>
                                <input type="date" name="log_date" id="log_date" required
                                    class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                    x-model="logData.log_date">
                            </div>

                            <!-- Engineer Searchable Select -->
                            <div class="relative">
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kỹ sư thực hiện (*)</label>
                                <div class="relative">
                                    <input type="text" placeholder="-- Chọn Kỹ sư --"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8 bg-white"
                                        x-model="engSearch" @focus="openEng = true" @click="openEng = true"
                                        @input="openEng = true; engTyping = true"
                                        @click.away="setTimeout(function(){ openEng = false; engTyping = false; const found = engineersList.find(e => e.id == logData.user_id); engSearch = found ? found.name : currentUserName }, 200)">
                                    <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                                <div x-show="openEng"
                                    class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto p-1 space-y-0.5"
                                    x-cloak>
                                    <template x-for="eng in engineersList" :key="eng.id">
                                        <button type="button"
                                            x-show="!engTyping || eng.name.toLowerCase().includes(engSearch.toLowerCase())"
                                            @mousedown.prevent="logData.user_id = eng.id; engSearch = eng.name; openEng = false; engTyping = false"
                                            class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors"
                                            :class="{'font-bold text-primary bg-blue-50': logData.user_id == eng.id}"
                                            x-text="eng.name"></button>
                                    </template>
                                </div>
                                <input type="hidden" name="user_id" :value="logData.user_id">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- Serial Number -->
                            <div>
                                <label for="serial_number" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Số S/N</label>
                                <input type="text" name="serial_number" id="serial_number" placeholder="Số S/N thiết bị..."
                                    class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                    x-model="logData.serial_number">
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="log_status" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Trạng thái công việc (*)</label>
                                <select name="status" id="log_status" required
                                    class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                    x-model="logData.status">
                                    <option value="open">Mới tạo (Open)</option>
                                    <option value="assigned">Đã phân công</option>
                                    <option value="pending">Tạm ngưng (Pending)</option>
                                    <option value="escalate">Cần hỗ trợ thêm (Escalate)</option>
                                    <option value="completed">Hoàn thành (Completed)</option>
                                    <option value="closed">Đã đóng (Closed)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- Customer Searchable Select -->
                            <div class="relative">
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Thông tin khách hàng</label>
                                <div class="relative">
                                    <input type="text" placeholder="-- Chọn Khách hàng --"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary pr-8 bg-white"
                                        x-model="custSearch" @focus="openCust = true" @click="openCust = true"
                                        @input="openCust = true; custTyping = true"
                                        @click.away="setTimeout(function(){ openCust = false; custTyping = false; custSearch = logData.customer_info || '' }, 200)">
                                    <div class="absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                                <div x-show="openCust"
                                    class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto p-1 space-y-0.5"
                                    x-cloak>
                                    <button type="button"
                                        @mousedown.prevent="logData.customer_info = ''; custSearch = ''; openCust = false; custTyping = false"
                                        class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors italic text-gray-400">
                                        -- Chọn Khách hàng --
                                    </button>
                                    <template x-for="cust in customersList" :key="cust.name">
                                        <button type="button"
                                            x-show="!custTyping || cust.name.toLowerCase().includes(custSearch.toLowerCase())"
                                            @mousedown.prevent="logData.customer_info = cust.name; custSearch = cust.name; openCust = false; custTyping = false"
                                            class="w-full text-left px-2 py-1.5 rounded hover:bg-gray-100 text-xs transition-colors"
                                            :class="{'font-bold text-primary bg-blue-50': logData.customer_info == cust.name}"
                                            x-text="cust.name"></button>
                                    </template>
                                </div>
                                <input type="hidden" name="customer_info" :value="logData.customer_info">
                            </div>

                            <!-- Contact Info -->
                            <div>
                                <label for="contact_info" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Thông tin người liên hệ</label>
                                <input type="text" name="contact_info" id="contact_info" placeholder="Họ tên, SĐT, Chức vụ..."
                                    class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                    x-model="logData.contact_info">
                            </div>
                        </div>

                        <!-- Support Content -->
                        <div>
                            <label for="support_content" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nội dung hỗ trợ (*)</label>
                            <textarea name="support_content" id="support_content" required rows="3"
                                placeholder="Nhập chi tiết nội dung công việc xử lý..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                x-model="logData.support_content"></textarea>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ghi chú</label>
                            <textarea name="notes" id="notes" rows="2" placeholder="Ghi chú thêm..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                x-model="logData.notes"></textarea>
                        </div>
                    </div>

                    <!-- Modal Actions (Fixed at bottom) -->
                    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 flex justify-end space-x-3 shrink-0">
                        <button type="button" @click="openLogModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors bg-white">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm"
                            x-text="logEditMode ? 'Lưu thay đổi' : 'Thêm nhật ký'"></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 5. Quick Progress / Evaluation Update Modal -->
        <div x-show="openProgressModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-2 sm:p-4 bg-black/60 backdrop-blur-xs" x-cloak>
            <div @click.away="openProgressModal = false"
                class="bg-white rounded-2xl shadow-2xl max-w-xl w-full max-h-[90vh] flex flex-col border border-gray-200 overflow-hidden transform transition-all my-auto">
                <!-- Modal Header (Fixed at top) -->
                <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50/90 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900">
                            Cập nhật tiến độ & Phương án xử lý
                        </h3>
                    </div>
                    <button type="button" @click="openProgressModal = false"
                        class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center transition-colors focus:outline-none">
                        <i class="fas fa-times text-base"></i>
                    </button>
                </div>

                <!-- Modal Form (Scrollable body + Fixed footer) -->
                <form action="{{ route('technical-tickets.update-progress', $ticket->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
                    @csrf
                    @method('PUT')

                    <!-- Action flag -->
                    <input type="hidden" name="action" value="update_solution">

                    <!-- Scrollable Body -->
                    <div class="p-5 space-y-3.5 overflow-y-auto flex-1">
                        <!-- Status Checkboxes -->
                        @if(!in_array($ticket->status, ['completed', 'closed']))
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2.5 bg-emerald-50 border border-emerald-200/70 p-3 rounded-xl">
                                <input type="checkbox" name="is_completed" id="progress_is_completed" value="1"
                                    class="h-4 w-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500">
                                <label for="progress_is_completed" class="text-xs font-semibold text-emerald-900 cursor-pointer">Đã hoàn thành công việc kỹ thuật (Completed)</label>
                            </div>
                            <div class="flex items-center space-x-2.5 bg-purple-50 border border-purple-200/70 p-3 rounded-xl">
                                <input type="checkbox" name="is_waiting" id="progress_is_waiting" value="1"
                                    {{ $ticket->status === 'waiting' ? 'checked' : '' }}
                                    class="h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                                <label for="progress_is_waiting" class="text-xs font-semibold text-purple-900 cursor-pointer">Chờ phản hồi từ Khách hàng / Đối tác / Nhà cung cấp</label>
                            </div>
                        </div>
                        @endif

                        <!-- Solution / Evaluation -->
                        <div>
                            <label for="progress_solution" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nguyên nhân / Phương án / Cách xử lý</label>
                            <textarea name="solution" id="progress_solution" rows="4"
                                placeholder="Nhập nguyên nhân lỗi, phương án khắc phục, cấu hình chi tiết..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">{{ $ticket->solution }}</textarea>
                        </div>
                    </div>

                    <!-- Modal Actions (Fixed at bottom) -->
                    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 flex justify-end space-x-3 shrink-0">
                        <button type="button" @click="openProgressModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors bg-white">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm">
                            Lưu cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 6. Handover (Bàn giao) Modal -->
        <div x-show="openHandoverModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-2 sm:p-4 bg-black/60 backdrop-blur-xs" x-cloak>
            <div @click.away="openHandoverModal = false"
                class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col border border-gray-200 overflow-hidden transform transition-all my-auto">
                <!-- Modal Header -->
                <div class="px-5 py-3.5 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-blue-50 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Bàn giao Ticket Kỹ thuật (Handover)</h3>
                            <p class="text-xs text-gray-500">Chuyển giao quyền phụ trách cho Kỹ sư / Team Lead mới</p>
                        </div>
                    </div>
                    <button type="button" @click="openHandoverModal = false"
                        class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 flex items-center justify-center transition-colors focus:outline-none">
                        <i class="fas fa-times text-base"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form action="{{ route('technical-tickets.handover', $ticket->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
                    @csrf

                    <!-- Scrollable Body -->
                    <div class="p-5 space-y-4 overflow-y-auto flex-1">
                        <!-- Notice banner -->
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-800 flex items-start gap-2">
                            <i class="fas fa-info-circle text-blue-600 mt-0.5 shrink-0"></i>
                            <div>
                                Sau khi bàn giao, các Kỹ sư cũ vẫn có quyền <strong>Xem và Trao đổi</strong> trong ticket, nhưng chỉ có Kỹ sư mới nhận bàn giao mới có quyền cập nhật tiến độ và nhật ký kỹ thuật.
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- New Team Lead (Optional) -->
                            <div>
                                <label for="handover_lead_id" class="block text-xs font-semibold text-gray-700 mb-1">
                                    <i class="fas fa-user-tie text-purple-600 mr-1"></i> Trưởng nhóm phụ trách (Lead chính)
                                </label>
                                <select name="new_team_lead_id" id="handover_lead_id"
                                    class="w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary">
                                    @foreach($leads as $ld)
                                        <option value="{{ $ld->id }}" {{ (old('new_team_lead_id', $ticket->team_lead_id) == $ld->id) ? 'selected' : '' }}>
                                            {{ $ld->name }} ({{ $ld->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- New Co-Leads (Checkbox list) -->
                            @php
                                $currentCoLeads = (array)($ticket->co_lead_ids ?? []);
                            @endphp
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    <i class="fas fa-handshake text-teal-600 mr-1"></i> Lead phối hợp (Nhóm khác)
                                </label>
                                <div class="grid grid-cols-1 gap-1.5 max-h-36 overflow-y-auto p-1.5 bg-gray-50/70 rounded-lg border border-gray-200">
                                    @foreach($leads as $ld)
                                        @php
                                            $isCoLead = in_array($ld->id, $currentCoLeads);
                                        @endphp
                                        <label class="flex items-center gap-2 p-1.5 bg-white rounded border border-gray-200 hover:border-teal-400 cursor-pointer text-xs transition-colors">
                                            <input type="checkbox" name="new_co_lead_ids[]" value="{{ $ld->id }}" {{ $isCoLead ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            <span class="font-medium text-gray-800 truncate">{{ $ld->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Assign New Engineers -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fas fa-user-check text-indigo-600"></i> Chọn Kỹ sư mới tiếp nhận xử lý <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                                    <input type="text" x-model="handoverSearch" placeholder="Tìm kỹ sư..."
                                           class="pl-7 pr-3 py-1 bg-white border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-primary focus:border-primary w-36 sm:w-44 outline-none">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-2 border border-gray-200 rounded-xl bg-gray-50/50">
                                @foreach($engineers as $eng)
                                    @php
                                        $isCurrentlyActive = $ticket->activeEngineers->contains('id', $eng->id);
                                    @endphp
                                    <label class="flex items-start gap-2 p-2 bg-white rounded-lg border border-gray-200 hover:border-indigo-300 cursor-pointer text-xs transition-colors"
                                           x-show="!handoverSearch || '{{ mb_strtolower($eng->name) }}'.includes(handoverSearch.toLowerCase()) || '{{ mb_strtolower($eng->email) }}'.includes(handoverSearch.toLowerCase())">
                                        <input type="checkbox" name="assigned_to[]" value="{{ $eng->id }}"
                                               class="rounded border-gray-300 text-primary focus:ring-primary mt-0.5">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-gray-900 truncate">{{ $eng->name }}</div>
                                            <div class="text-[11px] text-gray-400 truncate">{{ $eng->email }}</div>
                                            @if($isCurrentlyActive)
                                                <span class="text-[9px] bg-blue-100 text-blue-700 px-1 py-0.2 rounded font-semibold mt-0.5 inline-block">Đang phụ trách</span>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Handover Note -->
                        <div>
                            <label for="handover_note" class="block text-xs font-semibold text-gray-700 mb-1">
                                Nội dung / Ghi chú bàn giao <span class="text-red-500">*</span>
                            </label>
                            <textarea name="handover_note" id="handover_note" required rows="3"
                                placeholder="Mô tả lý do bàn giao, hiện trạng công việc, các lưu ý cần tiếp tục xử lý..."
                                class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"></textarea>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 flex justify-end space-x-3 shrink-0">
                        <button type="button" @click="openHandoverModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors bg-white">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-check"></i> Xác nhận bàn giao
                        </button>
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
    @endpush
@endsection
