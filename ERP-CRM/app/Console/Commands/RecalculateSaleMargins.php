<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;

/**
 * Tính lại margin đã lưu (sales.margin / margin_percent / cost) theo công thức
 * thống nhất với bảng P&L. Mặc định chỉ xem trước, thêm --apply để ghi.
 */
class RecalculateSaleMargins extends Command
{
    protected $signature = 'sales:recalculate-margin
                            {--apply : Ghi giá trị mới vào database}
                            {--code=* : Chỉ xử lý các mã đơn này}';

    protected $description = 'Tính lại margin đơn hàng theo công thức P&L (bỏ qua đơn có dòng hàng mất giá vốn)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $codes = (array) $this->option('code');

        $changed = 0;
        $skipped = [];

        Sale::with(['items', 'expenses'])
            ->whereHas('items')
            ->when($codes, fn ($q) => $q->whereIn('code', $codes))
            ->orderBy('id')
            ->each(function (Sale $sale) use ($apply, &$changed, &$skipped) {
                // Dòng hàng (không phải dịch vụ) có giá bán nhưng giá vốn = 0: dữ liệu có thể đã
                // bị ghi đè sau khi duyệt -> không tính lại để tránh làm sai margin đã duyệt.
                $lostCost = $sale->items->contains(fn ($i) => !$i->is_service && (float) $i->total > 0 && (float) $i->cost_total <= 0);

                $oldMargin = (float) $sale->margin;
                $calc = clone $sale;
                $calc->calculateMargin();
                $newMargin = (float) $calc->margin;

                if (abs($newMargin - $oldMargin) < 1) {
                    return;
                }

                if ($lostCost) {
                    $skipped[] = $sale->code;
                    return;
                }

                $changed++;
                $this->line(sprintf(
                    '%s: %s (%.1f%%) -> %s (%.1f%%)',
                    $sale->code,
                    number_format($oldMargin),
                    (float) $sale->margin_percent,
                    number_format($newMargin),
                    (float) $calc->margin_percent
                ));

                if ($apply) {
                    Sale::whereKey($sale->id)->update([
                        'margin' => $calc->margin,
                        'margin_percent' => $calc->margin_percent,
                        'cost' => $calc->cost,
                    ]);
                }
            });

        if ($skipped) {
            $this->warn('Bỏ qua (dòng hàng mất giá vốn, cần kiểm tra tay): ' . implode(', ', $skipped));
        }

        $this->info(($apply ? 'Đã cập nhật ' : 'Sẽ cập nhật ') . $changed . ' đơn.' . ($apply ? '' : ' Chạy lại với --apply để ghi.'));

        return Command::SUCCESS;
    }
}
