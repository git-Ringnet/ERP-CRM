@extends('layouts.app')
@section('title', 'Kho vật phẩm & Quà tặng Marketing')
@section('page-title', 'Quản lý Kho vật phẩm & Quà tặng Marketing')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>

<div class="space-y-6" x-data="{ 
    showAddItemModal: false,
    showImportModal: false,
    showExportModal: false,
    selectedItem: null,
    editItem: null,
    importForm: { marketing_item_id: '', quantity: 1, unit_cost: 0, note: '', reference_code: '' },
    exportForm: { marketing_item_id: '', quantity: 1, opportunity_id: '', marketing_event_id: '', note: '', reference_code: '' },
    openImport(item) {
        this.selectedItem = item;
        this.importForm.marketing_item_id = item.id;
        this.importForm.unit_cost = item.unit_cost;
        this.showImportModal = true;
    },
    openExport(item) {
        this.selectedItem = item;
        this.exportForm.marketing_item_id = item.id;
        this.showExportModal = true;
    },
    openEdit(item) {
        this.editItem = item;
        this.showAddItemModal = true;
    }
}">

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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                <button type="button" @click="showAddItemModal = true; editItem = null;"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5 shadow-sm">
                    <i class="fas fa-plus"></i> Thêm vật phẩm mới
                </button>
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
            <form method="GET" action="{{ route('marketing-items.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
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
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-xs font-semibold hover:bg-gray-700 transition-colors flex-1">
                        <i class="fas fa-filter mr-1"></i> Lọc
                    </button>
                    @if(request()->anyFilled(['search', 'category', 'stock_status']))
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
                        <th class="px-4 py-3.5 text-center">Trạng thái</th>
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
                                        <div class="font-bold text-gray-900">{{ $item->name }}</div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs font-mono font-semibold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded">{{ $item->code }}</span>
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
                                <div class="flex items-center justify-center gap-1.5">
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
                                        class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 flex items-center justify-center text-xs transition-colors">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <button type="button" @click="openEdit({{ json_encode($item) }})" title="Sửa thông tin"
                                        class="w-7 h-7 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 flex items-center justify-center text-xs transition-colors">
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
                            <td colspan="8" class="text-center py-10 text-gray-400">
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

    {{-- MODAL THÊM / SỬA VẬT PHẨM --}}
    <div x-show="showAddItemModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="showAddItemModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-bold text-gray-800" x-text="editItem ? 'Chỉnh sửa vật phẩm' : 'Thêm vật phẩm mới'"></h3>
                <button type="button" @click="showAddItemModal = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>

            <form :action="editItem ? ('/marketing-items/' + editItem.id) : '{{ route('marketing-items.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="editItem">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tên vật phẩm <span class="text-red-500">*</span></label>
                    <input type="text" name="name" :value="editItem ? editItem.name : ''" required placeholder="Ví dụ: Sổ tay da A5, Bút kim loại..."
                        class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Phân loại <span class="text-red-500">*</span></label>
                        <select name="category" required class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                            @foreach($categories as $key => $name)
                                <option value="{{ $key }}" :selected="editItem && editItem.category === '{{ $key }}'">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Đơn vị tính <span class="text-red-500">*</span></label>
                        <input type="text" name="unit" :value="editItem ? editItem.unit : 'Cái'" required placeholder="Cái, Cuốn, Bộ..."
                            class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div x-show="!editItem">
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Số lượng ban đầu</label>
                        <input type="number" name="stock_quantity" value="0" min="0"
                            class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Cảnh báo tồn tối thiểu</label>
                        <input type="number" name="min_stock_alert" :value="editItem ? editItem.min_stock_alert : '10'" min="0"
                            class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Đơn giá ước tính (VNĐ)</label>
                    <input type="number" name="unit_cost" :value="editItem ? editItem.unit_cost : '0'" min="0" step="1000"
                        class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Mô tả / Ghi chú</label>
                    <textarea name="description" rows="2" placeholder="Chất liệu, quy cách đóng gói, mục đích sử dụng..."
                        class="w-full text-xs rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500 px-3 py-2"
                        x-text="editItem ? editItem.description : ''"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" @click="showAddItemModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200">
                        Hủy
                    </button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 text-white rounded-lg text-xs font-bold hover:bg-purple-700 shadow-sm">
                        Lưu thông tin
                    </button>
                </div>
            </form>
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
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Chọn vật phẩm <span class="text-red-500">*</span></label>
                    <select name="marketing_item_id" x-model="importForm.marketing_item_id" required class="w-full text-xs rounded-lg border-gray-300 focus:border-green-500 focus:ring-green-500 px-3 py-2">
                        <option value="">-- Chọn vật phẩm --</option>
                        @foreach($items as $it)
                            <option value="{{ $it->id }}">{{ $it->code }} - {{ $it->name }} (Hiện có: {{ $it->stock_quantity }} {{ $it->unit }})</option>
                        @endforeach
                    </select>
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
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Chọn vật phẩm <span class="text-red-500">*</span></label>
                    <select name="marketing_item_id" x-model="exportForm.marketing_item_id" required class="w-full text-xs rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 px-3 py-2">
                        <option value="">-- Chọn vật phẩm xuất --</option>
                        @foreach($items as $it)
                            <option value="{{ $it->id }}">{{ $it->code }} - {{ $it->name }} (Tồn khả dụng: {{ $it->stock_quantity }} {{ $it->unit }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Số lượng xuất <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" x-model="exportForm.quantity" min="1" required class="w-full text-xs rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Gắn với Cơ hội (nếu có)</label>
                    <select name="opportunity_id" class="w-full text-xs rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 px-3 py-2">
                        <option value="">-- Không gắn Cơ hội --</option>
                        @foreach($opportunities as $opp)
                            <option value="{{ $opp->id }}">{{ $opp->name }} (KH: {{ $opp->customer_display_name }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Gắn với Sự kiện Marketing (nếu có)</label>
                    <select name="marketing_event_id" class="w-full text-xs rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 px-3 py-2">
                        <option value="">-- Không gắn Sự kiện --</option>
                        @foreach($marketingEvents as $ev)
                            <option value="{{ $ev->id }}">{{ $ev->name }}</option>
                        @endforeach
                    </select>
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
</div>
@endsection
