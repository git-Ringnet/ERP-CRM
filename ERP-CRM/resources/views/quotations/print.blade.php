<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo giá {{ $quotation->code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 20px;
        }

        .company-logo {
            max-height: 80px;
            margin-right: 20px;
        }

        .company-logo img {
            max-height: 80px;
        }

        .header-content {
            flex: 1;
            text-align: left;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
        }

        .document-title-container {
            text-align: center;
            margin-top: 10px;
        }

        .document-title {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            margin-top: 5px;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .info-box {
            width: 48%;
        }

        .info-box h4 {
            background: #f8f9fa;
            padding: 8px;
            margin-bottom: 10px;
            border-left: 3px solid #3498db;
        }

        .info-row {
            display: flex;
            margin-bottom: 5px;
        }

        .info-label {
            width: 120px;
            color: #666;
        }

        .info-value {
            flex: 1;
            font-weight: 500;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th {
            background: #3498db;
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            width: 300px;
            margin-left: auto;
            page-break-inside: avoid;
        }

        .totals .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .totals .total-row {
            font-size: 16px;
            font-weight: bold;
            color: #3498db;
            border-top: 2px solid #3498db;
        }

        .terms {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            page-break-inside: avoid;
        }

        .terms h4 {
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 200px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }

        .watermark {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
            <button onclick="window.print()"
                style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> In báo giá
            </button>
            <a href="{{ route('quotations.show', $quotation) }}"
                style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">
                Quay lại
            </a>
        </div>

        {{-- Watermark removed as per user request --}}

        <div class="header">
            @if(isset($companySettings['company_logo']) && $companySettings['company_logo'] && file_exists(public_path($companySettings['company_logo'])))
                <div class="company-logo">
                    <img src="{{ asset($companySettings['company_logo']) }}" alt="Logo">
                </div>
            @endif
            <div class="header-content">
                <div class="company-name">{{ $companySettings['company_name'] ?? 'CÔNG TY CỦA BẠN' }}</div>
                <div style="color: #666; margin-top: 5px;">Địa chỉ: {{ $companySettings['company_address'] ?? 'N/A' }}</div>
                <div style="color: #666;">
                    @if(isset($companySettings['company_phone'])) ĐT: {{ $companySettings['company_phone'] }} @endif
                    @if(isset($companySettings['company_email'])) | Email: {{ $companySettings['company_email'] }} @endif
                    @php
                        $taxCode = $companySettings['company_tax_code'] ?? $companySettings['company_tax'] ?? null;
                    @endphp
                    @if($taxCode) | MST: {{ $taxCode }} @endif
                </div>
            </div>
        </div>

        <div class="document-title-container">
            <div class="document-title">BÁO GIÁ</div>
            <div style="color: #666;">Số: {{ $quotation->code }}</div>
        </div>

        <div class="info-section">
            <div class="info-box">
                <h4>Thông tin khách hàng</h4>
                <div class="info-row">
                    <span class="info-label">Khách hàng:</span>
                    <span class="info-value">{{ $quotation->customer_name }}</span>
                </div>
                @if($quotation->customer)
                    <div class="info-row">
                        <span class="info-label">Địa chỉ:</span>
                        <span class="info-value">{{ $quotation->customer->address ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Điện thoại:</span>
                        <span class="info-value">{{ $quotation->customer->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value">{{ $quotation->customer->email ?? 'N/A' }}</span>
                    </div>
                @endif
            </div>
            <div class="info-box">
                <h4>Thông tin báo giá</h4>
                <div class="info-row">
                    <span class="info-label">Ngày báo giá:</span>
                    <span class="info-value">{{ $quotation->date->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Hiệu lực đến:</span>
                    <span class="info-value">{{ $quotation->valid_until->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tiêu đề:</span>
                    <span class="info-value">{{ $quotation->title }}</span>
                </div>
            </div>
        </div>

        @php
            $isForeign = $quotation->currency && !$quotation->currency->is_base;
            $rate = $quotation->exchange_rate ?: 1;
            $decimals = $quotation->currency->decimal_places ?? 2;
            $symbol = $quotation->currency->symbol ?? $quotation->currency->code ?? '';

            $customColumns = $quotation->custom_columns ?? [];
            if (!is_array($customColumns)) {
                $customColumns = [];
            }

            // Subtotal
            $subtotalForeign = $isForeign ? $quotation->items->sum('total') : $quotation->subtotal;
            $subtotalVnd = $isForeign ? round($subtotalForeign * $rate) : $quotation->subtotal;

            // Discount Amount
            $discountForeign = round($subtotalForeign * ($quotation->discount / 100), $decimals);
            $discountVnd = $isForeign ? round($discountForeign * $rate) : round($subtotalVnd * ($quotation->discount / 100));

            // VAT Amount (Sum from items)
            $vatForeign = $quotation->items->sum('vat_amount');
            $vatVnd = $quotation->vat_amount ?: round($vatForeign * $rate);

            // Total
            $totalForeign = $quotation->total_foreign ?: round($subtotalForeign - $discountForeign + $vatForeign, $decimals);
            $totalVnd = $quotation->total;
        @endphp
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">STT</th>
                    <th>Sản phẩm</th>
                    <th style="width: 60px;" class="text-center">SL</th>
                    <th style="width: 110px;" class="text-right">Đơn giá</th>
                    <th style="width: 70px;" class="text-center">VAT (%)</th>
                    @foreach($customColumns as $colName)
                        <th style="width: 90px;">{{ $colName }}</th>
                    @endforeach
                    <th style="width: 120px;" class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $index => $item)
                    @php
                        $itemPriceForeign = $item->price;
                        $itemPriceVnd = $isForeign ? round($item->price * $rate) : $item->price;
                        $itemTotalForeign = $item->total;
                        $itemTotalVnd = $isForeign ? round($item->total * $rate) : $item->total;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item->product_code ?: $item->product_name }}</strong>
                            @if($item->product_code)
                                <br><small style="color: #666; white-space: pre-line;">{{ $item->description ?: $item->product_name }}</small>
                            @else
                                @if($item->description)
                                    <br><small style="color: #666; white-space: pre-line;">{{ $item->description }}</small>
                                @endif
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">
                            @if($isForeign)
                                <div style="font-weight: bold;">{{ $symbol }}{{ number_format($itemPriceForeign, $decimals, '.', ',') }}</div>
                                <div style="font-size: 11px; color: #666;">{{ number_format($itemPriceVnd) }} đ</div>
                            @else
                                {{ number_format($itemPriceVnd) }} đ
                            @endif
                        </td>
                        <td class="text-center">{{ $item->vat == -1 ? 'KCT' : (float)$item->vat . '%' }}</td>
                        @foreach($customColumns as $colName)
                            <td>{{ $item->custom_fields[$colName] ?? '' }}</td>
                        @endforeach
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

        <div class="totals text-right">
            <div class="row">
                <span>Tổng tiền hàng:</span>
                <span class="text-right font-medium">
                    @if($isForeign)
                        <div style="font-weight: bold;">{{ $symbol }}{{ number_format($subtotalForeign, $decimals, '.', ',') }}</div>
                        <div style="font-size: 11px; color: #666;">{{ number_format($subtotalVnd) }} đ</div>
                    @else
                        <strong>{{ number_format($subtotalVnd) }} đ</strong>
                    @endif
                </span>
            </div>
            @if($quotation->discount > 0)
                <div class="row text-red-600">
                    <span>Chiết khấu ({{ (float) $quotation->discount }}%):</span>
                    <span class="text-right">
                        @if($isForeign)
                            <div style="font-weight: bold;">-{{ $symbol }}{{ number_format($discountForeign, $decimals, '.', ',') }}</div>
                            <div style="font-size: 11px; color: #666;">-{{ number_format($discountVnd) }} đ</div>
                        @else
                            -{{ number_format($discountVnd) }} đ
                        @endif
                    </span>
                </div>
            @endif
            <div class="row">
                <span>Thuế VAT:</span>
                <span class="text-right font-medium" style="color: #3498db;">
                    @if($isForeign)
                        <div style="font-weight: bold;">{{ $symbol }}{{ number_format($vatForeign, $decimals, '.', ',') }}</div>
                        <div style="font-size: 11px; color: #666;">{{ number_format($vatVnd) }} đ</div>
                    @else
                        {{ number_format($vatVnd) }} đ
                    @endif
                </span>
            </div>
            <div class="row total-row">
                <span>TỔNG CỘNG:</span>
                <span class="text-right" style="color: #3498db;">
                    @if($isForeign)
                        <div>{{ $symbol }}{{ number_format($totalForeign, $decimals, '.', ',') }}</div>
                        <div style="font-size: 14px; color: #666; font-weight: normal; margin-top: 5px;">
                            {{ number_format($totalVnd) }} đ
                            <span style="font-size: 11px;">(Tỷ giá: {{ number_format($quotation->exchange_rate, 0, ',', '.') }})</span>
                        </div>
                    @else
                        {{ number_format($totalVnd) }} đ
                    @endif
                </span>
            </div>
        </div>

        @if($quotation->payment_terms || $quotation->delivery_time || !empty($quotation->note_array) || !empty($quotation->disclaimer_array))
            <div class="terms">
                <h4 style="border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 8px;">ĐIỀU KHOẢN & GHI CHÚ</h4>
                @if($quotation->payment_terms)
                    <p style="margin-bottom: 5px;"><strong>Điều khoản thanh toán:</strong> {{ $quotation->payment_terms }}</p>
                @endif
                @if($quotation->delivery_time)
                    <p style="margin-bottom: 5px;"><strong>Thời gian giao hàng:</strong> {{ $quotation->delivery_time }}</p>
                @endif
                @if(!empty($quotation->note_array))
                    <div style="margin-bottom: 8px;">
                        <strong>Ghi chú:</strong>
                        <div style="margin-top: 3px; padding-left: 10px;">
                            @foreach($quotation->note_array as $i => $item)
                                <div style="margin-bottom: 3px;">({{ $i + 1 }}) {{ $item }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if(!empty($quotation->disclaimer_array))
                    <div style="margin-top: 10px; border-top: 1px dashed #ccc; padding-top: 8px;">
                        <strong style="color: #c0392b;">Cảnh báo / Lưu ý:</strong>
                        <div style="margin-top: 4px; padding-left: 10px;">
                            @foreach($quotation->disclaimer_array as $i => $item)
                                <div style="margin-bottom: 3px; color: #c0392b;">({{ $i + 1 }}) {{ $item }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="footer">
            <div class="signature-box">
                <strong>Khách hàng</strong>
                <div class="signature-line">{{ $quotation->customer_name }}</div>
            </div>
            <div class="signature-box">
                <strong>Người lập báo giá</strong>
                <div class="signature-line">{{ $quotation->creator->name ?? '' }}</div>
            </div>
        </div>
    </div>
</body>

</html>