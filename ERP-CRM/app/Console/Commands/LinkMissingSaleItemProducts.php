<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Gắn mã sản phẩm danh mục cho các dòng đơn bán chưa có product_id (Sales gõ tên/mã thay vì chọn từ danh mục).
 * Dòng chưa gắn thì yêu cầu xuất kho không xuất được ("Chưa có mã hàng").
 *
 * Thứ tự:
 *  1. Dòng yêu cầu đặt hàng / dòng PO của chính dòng đơn bán đã có sản phẩm (tạo khi nhập kho từ PO).
 *  2. Mã hàng trong danh mục trùng sau khi bỏ khoảng trắng, -, _, ., / (VD "FC-10-GT50G-950-02- 12"), hoặc trùng tên.
 *  3. Không có thì tạo sản phẩm mới (mã = nội dung đã bỏ khoảng trắng thừa), giống khi lưu đơn.
 * Mặc định chỉ xem trước; --apply để ghi.
 */
class LinkMissingSaleItemProducts extends Command
{
    protected $signature = 'sales:link-missing-products
                            {--sale= : Chỉ xử lý một đơn (mã SO)}
                            {--apply : Ghi thay đổi vào database (mặc định chỉ xem trước)}';

    protected $description = 'Gắn mã sản phẩm danh mục cho dòng đơn bán chưa có mã hàng (để tạo được phiếu xuất kho).';

    public function handle(): int
    {
        $items = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->whereNull('si.product_id')
            ->where('s.status', '!=', 'cancelled')
            ->where(fn ($q) => $q->whereNull('si.is_service')->orWhere('si.is_service', false))
            ->when($this->option('sale'), fn ($q, $code) => $q->where('s.code', $code))
            ->orderBy('s.id')
            ->get(['si.id', 'si.product_name', 's.code']);

        $rows = [];
        $toCreate = [];
        $skipped = [];
        foreach ($items as $item) {
            [$productId, $source] = $this->match($item);
            if ($productId) {
                $code = DB::table('products')->where('id', $productId)->value('code');
                $rows[] = ['item' => $item, 'product_id' => $productId, 'row' => [$item->code, $item->product_name, "#{$productId} {$code}", $source]];
            } elseif (Product::resolveFromText($item->product_name, false) === null && preg_replace('/\s+/u', ' ', trim((string) $item->product_name)) !== '' && !in_array(strtoupper(trim((string) $item->product_name)), ['N/A', 'NA', '-'], true)) {
                $clean = mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $item->product_name)));
                $rows[] = ['item' => $item, 'product_id' => null, 'row' => [$item->code, $item->product_name, "Tạo mới: {$clean}", 'Chưa có trong danh mục']];
                $toCreate[] = $item;
            } else {
                $skipped[] = [$item->code, $item->product_name];
            }
        }

        if ($rows) {
            $this->table(['Mã SO', 'Dòng đơn bán', 'Gắn sản phẩm', 'Căn cứ'], array_column($rows, 'row'));
        }
        $this->info(count($rows) . ' dòng sẽ được gắn mã sản phẩm (trong đó ' . count($toCreate) . ' dòng tạo sản phẩm mới).');

        if ($skipped) {
            $this->warn(count($skipped) . ' dòng bỏ qua (tên trống hoặc "N/A"), Sales cần sửa đơn và chọn sản phẩm:');
            $this->table(['Mã SO', 'Dòng đơn bán'], $skipped);
        }

        if (!$this->option('apply')) {
            $this->line('Đang ở chế độ xem trước, chưa ghi gì. Chạy lại với --apply để ghi.');
            return self::SUCCESS;
        }

        $done = 0;
        foreach ($rows as $r) {
            $productId = $r['product_id'] ?? Product::resolveFromText($r['item']->product_name)?->id;
            if (!$productId) {
                continue;
            }
            // Chỉ ghi nếu dòng vẫn chưa có mã (tránh ghi đè thay đổi mới)
            $done += DB::table('sale_items')->where('id', $r['item']->id)->whereNull('product_id')
                ->update(['product_id' => $productId, 'updated_at' => now()]);
        }
        $this->info("Đã gắn {$done}/" . count($rows) . ' dòng.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    private function match(object $item): array
    {
        $fromOrder = DB::table('sale_order_request_items as r')
            ->leftJoin('purchase_order_items as po', 'po.sale_order_request_item_id', '=', 'r.id')
            ->where('r.sale_item_id', $item->id)
            ->selectRaw('COALESCE(r.product_id, po.product_id) as pid')
            ->pluck('pid')->filter()->unique();
        if ($fromOrder->count() === 1) {
            return [(int) $fromOrder->first(), 'Yêu cầu đặt hàng / PO'];
        }

        $found = Product::resolveFromText($item->product_name, false);

        return $found ? [(int) $found->id, 'Trùng mã (bỏ khoảng trắng, -, _, ., /) hoặc tên'] : [null, ''];
    }
}
