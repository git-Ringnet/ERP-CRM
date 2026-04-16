@extends('layouts.app')

@section('title', 'Chi tiết báo giá NCC')
@section('page-title', 'Chi tiết báo giá: ' . $supplierQuotation->code)

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <a href="{{ route('supplier-quotations.index') }}" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
            <div class="flex space-x-2">
                @if($supplierQuotation->status == 'pending')
                    <a href="{{ route('supplier-quotations.edit', $supplierQuotation) }}"
                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                        <i class="fas fa-edit mr-2"></i> Sửa
                    </a>
                    <form action="{{ route('supplier-quotations.select', $supplierQuotation) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                            onclick="return confirm('Chọn báo giá này để tạo PO?')">
                            <i class="fas fa-check mr-2"></i> Chọn báo giá này
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-500">Mã báo giá</p>
                    <p class="font-semibold text-primary text-lg">{{ $supplierQuotation->code }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Nhà cung cấp</p>
                    <p class="font-medium">{{ $supplierQuotation->supplier->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Trạng thái</p>
                    @if($supplierQuotation->status == 'pending')
                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                    @elseif($supplierQuotation->status == 'selected')
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Đã chọn</span>
                    @else
                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Từ chối</span>
                    @endif
                </div>
                <div>
                    <p class="text-sm text-gray-500">Yêu cầu báo giá</p>
                    @if($supplierQuotation->purchaseRequest)
                        <a href="{{ route('purchase-requests.show', $supplierQuotation->purchaseRequest) }}"
                            class="text-primary hover:underline">
                            {{ $supplierQuotation->purchaseRequest->code }}
                        </a>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Ngày báo giá</p>
                    <p class="font-medium">{{ $supplierQuotation->quotation_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Hiệu lực đến</p>
                    <p class="font-medium {{ $supplierQuotation->valid_until < now() ? 'text-red-600' : '' }}">
                        {{ $supplierQuotation->valid_until->format('d/m/Y') }}
                        @if($supplierQuotation->valid_until < now()) (Hết hạn) @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Thời gian giao hàng</p>
                    <p class="font-medium">{{ $supplierQuotation->delivery_days ?? '-' }} ngày</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Bảo hành</p>
                    <p class="font-medium">{{ $supplierQuotation->warranty ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">Chi tiết sản phẩm</h3>
                @if($supplierQuotation->currency && !$supplierQuotation->currency->is_base)
                    <div class="text-sm px-3 py-1 bg-blue-50 text-blue-700 rounded-full border border-blue-100">
                        <i class="fas fa-info-circle mr-1"></i>
                        Tỷ giá: 1 {{ $supplierQuotation->currency->code }} = {{ number_format($supplierQuotation->exchange_rate) }} ₫
                    </div>
                @endif
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sản phẩm</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        @php
                            $symbol = $supplierQuotation->currency->symbol ?? '₫';
                            $isForeign = $supplierQuotation->currency && !$supplierQuotation->currency->is_base;
                            $decimals = $isForeign ? ($supplierQuotation->currency->decimal_places ?? 2) : 0;
                        @endphp
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đơn giá ({{ $symbol }})</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thành tiền ({{ $symbol }})</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($supplierQuotation->items as $index => $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 border-l border-transparent hover:border-primary">{{ $item->product_name }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ number_format($item->quantity) }} {{ $item->unit }}</td>
                            <td class="px-4 py-3 text-right text-sm text-gray-900">{{ number_format($item->unit_price, $decimals) }} {{ $symbol }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">{{ number_format($item->total, $decimals) }} {{ $symbol }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-100">
                    @php
                        $rate = $supplierQuotation->exchange_rate ?? 1;
                        $renderRow = function($label, $val, $isVnd = false, $class = "text-gray-600", $valClass = "font-medium text-gray-900") use ($symbol, $decimals, $isForeign, $rate) {
                            $formattedForeign = number_format($val, $decimals) . ' ' . $symbol;
                            $formattedVnd = number_format(round($val * $rate)) . ' ₫';
                            $html = "<tr>
                                <td colspan=\"4\" class=\"px-4 py-2 text-right $class\">$label:</td>
                                <td class=\"px-4 py-2 text-right\">
                                    <div class=\"$valClass\">$formattedForeign</div>";
                            if ($isForeign) {
                                $html .= "<div class=\"text-xs text-gray-400 mt-0.5\">≈ $formattedVnd</div>";
                            }
                            $html .= "</td></tr>";
                            return $html;
                        };
                    @endphp

                    {!! $renderRow("Tổng tiền hàng", $subtotal) !!}

                    @if($supplierQuotation->discount_percent > 0)
                        {!! $renderRow("Chiết khấu ({$supplierQuotation->discount_percent}%)", -$discount, false, "text-gray-600", "font-medium text-red-600") !!}
                    @endif

                    @if($supplierQuotation->shipping_cost > 0)
                        {!! $renderRow("Phí vận chuyển", $supplierQuotation->shipping_cost) !!}
                    @endif

                    {!! $renderRow("VAT ({$supplierQuotation->vat_percent}%)", $vat) !!}

                    <tr class="bg-blue-50/50">
                        <td colspan="4" class="px-4 py-4 text-right text-lg font-bold text-gray-800 uppercase tracking-wider">Tổng cộng:</td>
                        <td class="px-4 py-4 text-right">
                            <div class="text-2xl font-bold text-primary">{{ number_format($total, $decimals) }} {{ $symbol }}</div>
                            @if($isForeign)
                                <div class="text-sm font-semibold text-gray-500 mt-1">= {{ number_format($supplierQuotation->total) }} ₫</div>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if(isset($compareQuotations) && count($compareQuotations) > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-4">So sánh với báo giá khác cùng yêu cầu</h3>
                <div class="grid grid-cols-1 md:grid-cols-{{ min(count($compareQuotations) + 1, 3) }} gap-4">
                    <div class="border rounded-lg p-4 bg-green-50 border-green-300">
                        <p class="font-semibold text-green-800">{{ $supplierQuotation->supplier->name }}</p>
                        <p class="text-2xl font-bold text-green-600 mt-2">{{ number_format($supplierQuotation->total) }}đ</p>
                        <p class="text-sm text-gray-600 mt-1">Giao: {{ $supplierQuotation->delivery_days ?? '-' }} ngày</p>
                        <span class="inline-block mt-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Đang xem</span>
                    </div>
                    @foreach($compareQuotations as $compare)
                        <div
                            class="border rounded-lg p-4 {{ $compare->total < $supplierQuotation->total ? 'bg-blue-50 border-blue-300' : '' }}">
                            <p class="font-semibold">{{ $compare->supplier->name }}</p>
                            <p
                                class="text-2xl font-bold {{ $compare->total < $supplierQuotation->total ? 'text-blue-600' : 'text-gray-700' }} mt-2">
                                {{ number_format($compare->total) }}đ
                            </p>
                            <p class="text-sm text-gray-600 mt-1">Giao: {{ $compare->delivery_days ?? '-' }} ngày</p>
                            @if($compare->total < $supplierQuotation->total)
                                <span class="inline-block mt-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">Rẻ hơn
                                    {{ number_format($supplierQuotation->total - $compare->total) }}đ</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection