<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>HoaDon</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 11pt; }
        table { border-collapse: collapse; }
        td, th { padding: 3px 5px; vertical-align: middle; }
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
    $companyTax     = Setting::get('company_tax', '');

    $isForeign = $sale->currency && !$sale->currency->is_base;
    $rate      = $sale->exchange_rate ?: 1;

    $subtotalVnd   = $sale->subtotal;
    $discountAmt   = round($subtotalVnd * ($sale->discount / 100));
    $afterDiscount = $subtotalVnd - $discountAmt;
    $vatAmt        = round($afterDiscount * ($sale->vat / 100));
    $totalVnd      = $sale->total;
@endphp

{{-- ONE single table — PhpSpreadsheet maps each <tr> to a row, each <td> to a cell --}}
<table>
    {{-- Row 1: Tên công ty --}}
    <tr>
        <td colspan="6" class="bold">{{ strtoupper($companyName) }}</td>
    </tr>
    {{-- Row 2: Địa chỉ --}}
    <tr>
        <td colspan="6">Địa chỉ: {{ $companyAddress }}</td>
    </tr>
    {{-- Row 3: ĐT / MST --}}
    <tr>
        <td colspan="3">ĐT: {{ $companyPhone }}</td>
        <td colspan="3">MST: {{ $companyTax }}</td>
    </tr>
    {{-- Row 4: blank --}}
    <tr><td colspan="6"></td></tr>

    {{-- Row 5: Tiêu đề HÓA ĐƠN BÁN HÀNG --}}
    <tr>
        <td colspan="6" class="bold center" style="font-size:16pt;">HÓA ĐƠN BÁN HÀNG</td>
    </tr>
    {{-- Row 6: Mẫu số / Ký hiệu --}}
    <tr>
        <td colspan="6" class="italic center">Mẫu số: 01-GTGT &nbsp; Ký hiệu: {{ substr($sale->code, 0, 8) }}</td>
    </tr>
    {{-- Row 7: Số / Ngày --}}
    <tr>
        <td colspan="6" class="italic center">
            Số: {{ $sale->code }} &nbsp;|&nbsp; Ngày {{ $sale->date->format('d') }} tháng {{ $sale->date->format('m') }} năm {{ $sale->date->format('Y') }}
        </td>
    </tr>
    {{-- Row 8: blank --}}
    <tr><td colspan="6"></td></tr>

    {{-- Row 9: Tên KH --}}
    <tr>
        <td colspan="4">Tên người mua hàng: <b>{{ $sale->customer_name }}</b></td>
        <td colspan="2">MST: {{ $sale->customer->tax_code ?? '' }}</td>
    </tr>
    {{-- Row 10: Địa chỉ KH --}}
    <tr>
        <td colspan="6">Địa chỉ: {{ $sale->customer->address ?? '' }}</td>
    </tr>
    {{-- Row 11: HTTT --}}
    <tr>
        <td colspan="4">Hình thức thanh toán: Chuyển khoản / Tiền mặt</td>
        <td colspan="2">Số TK: {{ $sale->customer->bank_account ?? '' }}</td>
    </tr>
    {{-- Row 12: blank --}}
    <tr><td colspan="6"></td></tr>

    {{-- Row 13: Header bảng sản phẩm --}}
    <tr>
        <th class="border bold center" style="width:40px;">STT</th>
        <th class="border bold center">Tên hàng hóa, dịch vụ</th>
        <th class="border bold center" style="width:60px;">ĐVT</th>
        <th class="border bold center" style="width:70px;">Số lượng</th>
        <th class="border bold center" style="width:130px;">Đơn giá</th>
        <th class="border bold center" style="width:150px;">Thành tiền</th>
    </tr>

    {{-- Sản phẩm --}}
    @foreach($sale->items as $index => $item)
    @php
        $itemPrice = $isForeign ? round($item->price * $rate) : $item->price;
        $itemTotal = $isForeign ? round($item->total * $rate) : $item->total;
    @endphp
    <tr>
        <td class="border center">{{ $index + 1 }}</td>
        <td class="border left">{{ $item->product_name }}</td>
        <td class="border center">{{ $item->product->unit ?? 'Cái' }}</td>
        <td class="border center">{{ number_format($item->quantity) }}</td>
        <td class="border right">{{ number_format($itemPrice) }}</td>
        <td class="border right">{{ number_format($itemTotal) }}</td>
    </tr>
    @endforeach

    {{-- Cộng tiền hàng --}}
    <tr>
        <td colspan="5" class="border bold">Cộng tiền hàng:</td>
        <td class="border right bold">{{ number_format($subtotalVnd) }}</td>
    </tr>

    @if($sale->discount > 0)
    {{-- Chiết khấu --}}
    <tr>
        <td colspan="5" class="border">Chiết khấu thương mại ({{ $sale->discount }}%):</td>
        <td class="border right">-{{ number_format($discountAmt) }}</td>
    </tr>
    @endif

    {{-- VAT --}}
    <tr>
        <td colspan="5" class="border">Thuế suất GTGT ({{ $sale->vat }}%):</td>
        <td class="border right">{{ number_format($vatAmt) }}</td>
    </tr>

    {{-- Tổng cộng --}}
    <tr>
        <td colspan="5" class="border bold">TỔNG CỘNG THANH TOÁN:</td>
        <td class="border right bold">{{ number_format($totalVnd) }}</td>
    </tr>

    {{-- blank --}}
    <tr><td colspan="6"></td></tr>

    {{-- Số tiền bằng chữ --}}
    <tr>
        <td colspan="6">Số tiền viết bằng chữ: .........................................................................................................................</td>
    </tr>

    {{-- blank --}}
    <tr><td colspan="6"></td></tr>

    {{-- Ngày ký --}}
    <tr>
        <td colspan="6" class="italic center">
            Ngày {{ $sale->date->format('d') }} tháng {{ $sale->date->format('m') }} năm {{ $sale->date->format('Y') }}
        </td>
    </tr>

    {{-- blank --}}
    <tr><td colspan="6"></td></tr>

    {{-- Ký tên --}}
    <tr>
        <td colspan="2" class="bold center">Người mua hàng</td>
        <td colspan="2" class="bold center">Người bán hàng</td>
        <td colspan="2" class="bold center">Giám đốc</td>
    </tr>
    <tr>
        <td colspan="2" class="italic center">(Ký, ghi rõ họ tên)</td>
        <td colspan="2" class="italic center">(Ký, ghi rõ họ tên)</td>
        <td colspan="2" class="italic center">(Ký, đóng dấu, ghi rõ họ tên)</td>
    </tr>
    {{-- Chỗ ký --}}
    <tr>
        <td colspan="2" style="height:50px;"></td>
        <td colspan="2"></td>
        <td colspan="2"></td>
    </tr>
</table>
</body>
</html>
