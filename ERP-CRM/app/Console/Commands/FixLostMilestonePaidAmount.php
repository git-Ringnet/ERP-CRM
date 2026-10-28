<?php

namespace App\Console\Commands;

use App\Models\PaymentHistory;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sửa các đợt thanh toán bị mất "đã thu" sau khi lộ trình được tạo lại (syncPaymentSchedules cũ không giữ paid_amount).
 *
 * Triệu chứng: đợt đã thu (status = paid, Finance xác nhận) có paid_amount = 0, nên updateDebt() phân bổ
 * lại số tiền đó sang đợt sau dưới dạng "System Auto" -> đợt sau hiện "Đã thu 1 phần" sai.
 *
 * Cách sửa (chỉ đụng tới bảng sale_payment_schedules, KHÔNG đổi paid_amount/công nợ của đơn):
 *  1. Đợt paid do người xác nhận mà paid_amount = 0 -> paid_amount = số tiền đợt.
 *  2. Phân bổ lại phần còn lại (Đã TT của đơn - tổng các đợt người xác nhận) vào các đợt "System Auto" theo thứ tự.
 * Đơn nào sau khi sửa mà tổng đã thu các đợt không khớp Đã TT của đơn thì bỏ qua, liệt kê để kiểm tra tay.
 *
 * Mặc định chỉ xem trước; thêm --apply để ghi. Trước khi ghi lưu bản sao dữ liệu cũ vào storage/app/backups.
 */
class FixLostMilestonePaidAmount extends Command
{
    protected $signature = 'payments:fix-lost-milestone-paid
                            {--sale= : Chỉ xử lý một đơn (mã SO)}
                            {--apply : Ghi thay đổi vào database (mặc định chỉ xem trước)}';

    protected $description = 'Sửa đợt thanh toán đã thu bị mất số tiền đã thu, khiến tiền bị phân bổ trùng sang đợt sau.';

    private const AUTO = 'System Auto';
    private const TOLERANCE = 1.0; // đ

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $saleIds = DB::table('sale_payment_schedules')
            ->where('status', 'paid')
            ->whereNotNull('confirmed_by')
            ->whereNotIn('confirmed_by', ['', self::AUTO])
            ->whereRaw('COALESCE(paid_amount, 0) <= 0')
            ->where('amount', '>', 0)
            ->distinct()
            ->pluck('sale_id');

        $sales = Sale::whereIn('id', $saleIds)
            ->when($this->option('sale'), fn ($q, $code) => $q->where('code', $code))
            ->orderBy('id')
            ->get(['id', 'code', 'total', 'paid_amount']);

        if ($sales->isEmpty()) {
            $this->info('Không có đơn nào bị lỗi.');
            return self::SUCCESS;
        }

        $plans = [];
        $skipped = [];
        foreach ($sales as $sale) {
            $result = $this->plan($sale);
            if (isset($result['error'])) {
                $skipped[] = [$sale->code, number_format((float) $sale->paid_amount), $result['error']];
            } elseif (!empty($result['changes'])) {
                $plans[] = ['sale' => $sale, 'changes' => $result['changes'], 'original' => $result['original']];
            }
        }

        $rows = [];
        foreach ($plans as $plan) {
            foreach ($plan['changes'] as $c) {
                $rows[] = [
                    $plan['sale']->code,
                    number_format((float) $plan['sale']->paid_amount),
                    $c['milestone_name'],
                    number_format($c['amount']),
                    $c['old_status'] . ' → ' . $c['new_status'],
                    number_format($c['old_paid']) . ' → ' . number_format($c['new_paid']),
                ];
            }
        }

        if ($rows) {
            $this->table(['Mã SO', 'Đã TT của đơn', 'Đợt', 'Số tiền đợt', 'Trạng thái', 'Đã thu của đợt'], $rows);
        }
        $this->info(count($plans) . ' đơn sẽ được sửa.');

        if ($skipped) {
            $this->warn(count($skipped) . ' đơn KHÔNG tự sửa, cần kiểm tra tay:');
            $this->table(['Mã SO', 'Đã TT của đơn', 'Lý do'], $skipped);
        }

        if (!$apply) {
            $this->line('Đang ở chế độ xem trước, chưa ghi gì. Chạy lại với --apply để ghi.');
            return self::SUCCESS;
        }

        if (empty($plans)) {
            return self::SUCCESS;
        }

        $backupPath = storage_path('app/backups/fix-lost-milestone-paid-' . now()->format('Ymd_His') . '.json');
        @mkdir(dirname($backupPath), 0775, true);
        file_put_contents($backupPath, json_encode(
            collect($plans)->map(fn ($p) => ['sale_code' => $p['sale']->code, 'schedules' => $p['original']])->all(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ));
        $this->info("Đã lưu bản sao dữ liệu cũ: {$backupPath}");

        $done = 0;
        foreach ($plans as $plan) {
            try {
                DB::transaction(function () use ($plan) {
                    // Khóa và kiểm tra dữ liệu không đổi kể từ lúc xem trước
                    $current = DB::table('sale_payment_schedules')
                        ->where('sale_id', $plan['sale']->id)
                        ->orderBy('sort_order')
                        ->lockForUpdate()
                        ->get(['id', 'status', 'paid_amount', 'confirmed_by'])
                        ->keyBy('id');
                    $salePaid = (float) DB::table('sales')->where('id', $plan['sale']->id)->lockForUpdate()->value('paid_amount');

                    if (abs($salePaid - (float) $plan['sale']->paid_amount) > self::TOLERANCE) {
                        throw new \RuntimeException('Đã TT của đơn vừa thay đổi');
                    }
                    foreach ($plan['original'] as $orig) {
                        $now = $current[$orig['id']] ?? null;
                        if (!$now || $now->status !== $orig['status'] || (float) $now->paid_amount !== (float) $orig['paid_amount'] || $now->confirmed_by !== $orig['confirmed_by']) {
                            throw new \RuntimeException('Đợt thanh toán vừa thay đổi');
                        }
                    }

                    foreach ($plan['changes'] as $c) {
                        DB::table('sale_payment_schedules')->where('id', $c['id'])->update($c['update'] + ['updated_at' => now()]);
                    }
                });
                $done++;
            } catch (\Throwable $e) {
                $this->error("{$plan['sale']->code}: bỏ qua - {$e->getMessage()}. Chạy lại lệnh để xem trước lại.");
            }
        }

        $this->info("Đã sửa {$done}/" . count($plans) . ' đơn.');

        return self::SUCCESS;
    }

    /**
     * Tính thay đổi cho một đơn, không ghi gì.
     */
    private function plan(Sale $sale): array
    {
        $schedules = DB::table('sale_payment_schedules')
            ->where('sale_id', $sale->id)
            ->orderBy('sort_order')
            ->get(['id', 'milestone_name', 'amount', 'paid_amount', 'status', 'confirmed_by', 'confirmed_at']);

        $salePaid = (float) $sale->paid_amount;
        $isManual = fn ($s) => in_array($s->status, ['paid', 'partially_paid'], true)
            && !in_array($s->confirmed_by, [self::AUTO, null, ''], true);

        $new = [];
        foreach ($schedules as $s) {
            $new[$s->id] = ['status' => $s->status, 'paid_amount' => (float) $s->paid_amount, 'confirmed_by' => $s->confirmed_by, 'confirmed_at' => $s->confirmed_at];
        }

        // Bước 1: đợt người xác nhận đã thu đủ nhưng mất paid_amount
        $manualSum = 0.0;
        foreach ($schedules as $s) {
            if (!$isManual($s)) {
                continue;
            }
            if ($s->status === 'paid' && (float) $s->paid_amount <= 0 && (float) $s->amount > 0) {
                $new[$s->id]['paid_amount'] = (float) $s->amount;
            }
            $manualSum += $new[$s->id]['paid_amount'];
        }

        if ($manualSum - $salePaid > self::TOLERANCE) {
            return ['error' => 'Tổng các đợt đã xác nhận (' . number_format($manualSum) . ') lớn hơn Đã TT của đơn'];
        }

        // Bước 2: phân bổ lại phần còn lại vào các đợt System Auto
        $leftover = $salePaid - $manualSum;
        foreach ($schedules as $s) {
            if ($s->confirmed_by !== self::AUTO) {
                continue;
            }
            $amount = (float) $s->amount;
            $alloc = max(0.0, min($leftover, $amount));
            if ($amount > 0 && $alloc >= $amount - self::TOLERANCE) {
                $new[$s->id] = ['status' => 'paid', 'paid_amount' => $amount, 'confirmed_by' => self::AUTO, 'confirmed_at' => $s->confirmed_at ?? now()];
                $leftover -= $amount;
            } elseif ($alloc > 0) {
                $new[$s->id] = ['status' => 'partially_paid', 'paid_amount' => $alloc, 'confirmed_by' => self::AUTO, 'confirmed_at' => $s->confirmed_at ?? now()];
                $leftover -= $alloc;
            } else {
                $new[$s->id] = ['status' => 'pending', 'paid_amount' => 0.0, 'confirmed_by' => null, 'confirmed_at' => null];
            }
        }

        // Kiểm tra cuối: tổng đã thu các đợt phải khớp Đã TT của đơn
        $newSum = array_sum(array_column($new, 'paid_amount'));
        if (abs($newSum - $salePaid) > self::TOLERANCE) {
            return ['error' => 'Sau khi sửa, tổng đã thu các đợt (' . number_format($newSum) . ') không khớp Đã TT của đơn'];
        }

        $historiesSum = (float) PaymentHistory::where('sale_id', $sale->id)->sum('amount');
        if ($historiesSum > 0 && abs($historiesSum - $salePaid) > self::TOLERANCE) {
            return ['error' => 'Đã TT của đơn khác tổng lịch sử thanh toán (' . number_format($historiesSum) . ')'];
        }

        $changes = [];
        $original = [];
        foreach ($schedules as $s) {
            $n = $new[$s->id];
            $original[] = ['id' => $s->id, 'milestone_name' => $s->milestone_name, 'amount' => (float) $s->amount, 'status' => $s->status, 'paid_amount' => (float) $s->paid_amount, 'confirmed_by' => $s->confirmed_by, 'confirmed_at' => $s->confirmed_at];

            if ($n['status'] === $s->status && abs($n['paid_amount'] - (float) $s->paid_amount) < 0.01 && $n['confirmed_by'] === $s->confirmed_by) {
                continue;
            }
            $changes[] = [
                'id' => $s->id,
                'milestone_name' => $s->milestone_name,
                'amount' => (float) $s->amount,
                'old_status' => $s->status,
                'new_status' => $n['status'],
                'old_paid' => (float) $s->paid_amount,
                'new_paid' => $n['paid_amount'],
                'update' => [
                    'status' => $n['status'],
                    'paid_amount' => round($n['paid_amount'], 2),
                    'confirmed_by' => $n['confirmed_by'],
                    'confirmed_at' => $n['confirmed_at'],
                ],
            ];
        }

        return ['changes' => $changes, 'original' => $original];
    }
}
