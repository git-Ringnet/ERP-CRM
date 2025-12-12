<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo giá {{ $quotation->code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; line-height: 1.5; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #3498db; padding-bottom: 20px; }
        .company-name { font-size: 24px; font-weight: bold; color: #2c3e50; }
        .document-title { font-size: 20px; color: #3498db; margin-top: 10px; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { width: 48%; }
        .info-box h4 { background: #f8f9fa; padding: 8px; margin-bottom: 10px; border-left: 3px solid #3498db; }
        .info-row { display: flex; margin-bottom: 5px; }
        .info-label { width: 120px; color: #666; }
        .info-value { flex: 1; font-weight: 500; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #3498db; color: white; font-weight: 600; }
        tr:nth-child(even) { background: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { width: 300px; margin-left: auto; }
        .totals .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .totals .total-row { font-size: 16px; font-weight: bold; color: #3498db; border-top: 2px solid #3498db; }
        .terms { margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .terms h4 { margin-bottom: 10px; color: #2c3e50; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { width: 200px; text-align: center; }
        .signature-line { border-top: 1px solid #333; margin-top: 60px; padding-top: 5px; }
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
            <button onclick="window.print()" style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> In báo giá
            </button>
            <a href="{{ route('quotations.show', $quotation) }}" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;">
                Quay lại
            </a>
        </div>

        <div class="header">
            <div class="company-name">CÔNG TY CỦA BẠN</div>
            <div style="color: #666; margin-top: 5px;">Địa chỉ: 123 Đường ABC, Quận XYZ, TP. HCM</div>
            <div style="color: #666;">ĐT: 0123 456 789 | Email: info@company.com</div>
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

        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">STT</th>
                    <th>Sản phẩm</th>
                    <th style="width: 80px;" class="text-center">SL</th>
                    <th style="width: 120px;" class="text-right">Đơn giá</th>
                    <th style="width: 130px;" class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product_name }}
                        @if($item->product_code)
                            <br><small style="color: #666;">Mã: {{ $item->product_code }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                    <td class="text-right">{{ number_format($item->total, 0, ',', '.') }} đ</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row">
                <span>Tổng tiền hàng:</span>
                <span>{{ number_format($quotation->subtotal, 0, ',', '.') }} đ</span>
            </div>
            @if($quotation->discount > 0)
            <div class="row">
                <span>Chiết khấu ({{ $quotation->discount }}%):</span>
                <span>-{{ number_format($quotation->subtotal * $quotation->discount / 100, 0, ',', '.') }} đ</span>
            </div>
            @endif
            <div class="row">
                <span>VAT ({{ $quotation->vat }}%):</span>
                <span>{{ number_format(($quotation->subtotal - $quotation->subtotal * $quotation->discount / 100) * $quotation->vat / 100, 0, ',', '.') }} đ</span>
            </div>
            <div class="row total-row">
                <span>TỔNG CỘNG:</span>
                <span>{{ number_format($quotation->total, 0, ',', '.') }} đ</span>
            </div>
        </div>

        @if($quotation->payment_terms || $quotation->delivery_time || $quotation->note)
        <div class="terms">
            <h4>Điều khoản & Ghi chú</h4>
            @if($quotation->payment_terms)
                <p><strong>Điều khoản thanh toán:</strong> {{ $quotation->payment_terms }}</p>
            @endif
            @if($quotation->delivery_time)
                <p><strong>Thời gian giao hàng:</strong> {{ $quotation->delivery_time }}</p>
            @endif
            @if($quotation->note)
                <p><strong>Ghi chú:</strong> {{ $quotation->note }}</p>
            @endif
        </div>
        @endif

        <div class="footer">
            <div class="signature-box">
                <strong>Khách hàng</strong>
                <div class="signature-line">(Ký, ghi rõ họ tên)</div>
            </div>
            <div class="signature-box">
                <strong>Người lập báo giá</strong>
                <div class="signature-line">(Ký, ghi rõ họ tên)</div>
            </div>
        </div>
    </div>
</body>
</html>
