@extends('layouts.app')
@section('title', 'Marketing Events & Funds')
@section('page-title', 'Quản lý sự kiện Marketing & Quỹ Hãng')

@section('content')
<style>
    [x-cloak] { display: none !important; }
</style>
@php
    $user = auth()->user();
    $isSuperOrMktOrOMOrBOD = $user->hasRole('super_admin') || $user->hasRole('marketing') || $user->hasRole('order_management') || $user->hasRole('director') || $user->hasRole('accountant');
    $currentTab = request('tab', 'events');
@endphp

<div class="space-y-4" x-data="{ showAddFundModal: false }">
    {{-- Tabs Navigation --}}
    @if($isSuperOrMktOrOMOrBOD)
    <div class="bg-white rounded-lg shadow-sm p-2 flex border-b border-gray-100">
        <a href="{{ route('marketing-events.index', ['tab' => 'events']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all {{ $currentTab === 'events' ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:text-purple-600 hover:bg-gray-50' }}">
            <i class="fas fa-calendar-alt text-base"></i> Sự kiện Marketing
        </a>
        <a href="{{ route('marketing-events.index', ['tab' => 'funds']) }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg transition-all {{ $currentTab === 'funds' ? 'bg-purple-50 text-purple-700' : 'text-gray-500 hover:text-purple-600 hover:bg-gray-50' }}">
            <i class="fas fa-wallet text-base"></i> Quản lý Quỹ Hãng & Công nợ
        </a>
    </div>
    @endif

    @if($currentTab === 'events')
        {{-- Header for Events --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-calendar-alt text-purple-500 mr-2"></i>Danh sách sự kiện Marketing
                </h2>
                <div class="flex flex-wrap gap-2">
                    <form action="{{ route('marketing-events.index') }}" method="GET" class="flex gap-2">
                        <input type="hidden" name="tab" value="events">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm..."
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                        <select name="status" onchange="this.form.submit()"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <option value="">Tất cả trạng thái</option>
                            <option value="draft"    {{ request('status') === 'draft'    ? 'selected' : '' }}>Nháp</option>
                            <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Chờ duyệt</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Từ chối</option>
                        </select>
                    </form>
                    @can('create_marketing_events')
                    <a href="{{ route('marketing-events.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                        <i class="fas fa-plus mr-2"></i> Tạo sự kiện
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Events Table --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sự kiện</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Địa điểm</th>
                            @if($isSuperOrMktOrOMOrBOD)
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">NS dự toán</th>
                            @endif
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">KH mời</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Người tạo</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($events as $event)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('marketing-events.show', $event) }}" class="font-medium text-purple-600 hover:underline">
                                    {{ $event->title }}
                                </a>
                                @if($event->description)
                                <div class="text-xs text-gray-500 truncate max-w-xs">{{ $event->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $event->event_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $event->location ?? '—' }}</td>
                            @if($isSuperOrMktOrOMOrBOD)
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($event->budget) }} đ</td>
                            @endif
                            <td class="px-4 py-3 text-center text-sm">{{ $event->customers_count ?? $event->customers->count() }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $event->status_color }}">
                                    {{ $event->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-gray-600">{{ $event->creator->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('marketing-events.show', $event) }}" 
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition-colors" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @php
                                        $canApprove = false;
                                        if ($mktWorkflow && $event->status === 'pending') {
                                            $pendingHist = $event->approvalHistories->where('action', 'pending')->sortBy('level')->first();
                                            if ($pendingHist) {
                                                $level = $mktWorkflow->levels->where('level', $pendingHist->level)->first();
                                                $canApprove = $level?->canApprove(auth()->user(), (float)$event->budget) ?? false;
                                            }
                                        }
                                    @endphp

                                    @if($canApprove)
                                    <div class="flex items-center gap-1">
                                        <form action="{{ route('marketing-events.approve', $event) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                onclick="return confirm('Duyệt ngân sách sự kiện này?')"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors" 
                                                title="Duyệt nhanh">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('marketing-events.show', $event) }}?reject=1" 
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors" 
                                            title="Từ chối">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                    @endif

                                    @if($event->isEditable())
                                    <a href="{{ route('marketing-events.edit', $event) }}" 
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors" 
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif

                                    @if($event->isEditable() || $event->status === 'cancelled')
                                    <form action="{{ route('marketing-events.destroy', $event) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                            onclick="return confirm('Xóa sự kiện này?')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors" 
                                            title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Chưa có sự kiện nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $events->links() }}</div>
        </div>
    @elseif($currentTab === 'funds' && $isSuperOrMktOrOMOrBOD)
        {{-- Funds statistics cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-4 border border-purple-100">
                <div class="text-xs font-bold text-gray-400 uppercase">Tổng quỹ đã nhận</div>
                <div class="text-2xl font-black text-purple-700 mt-1">
                    {{ number_format($supplierFunds->sum('amount')) }} đ
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-emerald-100">
                <div class="text-xs font-bold text-gray-400 uppercase">Đã sử dụng thực tế</div>
                <div class="text-2xl font-black text-emerald-700 mt-1">
                    {{ number_format($supplierFunds->sum('used_amount')) }} đ
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-blue-100">
                <div class="text-xs font-bold text-gray-400 uppercase">Số dư còn lại</div>
                <div class="text-2xl font-black text-blue-700 mt-1">
                    {{ number_format($supplierFunds->sum('remaining_amount')) }} đ
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-red-100">
                <div class="text-xs font-bold text-gray-400 uppercase">Công nợ hãng chờ thu</div>
                <div class="text-2xl font-black text-red-700 mt-1">
                    {{ number_format($transactions->where('type', 'receivable')->where('status', 'pending')->sum('amount')) }} đ
                </div>
            </div>
        </div>

        {{-- Funds Management block --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-md font-bold text-gray-800">
                    <i class="fas fa-wallet text-purple-500 mr-2"></i>Quản lý Nguồn Quỹ từ Hãng
                </h3>
                @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing'))
                <button @click="showAddFundModal = true"
                    class="inline-flex items-center px-3.5 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-xs font-bold shadow-sm">
                    <i class="fas fa-plus mr-1.5"></i> Khai báo quỹ mới
                </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Hãng</th>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Tên Quỹ Hỗ Trợ</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Thời gian</th>
                            <th class="px-4 py-2.5 text-right font-bold text-gray-600">Tổng Tiền Quỹ</th>
                            <th class="px-4 py-2.5 text-right font-bold text-gray-600">Đã Dùng</th>
                            <th class="px-4 py-2.5 text-right font-bold text-gray-600">Số Dư Còn Lại</th>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($supplierFunds as $fund)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-2.5 font-semibold text-gray-900">{{ $fund->supplier->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-purple-700 font-medium">{{ $fund->name }}</td>
                            <td class="px-4 py-2.5 text-center text-gray-600">{{ $fund->quarter }} - {{ $fund->year }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-gray-800">{{ number_format($fund->amount) }} đ</td>
                            <td class="px-4 py-2.5 text-right text-red-600">{{ number_format($fund->used_amount) }} đ</td>
                            <td class="px-4 py-2.5 text-right text-blue-600 font-bold">{{ number_format($fund->remaining_amount) }} đ</td>
                            <td class="px-4 py-2.5 text-gray-500 text-xs truncate max-w-xs">{{ $fund->note ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Chưa khai báo nguồn quỹ nào của hãng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Supplier Debt Ledger --}}
        <div class="bg-white rounded-lg shadow-sm p-4">
            <h3 class="text-md font-bold text-gray-800 mb-3">
                <i class="fas fa-hand-holding-usd text-red-500 mr-2"></i>Theo dõi Công nợ Hãng & Thu hồi
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Hãng</th>
                            <th class="px-4 py-2.5 text-left font-bold text-gray-600">Nội dung công nợ / Sự kiện</th>
                            <th class="px-4 py-2.5 text-right font-bold text-gray-600">Số tiền hỗ trợ</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Loại giao dịch</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Trạng thái</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Ngày ghi nhận</th>
                            <th class="px-4 py-2.5 text-center font-bold text-gray-600">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($transactions->whereIn('type', ['receivable', 'collected']) as $tx)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-2.5 font-semibold text-gray-900">{{ $tx->supplier->name ?? '—' }}</td>
                            <td class="px-4 py-2.5">
                                <div>{{ $tx->note }}</div>
                                @if($tx->event)
                                <div class="text-[10px] text-purple-500 font-medium">Sự kiện: {{ $tx->event->title }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right font-semibold text-gray-800">{{ number_format($tx->amount) }} đ</td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $tx->type === 'receivable' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }}">
                                    {{ $tx->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                @if($tx->type === 'receivable')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $tx->status === 'collected' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $tx->status === 'collected' ? 'Đã thu nợ' : 'Hãng chưa trả' }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-800">Hoàn tất</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center text-gray-500 text-xs">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2.5 text-center">
                                @if($tx->type === 'receivable' && $tx->status === 'pending')
                                    @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('marketing') || auth()->user()->hasRole('accountant'))
                                    <form action="{{ route('marketing-events.transactions.collect', $tx) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Xác nhận hãng đã thanh toán khoản tiền hỗ trợ này?')"
                                            class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-bold transition-all shadow-sm">
                                            <i class="fas fa-check-double mr-1"></i> Xác nhận đã thu
                                        </button>
                                    </form>
                                    @endif
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Không có lịch sử công nợ hãng phát sinh.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add Fund Modal --}}
        <div x-show="showAddFundModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto p-4" x-transition>
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100" @click.away="showAddFundModal = false">
                <div class="bg-purple-700 px-4 py-3 flex justify-between items-center">
                    <h4 class="text-white font-bold text-sm">Khai báo Nguồn Quỹ Mới của Hãng</h4>
                    <button @click="showAddFundModal = false" class="text-white hover:text-purple-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="{{ route('marketing-events.funds.store') }}" method="POST" class="p-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Chọn Hãng cấp quỹ <span class="text-red-500">*</span></label>
                        <select name="supplier_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 bg-white">
                            <option value="">-- Chọn nhà cung cấp / Hãng --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tên chương trình quỹ / Tên quỹ <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="Ví dụ: Fortinet MDF Q3-2026, Cisco Support"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Chọn Quý <span class="text-red-500">*</span></label>
                            <select name="quarter" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 bg-white">
                                <option value="Q1">Quý 1 (Q1)</option>
                                <option value="Q2">Quý 2 (Q2)</option>
                                <option value="Q3">Quý 3 (Q3)</option>
                                <option value="Q4">Quý 4 (Q4)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Năm <span class="text-red-500">*</span></label>
                            <input type="number" name="year" value="{{ date('Y') }}" required min="2020" max="2100"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Số tiền quỹ cấp (VND) <span class="text-red-500">*</span></label>
                        <input type="text" name="amount" required placeholder="Bằng số, VD: 150000000"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Ghi chú</label>
                        <textarea name="note" rows="3" placeholder="Ghi chú về điều kiện chi tiêu quỹ, chính sách..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button type="button" @click="showAddFundModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-xs font-bold">Huỷ</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-xs font-bold">Khai báo</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
