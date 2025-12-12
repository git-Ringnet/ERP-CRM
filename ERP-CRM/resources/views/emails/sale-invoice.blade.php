<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn {{ $sale->code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #1e3a5f;
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-box h3 {
            margin-top: 0;
            color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        th {
            background: #1e3a5f;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 18px;
        }
        .total-row td {
            padding: 15px 12px;
        }
        .button {
            display: inline-block;
            background: #1e3a5f;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background: #2c5282;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="header">
        <h1>HÓA ĐƠN BÁN HÀNG</h1>
        <p style="margin: 15px 0 0 0; font-size: 18px; letter-spacing: 1px;">{{ $sale->code }}</p>
    </div>

    <div class="content">
        <p style="font-size: 16px; margin-top: 0;">Kính gửi <strong>{{ $sale->customer_name }}</strong>,</p>
        <p>Cảm ơn quý khách đã tin tưởng và sử dụng sản phẩm/dịch vụ của chúng tôi. Dưới đây là thông tin chi tiết đơn hàng:</p>

        <div class="info-box">
            <h3 style="margin-bottom: 15px;">Thông tin đơn hàng</h3>
            <div class="info-row">
                <span class="info-label">Mã đơn hàng:&nbsp;&nbsp;</span>
                <span class="info-value">{{ $sale->code }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ngày tạo:&nbsp;&nbsp;</span>
                <span class="info-value">{{ $sale->date->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Loại đơn hàng:&nbsp;&nbsp;</span>
                <span class="info-value">{{ $sale->type_label }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Trạng thái:&nbsp;&nbsp;</span>
                <span class="info-value">
                    <span class="status-badge status-{{ $sale->status }}">{{ $sale->status_label }}</span>
                </span>
            </div>
            @if($sale->delivery_address)
            <div class="info-row">
                <span class="info-label">Địa chỉ giao hàng:&nbsp;&nbsp;</span>
                <span class="info-value">{{ $sale->delivery_address }}</span>
            </div>
            @endif
        </div>

        <div class="info-box">
            <h3 style="margin-bottom: 15px;">Chi tiết sản phẩm</h3>
            <table>
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th style="text-align: center;">SL</th>
                        <th style="text-align: right;">Đơn giá</th>
                        <th style="text-align: right;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td style="text-align: center;">{{ number_format($item->quantity) }}</td>
                        <td style="text-align: right;">{{ number_format($item->price) }} đ</td>
                        <td style="text-align: right;"><strong>{{ number_format($item->total) }} đ</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="info-box">
            <h3 style="margin-bottom: 15px;">Tổng kết thanh toán</h3>
            <div class="info-row">
                <span class="info-label">Tổng tiền hàng:&nbsp;&nbsp;</span>
                <span class="info-value">{{ number_format($sale->subtotal) }} đ</span>
            </div>
            @if($sale->discount > 0)
            <div class="info-row">
                <span class="info-label">Chiết khấu ({{ $sale->discount }}%):&nbsp;&nbsp;</span>
                <span class="info-value" style="color: #dc3545;">-{{ number_format($sale->subtotal * $sale->discount / 100) }} đ</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">VAT ({{ $sale->vat }}%):&nbsp;&nbsp;</span>
                <span class="info-value">{{ number_format(($sale->subtotal - $sale->subtotal * $sale->discount / 100) * $sale->vat / 100) }} đ</span>
            </div>
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 15px; border-top: 2px solid #1e3a5f;">
                <tr>
                    <td style="padding: 15px 0; vertical-align: middle;">
                        <span style="font-weight: bold; color: #1e3a5f; font-size: 16px;">TỔNG CỘNG:&nbsp;&nbsp;&nbsp;</span>
                        <span style="color: #1e3a5f; font-size: 20px; font-weight: bold;">{{ number_format($sale->total) }} đ</span>
                    </td>
                </tr>
            </table>
            @if($sale->paid_amount > 0)
            <div class="info-row">
                <span class="info-label">Đã thanh toán:&nbsp;&nbsp;</span>
                <span class="info-value" style="color: #28a745;">{{ number_format($sale->paid_amount) }} đ</span>
            </div>
            <div class="info-row">
                <span class="info-label">Còn lại:&nbsp;&nbsp;</span>
                <span class="info-value" style="color: #dc3545;"><strong>{{ number_format($sale->debt_amount) }} đ</strong></span>
            </div>
            @endif
        </div>

        @if($sale->note)
        <div class="info-box" style="background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3 style="color: #856404; border-color: #ffc107;">Ghi chú</h3>
            <p style="margin: 0; color: #856404;">{{ $sale->note }}</p>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ route('sales.pdf', $sale->id) }}" class="button" style="color: white !important;">
                Xem hóa đơn chi tiết
            </a>
        </div>

        <div class="footer">
            <p><strong>Cảm ơn quý khách đã tin tưởng!</strong></p>
            <p>Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi.</p>
            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                Email này được gửi tự động từ hệ thống Mini ERP.<br>
                Vui lòng không trả lời email này.
            </p>
        </div>
    </div>
</body>
</html>
