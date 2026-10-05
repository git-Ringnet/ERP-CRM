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

        $target = $columnMapping[$column] ?? $column;
        if (!is_string($target)) {
            return [];
        }

        $dbCol = str_contains($target, '.') ? $target : "{$config['table']}.{$target}";
        $fieldOnly = str_contains($dbCol, '.') ? explode('.', $dbCol)[1] : $dbCol;

        $query = $modelClass::query()
            ->selectRaw("{$dbCol} as val, COUNT(*) as count")
            ->whereNotNull($dbCol)
            ->where($dbCol, '!=', '');

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
            $emptyCount = $modelClass::query()
                ->where(fn($q) => $q->whereNull($dbCol)->orWhere($dbCol, ''))
                ->count();

            if ($emptyCount > 0) {
                $list[] = [
                    'value' => '(Trống / N/A)',
                    'label' => '(Trống / N/A)',
                    'count' => $emptyCount,
                ];
            }
        }

        return $list;
    }
}
