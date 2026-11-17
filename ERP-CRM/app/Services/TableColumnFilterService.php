<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TableColumnFilterService
{
    /**
     * Map of supported modules to their Eloquent Models and default column mappings
     */
    protected static array $moduleModels = [
        'products' => [
            'model' => \App\Models\Product::class,
            'table' => 'products',
            'columns' => [
                'code' => 'products.code',
                'name' => 'products.name',
                'brand' => 'products.brand',
                'category' => 'products.category',
                'unit' => 'products.unit',
                'description' => 'products.description',
            ],
        ],
        'customers' => [
            'model' => \App\Models\Customer::class,
            'table' => 'customers',
            'columns' => [
                'tax_code' => 'customers.tax_code',
                'name' => 'customers.name',
                'abv_name' => 'customers.abv_name',
                'email' => 'customers.email',
                'phone' => 'customers.phone',
                'type' => 'customers.type',
                'am' => 'customers.am',
                'debt_limit' => 'customers.debt_limit',
            ],
        ],
        'sales' => [
            'model' => \App\Models\Sale::class,
            'table' => 'sales',
            'columns' => [
                'code' => 'sales.code',
                'quotation_code' => 'sales.quotation_code',
                'type' => 'sales.type',
                'status' => 'sales.status',
                'customer_name' => 'customers.name',
                'user_name' => 'users.name',
                'customer' => ['relation' => 'customer', 'field' => 'name'],
                'user' => ['relation' => 'user', 'field' => 'name'],
                'total_amount' => 'sales.total_amount',
                'margin' => 'sales.margin',
                'created_at' => 'sales.created_at',
            ],
        ],
        'quotations' => [
            'model' => \App\Models\Quotation::class,
            'table' => 'quotations',
            'columns' => [
                'code' => 'quotations.code',
                'status' => 'quotations.status',
                'customer_name' => 'customers.name',
                'user_name' => 'users.name',
                'customer' => ['relation' => 'customer', 'field' => 'name'],
                'creator' => ['relation' => 'creator', 'field' => 'name'],
                'total_amount' => 'quotations.total_amount',
                'created_at' => 'quotations.created_at',
            ],
        ],
        'projects' => [
            'model' => \App\Models\Project::class,
            'table' => 'projects',
            'columns' => [
                'code' => 'projects.code',
                'name' => 'projects.name',
                'status' => 'projects.status',
                'customer_name' => 'customers.name',
                'manager_name' => 'users.name',
                'customer' => ['relation' => 'customer', 'field' => 'name'],
                'manager' => ['relation' => 'manager', 'field' => 'name'],
                'brand' => ['relation' => 'vendor', 'field' => 'name'],
                'start_date' => 'projects.start_date',
                'end_date' => 'projects.end_date',
            ],
        ],
        'suppliers' => [
            'model' => \App\Models\Supplier::class,
            'table' => 'suppliers',
            'columns' => [
                'code' => 'suppliers.code',
                'name' => 'suppliers.name',
                'tax_code' => 'suppliers.tax_code',
                'email' => 'suppliers.email',
                'phone' => 'suppliers.phone',
                'address' => 'suppliers.address',
                'payment_term' => 'suppliers.payment_term',
            ],
        ],
        'employees' => [
            'model' => \App\Models\User::class,
            'table' => 'users',
            'columns' => [
                'employee_code' => 'users.employee_code',
                'name' => 'users.name',
                'position' => 'users.position',
                'department' => 'users.department',
                'email' => 'users.email',
                'phone' => 'users.phone',
                'status' => 'users.status',
            ],
        ],
        // Cột liên kết (Mã SO, Partner, ...) và trạng thái do PurchaseOrderColumnFilter xử lý
        'purchase-orders' => [
            'model' => \App\Models\PurchaseOrder::class,
            'table' => 'purchase_orders',
            'columns' => [
                'code' => 'purchase_orders.code',
                'cpq' => 'purchase_orders.cpq_number',
                'order_date' => 'purchase_orders.order_date',
                'expected_delivery' => 'purchase_orders.expected_delivery',
                'manufacturer_release_date' => 'purchase_orders.manufacturer_release_date',
            ],
        ],
        'warehouses' => [
            'model' => \App\Models\Warehouse::class,
            'table' => 'warehouses',
            'columns' => [
                'code' => 'warehouses.code',
                'name' => 'warehouses.name',
                'location' => 'warehouses.location',
            ],
        ],
    ];

    /**
     * Apply column filters, condition filters, and sorting to an Eloquent Query
     *
     * @param Builder $query
     * @param Request $request
     * @param array $columnMap Mapping between column alias/name and DB field or closure
     * @return Builder
     */
    public static function apply($query, Request $request, array $columnMap = []): Builder
    {
        // 1. Apply multi-value checklist filters (col_filter[column] = [val1, val2...])
        $colFilters = $request->input('col_filter');
        if (is_array($colFilters)) {
            foreach ($colFilters as $col => $values) {
                if (empty($values) && $values !== '0') {
                    continue;
                }

                $valuesArray = is_array($values) ? $values : [$values];
                $valuesArray = array_map('trim', $valuesArray);

                if (empty($valuesArray)) {
                    continue;
                }

                self::applyValueFilter($query, $col, $valuesArray, $columnMap);
            }
        }

        // 2. Apply condition-based filters (col_filter_op, col_filter_val, col_filter_val2)
        $colOps = $request->input('col_filter_op');
        $colVals = $request->input('col_filter_val');
        $colVals2 = $request->input('col_filter_val2');

        if (is_array($colOps)) {
            foreach ($colOps as $col => $op) {
                if (empty($op)) {
                    continue;
                }

                $val1 = isset($colVals[$col]) ? trim((string)$colVals[$col]) : '';
                $val2 = isset($colVals2[$col]) ? trim((string)$colVals2[$col]) : '';

                self::applyConditionFilter($query, $col, $op, $val1, $val2, $columnMap);
            }
        }

        // 3. Apply column sorting (col_sort, col_dir)
        $colSort = $request->input('col_sort');
        $colDir = strtolower($request->input('col_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (!empty($colSort)) {
            self::applySorting($query, $colSort, $colDir, $columnMap);
        }

        return $query;
    }

    /**
     * Apply value checklist filter (in / whereIn)
     */
    protected static function applyValueFilter(Builder $query, string $col, array $values, array $columnMap): void
    {
        $target = $columnMap[$col] ?? $col;

        if ($target instanceof \Closure) {
            $target($query, 'in', $values);
            return;
        }

        $dbCol = self::resolveDbColumn($query, $target);
        if (!$dbCol) {
            return;
        }

        $hasEmpty = false;
        $filteredValues = [];

        foreach ($values as $v) {
            if ($v === '(Trống / N/A)' || $v === '(Trống)' || $v === '__EMPTY__' || $v === '') {
                $hasEmpty = true;
            } else {
                $filteredValues[] = $v;
            }
        }

        $query->where(function ($q) use ($dbCol, $filteredValues, $hasEmpty) {
            if (!empty($filteredValues)) {
                $q->whereIn($dbCol, $filteredValues);
            }

            if ($hasEmpty) {
                if (!empty($filteredValues)) {
                    $q->orWhereNull($dbCol)->orWhere($dbCol, '');
                } else {
                    $q->whereNull($dbCol)->orWhere($dbCol, '');
                }
            }
        });
    }

    /**
     * Apply condition filter (contains, equals, gt, lt, between, etc.)
     */
    protected static function applyConditionFilter(Builder $query, string $col, string $op, string $val1, string $val2, array $columnMap): void
    {
        $target = $columnMap[$col] ?? $col;

        if ($target instanceof \Closure) {
            $target($query, $op, $val1, $val2);
            return;
        }

        $dbCol = self::resolveDbColumn($query, $target);
        if (!$dbCol) {
            return;
        }

        switch ($op) {
            case 'contains':
                if ($val1 !== '') $query->where($dbCol, 'like', "%{$val1}%");
                break;
            case 'not_contains':
                if ($val1 !== '') $query->where($dbCol, 'not like', "%{$val1}%");
                break;
            case 'equals':
                if ($val1 !== '') $query->where($dbCol, '=', $val1);
                break;
            case 'starts_with':
                if ($val1 !== '') $query->where($dbCol, 'like', "{$val1}%");
                break;
            case 'ends_with':
                if ($val1 !== '') $query->where($dbCol, 'like', "%{$val1}");
                break;
            case 'is_empty':
                $query->where(fn($q) => $q->whereNull($dbCol)->orWhere($dbCol, ''));
                break;
            case 'is_not_empty':
                $query->whereNotNull($dbCol)->where($dbCol, '!=', '');
                break;
            case 'gt':
                if ($val1 !== '') $query->where($dbCol, '>', self::cleanNumber($val1));
                break;
            case 'gte':
                if ($val1 !== '') $query->where($dbCol, '>=', self::cleanNumber($val1));
                break;
            case 'lt':
                if ($val1 !== '') $query->where($dbCol, '<', self::cleanNumber($val1));
                break;
            case 'lte':
                if ($val1 !== '') $query->where($dbCol, '<=', self::cleanNumber($val1));
                break;
            case 'gt_0':
                $query->where($dbCol, '>', 0);
                break;
            case 'eq_0':
                $query->where(fn($q) => $q->where($dbCol, 0)->orWhereNull($dbCol));
                break;
            case 'between':
                $n1 = self::cleanNumber($val1);
                $n2 = self::cleanNumber($val2);
                if ($val1 !== '' && $val2 !== '') {
                    $query->whereBetween($dbCol, [min($n1, $n2), max($n1, $n2)]);
                }
                break;
            case 'before':
                if ($val1 !== '') $query->whereDate($dbCol, '<=', $val1);
                break;
            case 'after':
                if ($val1 !== '') $query->whereDate($dbCol, '>=', $val1);
                break;
        }
    }

    /**
     * Apply sorting
     */
    protected static function applySorting(Builder $query, string $col, string $dir, array $columnMap): void
    {
        $target = $columnMap[$col] ?? $col;

        if ($target instanceof \Closure) {
            $target($query, 'sort', $dir);
            return;
        }

        $dbCol = self::resolveDbColumn($query, $target);
        if (!$dbCol) {
            return;
        }

        // Reorder if existing orders exist or add order
        $query->reorder($dbCol, $dir);
    }

    /**
     * Resolve valid database column
     */
    protected static function resolveDbColumn(Builder $query, string $col): ?string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_.]/', '', $col);
        if (empty($clean)) {
            return null;
        }

        // Check if relation dot notation (e.g. table.column)
        if (str_contains($clean, '.')) {
            return $clean;
        }

        $table = $query->getModel()->getTable();
        return "{$table}.{$clean}";
    }

    /**
     * Clean numeric input string (removes commas, dots, currency symbols)
     */
    protected static function cleanNumber(string $val): float
    {
        $cleaned = preg_replace('/[^\d.-]/', '', $val);
        return is_numeric($cleaned) ? (float)$cleaned : 0.0;
    }

    /**
     * Bộ lọc / sắp xếp cho cột hiển thị 1 trường của quan hệ BelongsTo (VD: Hãng = vendor.name).
     * Hỗ trợ đủ: chọn giá trị (kể cả "Trống"), chứa / không chứa / bằng / bắt đầu / kết thúc, trống / không trống, sắp xếp.
     */
    public static function relationColumn(string $relation, string $field): \Closure
    {
        return function ($q, $op, $val = null) use ($relation, $field) {
            $filled = fn ($r) => $r->whereNotNull($field)->where($field, '!=', '');

            if ($op === 'sort') {
                $rel = $q->getModel()->{$relation}();
                if ($rel instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                    $q->reorder(
                        $rel->getRelated()->newQuery()->select($field)
                            ->whereColumn($rel->getQualifiedOwnerKeyName(), $rel->getQualifiedForeignKeyName())
                            ->limit(1),
                        $val === 'desc' ? 'desc' : 'asc'
                    );
                }
                return;
            }

            if ($op === 'in') {
                $values = array_values(array_filter((array) $val, fn ($v) => !in_array($v, ['(Trống / N/A)', '(Trống)', '__EMPTY__', ''], true)));
                $hasEmpty = count($values) !== count((array) $val);
                $q->where(function ($sub) use ($relation, $field, $values, $hasEmpty, $filled) {
                    if ($values) {
                        $sub->whereHas($relation, fn ($r) => $r->whereIn($field, $values));
                    }
                    if ($hasEmpty) {
                        $sub->orWhereDoesntHave($relation, $filled);
                    }
                });
                return;
            }

            if ($op === 'is_empty') {
                $q->whereDoesntHave($relation, $filled);
                return;
            }
            if ($op === 'is_not_empty') {
                $q->whereHas($relation, $filled);
                return;
            }

            $raw = trim((string) $val);
            $val = addcslashes($raw, '%_');
            if ($val === '') {
                return;
            }
            match ($op) {
                'not_contains' => $q->whereDoesntHave($relation, fn ($r) => $r->where($field, 'like', "%{$val}%")),
                'equals' => $q->whereHas($relation, fn ($r) => $r->where($field, $raw)),
                'starts_with' => $q->whereHas($relation, fn ($r) => $r->where($field, 'like', "{$val}%")),
                'ends_with' => $q->whereHas($relation, fn ($r) => $r->where($field, 'like', "%{$val}")),
                default => $q->whereHas($relation, fn ($r) => $r->where($field, 'like', "%{$val}%")),
            };
        };
    }

    /**
     * Get distinct values for a module column (for popover values checklist)
     */
    public static function getDistinctValues(string $module, string $column, ?string $search = null, int $limit = 60): array
    {
        if (!isset(self::$moduleModels[$module])) {
            return [];
        }

        $config = self::$moduleModels[$module];
        $modelClass = $config['model'];
        $columnMapping = $config['columns'];

        // BS 13: Phân loại SP theo P&L của đơn hàng bán (đếm số đơn theo từng phân loại)
        if ($module === 'sales' && $column === 'deal_type') {
            return self::getSaleDealTypeValues($search, $limit);
        }
        if ($module === 'sales' && $column === 'eu') {
            return self::getSaleEuValues($search, $limit);
        }
        if ($module === 'sales' && $column === 'brand') {
            return self::getSaleBrandValues($search, $limit);
        }
        // Cột "Trạng thái ĐKDA" hiển thị nhãn của registration_status
        if ($module === 'projects' && $column === 'status') {
            return self::getProjectRegistrationStatusValues($search, $limit);
        }

        if ($module === 'purchase-orders' && ($values = PurchaseOrderColumnFilter::distinctValues($column, $search, $limit)) !== null) {
            return $values;
        }

        $target = $columnMapping[$column] ?? $column;
        if (is_array($target) && isset($target['relation'], $target['field'])) {
            return self::getDistinctRelationValues($modelClass, $target['relation'], $target['field'], $search, $limit);
        }
        if (!is_string($target)) {
            return [];
        }

        $dbCol = str_contains($target, '.') ? $target : "{$config['table']}.{$target}";
        $fieldOnly = str_contains($dbCol, '.') ? explode('.', $dbCol)[1] : $dbCol;

        try {
            // Check if column exists in the database table to prevent SQL errors
            if (!\Illuminate\Support\Facades\Schema::hasColumn($config['table'], $fieldOnly)) {
                return [];
            }

            $query = $modelClass::query()
                ->selectRaw("{$dbCol} as val, COUNT(*) as count")
                ->whereNotNull($dbCol)
                ->where($dbCol, '!=', '');

            // Specific module scopes
            if ($module === 'employees') {
                $query->whereNotNull('employee_code')->where('is_hidden', false);
            }

            if (!empty($search)) {
                $query->where($dbCol, 'like', "%{$search}%");
            }

            $results = $query->groupBy($dbCol)
                ->orderByDesc('count')
                ->limit($limit)
                ->get();

            $list = [];
            foreach ($results as $row) {
                $valStr = (string)$row->val;
                $list[] = [
                    'value' => $valStr,
                    'label' => $valStr,
                    'count' => (int)$row->count,
                ];
            }

            // Check empty count
            if (empty($search)) {
                $emptyQuery = $modelClass::query()
                    ->where(fn($q) => $q->whereNull($dbCol)->orWhere($dbCol, ''));

                if ($module === 'employees') {
                    $emptyQuery->whereNotNull('employee_code')->where('is_hidden', false);
                }

                $emptyCount = $emptyQuery->count();

                if ($emptyCount > 0) {
                    $list[] = [
                        'value' => '(Trống / N/A)',
                        'label' => '(Trống / N/A)',
                        'count' => $emptyCount,
                    ];
                }
            }

            return $list;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("TableColumnFilter distinct values error [{$module}][{$column}]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Nhãn trạng thái ĐKDA -> mã registration_status (dùng chung cho danh sách giá trị và bộ lọc).
     *
     * @return array<string, string> [mã => nhãn]
     */
    public static function projectRegistrationStatusLabels(): array
    {
        $labels = [];
        foreach (['submitted', 'processing', 'vendor_processing', 'vendor_reminded', 'vendor_quoted', 'vendor_rejected', 'update_status',
            'closed_won', 'closed_lost', 'cancelled', 'expired', 'duplicate', 'incomplete', 'on_hold'] as $key) {
            $labels[$key] = (new \App\Models\Project(['registration_status' => $key]))->registration_status_badge['label'];
        }

        return $labels;
    }

    protected static function getProjectRegistrationStatusValues(?string $search, int $limit): array
    {
        $labels = self::projectRegistrationStatusLabels();
        $counts = \App\Models\Project::forUser(auth()->user())
            ->selectRaw('registration_status, COUNT(*) as count')
            ->groupBy('registration_status')
            ->pluck('count', 'registration_status');

        $merged = [];
        foreach ($counts as $key => $count) {
            $label = $labels[$key] ?? 'Mới tạo';
            $merged[$label] = ($merged[$label] ?? 0) + (int) $count;
        }

        return self::toValueList($merged, $search, $limit);
    }

    /**
     * EU của đơn hàng bán: gộp từ đơn (eu_name), Yêu cầu đặt hàng (eu_name_mst) và dự án (eu_name_vi/en).
     */
    protected static function getSaleEuValues(?string $search, int $limit): array
    {
        $saleIds = fn () => \App\Models\Sale::forUser(auth()->user())->select('sales.id');
        $like = $search ? '%' . addcslashes($search, '%_') . '%' : null;
        $merged = [];
        $add = function ($rows) use (&$merged) {
            foreach ($rows as $value => $count) {
                $value = trim((string) $value);
                if ($value !== '' && !\App\Models\Sale::isPlaceholderEu($value)) {
                    $merged[$value] = ($merged[$value] ?? 0) + (int) $count;
                }
            }
        };

        $add(\DB::table('sales')->whereIn('id', $saleIds())
            ->whereNotNull('eu_name')->where('eu_name', '!=', '')
            ->when($like, fn ($q) => $q->where('eu_name', 'like', $like))
            ->selectRaw('eu_name as val, COUNT(*) as count')->groupBy('eu_name')
            ->orderByDesc('count')->limit($limit)->pluck('count', 'val'));

        $add(\DB::table('sale_order_request_items as sori')
            ->join('sale_order_requests as sor', 'sor.id', '=', 'sori.sale_order_request_id')
            ->whereIn('sor.sale_id', $saleIds())
            ->whereNotNull('sori.eu_name_mst')->where('sori.eu_name_mst', '!=', '')
            ->when($like, fn ($q) => $q->where('sori.eu_name_mst', 'like', $like))
            ->selectRaw('sori.eu_name_mst as val, COUNT(DISTINCT sor.sale_id) as count')->groupBy('sori.eu_name_mst')
            ->orderByDesc('count')->limit($limit)->pluck('count', 'val'));

        foreach (['eu_name_vi', 'eu_name_en'] as $field) {
            $add(\DB::table('sales')->join('projects', 'projects.id', '=', 'sales.project_id')
                ->whereIn('sales.id', $saleIds())
                ->whereNotNull("projects.{$field}")->where("projects.{$field}", '!=', '')
                ->when($like, fn ($q) => $q->where("projects.{$field}", 'like', $like))
                ->selectRaw("projects.{$field} as val, COUNT(*) as count")->groupBy("projects.{$field}")
                ->orderByDesc('count')->limit($limit)->pluck('count', 'val'));
        }

        return self::toValueList($merged, null, $limit);
    }

    /**
     * Hãng của đơn hàng bán: hãng của sản phẩm trên đơn + hãng khai trên Yêu cầu đặt hàng.
     */
    protected static function getSaleBrandValues(?string $search, int $limit): array
    {
        $saleIds = fn () => \App\Models\Sale::forUser(auth()->user())->select('sales.id');
        $pairs = \DB::table('sale_items')->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereIn('sale_items.sale_id', $saleIds())
            ->whereNotNull('products.brand')->where('products.brand', '!=', '')
            ->select('sale_items.sale_id', 'products.brand as val')->distinct()->get()
            ->concat(\DB::table('sale_order_request_items as sori')
                ->join('sale_order_requests as sor', 'sor.id', '=', 'sori.sale_order_request_id')
                ->whereIn('sor.sale_id', $saleIds())
                ->whereNotNull('sori.vendor')->where('sori.vendor', '!=', '')
                ->select('sor.sale_id', 'sori.vendor as val')->distinct()->get());

        $merged = $pairs->map(fn ($r) => ['sale_id' => $r->sale_id, 'val' => trim((string) $r->val)])
            ->unique(fn ($r) => $r['sale_id'] . '|' . mb_strtolower($r['val']))
            ->countBy('val')->all();

        return self::toValueList($merged, $search, $limit);
    }

    /**
     * @param  array<string, int>  $counts  [giá trị => số dòng]
     */
    protected static function toValueList(array $counts, ?string $search, int $limit): array
    {
        $list = [];
        foreach ($counts as $value => $count) {
            $value = (string) $value;
            if ($search && !str_contains(mb_strtolower($value), mb_strtolower($search))) {
                continue;
            }
            $list[] = ['value' => $value, 'label' => $value, 'count' => (int) $count];
        }
        usort($list, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($list, 0, $limit);
    }

    /**
     * Phân loại SP (deal_item_type) của đơn hàng bán: giá trị là tên hiển thị, đếm theo số đơn.
     */
    protected static function getSaleDealTypeValues(?string $search, int $limit): array
    {
        $saleQuery = fn () => \App\Models\Sale::forUser();

        $counts = \App\Models\SaleItem::query()
            ->whereIn('sale_id', $saleQuery()->select('sales.id'))
            ->whereNotNull('deal_item_type')
            ->where('deal_item_type', '!=', '')
            ->selectRaw('deal_item_type, COUNT(DISTINCT sale_id) as count')
            ->groupBy('deal_item_type')
            ->pluck('count', 'deal_item_type');

        // Đơn License VNET chưa phân loại dòng nào cũng hiển thị là License VNET
        $vnetOnly = $saleQuery()->where('sales.is_license_vnet', true)
            ->whereDoesntHave('items', fn ($i) => $i->whereNotNull('deal_item_type')->where('deal_item_type', '!=', ''))
            ->count();
        if ($vnetOnly > 0) {
            $counts['license_vnet'] = ($counts['license_vnet'] ?? 0) + $vnetOnly;
        }

        $list = [];
        foreach ($counts as $key => $count) {
            $name = \App\Models\SaleItem::DEAL_ITEM_TYPES[$key]['name'] ?? $key;
            if ($search && !str_contains(mb_strtolower($name), mb_strtolower($search))) {
                continue;
            }
            $list[] = ['value' => $name, 'label' => $name, 'count' => (int) $count];
        }

        if (!$search) {
            $empty = $saleQuery()
                ->whereDoesntHave('items', fn ($i) => $i->whereNotNull('deal_item_type')->where('deal_item_type', '!=', ''))
                ->where(fn ($v) => $v->whereNull('sales.is_license_vnet')->orWhere('sales.is_license_vnet', false))
                ->count();
            if ($empty > 0) {
                $list[] = ['value' => '(Trống / N/A)', 'label' => '(Trống / N/A)', 'count' => $empty];
            }
        }

        usort($list, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($list, 0, $limit);
    }

    /**
     * Distinct values of a BelongsTo relation field (e.g. customer name), counted per parent row
     */
    protected static function getDistinctRelationValues(string $modelClass, string $relationName, string $field, ?string $search, int $limit): array
    {
        try {
            $model = new $modelClass;
            $relation = $model->{$relationName}();
            if (!$relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                return [];
            }

            $relatedTable = $relation->getRelated()->getTable();
            $relatedField = "{$relatedTable}.{$field}";
            if (!Schema::hasColumn($relatedTable, $field)) {
                return [];
            }

            $query = $modelClass::query()
                ->join($relatedTable, $relation->getQualifiedOwnerKeyName(), '=', $relation->getQualifiedForeignKeyName())
                ->selectRaw("{$relatedField} as val, COUNT(*) as count")
                ->whereNotNull($relatedField)
                ->where($relatedField, '!=', '');

            if (!empty($search)) {
                $query->where($relatedField, 'like', "%{$search}%");
            }

            $results = $query->groupBy($relatedField)
                ->orderByDesc('count')
                ->limit($limit)
                ->get();

            $list = [];
            foreach ($results as $row) {
                $valStr = (string)$row->val;
                $list[] = [
                    'value' => $valStr,
                    'label' => $valStr,
                    'count' => (int)$row->count,
                ];
            }

            return $list;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("TableColumnFilter relation values error [{$modelClass}][{$relationName}]: " . $e->getMessage());
            return [];
        }
    }
}
