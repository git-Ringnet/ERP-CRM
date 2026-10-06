<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject ?? 'Báo giá' }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; line-height: 1.6; background-color: #f8fafc; margin: 0; padding: 24px;">
    <div style="max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #1e40af, #3b82f6); padding: 24px 28px; color: #ffffff;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.02em;">THÔNG TIN BÁO GIÁ</h2>
            <p style="margin: 4px 0 0; font-size: 13px; color: #dbeafe;">Mã số: <strong>{{ $quotation->code }}</strong></p>
        </div>

        <!-- Body -->
        <div style="padding: 28px;">
            <p style="margin-top: 0; font-size: 15px;">
                Kính gửi <strong>{{ $quotation->contact?->name ?: ($quotation->customer?->name ?: 'Quý khách hàng') }}</strong>,
            </p>

            @if(!empty($messageText))
                <div style="background-color: #f1f5f9; border-left: 4px solid #3b82f6; padding: 12px 16px; margin: 16px 0; border-radius: 0 8px 8px 0; font-size: 14px; color: #334155; white-space: pre-line;">
                    {{ $messageText }}
                </div>
            @else
                <p style="font-size: 14px; color: #4b5563;">
                    Chúng tôi xin trân trọng gửi tới Quý khách bảng báo giá chi tiết cho các hạng mục sản phẩm / dịch vụ theo thông tin dưới đây:
                </p>
            @endif

            <!-- Summary Table -->
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
                <tbody>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px 14px; color: #64748b; width: 140px; font-weight: 500;">Mã báo giá:</td>
                        <td style="padding: 10px 14px; font-weight: 700; color: #1e293b;">{{ $quotation->code }}</td>
                    </tr>
                    @if($quotation->title)
                    <tr style="border-bottom: 1px solid #f1f5f9; background-color: #f8fafc;">
                        <td style="padding: 10px 14px; color: #64748b; font-weight: 500;">Chủ đề / Dự án:</td>
                        <td style="padding: 10px 14px; color: #1e293b;">{{ $quotation->title }}</td>
                    </tr>
                    @endif
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px 14px; color: #64748b; font-weight: 500;">Khách hàng:</td>
                        <td style="padding: 10px 14px; color: #1e293b;">{{ $quotation->customer?->name ?: $quotation->customer_name }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9; background-color: #f8fafc;">
                        <td style="padding: 10px 14px; color: #64748b; font-weight: 500;">Hiệu lực đến:</td>
                        <td style="padding: 10px 14px; color: #b91c1c; font-weight: 600;">
                            {{ $quotation->valid_until ? $quotation->valid_until->format('d/m/Y') : 'Theo thỏa thuận' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 14px; color: #64748b; font-weight: 600; font-size: 14px;">Tổng giá trị:</td>
                        <td style="padding: 12px 14px; font-weight: 800; color: #2563eb; font-size: 16px;">
                            {{ number_format($quotation->total) }} đ
                        </td>
                    </tr>
                </tbody>
            </table>

            @if($hasAttachment)
                <div style="margin: 18px 0; padding: 12px 16px; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; font-size: 13px; color: #065f46; display: flex; align-items: center;">
                    <span>📎 <strong>Đính kèm:</strong> Bảng báo giá chi tiết (file Excel <code>.xlsx</code>) đã được đính kèm cùng thư này. Quý khách vui lòng tải về để xem chi tiết từng mã hàng hóa, đơn giá và quy cách.</span>
                </div>
            @endif

            <p style="font-size: 13px; color: #64748b; margin-top: 20px;">
                Nếu Quý khách có bất kỳ câu hỏi nào hoặc cần điều chỉnh thêm thông tin, xin vui lòng phản hồi trực tiếp qua email này.
            </p>

            <!-- Signature -->
            <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #475569;">
                <p style="margin: 0; font-weight: 700; color: #1e293b;">Trân trọng,</p>
                @if(!empty($salesName))
                    <p style="margin: 4px 0 2px; font-weight: 600; color: #2563eb; font-size: 14px;">{{ $salesName }}</p>
                @endif
                @if(!empty($salesEmail))
                    <p style="margin: 2px 0; color: #64748b;">Email liên hệ: <a href="mailto:{{ $salesEmail }}" style="color: #2563eb; text-decoration: none;">{{ $salesEmail }}</a></p>
                @endif
                <p style="margin: 2px 0; color: #94a3b8; font-size: 12px;">Được gửi tự động qua hệ thống ERP-CRM</p>
            </div>
        </div>
    </div>
</body>
</html>
