<table>
    <thead>
        <tr>
            <th colspan="13" style="font-size: 14pt; font-weight: bold; text-align: center;">
                Báo cáo Lãi/Lỗ (Margin) theo đơn hàng tháng {{ \Carbon\Carbon::parse($dateFrom)->format('m/Y') }} (Từ ngày {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} đến ngày {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }})
            </th>
        </tr>
        <tr></tr>
        <tr style="background-color: #1e40af; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #000000;">
            <th style="width: 5; border: 1px solid #000000;">STT</th>
            <th style="width: 25; border: 1px solid #000000;">Tên khách hàng</th>
            <th style="width: 20; border: 1px solid #000000;">Số Hóa đơn tài chính (hoặc Số đơn hàng khởi tạo theo phần mềm)</th>
            <th style="width: 15; border: 1px solid #000000;">Ngày xuất hóa đơn</th>
            <th style="width: 15; border: 1px solid #000000;">HÃNG</th>
            <th style="width: 10; border: 1px solid #000000;">License</th>
            <th style="width: 20; border: 1px solid #000000;">Loại hàng</th>
            <th style="width: 15; border: 1px solid #000000;">Mã Hàng hóa chính</th>
            <th style="background-color: #dbeafe; color: #000000; border: 1px solid #000000;">Margin</th>
            <th style="background-color: #dbeafe; color: #000000; border: 1px solid #000000;">Margin %</th>
            <th style="background-color: #dbeafe; color: #000000; border: 1px solid #000000;">NV Kinh doanh</th>
            <th style="background-color: #dcfce7; color: #000000; border: 1px solid #000000;">Tổng tiền khách hàng đã thanh toán</th>
            <th style="background-color: #dcfce7; color: #000000; border: 1px solid #000000;">Tỷ lệ khách hàng đã thanh toán (%)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $index => $row)
            <tr>
                <td align="center" style="border: 1px solid #000000;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ $row['customer_name'] }}</td>
                <td style="border: 1px solid #000000;">{{ $row['sale_code'] }}</td>
                <td align="center" style="border: 1px solid #000000;">{{ $row['sale_date']->format('d/m/Y') }}</td>
                <td style="border: 1px solid #000000;">{{ $row['supplier'] }}</td>
                <td align="center" style="border: 1px solid #000000;">{{ $row['is_license'] ? 'x' : '' }}</td>
                <td style="border: 1px solid #000000;">{{ $row['item_type'] }}</td>
                <td style="border: 1px solid #000000;">{{ $row['product_code'] }}</td>
                <td align="right" style="border: 1px solid #000000;">{{ number_format($row['net_profit'], 0, ',', '.') }}</td>
                <td align="center" style="border: 1px solid #000000;">{{ round($row['margin_percent'], 1) }}%</td>
                <td style="border: 1px solid #000000;">{{ $row['salesperson'] }}</td>
                <td align="right" style="border: 1px solid #000000;">{{ $row['paid_amount'] > 0 ? number_format($row['paid_amount'], 0, ',', '.') : 'Chưa thanh toán' }}</td>
                <td align="center" style="border: 1px solid #000000;">{{ round($row['payment_percent'], 1) }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>
