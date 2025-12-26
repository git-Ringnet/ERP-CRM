<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn đặt hàng {{ $order->code }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { background: #7c3aed; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .info-box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .info-box h3 { margin-top: 0; color: #7c3aed; border-bottom: 2px solid #7c3aed; padding-bottom: 10px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: bold; color: #666; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
        th { background: #7c3aed; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; color: #666; font-size: 14px; }
        .highlight { background: #f3e8ff; padding: 15px; border-radius: 8px; border-left: 4px solid #7c3aed; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ĐƠN ĐẶT HÀNG</h1>
        <p style="margin: 15px 0 0 0; font-size: 20px; letter-spacing: 1px;">{{ $order->code }}</p>
    </div>

    <div class="content">
        <p style="font-size: 16px;">Kính gửi <strong>{{ $order->supplier->name }}</strong>,</p>
        <p>Chúng tôi gửi đến quý công ty đơn đặt hàng với thông tin chi tiết như sau:</p>

        <div class="info-box">
            <h3>Thông tin đơn hàng</h3>
            <div class="info-row">
                <span class="info-label">Mã đơn hàng:</span>
                <span>{{ $order->code }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ngày đặt hàng:</span>
                <span>{{ $order->order_date->format('d/m/Y') }}</span>
            </div>
            @if($order->expected_delivery)
            <div class="info-row">
                <span class="info-label">Ngày giao hàng dự kiến:</span>
                <span style="color: #dc3545; font-weight: bold;">{{ $order->expected_delivery->format('d/m/Y') }}</span>
            </div>
            @endif
            @if($order->delivery_address)
            <div class="info-row">
                <span class="info-label">Địa chỉ giao hàng:</span>
                <span>{{ $order->delivery_address }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Điều khoản thanh toán:</span>
                <span>{{ $order->payment_terms_label }}</span>
            </div>
        </div>

        <div class="info-box">
            <h3>Chi tiết sản phẩm đặt hàng</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">STT</th>
                        <th>Sản phẩm</th>
                        <th style="text-align: center;">ĐVT</th>
                        <th style="text-align: center;">SL</th>
                        <th style="text-align: right;">Đơn giá</th>
                        <th style="text-align: right;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td style="text-align: center;">{{ $item->unit }}</td>
                        <td style="text-align: center;">{{ number_format($item->quantity) }}</td>
                        <td style="text-align: right;">{{ number_format($item->unit_price) }}đ</td>
                        <td style="text-align: right;"><strong>{{ number_format($item->total) }}đ</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="info-box">
            <h3>Tổng kết</h3>
            <div class="info-row">
                <span class="info-label">Tổng tiền hàng:</span>
                <span>{{ number_format($order->subtotal) }}đ</span>
            </div>
            @if($order->discount_percent > 0)
            <div class="info-row">
                <span class="info-label">Chiết khấu ({{ $order->discount_percent }}%):</span>
                <span style="color: #28a745;">-{{ number_format($order->discount_amount) }}đ</span>
            </div>
            @endif
            @if($order->shipping_cost > 0)
            <div class="info-row">
                <span class="info-label">Phí vận chuyển:</span>
                <span>{{ number_format($order->shipping_cost) }}đ</span>
            </div>
            @endif
            @if($order->other_cost > 0)
            <div class="info-row">
                <span class="info-label">Chi phí khác:</span>
                <span>{{ number_format($order->other_cost) }}đ</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">VAT ({{ $order->vat_percent }}%):</span>
                <span>{{ number_format($order->vat_amount) }}đ</span>
            </div>
            <div class="info-row" style="font-size: 18px; padding-top: 15px; border-top: 2px solid #7c3aed;">
                <span class="info-label" style="color: #7c3aed;">TỔNG CỘNG:</span>
                <span style="color: #7c3aed; font-weight: bold;">{{ number_format($order->total) }}đ</span>
            </div>
        </div>

        @if($order->note)
        <div class="highlight">
            <strong>Ghi chú:</strong> {{ $order->note }}
        </div>
        @endif

        <div class="highlight" style="background: #d1fae5; border-color: #10b981;">
            <strong>Yêu cầu:</strong> Vui lòng xác nhận đơn hàng này trong vòng 24 giờ. 
            Nếu có bất kỳ thay đổi nào về giá hoặc thời gian giao hàng, xin vui lòng thông báo ngay cho chúng tôi.
        </div>

        <div class="footer">
            <p><strong>Trân trọng cảm ơn sự hợp tác của quý công ty!</strong></p>
            <p style="margin-top: 20px; font-size: 12px; color: #999;">
                Email này được gửi tự động từ hệ thống {{ config('app.name') }}.<br>
                Vui lòng liên hệ trực tiếp nếu cần hỗ trợ.
            </p>
        </div>
    </div>
</body>
</html>
