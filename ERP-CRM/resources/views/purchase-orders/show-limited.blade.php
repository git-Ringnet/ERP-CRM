@extends('layouts.app')

{{--
    Bản PO rút gọn cho Sales: chỉ trạng thái, mốc thời gian, S/N và license của các dòng
    thuộc SO mà người xem được phép xem. KHÔNG hiển thị giá mua, tổng tiền, VAT, chiết khấu,
    ghi chú nội bộ hay file đính kèm của PO (thông tin nội bộ PO Team).
    Chỉ dùng các field liệt kê ở đây - đừng thêm field giá vào view này.
--}}

@section('title', 'Đơn mua hàng ' . $purchaseOrder->code)
@section('page-title', 'Đơn mua hàng: ' . $purchaseOrder->code)

@section('content')
@php
    $fmtDate = fn ($d) => $d ? $d->format('d/m/Y') : '--';
@endphp

<div class="space-y-4">
    <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-lg px-4 py-3 flex items-start gap-2">
        <i class="fas fa-info-circle mt-0.5"></i>
        <span>Bạn đang xem bản rút gọn của PO: trạng thái, mốc thời gian, S/N và license của các dòng hàng thuộc đơn bán của bạn.</span>
    </div>

    {{-- Thông tin chung --}}
    <div class="bg-white rounded-lg shadow-sm p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ $purchaseOrder->code }}</h2>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $purchaseOrder->status_color }}">
                {{ $purchaseOrder->is_hold ? 'Tạm hoãn (Hold)' : $purchaseOrder->status_label }}
            </span>
        </div>

        <dl class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase">Nhà cung cấp</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $purchaseOrder->supplier->name ?? '--' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase">Ngày đặt hàng</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $fmtDate($purchaseOrder->order_date) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase">ETD (Hãng xuất)</dt>
                <dd class="font-medium text-purple-700 mt-0.5">{{ $fmtDate($purchaseOrder->manufacturer_release_date) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase">ETA (Dự kiến về VN)</dt>
                <dd class="font-medium text-blue-700 mt-0.5">{{ $fmtDate($purchaseOrder->expected_arrival_date) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase">Giao hàng dự kiến</dt>
                <dd class="font-medium text-gray-900 mt-0.5">{{ $fmtDate($purchaseOrder->expected_delivery) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold text-gray-500 uppercase">Về kho thực tế</dt>
                <dd class="font-medium text-green-700 mt-0.5">{{ $fmtDate($purchaseOrder->actual_delivery) }}</dd>
            </div>
        </dl>

        @if($purchaseOrder->is_hold && $purchaseOrder->hold_reason)
            <div class="mt-4 bg-orange-50 border border-orange-200 text-orange-800 text-sm rounded-lg px-3 py-2">
                <span class="font-bold"><i class="fas fa-pause-circle mr-1"></i>Lý do tạm hoãn:</span>
                {{ $purchaseOrder->hold_reason }}
            </div>
        @endif
    </div>

    {{-- Dòng hàng thuộc SO của bạn --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-3 border-b border-gray-200">
            <h3 class="text-base font-semibold text-gray-900">Sản phẩm ({{ $items->count() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Đơn bán</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">SL đặt</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Đã về</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">S/N</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">License</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($items as $item)
                        @php
                            $sale = $item->saleOrderRequestItem?->saleOrderRequest?->sale ?: $purchaseOrder->sale;

                            $serials = json_decode((string) $item->serial_number, true);
                            $serials = is_array($serials)
                                ? array_values(array_filter(array_map(fn ($s) => trim((string) $s), $serials), 'strlen'))
                                : [];

                            $licenseFiles = [];
                            if ($item->license_file) {
                                $decoded = json_decode($item->license_file, true);
                                $licenseFiles = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [$item->license_file];
                            }

                            $itemStatusClass = match ($item->status) {
                                'received' => 'bg-green-100 text-green-800',
                                'shipping' => 'bg-purple-100 text-purple-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                default => 'bg-yellow-100 text-yellow-800',
                            };
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3 text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $item->product_name ?: ($item->product->name ?? '--') }}</div>
                                @if($item->product?->code)
                                    <div class="text-xs text-gray-500 font-mono">{{ $item->product->code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($sale)
                                    <a href="{{ route('sales.show', $sale->id) }}" class="text-blue-600 hover:underline font-medium">{{ $sale->code }}</a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->quantity) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item->received_quantity ?? 0) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $itemStatusClass }}">{{ $item->status_label }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if(count($serials))
                                    <div class="text-xs text-gray-500 mb-0.5">{{ count($serials) }}/{{ number_format($item->quantity) }} S/N</div>
                                    <div class="font-mono text-xs text-purple-900 break-all leading-snug max-w-xs">{{ implode(', ', $serials) }}</div>
                                @else
                                    <span class="text-gray-400 text-xs">Chưa có</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @forelse($licenseFiles as $index => $file)
                                    <div class="flex items-center gap-2 text-xs mb-1">
                                        <a href="javascript:void(0)"
                                           onclick="openFilePreviewModal('{{ route('purchase-orders.items.preview-license', [$item->id, $index]) }}', '{{ addslashes(basename($file)) }}')"
                                           class="text-blue-600 hover:underline truncate max-w-[160px]" title="{{ basename($file) }}">
                                            <i class="fas fa-file-alt mr-1"></i>{{ basename($file) }}
                                        </a>
                                        <a href="{{ route('purchase-orders.items.download-license', [$item->id, $index]) }}"
                                           class="text-gray-500 hover:text-gray-800" title="Tải về">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                @empty
                                    <span class="text-gray-400 text-xs">Chưa có</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
