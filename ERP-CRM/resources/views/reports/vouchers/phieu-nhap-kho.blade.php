<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu Nhập Kho - {{ $import->code }}</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; margin: 0; padding: 1.5cm; line-height: 1.3; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .company-info { width: 60%; }
        .voucher-code { text-align: right; width: 40%; font-size: 11pt; }
        .title { text-align: center; margin: 10px 0; }
        .title h1 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        .title p { margin: 5px 0; font-style: italic; }
        .info-table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 3px 0; }
        .items-table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid black; padding: 5px; text-align: center; }
        .items-table th { background-color: #f2f2f2; }
        .signatures { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 40px; text-align: center; font-size: 11pt; }
        .signature-box strong { display: block; margin-bottom: 60px; }
        .footer-date { text-align: right; font-style: italic; margin-top: 20px; }
        @media print {
            body { padding: 0.5cm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">In Phiếu Nhập Kho</button>
    </div>

    <div class="header">
        <div class="company-info">
            <strong>Đơn vị:</strong> CÔNG TY TNHH RINGNET<br>
            <strong>Địa chỉ:</strong> TP. Hồ Chí Minh
        </div>
        <div class="voucher-code">
            <strong>Mẫu số 01 - VT</strong><br>
            (Ban hành theo Thông tư số 133/2016/TT-BTC <br> ngày 26/8/2016 của Bộ Tài chính)
        </div>
    </div>

    <div class="title">
        <h1>PHIẾU NHẬP KHO</h1>
        <p>Ngày {{ $import->date->format('d') }} tháng {{ $import->date->format('m') }} năm {{ $import->date->format('Y') }}</p>
        <p>Số: {{ $import->code }}</p>
        
    </div>

    <table class="info-table">
        <tr><td>- Họ và tên người giao hàng: {{ $import->supplier->name ?? '........................................................................................' }}</td></tr>
        <tr><td>- Theo {{ $import->purchaseOrder->code ?? '........................' }} số ................. ngày ........ tháng ....... năm ........... của ...........................................</td></tr>
        <tr><td>- Nhập tại kho: {{ $import->warehouse->name ?? '.......................' }} Địa điểm: {{ $import->warehouse->address ?? '........................................' }}</td></tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th rowspan="2">STT</th>
                <th rowspan="2">Tên, nhãn hiệu, quy cách, phẩm chất vật tư, dụng cụ sản phẩm, hàng hóa</th>
                <th rowspan="2">Mã số</th>
                <th rowspan="2">Đơn vị tính</th>
                <th rowspan="2">Số Serial</th>
                <th colspan="2">Số lượng</th>
                <th rowspan="2">Đơn giá</th>
                <th rowspan="2">Thành tiền</th>
            </tr>
            <tr>
                <th>Theo chứng từ</th>
                <th>Thực nhập</th>
            </tr>
        </thead>
        <tbody>
            @foreach($import->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="text-align: left;">{{ $item->product->name }}</td>
                <td>{{ $item->product->code }}</td>
                <td>{{ $item->unit ?? 'Cái' }}</td>
                <td style="text-align: left; font-size: 10pt; word-break: break-all;">
                    @php
                        $serials = [];
                        if (!empty($item->serial_number)) {
                            $decoded = json_decode($item->serial_number, true);
                            if (is_array($decoded)) {
                                $serials = $decoded;
                            } elseif (is_string($item->serial_number)) {
                                $serials = [$item->serial_number];
                            }
                        }
                    @endphp
                    @if(!empty($serials))
                        {{ implode(', ', $serials) }}
                    @endif
                </td>
                <td>{{ number_format($item->quantity) }}</td>
                <td>{{ number_format($item->quantity) }}</td>
                <td>{{ number_format($item->cost ?? 0) }}</td>
                <td>{{ number_format(($item->cost ?? 0) * $item->quantity) }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="5"><strong>Cộng</strong></td>
                <td><strong>{{ number_format($import->items->sum('quantity')) }}</strong></td>
                <td><strong>{{ number_format($import->items->sum('quantity')) }}</strong></td>
                <td>x</td>
                <td><strong>{{ number_format($import->items->sum(function($i){ return ($i->cost ?? 0) * $i->quantity; })) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <p>- Tổng số tiền (viết bằng chữ): .....................................................................................................................</p>
    <p>- Số chứng từ gốc kèm theo: ............................................................................................................................</p>

    <div class="footer-date">
        Ngày {{ $import->date->format('d') }} tháng {{ $import->date->format('m') }} năm {{ $import->date->format('Y') }}
    </div>

    <div class="signatures">
        <div class="signature-box">
            <strong>Người lập phiếu</strong>
            (Ký, họ tên)
        </div>
        <div class="signature-box">
            <strong>Người giao hàng</strong>
            (Ký, họ tên)
        </div>
        <div class="signature-box">
            <strong>Thủ kho</strong>
            (Ký, họ tên)
        </div>
        <div class="signature-box">
            <strong>Kế toán trưởng</strong>
            (Hoặc bộ phận có nhu cầu lập) <br> (Ký, họ tên)
        </div>
        <div class="signature-box">
            <strong>Giám đốc</strong>
            (Ký, họ tên, đóng dấu)
        </div>
    </div>
</body>
</html>
