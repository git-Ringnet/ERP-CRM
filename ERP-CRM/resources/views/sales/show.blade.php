@extends('layouts.app')

@section('title', 'Chi tiết đơn hàng')
@section('page-title', 'Chi tiết đơn hàng: ' . $sale->code)

@section('content')
<div class="space-y-4 overflow-auto">
    <!-- Actions -->
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('sales.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại
        </a>
        <a href="{{ route('sales.edit', $sale->id) }}" 
           class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
            <i class="fas fa-edit mr-2"></i> Sửa
        </a>
        <a href="{{ route('sales.pdf', $sale->id) }}" target="_blank"
           class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
            <i class="fas fa-file-pdf mr-2"></i> Hóa đơn (In)
        </a>
        <form action="{{ route('sales.email', $sale->id) }}" method="POST" class="inline">
            @csrf
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-envelope mr-2"></i> Gửi Email
            </button>
        </form>
        @if($sale->debt_amount > 0)
        <button onclick="openPaymentModal()" 
                class="inline-flex items-center px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
            <i class="fas fa-money-bill mr-2"></i> Ghi nhận thanh toán
        </button>
        @endif
    </div>
    
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
                <div class="flex items-center text-[11px]">
                    <span class="px-2 py-1 rounded {{ $sale->status === 'pending' ? 'bg-yellow-100 text-yellow-800 font-bold' : 'bg-gray-100 text-gray-500' }}">Chờ duyệt</span>
                    <i class="fas fa-chevron-right mx-2 text-gray-200"></i>
                    <span class="px-2 py-1 rounded {{ $sale->status === 'approved' ? 'bg-blue-100 text-blue-800 font-bold' : 'bg-gray-100 text-gray-500' }}">Đã duyệt</span>
                    <i class="fas fa-chevron-right mx-2 text-gray-200"></i>
                    <span class="px-2 py-1 rounded {{ $sale->status === 'shipping' ? 'bg-purple-100 text-purple-800 font-bold' : 'bg-gray-100 text-gray-500' }}">Giao hàng</span>
                    <i class="fas fa-chevron-right mx-2 text-gray-200"></i>
                    <span class="px-2 py-1 rounded {{ $sale->status === 'completed' ? 'bg-green-100 text-green-800 font-bold' : 'bg-gray-100 text-gray-500' }}">Hoàn thành</span>
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
                    <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline" onsubmit="return confirm('Xác nhận hủy đơn hàng?')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="px-3 py-1 bg-white border border-red-200 text-red-600 text-xs font-bold rounded hover:bg-red-50 transition-all">
                            <i class="fas fa-times mr-1"></i> HỦY ĐƠN
                        </button>
                    </form>
                @endif

                @if($sale->status === 'approved')
                    <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="shipping">
                        <button type="submit" class="px-3 py-1 bg-purple-600 text-white text-xs font-bold rounded shadow-sm hover:bg-purple-700 transition-all">
                            <i class="fas fa-truck mr-1"></i> GIAO HÀNG
                        </button>
                    </form>
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
                        <dt class="w-32 text-gray-500">Nhân viên kinh doanh:</dt>
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
                <button @click="activeTab = 'margin'"
                    :class="activeTab === 'margin' ? 'border-green-600 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-all duration-200">
                    <i class="fas fa-chart-pie mr-2"></i> Tổng quan Margin
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
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
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item->product_name }}</td>
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

        <!-- Tab: Margin -->
        <div x-show="activeTab === 'margin'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95">
            @php
                $totalCostOfGoods = $sale->items->sum('cost_total');
                $grossMargin = $sale->total - $totalCostOfGoods;
                $grossMarginPercent = $sale->total > 0 ? ($grossMargin / $sale->total) * 100 : 0;
                $netMargin = $grossMargin - $sale->cost;
                $netMarginPercent = $sale->total > 0 ? ($netMargin / $sale->total) * 100 : 0;
            @endphp
            
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mt-4">
                <div class="p-4 border-b bg-gradient-to-r from-green-50 to-blue-50">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-chart-pie mr-2 text-green-600"></i>
                        Phân tích Margin theo đơn hàng
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Thể hiện giá bán, giá vốn và các chi phí liên quan</p>
                </div>
                
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Summary -->
                        <div class="space-y-4">
                            <h4 class="font-semibold text-gray-800 border-b pb-2">Tổng quan doanh thu & chi phí</h4>
                            
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-blue-700">Doanh thu (Giá bán)</span>
                                    <span class="text-lg font-bold text-blue-700">{{ number_format($sale->total) }} đ</span>
                                </div>
                            </div>
                            
                            <div class="bg-orange-50 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-orange-700">Giá vốn hàng bán (COGS)</span>
                                    <span class="text-lg font-bold text-orange-700">{{ number_format($totalCostOfGoods) }} đ</span>
                                </div>
                            </div>
                            
                            <div class="bg-red-50 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-red-700">Chi phí vận hành (OpEx)</span>
                                    <span class="text-lg font-bold text-red-700">{{ number_format($sale->cost) }} đ</span>
                                </div>
                                <div class="mt-2 text-xs space-y-1">
                                    @foreach($sale->expenses as $expense)
                                    <div class="flex justify-between text-red-600">
                                        <span>{{ $expense->type_label }}: {{ $expense->description }}</span>
                                        <span>{{ number_format($expense->amount) }} đ</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Summary -->
                        <div class="space-y-4">
                            <h4 class="font-semibold text-gray-800 border-b pb-2">Kết quả kinh doanh</h4>
                            
                            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-yellow-700">Lợi nhuận gộp (Gross Profit)</span>
                                    <span class="text-lg font-bold text-yellow-700">{{ number_format($grossMargin) }} đ</span>
                                </div>
                                <div class="mt-2 text-xs text-yellow-600">Tỷ lệ: {{ number_format($grossMarginPercent, 1) }}%</div>
                            </div>
                            
                            <div class="rounded-lg p-6 border-2 {{ $netMargin >= 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                                <div class="text-center">
                                    <div class="text-sm font-bold uppercase {{ $netMargin >= 0 ? 'text-green-800' : 'text-red-800' }}">
                                        Lợi nhuận ròng (Net Profit)
                                    </div>
                                    <div class="text-3xl font-extrabold mt-2 {{ $netMargin >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                        {{ number_format($netMargin) }} đ
                                    </div>
                                    <div class="mt-2 text-sm font-bold {{ $netMargin >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        Margin ròng: {{ number_format($netMarginPercent, 1) }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        </div>
    </div>
    <!-- End Tabs Wrapper -->

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
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

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền thanh toán <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" id="payment_amount" required min="0" step="0.01"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                            <p class="text-xs text-gray-500 mt-1">Còn nợ: <span id="payment_debt_display">{{ number_format($sale->debt_amount) }}</span> đ</p>
                        </div>
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
function openPaymentModal() {
    document.getElementById('paymentModal').classList.remove('hidden');
    handlePaymentCurrencyChange();
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function handlePaymentCurrencyChange() {
    const select = document.getElementById('payment_currency_id');
    const currencyId = select.value;
    const baseCurrencyId = @json($baseCurrencyId);
    const container = document.getElementById('payment_exchange_rate_container');
    const rateInput = document.getElementById('payment_exchange_rate');
    
    if (currencyId == baseCurrencyId) {
        container.classList.add('hidden');
        rateInput.value = 1;
    } else {
        container.classList.remove('hidden');
        // If it matches the sale currency, use sale rate, otherwise fetch latest
        if (currencyId == @json($sale->currency_id ?? null)) {
            rateInput.value = @json($sale->exchange_rate ?? 1);
        } else {
            // Fetch current rate
            fetch(`/exchange-rates/latest/${currencyId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.rate) rateInput.value = data.rate;
                });
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
@endpush
@endsection
