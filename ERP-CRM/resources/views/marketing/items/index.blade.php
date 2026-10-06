@extends('layouts.app')
@section('title', 'Kho vật phẩm & Quà tặng Marketing')
@section('page-title', 'Quản lý Kho vật phẩm & Quà tặng Marketing')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>

<div class="space-y-6" x-data="marketingItemsManager({ 
    allItems: {{ Js::from($allItems) }},
    opportunities: {{ Js::from($opportunities) }},
    marketingEvents: {{ Js::from($marketingEvents) }},
    supplierFunds: {{ Js::from($supplierFunds ?? []) }}
})">

    {{-- Tabs Navigation --}}
    <div class="bg-white rounded-xl shadow-sm p-2 flex border border-gray-100 flex-wrap gap-1">
        <a href="{{ route('marketing-events.index', ['tab' => 'events']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all text-gray-500 hover:text-purple-600 hover:bg-gray-50">
            <i class="fas fa-calendar-alt text-base"></i> Sự kiện Marketing
        </a>
        <a href="{{ route('marketing-events.index', ['tab' => 'funds']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all text-gray-500 hover:text-purple-600 hover:bg-gray-50">
            <i class="fas fa-wallet text-base"></i> Quỹ Hãng & Công nợ
        </a>
        <a href="{{ route('marketing-events.index', ['tab' => 'requests']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all text-gray-500 hover:text-purple-600 hover:bg-gray-50">
            <i class="fas fa-ticket-alt text-base"></i> Ticket từ Sales
        </a>
        <a href="{{ route('marketing-items.index') }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all bg-purple-50 text-purple-700">
            <i class="fas fa-boxes text-base"></i> Kho vật phẩm & Quà tặng
        </a>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tổng loại vật phẩm</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalTypes) }}</h3>
                <span class="text-xs text-purple-600 font-medium">Danh mục quản lý</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                <i class="fas fa-gift"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tổng số lượng tồn</p>
                <h3 class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($totalQuantity) }}</h3>
                <span class="text-xs text-gray-400">Vật phẩm trong kho</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fas fa-cubes"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Giá trị tồn kho ước tính</p>
                <h3 class="text-2xl font-bold text-green-600 mt-1">{{ number_format($totalValue, 0, ',', '.') }} đ</h3>
                <span class="text-xs text-gray-400">Theo đơn giá định mức</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center text-xl">
                <i class="fas fa-coins"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Cảnh báo sắp hết</p>
                <h3 class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">{{ number_format($lowStockCount) }}</h3>
                <span class="text-xs {{ $lowStockCount > 0 ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                    {{ $lowStockCount > 0 ? 'Cần nhập bổ sung' : 'Tồn kho an toàn' }}
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl {{ $lowStockCount > 0 ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-400' }} flex items-center justify-center text-xl">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Chờ BOD duyệt</p>
                <h3 class="text-2xl font-bold {{ $pendingApprovalCount > 0 ? 'text-amber-600' : 'text-gray-800' }} mt-1">{{ number_format($pendingApprovalCount) }}</h3>
                <span class="text-xs {{ $pendingApprovalCount > 0 ? 'text-amber-500 font-semibold' : 'text-gray-400' }}">
                    {{ $pendingApprovalCount > 0 ? 'Cần BOD phê duyệt' : 'Đã duyệt hết' }}
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl {{ $pendingApprovalCount > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-400' }} flex items-center justify-center text-xl">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>

    {{-- Main Content Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Header & Actions --}}
        <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-warehouse text-purple-600"></i> Danh mục Vật phẩm & Tồn kho
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Theo dõi số lượng quà tặng, ấn phẩm và lịch sử xuất cho Cơ hội / Sự kiện</p>
            </div>
            
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" @click="openCreateItem()"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-plus"></i> Đề xuất vật phẩm mới
                </button>
                <button type="button" @click="showImportFileModal = true"
                    class="px-3.5 py-2 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5">
                    <i class="fas fa-file-excel"></i> Import quà tặng (Excel)
                </button>
                <a href="{{ route('marketing-items.download-template') }}"
                   class="px-3 py-2 bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5"
                   title="Tải file mẫu Excel (.xlsx) chuẩn để nhập dữ liệu">
                    <i class="fas fa-file-excel text-xs text-emerald-600"></i> File mẫu (.xlsx)
                </a>
                <button type="button" @click="showImportModal = true; selectedItem = null; importForm.marketing_item_id = ''"
                    class="px-3.5 py-2 bg-green-50 text-green-700 hover:bg-green-100 border border-green-200 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5">
                    <i class="fas fa-arrow-down"></i> Nhập kho
                </button>
                <button type="button" @click="showExportModal = true; selectedItem = null; exportForm.marketing_item_id = ''"
                    class="px-3.5 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5">
                    <i class="fas fa-arrow-up"></i> Xuất quà tặng
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="p-4 bg-gray-50/70 border-b border-gray-100">
            <form method="GET" action="{{ route('marketing-items.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm mã, tên, mô tả vật phẩm..."
                        class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2 bg-white">
                </div>
                <div>
                    <select name="category" class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2 bg-white" onchange="this.form.submit()">
                        <option value="">-- Tất cả phân loại --</option>
                        @foreach($categories as $key => $name)
                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="stock_status" class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2 bg-white" onchange="this.form.submit()">
                        <option value="">-- Trạng thái tồn kho --</option>
                        <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>⚠️ Sắp hết hàng (Dưới ngưỡng)</option>
                        <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>🛑 Hết hàng (= 0)</option>
                    </select>
                </div>
                <div>
                    <select name="approval_status" class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2 bg-white" onchange="this.form.submit()">
                        <option value="">-- Trạng thái duyệt BOD --</option>
                        <option value="pending" {{ request('approval_status') === 'pending' ? 'selected' : '' }}>⏳ Chờ BOD duyệt</option>
                        <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>✅ Đã duyệt</option>
                        <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>❌ Bị từ chối</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-xs font-semibold hover:bg-gray-700 transition-colors flex-1">
                        <i class="fas fa-filter mr-1"></i> Lọc
                    </button>
                    @if(request()->anyFilled(['search', 'category', 'stock_status', 'approval_status']))
                        <a href="{{ route('marketing-items.index') }}" class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold hover:bg-gray-300 transition-colors flex items-center justify-center">
                            <i class="fas fa-redo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs font-bold uppercase text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3.5">Mã & Tên vật phẩm</th>
                        <th class="px-4 py-3.5">Phân loại</th>
                        <th class="px-4 py-3.5 text-center">ĐVT</th>
                        <th class="px-4 py-3.5 text-right">Tồn kho</th>
                        <th class="px-4 py-3.5 text-right">Đơn giá ước tính</th>
                        <th class="px-4 py-3.5 text-right">Tổng giá trị</th>
                        <th class="px-4 py-3.5 text-center">Trạng thái kho</th>
                        <th class="px-4 py-3.5 text-center">Duyệt BOD</th>
                        <th class="px-4 py-3.5 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($items as $item)
                        <tr class="hover:bg-purple-50/20 transition-colors {{ $item->is_low_stock ? 'bg-red-50/20' : '' }}">
                            <td class="px-4 py-3.5">
                                <div class="flex items-start gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0 font-bold text-sm border border-purple-100 mt-0.5">
                                        <i class="fas fa-gift"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 cursor-pointer hover:text-purple-600" @click="openProposalDetail({{ Js::from($item) }})">
                                            {{ $item->name }}
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                            <span class="text-xs font-mono font-semibold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded">{{ $item->code }}</span>
                                            @if($item->funding_source)
                                                <span class="text-3xs font-semibold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100" title="Nguồn kinh phí">
                                                    <i class="fas fa-coins text-3xs mr-0.5"></i> {{ $item->funding_source_label }}
                                                    @if($item->fund)
                                                        ({{ $item->fund->supplier->name ?? $item->fund->fund_name }})
                                                    @endif
                                                </span>
                                            @endif
                                            @if($item->event)
                                                <span class="text-3xs font-semibold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100" title="Sự kiện: {{ $item->event->title }}">
                                                    <i class="fas fa-calendar-alt text-3xs mr-0.5"></i> {{ $item->event->code }}
                                                </span>
                                            @endif
                                            @if($item->description)
                                                <span class="text-xs text-gray-400 truncate max-w-xs" title="{{ $item->description }}">{{ $item->description }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">
                                    {{ $item->category_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center text-xs font-medium text-gray-600">
                                {{ $item->unit }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="font-bold text-base {{ $item->stock_quantity <= 0 ? 'text-red-600' : ($item->is_low_stock ? 'text-amber-600' : 'text-gray-900') }}">
                                    {{ number_format($item->stock_quantity) }}
                                </div>
                                <div class="text-2xs text-gray-400">
                                    Ngưỡng: {{ $item->min_stock_alert }} {{ $item->unit }}
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-medium text-gray-600">
                                {{ number_format($item->unit_cost, 0, ',', '.') }} đ
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-gray-800">
                                {{ number_format($item->stock_quantity * $item->unit_cost, 0, ',', '.') }} đ
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if($item->stock_quantity <= 0)
                                    <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700 border border-red-200">
                                        Hết hàng
                                    </span>
                                @elseif($item->is_low_stock)
                                    <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                                        Sắp hết
                                    </span>
                                @else
                                    <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700 border border-green-200">
                                        Còn hàng
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if(($item->approval_status ?? 'approved') === 'pending')
                                    <div class="space-y-1">
                                        <span class="inline-block text-2xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                                            Chờ BOD duyệt
                                        </span>
                                        <button type="button" @click="openProposalDetail({{ Js::from($item) }})" class="text-3xs text-purple-600 hover:text-purple-800 underline block mx-auto">
                                            <i class="fas fa-eye"></i> Xem đề xuất
                                        </button>
                                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('director') || auth()->user()->hasRole('admin'))
                                            <div class="flex items-center justify-center gap-1 mt-1">
                                                <form action="{{ route('marketing-items.approve', $item->id) }}" method="POST" class="inline m-0" onsubmit="return confirm('BOD phê duyệt vật phẩm này vào kho chính thức?')">
                                                    @csrf
                                                    <button type="submit" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-2xs font-bold transition-all shadow-xs" title="Duyệt">
                                                        <i class="fas fa-check"></i> Duyệt
                                                    </button>
                                                </form>
                                                <button type="button" @click="openRejectModal({{ Js::from($item) }})" class="px-2 py-0.5 bg-red-100 hover:bg-red-200 text-red-700 rounded text-2xs font-bold transition-all" title="Từ chối">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @elseif(($item->approval_status ?? 'approved') === 'rejected')
                                    <div>
                                        <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700 border border-red-200 block" title="{{ $item->rejection_reason }}">
                                            Từ chối
                                        </span>
                                        <button type="button" @click="openProposalDetail({{ Js::from($item) }})" class="text-3xs text-gray-500 hover:text-purple-600 underline block mx-auto mt-0.5">
                                            Chi tiết
                                        </button>
                                        @if($item->rejection_reason)
                                            <span class="text-3xs text-red-500 italic block mt-0.5 truncate max-w-[100px]" title="{{ $item->rejection_reason }}">"{{ $item->rejection_reason }}"</span>
                                        @endif
                                    </div>
                                @else
                                    <div>
                                        <span class="text-2xs font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Đã duyệt
                                        </span>
                                        <button type="button" @click="openProposalDetail({{ Js::from($item) }})" class="text-3xs text-gray-400 hover:text-purple-600 underline block mx-auto mt-0.5">
                                            Chi tiết
                                        </button>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="openProposalDetail({{ Js::from($item) }})" title="Xem chi tiết đề xuất"
                                        class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 flex items-center justify-center text-xs transition-colors">
                                        <i class="fas fa-file-invoice"></i>
                                    </button>
                                    <button type="button" @click="openImport({{ json_encode($item) }})" title="Nhập thêm hàng"
                                        class="w-7 h-7 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 flex items-center justify-center text-xs transition-colors">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" @click="openExport({{ json_encode($item) }})" title="Xuất quà tặng"
                                        class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center text-xs transition-colors"
                                        {{ $item->stock_quantity <= 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-share"></i>
                                    </button>
                                    <a href="{{ route('marketing-items.transactions', $item->id) }}" title="Lịch sử xuất nhập"
                                        class="w-7 h-7 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 flex items-center justify-center text-xs transition-colors">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <button type="button" @click="openEdit({{ json_encode($item) }})" title="Sửa thông tin"
                                        class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center text-xs transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @if($item->stock_quantity <= 0)
                                        <form action="{{ route('marketing-items.destroy', $item->id) }}" method="POST" class="inline m-0" onsubmit="return confirm('Bạn có chắc muốn xóa vật phẩm này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Xóa" class="w-7 h-7 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center text-xs transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-10 text-gray-400">
                                <i class="fas fa-box-open text-4xl mb-2 text-gray-300"></i>
                                <p class="text-sm">Chưa có vật phẩm nào trong kho Marketing.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    {{-- Giao dịch gần đây --}}
    @if($recentTransactions->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-history text-purple-600"></i> Nhật ký Xuất - Nhập kho gần đây
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 uppercase text-gray-500 font-bold border-b border-gray-100">
                        <tr>
                            <th class="px-3 py-2">Thời gian</th>
                            <th class="px-3 py-2">Loại GD</th>
                            <th class="px-3 py-2">Vật phẩm</th>
                            <th class="px-3 py-2 text-right">Số lượng</th>
                            <th class="px-3 py-2 text-right">Tồn sau GD</th>
                            <th class="px-3 py-2">Mục đích / Liên kết</th>
                            <th class="px-3 py-2">Người thực hiện</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentTransactions as $tx)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-gray-500 font-mono">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-0.5 rounded-full font-bold {{ $tx->type === 'import' ? 'bg-green-50 text-green-700' : 'bg-blue-50 text-blue-700' }}">
                                        {{ $tx->type_label }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-semibold text-gray-800">{{ $tx->marketingItem?->name }}</td>
                                <td class="px-3 py-2 text-right font-bold {{ $tx->type === 'import' ? 'text-green-600' : 'text-blue-600' }}">
                                    {{ $tx->type === 'import' ? '+' : '-' }}{{ number_format($tx->quantity) }} {{ $tx->marketingItem?->unit }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-gray-600">{{ number_format($tx->remaining_stock) }}</td>
                                <td class="px-3 py-2">
                                    @if($tx->opportunity)
                                        <a href="{{ route('opportunities.show', $tx->opportunity_id) }}" class="text-purple-600 font-semibold hover:underline">
                                            <i class="fas fa-bullseye mr-1"></i>Cơ hội: {{ $tx->opportunity->name }}
                                        </a>
                                    @elseif($tx->marketingEvent)
                                        <span class="text-blue-600 font-semibold">
                                            <i class="fas fa-calendar mr-1"></i>Sự kiện: {{ $tx->marketingEvent->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-500">{{ $tx->note ?: 'N/A' }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-600">{{ $tx->creator?->name ?: 'Hệ thống' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- MODAL THÊM / ĐỀ XUẤT / SỬA VẬT PHẨM --}}
    <div x-show="showAddItemModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[92vh] overflow-y-auto" @click.away="showAddItemModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                        <i class="fas" :class="editItem ? 'fa-edit' : 'fa-lightbulb'"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-800" x-text="editItem ? 'Chỉnh sửa vật phẩm Marketing' : 'Đề xuất vật phẩm Marketing mới'"></h3>
                        <p class="text-2xs text-gray-500" x-text="editItem ? 'Cập nhật định mức và thông tin vật phẩm' : 'Đề xuất thêm danh mục quà tặng / ấn phẩm & dự trù kinh phí'"></p>
                    </div>
                </div>
                <button type="button" @click="showAddItemModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-base"></i></button>
            </div>

            <form :action="editItem ? ('/marketing-items/' + editItem.id) : '{{ route('marketing-items.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="editItem">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                @if(!auth()->user()->hasRole('super_admin') && !auth()->user()->hasRole('director'))
                <div x-show="!editItem" class="p-3 bg-amber-50/80 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2.5">
                    <i class="fas fa-user-shield text-amber-600 mt-0.5 shrink-0 text-sm"></i>
                    <div>
                        <strong>Quy trình phê duyệt Ban Giám đốc (BOD):</strong> 
                        <p class="mt-0.5 text-2xs text-amber-800 leading-relaxed">
                            Đề xuất vật phẩm mới sẽ được chuyển tới Ban Giám đốc xem xét & phê duyệt nguồn kinh phí dự toán. Sau khi được duyệt, tồn kho ban đầu sẽ chính thức được tạo và sẵn sàng cấp phát.
                        </p>
                    </div>
                </div>
                @endif

                {{-- Row 1: Name & Status --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tên vật phẩm <span class="text-red-500">*</span></label>
                        <input type="text" name="name" x-model="proposalForm.name" required placeholder="Ví dụ: Sổ tay da A5 dập logo, Áo mưa dù cao cấp..."
                            class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                    </div>
                    <div x-show="editItem">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Trạng thái danh mục</label>
                        <select name="status" x-model="proposalForm.status" class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                            <option value="active">Đang áp dụng</option>
                            <option value="inactive">Ngừng sử dụng</option>
                        </select>
                    </div>
                </div>

                {{-- Row 2: Category & Unit --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Phân loại danh mục <span class="text-red-500">*</span></label>
                        <select name="category" x-model="proposalForm.category" required class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                            @foreach($categories as $key => $name)
                                <option value="{{ $key }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Đơn vị tính <span class="text-red-500">*</span></label>
                        <input type="text" name="unit" x-model="proposalForm.unit" required placeholder="Cái, Cuốn, Bộ, Chiếc, Hộp..."
                            class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                    </div>
                </div>

                {{-- Row 3: Quantity, Unit Cost, Min Stock --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div x-show="!editItem">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Số lượng đề xuất <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="stock_quantity" x-model.number="proposalForm.stock_quantity" min="0" required
                            class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2 font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Đơn giá ước tính (VNĐ)</label>
                        <input type="number" name="unit_cost" x-model.number="proposalForm.unit_cost" min="0" step="1000" placeholder="0"
                            class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2 font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cảnh báo tồn tối thiểu</label>
                        <input type="number" name="min_stock_alert" x-model.number="proposalForm.min_stock_alert" min="0"
                            class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                    </div>
                </div>

                {{-- Live Total Cost Calculation Banner --}}
                <div class="p-3 bg-purple-50/80 border border-purple-200 rounded-xl flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-calculator text-purple-600 text-sm"></i>
                        <span class="text-xs text-purple-900 font-semibold">Tổng dự toán kinh phí dự kiến:</span>
                    </div>
                    <div class="text-sm font-black text-purple-800" x-text="formatMoney(totalProposalCost()) + ' VNĐ'"></div>
                </div>

                {{-- Row 4: Funding Source & Event Link --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nguồn kinh phí đề xuất <span class="text-red-500">*</span></label>
                        <select name="funding_source" x-model="proposalForm.funding_source" required class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                            <option value="fund_supplier">Quỹ Hãng (Khai báo)</option>
                            <option value="union">Quỹ Công đoàn</option>
                            <option value="company_support">Đề xuất Công ty hỗ trợ</option>
                            <option value="other">Nguồn khác</option>
                        </select>
                    </div>

                    {{-- Searchable Marketing Event Select --}}
                    <div class="relative" @click.away="proposalEventDropdownOpen = false">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Gắn với Sự kiện Marketing (nếu có)</label>
                        <div class="relative">
                            <i class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text"
                                x-model="proposalEventSearch"
                                @focus="proposalEventDropdownOpen = true"
                                @input="proposalEventDropdownOpen = true"
                                placeholder="Gõ tìm kiếm mã hoặc tên sự kiện..."
                                class="w-full text-xs rounded-lg border-gray-300 pl-8 pr-8 py-2 focus:border-purple-500 focus:ring-purple-500">
                            <button type="button" x-show="proposalEventSearch || proposalForm.marketing_event_id"
                                @click="clearProposalEvent()"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times-circle text-xs"></i>
                            </button>
                        </div>
                        <input type="hidden" name="marketing_event_id" :value="proposalForm.marketing_event_id">

                        <div x-show="proposalEventDropdownOpen" x-cloak
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-gray-100">
                            <div @click="selectProposalEvent(null)"
                                class="p-2.5 hover:bg-gray-100 cursor-pointer text-xs text-gray-500 italic flex items-center gap-2">
                                <i class="fas fa-ban text-gray-400"></i> -- Không gắn sự kiện cụ thể --
                            </div>
                            <template x-for="ev in filteredProposalEvents" :key="ev.id">
                                <div @click="selectProposalEvent(ev)"
                                    class="p-2.5 hover:bg-purple-50/60 cursor-pointer text-xs transition-colors"
                                    :class="{'bg-purple-50 text-purple-900 font-semibold': proposalForm.marketing_event_id == ev.id}">
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="ev.code">
                                            <span class="font-mono font-bold text-purple-700 bg-purple-100 px-1.5 py-0.5 rounded text-[11px]" x-text="ev.code"></span>
                                        </template>
                                        <span class="font-medium text-gray-900" x-text="ev.title"></span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-500" x-show="ev.event_date">
                                        <i class="far fa-calendar-alt text-gray-400"></i>
                                        <span x-text="ev.event_date"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="filteredProposalEvents.length === 0" class="p-3 text-center text-xs text-gray-400">
                                Không tìm thấy sự kiện phù hợp
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conditional Declared Supplier Fund Selection & Warning --}}
                <div x-show="proposalForm.funding_source === 'fund_supplier'" class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl space-y-2.5 transition-all">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-blue-900 uppercase">
                            <i class="fas fa-wallet mr-1 text-blue-600"></i> Chọn Quỹ Hãng đã khai báo <span class="text-red-500">*</span>
                        </label>
                        <template x-if="currentSelectedFund">
                            <span class="text-xs text-blue-700 font-semibold">
                                Số dư khả dụng: <strong class="text-emerald-700" x-text="formatMoney(currentSelectedFund.remaining_amount) + ' đ'"></strong>
                            </span>
                        </template>
                    </div>

                    <select name="marketing_supplier_fund_id" x-model="proposalForm.marketing_supplier_fund_id"
                        class="w-full text-xs rounded-lg border-blue-300 bg-white focus:border-blue-500 focus:ring-blue-500 px-3 py-2">
                        <option value="">-- Chọn quỹ hãng tài trợ --</option>
                        <template x-for="fund in supplierFunds" :key="fund.id">
                            <option :value="fund.id"
                                x-text="(fund.supplier?.name ? (fund.supplier.name + ' - ') : '') + fund.fund_name + ' (Còn: ' + formatMoney(fund.remaining_amount) + ' đ)'"
                                :selected="proposalForm.marketing_supplier_fund_id == fund.id">
                            </option>
                        </template>
                    </select>

                    {{-- Warning if total cost > fund remaining amount --}}
                    <div x-show="isOverBudget()" class="p-2.5 bg-rose-50 border border-rose-300 rounded-lg text-rose-800 text-xs flex items-start gap-2 animate-pulse">
                        <i class="fas fa-exclamation-triangle text-rose-600 mt-0.5 shrink-0 text-sm"></i>
                        <div>
                            <strong>Cảnh báo vượt hạn mức quỹ!</strong>
                            <p class="text-2xs text-rose-700 mt-0.5">
                                Dự toán chi phí (<span x-text="formatMoney(totalProposalCost())"></span> đ) vượt quá số dư khả dụng hiện tại của quỹ (<span x-text="formatMoney(currentSelectedFund?.remaining_amount || 0)"></span> đ). Đề xuất này cần BOD phê duyệt đặc biệt hoặc bổ sung nguồn quỹ.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Row 5: Purpose of Use --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Mục đích sử dụng & Kế hoạch cấp phát</label>
                    <textarea name="purpose" x-model="proposalForm.purpose" rows="2" placeholder="Ví dụ: Tặng đối tác và khách hàng tham dự Workshop Q3, làm quà tri ân cuối năm..."
                        class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2"></textarea>
                </div>

                {{-- Row 6: Description / Note --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Quy cách & Mô tả chi tiết</label>
                    <textarea name="description" x-model="proposalForm.description" rows="2" placeholder="Chất liệu, kích thước, quy cách đóng gói, đơn vị cung cấp dự kiến..."
                        class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2"></textarea>
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAddItemModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200">
                        Hủy
                    </button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 text-white rounded-lg text-xs font-bold hover:bg-purple-700 shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-check"></i>
                        <span x-text="editItem ? 'Cập nhật vật phẩm' : 'Gửi Đề xuất / Lưu vật phẩm'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL CHI TIẾT ĐỀ XUẤT VẬT PHẨM & BOD PHÊ DUYỆT --}}
    <div x-show="showProposalDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[92vh] overflow-y-auto" @click.away="showProposalDetailModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-800">Chi tiết Đề xuất Vật phẩm Marketing</h3>
                        <p class="text-2xs text-gray-500" x-text="proposalDetailItem ? (proposalDetailItem.code + ' - ' + proposalDetailItem.name) : ''"></p>
                    </div>
                </div>
                <button type="button" @click="showProposalDetailModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-base"></i></button>
            </div>

            <template x-if="proposalDetailItem">
                <div class="space-y-4">
                    {{-- Status Banner --}}
                    <div class="p-3 rounded-xl flex items-center justify-between text-xs font-semibold"
                         :class="{
                             'bg-amber-50 text-amber-900 border border-amber-200': proposalDetailItem.approval_status === 'pending',
                             'bg-emerald-50 text-emerald-900 border border-emerald-200': proposalDetailItem.approval_status === 'approved' || !proposalDetailItem.approval_status,
                             'bg-red-50 text-red-900 border border-red-200': proposalDetailItem.approval_status === 'rejected'
                         }">
                        <div class="flex items-center gap-2">
                            <i :class="{
                                'fas fa-clock text-amber-600 animate-spin': proposalDetailItem.approval_status === 'pending',
                                'fas fa-check-circle text-emerald-600': proposalDetailItem.approval_status === 'approved' || !proposalDetailItem.approval_status,
                                'fas fa-times-circle text-red-600': proposalDetailItem.approval_status === 'rejected'
                            }"></i>
                            <span>Trạng thái: <strong x-text="proposalDetailItem.approval_status_label || (proposalDetailItem.approval_status === 'pending' ? 'Chờ BOD duyệt' : (proposalDetailItem.approval_status === 'rejected' ? 'BOD Từ chối' : 'Đã duyệt'))"></strong></span>
                        </div>
                        <template x-if="proposalDetailItem.approval_status === 'rejected' && proposalDetailItem.rejection_reason">
                            <span class="text-red-700 italic" x-text="'Lý do: ' + proposalDetailItem.rejection_reason"></span>
                        </template>
                    </div>

                    {{-- Main Info Grid --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-gray-50/70 p-3.5 rounded-xl border border-gray-100 text-xs">
                        <div>
                            <span class="text-gray-500 block text-2xs uppercase font-semibold">Tên vật phẩm</span>
                            <span class="font-bold text-gray-900" x-text="proposalDetailItem.name"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-2xs uppercase font-semibold">Phân loại</span>
                            <span class="font-medium text-gray-800" x-text="proposalDetailItem.category_label || proposalDetailItem.category"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-2xs uppercase font-semibold">Đơn vị tính</span>
                            <span class="font-medium text-gray-800" x-text="proposalDetailItem.unit"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-2xs uppercase font-semibold">Số lượng đề xuất / tồn kho</span>
                            <span class="font-bold text-base text-gray-900" x-text="proposalDetailItem.stock_quantity"></span>
                            <span class="text-2xs text-gray-400" x-text="'(Tồn tối thiểu: ' + proposalDetailItem.min_stock_alert + ')'"></span>
                        </div>
                        <div>
                            <span class="text-gray-500 block text-2xs uppercase font-semibold">Đơn giá ước tính</span>
                            <span class="font-bold text-gray-900" x-text="formatMoney(proposalDetailItem.unit_cost) + ' đ'"></span>
                        </div>
                        <div class="bg-purple-50/80 p-2 rounded-lg border border-purple-100">
                            <span class="text-purple-700 block text-2xs uppercase font-bold">Tổng dự toán kinh phí</span>
                            <span class="font-bold text-purple-900 text-sm" x-text="formatMoney(proposalDetailItem.total_estimated_cost || (proposalDetailItem.stock_quantity * proposalDetailItem.unit_cost)) + ' đ'"></span>
                        </div>
                    </div>

                    {{-- Funding & Event Details --}}
                    <div class="p-3.5 rounded-xl border border-gray-200 bg-white space-y-2.5 text-xs">
                        <h4 class="font-bold text-gray-800 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas fa-coins text-amber-500"></i> Nguồn kinh phí & Sự kiện liên quan
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <div>
                                <span class="text-gray-500 block text-2xs font-semibold">Nguồn kinh phí:</span>
                                <span class="font-bold text-gray-800" x-text="proposalDetailItem.funding_source_label || (proposalDetailItem.funding_source === 'fund_supplier' ? 'Quỹ Hãng (Khai báo)' : (proposalDetailItem.funding_source === 'union' ? 'Quỹ Công đoàn' : (proposalDetailItem.funding_source === 'company_support' ? 'Đề xuất Công ty hỗ trợ' : 'Khác')))"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-2xs font-semibold">Sự kiện liên kết:</span>
                                <template x-if="proposalDetailItem.event">
                                    <span class="font-semibold text-blue-700" x-text="proposalDetailItem.event.code + ' - ' + proposalDetailItem.event.title"></span>
                                </template>
                                <template x-if="!proposalDetailItem.event">
                                    <span class="text-gray-400 italic">Không gắn sự kiện cụ thể</span>
                                </template>
                            </div>
                        </div>

                        {{-- If Supplier Fund --}}
                        <template x-if="proposalDetailItem.fund">
                            <div class="p-2.5 rounded-lg bg-blue-50/70 border border-blue-100 flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-blue-900 font-bold block" x-text="(proposalDetailItem.fund.supplier?.name ? (proposalDetailItem.fund.supplier.name + ' - ') : '') + (proposalDetailItem.fund.fund_name || 'Quỹ Hãng')"></span>
                                    <span class="text-2xs text-blue-600" x-text="'Mã quỹ: #' + proposalDetailItem.fund.id"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xs text-gray-500 block">Số dư quỹ khả dụng</span>
                                    <span class="font-bold text-emerald-700" x-text="formatMoney(proposalDetailItem.fund.remaining_amount) + ' đ'"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Purpose & Description --}}
                    <div class="space-y-2 text-xs">
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <span class="text-gray-500 block text-2xs uppercase font-bold mb-1">Mục đích sử dụng & Kế hoạch cấp phát:</span>
                            <p class="text-gray-800 font-medium whitespace-pre-line" x-text="proposalDetailItem.purpose || 'Chưa ghi chú mục đích'"></p>
                        </div>
                        <template x-if="proposalDetailItem.description">
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                <span class="text-gray-500 block text-2xs uppercase font-bold mb-1">Quy cách & Mô tả chi tiết:</span>
                                <p class="text-gray-700 whitespace-pre-line" x-text="proposalDetailItem.description"></p>
                            </div>
                        </template>
                    </div>

                    {{-- Audit Trail (Submitter / Approver) --}}
                    <div class="flex items-center justify-between text-2xs text-gray-500 pt-2 border-t border-gray-100">
                        <div>
                            <span class="text-gray-400">Người đề xuất:</span>
                            <strong class="text-gray-700" x-text="proposalDetailItem.submitter?.name || 'Hệ thống'"></strong>
                            <span class="text-gray-400" x-show="proposalDetailItem.created_at" x-text="' • ' + (new Date(proposalDetailItem.created_at)).toLocaleDateString('vi-VN')"></span>
                        </div>
                        <template x-if="proposalDetailItem.approver">
                            <div>
                                <span class="text-gray-400">BOD phê duyệt:</span>
                                <strong class="text-emerald-700" x-text="proposalDetailItem.approver.name"></strong>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Actions --}}
            <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                <button type="button" @click="showProposalDetailModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200">
                    Đóng
                </button>

                <div class="flex items-center gap-2" x-show="proposalDetailItem && proposalDetailItem.approval_status === 'pending'">
                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('director') || auth()->user()->hasRole('admin'))
                        <button type="button" @click="showProposalDetailModal = false; openRejectModal(proposalDetailItem)"
                                class="px-3.5 py-2 bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5">
                            <i class="fas fa-times"></i> Từ chối
                        </button>
                        <form :action="'/marketing-items/' + (proposalDetailItem ? proposalDetailItem.id : '') + '/approve'" method="POST" class="inline m-0" onsubmit="return confirm('BOD xác nhận phê duyệt vật phẩm này vào danh mục và tạo tồn kho ban đầu?')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm flex items-center gap-1.5">
                                <i class="fas fa-check-circle"></i> BOD Phê duyệt
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL NHẬP KHO --}}
    <div x-show="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="showImportModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-arrow-down text-green-600"></i> Nhập kho vật phẩm Marketing
                </h3>
                <button type="button" @click="showImportModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>

            <form action="{{ route('marketing-items.import') }}" method="POST" class="space-y-4">
                @csrf
                {{-- Searchable Item Select --}}
                <div class="relative" @click.away="importItemDropdownOpen = false">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Chọn vật phẩm <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text"
                            x-model="importItemSearch"
                            @focus="importItemDropdownOpen = true"
                            @input="importItemDropdownOpen = true"
                            placeholder="Gõ mã hoặc tên vật phẩm để tìm..."
                            class="w-full text-xs rounded-lg border-gray-300 pl-8 pr-8 py-2 focus:border-green-500 focus:ring-green-500">
                        <button type="button" x-show="importItemSearch || importForm.marketing_item_id"
                            @click="clearImportItem()"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times-circle text-xs"></i>
                        </button>
                    </div>
                    <input type="hidden" name="marketing_item_id" :value="importForm.marketing_item_id" required>

                    <div x-show="importItemDropdownOpen" x-cloak
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-gray-100">
                        <template x-for="item in filteredImportItems" :key="item.id">
                            <div @click="selectImportItem(item)"
                                class="p-2.5 hover:bg-green-50/60 cursor-pointer flex items-center justify-between text-xs transition-colors"
                                :class="{'bg-green-50 text-green-900 font-semibold': importForm.marketing_item_id == item.id}">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded text-[11px]" x-text="item.code"></span>
                                        <span class="text-gray-900 font-medium" x-text="item.name"></span>
                                    </div>
                                </div>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium" x-text="'Hiện có: ' + item.stock_quantity + ' ' + item.unit"></span>
                            </div>
                        </template>
                        <div x-show="filteredImportItems.length === 0" class="p-3 text-center text-xs text-gray-400">
                            Không tìm thấy vật phẩm phù hợp
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Số lượng nhập <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity" x-model="importForm.quantity" min="1" required class="w-full text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Đơn giá nhập (VNĐ)</label>
                        <input type="number" name="unit_cost" x-model="importForm.unit_cost" min="0" step="1000" class="w-full text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Mã tham chiếu / Hóa đơn</label>
                    <input type="text" name="reference_code" placeholder="PO-MKT-..., HĐ..." class="w-full text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ghi chú nhập kho</label>
                    <textarea name="note" rows="2" placeholder="Nhập từ nhà cung cấp nào, đợt mua sắm nào..." class="w-full text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 px-3 py-2"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="showImportModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200">
                        Hủy
                    </button>
                    <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 shadow-sm">
                        Xác nhận nhập kho
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL XUẤT KHO / QUÀ TẶNG --}}
    <div x-show="showExportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="showExportModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-arrow-up text-blue-600"></i> Xuất kho Quà tặng / Sự kiện
                </h3>
                <button type="button" @click="showExportModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>

            <form action="{{ route('marketing-items.export') }}" method="POST" class="space-y-4">
                @csrf
                {{-- Searchable Item Select --}}
                <div class="relative" @click.away="exportItemDropdownOpen = false">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Chọn vật phẩm <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text"
                            x-model="exportItemSearch"
                            @focus="exportItemDropdownOpen = true"
                            @input="exportItemDropdownOpen = true"
                            placeholder="Gõ mã hoặc tên quà tặng để tìm..."
                            class="w-full text-xs rounded-lg border-gray-300 pl-8 pr-8 py-2 focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" x-show="exportItemSearch || exportForm.marketing_item_id"
                            @click="clearExportItem()"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times-circle text-xs"></i>
                        </button>
                    </div>
                    <input type="hidden" name="marketing_item_id" :value="exportForm.marketing_item_id" required>

                    <div x-show="exportItemDropdownOpen" x-cloak
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-gray-100">
                        <template x-for="item in filteredExportItems" :key="item.id">
                            <div @click="selectExportItem(item)"
                                class="p-2.5 cursor-pointer flex items-center justify-between text-xs transition-colors"
                                :class="{
                                    'bg-gray-50 opacity-50 cursor-not-allowed': item.stock_quantity <= 0,
                                    'hover:bg-blue-50/60': item.stock_quantity > 0,
                                    'bg-blue-50 text-blue-900 font-semibold': exportForm.marketing_item_id == item.id
                                }">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded text-[11px]" x-text="item.code"></span>
                                        <span class="text-gray-900 font-medium" x-text="item.name"></span>
                                    </div>
                                </div>
                                <span class="text-[11px] px-2 py-0.5 rounded-full font-semibold"
                                    :class="item.stock_quantity <= 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800'"
                                    x-text="item.stock_quantity <= 0 ? 'Hết hàng (0 ' + item.unit + ')' : ('Tồn: ' + item.stock_quantity + ' ' + item.unit)">
                                </span>
                            </div>
                        </template>
                        <div x-show="filteredExportItems.length === 0" class="p-3 text-center text-xs text-gray-400">
                            Không tìm thấy vật phẩm phù hợp
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Số lượng xuất <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" x-model="exportForm.quantity" min="1" required class="w-full text-xs rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 px-3 py-2">
                </div>

                {{-- Searchable Opportunity Select --}}
                <div class="relative" @click.away="exportOppDropdownOpen = false">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Gắn với Cơ hội (nếu có)</label>
                    <div class="relative">
                        <i class="fas fa-handshake absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text"
                            x-model="exportOppSearch"
                            @focus="exportOppDropdownOpen = true"
                            @input="exportOppDropdownOpen = true"
                            placeholder="Gõ tìm theo tên cơ hội hoặc khách hàng..."
                            class="w-full text-xs rounded-lg border-gray-300 pl-8 pr-8 py-2 focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" x-show="exportOppSearch || exportForm.opportunity_id"
                            @click="clearExportOpp()"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times-circle text-xs"></i>
                        </button>
                    </div>
                    <input type="hidden" name="opportunity_id" :value="exportForm.opportunity_id">

                    <div x-show="exportOppDropdownOpen" x-cloak
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-gray-100">
                        <div @click="selectExportOpp(null)"
                            class="p-2.5 hover:bg-gray-100 cursor-pointer text-xs text-gray-500 italic flex items-center gap-2">
                            <i class="fas fa-ban text-gray-400"></i> -- Không gắn Cơ hội --
                        </div>
                        <template x-for="opp in filteredOpportunities" :key="opp.id">
                            <div @click="selectExportOpp(opp)"
                                class="p-2.5 hover:bg-blue-50/60 cursor-pointer text-xs transition-colors"
                                :class="{'bg-blue-50 text-blue-900 font-semibold': exportForm.opportunity_id == opp.id}">
                                <div class="font-medium text-gray-900" x-text="opp.name"></div>
                                <div class="flex items-center gap-2 mt-0.5 text-[11px] text-gray-500">
                                    <span class="text-blue-600"><i class="fas fa-building mr-1"></i><span x-text="opp.customer_name"></span></span>
                                    <span class="text-gray-400" x-show="opp.status_label" x-text="'• ' + opp.status_label"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="filteredOpportunities.length === 0" class="p-3 text-center text-xs text-gray-400">
                            Không tìm thấy cơ hội phù hợp
                        </div>
                    </div>
                </div>

                {{-- Searchable Marketing Event Select --}}
                <div class="relative" @click.away="exportEventDropdownOpen = false">
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Gắn với Sự kiện Marketing (nếu có)</label>
                    <div class="relative">
                        <i class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text"
                            x-model="exportEventSearch"
                            @focus="exportEventDropdownOpen = true"
                            @input="exportEventDropdownOpen = true"
                            placeholder="Gõ tìm theo mã hoặc tên sự kiện MKT..."
                            class="w-full text-xs rounded-lg border-gray-300 pl-8 pr-8 py-2 focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" x-show="exportEventSearch || exportForm.marketing_event_id"
                            @click="clearExportEvent()"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times-circle text-xs"></i>
                        </button>
                    </div>
                    <input type="hidden" name="marketing_event_id" :value="exportForm.marketing_event_id">

                    <div x-show="exportEventDropdownOpen" x-cloak
                        class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-56 overflow-y-auto divide-y divide-gray-100">
                        <div @click="selectExportEvent(null)"
                            class="p-2.5 hover:bg-gray-100 cursor-pointer text-xs text-gray-500 italic flex items-center gap-2">
                            <i class="fas fa-ban text-gray-400"></i> -- Không gắn Sự kiện --
                        </div>
                        <template x-for="ev in filteredMarketingEvents" :key="ev.id">
                            <div @click="selectExportEvent(ev)"
                                class="p-2.5 hover:bg-purple-50/60 cursor-pointer text-xs transition-colors"
                                :class="{'bg-purple-50 text-purple-900 font-semibold': exportForm.marketing_event_id == ev.id}">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded text-[11px]" x-text="ev.code"></span>
                                    <span class="text-gray-900 font-medium" x-text="ev.title"></span>
                                </div>
                                <div class="text-[11px] text-gray-400 mt-0.5 pl-0.5" x-show="ev.event_date" x-text="'📅 Ngày: ' + ev.event_date"></div>
                            </div>
                        </template>
                        <div x-show="filteredMarketingEvents.length === 0" class="p-3 text-center text-xs text-gray-400">
                            Không tìm thấy sự kiện phù hợp
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ghi chú xuất quà</label>
                    <textarea name="note" rows="2" placeholder="Xuất tặng khách hàng nào, nhân viên nhận quà đi gặp khách..." class="w-full text-xs rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 px-3 py-2"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="showExportModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200">
                        Hủy
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-xs font-bold hover:bg-blue-700 shadow-sm">
                        Xác nhận xuất kho
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL IMPORT FILE QUÀ TẶNG (EXCEL / CSV) --}}
    <div x-show="showImportFileModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="showImportFileModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-file-excel text-emerald-600"></i> Import Danh sách Quà tặng / Vật phẩm
                </h3>
                <button type="button" @click="showImportFileModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>

            <form action="{{ route('marketing-items.import-file') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                
                <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-2 text-xs text-emerald-900">
                    <div class="font-bold flex items-center gap-1.5 text-emerald-800">
                        <i class="fas fa-info-circle"></i> Hướng dẫn nhập file Excel (.xlsx):
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-emerald-800/90 text-2xs">
                        <li>File cần chứa các cột: <strong>Mã vật phẩm, Tên vật phẩm (*), Phân loại, Đơn vị tính, Số lượng nhập (*), Cảnh báo tồn tối thiểu, Đơn giá ước tính (VNĐ), Mô tả / Ghi chú</strong>.</li>
                        <li>Phân loại chấp nhận: <code>gift</code> (hoặc Quà tặng), <code>clothing</code> (hoặc Đồng phục), <code>publication</code> (hoặc Ấn phẩm), <code>equipment</code> (hoặc Vật tư/Thiết bị/Standee), <code>other</code> (hoặc Khác).</li>
                        <li>Nếu bỏ trống cột <strong>Mã vật phẩm</strong>, hệ thống sẽ tự động phát sinh mã chuẩn (ví dụ: MKT-0015).</li>
                        <li>Nếu mã vật phẩm đã tồn tại, hệ thống sẽ tự động cập nhật thông tin và cộng dồn số lượng vào kho.</li>
                    </ul>
                    <div class="pt-1">
                        <a href="{{ route('marketing-items.download-template') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-emerald-300 text-emerald-700 font-bold rounded-lg hover:bg-emerald-50 transition-colors shadow-2xs">
                            <i class="fas fa-file-excel text-emerald-600"></i> Tải file Excel mẫu chuẩn (.xlsx)
                        </a>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Chọn file Excel (.xlsx, .xls) <span class="text-red-500">*</span></label>
                    <input type="file" name="file" required accept=".xlsx,.xls"
                           class="w-full text-xs text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-gray-300 rounded-lg p-2 bg-white">
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="showImportFileModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200">
                        Hủy
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-cloud-upload-alt"></i> Tải lên & Thực hiện Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL BOD TỪ CHỐI DUYỆT VẬT PHẨM --}}
    <div x-show="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="showRejectModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-red-600 flex items-center gap-2">
                    <i class="fas fa-times-circle"></i> BOD Từ chối Phê duyệt Vật phẩm
                </h3>
                <button type="button" @click="showRejectModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>

            <form :action="rejectItem ? ('/marketing-items/' + rejectItem.id + '/reject') : '#'" method="POST" class="space-y-4">
                @csrf
                <p class="text-xs text-gray-600">
                    Vật phẩm: <strong class="text-gray-900" x-text="rejectItem ? (rejectItem.code + ' - ' + rejectItem.name) : ''"></strong>
                </p>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Lý do từ chối <span class="text-red-500">*</span></label>
                    <textarea name="rejection_reason" rows="3" required placeholder="Nhập lý do không phê duyệt vật phẩm này..."
                              class="w-full text-xs rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 px-3 py-2"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="showRejectModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200">
                        Đóng
                    </button>
                    <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold shadow-sm">
                        Xác nhận Từ chối
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function marketingItemsManager(config) {
    return {
        showAddItemModal: false,
        showImportModal: false,
        showExportModal: false,
        showImportFileModal: false,
        showRejectModal: false,
        showProposalDetailModal: false,
        selectedItem: null,
        editItem: null,
        rejectItem: null,
        proposalDetailItem: null,

        allItems: config.allItems || [],
        opportunities: config.opportunities || [],
        marketingEvents: config.marketingEvents || [],
        supplierFunds: config.supplierFunds || [],

        proposalForm: {
            name: '',
            category: 'gift',
            unit: 'Cái',
            stock_quantity: 0,
            unit_cost: 0,
            min_stock_alert: 10,
            funding_source: 'fund_supplier',
            marketing_supplier_fund_id: '',
            marketing_event_id: '',
            purpose: '',
            description: '',
            status: 'active'
        },
        proposalEventSearch: '',
        proposalEventDropdownOpen: false,

        openCreateItem() {
            this.editItem = null;
            this.proposalForm = {
                name: '',
                category: 'gift',
                unit: 'Cái',
                stock_quantity: 0,
                unit_cost: 0,
                min_stock_alert: 10,
                funding_source: 'fund_supplier',
                marketing_supplier_fund_id: (this.supplierFunds.length > 0 ? this.supplierFunds[0].id : ''),
                marketing_event_id: '',
                purpose: '',
                description: '',
                status: 'active'
            };
            this.proposalEventSearch = '';
            this.proposalEventDropdownOpen = false;
            this.showAddItemModal = true;
        },

        openEdit(item) {
            this.editItem = item;
            this.proposalForm = {
                name: item.name || '',
                category: item.category || 'gift',
                unit: item.unit || 'Cái',
                stock_quantity: item.stock_quantity || 0,
                unit_cost: item.unit_cost || 0,
                min_stock_alert: item.min_stock_alert || 10,
                funding_source: item.funding_source || 'fund_supplier',
                marketing_supplier_fund_id: item.marketing_supplier_fund_id || '',
                marketing_event_id: item.marketing_event_id || '',
                purpose: item.purpose || '',
                description: item.description || '',
                status: item.status || 'active'
            };
            if (item.marketing_event_id) {
                const ev = this.marketingEvents.find(e => e.id == item.marketing_event_id);
                this.proposalEventSearch = ev ? ((ev.code ? (ev.code + ' - ') : '') + ev.title) : '';
            } else {
                this.proposalEventSearch = '';
            }
            this.proposalEventDropdownOpen = false;
            this.showAddItemModal = true;
        },

        selectProposalEvent(ev) {
            if (ev) {
                this.proposalForm.marketing_event_id = ev.id;
                this.proposalEventSearch = (ev.code ? (ev.code + ' - ') : '') + ev.title;
            } else {
                this.proposalForm.marketing_event_id = '';
                this.proposalEventSearch = '';
            }
            this.proposalEventDropdownOpen = false;
        },

        clearProposalEvent() {
            this.proposalForm.marketing_event_id = '';
            this.proposalEventSearch = '';
        },

        openProposalDetail(item) {
            this.proposalDetailItem = item;
            this.showProposalDetailModal = true;
        },

        openRejectModal(item) {
            this.rejectItem = item;
            this.showRejectModal = true;
        },

        formatMoney(val) {
            if (val === null || val === undefined || isNaN(val)) return '0';
            return Number(val).toLocaleString('vi-VN');
        },

        totalProposalCost() {
            const qty = Number(this.proposalForm.stock_quantity) || 0;
            const cost = Number(this.proposalForm.unit_cost) || 0;
            return qty * cost;
        },

        get currentSelectedFund() {
            if (!this.proposalForm.marketing_supplier_fund_id) return null;
            return this.supplierFunds.find(f => f.id == this.proposalForm.marketing_supplier_fund_id) || null;
        },

        isOverBudget() {
            if (this.proposalForm.funding_source !== 'fund_supplier') return false;
            const fund = this.currentSelectedFund;
            if (!fund) return false;
            return this.totalProposalCost() > (Number(fund.remaining_amount) || 0);
        },

        importForm: {
            marketing_item_id: '',
            quantity: 1,
            unit_cost: 0,
            note: '',
            reference_code: ''
        },
        importItemSearch: '',
        importItemDropdownOpen: false,

        exportForm: {
            marketing_item_id: '',
            quantity: 1,
            opportunity_id: '',
            marketing_event_id: '',
            note: '',
            reference_code: ''
        },
        exportItemSearch: '',
        exportItemDropdownOpen: false,

        exportOppSearch: '',
        exportOppDropdownOpen: false,

        exportEventSearch: '',
        exportEventDropdownOpen: false,

        openImport(item) {
            this.selectedItem = item;
            this.importForm.marketing_item_id = item ? item.id : '';
            this.importForm.unit_cost = item ? item.unit_cost : 0;
            this.importForm.quantity = 1;
            this.importItemSearch = item ? (item.code + ' - ' + item.name) : '';
            this.importItemDropdownOpen = false;
            this.showImportModal = true;
        },

        selectImportItem(item) {
            this.importForm.marketing_item_id = item.id;
            this.importForm.unit_cost = item.unit_cost || 0;
            this.importItemSearch = item.code + ' - ' + item.name;
            this.importItemDropdownOpen = false;
        },

        clearImportItem() {
            this.importForm.marketing_item_id = '';
            this.importItemSearch = '';
        },

        openExport(item) {
            this.selectedItem = item;
            this.exportForm.marketing_item_id = item ? item.id : '';
            this.exportForm.quantity = 1;
            this.exportForm.opportunity_id = '';
            this.exportForm.marketing_event_id = '';
            this.exportForm.note = '';
            this.exportItemSearch = item ? (item.code + ' - ' + item.name) : '';
            this.exportOppSearch = '';
            this.exportEventSearch = '';
            this.exportItemDropdownOpen = false;
            this.exportOppDropdownOpen = false;
            this.exportEventDropdownOpen = false;
            this.showExportModal = true;
        },

        selectExportItem(item) {
            if (item.stock_quantity <= 0) return;
            this.exportForm.marketing_item_id = item.id;
            this.exportItemSearch = item.code + ' - ' + item.name;
            this.exportItemDropdownOpen = false;
        },

        clearExportItem() {
            this.exportForm.marketing_item_id = '';
            this.exportItemSearch = '';
        },

        selectExportOpp(opp) {
            if (opp) {
                this.exportForm.opportunity_id = opp.id;
                this.exportOppSearch = opp.name + (opp.customer_name ? ' (' + opp.customer_name + ')' : '');
            } else {
                this.exportForm.opportunity_id = '';
                this.exportOppSearch = '';
            }
            this.exportOppDropdownOpen = false;
        },

        clearExportOpp() {
            this.exportForm.opportunity_id = '';
            this.exportOppSearch = '';
        },

        selectExportEvent(ev) {
            if (ev) {
                this.exportForm.marketing_event_id = ev.id;
                this.exportEventSearch = ev.code + ' - ' + ev.title;
            } else {
                this.exportForm.marketing_event_id = '';
                this.exportEventSearch = '';
            }
            this.exportEventDropdownOpen = false;
        },

        clearExportEvent() {
            this.exportForm.marketing_event_id = '';
            this.exportEventSearch = '';
        },

        get filteredImportItems() {
            const q = this.importItemSearch.trim().toLowerCase();
            if (!q) return this.allItems;
            return this.allItems.filter(i => 
                (i.name && i.name.toLowerCase().includes(q)) || 
                (i.code && i.code.toLowerCase().includes(q))
            );
        },

        get filteredExportItems() {
            const q = this.exportItemSearch.trim().toLowerCase();
            if (!q) return this.allItems;
            return this.allItems.filter(i => 
                (i.name && i.name.toLowerCase().includes(q)) || 
                (i.code && i.code.toLowerCase().includes(q))
            );
        },

        get filteredOpportunities() {
            const q = this.exportOppSearch.trim().toLowerCase();
            if (!q) return this.opportunities;
            return this.opportunities.filter(o => 
                (o.name && o.name.toLowerCase().includes(q)) || 
                (o.customer_name && o.customer_name.toLowerCase().includes(q))
            );
        },

        get filteredMarketingEvents() {
            const q = this.exportEventSearch.trim().toLowerCase();
            if (!q) return this.marketingEvents;
            return this.marketingEvents.filter(e => 
                (e.title && e.title.toLowerCase().includes(q)) || 
                (e.code && e.code.toLowerCase().includes(q)) ||
                (e.event_date && e.event_date.toLowerCase().includes(q))
            );
        },

        get filteredProposalEvents() {
            const q = this.proposalEventSearch.trim().toLowerCase();
            if (!q) return this.marketingEvents;
            return this.marketingEvents.filter(e => 
                (e.title && e.title.toLowerCase().includes(q)) || 
                (e.code && e.code.toLowerCase().includes(q)) ||
                (e.event_date && e.event_date.toLowerCase().includes(q))
            );
        }
    };
}
</script>
@endsection
