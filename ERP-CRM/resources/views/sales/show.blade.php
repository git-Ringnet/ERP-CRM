@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng')
@section('page-title', 'Chi tiết đơn hàng: ' . $sale->code)

@section('content')
<div class="space-y-4 overflow-auto">
    <!-- Actions -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ url()->previous() }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
        <a href="{{ route('sales.edit', $sale->id) }}" 
           class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
            <i class="fas fa-edit mr-2"></i> Sửa
        </a>
        @if($sale->dashboard_step >= 4)
        <a href="{{ route('sales.pdf', $sale->id) }}" target="_blank"
           class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
            <i class="fas fa-file-pdf mr-2"></i> Hóa đơn (In)
        </a>
        @endif
        <form action="{{ route('sales.email', $sale->id) }}" method="POST" class="inline" id="emailForm">
            @csrf
            <button type="button" onclick="confirmSendEmail()"
                    class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-envelope mr-2"></i> Gửi Email
            </button>
        </form>
        @if($sale->debt_amount > 0 && in_array($sale->status, ['shipping', 'completed']))
        <button onclick="openPaymentModal()" 
                class="inline-flex items-center px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
            <i class="fas fa-money-bill mr-2"></i> Ghi nhận thanh toán
        </button>
        @endif
        @if($sale->pl_status === 'approved')
        <a href="{{ route('sales.order-request.create', $sale->id) }}" 
                class="inline-flex items-center px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors">
            <i class="fas fa-cart-plus mr-2"></i> Yêu cầu đặt hàng
            @if($sale->orderRequests && $sale->orderRequests->count() > 0)
                <span class="ml-1.5 bg-white/30 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ $sale->orderRequests->count() }}</span>
            @endif
        </a>
        @endif
    </div>
    
    {{-- System Alert for Auto-created POs --}}
    @if($sale->purchaseOrders && $sale->purchaseOrders->count() > 0)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center">
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

    {{-- Workflow Progress Tracker --}}
    @php
        $pnlWorkflow = \App\Models\ApprovalWorkflow::getForDocumentType('sale_pnl');
        $pnlHistory = \App\Models\ApprovalHistory::where('document_type', 'sale_pnl')
            ->where('document_id', $sale->id)
            ->orderBy('created_at', 'desc')
            ->get();
        $latestReject = $pnlHistory->where('action', 'rejected')->first();
        $pendingHist = $pnlHistory->where('action', 'pending')->sortBy('level')->first();
    @endphp

    @if($pnlWorkflow || $sale->status !== 'cancelled')
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        {{-- P&L Workflow Header --}}
        <div class="px-5 py-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 {{ $sale->pl_status === 'approved' ? 'bg-green-50/50' : ($sale->pl_status === 'rejected' ? 'bg-red-50/50' : 'bg-blue-50/50') }}">
            <div class="flex items-center gap-4">
                <div class="flex flex-col">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Quy trình duyệt P&L</span>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $sale->pl_status_color }}">
                            {{ $sale->pl_status_label }}
                        </span>
                        <i class="fas fa-chevron-right text-gray-300 text-[10px]"></i>
                        <span class="text-sm text-gray-700">
                            @if($sale->pl_status === 'pending' && $pendingHist)
                                Đang chờ: <span class="font-bold text-blue-600">{{ $pendingHist->level_name }}</span>
                            @elseif($sale->pl_status === 'approved')
                                <span class="text-green-600 font-bold">Đã duyệt hoàn tất</span>
                            @elseif($sale->pl_status === 'rejected')
                                <span class="text-red-600 font-bold">Bị từ chối - Chờ Sales sửa</span>
                            @else
                                <span class="text-gray-400 italic">Bản nháp</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            @if($sale->pl_status === 'rejected' && $latestReject)
            <div class="flex-1 max-w-xl bg-white/60 p-2 rounded border border-red-100 flex items-start gap-2">
                <i class="fas fa-exclamation-circle text-red-500 mt-1"></i>
                <div class="text-xs">
                    <span class="font-bold text-red-700">Lý do từ chối:</span>
                    <span class="text-red-600">"{{ $latestReject->comment }}"</span>
                    <span class="text-[10px] text-red-400 ml-1">({{ $latestReject->approver_name }})</span>
                </div>
            </div>
            @endif
        </div>

        {{-- Order Status & Quick Actions --}}
        <div class="px-5 py-3 bg-white border-b border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mr-2">Trạng thái đơn:</span>
                <div class="flex items-center text-[10px] sm:text-[11px] overflow-x-auto pb-1 no-scrollbar">
                    @php
                        $currentStep = $sale->dashboard_step;
                        $steps = [
                            ['label' => 'Chờ duyệt', 'color' => 'yellow'],
                            ['label' => 'Đã duyệt', 'color' => 'blue'],
                            ['label' => 'Đã đặt hàng', 'color' => 'indigo'],
                            ['label' => 'Chờ hàng về', 'color' => 'purple'],
                            ['label' => 'Hàng về', 'color' => 'emerald'],
                            ['label' => 'Giao hàng', 'color' => 'amber'],
                            ['label' => 'Hoàn thành', 'color' => 'green'],
                        ];
                    @endphp

                    @foreach($steps as $index => $step)
                        <span class="px-2 py-1 rounded whitespace-nowrap {{ $currentStep === $index ? "bg-{$step['color']}-100 text-{$step['color']}-800 font-bold ring-1 ring-{$step['color']}-300" : ($currentStep > $index ? "text-{$step['color']}-600" : 'bg-gray-50 text-gray-400') }}">
                            {{ $step['label'] }}
                        </span>
                        @if(!$loop->last)
                            <i class="fas fa-chevron-right mx-1.5 {{ $currentStep > $index ? "text-{$step['color']}-300" : 'text-gray-200' }}"></i>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mr-2">Thao tác:</span>
                @if($sale->status === 'pending' && $sale->pl_status === 'approved')
                    <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded shadow-sm hover:bg-blue-700 transition-all">
                            <i class="fas fa-check mr-1"></i> DUYỆT ĐƠN
                        </button>
                    </form>
                @endif
                
                @if(in_array($sale->status, ['pending', 'approved']))
                    <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="button" 
                                onclick="confirmAction(this.closest('form'), 'Xác nhận hủy đơn?', 'Bạn có chắc chắn muốn hủy đơn hàng này không?', 'warning', 'Đồng ý hủy', '#d33')"
                                class="px-3 py-1 bg-white border border-red-200 text-red-600 text-xs font-bold rounded hover:bg-red-50 transition-all">
                            <i class="fas fa-times mr-1"></i> HỦY ĐƠN
                        </button>
                    </form>
                @endif

                @if($sale->status === 'approved')
                    @php 
                        $hasOfficialInvoice = $sale->invoiceRequests->where('status', 'official_issued')->isNotEmpty();
                    @endphp
                    @if($hasOfficialInvoice && $sale->isFullyReceived())
                        <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="shipping">
                            <button type="submit" class="px-3 py-1 bg-purple-600 text-white text-xs font-bold rounded shadow-sm hover:bg-purple-700 transition-all">
                                <i class="fas fa-truck mr-1"></i> GIAO HÀNG
                            </button>
                        </form>
                    @elseif($hasOfficialInvoice && !$sale->isFullyReceived())
                        <button type="button" disabled class="px-3 py-1 bg-gray-200 text-gray-400 text-xs font-bold rounded cursor-not-allowed opacity-60" title="Hàng chưa về đủ để giao">
                            <i class="fas fa-truck mr-1"></i> GIAO HÀNG
                        </button>
                        <span class="text-[10px] text-red-600 ml-1 italic"><i class="fas fa-exclamation-triangle"></i> Hàng chưa về đủ</span>
                    @else
                        <button type="button" disabled class="px-3 py-1 bg-gray-200 text-gray-400 text-xs font-bold rounded cursor-not-allowed opacity-60" title="Cần có hóa đơn chính thức để giao hàng">
                            <i class="fas fa-truck mr-1"></i> GIAO HÀNG
                        </button>
                        <span class="text-[10px] text-amber-600 ml-1 italic"><i class="fas fa-info-circle"></i> Chờ HĐ chính thức</span>
                    @endif
                @endif

                @if($sale->status === 'shipping')
                    <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="px-3 py-1 bg-green-600 text-white text-xs font-bold rounded shadow-sm hover:bg-green-700 transition-all">
                            <i class="fas fa-check-double mr-1"></i> HOÀN THÀNH
                        </button>
                    </form>
                @endif

                @if($sale->status === 'completed')
                    <span class="text-xs text-green-600 font-bold bg-green-50 px-2 py-1 rounded">
                        <i class="fas fa-check-circle mr-1"></i> ĐÃ HOÀN TẤT
                    </span>
                @endif
            </div>
        </div>

        {{-- Warehouse & Export Tracking --}}
        @php
            $linkedExport = \App\Models\Export::where('reference_type', 'sale')->where('reference_id', $sale->id)->first();
        @endphp
        <div class="px-5 py-2.5 bg-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mr-2">Kho & Giao nhận:</span>
                @if($linkedExport)
                    <div class="flex items-center gap-4">
                        <a href="{{ route('exports.show', $linkedExport->id) }}" class="text-sm font-bold text-blue-600 hover:underline">
                            <i class="fas fa-file-invoice mr-1"></i>{{ $linkedExport->code }}
                        </a>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $linkedExport->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $linkedExport->status === 'completed' ? 'Đã xuất kho (Trừ tồn)' : 'Chờ xuất kho' }}
                        </span>
                        @if($linkedExport->status !== 'completed' && auth()->user()->hasRole('admin'))
                            <span class="text-[10px] text-red-500 italic font-medium"><i class="fas fa-exclamation-triangle mr-1"></i>Vui lòng duyệt phiếu xuất để hoàn tất trừ tồn kho</span>
                        @endif
                    </div>
                @else
                    @if(in_array($sale->status, ['approved', 'shipping', 'completed']))
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-red-500 font-medium">
                                <i class="fas fa-times-circle mr-1"></i>Chưa có phiếu xuất kho
                            </span>
                            <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $sale->status }}">
                                <button type="submit" class="text-[10px] bg-red-50 text-red-600 border border-red-200 px-2 py-0.5 rounded hover:bg-red-100 transition-all font-bold">
                                    <i class="fas fa-sync-alt mr-1"></i>TẠO LẠI PHIẾU XUẤT
                                </button>
                            </form>
                        </div>
                    @else
                        <span class="text-xs text-gray-400 italic">Chờ duyệt đơn để tạo phiếu xuất</span>
                    @endif
                @endif
            </div>
            
            @if($linkedExport && $linkedExport->warehouse)
                <div class="text-[11px] text-gray-500">
                    <i class="fas fa-warehouse mr-1"></i>Kho: <span class="font-bold text-gray-700">{{ $linkedExport->warehouse->name }}</span>
                </div>
            @endif
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

    <!-- Sale Info -->
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                        <dt class="w-32 text-gray-500">Trạng thái:</dt>
                        <dd>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                                {{ $sale->status_label }}
                            </span>
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
            $displayTerms = ($sale->payment_terms && count($sale->payment_terms) > 0)
                ? $sale->payment_terms
                : (($sale->customer && $sale->customer->payment_terms && count($sale->customer->payment_terms) > 0)
                    ? $sale->customer->payment_terms
                    : []);
        @endphp
        @if(count($displayTerms) > 0)
        <div class="mt-8 pt-6 border-t border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-hand-holding-usd mr-2 text-primary"></i>
                Lộ trình thanh toán & Công nợ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($displayTerms as $ms)
                @php
                    $msDays = (int)($ms['days'] ?? 0);
                    $approvedDate = $sale->approved_at ?? $sale->created_at;
                    $dueDate = $approvedDate ? \Carbon\Carbon::parse($approvedDate)->addDays($msDays) : null;
                    $now = now();
                    $daysLeft = $dueDate ? $now->diffInDays($dueDate, false) : null;

                    // Determine status
                    if ($daysLeft === null) {
                        $deadlineStatus = null;
                    } elseif ($daysLeft < 0) {
                        $deadlineStatus = 'overdue';
                        $deadlineLabel = 'Quá hạn ' . abs((int)$daysLeft) . ' ngày';
                        $deadlineColor = 'bg-red-100 text-red-700 border-red-200';
                        $deadlineIcon = 'fas fa-exclamation-circle';
                    } elseif ($daysLeft <= 3) {
                        $deadlineStatus = 'due_soon';
                        $deadlineLabel = $daysLeft == 0 ? 'Tới hạn hôm nay' : 'Còn ' . (int)$daysLeft . ' ngày';
                        $deadlineColor = 'bg-yellow-100 text-yellow-700 border-yellow-200';
                        $deadlineIcon = 'fas fa-clock';
                    } else {
                        $deadlineStatus = 'normal';
                        $deadlineLabel = 'Còn ' . (int)$daysLeft . ' ngày';
                        $deadlineColor = 'bg-green-100 text-green-700 border-green-200';
                        $deadlineIcon = 'far fa-calendar-check';
                    }
                @endphp
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 {{ $deadlineStatus === 'overdue' ? 'ring-2 ring-red-300' : ($deadlineStatus === 'due_soon' ? 'ring-2 ring-yellow-300' : '') }}">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-sm font-bold text-gray-700">{{ $ms['label'] }}</span>
                        <span class="text-xs font-bold bg-primary/10 text-primary px-1.5 py-0.5 rounded">{{ $ms['percent'] }}%</span>
                    </div>
                    <div class="text-lg font-bold text-gray-900 mb-1">
                        {{ number_format(($sale->total * $ms['percent'] / 100)) }} <span class="text-xs font-normal">₫</span>
                    </div>
                    @if($dueDate)
                    <div class="text-[10px] text-gray-500 italic mb-1.5">
                        Hạn: {{ $dueDate->format('d/m/Y') }}
                    </div>
                    @if($sale->payment_status !== 'paid')
                    <div class="text-[10px] font-bold px-2 py-1 rounded border {{ $deadlineColor }} inline-flex items-center gap-1">
                        <i class="{{ $deadlineIcon }}"></i> {{ $deadlineLabel }}
                    </div>
                    @endif
                    @else
                    <div class="text-[10px] text-gray-500 italic">
                        Thời hạn: {{ $msDays }} ngày kể từ ngày duyệt
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
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
                    <i class="fas fa-shopping-cart mr-2"></i> Thông tin mua hàng (PO)
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
                $afterDiscountForeign = $subtotalForeign - $discountForeign;
                $vatForeign = round($afterDiscountForeign * ($sale->vat / 100), $decimals);
                $vatVnd = $isForeign ? round($vatForeign * $rate) : round(($subtotalVnd - $discountVnd) * ($sale->vat / 100));
                $totalForeign = $sale->total_foreign ?? ($isForeign ? round($afterDiscountForeign + $vatForeign, $decimals) : $sale->total);
                $totalVnd = $sale->total;
            @endphp
            
            <div class="bg-white rounded-b-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto overflow-y-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã sản phẩm</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SL</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá bán</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá vốn</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Bảo hành</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng vốn</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lợi nhuận</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($sale->items as $index => $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item->product->code ?? $item->product_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($item->quantity) }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if($isForeign)
                                        <div class="text-sm font-medium text-gray-900">{{ $symbol }} {{ number_format($item->price, $decimals) }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ number_format($item->price * $rate) }} đ</div>
                                    @else
                                        <div class="text-sm font-medium text-gray-900">{{ number_format($item->price) }} đ</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-orange-600 text-right">{{ number_format($item->cost_price) }} đ</td>
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
                                    @if($isForeign)
                                        <div class="text-sm font-medium text-gray-900">{{ $symbol }} {{ number_format($item->total, $decimals) }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ number_format($item->total * $rate) }} đ</div>
                                    @else
                                        <div class="text-sm font-medium text-gray-900">{{ number_format($item->total) }} đ</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-orange-600 text-right">{{ number_format($item->cost_total) }} đ</td>
                                <td class="px-4 py-3 text-sm text-right font-medium {{ $item->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($item->profit) }} đ
                                    <span class="text-xs">({{ number_format($item->profit_percent, 1) }}%)</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-white border-t-2 border-gray-100">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Tổng tiền hàng:</td>
                                <td colspan="3" class="px-4 py-3 text-right font-semibold">
                                    @if($isForeign)
                                        <div class="text-sm text-gray-900">{{ $symbol }} {{ number_format($subtotalForeign, $decimals) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($subtotalVnd) }} đ</div>
                                    @else
                                        <div class="text-sm text-gray-900">{{ number_format($subtotalVnd) }} đ</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">Chiết khấu ({{ $sale->discount }}%):</td>
                                <td colspan="3" class="px-4 py-3 text-right font-semibold">
                                    @if($isForeign)
                                        <div class="text-sm text-red-600">-{{ $symbol }} {{ number_format($discountForeign, $decimals) }}</div>
                                        <div class="text-xs text-red-400">-{{ number_format($discountVnd) }} đ</div>
                                    @else
                                        <div class="text-sm text-red-600">-{{ number_format($discountVnd) }} đ</div>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-sm font-medium text-gray-700 text-right">VAT ({{ $sale->vat }}%):</td>
                                <td colspan="3" class="px-4 py-3 text-right font-semibold">
                                    @if($isForeign)
                                        <div class="text-sm text-gray-900">{{ $symbol }} {{ number_format($vatForeign, $decimals) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($vatVnd) }} đ</div>
                                    @else
                                        <div class="text-sm text-gray-900">{{ number_format($vatVnd) }} đ</div>
                                    @endif
                                </td>
                            </tr>
                            <tr class="bg-blue-50">
                                <td colspan="6" class="px-4 py-3 text-base font-bold text-gray-900 text-right uppercase">Tổng cộng hồ sơ:</td>
                                <td colspan="3" class="px-4 py-3 text-right">
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
    </div>
    <!-- End Tabs Wrapper -->




    {{-- Order Request Modal + History --}}
    @include('sales.partials.order-request')

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-auto max-h-[90vh] overflow-y-auto">
            <div class="p-6" x-data="paymentForm()">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ghi nhận thanh toán</h3>
                <form action="{{ route('sales.payment', $sale->id) }}" method="POST">
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
                            </div>
                        </div>

                        {{-- Nội dung thanh toán --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nội dung thanh toán</label>
                            <select name="payment_label" x-model="paymentLabel" @change="onLabelChange()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                                <option value="">-- Chọn --</option>
                                <template x-for="(ms, idx) in availableMilestones" :key="idx">
                                    <option :value="ms.label" x-text="ms.label + ' (' + ms.percent + '%)'" :data-percent="ms.percent"></option>
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
</div>

@push('scripts')
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

        get availableMilestones() {
            if (allMilestones.length === 0) {
                // Fallback defaults
                return [
                    { label: 'Cọc', percent: 0, days: 0 },
                    { label: 'Thanh toán đợt 1', percent: 0, days: 0 },
                    { label: 'Thanh toán cuối', percent: 0, days: 0 },
                    { label: 'Thanh toán toàn bộ', percent: 0, days: 0 },
                ].filter(ms => !this.paidLabels.includes(ms.label));
            }
            return allMilestones.filter(ms => !this.paidLabels.includes(ms.label));
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
            return new Intl.NumberFormat('vi-VN').format(Math.round(n));
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
window.OR_VENDORS = @json(\App\Models\SaleOrderRequest::VENDORS);
window.OR_TYPES = @json(\App\Models\SaleOrderRequest::TYPES);
window.OR_SUPPLIERS = @json($orderRequestSuppliers);
window.OR_SALE_ITEMS = @json($orderRequestItems);
</script>
<script src="{{ asset('js/order-request.js') }}"></script>
@endpush
@endsection
