{{--
    Gắn / bỏ gắn 1 dòng PO với dòng Yêu cầu đặt hàng của SO liên kết.
    Dùng cho PO tạo tay (vd. đặt qua NCC khác hãng ghi trong yêu cầu): nếu không gắn, dòng yêu cầu
    vẫn bị tính là "còn cần đặt" và SO vẫn hiện ở Gom đơn cần đặt.
    Tham số: $item, $purchaseOrder, $canEditItems, $requestItemOptions
--}}
@php
    $linkedReq = $item->saleOrderRequestItem;
@endphp

@if($linkedReq)
    <div class="mt-1 text-[10px] text-gray-500 flex flex-wrap items-center gap-1">
        <i class="fas fa-link text-teal-500"></i>
        <span>Gắn yêu cầu: <span class="font-semibold text-gray-700">{{ $linkedReq->saleOrderRequest?->code ?? '#' . $linkedReq->id }}</span> · {{ $linkedReq->part_number }}</span>
        @php
            $linkedStatus = $linkedReq->saleOrderRequest?->status;
            $linkedStatusClass = match ($linkedStatus) {
                \App\Models\SaleOrderRequest::STATUS_PENDING_ADMIN => 'bg-yellow-100 text-yellow-800',
                \App\Models\SaleOrderRequest::STATUS_DRAFT => 'bg-gray-100 text-gray-700',
                \App\Models\SaleOrderRequest::STATUS_NEED_INFO => 'bg-orange-100 text-orange-800',
                \App\Models\SaleOrderRequest::STATUS_COMPLETED => 'bg-green-100 text-green-800',
                default => 'bg-purple-100 text-purple-800',
            };
        @endphp
        @if($linkedStatus)
            <span class="px-1.5 py-0.5 rounded font-bold uppercase {{ $linkedStatusClass }}">{{ $linkedReq->saleOrderRequest->status_label }}</span>
        @endif
        @if($canEditItems && $purchaseOrder->status !== 'cancelled')
            <form method="POST" action="{{ route('purchase-orders.items.unlink-request-item', $item) }}" class="inline"
                  onsubmit="return confirm('Bỏ gắn dòng {{ addslashes($item->product_name) }} khỏi yêu cầu {{ addslashes($linkedReq->saleOrderRequest?->code ?? '') }}?\n\nDòng yêu cầu sẽ quay lại Gom đơn cần đặt như chưa đặt hàng. Chỉ dùng khi gắn nhầm.');">
                @csrf
                <button type="submit" class="text-red-500 hover:text-red-700 underline">Bỏ gắn</button>
            </form>
        @endif
    </div>
@elseif($canEditItems && $purchaseOrder->status !== 'cancelled')
    <div class="mt-1.5 p-2 rounded border border-amber-200 bg-amber-50 text-[10px] text-amber-900">
        <div class="font-bold mb-1"><i class="fas fa-unlink mr-1"></i>Chưa gắn dòng yêu cầu của Sales</div>
        @if(! $purchaseOrder->sale_id)
            <div>Chọn "Đơn bán hàng liên kết" cho PO trước để gắn dòng này vào yêu cầu của SO.</div>
        @elseif($requestItemOptions->isEmpty())
            <div>SO liên kết không còn dòng yêu cầu nào cần đặt.</div>
        @else
            @php
                $itemQty = (float) ($item->ordered_quantity ?: $item->quantity);
                $norm = fn ($v) => strtoupper(preg_replace('/\s+/', '', (string) $v));
                $suggested = $requestItemOptions->first(fn ($o) => $norm($o->part_number) === $norm($item->product_name));
            @endphp
            <form method="POST" action="{{ route('purchase-orders.items.link-request-item', $item) }}" class="flex flex-wrap items-center gap-1.5"
                  onsubmit="return confirm('Gắn dòng {{ addslashes($item->product_name) }} vào dòng yêu cầu đã chọn?');">
                @csrf
                <select name="sale_order_request_item_id" required
                        class="text-[10px] border border-amber-300 rounded px-1.5 py-1 bg-white max-w-[260px]">
                    <option value="">-- Chọn dòng yêu cầu --</option>
                    @foreach($requestItemOptions as $opt)
                        @php
                            $remaining = (float) $opt->remaining_order_quantity;
                            // Model có cả cột `vendor` (chuỗi) lẫn quan hệ vendor(): lấy quan hệ đã eager-load
                            $vendorName = $opt->relationLoaded('vendor') ? ($opt->getRelation('vendor')?->name) : null;
                            // Yêu cầu chưa duyệt vẫn cho gắn (PO đặt trước) nhưng ghi rõ để PO Team biết
                            $reqStatus = $opt->saleOrderRequest?->status;
                            $unapprovedLabel = match ($reqStatus) {
                                \App\Models\SaleOrderRequest::STATUS_PENDING_ADMIN => ' · CHỜ DUYỆT',
                                \App\Models\SaleOrderRequest::STATUS_DRAFT => ' · BẢN NHÁP',
                                \App\Models\SaleOrderRequest::STATUS_NEED_INFO => ' · THIẾU THÔNG TIN',
                                default => '',
                            };
                        @endphp
                        <option value="{{ $opt->id }}"
                                {{ $suggested && $suggested->id === $opt->id ? 'selected' : '' }}
                                {{ $itemQty > $remaining + 0.001 ? 'disabled' : '' }}>
                            {{ $opt->saleOrderRequest?->code }} · {{ $opt->part_number }} · còn {{ rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.') }}/{{ rtrim(rtrim(number_format((float) $opt->quantity, 2, '.', ''), '0'), '.') }}{{ $vendorName ? ' · ' . $vendorName : '' }}{{ $unapprovedLabel }}{{ $itemQty > $remaining + 0.001 ? ' (không đủ SL)' : '' }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded font-bold">Gắn</button>
            </form>
        @endif
    </div>
@endif
