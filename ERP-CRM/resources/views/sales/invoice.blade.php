<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn {{ $sale->code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 14px; line-height: 1.6; padding: 20px; }
        .invoice { max-width: 800px; margin: 0 auto; background: white; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #333; padding-bottom: 20px; }
        .header h1 { font-size: 28px; color: #333; margin-bottom: 5px; }
        .header p { color: #666; }
        .info-section { display: table; width: 100%; margin-bottom: 30px; }
        .info-left, .info-right { display: table-cell; width: 50%; vertical-align: top; }
        .info-right { text-align: right; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 10px; }
        .info-box h3 { font-size: 16px; margin-bottom: 10px; color: #333; }
        .info-box p { margin: 5px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #333; color: white; padding: 12px; text-align: left; font-weight: bold; }
        td { padding: 10px 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 20px; }
        .totals table { width: 400px; margin-left: auto; }
        .totals td { border: none; padding: 8px 12px; }
        .totals .total-row { font-size: 18px; font-weight: bold; background: #f8f9fa; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd; text-align: center; color: #666; }
        .signature-section { display: table; width: 100%; margin-top: 50px; }
        .signature { display: table-cell; width: 50%; text-align: center; }
        .signature p { margin-bottom: 80px; font-weight: bold; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">
            <i class="fas fa-print"></i> In hóa đơn
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Đóng
        </button>
    </div>

    <div class="invoice">
        <!-- Header -->
        <div class="header">
            <h1>HÓA ĐƠN BÁN HÀNG</h1>
            <p>{{ $sale->code }}</p>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-left">
                <div class="info-box">
                    <h3>Thông tin khách hàng</h3>
                    <p><strong>Tên:</strong> {{ $sale->customer_name }}</p>
                    @if($sale->customer)
                        <p><strong>Email:</strong> {{ $sale->customer->email }}</p>
                        <p><strong>Điện thoại:</strong> {{ $sale->customer->phone }}</p>
                        @if($sale->customer->address)
                            <p><strong>Địa chỉ:</strong> {{ $sale->customer->address }}</p>
                        @endif
                    @endif
                    @if($sale->delivery_address)
                        <p><strong>Địa chỉ giao hàng:</strong> {{ $sale->delivery_address }}</p>
                    @endif
                </div>
            </div>
            <div class="info-right">
                <div class="info-box">
                    <h3>Thông tin đơn hàng</h3>
                    <p><strong>Ngày:</strong> {{ $sale->date->format('d/m/Y') }}</p>
                    <p><strong>Loại:</strong> {{ $sale->type_label }}</p>
                    <p><strong>Trạng thái:</strong> {{ $sale->status_label }}</p>
                    <p><strong>Thanh toán:</strong> {{ $sale->payment_status_label }}</p>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        @php
            $isForeign = $sale->currency && !$sale->currency->is_base;
            $rate = $sale->exchange_rate ?: 1;
            $decimals = $sale->currency->decimal_places ?? 2;
            $symbol = $sale->currency->symbol ?? $sale->currency->code ?? '';

            // Subtotal
            $subtotalVnd = $sale->subtotal;
            $subtotalForeign = $isForeign ? round($sale->subtotal / $rate, $decimals) : $subtotalVnd;

            // Discount Amount
            $discountForeign = round($subtotalForeign * ($sale->discount / 100), $decimals);
            $discountVnd = $isForeign ? round($discountForeign * $rate) : round($subtotalVnd * ($sale->discount / 100));

            // VAT Amount
            $afterDiscountForeign = $subtotalForeign - $discountForeign;
            $vatForeign = round($afterDiscountForeign * ($sale->vat / 100), $decimals);
            $vatVnd = $isForeign ? round($vatForeign * $rate) : round(($subtotalVnd - $discountVnd) * ($sale->vat / 100));

            // Total
            $totalForeign = $sale->total_foreign ?? ($isForeign ? round($afterDiscountForeign + $vatForeign, $decimals) : $sale->total);
            $totalVnd = $sale->total;
            
            // Paid Amount & Debt
            $paidForeign = $isForeign ? round($sale->paid_amount / $rate, $decimals) : $sale->paid_amount;
            $debtForeign = $isForeign ? round($sale->debt_amount / $rate, $decimals) : $sale->debt_amount;
        @endphp
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">STT</th>
                    <th>Sản phẩm</th>
                    <th class="text-center" style="width: 100px;">Số lượng</th>
                    <th class="text-right" style="width: 120px;">Đơn giá</th>
                    <th class="text-right" style="width: 150px;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $index => $item)
                @php
                    $itemPriceForeign = $item->price;
                    $itemPriceVnd = $isForeign ? round($item->price * $rate) : $item->price;
                    $itemTotalForeign = $item->total;
                    $itemTotalVnd = $isForeign ? round($item->total * $rate) : $item->total;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-center">{{ number_format($item->quantity) }}</td>
                    <td class="text-right">
                        @if($isForeign)
                            <div style="font-weight: bold;">{{ $symbol }}{{ number_format($itemPriceForeign, $decimals, '.', ',') }}</div>
                            <div style="font-size: 11px; color: #666;">{{ number_format($itemPriceVnd) }} đ</div>
                        @else
                            {{ number_format($itemPriceVnd) }} đ
                        @endif
                    </td>
                    <td class="text-right">
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

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td>Tổng tiền hàng:</td>
                    <td class="text-right">
                        @if($isForeign)
                            <div style="font-weight: bold;">{{ $symbol }}{{ number_format($subtotalForeign, $decimals, '.', ',') }}</div>
                            <div style="font-size: 11px; color: #666;">{{ number_format($subtotalVnd) }} đ</div>
                        @else
                            <strong>{{ number_format($subtotalVnd) }} đ</strong>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Chiết khấu ({{ $sale->discount }}%):</td>
                    <td class="text-right" style="color: #dc3545;">
                        @if($isForeign)
                            <div style="font-weight: bold;">-{{ $symbol }}{{ number_format($discountForeign, $decimals, '.', ',') }}</div>
                            <div style="font-size: 11px; color: #666;">-{{ number_format($discountVnd) }} đ</div>
                        @else
                            -{{ number_format($discountVnd) }} đ
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>VAT ({{ $sale->vat }}%):</td>
                    <td class="text-right">
                        @if($isForeign)
                            <div style="font-weight: bold;">{{ $symbol }}{{ number_format($vatForeign, $decimals, '.', ',') }}</div>
                            <div style="font-size: 11px; color: #666;">{{ number_format($vatVnd) }} đ</div>
                        @else
                            {{ number_format($vatVnd) }} đ
                        @endif
                    </td>
                </tr>
                <tr class="total-row">
                    <td>TỔNG CỘNG:</td>
                    <td class="text-right" style="color: #007bff;">
                        @if($isForeign)
                            <div>{{ $symbol }}{{ number_format($totalForeign, $decimals, '.', ',') }}</div>
                            <div style="font-size: 14px; color: #666; font-weight: normal; margin-top: 5px;">
                                {{ number_format($totalVnd) }} đ
                                <span style="font-size: 11px;">(Tỷ giá: {{ number_format($sale->exchange_rate, 0, ',', '.') }})</span>
                            </div>
                        @else
                            {{ number_format($totalVnd) }} đ
                        @endif
                    </td>
                </tr>
                @if($sale->paid_amount > 0)
                <tr>
                    <td>Đã thanh toán:</td>
                    <td class="text-right" style="color: #28a745;">
                        @if($isForeign)
                            <div style="font-weight: bold;">{{ $symbol }}{{ number_format($paidForeign, $decimals, '.', ',') }}</div>
                            <div style="font-size: 11px; color: #666;">{{ number_format($sale->paid_amount) }} đ</div>
                        @else
                            {{ number_format($sale->paid_amount) }} đ
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Còn lại:</td>
                    <td class="text-right" style="color: #dc3545;">
                        @if($isForeign)
                            <div style="font-weight: bold;">{{ $symbol }}{{ number_format($debtForeign, $decimals, '.', ',') }}</div>
                            <div style="font-size: 11px; color: #666;">{{ number_format($sale->debt_amount) }} đ</div>
                        @else
                            <strong>{{ number_format($sale->debt_amount) }} đ</strong>
                        @endif
                    </td>
                </tr>
                @endif
            </table>
        </div>

        @if($sale->note)
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
            <strong>Ghi chú:</strong> {{ $sale->note }}
        </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature">
                <p>Người mua hàng</p>
                <p style="margin-top: 80px; font-weight: normal; font-style: italic;">(Ký và ghi rõ họ tên)</p>
            </div>
            <div class="signature">
                <p>Người bán hàng</p>
                <p style="margin-top: 80px; font-weight: normal; font-style: italic;">(Ký và ghi rõ họ tên)</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
            <p style="margin-top: 10px; font-size: 12px;">Hóa đơn được tạo tự động từ hệ thống Mini ERP</p>
        </div>
    </div>
</body>
</html>
