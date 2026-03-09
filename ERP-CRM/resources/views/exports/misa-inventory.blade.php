<table>
    @foreach($vouchers as $voucher)
        @php
            $parent = $voucher['parent'];
            $items = $voucher['items'];
            $isImport = $voucher['type'] === 'import';
            $title = $isImport ? 'PHIẾU NHẬP KHO' : 'PHIẾU XUẤT KHO';
            $formCode = $isImport ? 'Mẫu số: 01 - VT' : 'Mẫu số: 02 - VT';
            $partyLabel = $isImport ? 'Họ và tên người giao hàng:' : 'Họ và tên người nhận hàng:';
            $partyName = $isImport ? ($parent->supplier->name ?? '') : ($parent->customer->name ?? $parent->project->name ?? '');

            // Lý do nhập/xuất
            $reason = $parent->note;
            if (empty($reason)) {
                if ($isImport) {
                    $reason = $parent->supplier ? 'Nhập hàng từ ' . $parent->supplier->name : '';
                } else {
                    if ($parent->project) {
                        $reason = 'Xuất kho cho dự án ' . $parent->project->name;
                    } elseif ($parent->customer) {
                        $reason = 'Xuất kho bán hàng ' . $parent->customer->name;
                    }
                }
            }
            
            // Tài khoản kế toán
            $drAcc = $isImport ? '1561' : '632'; // 1561 theo mẫu ảnh
            $crAcc = $isImport ? '331' : '1561';
            
            $qtyHeader1 = $isImport ? 'Theo chứng từ' : 'Yêu cầu';
            $qtyHeader2 = $isImport ? 'Thực nhập' : 'Thực xuất';
        @endphp
        
        <!-- Header Section -->
        <tr>
            <td colspan="5"><strong>CÔNG TY CỔ PHẦN THƯƠNG MẠI DỊCH VỤ CÔNG NGHỆ CHÂN TRỜI</strong></td>
            <td colspan="4" align="right"><strong>{{ $formCode }}</strong></td>
        </tr>
        <tr>
            <td colspan="5">Địa chỉ: Số 22 đường số 9 KDC Trung Sơn, ấp 49, Xã Bình Hưng,</td>
            <td colspan="4" align="right"><font size="2">(Ban hành theo Thông tư số 133/2016/TT-BTC</font></td>
        </tr>
        <tr>
            <td colspan="5">Thành phố Hồ CHí Minh, Việt Nam.</td>
            <td colspan="4" align="right"><font size="2">Ngày 26/08/2016 của Bộ Tài chính)</font></td>
        </tr>
        <tr></tr><tr></tr>
        <tr>
            <td colspan="9" align="center" style="font-size: 18pt; font-weight: bold;">
                {{ $title }}
            </td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td colspan="5" align="center"><i>Ngày {{ $parent->date->format('d') }} tháng {{ $parent->date->format('m') }} năm {{ $parent->date->format('Y') }}</i></td>
            <td colspan="2" align="left">Nợ: {{ $drAcc }}</td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td colspan="5" align="center">Số: {{ $parent->code }}</td>
            <td colspan="2" align="left">Có: {{ $crAcc }}</td>
        </tr>
        <tr></tr>

        <tr>
            <td colspan="9">- {{ $partyLabel }} {{ $partyName }}</td>
        </tr>
        @if(!$isImport)
        <tr>
            <td colspan="9">- Địa chỉ (bộ phận): .................................................................................................</td>
        </tr>
        <tr>
            <td colspan="9">- Lý do {{ $isImport ? 'nhập' : 'xuất' }} kho: {{ $reason }}</td>
        </tr>
        @else
        <tr>
            <td colspan="9">- Theo hóa đơn số ................... ngày ..... tháng ..... năm ......... của ...........................................</td>
        </tr>
        @endif
        <tr>
            <td colspan="5">- {{ $isImport ? 'Nhập' : 'Xuất' }} tại kho: {{ $parent->warehouse->name ?? '' }}</td>
            <td colspan="4">Địa điểm: {{ $parent->warehouse->address ?? '' }}</td>
        </tr>
        <tr></tr>

        <!-- Table Header -->
        <thead>
            <tr>
                <th rowspan="2" style="border: 1px solid #000;"><strong>STT</strong></th>
                <th rowspan="2" style="border: 1px solid #000;" colspan="2"><strong>Tên, nhãn hiệu, quy cách, phẩm chất vật tư, dụng cụ sản phẩm, hàng hóa</strong></th>
                <th rowspan="2" style="border: 1px solid #000;"><strong>Mã số</strong></th>
                <th rowspan="2" style="border: 1px solid #000;"><strong>Đơn vị tính</strong></th>
                <th colspan="2" style="border: 1px solid #000;"><strong>Số lượng</strong></th>
                <th rowspan="2" style="border: 1px solid #000;"><strong>Đơn giá</strong></th>
                <th rowspan="2" style="border: 1px solid #000;"><strong>Thành tiền</strong></th>
            </tr>
            <tr>
                <th style="border: 1px solid #000;"><strong>{{ $qtyHeader1 }}</strong></th>
                <th style="border: 1px solid #000;"><strong>{{ $qtyHeader2 }}</strong></th>
            </tr>
            <tr>
                <th align="center" style="border: 1px solid #000;">A</th>
                <th align="center" style="border: 1px solid #000;" colspan="2">B</th>
                <th align="center" style="border: 1px solid #000;">C</th>
                <th align="center" style="border: 1px solid #000;">D</th>
                <th align="center" style="border: 1px solid #000;">1</th>
                <th align="center" style="border: 1px solid #000;">2</th>
                <th align="center" style="border: 1px solid #000;">3</th>
                <th align="center" style="border: 1px solid #000;">4</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                @php
                    $price = $isImport ? ($item->cost ?? 0) : ($item->price ?? 0);
                    $total = $price * $item->quantity;
                @endphp
                <tr>
                    <td style="border: 1px solid #000;" align="center">{{ $index + 1 }}</td>
                    <td style="border: 1px solid #000;" colspan="2">{{ $item->product->name }}</td>
                    <td style="border: 1px solid #000;" align="center">{{ $item->product->code }}</td>
                    <td style="border: 1px solid #000;" align="center">{{ $item->product->unit ?? 'Cái' }}</td>
                    <td style="border: 1px solid #000;" align="right">{{ number_format($item->quantity) }}</td>
                    <td style="border: 1px solid #000;" align="right">{{ number_format($item->quantity) }}</td>
                    <td style="border: 1px solid #000;" align="right">{{ number_format($price) }}</td>
                    <td style="border: 1px solid #000;" align="right">{{ number_format($total) }}</td>
                </tr>
            @endforeach
            <tr>
                <td style="border: 1px solid #000;" colspan="5" align="center"><strong>Cộng</strong></td>
                <td style="border: 1px solid #000;" align="right"><strong>{{ number_format($items->sum('quantity')) }}</strong></td>
                <td style="border: 1px solid #000;" align="right"><strong>{{ number_format($items->sum('quantity')) }}</strong></td>
                <td style="border: 1px solid #000;"></td>
                <td style="border: 1px solid #000;" align="right"><strong>{{ number_format($items->sum(function($i) use ($isImport){ return ($isImport ? $i->cost : $i->price) * $i->quantity; })) }}</strong></td>
            </tr>
        </tbody>
        <tr></tr>
        
        <!-- Footer Info -->
        @php
            $grandTotal = $items->sum(function($i) use ($isImport){ 
                return ($isImport ? $i->cost : $i->price) * $i->quantity; 
            });
            $totalInWords = \App\Helpers\NumberHelper::currencyToVietnameseWords($grandTotal);
        @endphp
        <tr>
            <td colspan="9">- Tổng số tiền (Viết bằng chữ): {{ $totalInWords }}</td>
        </tr>
        <tr>
            <td colspan="9">- Số chứng từ gốc kèm theo: .................................................................................................</td>
        </tr>
        <tr></tr>

        <!-- Footer Signatures -->
        <tr>
            <td colspan="9" align="right"><i>Ngày ..... tháng ..... năm .........</i></td>
        </tr>
        @php
            $accLabel = $isImport ? 'nhập' : 'nhập'; // Nhãn trong ngoặc cho Kế toán trưởng
            $partySignLabel = $isImport ? 'Người giao hàng' : 'Người nhận hàng';
        @endphp
        @if($isImport)
            <tr>
                <td colspan="2" align="center"><strong>Người lập phiếu</strong></td>
                <td colspan="2" align="center"><strong>{{ $partySignLabel }}</strong></td>
                <td colspan="2" align="center"><strong>Thủ kho</strong></td>
                <td colspan="3" align="center"><strong>Kế toán trưởng</strong><br><strong>(Hoặc bộ phận có nhu cầu {{ $accLabel }})</strong></td>
            </tr>
            <tr>
                <td colspan="2" align="center"><i>(Ký, họ tên)</i></td>
                <td colspan="2" align="center"><i>(Ký, họ tên)</i></td>
                <td colspan="2" align="center"><i>(Ký, họ tên)</i></td>
                <td colspan="3" align="center"><i>(Ký, họ tên)</i></td>
            </tr>
        @else
            <tr>
                <td colspan="2" align="center"><strong>Người lập phiếu</strong></td>
                <td colspan="2" align="center"><strong>Người nhận hàng</strong></td>
                <td align="center"><strong>Thủ kho</strong></td>
                <td colspan="2" align="center"><strong>Kế toán trưởng</strong><br><strong>(Hoặc bộ phận có nhu cầu {{ $accLabel }})</strong></td>
                <td colspan="2" align="center"><strong>Giám đốc</strong></td>
            </tr>
            <tr>
                <td colspan="2" align="center"><i>(Ký, họ tên)</i></td>
                <td colspan="2" align="center"><i>(Ký, họ tên)</i></td>
                <td align="center"><i>(Ký, họ tên)</i></td>
                <td colspan="2" align="center"><i>(Ký, họ tên)</i></td>
                <td colspan="2" align="center"><i>(Ký, họ tên, đóng dấu)</i></td>
            </tr>
        @endif
        <tr></tr><tr></tr><tr></tr>
        <tr></tr><tr></tr><tr></tr>
    @endforeach
</table>
