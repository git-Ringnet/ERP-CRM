<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TableColumnFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableColumnFilterApiController extends Controller
{
    /**
     * Fetch distinct values for a given module and column
     */
    public function distinctValues(Request $request): JsonResponse
    {
        $module = trim((string)$request->query('module', ''));
        $column = trim((string)$request->query('column', ''));
        $search = trim((string)$request->query('search', ''));
        $limit = min((int)$request->query('limit', 60), 100);

        if (empty($module) || empty($column)) {
            return response()->json(['values' => []]);
        }

        $values = TableColumnFilterService::getDistinctValues($module, $column, $search ?: null, $limit);

        return response()->json([
            'success' => true,
            'module' => $module,
            'column' => $column,
            'values' => $values,
        ]);
    }
}
