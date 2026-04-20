@extends('layouts.app')

@section('title', 'Chi tiết báo cáo')
@section('page-title', 'Chi Tiết Báo Cáo: ' . $damagedGood->code)

@section('content')
    <div class="space-y-6 text-gray-800">
        {{-- Header & Actions --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-4">
                <a href="{{ route('damaged-goods.index') }}" 
                    class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-all shadow-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $damagedGood->code }}</h2>
                    <p class="text-xs text-gray-500 flex items-center gap-2 mt-1">
                        <i class="far fa-calendar-alt"></i> Ngày báo cáo: {{ $damagedGood->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($damagedGood->status === 'pending')
                    <a href="{{ route('damaged-goods.edit', $damagedGood) }}"
                        class="flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors text-sm font-medium shadow-sm">
                        <i class="fas fa-edit"></i> Chỉnh sửa
                    </a>
                    <form action="{{ route('damaged-goods.destroy', $damagedGood) }}" method="POST" class="inline"
                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa bản ghi này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                            class="flex items-center gap-2 px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg transition-colors text-sm font-medium shadow-sm">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </form>
                @endif
                <button onclick="window.print()" 
                    class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-sm font-medium shadow-sm border border-gray-200">
                    <i class="fas fa-print"></i> In
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 font-sans">
            {{-- Main Content Area --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- General Info Card --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
                        <i class="fas fa-info-circle text-primary"></i>
                        <h3 class="font-bold text-gray-900 uppercase tracking-wider text-xs">Thông Tin Chung</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Loại báo cáo</label>
                                <div class="flex">
                                    <span class="px-3 py-1 text-xs font-bold rounded-full {{ $damagedGood->type === 'damaged' ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-amber-100 text-amber-700 border border-amber-200' }}">
                                        {{ $damagedGood->getTypeLabel() }}
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Ngày phát hiện</label>
                                <p class="text-gray-900 font-semibold flex items-center gap-2">
                                    <i class="far fa-clock text-gray-300"></i>
                                    {{ $damagedGood->discovery_date ? $damagedGood->discovery_date->format('d/m/Y') : 'N/A' }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Sản phẩm chính</label>
                                <div class="flex items-center gap-2 group">
                                    <a href="{{ route('products.show', $damagedGood->product_id) }}"
                                        class="text-primary font-black hover:text-primary-dark transition-colors border-b border-transparent hover:border-primary">
                                        {{ $damagedGood->product->name }}
                                    </a>
                                    <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded font-mono">#{{ $damagedGood->product->code }}</span>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Kho phát hiện</label>
                                <p class="text-gray-900 font-semibold flex items-center gap-2">
                                    <i class="fas fa-warehouse text-gray-300"></i>
                                    {{ $damagedGood->warehouse ? $damagedGood->warehouse->name : 'N/A' }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Người báo cáo</label>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center text-[10px] font-black text-primary">
                                        {{ strtoupper(substr($damagedGood->discoveredBy->name, 0, 1)) }}
                                    </div>
                                    <span class="text-gray-900 font-semibold text-sm">{{ $damagedGood->discoveredBy->name }}</span>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tổng số lượng</label>
                                <p class="text-lg font-black text-gray-900">{{ number_format($damagedGood->quantity, 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table Section --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-barcode text-primary"></i>
                            <h3 class="font-bold text-gray-900 uppercase tracking-wider text-[11px]">Danh sách chi tiết Serial / SKU ({{ $damagedGood->items->count() }})</h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto min-h-[150px]">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 border-b border-gray-100">
                                    <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">#</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">SẢN PHẨM</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">SERIAL / ITEM ID</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">CHI TIẾT MÔ TẢ</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">SL</th>
                                    <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">TÌNH TRẠNG</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($damagedGood->items as $index => $item)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 text-xs text-gray-400">{{ $index + 1 }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-gray-900">{{ $item->product->name }}</span>
                                                <span class="text-[9px] text-gray-500 font-mono">{{ $item->product->code }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-black text-gray-900 tracking-tight font-mono">{{ $item->sku }}</span>
                                                <span class="text-[9px] text-gray-400 uppercase tracking-tighter">REF: #{{ $item->import_id ?? 'SYSTEM' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs text-gray-600 line-clamp-1 italic">
                                                {{ $item->description ?: 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="text-sm font-bold text-gray-900">{{ number_format($item->quantity, 0) }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $statusColors = [
                                                    \App\Models\ProductItem::STATUS_DAMAGED => 'bg-rose-100 text-rose-700 border-rose-200',
                                                    \App\Models\ProductItem::STATUS_LIQUIDATION => 'bg-amber-100 text-amber-700 border-amber-200',
                                                    \App\Models\ProductItem::STATUS_IN_STOCK => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                ];
                                                $statusLabels = \App\Models\ProductItem::getStatuses();
                                            @endphp
                                            <span class="px-2 py-1 text-[9px] font-black rounded uppercase border {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                                {{ $statusLabels[$item->status] ?? $item->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    @if($damagedGood->productItem)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4 text-xs text-gray-400">1</td>
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-gray-900">{{ $damagedGood->product->name }}</span>
                                                    <span class="text-[9px] text-gray-500 font-mono">{{ $damagedGood->product->code }}</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-black text-gray-900 font-mono">{{ $damagedGood->productItem->sku }}</td>
                                            <td class="px-6 py-4 text-xs text-gray-500 italic">Mã đơn lẻ</td>
                                            <td class="px-6 py-4 text-right text-sm font-bold">1</td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="px-2 py-1 text-[9px] font-black rounded uppercase bg-rose-100 text-rose-700 border border-rose-200">
                                                    Hư hỏng
                                                </span>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic text-sm">
                                                <i class="fas fa-inbox block text-4xl mb-3 opacity-20"></i>
                                                Không có thông tin chi tiết các Item được chọn.
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Analysis & Notes Section --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Reason --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-rose-500"></i>
                            <h3 class="font-bold text-gray-900 uppercase tracking-wider text-[11px]">Lý do / Hiện trạng</h3>
                        </div>
                        <div class="p-5 flex-grow">
                            <div class="bg-rose-50/50 rounded-xl p-4 border border-rose-100 min-h-[80px]">
                                <p class="text-sm text-rose-900 leading-relaxed font-medium">"{{ $damagedGood->reason }}"</p>
                            </div>
                        </div>
                    </div>
                    {{-- Solution --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
                            <i class="fas fa-lightbulb text-emerald-500"></i>
                            <h3 class="font-bold text-gray-900 uppercase tracking-wider text-[11px]">Phương án xử lý</h3>
                        </div>
                        <div class="p-5 flex-grow">
                            @if($damagedGood->solution)
                                <div class="bg-emerald-50/50 rounded-xl p-4 border border-emerald-100 min-h-[80px]">
                                    <p class="text-sm text-emerald-900 leading-relaxed font-medium">{{ $damagedGood->solution }}</p>
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center py-6">
                                    <span class="text-xs text-gray-400 italic">Chưa đề xuất giải pháp xử lý.</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    {{-- Ghi chú --}}
                    <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
                            <i class="fas fa-comment-alt text-amber-500"></i>
                            <h3 class="font-bold text-gray-900 uppercase tracking-wider text-[11px]">Ghi chú nội bộ</h3>
                        </div>
                        <div class="p-5">
                            @if($damagedGood->note)
                                <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700 leading-relaxed border border-gray-100">
                                    {{ $damagedGood->note }}
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic text-center py-2">Không có ghi chú thêm.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar Area --}}
            <div class="space-y-6">
                {{-- Financial Summary Card --}}
                <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden group">
                    <div class="p-4 border-b border-gray-100 bg-primary/5 flex items-center gap-2">
                        <i class="fas fa-coins text-primary"></i>
                        <h3 class="font-bold text-gray-900 uppercase tracking-wider text-xs">Phân tích Giá trị</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="flex justify-between items-end border-b border-gray-50 pb-4">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Giá trị gốc</label>
                                <p class="text-lg font-black text-gray-800">{{ number_format($damagedGood->original_value, 0) }}đ</p>
                            </div>
                            <div class="text-right">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Giá trị thu hồi</label>
                                <p class="text-lg font-black text-emerald-600">+ {{ number_format($damagedGood->recovery_value, 0) }}đ</p>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-gray-900 to-slate-800 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
                            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/5 rounded-full blur-2xl group-hover:bg-white/10 transition-all duration-500"></div>
                            
                            <label class="text-[10px] font-bold text-rose-300 uppercase tracking-widest mb-1 block">Tổn thất (Net Loss)</label>
                            <p class="text-4xl font-black text-rose-50 tracking-tighter">
                                {{ number_format($damagedGood->getLossAmount(), 0) }}đ
                            </p>
                            
                            <div class="mt-6 space-y-2">
                                <div class="flex justify-between text-[11px] font-bold mb-1">
                                    <span class="text-white/60">Tỉ lệ thu hồi (Recovery Rate)</span>
                                    <span class="text-emerald-400">{{ number_format($damagedGood->getRecoveryRate(), 1) }}%</span>
                                </div>
                                <div class="w-full bg-white/10 h-2 rounded-full overflow-hidden border border-white/5">
                                    <div class="bg-gradient-to-r from-rose-500 to-emerald-400 h-full shadow-[0_0_8px_rgba(52,211,153,0.5)]" style="width: {{ min(100, $damagedGood->getRecoveryRate()) }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status & Approval Card --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
                        <i class="fas fa-shield-alt text-primary"></i>
                        <h3 class="font-bold text-gray-900 uppercase tracking-wider text-xs">Duyệt & Phê duyệt</h3>
                    </div>
                    <div class="p-6">
                        <div class="mb-8 flex flex-col items-center">
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-amber-100 text-amber-700 border-amber-200 ring-amber-50',
                                    'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200 ring-emerald-50',
                                    'rejected' => 'bg-rose-100 text-rose-700 border-rose-200 ring-rose-50',
                                    'processed' => 'bg-blue-100 text-blue-700 border-blue-200 ring-blue-50',
                                ];
                                $currentStatusClass = $statusClasses[$damagedGood->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="inline-flex items-center gap-3 px-8 py-3 rounded-full border-2 text-sm font-black uppercase tracking-widest ring-8 {{ $currentStatusClass }}">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-current opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-current"></span>
                                </span>
                                {{ $damagedGood->getStatusLabel() }}
                            </span>
                        </div>

                        @if($damagedGood->status === 'pending')
                            <div class="bg-gray-50 rounded-2xl p-5 border border-indigo-50 shadow-inner">
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-5 flex items-center gap-2">
                                    <i class="fas fa-user-check"></i> CẬP NHẬT TRẠNG THÁI
                                </h4>
                                <form action="{{ route('damaged-goods.update-status', $damagedGood) }}" method="POST" class="space-y-4">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <select name="status" id="status"
                                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-bold focus:outline-none focus:ring-4 focus:ring-primary/10 bg-white transition-all appearance-none cursor-pointer shadow-sm"
                                            required>
                                            <option value="pending" {{ $damagedGood->status == 'pending' ? 'selected' : '' }}>Chờ Duyệt</option>
                                            <option value="approved" {{ $damagedGood->status == 'approved' ? 'selected' : '' }}>Đồng ý Phê Duyệt</option>
                                            <option value="rejected" {{ $damagedGood->status == 'rejected' ? 'selected' : '' }}>Từ Chối Báo Cáo</option>
                                            <option value="processed" {{ $damagedGood->status == 'processed' ? 'selected' : '' }}>Xác Nhận Đã Xử Lý</option>
                                        </select>
                                    </div>
                                    <div>
                                        <textarea name="solution" id="solution" rows="4" placeholder="Nhập hướng xử lý chi tiết tại đây..."
                                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-4 focus:ring-primary/10 bg-white transition-all shadow-sm">{{ $damagedGood->solution }}</textarea>
                                    </div>
                                    <button type="submit"
                                        class="w-full px-4 py-3.5 bg-primary hover:bg-primary-dark text-white rounded-xl transition-all text-sm font-black shadow-lg shadow-primary/30 flex items-center justify-center gap-2 active:scale-95">
                                        <i class="fas fa-save"></i> LƯU THAY ĐỔI
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="text-center p-6 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-lock text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-xs text-gray-500 italic font-medium leading-relaxed">Báo cáo đã ở trạng thái chốt hoặc đang xử lý. Mọi thay đổi đều được ghi lại trong nhật ký hệ thống.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection