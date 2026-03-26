<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu Thu - {{ $transaction->reference_number ?? $transaction->id }}</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 13pt; margin: 0; padding: 2cm; line-height: 1.3; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .company-info { width: 60%; }
        .voucher-code { text-align: right; width: 40%; }
        .title { text-align: center; margin: 20px 0; }
        .title h1 { margin: 0; font-size: 18pt; text-transform: uppercase; }
        .title p { margin: 5px 0; font-style: italic; }
        .content { margin-bottom: 30px; }
        .content-row { margin-bottom: 8px; display: flex; }
        .label { min-width: 150px; }
        .dots { flex-grow: 1; border-bottom: 1px dotted #000; position: relative; top: -4px; margin-left: 5px; }
        .value { padding-left: 5px; font-weight: bold; }
        .signatures { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-top: 50px; text-align: center; }
        .signature-box { font-size: 11pt; }
        .signature-box strong { display: block; margin-bottom: 60px; }
        .footer-date { text-align: right; font-style: italic; margin-top: 30px; }
        @media print {
            body { padding: 0.5cm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">In Phiếu Thu</button>
    </div>

    <div class="header">
        <div class="company-info">
            <strong>Đơn vị:</strong> {{ \App\Models\Setting::get('company_name', 'CÔNG TY TNHH RINGNET') }}<br>
            <strong>Địa chỉ:</strong> {{ \App\Models\Setting::get('company_address', 'TP. Hồ Chí Minh') }}
        </div>
        <div class="voucher-code">
            <strong>Mẫu số 01 - TT</strong><br>
            (Ban hành theo Thông tư số 133/2016/TT-BTC <br> ngày 26/8/2016 của Bộ Tài chính)
        </div>
    </div>

    <div class="title">
        <h1>PHIẾU THU</h1>
        <p>Ngày {{ $transaction->date->format('d') }} tháng {{ $transaction->date->format('m') }} năm {{ $transaction->date->format('Y') }}</p>
        <p>Quyển số: ....................<br>Số: {{ $transaction->reference_number ?? ('PT-'.str_pad($transaction->id, 5, '0', STR_PAD_LEFT)) }}</p>
        <p>Nợ: 1111<br>Có: {{ $transaction->category->misa_code ?? '131' }}</p>
    </div>

    <div class="content">
        <div class="content-row">
            <span class="label">Họ và tên người nộp tiền:</span>
            <span class="value">{{ $transaction->payer_name ?? '..................................................................................................' }}</span>
        </div>
        <div class="content-row">
            <span class="label">Địa chỉ:</span>
            <span class="value">{{ $transaction->address ?? '............................................................................................................' }}</span>
        </div>
        <div class="content-row">
            <span class="label">Lý do nộp:</span>
            <span class="value">{{ $transaction->note ?? $transaction->category->name }}</span>
        </div>
        <div class="content-row">
            <span class="label">Số tiền:</span>
            <span class="value">{{ number_format($transaction->amount, 0, ',', '.') }} VND</span>
        </div>
        <div class="content-row">
            <span class="label">(Viết bằng chữ):</span>
            <span class="value" style="font-style: italic; font-weight: normal;">..........................................................................................................</span>
        </div>
        <div class="content-row">
            <span class="label">Kèm theo:</span>
            <span>................ Chứng từ gốc.</span>
        </div>
    </div>

    <div class="footer-date">
        Ngày {{ $transaction->date->format('d') }} tháng {{ $transaction->date->format('m') }} năm {{ $transaction->date->format('Y') }}
    </div>

    <div class="signatures">
        <div class="signature-box">
            <strong>Giám đốc</strong>
            (Ký, họ tên, đóng dấu)
        </div>
        <div class="signature-box">
            <strong>Kế toán trưởng</strong>
            (Ký, họ tên)
        </div>
        <div class="signature-box">
            <strong>Người nộp tiền</strong>
            (Ký, họ tên)
        </div>
        <div class="signature-box">
            <strong>Người lập phiếu</strong>
            (Ký, họ tên)
        </div>
        <div class="signature-box">
            <strong>Thủ quỹ</strong>
            (Ký, họ tên)
        </div>
    </div>

    <div style="margin-top: 80px;">
        Đã nhận đủ số tiền (viết bằng chữ): .....................................................................................................................
    </div>
</body>
</html>
