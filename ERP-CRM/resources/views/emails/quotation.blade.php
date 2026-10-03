<!doctype html>
<html lang="vi">
<body style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.55">
    <p>Kính gửi Quý khách,</p>
    <p>{{ $messageText ?: 'Chúng tôi xin gửi Quý khách báo giá theo thông tin dưới đây.' }}</p>
    <table style="border-collapse:collapse;margin:16px 0">
        <tr><td style="padding:4px 16px 4px 0;color:#6b7280">Mã báo giá</td><td><strong>{{ $quotation->code }}</strong></td></tr>
        <tr><td style="padding:4px 16px 4px 0;color:#6b7280">Nội dung</td><td>{{ $quotation->title }}</td></tr>
        <tr><td style="padding:4px 16px 4px 0;color:#6b7280">Hiệu lực đến</td><td>{{ $quotation->valid_until?->format('d/m/Y') }}</td></tr>
        <tr><td style="padding:4px 16px 4px 0;color:#6b7280">Tổng giá trị</td><td><strong>{{ number_format($quotation->total) }} đ</strong></td></tr>
    </table>
    <p>Trân trọng.</p>
</body>
</html>
