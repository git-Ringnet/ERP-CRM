<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>BaoGia_{{ $quotation->code }}</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 11pt; }
        table { border-collapse: collapse; }
        td, th { padding: 4px 6px; vertical-align: top; }
        .border { border: 1px solid black; }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .italic { font-style: italic; }
    </style>
</head>
<body>
@php
    use App\Models\Setting;
    $companyName    = Setting::get('company_name', 'CÔNG TY TNHH RINGNET');
    $companyAddress = Setting::get('company_address', 'TP. Hồ Chí Minh');
    $companyPhone   = Setting::get('company_phone', '');
    $companyEmail   = Setting::get('company_email', '');
    $companyTax     = Setting::get('company_tax_code', Setting::get('company_tax', ''));

    $isForeign = $quotation->currency && !$quotation->currency->is_base;
    $rate      = $quotation->exchange_rate ?: 1;
    $decimals  = $quotation->currency->decimal_places ?? 2;
    $symbol    = $quotation->currency->symbol ?? $quotation->currency->code ?? '';

    $customColumns = $quotation->custom_columns ?? [];
    if (!is_array($customColumns)) {
        $customColumns = [];
    }
    $customColumns = array_values(array_filter($customColumns, fn($col) => !in_array($col, ['product_id', 'quantity', 'price', 'vat', 'row_total'])));
    $totalCols = 6 + count($customColumns);

    $subtotalForeign = $isForeign ? $quotation->items->sum('total') : $quotation->subtotal;
    $subtotalVnd = $isForeign ? round($subtotalForeign * $rate) : $quotation->subtotal;

    $discountForeign = round($subtotalForeign * ($quotation->discount / 100), $decimals);
    $discountVnd = $isForeign ? round($discountForeign * $rate) : round($subtotalVnd * ($quotation->discount / 100));

    $vatForeign = $quotation->items->sum('vat_amount');
    $vatVnd = $quotation->vat_amount ?: round($vatForeign * $rate);

    $totalForeign = $quotation->total_foreign ?: round($subtotalForeign - $discountForeign + $vatForeign, $decimals);
    $totalVnd = $quotation->total;
@endphp

<table>
    {{-- Row 1: Company Name --}}
    <tr>
        <td colspan="{{ $totalCols }}" class="bold" style="font-size:12pt; color:#2c3e50;">{{ strtoupper($companyName) }}</td>
    </tr>
    {{-- Row 2: Address --}}
    <tr>
        <td colspan="{{ $totalCols }}" style="color:#555;">Địa chỉ: {{ $companyAddress }}</td>
    </tr>
    {{-- Row 3: Phone / Email / MST --}}
    <tr>
        <td colspan="{{ $totalCols }}" style="color:#555;">
            ĐT: {{ $companyPhone }} 
            @if($companyEmail)  |  Email: {{ $companyEmail }} @endif
            @if($companyTax)  |  MST: {{ $companyTax }} @endif
        </td>
    </tr>
    {{-- Row 4: Blank --}}
    <tr><td colspan="{{ $totalCols }}"></td></tr>

    {{-- Row 5: Title --}}
    <tr>
        <td colspan="{{ $totalCols }}" class="bold center" style="font-size:16pt; color:#3498db;">BÁO GIÁ</td>
    </tr>
    {{-- Row 6: Code & Date --}}
    <tr>
        <td colspan="{{ $totalCols }}" class="italic center" style="color:#555;">
            Số: {{ $quotation->code }}  |  Ngày lập: {{ $quotation->date->format('d/m/Y') }}  |  Hiệu lực đến: {{ $quotation->valid_until->format('d/m/Y') }}
        </td>
    </tr>
    {{-- Row 7: Blank --}}
    <tr><td colspan="{{ $totalCols }}"></td></tr>

    {{-- Row 8: Customer Title --}}
    <tr>
        <td colspan="{{ $totalCols }}" class="bold" style="font-size:11pt; border-bottom: 1px solid #ccc; padding-bottom: 3px;">KÍNH GỬI QUÝ KHÁCH HÀNG:</td>
    </tr>
    {{-- Row 9: Customer Name & Tax Code --}}
    <tr>
        <td colspan="{{ (int)floor($totalCols/2) }}">Đơn vị: <b>{{ $quotation->customer_name }}</b></td>
        <td colspan="{{ (int)ceil($totalCols/2) }}">MST: {{ $quotation->customer->tax_code ?? 'N/A' }}</td>
    </tr>
    {{-- Row 10: Address & Phone --}}
    <tr>
        <td colspan="{{ (int)floor($totalCols/2) }}">Địa chỉ: {{ $quotation->customer->address ?? 'N/A' }}</td>
        <td colspan="{{ (int)ceil($totalCols/2) }}">Điện thoại: {{ $quotation->customer->phone ?? 'N/A' }}</td>
    </tr>
    {{-- Row 11: Contact Person / Position --}}
    <tr>
        <td colspan="{{ $totalCols }}">
            Người liên hệ: 
            @if($quotation->contact)
                <b>{{ $quotation->contact->name }}</b>
                @if($quotation->contact->position) ({{ $quotation->contact->position }}) @endif
                @if($quotation->contact->phone)  -  ĐT: {{ $quotation->contact->phone }} @endif
                @if($quotation->contact->email)  -  Email: {{ $quotation->contact->email }} @endif
            @else
                N/A
            @endif
        </td>
    </tr>
    {{-- Row 12: Title / Subject --}}
    <tr>
        <td colspan="{{ $totalCols }}">Tiêu đề / Nội dung: <b>{{ $quotation->title }}</b></td>
    </tr>
    {{-- Row 13: Blank --}}
    <tr><td colspan="{{ $totalCols }}"></td></tr>

    {{-- Row 14: Header bảng sản phẩm --}}
    <tr>
        <th class="border bold center" style="background-color: #3498db; color: #ffffff;">STT</th>
        <th class="border bold center">Tên hàng hóa, dịch vụ / Mô tả</th>
        <th class="border bold center">Số lượng</th>
        <th class="border bold center">Đơn giá</th>
        <th class="border bold center">VAT (%)</th>
        @foreach($customColumns as $colName)
            <th class="border bold center">{{ $colName }}</th>
        @endforeach
        <th class="border bold center">Thành tiền</th>
    </tr>

    {{-- Sản phẩm --}}
    @foreach($quotation->items as $index => $item)
    @php
        $priceVal = $item->price;
        $totalVal = $item->total;
    @endphp
    <tr>
        <td class="border center">{{ $index + 1 }}</td>
        <td class="border left">
            <b>{{ $item->product_code ?: $item->product_name }}</b>
            @if($item->product_code)
                <br><span style="color: #555; font-size: 10pt;">{!! nl2br(e($item->description ?: $item->product_name)) !!}</span>
            @else
                @if($item->description)
                    <br><span style="color: #555; font-size: 10pt;">{!! nl2br(e($item->description)) !!}</span>
                @endif
            @endif
        </td>
        <td class="border center">{{ number_format($item->quantity) }}</td>
        <td class="border right">
            @if($isForeign)
                {{ $symbol }}{{ number_format($priceVal, $decimals, '.', ',') }}
            @else
                {{ number_format($priceVal) }}
            @endif
        </td>
        <td class="border center">{{ $item->vat == -1 ? 'KCT' : (float)$item->vat . '%' }}</td>
        @foreach($customColumns as $colName)
            <td class="border left">{{ $item->custom_fields[$colName] ?? '' }}</td>
        @endforeach
        <td class="border right font-medium">
            @if($isForeign)
                {{ $symbol }}{{ number_format($totalVal, $decimals, '.', ',') }}
            @else
                {{ number_format($totalVal) }}
            @endif
        </td>
    </tr>
    @endforeach

    {{-- Cộng tiền hàng --}}
    <tr>
        <td colspan="{{ $totalCols - 1 }}" class="border bold">Cộng tiền hàng:</td>
        <td class="border right bold">
            @if($isForeign)
                {{ $symbol }}{{ number_format($subtotalForeign, $decimals, '.', ',') }}
            @else
                {{ number_format($subtotalVnd) }}
            @endif
        </td>
    </tr>

    {{-- Chiết khấu --}}
    @if($quotation->discount > 0)
    <tr>
        <td colspan="{{ $totalCols - 1 }}" class="border">Chiết khấu thương mại ({{ (float)$quotation->discount }}%):</td>
        <td class="border right text-red-600">
            @if($isForeign)
                -{{ $symbol }}{{ number_format($discountForeign, $decimals, '.', ',') }}
            @else
                -{{ number_format($discountVnd) }}
            @endif
        </td>
    </tr>
    @endif

    {{-- Thuế VAT --}}
    <tr>
        <td colspan="{{ $totalCols - 1 }}" class="border">Thuế VAT:</td>
        <td class="border right" style="color: #3498db;">
            @if($isForeign)
                {{ $symbol }}{{ number_format($vatForeign, $decimals, '.', ',') }}
            @else
                {{ number_format($vatVnd) }}
            @endif
        </td>
    </tr>

    {{-- TỔNG CỘNG --}}
    <tr>
        <td colspan="{{ $totalCols - 1 }}" class="border bold" style="background-color: #f8f9fa;">TỔNG CỘNG THANH TOÁN:</td>
        <td class="border right bold" style="background-color: #f8f9fa; color: #3498db;">
            @if($isForeign)
                {{ $symbol }}{{ number_format($totalForeign, $decimals, '.', ',') }}
            @else
                {{ number_format($totalVnd) }}
            @endif
        </td>
    </tr>

    @if($isForeign)
    {{-- Tỷ giá & Quy đổi VND --}}
    <tr>
        <td colspan="{{ $totalCols - 1 }}" class="italic">Quy đổi tương đương (Tỷ giá: {{ number_format($rate, 0, ',', '.') }}):</td>
        <td class="border right bold italic">{{ number_format($totalVnd) }} đ</td>
    </tr>
    @endif

    {{-- blank --}}
    <tr><td colspan="{{ $totalCols }}"></td></tr>

    {{-- Điều khoản --}}
    @if($quotation->payment_terms || $quotation->delivery_time || !empty($quotation->note_array) || !empty($quotation->disclaimer_array))
        <tr>
            <td colspan="{{ $totalCols }}" class="bold" style="font-size: 11pt; text-decoration: underline;">ĐIỀU KHOẢN & GHI CHÚ:</td>
        </tr>
        @if($quotation->payment_terms)
            <tr>
                <td colspan="{{ $totalCols }}"><strong>- Điều khoản thanh toán:</strong> {{ $quotation->payment_terms }}</td>
            </tr>
        @endif
        @if($quotation->delivery_time)
            <tr>
                <td colspan="{{ $totalCols }}"><strong>- Thời gian giao hàng:</strong> {{ $quotation->delivery_time }}</td>
            </tr>
        @endif
        @if(!empty($quotation->note_array))
            <tr>
                <td colspan="{{ $totalCols }}" class="bold">- Ghi chú:</td>
            </tr>
            @foreach($quotation->note_array as $i => $item)
                <tr>
                    <td colspan="{{ $totalCols }}">    ({{ $i + 1 }}) {{ $item }}</td>
                </tr>
            @endforeach
        @endif
        @if(!empty($quotation->disclaimer_array))
            <tr>
                <td colspan="{{ $totalCols }}" class="bold" style="color: #c0392b;">- Cảnh báo / Lưu ý:</td>
            </tr>
            @foreach($quotation->disclaimer_array as $i => $item)
                <tr>
                    <td colspan="{{ $totalCols }}" style="color: #c0392b;">    ({{ $i + 1 }}) {{ $item }}</td>
                </tr>
            @endforeach
        @endif
        {{-- blank --}}
        <tr><td colspan="{{ $totalCols }}"></td></tr>
    @endif

    {{-- blank --}}
    <tr><td colspan="{{ $totalCols }}"></td></tr>

    {{-- Ký tên --}}
    <tr>
        <td colspan="{{ (int)floor($totalCols/2) }}" class="bold center">Khách hàng</td>
        <td colspan="{{ (int)ceil($totalCols/2) }}" class="bold center">Người lập báo giá</td>
    </tr>
    <tr>
        <td colspan="{{ (int)floor($totalCols/2) }}" class="italic center">(Ký, ghi rõ họ tên)</td>
        <td colspan="{{ (int)ceil($totalCols/2) }}" class="italic center">(Ký, ghi rõ họ tên)</td>
    </tr>
    {{-- Spacer for signatures --}}
    <tr>
        <td colspan="{{ (int)floor($totalCols/2) }}" style="height: 60px;"></td>
        <td colspan="{{ (int)ceil($totalCols/2) }}" style="height: 60px;"></td>
    </tr>
    <tr>
        <td colspan="{{ (int)floor($totalCols/2) }}" class="center"><b>{{ $quotation->customer_name }}</b></td>
        <td colspan="{{ (int)ceil($totalCols/2) }}" class="center"><b>{{ $quotation->creator->name ?? '' }}</b></td>
    </tr>
</table>
</body>
</html>
