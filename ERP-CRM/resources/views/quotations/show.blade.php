@extends('layouts.app')

@section('title', 'Chi tiết báo giá - ' . $quotation->code)
@section('page-title', 'Chi tiết báo giá: ' . $quotation->code)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Quotation Info -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Thông tin báo giá</h3>
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $quotation->status_color }}">
                    {{ $quotation->status_label }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Mã báo giá:</span>
                    <span class="font-medium ml-2">{{ $quotation->code }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Ngày tạo:</span>
                    <span class="font-medium ml-2">{{ $quotation->date->format('d/m/Y') }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Khách hàng:</span>
                    <span class="font-medium ml-2">{{ $quotation->customer_name }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Hạn báo giá:</span>
                    <span class="font-medium ml-2 {{ $quotation->isExpired() ? 'text-red-600' : '' }}">
                        {{ $quotation->valid_until->format('d/m/Y') }}
                        @if($quotation->isExpired())
                            <i class="fas fa-exclamation-circle ml-1" title="Đã hết hạn"></i>
                        @endif
                    </span>
                </div>
                <div class="col-span-2">
                    <span class="text-gray-500">Tiêu đề:</span>
                    <span class="font-medium ml-2">{{ $quotation->title }}</span>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Chi tiết sản phẩm</h3>
            
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($quotation->items as $index => $item)
                        <tr>
                            <td class="px-4 py-3 text-center text-sm">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                                @if($item->product_code)
                                    <div class="text-xs text-gray-500">{{ $item->product_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format($item->total, 0, ',', '.') }} đ</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden space-y-3">
                @foreach($quotation->items as $index => $item)
                <div class="bg-gray-50 p-3 rounded-lg">
                    <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                    <div class="text-sm text-gray-500 mt-1">
                        SL: {{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }} đ
                    </div>
                    <div class="text-sm font-medium text-right mt-2">
                        = {{ number_format($item->total, 0, ',', '.') }} đ
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Totals -->
            <div class="mt-4 border-t pt-4">
                <div class="flex justify-end">
                    <div class="w-full md:w-64 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tổng tiền hàng:</span>
                            <span>{{ number_format($quotation->subtotal, 0, ',', '.') }} đ</span>
                        </div>
                        @if($quotation->discount > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Chiết khấu ({{ $quotation->discount }}%):</span>
                            <span>-{{ number_format($quotation->subtotal * $quotation->discount / 100, 0, ',', '.') }} đ</span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-500">VAT ({{ $quotation->vat }}%):</span>
                            <span>{{ number_format(($quotation->subtotal - $quotation->subtotal * $quotation->discount / 100) * $quotation->vat / 100, 0, ',', '.') }} đ</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg border-t pt-2">
                            <span>Tổng cộng:</span>
                            <span class="text-primary">{{ number_format($quotation->total, 0, ',', '.') }} đ</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms -->
        @if($quotation->payment_terms || $quotation->delivery_time || $quotation->note)
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Điều khoản & Ghi chú</h3>
            <div class="space-y-3 text-sm">
                @if($quotation->payment_terms)
                <div>
                    <span class="text-gray-500 font-medium">Điều khoản thanh toán:</span>
                    <p class="mt-1">{{ $quotation->payment_terms }}</p>
                </div>
                @endif
                @if($quotation->delivery_time)
                <div>
                    <span class="text-gray-500 font-medium">Thời gian giao hàng:</span>
                    <p class="mt-1">{{ $quotation->delivery_time }}</p>
                </div>
                @endif
                @if($quotation->note)
                <div>
                    <span class="text-gray-500 font-medium">Ghi chú:</span>
                    <p class="mt-1">{{ $quotation->note }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Actions -->
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Thao tác</h3>
            <div class="space-y-3">
                @if($quotation->status === 'draft')
                    <a href="{{ route('quotations.edit', $quotation) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                        <i class="fas fa-edit mr-2"></i> Chỉnh sửa
                    </a>
                    <form action="{{ route('quotations.submit', $quotation) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                            <i class="fas fa-paper-plane mr-2"></i> Gửi duyệt
                        </button>
                    </form>
                @endif

                @if($quotation->status === 'pending')
                    <form action="{{ route('quotations.approve', $quotation) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-check mr-2"></i> Duyệt
                        </button>
                    </form>
                    <button onclick="showRejectModal()" class="w-full inline-flex items-center justify-center px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors">
                        <i class="fas fa-times mr-2"></i> Từ chối
                    </button>
                @endif

                @if($quotation->status === 'approved')
                    <form action="{{ route('quotations.send', $quotation) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="fas fa-envelope mr-2"></i> Đánh dấu đã gửi khách
                        </button>
                    </form>
                @endif

                @if(in_array($quotation->status, ['approved', 'sent']))
                    <form action="{{ route('quotations.response', $quotation) }}" method="POST" class="flex gap-2">
                        @csrf
                        <button type="submit" name="response" value="accepted" class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-success text-white rounded-lg hover:bg-green-600 text-sm transition-colors">
                            <i class="fas fa-thumbs-up mr-1"></i> KH Chấp nhận
                        </button>
                        <button type="submit" name="response" value="declined" class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-danger text-white rounded-lg hover:bg-red-600 text-sm transition-colors">
                            <i class="fas fa-thumbs-down mr-1"></i> KH Từ chối
                        </button>
                    </form>
                @endif

                @if($quotation->canConvertToSale())
                    <form action="{{ route('quotations.convert', $quotation) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                            <i class="fas fa-exchange-alt mr-2"></i> Chuyển thành đơn hàng
                        </button>
                    </form>
                @endif

                <a href="{{ route('quotations.print', $quotation) }}" target="_blank" 
                   class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-print mr-2"></i> In báo giá
                </a>

                @if(in_array($quotation->status, ['draft', 'rejected']))
                    <form action="{{ route('quotations.destroy', $quotation) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa báo giá này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                            <i class="fas fa-trash mr-2"></i> Xóa báo giá
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Approval History -->
        @if($workflow && count($approvalHistories) > 0)
        <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Lịch sử duyệt</h3>
            <div class="space-y-4">
                @foreach($approvalHistories as $history)
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        @if($history->action === 'approved')
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                        @elseif($history->action === 'rejected')
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-times text-red-600"></i>
                            </div>
                        @else
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-yellow-600"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-sm">Cấp {{ $history->level }}: {{ $history->level_name }}</div>
                        <div class="text-xs text-gray-500">
                            @if($history->action === 'pending')
                                Đang chờ duyệt
                            @else
                                {{ $history->approver_name }} - {{ $history->action === 'approved' ? 'Đã duyệt' : 'Từ chối' }}
                                @if($history->action_at)
                                    <br>{{ \Carbon\Carbon::parse($history->action_at)->format('d/m/Y H:i') }}
                                @endif
                            @endif
                        </div>
                        @if($history->comment)
                            <div class="text-xs text-gray-600 mt-1 italic">"{{ $history->comment }}"</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Converted Sale -->
        @if($quotation->converted_to_sale_id)
        <div class="bg-green-50 rounded-lg shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-green-800 mb-2">Đã chuyển đơn hàng</h3>
            <a href="{{ route('sales.show', $quotation->converted_to_sale_id) }}" class="text-green-600 hover:text-green-800 font-medium">
                <i class="fas fa-external-link-alt mr-1"></i> Xem đơn hàng
            </a>
        </div>
        @endif

        <!-- Back Button -->
        <a href="{{ route('quotations.index') }}" 
           class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Quay lại danh sách
        </a>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Từ chối báo giá</h3>
        <form action="{{ route('quotations.reject', $quotation) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Lý do từ chối <span class="text-red-500">*</span></label>
                <textarea name="comment" rows="3" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Nhập lý do từ chối..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="hideRejectModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Hủy</button>
                <button type="submit" class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-600">Từ chối</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}
function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}
</script>
@endpush
@endsection
