<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\DashboardExportService;
use App\Models\PermissionAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\RedirectResponse;

/**
 * BusinessDashboardController handles business activity dashboard operations
 * Requirements: 1.9, 2.1-2.7, 8.1-8.4, 8.9, 8.10, 9.1, 9.4, 9.5, 10.1, 10.6, 10.7, 12.1, 12.2, 12.6
 */
class BusinessDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param DashboardService $dashboardService
     * @param DashboardExportService $exportService
     */
    public function __construct(
        private DashboardService $dashboardService,
        private DashboardExportService $exportService
    ) {
        // Apply authorization middleware
        $this->middleware(function ($request, $next) {
            if (!Gate::allows('viewDashboard', 'business-dashboard')) {
                // Log unauthorized access attempt (Requirement 9.4)
                $this->logAccessAttempt(auth()->user(), 'view_dashboard', false);
                
                abort(403, 'Bạn không có quyền truy cập trang này.');
            }
            
            // Log successful access attempt (Requirement 9.4)
            $this->logAccessAttempt(auth()->user(), 'view_dashboard', true);
            
            return $next($request);
        });
    }

    /**
     * Display the business dashboard
     * Requirements: 1.9, 2.1, 2.2, 2.3, 2.4, 10.1, 12.1, 12.2
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        try {
            // Get time period from request or session (Requirement 2.7)
            $periodType = $request->input('period_type', session('dashboard_period_type', 'month'));
            $startDate = $request->input('start_date', session('dashboard_start_date'));
            $endDate = $request->input('end_date', session('dashboard_end_date'));

            // Parse predefined periods (Requirement 2.2)
            if (!$startDate || !$endDate) {
                $dates = $this->parsePredefinedPeriod($periodType);
                $startDate = $dates['start'];
                $endDate = $dates['end'];
            }

            // Validate date range (Requirement 2.5, 2.6, 9.4)
            $validation = $this->validateDateRange($startDate, $endDate);
            if (!$validation['valid']) {
                return view('dashboard.business-activity', [
                    'error' => $validation['message'],
                    'period_type' => $periodType,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);
            }

            // Store selected time period in session (Requirement 2.7, 9.6)
            session([
                'dashboard_period_type' => $periodType,
                'dashboard_start_date' => $startDate,
                'dashboard_end_date' => $endDate,
            ]);

            // Get dashboard data (Requirement 1.9)
            $data = $this->dashboardService->getDashboardData([
                'period_type' => $periodType,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // Check export permission for view (Requirement 9.5)
            $canExport = Gate::allows('exportReports', 'business-dashboard');

            return view('dashboard.business-activity', array_merge($data, [
                'can_export' => $canExport,
            ]));

        } catch (\Illuminate\Database\QueryException $e) {
            // Database connection error (Requirement 12.1, 12.6)
            Log::error('Dashboard database error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'timestamp' => now(),
            ]);

            return view('dashboard.business-activity', [
                'error' => 'Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.',
            ]);

        } catch (\Exception $e) {
            // General error (Requirement 12.2, 12.6, 9.13)
            Log::error('Dashboard error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'timestamp' => now(),
            ]);

            return view('dashboard.business-activity', [
                'error' => 'Đã xảy ra lỗi khi tải dashboard. Vui lòng thử lại sau.',
            ]);
        }
    }

    /**
     * Export dashboard report
     * Requirements: 8.1, 8.2, 8.3, 8.4, 8.9, 8.10, 9.5, 9.8
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        try {
            // Check export permission (Requirement 9.5)
            if (!Gate::allows('exportReports', 'business-dashboard')) {
                // Log unauthorized export attempt (Requirement 9.11)
                $this->logAccessAttempt(auth()->user(), 'export_reports', false);
                
                abort(403, 'Bạn không có quyền xuất báo cáo.');
            }

            // Log successful export attempt (Requirement 9.11)
            $this->logAccessAttempt(auth()->user(), 'export_reports', true);

            // Validate export format (Requirement 8.2, 8.3)
            $request->validate([
                'format' => 'required|in:pdf,excel,csv',
            ]);

            $format = $request->input('format');

            // Get current dashboard filters from session
            $filters = [
                'period_type' => session('dashboard_period_type', 'month'),
                'start_date' => session('dashboard_start_date'),
                'end_date' => session('dashboard_end_date'),
            ];

            // Parse dates if needed
            if (!$filters['start_date'] || !$filters['end_date']) {
                $dates = $this->parsePredefinedPeriod($filters['period_type']);
                $filters['start_date'] = $dates['start'];
                $filters['end_date'] = $dates['end'];
            }

            // Get dashboard data
            $data = $this->dashboardService->getDashboardData($filters);

            // Generate export file based on format (Requirement 8.4)
            $filePath = match ($format) {
                'pdf' => $this->exportService->exportToPDF($data, $filters),
                'excel' => $this->exportService->exportToExcel($data, $filters),
                'csv' => $this->exportService->exportToCSV($data, $filters),
            };

            // Stream file to browser (Requirement 8.9)
            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation error
            Log::warning('Dashboard export validation error', [
                'errors' => $e->errors(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Định dạng xuất không hợp lệ.');

        } catch (\Exception $e) {
            // Export error (Requirement 8.10, 12.6, 9.13)
            Log::error('Dashboard export error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'timestamp' => now(),
            ]);

            return back()->with('error', 'Không thể xuất báo cáo. Vui lòng thử lại.');
        }
    }

    /**
     * Refresh dashboard data (clear cache)
     * Requirements: 10.6, 10.7, 9.10
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function refresh(Request $request): RedirectResponse
    {
        try {
            // Get current filters from session
            $filters = [
                'period_type' => session('dashboard_period_type', 'month'),
                'start_date' => session('dashboard_start_date'),
                'end_date' => session('dashboard_end_date'),
            ];

            // Parse dates if needed
            if (!$filters['start_date'] || !$filters['end_date']) {
                $dates = $this->parsePredefinedPeriod($filters['period_type']);
                $filters['start_date'] = $dates['start'];
                $filters['end_date'] = $dates['end'];
            }

            // Clear cache (Requirement 10.6)
            $this->dashboardService->clearCache($filters);

            // Log refresh action (Requirement 9.13)
            Log::info('Dashboard cache refreshed', [
                'user_id' => auth()->id(),
                'filters' => $filters,
                'timestamp' => now(),
            ]);

            return redirect()->route('dashboard.business-activity')
                ->with('success', 'Đã làm mới dữ liệu dashboard thành công.');

        } catch (\Exception $e) {
            // Error during refresh (Requirement 12.6, 9.13)
            Log::error('Dashboard refresh error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'timestamp' => now(),
            ]);

            return redirect()->route('dashboard.business-activity')
                ->with('error', 'Không thể làm mới dữ liệu. Vui lòng thử lại.');
        }
    }

    /**
     * Parse predefined time period to start and end dates
     * Requirements: 2.2
     *
     * @param string $periodType
     * @return array
     */
    private function parsePredefinedPeriod(string $periodType): array
    {
        $now = Carbon::now();

        return match ($periodType) {
            'today' => [
                'start' => $now->copy()->startOfDay()->format('Y-m-d'),
                'end' => $now->copy()->endOfDay()->format('Y-m-d'),
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek()->format('Y-m-d'),
                'end' => $now->copy()->endOfWeek()->format('Y-m-d'),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $now->copy()->endOfMonth()->format('Y-m-d'),
            ],
            'quarter' => [
                'start' => $now->copy()->startOfQuarter()->format('Y-m-d'),
                'end' => $now->copy()->endOfQuarter()->format('Y-m-d'),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear()->format('Y-m-d'),
                'end' => $now->copy()->endOfYear()->format('Y-m-d'),
            ],
            default => [
                'start' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $now->copy()->endOfMonth()->format('Y-m-d'),
            ],
        };
    }

    /**
     * Validate date range
     * Requirements: 2.5, 2.6, 9.4
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    private function validateDateRange(?string $startDate, ?string $endDate): array
    {
        if (!$startDate || !$endDate) {
            return [
                'valid' => false,
                'message' => 'Vui lòng chọn ngày bắt đầu và ngày kết thúc.',
            ];
        }

        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            // Check if start date is after end date (Requirement 2.5, 2.6)
            if ($start->gt($end)) {
                return [
                    'valid' => false,
                    'message' => 'Ngày bắt đầu phải trước hoặc bằng ngày kết thúc.',
                ];
            }

            return ['valid' => true];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Định dạng ngày không hợp lệ.',
            ];
        }
    }

    /**
     * Log dashboard access attempts
     * Requirements: 9.4, 9.11
     *
     * @param \App\Models\User $user
     * @param string $action
     * @param bool $success
     * @return void
     */
    private function logAccessAttempt($user, string $action, bool $success): void
    {
        try {
            PermissionAuditLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'entity_type' => 'business_dashboard',
                'entity_id' => null,
                'old_value' => null,
                'new_value' => [
                    'success' => $success,
                    'ip_address' => request()->ip(),
                ],
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::warning('Failed to log dashboard access attempt', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'action' => $action,
            ]);
        }
    }
}
