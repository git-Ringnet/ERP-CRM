<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn GTGT - {{ $sale->code }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
            background: #fff;
            padding: 1.5cm 1.8cm;
            line-height: 1.5;
        }

        /* ---- HEADER ---- */
        .header {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 12px;
        }
        .company-logo img { max-height: 70px; max-width: 120px; }
        .company-info { flex: 1; }
        .company-name {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .company-info p { font-size: 10.5pt; margin-bottom: 2px; }
        .company-info .label { font-style: italic; }

        .divider { border-top: 2px solid #000; margin: 8px 0; }
        .divider-thin { border-top: 1px solid #000; margin: 6px 0; }

        /* ---- INVOICE TITLE ---- */
        .invoice-title {
            text-align: center;
            margin: 8px 0 4px;
        }
        .invoice-title h1 {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .invoice-title .en { font-size: 11pt; font-style: italic; }

        /* ---- INVOICE META (Ký hiệu, Số, Ngày) ---- */
        .invoice-meta {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 6px 0;
        }
        .invoice-meta-left { text-align: center; flex: 1; }
        .invoice-meta-right { text-align: right; min-width: 200px; }
        .invoice-meta .no-value { color: #c00; font-weight: bold; font-size: 13pt; }
        .invoice-date { text-align: center; font-size: 11pt; margin-top: 4px; }
        .invoice-date span { font-style: italic; }
        .ma-cqt { text-align: center; font-size: 9.5pt; margin-top: 2px; }

        /* ---- BUYER INFO ---- */
        .buyer-section { margin: 10px 0; }
        .buyer-section table { width: 100%; border-collapse: collapse; }
        .buyer-section td { padding: 2px 0; font-size: 11pt; vertical-align: top; }
        .buyer-section .label { font-style: italic; width: 220px; }
        .buyer-section .value { font-weight: bold; }

        /* ---- PRODUCT TABLE ---- */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 11pt;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: middle;
        }
        .items-table thead th {
            text-align: center;
            font-weight: bold;
            background: #f5f5f5;
        }
        .items-table thead .sub { font-style: italic; font-size: 9.5pt; font-weight: normal; }
        .items-table thead tr:first-child th { background: #f5f5f5; }
        .items-table .index-row th { background: #ebebeb; font-size: 10pt; }
        .items-table tbody td.center { text-align: center; }
        .items-table tbody td.right,
        .items-table tbody td.right { text-align: right; }
        .items-table td.right { text-align: right; }
        .items-table td.italic { font-style: italic; }
        .items-table .total-row td { font-weight: bold; }
        .items-table .money-row td { font-style: italic; }

        /* ---- SIGNATURES ---- */
        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 30px;
            text-align: center;
        }
        .sig-box { flex: 1; }
        .sig-box .sig-title { font-weight: bold; font-size: 11pt; }
        .sig-box .sig-sub { font-style: italic; font-size: 9.5pt; }
        .sig-space { height: 60px; }

        /* ---- PRINT ---- */
        @media print {
            body { padding: 0.8cm 1cm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
@php
    use App\Models\Setting;
    use App\Helpers\NumberHelper;

    $companyName    = Setting::get('company_name', 'CÔNG TY TNHH RINGNET');
    $companyAddress = Setting::get('company_address', '');
    $companyPhone   = Setting::get('company_phone', '');
    $companyLogo    = Setting::get('company_logo');
    $companyFax     = Setting::get('company_fax', '');
    $companyWebsite = Setting::get('company_website', '');
    $companyEmail   = Setting::get('company_email', '');
    $companyTax     = Setting::get('company_tax', '');
    $companyBank    = Setting::get('company_bank_account', '');

    $isForeign = $sale->currency && !$sale->currency->is_base;
    $rate      = $sale->exchange_rate ?: 1;

    $subtotalVnd   = $sale->subtotal;
    $discountAmt   = round($subtotalVnd * ($sale->discount / 100));
    $afterDiscount = $subtotalVnd - $discountAmt;
    $vatAmt        = round($afterDiscount * ($sale->vat / 100));
    $totalVnd      = $sale->total;

    $amountInWords = NumberHelper::currencyToVietnameseWords($totalVnd);
@endphp

<div class="no-print" style="margin-bottom:16px; text-align:center;">
    <button onclick="window.print()" style="padding:8px 24px; font-size:13pt; cursor:pointer; background:#1a56db; color:#fff; border:none; border-radius:5px;">
        🖨️ In / Lưu PDF
    </button>
</div>

{{-- HEADER: Logo + Thông tin công ty --}}
<div class="header">
    @if($companyLogo && file_exists(public_path($companyLogo)))
    <div class="company-logo" style="padding-top:4px;">
        <img src="{{ asset($companyLogo) }}" alt="Logo {{ $companyName }}">
    </div>
    @endif
    <div class="company-info" style="flex:1;">
        <div class="company-name">{{ $companyName }}</div>
        <p><span class="label">Mã số thuế (Tax code):</span> <strong>{{ $companyTax }}</strong></p>
        @if($companyAddress)
        <p><span class="label">Địa chỉ (Address):</span> {{ $companyAddress }}</p>
        @endif
        <p>
            @if($companyPhone)<span class="label">Điện thoại (Tel):</span> {{ $companyPhone }}&nbsp;&nbsp;@endif
            @if($companyFax)<span class="label">Fax:</span> {{ $companyFax }}@endif
        </p>
        @if($companyWebsite || $companyEmail)
        <p>
            @if($companyWebsite)<span class="label">Website:</span> {{ $companyWebsite }}&nbsp;&nbsp;@endif
            @if($companyEmail)<span class="label">Email:</span> {{ $companyEmail }}@endif
        </p>
        @endif
        @if($companyBank)
        <p><span class="label">Số tài khoản (Bank account):</span> {{ $companyBank }}</p>
        @endif
    </div>
</div>

<div class="divider"></div>

{{-- INVOICE TITLE --}}
<div class="invoice-title">
    <h1>HÓA ĐƠN GIÁ TRỊ GIA TĂNG</h1>
    <div class="en">VAT Invoice</div>
</div>

{{-- Ký hiệu / Số --}}
<div class="invoice-meta">
    <div style="flex:1;"></div>
    <div style="text-align:right; font-size:11pt;">
        <div>Ký hiệu <span class="label">(Sign)</span>: <strong>{{ strtoupper(substr($sale->code, 0, 8)) }}</strong></div>
        <div>Số <span class="label">(No.)</span>: <span class="no-value">{{ str_pad($sale->id, 8, '0', STR_PAD_LEFT) }}</span></div>
    </div>
</div>

{{-- Ngày --}}
<div class="invoice-date">
    <span>Ngày </span><strong>{{ $sale->date->format('d') }}</strong>
    <span> tháng </span><strong>{{ $sale->date->format('m') }}</strong>
    <span> năm </span><strong>{{ $sale->date->format('Y') }}</strong>
</div>

<div class="divider-thin"></div>

{{-- THÔNG TIN NGƯỜI MUA --}}
<div class="buyer-section">
    <table>
        <tr>
            <td class="label" style="width:200px;">Họ tên người mua hàng <em>(Buyer)</em>:</td>
            <td class="value">{{ $sale->customer_name }}</td>
        </tr>
        <tr>
            <td class="label">Tên đơn vị <em>(Company's name)</em>:</td>
            <td class="value">{{ $sale->customer->company ?? $sale->customer_name }}</td>
        </tr>
        <tr>
            <td class="label">Mã số thuế <em>(Tax code)</em>:</td>
            <td>{{ $sale->customer->tax_code ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Địa chỉ <em>(Address)</em>:</td>
            <td>{{ $sale->customer->address ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Hình thức thanh toán <em>(Payment method)</em>:</td>
            <td>
                Chuyển khoản &nbsp;&nbsp;
                <span class="label">Số tài khoản <em>(Bank account)</em>:</span>
                {{ $sale->customer->bank_account ?? '' }}
            </td>
        </tr>
    </table>
</div>

{{-- BẢNG SẢN PHẨM --}}
<table class="items-table">
    <thead>
        {{-- Hàng 1: Tiêu đề cột --}}
        <tr>
            <th style="width:40px;">STT<br><span class="sub">(No)</span></th>
            <th>Tên hàng hóa, dịch vụ<br><span class="sub">(Name of goods and services)</span></th>
            <th style="width:55px;">DVT<br><span class="sub">(Unit)</span></th>
            <th style="width:70px;">Số lượng<br><span class="sub">(Quantity)</span></th>
            <th style="width:120px;">Đơn giá<br><span class="sub">(Unit price)</span></th>
            <th style="width:130px;">Thành tiền<br><span class="sub">(Amount)</span></th>
        </tr>
        {{-- Hàng 2: Chỉ số cột A, B, C, 1, 2, 3=1x2 --}}
        <tr class="index-row">
            <th>A</th>
            <th>B</th>
            <th>C</th>
            <th>1</th>
            <th>2</th>
            <th>3=1x2</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $index => $item)
        @php
            $itemPrice = $isForeign ? round($item->price * $rate) : $item->price;
            $itemTotal = $isForeign ? round($item->total * $rate) : $item->total;
        @endphp
        <tr>
            <td class="center">{{ $index + 1 }}</td>
            <td>{{ $item->product_name }}</td>
            <td class="center">{{ $item->product->unit ?? 'Cái' }}</td>
            <td class="center">{{ number_format($item->quantity) }}</td>
            <td class="right">{{ number_format($itemPrice) }}</td>
            <td class="right">{{ number_format($itemTotal) }}</td>
        </tr>
        @endforeach

        {{-- Dòng trống (tối thiểu 8 dòng) --}}
        @for($i = $sale->items->count(); $i < 8; $i++)
        <tr style="height:24px;"><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor

        {{-- Cộng tiền hàng --}}
        <tr class="total-row">
            <td colspan="4" class="right italic">Cộng tiền hàng <em>(Total before VAT)</em>:</td>
            <td></td>
            <td class="right">{{ number_format($subtotalVnd) }}</td>
        </tr>

        @if($sale->discount > 0)
        <tr>
            <td colspan="4" class="right">Chiết khấu thương mại ({{ $sale->discount }}%):</td>
            <td></td>
            <td class="right">-{{ number_format($discountAmt) }}</td>
        </tr>
        @endif

        {{-- Thuế suất GTGT --}}
        <tr>
            <td colspan="2">Thuế suất GTGT <em>(VAT rate)</em>: <strong>{{ $sale->vat > 0 ? $sale->vat.'%' : 'KCT' }}</strong></td>
            <td colspan="2" class="italic">Tiền thuế GTGT <em>(VAT amount)</em>:</td>
            <td></td>
            <td class="right">{{ number_format($vatAmt) }}</td>
        </tr>

        {{-- Tổng cộng --}}
        <tr class="total-row">
            <td colspan="4" class="right italic">Tổng tiền thanh toán <em>(Total amount)</em>:</td>
            <td></td>
            <td class="right">{{ number_format($totalVnd) }}</td>
        </tr>

        {{-- Số tiền bằng chữ --}}
        <tr class="money-row">
            <td colspan="6">
                Số tiền viết bằng chữ <em>(Total amount in words)</em>: <strong>{{ $amountInWords }}</strong>
            </td>
        </tr>
    </tbody>
</table>

{{-- CHỮ KÝ --}}
<div class="signatures">
    <div class="sig-box">
        <div class="sig-title">Người mua hàng <em>(Buyer)</em></div>
        <div class="sig-sub">(Ký, ghi rõ họ, tên)</div>
        <div class="sig-sub"><em>(Signature, full name)</em></div>
        <div class="sig-space"></div>
    </div>
    <div class="sig-box">
        <div class="sig-title">Người bán hàng <em>(Seller)</em></div>
        <div class="sig-sub">(Ký, ghi rõ họ, tên)</div>
        <div class="sig-sub"><em>(Signature, full name)</em></div>
        <div class="sig-space"></div>
        <div style="font-weight:bold; text-transform:uppercase; font-size:10pt; color:#b00;">
            {{ $companyName }}
        </div>
        <div class="sig-sub">Ký ngày: {{ $sale->date->format('d/m/Y') }}</div>
        <div style="font-size:9pt; font-style:italic; margin-top:6px;">
            (Cần kiểm tra, đối chiếu trước khi lập, giao, nhận hóa đơn)
        </div>
    </div>
</div>
</body>
</html>
