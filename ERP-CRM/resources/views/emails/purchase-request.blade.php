<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu báo giá {{ $request->code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .info-box h3 {
            margin-top: 0;
            color: #3b82f6;
            border-bottom: 2px solid #3b82f6;
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

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        th {
            background: #3b82f6;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            color: #666;
            font-size: 14px;
        }

        .highlight {
            background: #dbeafe;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            margin: 20px 0;
        }

        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .priority-normal {
            background: #e5e7eb;
            color: #374151;
        }

        .priority-high {
            background: #fef3c7;
            color: #92400e;
        }

        .priority-urgent {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>YÊU CẦU BÁO GIÁ</h1>
        <p style="margin: 15px 0 0 0; font-size: 20px; letter-spacing: 1px;">{{ $request->code }}</p>
    </div>

    <div class="content">
        <p style="font-size: 16px;">Kính gửi <strong>{{ $supplier->name }}</strong>,</p>
        <p>Chúng tôi gửi đến quý công ty yêu cầu báo giá các sản phẩm sau. Kính mong quý công ty phản hồi báo giá trong
            thời hạn quy định.</p>

        <div class="info-box">
            <h3>Thông tin yêu cầu</h3>
            <div class="info-row">
                <span class="info-label">Mã yêu cầu:</span>
                <span>{{ $request->code }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tiêu đề:</span>
                <span>{{ $request->title }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Hạn báo giá:</span>
                <span style="color: #dc3545; font-weight: bold;">{{ $request->deadline->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Mức ưu tiên:</span>
                <span>
                    @if($request->priority == 'urgent')
                        <span class="priority-badge priority-urgent">Khẩn cấp</span>
                    @elseif($request->priority == 'high')
                        <span class="priority-badge priority-high">Cao</span>
                    @else
                        <span class="priority-badge priority-normal">Bình thường</span>
                    @endif
                </span>
            </div>
        </div>

        <div class="info-box">
            <h3>Danh sách sản phẩm cần báo giá</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">STT</th>
                        <th>Tên sản phẩm</th>
                        <th style="text-align: center;">ĐVT</th>
                        <th style="text-align: center;">Số lượng</th>
                        <th>Quy cách/Mô tả</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($request->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $item->product_name }}</strong></td>
                            <td style="text-align: center;">{{ $item->unit ?? 'Cái' }}</td>
                            <td style="text-align: center;">{{ number_format($item->quantity) }}</td>
                            <td>{{ $item->specifications ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($request->requirements)
            <div class="highlight">
                <strong>Yêu cầu đặc biệt:</strong><br>
                {{ $request->requirements }}
            </div>
        @endif

        <div class="highlight" style="background: #d1fae5; border-color: #10b981;">
            <strong>Vui lòng gửi báo giá bao gồm:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Đơn giá chi tiết cho từng sản phẩm</li>
                <li>Thời gian giao hàng dự kiến</li>
                <li>Điều kiện thanh toán</li>
                <li>Thời hạn hiệu lực của báo giá</li>
                <li>Chính sách bảo hành (nếu có)</li>
            </ul>
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