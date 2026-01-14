@extends('layouts.app')

@section('title', 'Báo cáo tuổi nợ (Aging Report)')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Báo cáo tuổi nợ</h1>
                <p class="text-muted mb-0">Phân tích công nợ theo thời gian quá hạn</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('customer-debts.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Quay lại
                </a>
                <a href="{{ route('customer-debts.aging-report.export', request()->query()) }}" class="btn btn-success">
                    <i class="bi bi-download me-1"></i> Xuất CSV
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-cash-stack text-primary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Tổng công nợ</h6>
                                <h4 class="mb-0">{{ number_format($stats['total_debt'] ?? 0) }}đ</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-check-circle text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Hiện tại (0-30 ngày)</h6>
                                <h4 class="mb-0 text-success">{{ number_format($stats['current'] ?? 0) }}đ</h4>
                                <small class="text-muted">{{ $stats['current_percent'] ?? 0 }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-danger bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-exclamation-triangle text-danger fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Trên 90 ngày</h6>
                                <h4 class="mb-0 text-danger">{{ number_format($stats['over_90'] ?? 0) }}đ</h4>
                                <small class="text-muted">{{ $stats['over_90_percent'] ?? 0 }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="bi bi-people text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">KH quá hạn</h6>
                                <h4 class="mb-0 text-warning">{{ $stats['overdue_customers'] ?? 0 }}</h4>
                                <small class="text-muted">khách hàng</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aging Distribution Chart -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Phân bổ theo tuổi nợ</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="progress" style="height: 30px;">
                            @php
                                $total = $stats['total_debt'] ?? 1;
                                $currentPct = $total > 0 ? ($stats['current'] / $total * 100) : 0;
                                $days31Pct = $total > 0 ? ($stats['days_31_60'] / $total * 100) : 0;
                                $days61Pct = $total > 0 ? ($stats['days_61_90'] / $total * 100) : 0;
                                $over90Pct = $total > 0 ? ($stats['over_90'] / $total * 100) : 0;
                            @endphp
                            <div class="progress-bar bg-success" style="width: {{ $currentPct }}%"
                                title="0-30 ngày: {{ number_format($stats['current'] ?? 0) }}đ">
                                @if($currentPct > 10) {{ round($currentPct) }}% @endif
                            </div>
                            <div class="progress-bar bg-info" style="width: {{ $days31Pct }}%"
                                title="31-60 ngày: {{ number_format($stats['days_31_60'] ?? 0) }}đ">
                                @if($days31Pct > 10) {{ round($days31Pct) }}% @endif
                            </div>
                            <div class="progress-bar bg-warning" style="width: {{ $days61Pct }}%"
                                title="61-90 ngày: {{ number_format($stats['days_61_90'] ?? 0) }}đ">
                                @if($days61Pct > 10) {{ round($days61Pct) }}% @endif
                            </div>
                            <div class="progress-bar bg-danger" style="width: {{ $over90Pct }}%"
                                title="Trên 90 ngày: {{ number_format($stats['over_90'] ?? 0) }}đ">
                                @if($over90Pct > 10) {{ round($over90Pct) }}% @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex flex-wrap gap-3 justify-content-end">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-1">&nbsp;</span> 0-30 ngày
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-info me-1">&nbsp;</span> 31-60 ngày
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-warning me-1">&nbsp;</span> 61-90 ngày
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-danger me-1">&nbsp;</span> >90 ngày
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('customer-debts.aging-report') }}" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control"
                            placeholder="Tìm kiếm theo tên hoặc mã khách hàng..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-search me-1"></i> Tìm kiếm
                        </button>
                        <a href="{{ route('customer-debts.aging-report') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Aging Report Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="bi bi-table me-2"></i>Chi tiết công nợ theo khách hàng
                    <span class="badge bg-secondary ms-2">{{ count($report['customers'] ?? []) }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Khách hàng</th>
                                <th class="text-end text-success">0-30 ngày</th>
                                <th class="text-end text-info">31-60 ngày</th>
                                <th class="text-end text-warning">61-90 ngày</th>
                                <th class="text-end text-danger">>90 ngày</th>
                                <th class="text-end">Tổng nợ</th>
                                <th class="text-center">Rủi ro</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['customers'] ?? [] as $customer)
                                <tr>
                                    <td>
                                        <div>
                                            <a href="{{ route('customer-debts.show', $customer['customer_id']) }}"
                                                class="fw-medium text-decoration-none">
                                                {{ $customer['customer_name'] }}
                                            </a>
                                        </div>
                                        <small class="text-muted">{{ $customer['customer_code'] }} |
                                            {{ $customer['phone'] }}</small>
                                    </td>
                                    <td class="text-end">
                                        @if($customer['current'] > 0)
                                            <span class="text-success">{{ number_format($customer['current']) }}đ</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($customer['days_31_60'] > 0)
                                            <span class="text-info">{{ number_format($customer['days_31_60']) }}đ</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($customer['days_61_90'] > 0)
                                            <span class="text-warning">{{ number_format($customer['days_61_90']) }}đ</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($customer['over_90'] > 0)
                                            <span class="text-danger fw-bold">{{ number_format($customer['over_90']) }}đ</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ number_format($customer['total']) }}đ
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $riskColors = [
                                                'high' => 'danger',
                                                'medium' => 'warning',
                                                'low' => 'info',
                                                'normal' => 'success',
                                            ];
                                            $riskLabels = [
                                                'high' => 'Cao',
                                                'medium' => 'TB',
                                                'low' => 'Thấp',
                                                'normal' => 'BT',
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $riskColors[$customer['risk_level']] ?? 'secondary' }}">
                                            {{ $riskLabels[$customer['risk_level']] ?? $customer['risk_level'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('customer-debts.show', $customer['customer_id']) }}"
                                            class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                        Không có khách hàng nào có công nợ
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($report['customers'] ?? []) > 0)
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td>TỔNG CỘNG</td>
                                    <td class="text-end text-success">{{ number_format($report['summary']['current'] ?? 0) }}đ
                                    </td>
                                    <td class="text-end text-info">{{ number_format($report['summary']['days_31_60'] ?? 0) }}đ
                                    </td>
                                    <td class="text-end text-warning">
                                        {{ number_format($report['summary']['days_61_90'] ?? 0) }}đ</td>
                                    <td class="text-end text-danger">{{ number_format($report['summary']['over_90'] ?? 0) }}đ
                                    </td>
                                    <td class="text-end">{{ number_format($report['summary']['total'] ?? 0) }}đ</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Generated time -->
        <div class="text-muted text-end mt-3">
            <small>Báo cáo tạo lúc: {{ $report['generated_at'] ?? now()->format('Y-m-d H:i:s') }}</small>
        </div>
    </div>
@endsection