@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng')
@section('page-title', 'Chi tiết đơn hàng: ' . $sale->code)

@section('content')
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
            <strong class="font-bold">Lỗi nhập liệu:</strong>
            <ul class="list-disc list-inside text-sm mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3 focus:outline-none" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif
<div class="space-y-4">
    <!-- Actions -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-gray-200 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ url()->previous() }}" 
               class="inline-flex items-center px-3.5 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors shadow-xs">
                <i class="fas fa-arrow-left mr-2 text-gray-500"></i> Quay lại
            </a>
            @if($sale->status === 'cancelled')
                <span class="inline-flex items-center px-3.5 py-2 bg-red-50 text-red-500 text-sm font-semibold rounded-lg border border-red-200 cursor-not-allowed" 
                      title="Đơn hàng đã hủy, không thể chỉnh sửa.">
                    <i class="fas fa-ban mr-2 text-red-400"></i> Đã hủy
                </span>
            @elseif(!$sale->hasPayment())
                <a href="{{ route('sales.edit', $sale->id) }}" 
                   class="inline-flex items-center px-3.5 py-2 bg-amber-500 text-white text-sm font-semibold rounded-lg hover:bg-amber-600 transition-colors shadow-xs">
                    <i class="fas fa-edit mr-2"></i> Sửa
                </a>
            @else
                <span class="inline-flex items-center px-3.5 py-2 bg-gray-100 text-gray-400 text-sm font-semibold rounded-lg border border-gray-200 cursor-not-allowed" 
                      title="Đơn hàng đã phát sinh thanh toán, đã khóa chỉnh sửa thông tin và BOM.">
                    <i class="fas fa-lock mr-2 text-gray-400"></i> Đã khóa (Đã thanh toán)
                </span>
            @endif

            @if($sale->status !== 'cancelled')
            <form action="{{ route('sales.email', $sale->id) }}" method="POST" class="inline" id="emailForm">
                @csrf
                <button type="button" onclick="confirmSendEmail()"
                        class="inline-flex items-center px-3.5 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors shadow-xs">
                    <i class="fas fa-envelope mr-2"></i> Gửi Email
                </button>
            </form>
            @endif
        </div>
        <div>
            @php
                $hasOfficialInvoiceForPayment = $sale->invoiceRequests->where('status', 'official_issued')->isNotEmpty();
            @endphp
            @if($sale->status !== 'cancelled' && $sale->pl_status === 'approved')
            <a href="{{ route('sales.order-request.create', $sale->id) }}" 
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-xs">
                <i class="fas fa-cart-plus mr-2"></i> Yêu cầu đặt hàng
                @if($sale->orderRequests && $sale->orderRequests->count() > 0)
                    <span class="ml-1.5 bg-white/30 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $sale->orderRequests->count() }}</span>
                @endif
            </a>
            @endif
        </div>
    </div>
    
    {{-- Project Closure Sync Banner --}}
    @php
        $linkedProject = $sale->project ?? $sale->items->map(fn($it) => $it->project)->filter()->first();
        $isProjectOpen = $linkedProject && !in_array($linkedProject->registration_status, ['closed_won', 'closed_lost', 'cancelled']);
    @endphp
    @if($isProjectOpen)
    <div class="bg-gradient-to-r from-purple-50 via-indigo-50/60 to-white border border-purple-200/80 rounded-xl p-4 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white flex items-center justify-center font-bold text-lg shadow-sm shrink-0">
                    <i class="fas fa-trophy"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700 bg-purple-100 px-2.5 py-0.5 rounded-full border border-purple-200">
                            <i class="fas fa-project-diagram mr-1"></i> Đăng ký Dự án
                        </span>
                        <a href="{{ route('projects.show', $linkedProject->id) }}" class="text-sm font-bold text-gray-900 hover:text-purple-600 hover:underline">
                            {{ $linkedProject->code }} - {{ $linkedProject->name }}
                        </a>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold {{ $linkedProject->registration_status_badge['class'] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $linkedProject->registration_status_label }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1.5 leading-relaxed">
                        Đơn hàng <strong class="text-gray-900">#{{ $sale->code }}</strong> (<span class="text-purple-700 font-bold">{{ number_format($sale->total, 0, ',', '.') }} đ</span>) liên kết với dự án này. Khi hoàn tất xuất hóa đơn hoặc giao hàng, bạn có thể bấm xác nhận để tự động đóng dự án (Closed Won) và đồng bộ BOM cùng đơn giá chốt thực tế.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0 self-end md:self-center">
                <button type="button" onclick="openSaleCloseProjectModal('{{ $linkedProject->id }}', '{{ $linkedProject->code }}', '{{ addslashes($linkedProject->name) }}', '{{ $sale->id }}', '{{ $sale->code }}', '{{ number_format($sale->total, 0, ',', '.') }} đ')"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-xs font-bold rounded-lg hover:from-purple-700 hover:to-indigo-700 shadow-sm transition-all hover:shadow">
                    <i class="fas fa-check-circle mr-1.5"></i> Xác nhận Đóng dự án & Đồng bộ
                </button>
                <a href="{{ route('projects.show', $linkedProject->id) }}" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition-colors shadow-xs">
                    <i class="fas fa-external-link-alt mr-1"></i> Xem DA
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- System Alert for Auto-created POs --}}
    @if($sale->purchaseOrders && $sale->purchaseOrders->count() > 0)
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3.5 flex items-center shadow-xs">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-blue-500 text-lg"></i>
        </div>
        <div class="ml-3">
            <p class="text-sm text-blue-800">
                Hệ thống đã tự động tạo Đơn mua hàng (PO) liên kết: 
                @foreach($sale->purchaseOrders as $po)
                    <a href="{{ route('purchase-orders.show', $po->id) }}" class="font-bold underline hover:text-blue-600">{{ $po->code }}</a>{{ $loop->last ? '' : ', ' }}
                @endforeach
            </p>
        </div>
    </div>
    @endif

    {{-- Order Requests Status Summary --}}
    @if($sale->orderRequests && $sale->orderRequests->count() > 0)
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 shadow-xs">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <i class="fas fa-cart-arrow-down text-emerald-600 text-base"></i>
                <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Yêu cầu đặt hàng ({{ $sale->orderRequests->count() }})</span>
            </div>
            <a href="{{ route('purchase-requests.index', ['my_requests' => 1]) }}" class="text-xs text-emerald-700 hover:text-emerald-900 font-semibold underline flex items-center gap-1">
                Xem ở Yêu cầu đặt hàng <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
        </div>
        <div class="space-y-1.5 text-xs">
            @foreach($sale->orderRequests as $req)
                <div class="flex flex-wrap items-center justify-between bg-white px-3 py-1.5 rounded-lg border border-emerald-100 gap-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-emerald-800">#{{ $req->code }}</span>
                        <span class="text-gray-500">({{ $req->items->count() }} sản phẩm)</span>
                        <span class="text-gray-400">| Ngày gửi: {{ $req->created_at ? $req->created_at->format('d/m/Y H:i') : '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @php
                            $badgeClass = match($req->status) {
                                'pending_admin' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                'submitted', 'processing' => 'bg-blue-100 text-blue-800 border-blue-200',
                                'need_info' => 'bg-orange-100 text-orange-800 border-orange-200',
                                'completed' => 'bg-green-100 text-green-800 border-green-200',
                                default => 'bg-gray-100 text-gray-800 border-gray-200'
                            };
                        @endphp
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $badgeClass }}">
                            {{ $req->status_label }}
                        </span>
                        @if($req->rejection_note)
                            <span class="text-red-600 text-[11px] italic" title="{{ $req->rejection_note }}">(Ghi chú: {{ Str::limit($req->rejection_note, 35) }})</span>
                        @endif
                        @if(in_array($req->status, ['draft', 'need_info']))
                            <a href="{{ route('sales.order-request.edit', [$sale->id, $req->id]) }}" class="text-blue-600 font-bold hover:underline text-[11px] ml-1">
                                <i class="fas fa-edit mr-0.5"></i> {{ $req->status === 'draft' ? 'Sửa nháp' : 'Chỉnh sửa' }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Workflow Progress Tracker --}}
    @php
        $pnlWorkflow = \App\Models\ApprovalWorkflow::getForDocumentType('sale_pnl');
        $pnlHistory = \App\Models\ApprovalHistory::where('document_type', 'sale_pnl')
            ->where('document_id', $sale->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $latestReject = $pnlHistory->whereIn('action', ['rejected', 'need_revision'])->first();
        $pendingHist = $pnlHistory->where('action', 'pending')->sortBy('level')->first();
    @endphp

    @if($pnlWorkflow || $sale->status !== 'cancelled')
    <div class="bg-white rounded-xl shadow-xs overflow-hidden border border-gray-200">
        {{-- P&L Workflow Header --}}
        <div class="px-5 py-3 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3 {{ $sale->pl_status === 'approved' ? 'bg-emerald-50/50' : ($sale->pl_status === 'rejected' ? 'bg-red-50/50' : ($sale->pl_status === 'need_revision' ? 'bg-amber-50/50' : 'bg-slate-50')) }}">
            <div class="flex items-center gap-3">
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Quy trình duyệt P&L:</span>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $sale->pl_status_color }}">
                        {{ $sale->pl_status_label }}
                    </span>
                    <i class="fas fa-chevron-right text-gray-300 text-[10px]"></i>
                    <span class="text-xs text-gray-700 font-medium">
                        @if($sale->pl_status === 'pending' && $pendingHist)
                            Đang chờ: <span class="font-bold text-blue-600">{{ $pendingHist->level_name }}</span>
                        @elseif($sale->pl_status === 'approved')
                            <span class="text-emerald-700 font-bold"><i class="fas fa-check-circle mr-1"></i>Đã duyệt hoàn tất</span>
                        @elseif($sale->pl_status === 'rejected')
                            <span class="text-red-600 font-bold"><i class="fas fa-times-circle mr-1"></i>Bị từ chối - Chờ Sales sửa</span>
                        @elseif($sale->pl_status === 'need_revision')
                            <span class="text-amber-600 font-bold"><i class="fas fa-exclamation-circle mr-1"></i>Yêu cầu chỉnh sửa - Chờ Sales sửa</span>
                        @else
                            <span class="text-gray-400 italic">Bản nháp</span>
                        @endif
                    </span>
                </div>
            </div>

            @if(in_array($sale->pl_status, ['rejected', 'need_revision']) && $latestReject)
            <div class="bg-white/80 px-3 py-1.5 rounded-lg border {{ $sale->pl_status === 'rejected' ? 'border-red-200' : 'border-amber-200' }} flex items-start gap-2 max-w-xl">
                <i class="fas fa-{{ $sale->pl_status === 'rejected' ? 'exclamation-circle text-red-500' : 'pen text-amber-500' }} mt-0.5 text-xs"></i>
                <div class="text-xs">
                    <span class="font-bold {{ $sale->pl_status === 'rejected' ? 'text-red-700' : 'text-amber-700' }}">{{ $sale->pl_status === 'rejected' ? 'Lý do:' : 'Cần sửa:' }}</span>
                    <span class="{{ $sale->pl_status === 'rejected' ? 'text-red-600' : 'text-amber-600' }}">"{{ $latestReject->comment }}"</span>
                    <span class="text-[10px] text-gray-400 ml-1">({{ $latestReject->approver_name }})</span>
                </div>
            </div>
            @endif
        </div>

        {{-- Order Status & Quick Actions --}}
        <div class="px-5 py-3 bg-white border-b border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider shrink-0">Trạng thái đơn:</span>
                <div class="flex items-center text-xs overflow-x-auto pb-1 no-scrollbar gap-1">
                    @php
                        $currentStep = $sale->dashboard_step;
                        $dashStatus = $sale->dashboard_status;
                        
                        // Nhãn bước đầu tiên thay đổi theo trạng thái PNL
                        $firstStepLabel = match($dashStatus) {
                            'pnl_rejected' => 'PNL Từ chối',
                            'pnl_need_revision' => 'Yêu cầu chỉnh sửa',
                            'pnl_pending' => 'Chờ duyệt PNL',
                            'draft' => 'Nháp',
                            'pending' => 'Chờ duyệt đơn hàng',
                            default => 'Nháp',
                        };
                        $firstStepColor = match($dashStatus) {
                            'pnl_rejected' => 'red',
                            'pnl_need_revision' => 'amber',
                            'pnl_pending' => 'orange',
                            'draft' => 'gray',
                            'pending' => 'yellow',
                            default => 'gray',
                        };

                        $steps = [
                            ['label' => $firstStepLabel, 'color' => $firstStepColor],
                            ['label' => 'Đã duyệt', 'color' => 'blue'],
                            ['label' => 'Đã đặt hàng', 'color' => 'indigo'],
                            ['label' => 'Chờ hàng về', 'color' => 'purple'],
                            ['label' => 'Hàng về', 'color' => 'emerald'],
                            ['label' => 'Giao hàng', 'color' => 'amber'],
                            ['label' => 'Hoàn thành', 'color' => 'green'],
                        ];
                    @endphp

                    @foreach($steps as $index => $step)
                        <span class="px-2.5 py-1 rounded-md whitespace-nowrap text-xs font-semibold {{ $currentStep === $index ? "bg-{$step['color']}-100 text-{$step['color']}-800 ring-1 ring-{$step['color']}-400 font-bold" : ($currentStep > $index ? "text-{$step['color']}-700 font-medium" : 'bg-gray-50 text-gray-400') }}">
                            {{ $step['label'] }}
                        </span>
                        @if(!$loop->last)
                            <i class="fas fa-chevron-right text-[10px] mx-1 {{ $currentStep > $index ? "text-{$step['color']}-400" : 'text-gray-200' }}"></i>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mr-1">Thao tác:</span>
                @if($sale->status === 'pending' && $sale->pl_status === 'approved')
                    <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-lg shadow-xs hover:bg-blue-700 transition-all">
                            <i class="fas fa-check mr-1"></i> DUYỆT ĐƠN
                        </button>
                    </form>
                @endif
                
                @php
                    $hasGoodsReceived = $sale->all_purchase_orders
                        ->contains(fn ($purchaseOrder) => in_array($purchaseOrder->status, ['partial_received', 'received'], true));
                    $hasPayment = $sale->hasPayment();
                @endphp
                @if(in_array($sale->status, ['pending', 'approved']) && !$hasGoodsReceived && !$hasPayment)
                    <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="button" 
                                onclick="confirmAction(this.closest('form'), 'Xác nhận hủy đơn?', 'Bạn có chắc chắn muốn hủy đơn hàng này không?', 'warning', 'Đồng ý hủy', '#d33')"
                                class="px-3 py-1.5 bg-white border border-red-200 text-red-600 text-xs font-bold rounded-lg hover:bg-red-50 transition-all shadow-xs">
                            <i class="fas fa-times mr-1"></i> HỦY ĐƠN
                        </button>
                    </form>
                @endif

                @if(in_array($sale->dashboard_status, ['completed']) || $sale->status === 'completed')
                    <span class="text-xs text-green-700 font-bold bg-green-100 px-3 py-1.5 rounded-lg border border-green-200 inline-flex items-center gap-1 shadow-2xs">
                        <i class="fas fa-check-circle"></i> ĐÃ HOÀN TẤT
                    </span>
                @elseif(in_array($sale->dashboard_status, ['invoiced', 'shipping']) || $sale->status === 'shipping')
                    <span class="text-xs text-amber-700 font-bold bg-amber-100 px-3 py-1.5 rounded-lg border border-amber-200 inline-flex items-center gap-1 shadow-2xs">
                        <i class="fas fa-truck"></i> ĐÃ XUẤT KHO / GIAO HÀNG
                    </span>
                @endif
            </div>
        </div>

        {{-- Warehouse & Export Tracking --}}
        @php
            $linkedExportsSummary = \App\Models\Export::where('reference_type', 'sale')->where('reference_id', $sale->id)->get();
        @endphp
        <div class="px-5 py-2.5 bg-gray-50/80 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Kho & Giao nhận:</span>
                @if($linkedExportsSummary->isNotEmpty())
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-medium text-gray-700">
                            Có {{ $linkedExportsSummary->count() }} yêu cầu xuất kho:
                        </span>
                        <a href="javascript:void(0)" @click="activeTab = 'warehouse'" class="text-xs font-bold text-teal-600 hover:underline">
                            Xem chi tiết ở tab Kho & Giao nhận (đã có {{ $linkedExportsSummary->count() }} phiếu)
                        </a>
                    </div>
                @else
                    <span class="text-xs text-red-500 font-medium flex items-center gap-1">
                        <i class="fas fa-exclamation-circle text-red-400"></i> Chưa có phiếu xuất kho. Vui lòng tạo ở tab Kho & Giao nhận.
                    </span>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Note -->
    @if($sale->note)
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Ghi chú</h3>
        <p class="text-gray-700">{{ $sale->note }}</p>
    </div>
    @endif

    @php
        $euInfos = collect();
        if ($sale->orderRequests && $sale->orderRequests->isNotEmpty()) {
            $latestRequest = $sale->orderRequests->sortByDesc('id')->first();
            if ($latestRequest) {
                $euInfos = $latestRequest->items->map(function($item) {
                    return [
                        'si_name' => $item->si_name,
                        'eu_name_mst' => $item->eu_name_mst,
                        'address' => $item->address,
                    ];
                })->filter(function($eu) {
                    return !empty($eu['si_name']) || !empty($eu['eu_name_mst']);
                })->unique(function($eu) {
                    return $eu['si_name'] . '|' . $eu['eu_name_mst'] . '|' . $eu['address'];
                })->values();
            }
        }
    @endphp

    <!-- Sale Info -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <div class="grid grid-cols-1 {{ $euInfos->isNotEmpty() ? 'md:grid-cols-3' : 'md:grid-cols-2' }} gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin đơn hàng</h3>
                <dl class="space-y-2">
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Mã đơn:</dt>
                        <dd class="font-medium text-gray-900">{{ $sale->code }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Loại:</dt>
                        <dd>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->type == 'project' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $sale->type_label }}
                            </span>
                        </dd>
                    </div>
                    @if($sale->project)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Dự án:</dt>
                        <dd>
                            <a href="{{ route('projects.show', $sale->project_id) }}" class="text-purple-600 hover:underline font-medium">
                                <i class="fas fa-project-diagram mr-1"></i>
                                {{ $sale->project->code }} - {{ $sale->project->name }}
                            </a>
                        </dd>
                    </div>
                    @endif
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Ngày tạo:</dt>
                        <dd class="text-gray-900">{{ $sale->date->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Hạn thanh toán:</dt>
                        <dd class="font-medium text-gray-950">
                            @if($sale->payment_due_date)
                                @php
                                    $isOverdue = $sale->payment_due_date->isPast() && $sale->payment_status !== 'paid';
                                @endphp
                                <span>{{ $sale->payment_due_date->format('d/m/Y') }}</span>
                                @if($isOverdue)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 ml-1" title="Đã quá hạn thanh toán!">
                                        <i class="fas fa-exclamation-triangle mr-0.5"></i> Quá hạn
                                    </span>
                                @endif
                            @elseif($sale->delivery_date)
                                @php
                                    $debtDays = $sale->customer->debt_days ?? 0;
                                    $dueDate = \Carbon\Carbon::parse($sale->delivery_date)->addDays($debtDays);
                                    $isOverdue = $dueDate->isPast() && $sale->payment_status !== 'paid';
                                @endphp
                                <span>{{ $dueDate->format('d/m/Y') }} (Tính từ ngày giao hàng {{ \Carbon\Carbon::parse($sale->delivery_date)->format('d/m/Y') }} + {{ $debtDays }} ngày nợ)</span>
                                @if($isOverdue)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 ml-1" title="Đã quá hạn thanh toán!">
                                        <i class="fas fa-exclamation-triangle mr-0.5"></i> Quá hạn
                                    </span>
                                @endif
                            @elseif($sale->invoice_date)
                                @php
                                    $debtDays = $sale->customer->debt_days ?? 0;
                                    $dueDate = \Carbon\Carbon::parse($sale->invoice_date)->addDays($debtDays);
                                    $isOverdue = $dueDate->isPast() && $sale->payment_status !== 'paid';
                                @endphp
                                <span>{{ $dueDate->format('d/m/Y') }} (Tính từ ngày xuất HĐ {{ \Carbon\Carbon::parse($sale->invoice_date)->format('d/m/Y') }} + {{ $debtDays }} ngày nợ)</span>
                                @if($isOverdue)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 ml-1" title="Đã quá hạn thanh toán!">
                                        <i class="fas fa-exclamation-triangle mr-0.5"></i> Quá hạn
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-500 italic">Tính khi giao hàng hoặc xuất HĐ</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-start">
                        <dt class="w-32 text-gray-500 shrink-0">Điều khoản TT:</dt>
                        <dd class="font-medium text-gray-900 break-words whitespace-pre-line">{{ $sale->payment_term ?: '-' }}</dd>
                    </div>
                    <div class="flex items-start">
                        <dt class="w-32 text-gray-500 shrink-0">Trạng thái:</dt>
                        <dd class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                                {{ $sale->status_label }}
                            </span>
                            @foreach($sale->pending_action_badges as $badge)
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $badge['color'] }} inline-flex items-center gap-1">
                                    <i class="{{ $badge['icon'] }}"></i>{{ $badge['label'] }}
                                </span>
                            @endforeach
                        </dd>
                    </div>
                    @if($sale->currency && !$sale->currency->is_base)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Tiền tệ:</dt>
                        <dd>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-money-bill-wave mr-1"></i>{{ $sale->currency->code }} - {{ $sale->currency->name_vi }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Tỷ giá:</dt>
                        <dd class="font-medium text-gray-900">1 {{ $sale->currency->code }} = {{ rtrim(rtrim(number_format($sale->exchange_rate, 2, ',', '.'), '0'), ',') }} VND</dd>
                    </div>
                    @endif
                </dl>
            </div>
            
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin khách hàng</h3>
                <dl class="space-y-2">
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Khách hàng:</dt>
                        <dd class="font-medium text-gray-900">{{ $sale->customer_name }}</dd>
                    </div>
                    <div class="flex border-b border-gray-50 pb-2 mb-1">
                        <dt class="w-32 text-gray-500">Người phụ trách:</dt>
                        <dd class="font-medium text-indigo-700">
                             @if($sale->contact)
                                {{ $sale->contact->name }} 
                                @if($sale->contact->position) <span class="text-xs text-gray-500">({{ $sale->contact->position }})</span> @endif
                                <div class="text-xs text-gray-500 font-normal mt-1">
                                    <i class="fas fa-envelope mr-1 text-gray-400"></i>{{ $sale->contact->email ?: 'N/A' }} | 
                                    <i class="fas fa-phone mr-1.5 ml-1 text-gray-400"></i>{{ $sale->contact->phone ?: 'N/A' }}
                                </div>
                            @else
                                <span class="text-red-500 font-medium">Chưa chọn P.I.C</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Salesperson:</dt>
                        <dd class="font-medium text-primary">{{ $sale->user->name ?? 'N/A' }}</dd>
                    </div>
                    @if($sale->customer)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Email:</dt>
                        <dd class="text-gray-900">{{ $sale->customer->email }}</dd>
                    </div>
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Điện thoại:</dt>
                        <dd class="text-gray-900">{{ $sale->customer->phone }}</dd>
                    </div>
                    @endif
                    @if($sale->delivery_address)
                    <div class="flex">
                        <dt class="w-32 text-gray-500">Địa chỉ giao:</dt>
                        <dd class="text-gray-900">{{ $sale->delivery_address }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            @if($euInfos->isNotEmpty())
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin đặt hàng</h3>
                @foreach($euInfos as $index => $eu)
                    @if($euInfos->count() > 1)
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Đối tác / EU #{{ $index + 1 }}</div>
                    @endif
                    <dl class="space-y-2 {{ !$loop->last ? 'border-b border-gray-100 pb-4 mb-4' : '' }}">
                        <div class="flex">
                            <dt class="w-32 text-gray-500">SI Name:</dt>
                            <dd class="font-medium text-gray-900">{{ $eu['si_name'] ?: 'N/A' }}</dd>
                        </div>
                        <div class="flex">
                            <dt class="w-32 text-gray-500">EU Name - MST:</dt>
                            <dd class="font-medium text-gray-900">{{ $eu['eu_name_mst'] ?: 'N/A' }}</dd>
                        </div>
                        @if($eu['address'])
                        <div class="flex">
                            <dt class="w-32 text-gray-500">Địa chỉ:</dt>
                            <dd class="text-gray-900">{{ $eu['address'] }}</dd>
                        </div>
                        @endif
                    </dl>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Shipment Tracking Info --}}
        @php
            $associatedPos = $sale->all_purchase_orders->filter(function($po) {
                return $po->expected_arrival_date || $po->manufacturer_release_date || $po->expected_delivery;
            });
        @endphp

        @if($associatedPos->count() > 0)
        <div class="mt-8 pt-6 border-t border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-shipping-fast mr-2 text-indigo-500"></i>
                Theo dõi lô hàng (Procurement Tracking)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($associatedPos as $po)
                <div class="bg-indigo-50/50 border border-indigo-100 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-3">
                        <a href="{{ route('purchase-orders.show', $po->id) }}" class="text-sm font-bold text-indigo-700 hover:underline">
                            {{ $po->code }}
                        </a>
                        <span class="text-[10px] bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded font-bold uppercase">
                            {{ $po->supplier->name ?? 'N/A' }}
                        </span>
                    </div>
                    <dl class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 italic">Dự kiến hàng về:</dt>
                            <dd class="font-bold text-gray-900">{{ $po->expected_arrival_date ? \Carbon\Carbon::parse($po->expected_arrival_date)->format('d/m/Y') : '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 italic">Hãng xuất sp:</dt>
                            <dd class="font-bold text-gray-900">{{ $po->manufacturer_release_date ? \Carbon\Carbon::parse($po->manufacturer_release_date)->format('d/m/Y') : '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 italic">Giao hàng dự kiến:</dt>
                            <dd class="font-bold text-gray-900">{{ $po->expected_delivery ? \Carbon\Carbon::parse($po->expected_delivery)->format('d/m/Y') : '-' }}</dd>
                        </div>
                    </dl>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @php
            $payStatus = $sale->getPaymentConditionStatus();
            $milestones = $payStatus['milestones'] ?? [];
            $currentUser = auth()->user();
            $isBOD = $currentUser->hasRole('director') || $currentUser->hasRole('super_admin') || $currentUser->hasRole('admin');
            $isFinance = $currentUser->hasRole('accountant') || $currentUser->hasRole('super_admin') || $currentUser->hasRole('admin');
            $hasCompletedExport = $sale->exports()->where('status', 'completed')->exists();
            $hasPaidMilestone = collect($milestones)->contains(fn($m) => ($m['status'] ?? '') === 'paid');
        @endphp

        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-xl p-5 mb-6 shadow-sm">
            <h4 class="text-sm font-semibold text-gray-800 mb-3 flex flex-wrap items-center justify-between gap-3">
                <span class="flex items-center"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i> Lộ trình thanh toán chi tiết & Kiểm soát quy trình</span>
                <div class="flex items-center gap-2 flex-wrap">
                    @if($sale->delivery_date)
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full flex items-center gap-1">
                            <i class="fas fa-shipping-fast"></i> Đã giao hàng: {{ $sale->delivery_date->format('d/m/Y') }}
                        </span>
                    @elseif($hasCompletedExport)
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full flex items-center gap-1">
                            <i class="fas fa-warehouse"></i> Đã xuất kho (Chờ giao hàng)
                        </span>
                    @endif
                    @if($hasCompletedExport)
                        <button onclick="openDeliveryModal()" class="px-3 py-1 bg-indigo-600 text-white hover:bg-indigo-700 text-xs font-bold rounded-full flex items-center gap-1 shadow-sm transition-colors">
                            <i class="fas fa-calendar-alt"></i> Cập nhật ngày giao hàng
                        </button>
                    @endif
                    <span class="text-xs font-normal text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200"><i class="fas fa-info-circle mr-1"></i>Chọn theo Điều khoản mẫu quy định</span>
                </div>
            </h4>

            @if($sale->has_bank_guarantee)
            <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                <div class="flex items-center gap-2 text-sm font-medium text-amber-900">
                    <i class="fas fa-university text-amber-600"></i> Có bảo lãnh thanh toán (Bank Guarantee)
                </div>
                @if($sale->bank_guarantee_note)
                    <div class="mt-1 text-xs text-amber-700 pl-6">{{ $sale->bank_guarantee_note }}</div>
                @endif
            </div>
            @endif

            <div class="grid grid-cols-1 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Điều khoản thanh toán</label>
                    @if($sale->status !== 'cancelled' && $sale->pl_status !== 'approved' && isset($paymentTemplates) && $paymentTemplates->isNotEmpty())
                    <div class="flex items-center gap-3">
                        <select id="milestonePresetSelect" onchange="onPaymentTermPresetChange(this)"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white cursor-pointer shadow-xs">
                            <option value="custom" {{ (!str_starts_with($sale->payment_term_type ?? '', 'template_') && $sale->payment_term_type !== 'customer_default') ? 'selected' : '' }}>-- Tùy chỉnh theo đơn hàng --</option>
                            @foreach($paymentTemplates as $tpl)
                                <option value="template_{{ $tpl->id }}" 
                                        data-name="{{ $tpl->name }}"
                                        data-items="{{ json_encode($tpl->items) }}" 
                                        {{ $sale->payment_term_type === 'template_' . $tpl->id ? 'selected' : '' }}>
                                    {{ $tpl->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                        @php
                            $currentTermName = 'Tùy chỉnh theo đơn hàng';
                            if ($sale->payment_term_type && isset($paymentTemplates)) {
                                $tplId = str_replace('template_', '', $sale->payment_term_type);
                                $matchedTpl = $paymentTemplates->firstWhere('id', $tplId);
                                if ($matchedTpl) {
                                    $currentTermName = $matchedTpl->name;
                                } elseif ($sale->payment_term_type === 'customer_default') {
                                    $currentTermName = 'Mặc định theo khách hàng';
                                } else {
                                    $currentTermName = $sale->payment_term_type;
                                }
                            }
                        @endphp
                        <div class="text-sm font-semibold text-gray-800 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 flex items-center justify-between">
                            <span>{{ $currentTermName }}</span>
                            @if($sale->pl_status === 'approved')
                                <span class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded font-medium flex items-center gap-1">
                                    <i class="fas fa-lock text-[10px]"></i> Đã duyệt P&L (Khóa thay đổi)
                                </span>
                            @elseif($sale->status === 'cancelled')
                                <span class="text-xs text-red-600 bg-red-50 border border-red-200 px-2 py-0.5 rounded font-medium flex items-center gap-1">
                                    <i class="fas fa-ban text-[10px]"></i> Đơn đã hủy
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Giao đoạn Kiểm soát Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <!-- 1. Đặt hàng -->
                <div class="border rounded-xl p-4 flex items-center justify-between {{ $payStatus['eligible_for_order'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                    <div class="flex items-center space-x-3">
                        <div class="p-3 rounded-lg {{ $payStatus['eligible_for_order'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            <i class="fas fa-shopping-cart text-xl"></i>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Giai đoạn Đặt hàng (PR/PO)</span>
                            <span class="text-sm font-bold {{ $payStatus['eligible_for_order'] ? 'text-green-700' : 'text-red-700' }}">
                                @if($payStatus['eligible_for_order'])
                                    @if($sale->is_payment_exception)
                                        Được duyệt ngoại lệ bởi BOD
                                    @else
                                        Đủ điều kiện đặt hàng
                                    @endif
                                @else
                                    Chưa đủ điều kiện đặt hàng
                                @endif
                            </span>
                            @if(!$payStatus['eligible_for_order'])
                                <p class="text-xs text-red-600 mt-1">Cần cọc/thanh toán đợt trước đặt hàng.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 2. Xuất hàng -->
                <div class="border rounded-xl p-4 flex items-center justify-between {{ $payStatus['eligible_for_export'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                    <div class="flex items-center space-x-3">
                        <div class="p-3 rounded-lg {{ $payStatus['eligible_for_export'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            <i class="fas fa-truck-loading text-xl"></i>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Giai đoạn Xuất hàng (Logistics)</span>
                            <span class="text-sm font-bold {{ $payStatus['eligible_for_export'] ? 'text-green-700' : 'text-red-700' }}">
                                @if($payStatus['eligible_for_export'])
                                    @if($sale->is_payment_exception)
                                        Được duyệt ngoại lệ bởi BOD
                                    @else
                                        Đủ điều kiện xuất hàng
                                    @endif
                                @else
                                    Chưa đủ điều kiện xuất hàng
                                @endif
                            </span>
                            @if(!$payStatus['eligible_for_export'])
                                <p class="text-xs text-red-600 mt-1">Cần thanh toán trước khi xuất kho.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Phê duyệt ngoại lệ cấp Đơn hàng bởi Giám đốc (BOD) -->
            @php
                $canApproveSaleException = $isBOD || ($sale->payment_exception_delegated_to === auth()->id());
            @endphp
            @if((!$payStatus['eligible_for_order'] || !$payStatus['eligible_for_export']) && $canApproveSaleException && !$sale->is_payment_exception)
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-red-100 pb-2 mb-3">
                        <h4 class="text-xs font-bold text-red-700 uppercase tracking-wider flex items-center">
                            <i class="fas fa-shield-alt mr-1"></i> Phê duyệt ngoại lệ thanh toán đơn hàng (BOD Approval)
                        </h4>
                        <!-- BOD Delegation form -->
                        @if($isBOD)
                            <div class="flex items-center space-x-2 text-xs text-gray-700 mt-2 md:mt-0">
                                <form action="{{ route('sales.delegatePaymentException', $sale->id) }}" method="POST" class="flex items-center space-x-1">
                                    @csrf
                                    <span class="font-bold text-red-700">Ủy quyền duyệt:</span>
                                    <select name="delegate_user_id" onchange="this.form.submit()" class="border border-gray-300 rounded px-1.5 py-0.5 text-xs bg-white">
                                        <option value="">-- Chọn người nhận ủy quyền --</option>
                                        @foreach(\App\Models\User::orderBy('name')->get() as $u)
                                            @if($u->id !== auth()->id())
                                                <option value="{{ $u->id }}" {{ $sale->payment_exception_delegated_to === $u->id ? 'selected' : '' }}>
                                                    {{ $u->name }} ({{ $u->roles->first()->name ?? 'No Role' }})
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        @endif
                    </div>
                    
                    @if($sale->payment_exception_delegated_to)
                        @php $delegatedUserObj = \App\Models\User::find($sale->payment_exception_delegated_to); @endphp
                        @if($delegatedUserObj)
                            <div class="text-xs text-indigo-700 font-bold mb-3 flex items-center">
                                <i class="fas fa-user-shield mr-1"></i> Đang ủy quyền duyệt ngoại lệ cho: <span class="ml-1 px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded-full font-bold text-[10px]">{{ $delegatedUserObj->name }}</span>
                            </div>
                        @endif
                    @endif

                    <p class="text-xs text-red-600 mb-4">Chức năng này dành cho Giám đốc/BOD hoặc người được ủy quyền để cho phép đặt hàng hoặc xuất hàng trước khi thanh toán.</p>
                    <form action="{{ route('sales.approvePaymentException', $sale->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-red-700 uppercase tracking-wider mb-2">Tài liệu phê duyệt đính kèm (Bắt buộc, hỗ trợ chọn nhiều file)</label>
                            <input type="file" name="payment_exception_files[]" multiple required
                                   class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-white focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-xs file:font-semibold file:bg-red-100 file:text-red-700 hover:file:bg-red-200">
                        </div>
                        <div class="flex justify-start">
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm focus:outline-none transition-colors duration-200">
                                <i class="fas fa-check mr-1.5"></i> Phê duyệt Ngoại lệ
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if($sale->is_payment_exception && $sale->payment_exception_file)
                @php
                    $exceptionFiles = [];
                    $decoded = json_decode($sale->payment_exception_file, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $exceptionFiles = $decoded;
                    } elseif ($sale->payment_exception_file) {
                        $exceptionFiles = [$sale->payment_exception_file];
                    }
                @endphp
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center space-x-2 text-blue-700 text-xs font-medium">
                        <i class="fas fa-file-signature text-base text-blue-600"></i>
                        <span>Đơn hàng đã được duyệt ngoại lệ thanh toán với {{ count($exceptionFiles) }} tài liệu đính kèm.</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($exceptionFiles as $index => $filePath)
                            <a href="{{ asset('storage/' . $filePath) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors">
                                <i class="fas fa-paperclip mr-1.5"></i> File {{ $index + 1 }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Bảng chi tiết đợt thanh toán (Milestones Table) -->
            <form action="{{ route('sales.milestones.sync', $sale->id) }}" method="POST" id="syncMilestonesForm" onsubmit="return validateMilestonesSubmit(event)">
                @csrf
                <input type="hidden" name="template_id" id="selectedTemplateId" value="{{ $sale->payment_term_type ?? 'custom' }}">

                <!-- Preview / Alert Banner -->
                <div id="milestonePreviewAlert" class="hidden mb-4 p-3.5 bg-amber-50 border border-amber-200 rounded-xl flex flex-wrap items-center justify-between gap-3 text-xs text-amber-900 shadow-xs">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-eye text-amber-600 text-sm"></i>
                        <span>Đang xem trước mẫu điều khoản: <b id="previewTemplateNameLabel" class="text-amber-950 font-bold"></b>. Bạn có thể chỉnh sửa/thêm đợt, sau đó nhấn <b>Lưu điều khoản</b> để áp dụng.</span>
                    </div>
                    <button type="button" onclick="resetMilestonesToOriginal()" class="px-3 py-1.5 bg-amber-200 hover:bg-amber-300 text-amber-900 font-semibold rounded-lg transition-colors flex items-center gap-1 shrink-0">
                        <i class="fas fa-undo"></i> Hủy xem trước
                    </button>
                </div>

                <div class="overflow-x-auto pb-2">
                    <table class="w-full text-left border-collapse min-w-[1050px]">
                        <thead>
                            <tr class="bg-gray-100 text-xs font-semibold text-gray-600 border-b border-gray-200">
                                <th class="p-2.5 min-w-[220px] text-sm">Tên đợt thanh toán</th>
                                <th class="p-2.5 min-w-[95px] text-sm text-right">Tỷ lệ (%)</th>
                                <th class="p-2.5 min-w-[145px] text-sm text-right">Số tiền (Tự tính)</th>
                                <th class="p-2.5 min-w-[160px] text-sm">Thời điểm thanh toán</th>
                                <th class="p-2.5 min-w-[140px] text-sm">Giai đoạn kiểm soát</th>
                                <th class="p-2.5 min-w-[85px] text-sm text-center">Có chặn?</th>
                                <th class="p-2.5 min-w-[90px] text-sm">Hạn (ngày)</th>
                                <th class="p-2.5 min-w-[150px] text-center text-sm">Trạng thái / Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="milestonesTbody" class="divide-y divide-gray-100 bg-white text-sm">
                            <!-- Populated dynamically by renderMilestonesTable() -->
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap justify-between items-center mt-3 pt-3 border-t border-gray-200 gap-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        @if($sale->status !== 'cancelled' && !$hasPaidMilestone && $sale->pl_status !== 'approved')
                        <button type="button" onclick="addManualPaymentMilestoneRow()"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-dark transition-colors shadow-xs">
                            <i class="fas fa-plus"></i> Thêm đợt thanh toán
                        </button>
                        <button type="submit" id="btnSaveMilestones"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-3.5 py-1.5 text-xs font-bold text-white transition-colors shadow-xs">
                            <i class="fas fa-save"></i> Lưu điều khoản
                        </button>
                        <button type="button" onclick="resetMilestonesToOriginal()" id="btnResetMilestones"
                                class="hidden inline-flex items-center gap-1.5 rounded-lg bg-gray-200 hover:bg-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors shadow-xs">
                            <i class="fas fa-undo"></i> Khôi phục ban đầu
                        </button>
                        @endif
                    </div>
                    <span id="milestonePercentSumIndicator" class="text-sm font-bold text-gray-700">Tổng tỷ lệ: 0%</span>
                </div>
            </form>
        </div>

        <!-- MODALS -->
        <!-- 1. Upload proof modal -->
        <div id="proofModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-800" id="proofModalTitle">Upload UNC thanh toán</h3>
                    <button onclick="closeProofModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>
                <form id="proofForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Đính kèm UNC/Ủy nhiệm chi (UNC)</label>
                        <input type="file" name="proof_file" required
                               class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                    </div>
                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" onclick="closeProofModal()" class="btn-secondary text-xs px-4 py-2">Hủy</button>
                        <button type="submit" class="btn-primary text-xs px-4 py-2 font-bold text-white bg-primary hover:bg-primary-hover border-none rounded-lg">Tải lên & Gửi</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. BOD Approval modal -->
        <div id="exceptionModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 bg-red-50 border-b border-red-100 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-red-800" id="exceptionModalTitle">Phê duyệt ngoại lệ đợt</h3>
                    <button onclick="closeExceptionModal()" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
                </div>
                <form id="exceptionForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-red-800 uppercase tracking-wider mb-2">Tệp phê duyệt đính kèm (Bắt buộc, hỗ trợ chọn nhiều file)</label>
                        <input type="file" name="bod_approval_files[]" multiple required
                               class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                    </div>
                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" onclick="closeExceptionModal()" class="btn-secondary text-xs px-4 py-2">Hủy</button>
                        <button type="submit" class="btn-primary text-xs px-4 py-2 bg-red-600 hover:bg-red-700 border-none font-bold text-white shadow-sm rounded-lg">Duyệt Ngoại Lệ</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Reject Payment modal -->
        <div id="rejectPaymentModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 bg-red-50 border-b border-red-100 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-red-800" id="rejectPaymentModalTitle">Từ chối chứng từ thanh toán</h3>
                    <button onclick="closeRejectPaymentModal()" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
                </div>
                <form id="rejectPaymentForm" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Lý do từ chối <span class="text-red-500">*</span></label>
                        <textarea name="reason" required rows="3" placeholder="Nhập lý do từ chối xác nhận thanh toán..."
                                  class="w-full text-sm border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2">
                        <button type="button" onclick="closeRejectPaymentModal()" class="btn-secondary text-xs px-4 py-2">Hủy</button>
                        <button type="submit" class="btn-primary text-xs px-4 py-2 bg-red-600 hover:bg-red-700 border-none font-bold text-white shadow-sm rounded-lg">Xác nhận Từ chối</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            window._saleTotal = {{ (float)$sale->total }};
            window._initialMilestones = @json($milestones);
            window._initialPreset = "{{ $sale->payment_term_type ?? 'custom' }}";
            window._isMilestonesLocked = {{ ($sale->status === 'cancelled' || $hasPaidMilestone || $sale->pl_status === 'approved') ? 'true' : 'false' }};
            window._canApproveBOD = {{ $isBOD ? 'true' : 'false' }};
            window._isFinance = {{ $isFinance ? 'true' : 'false' }};
            window._currentUserId = {{ $currentUser->id }};
            window._salePaymentDelegatedTo = {{ $sale->payment_exception_delegated_to ? (int)$sale->payment_exception_delegated_to : 'null' }};
            window._allUsers = @json(\App\Models\User::orderBy('name')->get(['id', 'name']));
            window._baseUrl = "{{ url('/') }}";
            window._saleId = {{ $sale->id }};
            window._csrfToken = '{{ csrf_token() }}';

            let _currentMilestones = [];
            let _milestoneCounter = 0;
            let _isPreviewMode = false;

            function formatMoneyVN(num) {
                if (isNaN(num) || num === null || num === undefined) return '0';
                return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            function unformatMoneyVN(str) {
                if (typeof str === 'number') return str;
                if (!str) return 0;
                return parseFloat(str.toString().replace(/[^0-9.-]/g, '')) || 0;
            }

            function renderMilestonesTable(list, isPreview = false) {
                _currentMilestones = JSON.parse(JSON.stringify(list || []));
                _isPreviewMode = isPreview;
                const tbody = document.getElementById('milestonesTbody');
                if (!tbody) return;
                tbody.innerHTML = '';
                _milestoneCounter = 0;

                if (_currentMilestones.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-gray-400 text-xs italic">Chưa có đợt thanh toán nào. Nhấn "+ Thêm đợt thanh toán" để thêm.</td></tr>`;
                    updateTotalPercentIndicator();
                    return;
                }

                _currentMilestones.forEach((ms, index) => {
                    appendMilestoneRow(ms, index, isPreview);
                });

                updateTotalPercentIndicator();
            }

            function appendMilestoneRow(ms, idx, isPreview = false) {
                const tbody = document.getElementById('milestonesTbody');
                if (!tbody) return;

                const rowIdx = _milestoneCounter++;
                const row = document.createElement('tr');
                row.className = 'border-b border-gray-100 hover:bg-gray-50/50 transition-colors';
                row.id = `ms-row-${rowIdx}`;

                const name = ms.milestone_name || ms.label || '';
                const pct = ms.percentage ?? ms.percent ?? 0;
                let amt = ms.amount ? unformatMoneyVN(ms.amount) : 0;
                if (!amt && pct > 0 && window._saleTotal > 0) {
                    amt = Math.round(window._saleTotal * (pct / 100));
                }
                const timing = ms.timing || 'after_contract';
                const reqBefore = ms.required_before || 'after_delivery';
                const isBlock = ms.is_blocking || 'yes';
                const dueDays = ms.due_days ?? ms.days ?? 0;
                const status = ms.status || 'unpaid';
                const isLocked = window._isMilestonesLocked || status === 'paid';
                const isOriginal = !isPreview && ms.hasOwnProperty('status');

                // Render Action Cell
                let actionCellHtml = '';
                if (isOriginal) {
                    let statusLabel = 'Chưa thanh toán';
                    let statusColor = 'bg-gray-100 text-gray-700';
                    if (status === 'paid') {
                        statusLabel = 'Đã thanh toán';
                        statusColor = 'bg-green-100 text-green-700 font-bold';
                    } else if (status === 'pending_finance') {
                        statusLabel = 'Chờ Finance xác nhận';
                        statusColor = 'bg-yellow-100 text-yellow-700 font-bold';
                    } else if (status === 'approved_preload' || status === 'approved_export_before_payment') {
                        statusLabel = 'Ngoại lệ BOD';
                        statusColor = 'bg-purple-100 text-purple-700 font-bold border border-purple-200';
                    } else if (status === 'not_yet_due') {
                        statusLabel = 'Chưa đến hạn';
                        statusColor = 'bg-gray-100 text-gray-500';
                    } else if (status === 'due') {
                        statusLabel = 'Đến hạn';
                        statusColor = 'bg-orange-100 text-orange-700';
                    } else if (status === 'overdue') {
                        statusLabel = `Quá hạn (${ms.overdue_days || 0} ngày)`;
                        statusColor = 'bg-red-100 text-red-700 ring-1 ring-red-300 font-bold';
                    }

                    actionCellHtml = `
                        <div class="flex flex-col items-center gap-1.5 py-1">
                            <span class="px-2 py-0.5 rounded-full text-[11px] ${statusColor}">${statusLabel}</span>
                            <div class="flex items-center justify-center gap-1 flex-wrap">
                                ${['unpaid', 'due', 'overdue', 'not_yet_due'].includes(status) ? `
                                    <button type="button" onclick="openProofModal(${idx}, '${name.replace(/'/g, "\\'")}')"
                                            class="px-2 py-0.5 text-[11px] bg-primary text-white font-bold rounded hover:bg-primary-hover shadow-xs">
                                        <i class="fas fa-upload mr-0.5"></i> UNC
                                    </button>
                                    ${(window._isFinance || window._canApproveBOD) ? (
                                        ms.proof_file_path ? `
                                            <form action="${window._baseUrl}/sales/${window._saleId}/milestones/${idx}/confirm-payment" method="POST" class="inline-block">
                                                <input type="hidden" name="_token" value="${window._csrfToken}">
                                                <button type="submit" class="px-2 py-0.5 text-[11px] bg-green-600 text-white font-bold rounded hover:bg-green-700 shadow-xs" onclick="return confirm('Xác nhận thanh toán đợt này?')">
                                                    <i class="fas fa-check mr-0.5"></i> Xác nhận
                                                </button>
                                            </form>
                                        ` : `
                                            <span class="px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-400 font-semibold rounded" title="Yêu cầu UNC">Bắt buộc UNC</span>
                                        `
                                    ) : ''}
                                    ${(window._canApproveBOD || ms.delegated_to_id === window._currentUserId || window._salePaymentDelegatedTo === window._currentUserId) ? `
                                        <button type="button" onclick="openExceptionModal(${idx}, '${name.replace(/'/g, "\\'")}')"
                                                class="px-2 py-0.5 text-[11px] bg-red-600 hover:bg-red-700 text-white font-bold rounded shadow-xs">
                                            <i class="fas fa-shield-alt mr-0.5"></i> Duyệt
                                        </button>
                                    ` : ''}
                                ` : ''}
                                ${status === 'pending_finance' ? `
                                    ${ms.proof_file_path ? `<a href="${window._baseUrl}/storage/${ms.proof_file_path}" target="_blank" class="text-[11px] font-bold text-blue-600 hover:underline"><i class="fas fa-file-download"></i> UNC</a>` : ''}
                                    ${(window._isFinance || window._canApproveBOD) ? `
                                        <form action="${window._baseUrl}/sales/${window._saleId}/milestones/${idx}/confirm-payment" method="POST" class="inline-block">
                                            <input type="hidden" name="_token" value="${window._csrfToken}">
                                            <button type="submit" class="px-2 py-0.5 text-[11px] bg-green-600 text-white font-bold rounded hover:bg-green-700 shadow-xs" onclick="return confirm('Xác nhận thanh toán đợt này?')">
                                                <i class="fas fa-check"></i> Xác nhận
                                            </button>
                                        </form>
                                    ` : ''}
                                    ${window._isFinance ? `
                                        <button type="button" onclick="openRejectPaymentModal(${idx}, '${name.replace(/'/g, "\\'")}')" class="px-2 py-0.5 text-[11px] bg-red-600 text-white font-bold rounded hover:bg-red-700 shadow-xs">
                                            <i class="fas fa-times"></i> Từ chối
                                        </button>
                                    ` : ''}
                                ` : ''}
                                ${!isLocked ? `
                                    <button type="button" onclick="removeMilestoneRow(${rowIdx})" class="p-1 text-red-400 hover:text-red-600 rounded hover:bg-red-50" title="Xóa đợt"><i class="fas fa-trash-alt text-xs"></i></button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                } else {
                    actionCellHtml = `
                        <div class="text-center">
                            <button type="button" onclick="removeMilestoneRow(${rowIdx})" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors" title="Xóa đợt">
                                <i class="fas fa-trash-alt text-sm"></i>
                            </button>
                        </div>
                    `;
                }

                row.innerHTML = `
                    <td class="p-2">
                        <input type="text" name="milestones[${rowIdx}][milestone_name]" value="${name}" required
                               class="w-full border border-gray-300 rounded px-2.5 py-1 text-xs bg-white text-gray-800 focus:ring-1 focus:ring-primary ${isLocked ? 'bg-gray-100 cursor-not-allowed' : ''}"
                               ${isLocked ? 'readonly' : ''} oninput="markMilestonesModified()">
                    </td>
                    <td class="p-2">
                        <div class="flex items-center justify-end">
                            <input type="number" step="any" min="0" max="100" name="milestones[${rowIdx}][percentage]" value="${pct}"
                                   class="ms-percent-input w-20 border border-gray-300 rounded px-2 py-1 text-xs text-right bg-white text-gray-800 focus:ring-1 focus:ring-primary ${isLocked ? 'bg-gray-100 cursor-not-allowed' : ''}"
                                   ${isLocked ? 'readonly' : ''} oninput="onPercentInputChanged(${rowIdx})">
                            <span class="ml-1 text-xs text-gray-500">%</span>
                        </div>
                    </td>
                    <td class="p-2">
                        <div class="flex items-center justify-end">
                            <input type="text" name="milestones[${rowIdx}][amount]" value="${formatMoneyVN(amt)}"
                                   class="ms-amount-input w-28 border border-gray-300 rounded px-2 py-1 text-xs text-right font-medium bg-white text-gray-800 focus:ring-1 focus:ring-primary ${isLocked ? 'bg-gray-100 cursor-not-allowed' : ''}"
                                   ${isLocked ? 'readonly' : ''} oninput="onAmountInputChanged(${rowIdx})">
                            <span class="ml-1 text-xs text-gray-500">₫</span>
                        </div>
                    </td>
                    <td class="p-2">
                        <select name="milestones[${rowIdx}][timing]" class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white text-gray-800 ${isLocked ? 'bg-gray-100 cursor-not-allowed' : ''}"
                                ${isLocked ? 'disabled' : ''} onchange="markMilestonesModified()">
                            <option value="after_contract" ${timing === 'after_contract' ? 'selected' : ''}>Sau khi ký HĐMB</option>
                            <option value="after_delivery_notice" ${timing === 'after_delivery_notice' ? 'selected' : ''}>Sau thông báo giao hàng</option>
                            <option value="before_export" ${timing === 'before_export' ? 'selected' : ''}>Trước khi xuất hàng</option>
                            <option value="after_delivery" ${timing === 'after_delivery' ? 'selected' : ''}>Sau khi giao hàng</option>
                            <option value="after_invoice" ${timing === 'after_invoice' ? 'selected' : ''}>Sau khi xuất hóa đơn</option>
                        </select>
                        ${isLocked ? `<input type="hidden" name="milestones[${rowIdx}][timing]" value="${timing}">` : ''}
                    </td>
                    <td class="p-2">
                        <select name="milestones[${rowIdx}][required_before]" class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white text-gray-800 ${isLocked ? 'bg-gray-100 cursor-not-allowed' : ''}"
                                ${isLocked ? 'disabled' : ''} onchange="markMilestonesModified()">
                            <option value="before_order" ${reqBefore === 'before_order' ? 'selected' : ''}>Trước đặt hàng</option>
                            <option value="before_export" ${reqBefore === 'before_export' ? 'selected' : ''}>Trước xuất kho</option>
                            <option value="after_delivery" ${reqBefore === 'after_delivery' ? 'selected' : ''}>Sau giao hàng</option>
                        </select>
                        ${isLocked ? `<input type="hidden" name="milestones[${rowIdx}][required_before]" value="${reqBefore}">` : ''}
                    </td>
                    <td class="p-2 text-center">
                        <select name="milestones[${rowIdx}][is_blocking]" class="w-20 border border-gray-300 rounded px-1.5 py-1 text-xs text-center bg-white text-gray-800 ${isLocked ? 'bg-gray-100 cursor-not-allowed' : ''}"
                                ${isLocked ? 'disabled' : ''} onchange="markMilestonesModified()">
                            <option value="yes" ${isBlock === 'yes' ? 'selected' : ''}>Có</option>
                            <option value="no" ${isBlock === 'no' ? 'selected' : ''}>Không</option>
                        </select>
                        ${isLocked ? `<input type="hidden" name="milestones[${rowIdx}][is_blocking]" value="${isBlock}">` : ''}
                    </td>
                    <td class="p-2">
                        <div class="flex items-center">
                            <input type="number" min="0" name="milestones[${rowIdx}][due_days]" value="${dueDays}"
                                   class="w-16 border border-gray-300 rounded px-2 py-1 text-xs text-right bg-white text-gray-800 focus:ring-1 focus:ring-primary ${isLocked ? 'bg-gray-100 cursor-not-allowed' : ''}"
                                   ${isLocked ? 'readonly' : ''} oninput="markMilestonesModified()">
                            <span class="ml-1 text-[11px] text-gray-500">ngày</span>
                        </div>
                        <input type="hidden" name="milestones[${rowIdx}][required_docs]" value="${ms.required_docs || 'none'}">
                        <input type="hidden" name="milestones[${rowIdx}][status]" value="${status}">
                        <input type="hidden" name="milestones[${rowIdx}][confirmed_by]" value="${ms.confirmed_by || ''}">
                        <input type="hidden" name="milestones[${rowIdx}][confirmed_at]" value="${ms.confirmed_at || ''}">
                        <input type="hidden" name="milestones[${rowIdx}][proof_file_path]" value="${ms.proof_file_path || ''}">
                        <input type="hidden" name="milestones[${rowIdx}][bod_approval_file_path]" value="${ms.bod_approval_file_path || ''}">
                        <input type="hidden" name="milestones[${rowIdx}][delegated_to_id]" value="${ms.delegated_to_id || ''}">
                    </td>
                    <td class="p-2 text-center">
                        ${actionCellHtml}
                    </td>
                `;

                tbody.appendChild(row);
            }

            function onPercentInputChanged(rowIdx) {
                const row = document.getElementById(`ms-row-${rowIdx}`);
                if (!row) return;
                const pctInput = row.querySelector('.ms-percent-input');
                const amtInput = row.querySelector('.ms-amount-input');
                const pct = parseFloat(pctInput.value) || 0;
                if (window._saleTotal > 0 && amtInput) {
                    const calculatedAmt = Math.round(window._saleTotal * (pct / 100));
                    amtInput.value = formatMoneyVN(calculatedAmt);
                }
                updateTotalPercentIndicator();
                markMilestonesModified();
            }

            function onAmountInputChanged(rowIdx) {
                const row = document.getElementById(`ms-row-${rowIdx}`);
                if (!row) return;
                const pctInput = row.querySelector('.ms-percent-input');
                const amtInput = row.querySelector('.ms-amount-input');
                const rawAmt = unformatMoneyVN(amtInput.value);
                amtInput.value = formatMoneyVN(rawAmt);
                if (window._saleTotal > 0 && pctInput) {
                    const calculatedPct = (rawAmt / window._saleTotal * 100).toFixed(2);
                    pctInput.value = calculatedPct;
                }
                updateTotalPercentIndicator();
                markMilestonesModified();
            }

            function updateTotalPercentIndicator() {
                const pctInputs = document.querySelectorAll('#milestonesTbody .ms-percent-input');
                let total = 0;
                pctInputs.forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                const indicator = document.getElementById('milestonePercentSumIndicator');
                if (indicator) {
                    indicator.innerText = `Tổng tỷ lệ: ${total.toFixed(1)}%`;
                    if (Math.abs(total - 100) < 0.01) {
                        indicator.className = 'text-sm font-bold text-green-600';
                    } else {
                        indicator.className = 'text-sm font-bold text-red-600';
                    }
                }
            }

            function markMilestonesModified() {
                const btnReset = document.getElementById('btnResetMilestones');
                if (btnReset) btnReset.classList.remove('hidden');
            }

            function onPaymentTermPresetChange(selectEl) {
                const val = selectEl.value;
                const selectedOpt = selectEl.options[selectEl.selectedIndex];
                const templateInput = document.getElementById('selectedTemplateId');
                if (templateInput) templateInput.value = val;

                if (val && val.startsWith('template_')) {
                    const rawItems = selectedOpt.getAttribute('data-items');
                    const templateName = selectedOpt.getAttribute('data-name') || selectedOpt.text;
                    let items = [];
                    try {
                        items = JSON.parse(rawItems || '[]');
                    } catch (e) {
                        console.error('Error parsing template items:', e);
                    }

                    // Show preview banner
                    const previewAlert = document.getElementById('milestonePreviewAlert');
                    const previewLabel = document.getElementById('previewTemplateNameLabel');
                    if (previewAlert && previewLabel) {
                        previewLabel.innerText = templateName;
                        previewAlert.classList.remove('hidden');
                    }

                    renderMilestonesTable(items, true);
                    markMilestonesModified();
                } else {
                    const previewAlert = document.getElementById('milestonePreviewAlert');
                    if (previewAlert) previewAlert.classList.add('hidden');
                    renderMilestonesTable(window._initialMilestones, false);
                    markMilestonesModified();
                }
            }

            function addManualPaymentMilestoneRow() {
                const pctInputs = document.querySelectorAll('#milestonesTbody .ms-percent-input');
                let total = 0;
                pctInputs.forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                const remainingPct = Math.max(0, parseFloat((100 - total).toFixed(2)));
                const newIdx = document.querySelectorAll('#milestonesTbody tr').length + 1;

                appendMilestoneRow({
                    milestone_name: `Đợt ${newIdx}`,
                    percentage: remainingPct,
                    amount: Math.round(window._saleTotal * (remainingPct / 100)),
                    timing: 'after_contract',
                    required_before: 'after_delivery',
                    is_blocking: 'no',
                    due_days: 0,
                    required_docs: 'none',
                    status: 'unpaid'
                }, newIdx, true);

                updateTotalPercentIndicator();
                markMilestonesModified();
            }

            function removeMilestoneRow(rowIdx) {
                const row = document.getElementById(`ms-row-${rowIdx}`);
                if (row) {
                    row.remove();
                    updateTotalPercentIndicator();
                    markMilestonesModified();
                }
            }

            function resetMilestonesToOriginal() {
                const select = document.getElementById('milestonePresetSelect');
                if (select) select.value = window._initialPreset;
                const templateInput = document.getElementById('selectedTemplateId');
                if (templateInput) templateInput.value = window._initialPreset;

                const previewAlert = document.getElementById('milestonePreviewAlert');
                if (previewAlert) previewAlert.classList.add('hidden');

                const btnReset = document.getElementById('btnResetMilestones');
                if (btnReset) btnReset.classList.add('hidden');

                renderMilestonesTable(window._initialMilestones, false);
            }

            function validateMilestonesSubmit(e) {
                const rows = document.querySelectorAll('#milestonesTbody tr');
                if (rows.length === 0) {
                    alert('Vui lòng tạo ít nhất 1 đợt thanh toán.');
                    e.preventDefault();
                    return false;
                }

                const pctInputs = document.querySelectorAll('#milestonesTbody .ms-percent-input');
                let total = 0;
                pctInputs.forEach(input => {
                    total += parseFloat(input.value) || 0;
                });

                if (Math.abs(total - 100) > 0.01) {
                    if (!confirm(`Tổng tỷ lệ hiện tại là ${total.toFixed(1)}% (chưa bằng 100%). Bạn có chắc chắn muốn lưu điều khoản này không?`)) {
                        e.preventDefault();
                        return false;
                    }
                }
                return true;
            }

            function openProofModal(index, name) {
                const modal = document.getElementById('proofModal');
                const form = document.getElementById('proofForm');
                const title = document.getElementById('proofModalTitle');
                if (modal && form) {
                    title.innerText = `Upload UNC: ${name}`;
                    form.action = `{{ url('/') }}/sales/{{ $sale->id }}/milestones/${index}/submit-proof`;
                    modal.classList.remove('hidden');
                }
            }
            function closeProofModal() {
                document.getElementById('proofModal').classList.add('hidden');
            }
            function openExceptionModal(index, name) {
                const modal = document.getElementById('exceptionModal');
                const form = document.getElementById('exceptionForm');
                const title = document.getElementById('exceptionModalTitle');
                if (modal && form) {
                    title.innerText = `Duyệt ngoại lệ BOD: ${name}`;
                    form.action = `{{ url('/') }}/sales/{{ $sale->id }}/milestones/${index}/approve-exception`;
                    modal.classList.remove('hidden');
                }
            }
            function closeExceptionModal() {
                document.getElementById('exceptionModal').classList.add('hidden');
            }
            function openRejectPaymentModal(index, name) {
                const modal = document.getElementById('rejectPaymentModal');
                const form = document.getElementById('rejectPaymentForm');
                const title = document.getElementById('rejectPaymentModalTitle');
                if (modal && form) {
                    title.innerText = `Từ chối UNC đợt: ${name}`;
                    form.action = `{{ url('/') }}/sales/{{ $sale->id }}/milestones/${index}/reject-payment`;
                    modal.classList.remove('hidden');
                }
            }
            function closeRejectPaymentModal() {
                document.getElementById('rejectPaymentModal').classList.add('hidden');
            }

            document.addEventListener('DOMContentLoaded', function() {
                renderMilestonesTable(window._initialMilestones, false);
            });
        </script>
    </div>

    <!-- Tabs Header -->
    <div x-data="{ activeTab: window.location.hash ? window.location.hash.substring(1) : 'items' }" x-init="$watch('activeTab', value => window.location.hash = value)">
        <div class="border-b border-gray-200 bg-white rounded-t-lg shadow-sm">
            <nav class="flex -mb-px px-4 space-x-8" aria-label="Tabs">
                <button @click="activeTab = 'items'"
                    :class="activeTab === 'items' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all duration-200">
                    <i class="fas fa-boxes mr-2"></i> Chi tiết sản phẩm
                </button>
                <button @click="activeTab = 'pnl'"
                    :class="activeTab === 'pnl' ? 'border-cyan-600 text-cyan-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all duration-200">
                    <i class="fas fa-chart-line mr-2"></i> Phân tích P&L
                </button>
                <button @click="activeTab = 'invoice'"
                    :class="activeTab === 'invoice' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all duration-200">
                    <i class="fas fa-file-invoice-dollar mr-2"></i> Quản lý Hóa đơn
                    @php $pendingCount = $sale->invoiceRequests->filter(fn($r) => $r->status === 'pending')->count(); @endphp
                    @if($pendingCount > 0)
                        <span class="ml-1.5 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full animate-pulse">{{ $pendingCount }}</span>
                    @elseif($sale->invoiceRequests->count() > 0)
                        <span class="ml-1.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $sale->invoiceRequests->count() }}</span>
                    @endif
                </button>
                <button @click="activeTab = 'procurement'"
                    :class="activeTab === 'procurement' ? 'border-orange-600 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all duration-200">
                    <i class="fas fa-key mr-2"></i> Trạng thái license
                </button>
                <button @click="activeTab = 'warehouse'"
                    :class="activeTab === 'warehouse' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all duration-200">
                    <i class="fas fa-warehouse mr-2"></i> Kho & Giao nhận
                    @php
                        $linkedExports = \App\Models\Export::where('reference_type', 'sale')->where('reference_id', $sale->id)->get();
                        $activeExportsCount = $linkedExports->filter(fn($e) => in_array($e->status, ['draft', 'pending_admin', 'pending_invoice']))->count();
                    @endphp
                    @if($activeExportsCount > 0)
                        <span class="ml-1.5 bg-teal-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full animate-pulse">{{ $activeExportsCount }}</span>
                    @endif
                </button>
                <button @click="activeTab = 'payment_history'"
                    :class="activeTab === 'payment_history' ? 'border-indigo-605 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all duration-200">
                    <i class="fas fa-history mr-2"></i> Lịch sử Phê duyệt
                    @php $logCount = $sale->paymentApprovalLogs->count(); @endphp
                    @if($logCount > 0)
                        <span class="ml-1.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ $logCount }}</span>
                    @endif
                </button>
            </nav>
        </div>

        <!-- Tabs Content -->
        
        <!-- Tab: Items -->
        <div x-show="activeTab === 'items'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95">
            @php
                $isForeign = $sale->currency && !$sale->currency->is_base;
                $rate = $sale->exchange_rate ?: 1;
                $decimals = $sale->currency->decimal_places ?? 2;
                $symbol = $sale->currency->symbol ?? $sale->currency->code ?? '';

                $subtotalVnd = $sale->subtotal;
                $subtotalForeign = $isForeign ? round($sale->subtotal / $rate, $decimals) : $subtotalVnd;
                $discountForeign = round($subtotalForeign * ($sale->discount / 100), $decimals);
                $discountVnd = $isForeign ? round($discountForeign * $rate) : round($subtotalVnd * ($sale->discount / 100));
                
                $vatForeign = $sale->items->sum('vat_amount');
                $vatVnd = $sale->vat_amount ?: round($vatForeign * $rate);

                $totalForeign = $sale->total_foreign ?? ($isForeign ? round($subtotalForeign - $discountForeign + $vatForeign, $decimals) : $sale->total);
                $totalVnd = $sale->total;
            @endphp
            
            <div class="bg-white rounded-b-lg shadow-sm overflow-hidden">
                <div class="p-3.5 bg-gray-50/90 border-b border-gray-200 flex flex-wrap justify-between items-center gap-2">
                    <div class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-cubes-stacked text-indigo-600"></i> Danh sách BOM sản phẩm ({{ $sale->items->count() }} mục)
                    </div>
                    @if(!$sale->hasPayment() && $sale->status !== 'cancelled')
                        <button type="button" onclick="openAdjustBomModal()" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700 transition-all shadow-xs flex items-center gap-1.5">
                            <i class="fas fa-edit"></i> Điều chỉnh BOM sản phẩm
                        </button>
                    @endif
                </div>
                <div class="overflow-x-auto overflow-y-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã sản phẩm</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SL</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá bán</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">VAT</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Bảo hành</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền (gồm VAT)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($sale->items as $index => $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    <div>{{ $item->product->code ?? $item->product_name }}</div>
                                    @if($item->is_from_stock)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 mt-1">
                                            <i class="fas fa-warehouse mr-1"></i>Hàng sẵn kho (Không cần đặt hàng)
                                        </span>
                                    @elseif($item->is_service)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-blue-100 text-blue-800 mt-1">
                                            <i class="fas fa-hand-holding-usd mr-1"></i>Dịch vụ (Không cần đặt hàng)
                                        </span>
                                    @elseif($item->supplier)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800 mt-1">
                                            <i class="fas fa-truck mr-1"></i>Đặt hàng: {{ $item->supplier->name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->quantity) }}</td>
                                <td class="px-4 py-3 text-right">
                                     @if($isForeign)
                                        <div class="text-sm font-medium text-gray-900">{{ $symbol }} {{ number_format($item->price, $decimals) }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ number_format($item->price * $rate) }} đ</div>
                                     @else
                                        <div class="text-sm font-medium text-gray-900">{{ number_format($item->price) }} đ</div>
                                     @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                     {{ $item->vat == -1 ? 'KCT' : (float)$item->vat . '%' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    @if($item->warranty_months)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fas fa-shield-alt mr-1"></i>{{ $item->warranty_months }} tháng
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @php
                                        $effectiveVat = $item->vat < 0 ? 0 : (float)$item->vat;
                                        $itemTotalWithVat = $item->quantity * $item->price * (1 + $effectiveVat / 100);
                                    @endphp
                                    @if($isForeign)
                                        <div class="text-sm font-medium text-gray-900">{{ $symbol }} {{ number_format($itemTotalWithVat, $decimals) }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ number_format($itemTotalWithVat * $rate) }} đ</div>
                                    @else
                                        <div class="text-sm font-medium text-gray-900">{{ number_format($itemTotalWithVat) }} đ</div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-white border-t-2 border-gray-100">
                            @php
                                $subtotalWithVatVnd = 0;
                                $subtotalWithVatForeign = 0;
                                foreach($sale->items as $item) {
                                    $effectiveVat = $item->vat < 0 ? 0 : (float)$item->vat;
                                    $itemSubtotalVnd = $item->quantity * ($isForeign ? $item->price * $rate : $item->price);
                                    $subtotalWithVatVnd += $itemSubtotalVnd * (1 + $effectiveVat / 100);
                                    
                                    if ($isForeign) {
                                        $itemSubtotalForeign = $item->quantity * $item->price;
                                        $subtotalWithVatForeign += $itemSubtotalForeign * (1 + $effectiveVat / 100);
                                    }
                                }
                            @endphp
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Tổng tiền hàng (chưa VAT):</td>
                                <td colspan="1" class="px-4 py-3 text-right font-semibold">
                                    @if($isForeign)
                                        <div class="text-sm text-gray-900">{{ $symbol }} {{ number_format($subtotalForeign, $decimals) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($subtotalVnd) }} đ</div>
                                    @else
                                        <div class="text-sm text-gray-900">{{ number_format($subtotalVnd) }} đ</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Tổng tiền hàng (đã gồm VAT):</td>
                                <td colspan="1" class="px-4 py-3 text-right font-semibold">
                                    @if($isForeign)
                                        <div class="text-sm text-gray-900">{{ $symbol }} {{ number_format($subtotalWithVatForeign, $decimals) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($subtotalWithVatVnd) }} đ</div>
                                    @else
                                        <div class="text-sm text-gray-900">{{ number_format($subtotalWithVatVnd) }} đ</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Chiết khấu ({{ $sale->discount }}%):</td>
                                <td colspan="1" class="px-4 py-3 text-right font-semibold">
                                    @if($isForeign)
                                        <div class="text-sm text-red-600">-{{ $symbol }} {{ number_format($discountForeign, $decimals) }}</div>
                                        <div class="text-xs text-red-400">-{{ number_format($discountVnd) }} đ</div>
                                    @else
                                        <div class="text-sm text-red-600">-{{ number_format($discountVnd) }} đ</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Thuế VAT:</td>
                                <td colspan="1" class="px-4 py-3 text-right font-semibold">
                                    @if($isForeign)
                                        <div class="text-sm text-gray-900">{{ $symbol }} {{ number_format($vatForeign, $decimals) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($vatVnd) }} đ</div>
                                    @else
                                        <div class="text-sm text-gray-900">{{ number_format($vatVnd) }} đ</div>
                                    @endif
                                </td>
                            </tr>
                            <tr class="bg-blue-50">
                                <td colspan="6" class="px-4 py-3 text-base font-bold text-gray-900 text-right uppercase">Tổng cộng (gồm VAT & CK):</td>
                                <td colspan="1" class="px-4 py-3 text-right">
                                    @if($isForeign)
                                        <div class="text-lg font-bold text-blue-700">{{ $symbol }} {{ number_format($totalForeign, $decimals) }}</div>
                                        <div class="text-xs text-blue-500 font-normal">≈ {{ number_format($totalVnd) }} đ</div>
                                    @else
                                        <div class="text-lg font-bold text-blue-700">{{ number_format($totalVnd) }} đ</div>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Order Expenses Section in Show View - Fully editable with direct save --}}
                @php
                    $expenseData = $sale->expenses
                        ->unique('type')
                        ->values()
                        ->map(fn($e) => [
                            'type' => $e->type,
                            'input_mode' => $e->input_mode ?? 'fixed',
                            'percent_value' => $e->percent_value,
                            'amount' => $e->amount,
                            'description' => $e->description ?? '',
                        ])->toArray();
                    $isExpenseLocked = ($sale->status === 'cancelled' || $sale->hasPayment());
                @endphp
                <form action="{{ route('sales.expenses.sync', $sale->id) }}" method="POST" class="mt-6 border-t border-gray-200 pt-5 px-4 pb-4">
                    @csrf
                    <script>window._saleSubtotal = {{ (float)$sale->subtotal }};</script>
                    @include('sales.partials.expense-section', [
                        'expenses' => $expenseData,
                        'currencySymbol' => $sale->currency ? ($sale->currency->symbol ?? $sale->currency->code) : '₫',
                        'isLocked' => $isExpenseLocked,
                        'showSaveButton' => !$isExpenseLocked,
                    ])
                </form>
            </div>
        </div>

        <!-- Tab: P&L -->
        <div x-show="activeTab === 'pnl'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" class="mt-4">
            @include('sales.partials.pnl-tab')
        </div>


        <!-- Tab: Invoice -->
        <div x-show="activeTab === 'invoice'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" class="mt-4">
            @include('sales.partials.invoice-tab')
        </div>

        <!-- Tab: Procurement -->
        <div x-show="activeTab === 'procurement'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" class="mt-4">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Trạng thái hàng về & License</h3>
                    <span class="text-[10px] text-gray-400 italic">Dữ liệu được cập nhật bởi bộ phận PO</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PO</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">File License</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $allPoItems = $sale->all_purchase_orders->flatMap(function($po) {
                                    return $po->items;
                                });
                            @endphp
                            @forelse($allPoItems as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ $item->product_name ?: ($item->saleOrderRequestItem->part_number ?? ($item->product->name ?? 'N/A')) }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-bold text-blue-600">{{ $item->purchaseOrder->code }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold shadow-sm
                                        {{ $item->status == 'ordered' ? 'bg-gray-100 text-gray-600' : '' }}
                                        {{ $item->status == 'shipping' ? 'bg-blue-100 text-blue-600' : '' }}
                                        {{ $item->status == 'received' ? 'bg-green-100 text-green-600' : '' }}
                                        {{ $item->status == 'cancelled' ? 'bg-red-100 text-red-600' : '' }}">
                                        {{ $item->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($item->license_file)
                                        @php
                                            $licenseFiles = [];
                                            $decoded = json_decode($item->license_file, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                $licenseFiles = $decoded;
                                            } else {
                                                $licenseFiles = [$item->license_file];
                                            }
                                        @endphp
                                        <div class="flex flex-wrap justify-center gap-1.5">
                                            @foreach($licenseFiles as $index => $file)
                                                <a href="javascript:void(0)" onclick="openFilePreviewModal('{{ route('purchase-orders.items.preview-license', [$item->id, $index]) }}', '{{ basename($file) }}')" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-600 text-white rounded text-xs font-bold hover:bg-indigo-700 transition-all shadow-sm active:scale-95 cursor-pointer"
                                                    title="{{ basename($file) }}">
                                                    <i class="fas fa-eye text-[10px]"></i> 
                                                    {{ count($licenseFiles) > 1 ? 'Lic ' . ($index + 1) : 'Xem License' }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                    <span class="text-xs text-gray-400 italic">Chờ upload...</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-2 text-gray-400">
                                        <i class="fas fa-shopping-cart text-4xl"></i>
                                        <p class="text-sm italic">Chưa có thông tin đặt mua hàng cho đơn này.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Payment Approval History -->
        <div x-show="activeTab === 'payment_history'" class="mt-4">
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center border-b border-gray-100 pb-3">
                    <i class="fas fa-history text-primary mr-2"></i>
                    Nhật ký Phê duyệt & Thay đổi Điều khoản Thanh toán
                </h4>
                <div class="relative border-l border-gray-200 ml-4 pl-6 space-y-6">
                    @forelse($sale->paymentApprovalLogs as $log)
                        <div class="relative">
                            <!-- Circle icon on line -->
                            <span class="absolute -left-[36px] top-0.5 flex items-center justify-center w-6 h-6 rounded-full ring-4 ring-white
                                @if($log->action === 'proof_uploaded') bg-blue-100 text-blue-700
                                @elseif($log->action === 'finance_confirmed') bg-green-100 text-green-700
                                @elseif($log->action === 'finance_rejected') bg-red-100 text-red-700
                                @elseif($log->action === 'bod_exception_approved') bg-purple-100 text-purple-700
                                @elseif($log->action === 'delegated') bg-indigo-100 text-indigo-700
                                @elseif($log->action === 'delegation_revoked') bg-yellow-100 text-yellow-700
                                @else bg-gray-100 text-gray-700 @endif">
                                @if($log->action === 'proof_uploaded') <i class="fas fa-upload text-[10px]"></i>
                                @elseif($log->action === 'finance_confirmed') <i class="fas fa-check text-[10px]"></i>
                                @elseif($log->action === 'finance_rejected') <i class="fas fa-times text-[10px]"></i>
                                @elseif($log->action === 'bod_exception_approved') <i class="fas fa-shield-alt text-[10px]"></i>
                                @elseif($log->action === 'delegated') <i class="fas fa-user-shield text-[10px]"></i>
                                @elseif($log->action === 'delegation_revoked') <i class="fas fa-user-slash text-[10px]"></i>
                                @else <i class="fas fa-info text-[10px]"></i> @endif
                            </span>
                            <div>
                                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                    <span class="font-bold text-gray-900">{{ $log->performer->name ?? 'System' }}</span>
                                    <span>{{ $log->performed_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                                <p class="text-sm text-gray-800 font-semibold">
                                    @if($log->action === 'proof_uploaded') Đã tải lên UNC thanh toán đợt "{{ $log->schedule->milestone_name ?? 'N/A' }}"
                                    @elseif($log->action === 'finance_confirmed') Finance đã xác nhận thanh toán đợt "{{ $log->schedule->milestone_name ?? 'N/A' }}"
                                    @elseif($log->action === 'finance_rejected') Finance đã từ chối xác nhận thanh toán đợt "{{ $log->schedule->milestone_name ?? 'N/A' }}"
                                    @elseif($log->action === 'bod_exception_approved') Đã duyệt ngoại lệ đợt "{{ $log->schedule->milestone_name ?? 'N/A' }}"
                                    @elseif($log->action === 'delegated') Đã ủy quyền duyệt ngoại lệ cho "{{ $log->new_value }}"
                                    @elseif($log->action === 'delegation_revoked') Đã hủy ủy quyền duyệt ngoại lệ
                                    @else Thay đổi trạng thái
                                    @endif
                                </p>
                                @if($log->reason)
                                    <p class="text-xs text-gray-500 mt-1 italic">"{{ $log->reason }}"</p>
                                @endif
                                @if($log->attachment_path)
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $log->attachment_path) }}" target="_blank"
                                           class="inline-flex items-center text-xs font-bold text-primary hover:underline">
                                            <i class="fas fa-paperclip mr-1"></i> Xem tệp đính kèm
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 italic">Chưa có nhật ký hoạt động nào cho điều khoản thanh toán đơn hàng này.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Tab: Warehouse & Export -->
        <div x-show="activeTab === 'warehouse'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95">
            <div class="bg-white rounded-b-lg shadow-sm p-6 border border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Chi tiết hàng hóa & Xuất kho</h3>
                        <p class="text-xs text-gray-500 mt-1">Theo dõi số lượng hàng về, số lượng đã xuất và số lượng còn lại có thể xuất.</p>
                    </div>
                </div>

                <!-- Cross-reference Banner cho Kho tra cứu (STT 4 & 5) -->
                <div class="mb-6 p-3.5 bg-slate-50 border border-slate-200 rounded-lg flex flex-wrap items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center gap-4 flex-wrap text-xs">
                        <div class="flex items-center gap-1.5 font-bold text-slate-800">
                            <i class="fas fa-shopping-cart text-indigo-600"></i> Mã đơn hàng (SO): <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded border border-indigo-100 font-mono text-sm">{{ $sale->code }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 font-bold text-slate-800">
                            <i class="fas fa-file-contract text-blue-600"></i> Mã PO Khách hàng: <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-100 font-mono text-sm">{{ $sale->po_number ?: 'Không có (Tìm theo SO)' }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 font-bold text-slate-800">
                            <i class="fas fa-file-invoice text-emerald-600"></i> Mã Yêu cầu Hóa đơn: 
                            @php $confirmedInv = $sale->invoiceRequests->where('status', 'official_issued')->first() ?: $sale->invoiceRequests->first(); @endphp
                            @if($confirmedInv)
                                <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded border border-emerald-200 font-mono text-sm">HĐ #{{ $confirmedInv->id }} ({{ $confirmedInv->status_label }})</span>
                            @else
                                <span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded border border-amber-200">Chưa tạo yêu cầu HĐ</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto mb-8 border border-gray-100 rounded-lg">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50">
                            <tr class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="px-4 py-3">STT</th>
                                <th class="px-4 py-3">Tên sản phẩm / Part Number</th>
                                <th class="px-4 py-3 text-center">Số lượng bán (SO)</th>
                                <th class="px-4 py-3 text-center">Hàng đã về (Nhập kho)</th>
                                <th class="px-4 py-3 text-center">Đã xuất kho</th>
                                <th class="px-4 py-3 text-center">Số lượng còn lại</th>
                                <th class="px-4 py-3 text-center">Trạng thái</th>
                                <th class="px-4 py-3 text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @foreach($sale->items as $index => $item)
                                @php
                                    $totalOrdered = $item->quantity;
                                    $totalReceived = 0;
                                    $prItems = \App\Models\SaleOrderRequestItem::where('sale_item_id', $item->id)->get();
                                    foreach ($prItems as $prItem) {
                                        $totalReceived += $prItem->received_quantity_total;
                                    }

                                    $totalExported = \App\Models\ExportItem::whereHas('export', function ($q) use ($sale) {
                                            $q->where('reference_type', 'sale')
                                              ->where('reference_id', $sale->id)
                                              ->where('status', 'completed');
                                        })
                                        ->where('product_id', $item->product_id)
                                        ->sum('quantity');

                                    $remainingToExport = max(0, $totalOrdered - $totalExported);
                                    
                                    $isLicense = false;
                                    $productCode = $item->product?->code;
                                    $productName = $item->product?->name ?? $item->product_name;
                                    if ($productCode && (
                                        str_starts_with($productCode, 'FC-') || 
                                        stripos($productCode, 'license') !== false || 
                                        stripos($productName, 'license') !== false || 
                                        stripos($productCode, 'e-license') !== false || 
                                        stripos($productName, 'e-license') !== false || 
                                        stripos($productCode, 'subscription') !== false || 
                                        stripos($productName, 'subscription') !== false || 
                                        stripos($productCode, 'renewal') !== false || 
                                        stripos($productName, 'renewal') !== false
                                    )) {
                                        $isLicense = true;
                                    }

                                    $isService = false;
                                    if (($item->is_service ?? false) || ($productCode && (
                                        str_starts_with($productCode, 'THSV-') || 
                                        stripos($productCode, 'COTERM') !== false ||
                                        stripos($productCode, 'service') !== false ||
                                        stripos($productName, 'service') !== false ||
                                        stripos($productCode, 'support') !== false ||
                                        stripos($productName, 'support') !== false
                                    ))) {
                                        $isService = true;
                                    }

                                    $salespersonName = $sale->employee?->name ?? $sale->user?->name;
                                    $isStockItem = $item->is_from_stock || (!$sale->isProjectOrder() && $sale->type === 'retail');
                                    $heldByMe = 0;
                                    if ($isStockItem && $item->product_id && $salespersonName) {
                                        $heldByMe = \App\Models\ProductItem::where('product_id', $item->product_id)
                                            ->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                                            ->where('borrower', $salespersonName)
                                            ->sum('quantity');
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-800">
                                        {{ $item->product?->code ?? $item->product_name }}
                                        <div class="text-xs font-normal text-gray-500 mt-0.5">{{ $item->product_name }}</div>
                                        @if($isStockItem)
                                            <span class="inline-block bg-teal-50 text-teal-700 text-[10px] font-bold px-1.5 py-0.2 rounded border border-teal-200 mt-0.5">
                                                <i class="fas fa-boxes-stacked text-[9px] mr-0.5"></i> Hàng có sẵn trong kho
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-800">{{ number_format($totalOrdered) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($isLicense)
                                            <span class="text-xs text-purple-600 font-medium bg-purple-50 px-2 py-0.5 rounded-full"><i class="fas fa-key mr-1"></i>License (K.Nhập)</span>
                                        @elseif($isService)
                                            <span class="text-xs text-blue-600 font-medium bg-blue-50 px-2 py-0.5 rounded-full"><i class="fas fa-concierge-bell mr-1"></i>Dịch vụ (K.Nhập)</span>
                                        @elseif($isStockItem)
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs font-bold {{ $heldByMe >= $totalOrdered ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-amber-700 bg-amber-50 border-amber-200' }} px-2 py-0.5 rounded border">
                                                    <i class="fas fa-hand-holding mr-1"></i>Đang giữ: {{ $heldByMe }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="font-semibold text-gray-600">{{ number_format($totalReceived) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-emerald-600">{{ number_format($totalExported) }}</td>
                                    <td class="px-4 py-3 text-center font-bold text-red-500">{{ number_format($remainingToExport) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($remainingToExport == 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase"><i class="fas fa-check-circle mr-1"></i>Đã xuất đủ</span>
                                        @elseif($totalExported > 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-100 text-yellow-800 uppercase"><i class="fas fa-hourglass-half mr-1"></i>Xuất một phần</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 uppercase"><i class="fas fa-minus-circle mr-1"></i>Chưa xuất</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @if($isLicense)
                                            <span class="text-xs text-purple-600 font-medium bg-purple-50 px-2 py-0.5 rounded-full"><i class="fas fa-key mr-1"></i>License</span>
                                        @elseif($isService)
                                            <span class="text-xs text-blue-600 font-medium bg-blue-50 px-2 py-0.5 rounded-full"><i class="fas fa-concierge-bell mr-1"></i>Dịch vụ</span>
                                        @elseif($item->isProjectItem())
                                            <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-500 rounded text-xs font-semibold border border-gray-200 cursor-not-allowed" title="Hàng dự án không được phép mượn hàng">
                                                <i class="fas fa-lock mr-1 text-[10px] text-gray-400"></i> Hàng dự án (Không mượn)
                                            </span>
                                        @elseif($isStockItem && $item->product_id)
                                            @if($heldByMe >= $remainingToExport && $remainingToExport > 0)
                                                <span class="inline-flex items-center px-2 py-1 bg-emerald-50 text-emerald-700 rounded text-xs font-semibold border border-emerald-200" title="Sales đã giữ đủ hàng trong kho để xuất">
                                                    <i class="fas fa-check-circle mr-1 text-emerald-600"></i> Đã giữ đủ hàng
                                                </span>
                                            @elseif($remainingToExport > 0)
                                                <a href="{{ route('tickets.create', ['product_id' => $item->product_id]) }}" class="inline-flex items-center px-2.5 py-1 bg-teal-600 text-white hover:bg-teal-700 rounded text-xs font-bold transition-all shadow-xs" title="Cần mượn thêm {{ max(0, $remainingToExport - $heldByMe) }} sản phẩm để có hàng xuất">
                                                    <i class="fas fa-people-arrows mr-1"></i> Mượn hàng (Cần {{ max(0, $remainingToExport - $heldByMe) }})
                                                </a>
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        @elseif($item->product_id)
                                            <a href="{{ route('tickets.create', ['product_id' => $item->product_id]) }}" class="inline-flex items-center px-2.5 py-1 bg-teal-50 text-teal-700 hover:bg-teal-100 hover:text-teal-800 rounded text-xs font-bold transition-all border border-teal-200" title="Yêu cầu mượn hàng cho sản phẩm này">
                                                <i class="fas fa-people-arrows mr-1"></i> Mượn hàng
                                            </a>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
                        <i class="fas fa-receipt text-gray-400"></i> Lịch sử / Danh sách yêu cầu xuất kho
                    </h4>
                    
                    @if($linkedExports->isEmpty())
                        <div class="text-center py-8 text-gray-400 border border-dashed border-gray-200 rounded-lg">
                            <i class="fas fa-receipt text-3xl mb-2 text-gray-300"></i>
                            <p class="text-sm">Chưa có yêu cầu xuất kho nào cho đơn hàng này.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto border border-gray-100 rounded-lg">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead class="bg-gray-50">
                                    <tr class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                                        <th class="px-4 py-3">Mã phiếu</th>
                                        <th class="px-4 py-3">Kho xuất</th>
                                        <th class="px-4 py-3">Ngày tạo</th>
                                        <th class="px-4 py-3">Tổng số lượng</th>
                                        <th class="px-4 py-3">Ghi chú</th>
                                        <th class="px-4 py-3 text-center">Trạng thái</th>
                                        <th class="px-4 py-3 text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($linkedExports as $export)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-4 py-3 font-semibold">
                                                <a href="{{ route('exports.show', $export->id) }}" class="text-blue-600 hover:underline">
                                                    {{ $export->code }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 text-gray-600">{{ $export->warehouse->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-gray-500">{{ $export->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="px-4 py-3 font-medium">{{ number_format($export->total_qty) }}</td>
                                            <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate" title="{{ $export->note }}">{{ $export->note ?: '-' }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-{{ $export->status_color }}-100 text-{{ $export->status_color }}-700 uppercase">
                                                    {{ $export->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @php
                                                    $user = auth()->user();
                                                    $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('purchase_manager'));
                                                @endphp
                                                @if($export->status === 'pending_admin')
                                                    @if($isAdmin)
                                                        <div class="flex items-center justify-center gap-1.5">
                                                            <!-- Approve Button -->
                                                            <button type="button" onclick="confirmApprove('{{ route('exports.admin-approve', $export->id) }}', 'phiếu đề xuất xuất kho')"
                                                                    class="px-2.5 py-1 text-xs bg-green-500 hover:bg-green-600 text-white rounded font-bold shadow-sm flex items-center gap-1 transition-all">
                                                                <i class="fas fa-check text-[10px]"></i> Duyệt
                                                            </button>
                                                            
                                                            <!-- Reject Button -->
                                                            <button type="button" onclick="confirmReject('{{ route('exports.admin-reject', $export->id) }}', 'phiếu đề xuất xuất kho')"
                                                                    class="px-2.5 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded font-bold shadow-sm flex items-center gap-1 transition-all">
                                                                <i class="fas fa-times text-[10px]"></i> Từ chối
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span class="text-xs text-gray-400">Chờ duyệt</span>
                                                    @endif
                                                @elseif($export->status === 'pending')
                                                    @if($isAdmin || auth()->user()->hasRole('warehouse_manager') || auth()->user()->hasRole('warehouse_staff'))
                                                        <div class="flex items-center justify-center gap-1.5">
                                                            <!-- Approve Button -->
                                                            <button type="button" onclick="confirmApprove('{{ route('exports.approve', $export->id) }}', 'phiếu xuất kho')"
                                                                    class="px-2.5 py-1 text-xs bg-emerald-500 hover:bg-emerald-600 text-white rounded font-bold shadow-sm flex items-center gap-1 transition-all">
                                                                <i class="fas fa-check text-[10px]"></i> Duyệt xuất
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span class="text-xs text-gray-400">Chờ kho xuất hàng</span>
                                                    @endif
                                                @elseif($export->status === 'draft')
                                                    @if(auth()->user()->id === $export->employee_id || $isAdmin)
                                                        <form action="{{ route('exports.request-export', $export->id) }}" method="POST" class="inline" onsubmit="return confirm('Xác nhận gửi yêu cầu xuất kho này để Admin duyệt?')">
                                                            @csrf
                                                            <button type="submit" class="px-2.5 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded font-bold shadow-sm flex items-center gap-1 transition-all">
                                                                <i class="fas fa-paper-plane text-[10px]"></i> Gửi duyệt
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-xs text-gray-400">Bản nháp</span>
                                                    @endif
                                                @elseif($export->status === 'pending_invoice')
                                                    <span class="text-xs text-gray-400 font-semibold text-yellow-600">Chờ xuất HĐ</span>
                                                @elseif($export->status === 'completed')
                                                    <span class="text-xs text-gray-400 font-semibold text-green-600">Đã hoàn thành</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
    <!-- End Tabs Wrapper -->

    <!-- Modal Yêu cầu xuất kho -->
    <div id="exportModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden" onclick="if(event.target === this) closeExportModal()">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden animate-slide-up mx-4">
            <div class="px-6 py-4 bg-teal-600 text-white flex justify-between items-center">
                <h3 class="text-lg font-bold"><i class="fas fa-file-export mr-2"></i>Tạo yêu cầu xuất kho</h3>
                <button type="button" onclick="closeExportModal()" class="text-white hover:text-gray-200 font-bold text-xl">&times;</button>
            </div>
            <form action="{{ route('sales.exports.create', $sale->id) }}" method="POST" class="p-6">
                @csrf
                <!-- Warehouse Selector -->
                @php
                    $isProjectOrder = $sale->isProjectOrder();
                    $defaultExportWarehouseId = '';
                    if ($sale->items->every(fn($i) => stripos($i->product?->code ?? '', 'FC-') === 0 || stripos($i->product?->code ?? '', 'license') !== false)) {
                        $licenseWh = \App\Models\Warehouse::where('code', 'WH_LICENSE')->first();
                        if ($licenseWh) $defaultExportWarehouseId = $licenseWh->id;
                    } elseif ($isProjectOrder) {
                        $projectWh = \App\Models\Warehouse::where('code', 'WH_PROJECT')->first();
                        if ($projectWh) $defaultExportWarehouseId = $projectWh->id;
                    } else {
                        $runrateWh = \App\Models\Warehouse::where('code', 'WH_RUNRATE')->first();
                        if ($runrateWh) $defaultExportWarehouseId = $runrateWh->id;
                    }
                @endphp
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Chọn kho xuất hàng <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" id="export_warehouse_select" onchange="updateExportMaxQuantities()" required class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">-- Chọn Kho --</option>
                        @foreach(\App\Models\Warehouse::where('status', 'active')->get() as $wh)
                            <option value="{{ $wh->id }}" {{ $defaultExportWarehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->product_type }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Products Table -->
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Danh sách sản phẩm xuất kho</label>
                    <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="px-4 py-2.5">Sản phẩm</th>
                                    <th class="px-4 py-2.5 text-center">SL chưa xuất</th>
                                    <th class="px-4 py-2.5 text-center w-28">SL xuất đợt này</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-150">
                                @php $itemIndex = 0; @endphp
                                @foreach($sale->items as $item)
                                    @php
                                        $totalReceived = 0;
                                        $prItems = \App\Models\SaleOrderRequestItem::where('sale_item_id', $item->id)->get();
                                        foreach ($prItems as $prItem) {
                                            $totalReceived += $prItem->received_quantity_total;
                                        }

                                        $totalExported = \App\Models\ExportItem::whereHas('export', function ($q) use ($sale) {
                                                $q->where('reference_type', 'sale')
                                                  ->where('reference_id', $sale->id)
                                                  ->where('status', '!=', 'cancelled');
                                            })
                                            ->where('product_id', $item->product_id)
                                            ->sum('quantity');

                                        if (!$isProjectOrder) {
                                            // Retail/runrate: remaining to export is SO ordered quantity - already exported
                                            $remaining = max(0, $item->quantity - $totalExported);
                                        } else {
                                            // Project: remaining to export is limited to received PO quantity - already exported
                                            $remaining = max(0, $totalReceived - $totalExported);
                                        }
                                    @endphp
                                    @if($remaining > 0)
                                        <tr class="product-export-row" data-product-id="{{ $item->product_id }}" data-remaining-so="{{ $remaining }}">
                                            <td class="px-4 py-3 font-medium">
                                                {{ $item->product?->code ?? $item->product_name }}
                                                <div class="text-xs text-gray-500">{{ $item->product_name }}</div>
                                                <input type="hidden" name="items[{{ $itemIndex }}][product_id]" value="{{ $item->product_id }}">
                                            </td>
                                            <td class="px-4 py-3 text-center font-bold text-red-500">
                                                {{ number_format($remaining) }}
                                                @if(!$isProjectOrder)
                                                    <div class="text-[10px] text-gray-400 font-normal mt-0.5">
                                                        Đang giữ: <span class="held-qty-display font-bold text-teal-600">0</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="number" name="items[{{ $itemIndex }}][qty]" value="{{ $remaining }}" min="0" max="{{ $remaining }}" required class="export-qty-input w-full border border-gray-300 rounded text-center text-sm py-1 focus:ring-teal-500 focus:border-teal-500 font-semibold">
                                            </td>
                                        </tr>
                                        @php $itemIndex++; @endphp
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Ghi chú yêu cầu xuất kho</label>
                    <textarea name="note" rows="2" placeholder="Nhập ghi chú hoặc lý do xuất kho (nếu có)..." class="w-full border-gray-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500"></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3 border-t border-gray-150 pt-4">
                    <button type="button" onclick="closeExportModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm font-medium transition-colors text-gray-700">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 rounded-lg text-white text-sm font-bold shadow transition-colors"><i class="fas fa-paper-plane mr-1.5"></i>Gửi yêu cầu xuất kho</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $salespersonName = $sale->employee?->name ?? $sale->user?->name;
        $heldStockData = \App\Models\ProductItem::where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
            ->where('borrower', $salespersonName)
            ->select('product_id', 'warehouse_id', \Illuminate\Support\Facades\DB::raw('count(*) as qty'))
            ->groupBy('product_id', 'warehouse_id')
            ->get();
    @endphp
    <!-- JS script for Modal -->
    <script>
        // Held stock data for the salesperson (only relevant for retail SOs)
        window.saleType = @json($sale->type);
        window.isProjectOrder = @json($isProjectOrder);
        window.salesHeldStock = @json($heldStockData);

        function openExportModal() {
            document.getElementById('exportModal').classList.remove('hidden');
            updateExportMaxQuantities();
        }

        function closeExportModal() {
            document.getElementById('exportModal').classList.add('hidden');
        }

        function updateExportMaxQuantities() {
            if (window.isProjectOrder || window.saleType !== 'retail') return;

            var warehouseSelect = document.getElementById('export_warehouse_select');
            var selectedWarehouseId = warehouseSelect ? parseInt(warehouseSelect.value) : 0;
            var rows = document.querySelectorAll('.product-export-row');

            rows.forEach(function(row) {
                var productId = parseInt(row.dataset.productId);
                var remainingSo = parseInt(row.dataset.remainingSo);
                var heldQtyDisplay = row.querySelector('.held-qty-display');
                var qtyInput = row.querySelector('.export-qty-input');

                // Find held quantity for this product in the selected warehouse
                var heldQty = 0;
                if (selectedWarehouseId) {
                    for (var i = 0; i < window.salesHeldStock.length; i++) {
                        var stock = window.salesHeldStock[i];
                        if (parseInt(stock.product_id) === productId && parseInt(stock.warehouse_id) === selectedWarehouseId) {
                            heldQty = parseInt(stock.qty);
                            break;
                        }
                    }
                }

                // Update held quantity display
                if (heldQtyDisplay) {
                    heldQtyDisplay.textContent = heldQty;
                    heldQtyDisplay.style.color = heldQty > 0 ? '#0d9488' : '#ef4444';
                }

                // Calculate new max: min of SO remaining and held qty
                var newMax = Math.min(remainingSo, heldQty);

                if (qtyInput) {
                    qtyInput.max = newMax;
                    // Adjust current value if it exceeds new max
                    if (parseInt(qtyInput.value) > newMax) {
                        qtyInput.value = newMax;
                    }
                    // Visual feedback
                    if (newMax === 0) {
                        qtyInput.style.borderColor = '#ef4444';
                        qtyInput.style.backgroundColor = '#fef2f2';
                        qtyInput.value = 0;
                    } else {
                        qtyInput.style.borderColor = '#d1d5db';
                        qtyInput.style.backgroundColor = '#ffffff';
                    }
                }
            });
        }
    </script>




    {{-- Order Request Modal + History (removed per user request) --}}

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-auto max-h-[90vh] overflow-y-auto">
            <div class="p-6" x-data="paymentForm()">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ghi nhận thanh toán</h3>
                <form action="{{ route('sales.payment', $sale->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tiền tệ <span class="text-red-500">*</span></label>
                                <select name="currency_id" id="payment_currency_id" required onchange="handlePaymentCurrencyChange()"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}" data-code="{{ $currency->code }}" {{ $currency->id == ($sale->currency_id ?? $baseCurrencyId) ? 'selected' : '' }}>
                                            {{ $currency->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="payment_exchange_rate_container" class="{{ ($sale->currency_id ?? $baseCurrencyId) == $baseCurrencyId ? 'hidden' : '' }}">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tỷ giá</label>
                                <input type="number" name="exchange_rate" id="payment_exchange_rate" step="0.000001" value="{{ $sale->exchange_rate ?? 1 }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                        </div>

                        {{-- Thông tin công nợ --}}
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 shadow-sm">
                            <div class="flex justify-between items-center text-xs text-amber-700 uppercase font-bold tracking-wider mb-2">
                                <span>Tóm tắt công nợ</span>
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Tổng giá trị:</span>
                                    <span class="font-bold text-gray-900" x-text="formatMoney(orderTotal) + ' ' + (currentCurrencySymbol || 'đ')"></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Đã thanh toán:</span>
                                    <span class="font-bold text-green-600" x-text="formatMoney(paidAmount) + ' ' + (currentCurrencySymbol || 'đ')"></span>
                                </div>
                                <div class="border-t border-amber-200 my-1 pt-1 flex justify-between text-base">
                                    <span class="font-bold text-amber-800">CÒN NỢ:</span>
                                    <span class="font-black text-red-600" x-text="formatMoney(orderTotal - paidAmount) + ' ' + (currentCurrencySymbol || 'đ')"></span>
                                </div>
                                <div class="border-t border-dashed border-amber-200 my-2 pt-2 space-y-1 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Hạn thanh toán đơn hàng:</span>
                                        <span class="font-semibold text-gray-800">
                                            @if($sale->payment_due_date)
                                                @php
                                                    $isOverdue = $sale->payment_due_date->isPast() && $sale->payment_status !== 'paid';
                                                @endphp
                                                {{ $sale->payment_due_date->format('d/m/Y') }} @if($isOverdue) (Quá hạn) @endif
                                            @elseif($sale->invoice_date)
                                                @php
                                                    $debtDays = $sale->customer->debt_days ?? 0;
                                                    $dueDate = \Carbon\Carbon::parse($sale->invoice_date)->addDays($debtDays);
                                                    $isOverdue = $dueDate->isPast() && $sale->payment_status !== 'paid';
                                                @endphp
                                                {{ $dueDate->format('d/m/Y') }} @if($isOverdue) (Quá hạn) @endif
                                            @else
                                                Tính khi xuất hóa đơn
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Nội dung thanh toán --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung thanh toán</label>
                            <select name="payment_label" x-model="paymentLabel" @change="onLabelChange()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                                <option value="">-- Chọn --</option>
                                <template x-for="(ms, idx) in availableMilestones" :key="idx">
                                    <option :value="ms.label" x-text="ms.label + ' (' + ms.percent + '%)' + (ms.due_date ? ' - Hạn: ' + ms.due_date : '')" :data-percent="ms.percent"></option>
                                </template>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>

                        {{-- Kiểu nhập: % hoặc VND --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kiểu nhập <span class="text-red-500">*</span></label>
                            <div class="flex rounded-lg overflow-hidden border border-gray-300">
                                <button type="button" @click="inputMode = 'percent'"
                                    :class="inputMode === 'percent' ? 'bg-purple-600 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                                    class="flex-1 px-3 py-2 text-sm font-medium transition-colors">
                                    <i class="fas fa-percent mr-1"></i> %
                                </button>
                                <button type="button" @click="inputMode = 'fixed'"
                                    :class="inputMode === 'fixed' ? 'bg-purple-600 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                                    class="flex-1 px-3 py-2 text-sm font-medium transition-colors border-l border-gray-300">
                                    <i class="fas fa-money-bill mr-1"></i> Tiền
                                </button>
                            </div>
                        </div>

                        {{-- Nhập % --}}
                        <div x-show="inputMode === 'percent'" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phần trăm thanh toán <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" x-model="percentValue" @input="calcAmount()" min="0" max="100" step="0.1"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-purple-400 text-right text-lg font-semibold"
                                       placeholder="0">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">%</span>
                            </div>
                            <div class="mt-2 bg-purple-50 border border-purple-200 rounded-lg px-3 py-2">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-purple-700">Số tiền tương ứng:</span>
                                    <span class="font-bold text-purple-800 text-base" x-text="formatMoney(calculatedAmount) + ' đ'"></span>
                                </div>
                                <div class="text-[10px] text-purple-500 mt-1">
                                    = <span x-text="formatMoney(orderTotal)"></span> đ × <span x-text="percentValue || 0"></span>%
                                </div>
                            </div>
                        </div>

                        {{-- Nhập số tiền --}}
                        <div x-show="inputMode === 'fixed'" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền thanh toán <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="number" x-model="fixedAmount" @input="calcPercent()" min="0" step="0.01"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-primary text-right"
                                       placeholder="0">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" x-text="currentCurrencySymbol || '₫'"></span>
                            </div>
                            <div class="mt-1 flex justify-between items-center text-xs text-gray-500">
                                <span>Tương đương: <span class="font-medium text-purple-600" x-text="calculatedPercent + '%'"></span></span>
                            </div>
                        </div>

                        {{-- Hidden input gửi số tiền thực tế --}}
                        <input type="hidden" name="amount" :value="inputMode === 'percent' ? calculatedAmount : fixedAmount">
                        <input type="hidden" name="payment_percent" :value="inputMode === 'percent' ? percentValue : calculatedPercent">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngày thanh toán <span class="text-red-500">*</span></label>
                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phương thức <span class="text-red-500">*</span></label>
                            <select name="payment_method" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="credit_card">Thẻ tín dụng</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Chứng từ UNC (Minh chứng thanh toán) <span class="text-red-500">*</span></label>
                            <input type="file" name="proof_file" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
                            <textarea name="note" rows="2"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-6">
                        <button type="button" onclick="closePaymentModal()"
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                            Hủy
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                            Xác nhận
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Delivery Date Modal -->
    <div id="deliveryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-full mx-auto p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Cập nhật ngày giao hàng thành công</h3>
            <form action="{{ route('sales.updateDeliveryDate', $sale->id) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày giao hàng thành công <span class="text-red-500">*</span></label>
                        <input type="date" name="delivery_date" value="{{ $sale->delivery_date ? $sale->delivery_date->format('Y-m-d') : date('Y-m-d') }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button type="button" onclick="closeDeliveryModal()"
                            class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-sm font-medium">
                        Hủy
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-indigo-650 hover:bg-indigo-700 bg-indigo-600 text-white rounded-lg transition-colors text-sm font-medium">
                        Xác nhận
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(isset($linkedProject) && $linkedProject)
<!-- Sale Project Closure Modal -->
<div id="saleCloseProjectModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeSaleCloseProjectModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-500"></i> Xác nhận Đóng dự án (Closed Won)
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeSaleCloseProjectModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="saleCloseProjectForm" action="{{ route('projects.close', $linkedProject->id) }}" method="POST">
                @csrf
                <input type="hidden" name="close_status" value="closed_won">
                <input type="hidden" name="source_mode" value="sync_sale">
                <input type="hidden" name="sale_id" value="{{ $sale->id }}">

                <div class="mt-4 space-y-4">
                    <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Dự án:</span>
                            <span class="font-bold text-indigo-900" id="sale_close_modal_project_title">{{ $linkedProject->code }} - {{ $linkedProject->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Đơn hàng đồng bộ:</span>
                            <span class="font-bold text-gray-900">#{{ $sale->code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Ngày đơn hàng:</span>
                            <span class="font-medium text-gray-900">{{ $sale->date ? $sale->date->format('d/m/Y') : '-' }}</span>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-indigo-100">
                            <span class="font-bold text-gray-900">Tổng giá trị đơn:</span>
                            <span class="font-bold text-indigo-700 text-sm">{{ number_format($sale->total, 0, ',', '.') }} đ</span>
                        </div>
                    </div>

                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="sync_bom" value="1" checked class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="text-xs font-bold text-emerald-900 flex items-center gap-1">
                                    <i class="fas fa-sync-alt text-emerald-600"></i> Tự động đồng bộ BOM & Đơn giá bán thực tế
                                </span>
                                <p class="text-[11px] text-emerald-700 mt-0.5">Hệ thống sẽ cập nhật {{ $sale->items->count() }} mặt hàng và mức giá chốt vào hồ sơ dự án.</p>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Ghi chú đóng dự án (Tùy chọn)</label>
                        <textarea name="close_note" rows="2" class="block w-full rounded-lg border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nhập ghi chú thêm nếu có..."></textarea>
                    </div>
                </div>

                <div class="mt-5 sm:mt-6 flex justify-end gap-3">
                    <button type="button" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50" onclick="closeSaleCloseProjectModal()">Hủy</button>
                    <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700">
                        <i class="fas fa-check mr-1.5"></i> Xác nhận & Đồng bộ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function openDeliveryModal() {
    document.getElementById('deliveryModal').classList.remove('hidden');
}
function closeDeliveryModal() {
    document.getElementById('deliveryModal').classList.add('hidden');
}
</script>
<script>
function paymentForm() {
    const allMilestones = @json(
        ($sale->payment_terms && count($sale->payment_terms) > 0)
            ? $sale->payment_terms
            : (($sale->customer && $sale->customer->payment_terms && count($sale->customer->payment_terms) > 0)
                ? $sale->customer->payment_terms
                : [])
    );
    
    // Get already-paid labels from financial transactions
    @php
        $paidLabels = \App\Models\FinancialTransaction::where('reference_number', $sale->code)
            ->where('type', 'income')
            ->pluck('note')
            ->map(function($note) {
                // Extract label from note like "Cọc (30%)" or "Thanh toán đợt 1 (70%) - ghi chú"
                if (preg_match('/^(.+?)\s*\(\d/', $note, $m)) {
                    return trim($m[1]);
                }
                if (preg_match('/^(Cọc|Thanh toán.+?)(\s*-|$)/u', $note, $m)) {
                    return trim($m[1]);
                }
                return trim($note);
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    @endphp
    const paidLabels = @json($paidLabels);
    
    return {
        inputMode: 'percent',
        percentValue: '',
        fixedAmount: '',
        calculatedAmount: 0,
        calculatedPercent: '0.0',
        orderTotal: {{ $sale->total ?: 0 }},
        paidAmount: {{ $sale->paid_amount ?: 0 }},
        currentCurrencySymbol: 'đ',
        paymentLabel: '',
        selectedMilestoneIndex: '',
        milestones: allMilestones,
        paidLabels: paidLabels,
        orderDate: '{{ $sale->date ? \Carbon\Carbon::parse($sale->date)->format('Y-m-d') : '' }}',

        get availableMilestones() {
            const list = allMilestones.length === 0 ? [
                { label: 'Cọc', percent: 0, days: 0 },
                { label: 'Thanh toán đợt 1', percent: 0, days: 0 },
                { label: 'Thanh toán cuối', percent: 0, days: 0 },
                { label: 'Thanh toán toàn bộ', percent: 0, days: 0 },
            ] : allMilestones;

            return list.filter(ms => !this.paidLabels.includes(ms.label)).map(ms => {
                let dueDateStr = '';
                if (this.orderDate && typeof ms.days !== 'undefined') {
                    const d = new Date(this.orderDate);
                    d.setDate(d.getDate() + parseInt(ms.days));
                    const day = String(d.getDate()).padStart(2, '0');
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const year = d.getFullYear();
                    dueDateStr = `${day}/${month}/${year}`;
                }
                return {
                    ...ms,
                    due_date: dueDateStr
                };
            });
        },

        onLabelChange() {
            // Find the selected milestone and auto-fill percent
            const selected = allMilestones.find(ms => ms.label === this.paymentLabel);
            if (selected && selected.percent) {
                this.percentValue = selected.percent;
                this.inputMode = 'percent';
                this.calcAmount();
            }
        },

        selectMilestone() {
            if (this.selectedMilestoneIndex !== '') {
                const ms = this.milestones[this.selectedMilestoneIndex];
                this.percentValue = ms.percent;
                this.paymentLabel = ms.label;
                this.calcAmount();
            }
        },

        calcAmount() {
            const pct = parseFloat(this.percentValue) || 0;
            this.calculatedAmount = Math.round(this.orderTotal * pct / 100);
        },

        calcPercent() {
            const amt = parseFloat(this.fixedAmount) || 0;
            if (this.orderTotal > 0) {
                this.calculatedPercent = (amt / this.orderTotal * 100).toFixed(1);
            } else {
                this.calculatedPercent = '0.0';
            }
        },

        formatMoney(n) {
            return new Intl.NumberFormat('en-US').format(Math.round(n));
        }
    };
}

function openPaymentModal() {
    document.getElementById('paymentModal').classList.remove('hidden');
    handlePaymentCurrencyChange();
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function confirmSendEmail() {
    Swal.fire({
        title: 'Xác nhận gửi Email?',
        text: "Hệ thống sẽ gửi thông tin đơn hàng đến khách hàng qua email.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Đồng ý, gửi ngay!',
        cancelButtonText: 'Hủy bỏ',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Đang gửi email...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            document.getElementById('emailForm').submit();
        }
    });
}

function handlePaymentCurrencyChange() {
    const select = document.getElementById('payment_currency_id');
    const currencyId = select.value;
    const symbol = select.options[select.selectedIndex].text.split('(')[0].trim();
    const baseCurrencyId = @json($baseCurrencyId);
    const container = document.getElementById('payment_exchange_rate_container');
    const rateInput = document.getElementById('payment_exchange_rate');
    
    // Update Alpine.js data via dispatch if needed, or direct access
    const alpineData = document.querySelector('[x-data="paymentForm()"]');
    if (alpineData && alpineData.__x) {
        alpineData.__x.$data.currentCurrencySymbol = (currencyId == baseCurrencyId) ? 'đ' : '$';
        
        // If switching to USD, recalculate values based on exchange rate
        const updateValues = (rate) => {
            if (currencyId != baseCurrencyId) {
                alpineData.__x.$data.orderTotal = {{ $sale->total }} / rate;
                alpineData.__x.$data.paidAmount = {{ $sale->paid_amount }} / rate;
            } else {
                alpineData.__x.$data.orderTotal = {{ $sale->total }};
                alpineData.__x.$data.paidAmount = {{ $sale->paid_amount }};
            }
            alpineData.__x.$data.calcAmount();
            alpineData.__x.$data.calcPercent();
        };

        if (currencyId == baseCurrencyId) {
            container.classList.add('hidden');
            rateInput.value = 1;
            updateValues(1);
        } else {
            container.classList.remove('hidden');
            if (currencyId == @json($sale->currency_id ?? null)) {
                rateInput.value = @json($sale->exchange_rate ?? 1);
                updateValues(rateInput.value);
            } else {
                fetch(`/exchange-rates/latest/${currencyId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.rate) {
                            rateInput.value = data.rate;
                            updateValues(data.rate);
                        }
                    });
            }
        }
    }
}

// Close modal when clicking outside
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});
</script>

@php
    $orderRequestSuppliers = $suppliers->map(function($s) { 
        return ['id' => $s->id, 'name' => $s->name]; 
    })->values();
    $orderRequestItems = $sale->items->map(function($item) {
        return [
            'product_id' => $item->product_id,
            'part_number' => $item->product_name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'vendor_id' => $item->product ? $item->product->supplier_id : null,
        ];
    })->values();
@endphp
<script>
function initExpDatePicker(selectorOrElement) {
    if (typeof flatpickr !== 'undefined') {
        flatpickr(selectorOrElement, {
            dateFormat: "Y-m-d",
            allowInput: true,
            parseDate: function(datestr, format) {
                const matches = datestr.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (matches) {
                    return new Date(
                        parseInt(matches[1], 10),
                        parseInt(matches[2], 10) - 1,
                        parseInt(matches[3], 10)
                    );
                }
                const d = new Date(datestr);
                if (!isNaN(d.getTime())) {
                    return d;
                }
                return null;
            },
            formatDate: function(date, format, locale) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
        });
        
        const elements = (typeof selectorOrElement === 'string') 
            ? document.querySelectorAll(selectorOrElement) 
            : [selectorOrElement];
            
        elements.forEach(el => {
            if (el && !el.dataset.maskBound) {
                el.dataset.maskBound = 'true';
                
                let prevValue = el.value || '';
                
                el.addEventListener('input', function(e) {
                    const currentVal = this.value;
                    if (currentVal.length < prevValue.length) {
                        prevValue = currentVal;
                        return;
                    }
                    
                    let digits = currentVal.replace(/\D/g, '');
                    let formatted = '';
                    if (digits.length > 0) {
                        formatted += digits.substring(0, 4);
                        if (digits.length >= 4) {
                            formatted += '-';
                            formatted += digits.substring(4, 6);
                            if (digits.length >= 6) {
                                formatted += '-';
                                formatted += digits.substring(6, 8);
                            }
                        }
                    }
                    
                    this.value = formatted;
                    prevValue = formatted;
                });
                
                el.addEventListener('blur', function() {
                    prevValue = this.value;
                });
                
                el.addEventListener('change', function() {
                    prevValue = this.value;
                });
            }
        });
    }
}
window.initExpDatePicker = initExpDatePicker;

window.OR_VENDORS = @json(\App\Models\SaleOrderRequest::VENDORS);
window.OR_TYPES = @json(\App\Models\SaleOrderRequest::TYPES);
window.OR_SUPPLIERS = @json($orderRequestSuppliers);
window.OR_SALE_ITEMS = @json($orderRequestItems);

// === Edit PR (Need Info) functions ===
var editRowCounters = {};

function addEditRow(prId) {
    if (!editRowCounters[prId]) {
        editRowCounters[prId] = document.querySelectorAll('#editItemRows_' + prId + ' .edit-item-row').length;
    }
    const idx = editRowCounters[prId]++;
    const tbody = document.getElementById('editItemRows_' + prId);
    const tr = document.createElement('tr');
    tr.className = 'edit-item-row border-b border-gray-100 hover:bg-gray-50';
    tr.dataset.index = idx;

    let supplierOptions = '<option value="">-- Chọn --</option>';
    window.OR_SUPPLIERS.forEach(s => {
        supplierOptions += `<option value="${s.id}">${s.name}</option>`;
    });

    let typeOptions = '<option value="">-- Chọn --</option>';
    window.OR_TYPES.forEach(t => {
        typeOptions += `<option value="${t}">${t}</option>`;
    });

    tr.innerHTML = `
        <td class="px-1 py-1">
            <select name="order_request_items[${idx}][vendor_id]" required
                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
                ${supplierOptions}
            </select>
        </td>
        <td class="px-1 py-1">
            <select name="order_request_items[${idx}][type]" required
                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
                ${typeOptions}
            </select>
        </td>
        <td class="px-1 py-1">
            <input type="text" name="order_request_items[${idx}][part_number]" required placeholder="P/N"
                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
            <input type="hidden" name="order_request_items[${idx}][product_id]" value="">
            <input type="hidden" name="order_request_items[${idx}][sale_item_id]" value="">
        </td>
        <td class="px-1 py-1">
            <input type="number" name="order_request_items[${idx}][quantity]" required step="0.01" value="1"
                class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
        </td>
        <td class="px-1 py-1">
            <input type="text" name="order_request_items[${idx}][unit]" placeholder="Đơn vị"
                class="w-full border border-gray-300 rounded px-1 py-1.5 text-xs text-center focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
        </td>
        <td class="px-1 py-1">
            <input type="text" name="order_request_items[${idx}][serial_number]" placeholder="SN"
                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
        </td>
        <td class="px-1 py-1">
            <input type="text" name="order_request_items[${idx}][exp_date]" placeholder="YYYY-MM-DD"
                class="exp-date-picker w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400">
        </td>
        <td class="px-1 py-1">
            <input type="text" name="order_request_items[${idx}][si_name]" required placeholder="SI Name"
                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
        </td>
        <td class="px-1 py-1">
            <input type="text" name="order_request_items[${idx}][pos_id]" placeholder="POS ID"
                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
        </td>
        <td class="px-1 py-1">
            <input type="text" name="order_request_items[${idx}][eu_name]" required placeholder="EU Name"
                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
        </td>
        <td class="px-1 py-1">
            <input type="text" name="order_request_items[${idx}][mst]" required placeholder="MST"
                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
        </td>
        <td class="px-1 py-1">
            <input type="text" name="order_request_items[${idx}][address]" placeholder="Địa chỉ"
                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-gray-50">
        </td>
        <td class="px-1 py-1 text-center">
            <button type="button" onclick="removeEditRow(this, '${prId}')" class="text-red-400 hover:text-red-600">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    initExpDatePicker(tr.querySelector('.exp-date-picker'));
}

function removeEditRow(btn, prId) {
    const rows = document.querySelectorAll('#editItemRows_' + prId + ' .edit-item-row');
    if (rows.length > 1) {
        btn.closest('.edit-item-row').remove();
    } else {
        alert('Yêu cầu đặt hàng phải có ít nhất 1 sản phẩm.');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initExpDatePicker(".exp-date-picker");
});
</script>
<script src="{{ asset('js/order-request.js') }}"></script>
@if(isset($linkedProject) && $linkedProject)
<script>
function openSaleCloseProjectModal(projectId, projectCode, projectName, saleId, saleCode, saleTotal) {
    const modal = document.getElementById('saleCloseProjectModal');
    const form = document.getElementById('saleCloseProjectForm');
    const title = document.getElementById('sale_close_modal_project_title');
    if (form && projectId) {
        form.action = "{{ url('/projects') }}/" + projectId + "/close";
    }
    if (title && projectCode) {
        title.textContent = projectCode + " - " + projectName;
    }
    if (modal) modal.classList.remove('hidden');
}

function closeSaleCloseProjectModal() {
    const modal = document.getElementById('saleCloseProjectModal');
    if (modal) modal.classList.add('hidden');
}
</script>
@endif

<!-- ================= MODALS FOR QUICK ACTIONS ================= -->

<!-- 1. Modal Điều chỉnh BOM Sản phẩm -->
<div id="adjustBomModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] flex flex-col overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-indigo-50/80">
            <div>
                <h3 class="text-base font-bold text-indigo-900 flex items-center gap-2">
                    <i class="fas fa-cubes-stacked text-indigo-600"></i> Điều chỉnh BOM sản phẩm đơn hàng #{{ $sale->code }}
                </h3>
                <p class="text-xs text-indigo-700 mt-0.5">Thêm, sửa, xóa sản phẩm, điều chỉnh số lượng, đơn giá, VAT, bảo hành trực tiếp</p>
            </div>
            <button onclick="closeAdjustBomModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form action="{{ route('sales.updateBom', $sale->id) }}" method="POST" class="flex-1 flex flex-col overflow-hidden">
            @csrf
            <div class="p-5 overflow-y-auto flex-1 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Danh sách dòng BOM sản phẩm</span>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openBomStockPickerModal()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs flex items-center gap-1.5">
                            <i class="fas fa-boxes-stacked"></i> Chọn hàng sẵn kho / Hàng giữ
                        </button>
                        <button type="button" onclick="addBomRow()" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-xs font-bold flex items-center gap-1.5">
                            <i class="fas fa-plus"></i> Thêm dòng sản phẩm
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl">
                    <table class="w-full text-left border-collapse text-xs" id="adjustBomTable">
                        <thead class="bg-gray-50 text-gray-700 font-bold uppercase border-b border-gray-200">
                            <tr>
                                <th class="p-3">Sản phẩm / Part Number</th>
                                <th class="p-3 w-28 text-right">Số lượng</th>
                                <th class="p-3 w-36 text-right">Đơn giá ({{ $sale->currency->code ?? 'VND' }})</th>
                                <th class="p-3 w-24 text-center">VAT</th>
                                <th class="p-3 w-24 text-center">Bảo hành</th>
                                <th class="p-3 w-28 text-center">Loại nguồn</th>
                                <th class="p-3 w-12 text-center"><i class="fas fa-trash"></i></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white" id="adjustBomTableBody">
                            @foreach($sale->items as $idx => $item)
                                <tr class="bom-edit-row hover:bg-gray-50/60 transition-colors" data-idx="{{ $idx }}">
                                    <td class="p-2.5">
                                        <div class="searchable-select bom-product-searchable relative" data-index="{{ $idx }}" data-ajax-url="{{ route('api.products.search') }}">
                                            <input type="text" class="searchable-input w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 font-medium" 
                                                   placeholder="Gõ để tìm hoặc tạo sản phẩm mới..." autocomplete="off"
                                                   value="{{ $item->product ? ($item->product->code ? '[' . $item->product->code . '] ' . $item->product->name : $item->product->name) : ($item->product_name ?? '') }}">
                                            <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}" class="bom-product-id product-id-input">
                                            <input type="hidden" name="items[{{ $idx }}][product_name]" value="{{ $item->product_name ?: ($item->product->name ?? '') }}" class="product-name-input">
                                            <input type="hidden" name="items[{{ $idx }}][new_name]" class="new-name-input" value="{{ !$item->product_id ? ($item->product_name ?? '') : '' }}">
                                            <input type="hidden" name="items[{{ $idx }}][new_code]" class="new-code-input" value="{{ $item->product?->code ?? '' }}">
                                            <input type="hidden" name="items[{{ $idx }}][new_unit]" class="new-unit-input" value="{{ $item->product?->unit ?? 'Cái' }}">
                                            <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-xl text-left left-0 mt-1"></div>
                                        </div>
                                        @if($item->product)
                                            <div class="text-[10px] text-gray-400 font-mono mt-0.5">Mã: {{ $item->product->code }}</div>
                                        @endif
                                    </td>
                                    <td class="p-2.5">
                                        <input type="number" step="any" min="0.01" name="items[{{ $idx }}][quantity]" value="{{ $item->quantity }}" required
                                               class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs text-right focus:ring-1 focus:ring-indigo-500 font-bold">
                                    </td>
                                    <td class="p-2.5">
                                        <input type="text" name="items[{{ $idx }}][price]" value="{{ number_format($item->price, 0, ',', '.') }}" required
                                               class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs text-right focus:ring-1 focus:ring-indigo-500 font-bold price-format-input" onkeyup="formatCurrencyInput(this)">
                                    </td>
                                    <td class="p-2.5">
                                        <select name="items[{{ $idx }}][vat]" class="w-full border border-gray-300 rounded px-1.5 py-1.5 text-xs bg-white text-center">
                                            <option value="-1" {{ $item->vat == -1 ? 'selected' : '' }}>KCT</option>
                                            <option value="0" {{ $item->vat == 0 ? 'selected' : '' }}>0%</option>
                                            <option value="5" {{ $item->vat == 5 ? 'selected' : '' }}>5%</option>
                                            <option value="8" {{ $item->vat == 8 ? 'selected' : '' }}>8%</option>
                                            <option value="10" {{ $item->vat == 10 ? 'selected' : '' }}>10%</option>
                                        </select>
                                    </td>
                                    <td class="p-2.5">
                                        <input type="number" min="0" max="120" name="items[{{ $idx }}][warranty_months]" value="{{ $item->warranty_months ?? 12 }}"
                                               class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs text-center warranty-input">
                                    </td>
                                    <td class="p-2.5 text-center">
                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                            <input type="checkbox" name="items[{{ $idx }}][is_from_stock]" value="1" {{ $item->is_from_stock ? 'checked' : '' }}
                                                   class="h-3.5 w-3.5 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                            <span class="text-[10px] font-bold text-emerald-800">Sẵn kho</span>
                                        </label>
                                    </td>
                                    <td class="p-2.5 text-center">
                                        <button type="button" onclick="removeBomRow(this)" class="p-1 text-red-400 hover:text-red-600">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 bg-gray-50 flex justify-end items-center gap-3">
                <button type="button" onclick="closeAdjustBomModal()" class="px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 text-xs">HỦY BỎ</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 text-xs shadow-md">
                    <i class="fas fa-save mr-1.5"></i> LƯU ĐIỀU CHỈNH BOM
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Chọn Hàng Sẵn Kho cho BOM -->
<div id="bomStockPickerModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[85vh] flex flex-col overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-emerald-50">
            <h3 class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                <i class="fas fa-boxes-stacked text-emerald-600"></i> Chọn hàng có sẵn trong kho / Hàng đang giữ
            </h3>
            <button onclick="closeBomStockPickerModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <input type="text" id="bomStockSearchInput" onkeyup="searchBomAvailableStock(this.value)" placeholder="Tìm kiếm theo tên sản phẩm, mã part..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div class="p-4 overflow-y-auto flex-1 max-h-96" id="bomStockPickerList">
            <div class="text-center py-6 text-gray-400 text-xs">Đang tải danh sách hàng sẵn kho...</div>
        </div>
    </div>
</div>

<!-- 2. Modal Thêm điều khoản thanh toán -->
<div id="addMilestoneModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-primary/10">
            <h3 class="text-base font-bold text-primary flex items-center gap-2">
                <i class="fas fa-hand-holding-usd"></i> Thêm đợt / Điều khoản thanh toán
            </h3>
            <button onclick="closeAddMilestoneModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('sales.milestones.add', $sale->id) }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tên đợt thanh toán <span class="text-red-500">*</span></label>
                <input type="text" name="milestone_name" required list="milestone-names" placeholder="VD: Đợt 1: Tạm ứng 30% khi ký HĐ, Đặt cọc..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                <datalist id="milestone-names">
                    <option value="Đợt 1">
                    <option value="Đợt 2">
                    <option value="Đợt 3">
                    <option value="Đặt cọc (Deposit)">
                    <option value="Thanh toán trước xuất kho">
                    <option value="Thanh toán cuối (Final Payment)">
                </datalist>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tỷ lệ thanh toán (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="percentage" id="ms_percentage" placeholder="VD: 30"
                           oninput="recalcMsAmount(this.value)"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Số tiền ({{ $sale->currency->code ?? 'VND' }})</label>
                    <input type="text" name="amount" id="ms_amount" placeholder="VD: 50.000.000"
                           onkeyup="formatCurrencyInput(this)"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none font-bold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Thời điểm thanh toán</label>
                    <select name="timing" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none bg-white">
                        <option value="after_contract">Sau khi ký HĐMB</option>
                        <option value="after_delivery_notice">Sau thông báo giao hàng</option>
                        <option value="before_export">Trước khi xuất hàng</option>
                        <option value="after_delivery">Sau khi giao hàng</option>
                        <option value="after_invoice">Sau khi xuất hóa đơn</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Giai đoạn kiểm soát chặn</label>
                    <select name="blocking_stage" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none bg-white">
                        <option value="NONE">Không chặn (Linh hoạt)</option>
                        <option value="BLOCK_PO_SEND">Chặn Đặt hàng (PO)</option>
                        <option value="BLOCK_WAREHOUSE_EXPORT">Chặn Xuất kho</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Thời hạn (Số ngày)</label>
                    <input type="number" min="0" name="due_days" value="7"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Chứng từ bắt buộc</label>
                    <input type="text" name="required_docs" value="UNC" list="suggested_docs"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary outline-none">
                    <datalist id="suggested_docs">
                        <option value="UNC">
                        <option value="Biên bản nghiệm thu">
                        <option value="Hóa đơn VAT">
                        <option value="Bảo lãnh thanh toán">
                    </datalist>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeAddMilestoneModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 text-xs">HỦY</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white font-bold rounded-lg hover:bg-primary-hover text-xs shadow-md">THÊM ĐỢT THANH TOÁN</button>
            </div>
        </form>
    </div>
</div>



<script>
const saleTotalAmount = {{ (float)$sale->total }};
const saleSubtotalAmount = {{ (float)$sale->subtotal }};

function formatCurrencyInput(input) {
    let val = input.value.replace(/\D/g, '');
    if (val) {
        input.value = new Intl.NumberFormat('vi-VN').format(val);
    } else {
        input.value = '';
    }
}

// Adjust BOM Modal
function openAdjustBomModal() {
    document.getElementById('adjustBomModal').classList.remove('hidden');
    initBomSearchableSelects();
}

function closeAdjustBomModal() {
    document.getElementById('adjustBomModal').classList.add('hidden');
}

let bomRowCounter = {{ $sale->items->count() + 10 }};

function addBomRow(productData = null) {
    const tbody = document.getElementById('adjustBomTableBody');
    const idx = bomRowCounter++;
    const tr = document.createElement('tr');
    tr.className = 'bom-edit-row hover:bg-gray-50/60 transition-colors';
    tr.dataset.idx = idx;

    const pid = productData ? (productData.id || '') : '';
    const pcode = productData ? (productData.code || '') : '';
    const pname = productData ? (productData.name || '') : '';
    const displayText = productData ? ((pcode ? '[' + pcode + '] ' : '') + pname) : '';
    const pprice = productData ? (productData.price ? new Intl.NumberFormat('vi-VN').format(productData.price) : '0') : '';
    const pwarranty = productData ? (productData.warranty_months || 12) : 12;
    const isFromStock = productData ? (productData.is_from_stock ? 1 : 0) : 0;
    const punit = productData ? (productData.unit || 'Cái') : 'Cái';

    tr.innerHTML = `
        <td class="p-2.5">
            <div class="searchable-select bom-product-searchable relative" data-index="${idx}" data-ajax-url="{{ route('api.products.search') }}">
                <input type="text" class="searchable-input w-full border border-gray-300 rounded px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-indigo-500 font-medium" 
                       placeholder="Gõ để tìm hoặc tạo sản phẩm mới..." autocomplete="off"
                       value="${displayText}">
                <input type="hidden" name="items[${idx}][product_id]" value="${pid}" class="bom-product-id product-id-input">
                <input type="hidden" name="items[${idx}][product_name]" value="${pname}" class="product-name-input">
                <input type="hidden" name="items[${idx}][new_name]" class="new-name-input" value="${pname}">
                <input type="hidden" name="items[${idx}][new_code]" class="new-code-input" value="${pcode}">
                <input type="hidden" name="items[${idx}][new_unit]" class="new-unit-input" value="${punit}">
                <div class="searchable-dropdown hidden absolute z-50 w-full bg-white border border-gray-300 rounded-b-lg max-h-48 overflow-y-auto shadow-xl text-left left-0 mt-1"></div>
            </div>
        </td>
        <td class="p-2.5">
            <input type="number" step="any" min="0.01" name="items[${idx}][quantity]" value="1" required
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs text-right focus:ring-1 focus:ring-indigo-500 font-bold">
        </td>
        <td class="p-2.5">
            <input type="text" name="items[${idx}][price]" value="${pprice}" required
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs text-right focus:ring-1 focus:ring-indigo-500 font-bold price-format-input" onkeyup="formatCurrencyInput(this)">
        </td>
        <td class="p-2.5">
            <select name="items[${idx}][vat]" class="w-full border border-gray-300 rounded px-1.5 py-1.5 text-xs bg-white text-center">
                <option value="-1">KCT</option>
                <option value="0">0%</option>
                <option value="5">5%</option>
                <option value="8" selected>8%</option>
                <option value="10">10%</option>
            </select>
        </td>
        <td class="p-2.5">
            <input type="number" min="0" max="120" name="items[${idx}][warranty_months]" value="${pwarranty}"
                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs text-center warranty-input">
        </td>
        <td class="p-2.5 text-center">
            <label class="inline-flex items-center gap-1 cursor-pointer">
                <input type="checkbox" name="items[${idx}][is_from_stock]" value="1" ${isFromStock ? 'checked' : ''}
                       class="h-3.5 w-3.5 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                <span class="text-[10px] font-bold text-emerald-800">Sẵn kho</span>
            </label>
        </td>
        <td class="p-2.5 text-center">
            <button type="button" onclick="removeBomRow(this)" class="p-1 text-red-400 hover:text-red-600">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    initBomSearchableSelects();
}

function initBomSearchableSelects() {
    document.querySelectorAll('.bom-product-searchable').forEach(container => {
        if (container.dataset.initialized) return;
        container.dataset.initialized = 'true';

        const input = container.querySelector('.searchable-input');
        const productIdInput = container.querySelector('.product-id-input');
        const productNameInput = container.querySelector('.product-name-input');
        const newNameInput = container.querySelector('.new-name-input');
        const newCodeInput = container.querySelector('.new-code-input');
        const newUnitInput = container.querySelector('.new-unit-input');
        const dropdown = container.querySelector('.searchable-dropdown');
        const row = container.closest('.bom-edit-row');
        const priceInput = row ? row.querySelector('.price-format-input') : null;
        const warrantyInput = row ? row.querySelector('.warranty-input') : null;

        const ajaxUrl = container.dataset.ajaxUrl;
        let debounceTimer;

        document.addEventListener('click', (e) => {
            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        input.addEventListener('focus', () => {
            if (input.value.trim().length > 0) {
                fetchAjaxOptions(input.value.trim());
            }
        });

        input.addEventListener('input', (e) => {
            const query = e.target.value;
            if (productNameInput) productNameInput.value = query;
            if (newNameInput) newNameInput.value = query;
            if (newCodeInput) newCodeInput.value = query;

            clearTimeout(debounceTimer);
            if (query.trim().length === 0) {
                dropdown.innerHTML = '';
                dropdown.classList.add('hidden');
                if (productIdInput) productIdInput.value = '';
                return;
            }
            debounceTimer = setTimeout(() => {
                fetchAjaxOptions(query);
            }, 250);
        });

        async function fetchAjaxOptions(query) {
            dropdown.innerHTML = '<div class="px-3 py-2 text-gray-500 italic text-xs"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tìm kiếm...</div>';
            dropdown.classList.remove('hidden');

            try {
                const response = await fetch(`${ajaxUrl}?q=${encodeURIComponent(query)}`);
                const data = await response.json();
                renderOptions(data, query);
            } catch (error) {
                console.error('Search error:', error);
                dropdown.innerHTML = '<div class="px-3 py-2 text-red-500 text-xs">Lỗi khi tìm kiếm</div>';
            }
        }

        function renderOptions(data, query) {
            dropdown.innerHTML = '';

            data.forEach(item => {
                const opt = document.createElement('div');
                opt.className = 'searchable-option px-3 py-2 hover:bg-indigo-50 cursor-pointer border-b border-gray-50 text-xs transition-colors';
                const codeStr = item.code ? `[${item.code}] ` : '';
                const displayPrice = item.price ? new Intl.NumberFormat('vi-VN').format(item.price) + ' đ' : '';
                
                opt.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="font-bold text-gray-800">${codeStr}${item.name}</div>
                        ${displayPrice ? `<span class="text-[11px] font-semibold text-indigo-600">${displayPrice}</span>` : ''}
                    </div>
                    <div class="text-[10px] text-gray-400 font-mono">ĐVT: ${item.unit || 'Cái'} ${item.warranty_months ? `| BH: ${item.warranty_months}T` : ''}</div>
                `;

                opt.addEventListener('click', (e) => {
                    e.stopPropagation();
                    input.value = `${codeStr}${item.name}`;
                    if (productIdInput) productIdInput.value = item.id;
                    if (productNameInput) productNameInput.value = item.name;
                    if (newNameInput) newNameInput.value = '';
                    if (newCodeInput) newCodeInput.value = '';
                    if (newUnitInput) newUnitInput.value = item.unit || 'Cái';

                    if (priceInput && (!priceInput.value || priceInput.value === '0') && item.price) {
                        priceInput.value = new Intl.NumberFormat('vi-VN').format(item.price);
                    }
                    if (warrantyInput && item.warranty_months) {
                        warrantyInput.value = item.warranty_months;
                    }
                    dropdown.classList.add('hidden');
                });

                dropdown.appendChild(opt);
            });

            // Quick create option
            if (query.trim().length > 0) {
                const addOpt = document.createElement('div');
                addOpt.className = 'searchable-option px-3 py-2.5 hover:bg-emerald-50 text-emerald-700 font-bold border-t border-gray-200 cursor-pointer text-xs bg-emerald-50/50';
                addOpt.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5"><i class="fas fa-plus-circle text-emerald-600"></i> Tạo sản phẩm mới: "${query}"</span>
                        <span class="text-[10px] px-1.5 py-0.5 bg-emerald-100 text-emerald-800 rounded">Tạo mới</span>
                    </div>
                `;
                addOpt.addEventListener('click', (e) => {
                    e.stopPropagation();
                    input.value = query;
                    if (productIdInput) productIdInput.value = 'new';
                    if (productNameInput) productNameInput.value = query;
                    if (newNameInput) newNameInput.value = query;
                    if (newCodeInput) newCodeInput.value = query;
                    if (newUnitInput) newUnitInput.value = 'Cái';
                    dropdown.classList.add('hidden');
                });
                dropdown.appendChild(addOpt);
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initBomSearchableSelects();
});

function removeBomRow(btn) {
    const rows = document.querySelectorAll('#adjustBomTableBody .bom-edit-row');
    if (rows.length > 1) {
        btn.closest('.bom-edit-row').remove();
    } else {
        alert('Đơn hàng cần có ít nhất 1 dòng sản phẩm trong BOM.');
    }
}

// Available Stock picker for BOM
function openBomStockPickerModal() {
    document.getElementById('bomStockPickerModal').classList.remove('hidden');
    searchBomAvailableStock('');
}

function closeBomStockPickerModal() {
    document.getElementById('bomStockPickerModal').classList.add('hidden');
}

function searchBomAvailableStock(query) {
    const list = document.getElementById('bomStockPickerList');
    fetch(`/api/products/available-stock?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                list.innerHTML = '<div class="text-center py-6 text-gray-400 text-xs">Không tìm thấy sản phẩm sẵn kho phù hợp</div>';
                return;
            }
            list.innerHTML = data.map(item => `
                <div class="flex items-center justify-between p-3.5 hover:bg-emerald-50/60 rounded-xl border border-gray-100 mb-2 transition-colors">
                    <div>
                        <div class="font-bold text-xs text-gray-800">${item.name}</div>
                        <div class="text-[11px] text-gray-500 font-mono">Mã: ${item.code} | ĐVT: ${item.unit}</div>
                        <div class="text-[11px] text-emerald-700 font-semibold mt-0.5">
                            <i class="fas fa-warehouse mr-1"></i>Tồn khả dụng: <span class="font-bold">${item.in_stock_quantity}</span> (${item.warehouses_detail})
                        </div>
                        <div class="text-[10px] text-indigo-700 font-medium mt-0.5">
                            <i class="fas fa-info-circle mr-1"></i>${item.holding_summary}
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <div class="text-xs font-bold text-gray-900">${new Intl.NumberFormat('vi-VN').format(item.price)} đ</div>
                            <div class="text-[10px] text-gray-400">BH: ${item.warranty_months} tháng</div>
                        </div>
                        <button type="button" onclick="selectStockItemForBom(${JSON.stringify(item).replace(/"/g, '&quot;')})"
                                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-xs">
                            <i class="fas fa-check mr-1"></i> Chọn
                        </button>
                    </div>
                </div>
            `).join('');
        })
        .catch(() => {
            list.innerHTML = '<div class="text-center py-6 text-red-500 text-xs">Lỗi tải danh sách sản phẩm</div>';
        });
}

function selectStockItemForBom(item) {
    addBomRow(item);
    closeBomStockPickerModal();
}

// Add Milestone Modal
function openAddMilestoneModal() {
    document.getElementById('addMilestoneModal').classList.remove('hidden');
}

function closeAddMilestoneModal() {
    document.getElementById('addMilestoneModal').classList.add('hidden');
}

function recalcMsAmount(percent) {
    if (percent && saleTotalAmount > 0) {
        const amt = Math.round(saleTotalAmount * (parseFloat(percent) / 100));
        document.getElementById('ms_amount').value = new Intl.NumberFormat('vi-VN').format(amt);
    }
}

</script>
@endpush

