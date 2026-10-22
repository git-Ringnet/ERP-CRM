<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;

/**
 * BS 06 - Rà soát các SO trước đây bị ghi nhận công nợ sớm.
 *
 * Quy tắc cũ: đơn vào công nợ ngay khi duyệt P&L (status approved/shipping/completed).
 * Quy tắc mới: phải đủ điều kiện VÀ đã XHĐ (Sale::scopeReceivable). Lệnh này liệt kê các đơn
 * đủ điều kiện nhưng chưa XHĐ - tức đã bị tính vào công nợ theo quy tắc cũ - để kế toán đối chiếu.
 * Chỉ đọc dữ liệu, không thay đổi gì.
 */
class ReviewEarlyDebtRecognition extends Command
{
    protected $signature = 'debts:review-early-recognition
                            {--csv= : Ghi danh sách ra file CSV (đường dẫn trong storage/app)}';

    protected $description = 'Liệt kê các SO đã duyệt nhưng chưa XHĐ (trước đây bị tính vào công nợ sớm).';

    public function handle(): int
    {
        $sales = Sale::with(['customer:id,name', 'user:id,name'])
            ->whereIn('sales.status', Sale::RECEIVABLE_STATUSES)
            ->whereNot(fn ($q) => $q->invoiced())
            ->orderBy('date')
            ->get(['id', 'code', 'customer_id', 'customer_name', 'user_id', 'status', 'pl_status', 'pl_approved_at', 'date', 'total', 'paid_amount', 'debt_amount']);

        $rows = $sales->map(fn (Sale $sale) => [
            $sale->code,
            $sale->customer_name ?: ($sale->customer->name ?? ''),
            $sale->user->name ?? '',
            $sale->status,
            optional($sale->pl_approved_at)->format('d/m/Y') ?? '',
            $sale->date?->format('d/m/Y') ?? '',
            (float) $sale->total,
            (float) $sale->paid_amount,
            (float) $sale->debt_amount,
            (float) $sale->paid_amount > 0 ? 'Ứng trước' : 'Bỏ khỏi công nợ',
        ]);

        $headers = ['Mã SO', 'Khách hàng', 'Nhân viên', 'Trạng thái', 'Duyệt P&L', 'Ngày đơn', 'Tổng tiền', 'Đã TT', 'Công nợ cũ', 'Xử lý mới'];

        $this->table($headers, $rows->map(fn ($r) => [
            $r[0], mb_strimwidth($r[1], 0, 40, '...'), $r[2], $r[3], $r[4], $r[5],
            number_format($r[6]), number_format($r[7]), number_format($r[8]), $r[9],
        ])->all());

        $this->info(sprintf(
            '%d SO đã duyệt nhưng chưa XHĐ. Công nợ cũ bị ghi nhận sớm: %s đ; trong đó khách đã ứng trước: %s đ.',
            $sales->count(),
            number_format($sales->sum('debt_amount')),
            number_format($sales->sum('paid_amount'))
        ));

        if ($csv = $this->option('csv')) {
            $path = storage_path('app/' . ltrim($csv, '/'));
            @mkdir(dirname($path), 0775, true);
            $fh = fopen($path, 'w');
            fwrite($fh, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM cho Excel
            fputcsv($fh, $headers);
            foreach ($rows as $row) {
                fputcsv($fh, $row);
            }
            fclose($fh);
            $this->info("Đã ghi file: {$path}");
        }

        return self::SUCCESS;
    }
}
