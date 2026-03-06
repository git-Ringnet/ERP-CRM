<table>
    @foreach($transactions as $transaction)
        @php
            $title = $transaction->type === 'income' ? 'PHIẾU THU' : 'PHIẾU CHI';
            $formCode = $transaction->type === 'income' ? 'Mẫu số: 01 - TT' : 'Mẫu số: 02 - TT';
            $partyLabel = $transaction->type === 'income' ? 'Họ tên người nộp tiền:' : 'Họ tên người nhận tiền:';
            $partyName = $transaction->type === 'income' ? $transaction->payer_name : $transaction->receiver_name;
            $drAcc = $transaction->type === 'income' ? '1111' : ($transaction->category->misa_code ?? '642');
            $crAcc = $transaction->type === 'income' ? ($transaction->category->misa_code ?? '131') : '1111';
            $reasonLabel = $transaction->type === 'income' ? 'Lý do nộp:' : 'Lý do chi:';
        @endphp
        
        <tr>
            <td colspan="4"><strong>CÔNG TY TNHH RINGNET</strong></td>
            <td colspan="2" align="right"><strong>{{ $formCode }}</strong></td>
        </tr>
        <tr>
            <td colspan="4">Địa chỉ: TP. Hồ Chí Minh</td>
            <td colspan="2" align="right">(Thông tư 133/2016/TT-BTC)</td>
        </tr>
        <tr></tr>
        
        <tr>
            <td colspan="6" align="center"><h1><strong>{{ $title }}</strong></h1></td>
        </tr>
        <tr>
            <td colspan="6" align="center"><i>Ngày {{ $transaction->date->format('d') }} tháng {{ $transaction->date->format('m') }} năm {{ $transaction->date->format('Y') }}</i></td>
        </tr>
        <tr>
            <td colspan="4" align="right">Số: {{ $transaction->reference_number ?? ('PT-'.str_pad($transaction->id, 5, '0', STR_PAD_LEFT)) }}</td>
            <td colspan="2" align="left">&nbsp;&nbsp;Nợ: {{ $drAcc }}<br>&nbsp;&nbsp;Có: {{ $crAcc }}</td>
        </tr>
        <tr></tr>

        <tr><td colspan="6">- {{ $partyLabel }} {{ $partyName }}</td></tr>
        <tr><td colspan="6">- Địa chỉ: {{ $transaction->address ?? '' }}</td></tr>
        <tr><td colspan="6">- {{ $reasonLabel }} {{ $transaction->note ?? $transaction->category->name }}</td></tr>
        <tr><td colspan="6">- Số tiền: <strong>{{ number_format($transaction->amount) }} VND</strong></td></tr>
        <tr><td colspan="6">- Viết bằng chữ: ..................................................................................................</td></tr>
        <tr><td colspan="6">- Kèm theo: ................. Chứng từ gốc.</td></tr>
        <tr></tr>

        <tr>
            <td colspan="6" align="right"><i>Ngày {{ date('d') }} tháng {{ date('m') }} năm {{ date('Y') }}</i></td>
        </tr>
        <tr>
            <td align="center"><strong>Giám đốc</strong></td>
            <td align="center"><strong>Kế toán trưởng</strong></td>
            <td align="center"><strong>Thủ quỹ</strong></td>
            <td align="center"><strong>Người nộp/nhận</strong></td>
            <td align="center" colspan="2"><strong>Người lập phiếu</strong></td>
        </tr>
        <tr>
            <td align="center"><i>(Ký, đóng dấu)</i></td>
            <td align="center"><i>(Ký, họ tên)</i></td>
            <td align="center"><i>(Ký, họ tên)</i></td>
            <td align="center"><i>(Ký, họ tên)</i></td>
            <td align="center" colspan="2"><i>(Ký, họ tên)</i></td>
        </tr>
        <tr></tr><tr></tr><tr></tr>
    @endforeach
</table>
