<?php

namespace App\Http\Controllers;

use App\Services\BODDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private BODDashboardService $bodService
    ) {}

    /**
     * Display the BOD Executive Dashboard.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'period_type',
            'date_from',
            'date_to',
            'team',
            'sales_id',
            'customer_id',
            'vendor_id',
            'model_code',
            'deal_type',
        ]);

        $bodData = $this->bodService->getDashboardData($filters, auth()->user());

        return view('dashboard.bod-overview', $bodData);
    }
}
