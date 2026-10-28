<?php

namespace App\Services;

use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Lọc theo cột (server) cho danh sách Đặt hàng với hãng (PO).
 * Các cột Mã SO / Partner / End user / Người tạo / Phân loại SP không nằm trên bảng purchase_orders mà lấy từ
 * dòng yêu cầu đặt hàng -> SO của từng dòng PO (và SO gắn thẳng vào PO), giống các accessor linked_* của PurchaseOrder.
 */
class PurchaseOrderColumnFilter
{
    /** Cột lấy qua liên kết: [biểu thức theo dòng PO, biểu thức theo SO gắn thẳng vào PO] */
    private const LINKED = [
        'so_code' => ['s.code', 'ps.code'],
        'partner' => ['COALESCE(s.customer_name, sori.si_name)', 'ps.customer_name'],
        'end_user' => ['COALESCE(sori.eu_name_mst, s.eu_name)', 'ps.eu_name'],
        'salesperson' => ['COALESCE(sc.name, su.name)', 'COALESCE(psu.name, pc.name)'],
        // Giống badge (linked_deal_item_types): dòng chưa phân loại mà đơn tick License VNET -> License VNET
        'deal_type' => ["COALESCE(NULLIF(sitm.deal_item_type, ''), CASE WHEN s.is_license_vnet = 1 THEN 'license_vnet' END)", 'NULL'],
    ];

    private const STATUS_LABELS = [
        'draft' => 'Chờ đặt',
        'pending_approval' => 'Chờ duyệt',
        'approved' => 'Đã đặt',
        'shipping' => 'Đang về',
        'partial_received' => 'Đang về (một phần)',
        'received' => 'Đã về – đủ hàng',
        'cancelled' => 'Đã hủy',
    ];

    private const EMPTY_MARKERS = ['(Trống / N/A)', '(Trống)', '__EMPTY__', ''];

    /** Column map cho TableColumnFilterService::apply */
    public static function columnMap(): array
    {
        $map = [
            'code' => 'purchase_orders.code',
            'cpq' => 'purchase_orders.cpq_number',
            'order_date' => 'purchase_orders.order_date',
            'expected_delivery' => 'purchase_orders.expected_delivery',
            'manufacturer_release_date' => 'purchase_orders.manufacturer_release_date',
            'total' => fn (Builder $q, string $op, ...$args) => self::applyExpression($q, 'COALESCE(purchase_orders.total_foreign, purchase_orders.total / NULLIF(purchase_orders.exchange_rate, 0))', $op, $args),
            'status' => fn (Builder $q, string $op, ...$args) => self::applyStatus($q, $op, $args),
        ];
        foreach (array_keys(self::LINKED) as $col) {
            $map[$col] = fn (Builder $q, string $op, ...$args) => self::applyLinked($q, $col, $op, $args);
        }

        return $map;
    }

    /** Danh sách giá trị cho popover lọc; null = để TableColumnFilterService xử lý cột thường */
    public static function distinctValues(string $column, ?string $search, int $limit): ?array
    {
        if ($column === 'status') {
            $counts = DB::table('purchase_orders')->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
            $list = [];
            foreach ($counts as $status => $count) {
                $label = self::STATUS_LABELS[$status] ?? $status;
                if (!$search || str_contains(mb_strtolower($label), mb_strtolower($search))) {
                    $list[] = ['value' => $label, 'label' => $label, 'count' => (int) $count];
                }
            }
            return $list;
        }

        if (!isset(self::LINKED[$column])) {
            return null;
        }

        $rows = DB::query()->fromSub(self::linkedValues($column), 'v')
            ->whereNotNull('v.val')->where('v.val', '!=', '')
            ->selectRaw('v.val, COUNT(DISTINCT v.po_id) c')
            ->groupBy('v.val')->orderByDesc('c')->limit($column === 'deal_type' ? 100 : $limit)
            ->get();

        $list = [];
        foreach ($rows as $row) {
            $label = $column === 'deal_type' ? (SaleItem::DEAL_ITEM_TYPES[$row->val]['name'] ?? $row->val) : (string) $row->val;
            if ($search && !str_contains(mb_strtolower($label), mb_strtolower($search))) {
                continue;
            }
            $list[] = ['value' => $label, 'label' => $label, 'count' => (int) $row->c];
        }

        return array_slice($list, 0, $limit);
    }

    /** (po_id, val) của một cột liên kết: theo từng dòng PO + theo SO gắn thẳng vào PO */
    private static function linkedValues(string $column): QueryBuilder
    {
        [$itemExpr, $poExpr] = self::LINKED[$column];

        $byItem = DB::table('purchase_order_items as poi')
            ->leftJoin('sale_order_request_items as sori', 'sori.id', '=', 'poi.sale_order_request_item_id')
            ->leftJoin('sale_order_requests as sor', 'sor.id', '=', 'sori.sale_order_request_id')
            ->leftJoin('sales as s', 's.id', '=', 'sor.sale_id')
            ->leftJoin('users as su', 'su.id', '=', 's.user_id')
            ->leftJoin('users as sc', 'sc.id', '=', 'sor.created_by')
            ->leftJoin('sale_items as sitm', 'sitm.id', '=', 'sori.sale_item_id')
            ->selectRaw("poi.purchase_order_id as po_id, {$itemExpr} as val");

        $byPo = DB::table('purchase_orders as po')
            ->leftJoin('sales as ps', 'ps.id', '=', 'po.sale_id')
            ->leftJoin('users as psu', 'psu.id', '=', 'ps.user_id')
            ->leftJoin('users as pc', 'pc.id', '=', 'po.created_by')
            ->selectRaw("po.id as po_id, {$poExpr} as val");

        return $byItem->unionAll($byPo);
    }

    private static function applyLinked(Builder $query, string $column, string $op, array $args): void
    {
        $ids = fn (callable $where) => DB::query()->fromSub(self::linkedValues($column), 'v')->select('v.po_id')->where($where);
        $nonEmpty = fn ($w) => $w->whereNotNull('v.val')->where('v.val', '!=', '');

        if ($op === 'sort') {
            $query->reorder()->orderBy(
                DB::query()->fromSub(self::linkedValues($column), 'v')->selectRaw('MIN(v.val)')->whereColumn('v.po_id', 'purchase_orders.id'),
                $args[0] ?? 'asc'
            );
            return;
        }

        if ($op === 'in') {
            $values = (array) ($args[0] ?? []);
            $hasEmpty = (bool) array_intersect($values, self::EMPTY_MARKERS);
            $values = array_values(array_diff($values, self::EMPTY_MARKERS));
            if ($column === 'deal_type') {
                // Popover hiển thị tên phân loại, DB lưu mã
                $values = array_map(fn ($v) => collect(SaleItem::DEAL_ITEM_TYPES)->search(fn ($t) => $t['name'] === $v) ?: $v, $values);
            }
            $query->where(function ($q) use ($ids, $nonEmpty, $values, $hasEmpty) {
                if ($values) {
                    $q->whereIn('purchase_orders.id', $ids(fn ($w) => $w->whereIn('v.val', $values)));
                }
                if ($hasEmpty) {
                    $q->orWhereNotIn('purchase_orders.id', $ids($nonEmpty));
                }
            });
            return;
        }

        $val = (string) ($args[0] ?? '');
        match ($op) {
            'contains' => $val !== '' && $query->whereIn('purchase_orders.id', $ids(fn ($w) => $w->where('v.val', 'like', "%{$val}%"))),
            'not_contains' => $val !== '' && $query->whereNotIn('purchase_orders.id', $ids(fn ($w) => $w->where('v.val', 'like', "%{$val}%"))),
            'equals' => $val !== '' && $query->whereIn('purchase_orders.id', $ids(fn ($w) => $w->where('v.val', $val))),
            'starts_with' => $val !== '' && $query->whereIn('purchase_orders.id', $ids(fn ($w) => $w->where('v.val', 'like', "{$val}%"))),
            'ends_with' => $val !== '' && $query->whereIn('purchase_orders.id', $ids(fn ($w) => $w->where('v.val', 'like', "%{$val}"))),
            'is_empty' => $query->whereNotIn('purchase_orders.id', $ids($nonEmpty)),
            'is_not_empty' => $query->whereIn('purchase_orders.id', $ids($nonEmpty)),
            default => null,
        };
    }

    private static function applyStatus(Builder $query, string $op, array $args): void
    {
        if ($op === 'sort') {
            $query->reorder('purchase_orders.status', $args[0] ?? 'asc');
            return;
        }
        // Popover hiển thị nhãn trạng thái, DB lưu mã
        $toCodes = fn (array $labels) => array_keys(array_filter(self::STATUS_LABELS, fn ($l, $code) => in_array($l, $labels, true) || in_array($code, $labels, true), ARRAY_FILTER_USE_BOTH));
        $val = (string) ($args[0] ?? '');
        match ($op) {
            'in' => $query->whereIn('purchase_orders.status', $toCodes((array) ($args[0] ?? []))),
            'equals' => $val !== '' && $query->whereIn('purchase_orders.status', $toCodes([$val])),
            'contains' => $val !== '' && $query->whereIn('purchase_orders.status', array_keys(array_filter(self::STATUS_LABELS, fn ($l) => str_contains(mb_strtolower($l), mb_strtolower($val))))),
            default => null,
        };
    }

    private static function applyExpression(Builder $query, string $expr, string $op, array $args): void
    {
        $num = fn ($v) => (float) preg_replace('/[^\d.-]/', '', (string) $v);
        [$v1, $v2] = [$args[0] ?? '', $args[1] ?? ''];
        match ($op) {
            'sort' => $query->reorder()->orderByRaw("{$expr} " . (($args[0] ?? 'asc') === 'desc' ? 'desc' : 'asc')),
            'gt' => $query->whereRaw("{$expr} > ?", [$num($v1)]),
            'gte' => $query->whereRaw("{$expr} >= ?", [$num($v1)]),
            'lt' => $query->whereRaw("{$expr} < ?", [$num($v1)]),
            'lte' => $query->whereRaw("{$expr} <= ?", [$num($v1)]),
            'equals' => $query->whereRaw("{$expr} = ?", [$num($v1)]),
            'between' => $query->whereRaw("{$expr} BETWEEN ? AND ?", [min($num($v1), $num($v2)), max($num($v1), $num($v2))]),
            'gt_0' => $query->whereRaw("{$expr} > 0"),
            'eq_0' => $query->whereRaw("COALESCE({$expr}, 0) = 0"),
            default => null,
        };
    }
}
