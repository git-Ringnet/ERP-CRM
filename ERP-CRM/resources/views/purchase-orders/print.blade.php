<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn mua hàng {{ $purchaseOrder->code }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0; color: #666; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px 0; vertical-align: top; }
        .info-table .label { color: #666; width: 120px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background: #f5f5f5; }
        .items-table .number { text-align: right; }
        .totals { width: 300px; margin-left: auto; }
        .totals td { padding: 5px 0; }
        .totals .label { text-align: right; padding-right: 15px; }
        .totals .total-row { font-weight: bold; font-size: 14px; border-top: 2px solid #333; }
        .footer { margin-top: 40px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; }
        .signature-box { text-align: center; width: 200px; }
        .signature-box .title { font-weight: bold; margin-bottom: 60px; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>ĐƠN MUA HÀNG</h1>
        <p>Số: {{ $purchaseOrder->code }}</p>
        <p>Ngày: {{ $purchaseOrder->order_date->format('d/m/Y') }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td>
                <table>
                    <tr><td class="label">Nhà cung cấp:</td><td><strong>{{ $purchaseOrder->supplier->name }}</strong></td></tr>
                    <tr><td class="label">Địa chỉ:</td><td>{{ $purchaseOrder->supplier->address ?? '-' }}</td></tr>
                    <tr><td class="label">Điện thoại:</td><td>{{ $purchaseOrder->supplier->phone ?? '-' }}</td></tr>
                    <tr><td class="label">Email:</td><td>{{ $purchaseOrder->supplier->email ?? '-' }}</td></tr>
                </table>
            </td>
            <td>
                <table>
                    <tr><td class="label">Ngày giao dự kiến:</td><td>{{ $purchaseOrder->expected_delivery ? $purchaseOrder->expected_delivery->format('d/m/Y') : '-' }}</td></tr>
                    <tr><td class="label">Địa chỉ giao:</td><td>{{ $purchaseOrder->delivery_address ?? '-' }}</td></tr>
                    <tr><td class="label">Thanh toán:</td><td>{{ $purchaseOrder->payment_terms_label }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @php
        $isForeign = $purchaseOrder->currency && !$purchaseOrder->currency->is_base;
        $rate = $purchaseOrder->exchange_rate ?: 1;
        $decimals = $purchaseOrder->currency->decimal_places ?? 2;
        $symbol = $purchaseOrder->currency->symbol ?? $purchaseOrder->currency->code ?? '';

        // All DB values (except total) are saved in the raw selected currency
        // Subtotal
        $subtotalForeign = $purchaseOrder->subtotal;
        $subtotalVnd = $isForeign ? round($subtotalForeign * $rate) : $subtotalForeign;

        // Discount
        $discountForeign = $purchaseOrder->discount_amount;
        $discountVnd = $isForeign ? round($discountForeign * $rate) : $discountForeign;

        // Shipping
        $shippingForeign = $purchaseOrder->shipping_cost;
        $shippingVnd = $isForeign ? round($shippingForeign * $rate) : $shippingForeign;

        // Other cost
        $otherForeign = $purchaseOrder->other_cost;
        $otherVnd = $isForeign ? round($otherForeign * $rate) : $otherForeign;

        // VAT
        $vatForeign = $purchaseOrder->vat_amount;
        $vatVnd = $isForeign ? round($vatForeign * $rate) : $vatForeign;

        // Total
        $beforeVatForeign = $subtotalForeign - $discountForeign + $shippingForeign + $otherForeign;
        $totalForeign = $purchaseOrder->total_foreign ?? ($isForeign ? round($beforeVatForeign + $vatForeign, $decimals) : $purchaseOrder->total);
        $totalVnd = $purchaseOrder->total;
    @endphp
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40px;">STT</th>
                <th>Sản phẩm</th>
                <th style="width: 80px;" class="number">Số lượng</th>
                <th style="width: 60px;">ĐVT</th>
                <th style="width: 100px;" class="number">Đơn giá</th>
                <th style="width: 120px;" class="number">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $index => $item)
            @php
                $itemPriceForeign = $item->unit_price;
                $itemPriceVnd = $isForeign ? round($item->unit_price * $rate) : $item->unit_price;
                $itemTotalForeign = $item->total;
                $itemTotalVnd = $isForeign ? round($item->total * $rate) : $item->total;
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td class="number">{{ number_format($item->quantity) }}</td>
                <td>{{ $item->unit }}</td>
                <td class="number">
                    @if($isForeign)
                        <div style="font-weight: bold;">{{ $symbol }}{{ number_format($itemPriceForeign, $decimals, '.', ',') }}</div>
                        <div style="font-size: 11px; color: #666;">{{ number_format($itemPriceVnd) }} đ</div>
                    @else
                        {{ number_format($itemPriceVnd) }} đ
                    @endif
                </td>
                <td class="number">
                    @if($isForeign)
                        <div style="font-weight: bold;">{{ $symbol }}{{ number_format($itemTotalForeign, $decimals, '.', ',') }}</div>
                        <div style="font-size: 11px; color: #666;">{{ number_format($itemTotalVnd) }} đ</div>
                    @else
                        <strong>{{ number_format($itemTotalVnd) }} đ</strong>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Tổng tiền hàng:</td>
            <td class="number">
                @if($isForeign)
                    <div style="font-weight: bold;">{{ $symbol }}{{ number_format($subtotalForeign, $decimals, '.', ',') }}</div>
                    <div style="font-size: 11px; color: #666;">{{ number_format($subtotalVnd) }} đ</div>
                @else
                    <strong>{{ number_format($subtotalVnd) }} đ</strong>
                @endif
            </td>
        </tr>
        @if($purchaseOrder->discount_percent > 0)
        <tr>
            <td class="label">Chiết khấu ({{ $purchaseOrder->discount_percent }}%):</td>
            <td class="number" style="color: #dc3545;">
                @if($isForeign)
                    <div style="font-weight: bold;">-{{ $symbol }}{{ number_format($discountForeign, $decimals, '.', ',') }}</div>
                    <div style="font-size: 11px; color: #666;">-{{ number_format($discountVnd) }} đ</div>
                @else
                    -{{ number_format($discountVnd) }} đ
                @endif
            </td>
        </tr>
        @endif
        @if($purchaseOrder->shipping_cost > 0)
        <tr>
            <td class="label">Phí vận chuyển:</td>
            <td class="number">
                @if($isForeign)
                    <div style="font-weight: bold;">{{ $symbol }}{{ number_format($shippingForeign, $decimals, '.', ',') }}</div>
                    <div style="font-size: 11px; color: #666;">{{ number_format($shippingVnd) }} đ</div>
                @else
                    {{ number_format($shippingVnd) }} đ
                @endif
            </td>
        </tr>
        @endif
        @if($purchaseOrder->other_cost > 0)
        <tr>
            <td class="label">Chi phí khác:</td>
            <td class="number">
                @if($isForeign)
                    <div style="font-weight: bold;">{{ $symbol }}{{ number_format($otherForeign, $decimals, '.', ',') }}</div>
                    <div style="font-size: 11px; color: #666;">{{ number_format($otherVnd) }} đ</div>
                @else
                    {{ number_format($otherVnd) }} đ
                @endif
            </td>
        </tr>
        @endif
        <tr>
            <td class="label">VAT ({{ $purchaseOrder->vat_percent }}%):</td>
            <td class="number">
                @if($isForeign)
                    <div style="font-weight: bold;">{{ $symbol }}{{ number_format($vatForeign, $decimals, '.', ',') }}</div>
                    <div style="font-size: 11px; color: #666;">{{ number_format($vatVnd) }} đ</div>
                @else
                    {{ number_format($vatVnd) }} đ
                @endif
            </td>
        </tr>
        <tr class="total-row">
            <td class="label">TỔNG CỘNG:</td>
            <td class="number" style="color: #007bff;">
                @if($isForeign)
                    <div>{{ $symbol }}{{ number_format($totalForeign, $decimals, '.', ',') }}</div>
                    <div style="font-size: 14px; color: #666; font-weight: normal; margin-top: 5px;">
                        {{ number_format($totalVnd) }} đ
                        <span style="font-size: 11px;">(Tỷ giá: {{ number_format($purchaseOrder->exchange_rate, 0, ',', '.') }})</span>
                    </div>
                @else
                    {{ number_format($totalVnd) }} đ
                @endif
            </td>
        </tr>
    </table>

    @if($purchaseOrder->note)
    <div style="margin-top: 20px;">
        <strong>Ghi chú:</strong> {{ $purchaseOrder->note }}
    </div>
    @endif

    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: center; width: 33%;">
                    <p><strong>Người lập</strong></p>
                    <p style="margin-top: 60px;">(Ký, ghi rõ họ tên)</p>
                </td>
                <td style="text-align: center; width: 33%;">
                    <p><strong>Người duyệt</strong></p>
                    <p style="margin-top: 60px;">(Ký, ghi rõ họ tên)</p>
                </td>
                <td style="text-align: center; width: 33%;">
                    <p><strong>Nhà cung cấp</strong></p>
                    <p style="margin-top: 60px;">(Ký, đóng dấu)</p>
                </td>
            </tr>
        </table>
    </div>

    <script>window.onload = function() { window.print(); }</script>
</body>
</html>
