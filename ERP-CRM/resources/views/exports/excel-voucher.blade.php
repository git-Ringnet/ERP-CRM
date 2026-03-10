<table>
    <tr>
        <td>CÔNG TY CỔ PHẦN THƯƠNG MẠI DỊCH VỤ CÔNG NGHỆ CHÂN TRỜI</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Mẫu số: 02 - VT</td>
        <td></td>
    </tr>
    <tr>
        <td>Địa chỉ: Số 22 đường số 9 KDC Trung Sơn, ấp 49, Xã Bình Hưng</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>(Ban hành theo Thông tư số 133/2016/TT-BTC</td>
        <td></td>
    </tr>
    <tr>
        <td>Thành phố Hồ Chí Minh, Việt Nam.</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Ngày 26/08/2016 của Bộ Tài chính)</td>
        <td></td>
    </tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr>
        <td>PHIẾU XUẤT KHO</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>Ngày {{ $export->date->format('d') }} tháng {{ $export->date->format('m') }} năm {{ $export->date->format('Y') }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Nợ: 632</td>
        <td></td>
    </tr>
    <tr>
        <td>Số: {{ $export->code }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Có: 1561</td>
        <td></td>
    </tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr>
        <td>Họ và tên người nhận hàng: {{ $export->customer->name ?? ($export->project->customer_name ?? '...') }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>- Địa chỉ (bộ phận): ...............................................................................................................................</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>- Lý do xuất kho: {{ $export->note ?? 'Xuất kho cho khách hàng / dự án' }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>- Xuất tại kho: {{ $export->warehouse->name ?? 'Kho Chính HCM' }} &nbsp;&nbsp;&nbsp;&nbsp; Địa điểm: {{ $export->warehouse->address ?? '123 Nguyễn Văn Linh, Quận 7, TP.HCM' }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr>
        <td rowspan="2" style="text-align: center;"><strong>STT</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Tên, nhãn hiệu, quy cách, phẩm chất vật tư, dụng cụ sản phẩm, hàng hóa</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Mã số</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Đơn vị tính</strong></td>
        <td colspan="2" style="text-align: center;"><strong>Số lượng</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Đơn giá</strong></td>
        <td rowspan="2" style="text-align: center;"><strong>Thành tiền</strong></td>
    </tr>
    <tr>
        <td style="text-align: center;"><strong>Yêu cầu</strong></td>
        <td style="text-align: center;"><strong>Thực xuất</strong></td>
    </tr>
    @foreach($export->items as $index => $item)
    <tr>
        <td style="text-align: center;">{{ $index + 1 }}</td>
        <td>{{ $item->product->name }}</td>
        <td style="text-align: center;">{{ $item->product->code }}</td>
        <td style="text-align: center;">{{ $item->product->unit ?? 'Cái' }}</td>
        <td style="text-align: center;">{{ $item->requested_quantity ? number_format($item->requested_quantity, 0, ',', '.') : number_format($item->quantity, 0, ',', '.') }}</td>
        <td style="text-align: center;">{{ number_format($item->quantity, 0, ',', '.') }}</td>
        <td style="text-align: center;">{{ number_format($item->price ?? 0, 0, ',', '.') }}</td>
        <td style="text-align: center;">{{ number_format(($item->price ?? 0) * $item->quantity, 0, ',', '.') }}</td>
    </tr>
    @endforeach
    <tr>
        <td colspan="4" style="text-align: center;"><strong>Cộng</strong></td>
        <td style="text-align: center;"><strong>{{ number_format($export->items->sum(function($i){ return $i->requested_quantity ?: $i->quantity; }), 0, ',', '.') }}</strong></td>
        <td style="text-align: center;"><strong>{{ number_format($export->items->sum('quantity'), 0, ',', '.') }}</strong></td>
        <td style="text-align: center;"><strong>x</strong></td>
        <td style="text-align: center;"><strong>{{ number_format($export->items->sum(function($i){ return ($i->price ?? 0) * $i->quantity; }), 0, ',', '.') }}</strong></td>
    </tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr>
        <td>- Tổng số tiền (Viết bằng chữ): Không đồng</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td>- Số chứng từ gốc kèm theo: ............................................................................................................................</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Ngày {{ $export->date->format('d') }} tháng {{ $export->date->format('m') }} năm {{ $export->date->format('Y') }}</td>
        <td></td>
    </tr>
    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <tr>
        <td colspan="2" style="text-align: center;"><strong>Người lập phiếu</strong></td>
        <td colspan="2" style="text-align: center;"><strong>Người nhận hàng</strong></td>
        <td style="text-align: center;"><strong>Thủ kho</strong></td>
        <td colspan="2" style="text-align: center;"><strong>Kế toán trưởng</strong></td>
        <td style="text-align: center;"><strong>Giám đốc</strong></td>
    </tr>
    <tr>
        <td colspan="2" style="text-align: center;">(Ký, họ tên)</td>
        <td colspan="2" style="text-align: center;">(Ký, họ tên)</td>
        <td style="text-align: center;">(Ký, họ tên)</td>
        <td colspan="2" style="text-align: center;">(Ký, họ tên, đóng dấu)</td>
        <td style="text-align: center;">(Ký, họ tên, đóng dấu)</td>
    </tr>
</table>
