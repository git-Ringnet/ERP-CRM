@extends('layouts.app')

@section('title', 'Đơn mua hàng')
@section('page-title', 'Quản lý đơn mua hàng (PO)')

@section('content')
<div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Chờ đặt</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Đã đặt</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['sent'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Đã về – đủ hàng</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['received'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Tổng giá trị PO</p>
            <p class="text-2xl font-bold text-primary">${{ number_format($stats['total_value'], 2) }}</p>

        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-gray-600">Quản lý đơn đặt hàng gửi cho nhà cung cấp</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('purchase-orders.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-emerald-600">
                <i class="fas fa-file-excel mr-2"></i> Xuất Excel
            </a>
            {{-- <a href="{{ route('purchase-orders.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                <i class="fas fa-plus mr-2"></i> Tạo PO
            </a> --}}
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 border-b border-gray-200 bg-gray-50">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm..." 
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Chờ đặt</option>
                <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>Chờ duyệt</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã đặt</option>
                <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang về</option>
                <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Đã về – đủ hàng</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>

            </select>
            <select name="supplier_id" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                <option value="">-- Tất cả NCC --</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                <i class="fas fa-search mr-2"></i> Lọc
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã PO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã SO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người tạo (Sales)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày đặt hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày giao hàng dự kiến</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                    @php
                        // Calculate order duration and status
                        $orderDate = $order->order_date;
                        $daysElapsed = $orderDate->diffInDays(now());
                        $weeksElapsed = floor($daysElapsed / 7);
                        
                        // Expected arrival: 4-6 weeks if not set
                        $expectedMinDate = $orderDate->copy()->addWeeks(4);
                        $expectedMaxDate = $orderDate->copy()->addWeeks(6);
                        
                        // Status indicators
                        $isOverdue = $order->expected_delivery && now()->startOfDay()->gt($order->expected_delivery) && $order->status !== 'received' && $order->status !== 'cancelled';
                        $isNearDelivery = $order->expected_delivery && now()->startOfDay()->diffInDays($order->expected_delivery, false) <= 7 && now()->startOfDay()->diffInDays($order->expected_delivery, false) >= 0 && $order->status !== 'received' && $order->status !== 'cancelled';
                        $isLongWaiting = $daysElapsed > 42 && $order->status !== 'received' && $order->status !== 'cancelled';
                        
                        // Row class
                        $rowClass = $isOverdue ? 'bg-red-50 hover:bg-red-100' : ($isLongWaiting ? 'bg-orange-50 hover:bg-orange-100' : ($isNearDelivery ? 'bg-green-50 hover:bg-green-100' : 'hover:bg-gray-50'));
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="px-4 py-3 font-medium text-primary">
                            <a href="{{ route('purchase-orders.show', $order) }}" class="hover:underline">{{ $order->code }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $order->linked_so_codes }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $order->linked_salesperson_names }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $order->order_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <input type="date" 
                                value="{{ $order->expected_delivery ? $order->expected_delivery->format('Y-m-d') : '' }}"
                                class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-blue-500 focus:border-blue-500 bg-transparent update-expected-delivery"
                                data-id="{{ $order->id }}">
                        </td>
                        <td class="px-4 py-3 text-right font-medium">
                            @php 
                                $val = $order->total_foreign ?? ($order->total / ($order->exchange_rate ?: 1));
                                $decimals = (floor($val) == $val) ? 0 : ($order->currency->decimal_places ?? 2);
                            @endphp
                            <div class="text-gray-900 font-semibold">${{ number_format($val, $decimals) }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php
                                $statuses = [
                                    'pending' => 'Chờ đặt', 
                                    'approved' => 'Đã đặt', 
                                    'shipping' => 'Đang về', 
                                    'received' => 'Đã về – đủ hàng'
                                ];
                                $currentKey = 'pending';
                                if (in_array($order->status, ['draft', 'pending_approval'])) $currentKey = 'pending';
                                elseif ($order->status == 'approved') $currentKey = 'approved';
                                elseif (in_array($order->status, ['shipping', 'partial_received'])) $currentKey = 'shipping';
                                elseif ($order->status == 'received') $currentKey = 'received';
                                elseif ($order->status == 'cancelled') $currentKey = 'cancelled';
                                
                                $currentIndex = array_search($currentKey, array_keys($statuses));
                                if ($currentIndex === false && $order->status !== 'cancelled') $currentIndex = 0;
                            @endphp

                            @if($order->status === 'cancelled')
                                <div class="text-center">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-700 uppercase">
                                        <i class="fas fa-times-circle mr-1"></i>Hủy đơn
                                    </span>
                                </div>
                            @else
                                <div class="flex items-center justify-center mb-1">
                                    @foreach($statuses as $key => $label)
                                        @php $idx = array_search($key, array_keys($statuses)); @endphp
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 rounded-full {{ $idx <= $currentIndex ? ($order->is_hold && $idx == $currentIndex ? 'bg-orange-500 animate-pulse ring-2 ring-orange-200' : 'bg-primary shadow-sm') : 'bg-gray-200' }}" 
                                                title="{{ $label }}"></div>
                                            @if(!$loop->last)
                                                <div class="w-4 h-0.5 {{ $idx < $currentIndex ? 'bg-primary' : 'bg-gray-100' }}"></div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div class="text-center">
                                    @if($order->is_hold)
                                        <span class="text-[10px] font-bold text-orange-600 uppercase tracking-tighter animate-pulse flex items-center justify-center">
                                            <i class="fas fa-pause-circle mr-1"></i>HOLD
                                        </span>
                                    @elseif($order->status == 'pending_approval')
                                        <span class="text-[10px] font-bold text-yellow-600 uppercase tracking-tighter flex items-center justify-center">
                                            <i class="fas fa-hourglass-half mr-1"></i>Chờ đặt
                                        </span>
                                    @else
                                        <span class="text-[10px] font-bold {{ $currentIndex == 0 ? 'text-yellow-600' : ($currentIndex == 1 ? 'text-blue-600' : ($currentIndex == 2 ? 'text-purple-600' : 'text-green-600')) }} uppercase tracking-tighter">
                                            {{ $order->status_label }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <a href="{{ route('purchase-orders.show', $order) }}" class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all" title="Xem">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(in_array($order->status, ['draft', 'pending_approval']))
                                    <a href="{{ route('purchase-orders.edit', $order) }}" class="inline-flex items-center justify-center w-8 h-8 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                @if($order->status == 'draft')
                                    <form action="{{ route('purchase-orders.submit-approval', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-orange-100 text-orange-600 rounded-lg hover:bg-orange-200 transition-all" title="Gửi yêu cầu duyệt">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($order->status == 'pending_approval')
                                    <form action="{{ route('purchase-orders.approve', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-all shadow-sm hover:shadow-md transform hover:scale-110" title="Duyệt: Xác nhận Đã đặt">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endif

                                @if($order->status == 'approved')
                                    <form action="{{ route('purchase-orders.ship', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-all shadow-sm hover:shadow-md transform hover:scale-110" title="Chuyển sang Đang về">
                                            <i class="fas fa-truck-moving"></i>
                                        </button>
                                    </form>
                                @endif

                                @if(in_array($order->status, ['shipping', 'partial_received']))
                                    <form action="{{ route('purchase-orders.receive-all', $order) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-all shadow-sm hover:shadow-md transform hover:scale-110" title="Xác nhận Đã về (Nhận hết)">
                                            <i class="fas fa-box-open"></i>
                                        </button>
                                    </form>
                                @endif

                                @if(in_array($order->status, ['approved', 'shipping', 'partial_received']))
                                    <form action="{{ route('purchase-orders.toggle-hold', $order) }}" method="POST" class="inline" id="hold-form-{{ $order->id }}">
                                        @csrf
                                        <input type="hidden" name="hold_reason" id="hold_reason_{{ $order->id }}">
                                        @if($order->is_hold)
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-all shadow-sm transform hover:scale-110" title="Gỡ Hold">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        @else
                                            <button type="button" onclick="const reason = prompt('Nhập lý do Hold đơn hàng:'); if(reason !== null) { document.getElementById('hold_reason_{{ $order->id }}').value = reason; document.getElementById('hold-form-{{ $order->id }}').submit(); }"
                                                class="inline-flex items-center justify-center w-8 h-8 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-all shadow-sm transform hover:scale-110" title="Hold đơn hàng">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        @endif
                                    </form>
                                @endif

                                <a href="{{ route('purchase-orders.print', $order) }}" class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-all" title="In" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>

                                @if(!in_array($order->status, ['received', 'cancelled']))
                                    <form action="{{ route('purchase-orders.cancel', $order) }}" method="POST" class="inline delete-form">
                                        @csrf
                                        <button type="button" class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-all" 
                                            title="Hủy đơn" 
                                            onclick="confirmAction(this.parentElement, 'Xác nhận hủy', 'Bạn có chắc chắn muốn hủy đơn hàng này không?', 'warning', 'Hủy ngay', '#95a5a6')">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                @endif

                                @if(in_array($order->status, ['draft', 'cancelled']) && auth()->user()->can('delete', $order))
                                    <form action="{{ route('purchase-orders.destroy', $order) }}" method="POST" class="inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="inline-flex items-center justify-center w-8 h-8 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all" 
                                            title="Xóa"
                                            onclick="confirmDelete(this.parentElement, 'đơn hàng')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">Chưa có đơn mua hàng nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('.update-expected-delivery');
        
        let activeRequests = {};
        let debounceTimers = {};
        
        function saveDate(input) {
            const poId = input.dataset.id;
            const date = input.value;
            const originalValue = input.defaultValue;
            
            // If the value hasn't changed from the last saved default value, do nothing
            if (date === originalValue) {
                return;
            }
            
            // Clear any pending debounce timer for this input
            if (debounceTimers[poId]) {
                clearTimeout(debounceTimers[poId]);
                delete debounceTimers[poId];
            }
            
            // Abort any ongoing fetch request for this input
            if (activeRequests[poId]) {
                activeRequests[poId].abort();
            }
            
            // Create a new AbortController for this request
            const controller = new AbortController();
            activeRequests[poId] = controller;
            
            // Show loading state (opacity) without disabling, so it doesn't lose focus
            input.classList.add('opacity-50');
            
            fetch(`/purchase-orders/${poId}/update-expected-delivery`, {
                method: 'POST',
                signal: controller.signal,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    expected_delivery: date
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.defaultValue = date;
                    if (window.Toast) {
                        Toast.fire({
                            icon: 'success',
                            title: 'Cập nhật ngày giao hàng thành công'
                        });
                    }
                } else {
                    alert('Có lỗi xảy ra khi cập nhật ngày giao hàng');
                    input.value = originalValue;
                }
            })
            .catch(error => {
                if (error.name === 'AbortError') {
                    return; // Request was aborted, do nothing
                }
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật ngày giao hàng');
                input.value = originalValue;
            })
            .finally(() => {
                if (activeRequests[poId] === controller) {
                    input.classList.remove('opacity-50');
                    delete activeRequests[poId];
                }
            });
        }
        
        dateInputs.forEach(input => {
            // When the user leaves the input (blur), save immediately
            input.addEventListener('blur', function() {
                saveDate(this);
            });
            
            // Save immediately on pressing Enter
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    this.blur(); // Triggers blur which will save immediately
                }
            });
        });
    });
</script>
@endpush
@endsection
