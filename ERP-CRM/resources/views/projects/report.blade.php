@extends('layouts.app')

@section('title', 'Báo cáo dự án')
@section('page-title', 'Báo cáo tổng hợp theo dự án')

@section('content')
    <div class="space-y-4">
        <!-- Filter Bar -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <!-- Quarter Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Theo Quý</label>
                        <select name="quarter" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Tất cả Quý --</option>
                            <option value="Q1" {{ request('quarter') == 'Q1' ? 'selected' : '' }}>Quý 1 (T1 - T3)</option>
                            <option value="Q2" {{ request('quarter') == 'Q2' ? 'selected' : '' }}>Quý 2 (T4 - T6)</option>
                            <option value="Q3" {{ request('quarter') == 'Q3' ? 'selected' : '' }}>Quý 3 (T7 - T9)</option>
                            <option value="Q4" {{ request('quarter') == 'Q4' ? 'selected' : '' }}>Quý 4 (T10 - T12)</option>
                        </select>
                    </div>

                    <!-- Year Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Năm</label>
                        <input type="number" name="year" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" value="{{ request('year', date('Y')) }}" placeholder="Năm">
                    </div>

                    <!-- Vendor Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Hãng (Vendor)</label>
                        <select name="vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Tất cả Hãng --</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sales Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nhân viên Sales</label>
                        <select name="manager_id" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Tất cả Sales --</option>
                            @foreach($managers as $mgr)
                                <option value="{{ $mgr->id }}" {{ request('manager_id') == $mgr->id ? 'selected' : '' }}>
                                    {{ $mgr->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- PM Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nhân viên PM/PO</label>
                        <select name="initial_processed_by" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Tất cả PM/PO --</option>
                            @foreach($pms as $pm)
                                <option value="{{ $pm->id }}" {{ request('initial_processed_by') == $pm->id ? 'selected' : '' }}>
                                    {{ $pm->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Trạng thái ĐKDA</label>
                        <select name="registration_status" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Tất cả Trạng thái --</option>
                            <option value="pending" {{ request('registration_status') == 'pending' ? 'selected' : '' }}>Chờ tiếp nhận</option>
                            <option value="incomplete" {{ request('registration_status') == 'incomplete' ? 'selected' : '' }}>Thiếu thông tin</option>
                            <option value="duplicate" {{ request('registration_status') == 'duplicate' ? 'selected' : '' }}>Dự án trùng</option>
                            <option value="vendor_processing" {{ request('registration_status') == 'vendor_processing' ? 'selected' : '' }}>Chờ hãng phản hồi</option>
                            <option value="vendor_reminded" {{ request('registration_status') == 'vendor_reminded' ? 'selected' : '' }}>Đang giục hãng</option>
                            <option value="vendor_quoted" {{ request('registration_status') == 'vendor_quoted' ? 'selected' : '' }}>Đã có giá hãng</option>
                            <option value="update_status" {{ request('registration_status') == 'update_status' ? 'selected' : '' }}>Đang cập nhật (Update Status)</option>
                            <option value="closed_won" {{ request('registration_status') == 'closed_won' ? 'selected' : '' }}>Closed Won (Thành công)</option>
                            <option value="closed_lost" {{ request('registration_status') == 'closed_lost' ? 'selected' : '' }}>Closed Lost (Thất bại)</option>
                            <option value="expired" {{ request('registration_status') == 'expired' ? 'selected' : '' }}>Expired (Quá hạn/Hủy)</option>
                        </select>
                    </div>

                    <!-- Date Range: Registration -->
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ngày Đăng Ký (Từ - Đến)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="start_date" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" value="{{ request('start_date') }}">
                            <input type="date" name="end_date" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" value="{{ request('end_date') }}">
                        </div>
                    </div>

                    <!-- Date Range: Expiry -->
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Ngày Hết Hạn Dự án (Từ - Đến)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="expiry_start_date" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" value="{{ request('expiry_start_date') }}">
                            <input type="date" name="expiry_end_date" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary" value="{{ request('expiry_end_date') }}">
                        </div>
                    </div>

                    <!-- Overdue SLA Filter -->
                    <div class="col-span-2 flex items-center h-full pt-4">
                        <label class="inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="is_overdue_sla" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" {{ request('is_overdue_sla') == '1' ? 'checked' : '' }}>
                            <span class="ml-2 text-sm font-bold text-red-600 uppercase tracking-wider"><i class="fas fa-exclamation-triangle mr-1"></i> Chỉ xem dự án quá hạn SLA</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-gray-100 pt-4 gap-3">
                    <div class="flex gap-2">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium shadow-sm">
                            <i class="fas fa-filter mr-1.5"></i> Áp dụng bộ lọc
                        </button>
                        <a href="{{ route('projects.report') }}" class="px-5 py-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg transition-colors text-sm font-medium">
                            <i class="fas fa-redo mr-1.5"></i> Reset bộ lọc
                        </a>
                    </div>
                    <a href="{{ route('projects.export', request()->query()) }}" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors text-sm font-semibold shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-file-excel text-base"></i> Tải báo cáo Excel (Bản đầy đủ)
                    </a>
                </div>
            </form>
        </div>

        <!-- Executive KPI Dashboard Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 lg:grid-cols-10 gap-3">
            <div class="bg-white rounded-xl p-3 border border-gray-200 shadow-sm text-center flex flex-col justify-between">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Tổng đăng ký</p>
                <p class="text-xl font-bold text-gray-900 mt-2">{{ number_format($kpis['total']) }}</p>
            </div>
            <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-200 text-center flex flex-col justify-between">
                <p class="text-xs text-emerald-700 font-bold uppercase tracking-wider">Closed Won</p>
                <p class="text-xl font-bold text-emerald-800 mt-2">{{ $kpis['closed_won_rate'] }}%</p>
            </div>
            <div class="bg-blue-50 rounded-xl p-3 border border-blue-200 text-center flex flex-col justify-between">
                <p class="text-xs text-blue-700 font-bold uppercase tracking-wider">Chờ Hãng</p>
                <p class="text-xl font-bold text-blue-800 mt-2">{{ number_format($kpis['waiting_vendor']) }}</p>
            </div>
            <div class="bg-red-50 rounded-xl p-3 border border-red-200 text-center flex flex-col justify-between">
                <p class="text-xs text-red-700 font-bold uppercase tracking-wider">Quá SLA</p>
                <p class="text-xl font-bold text-red-800 mt-2">{{ number_format($kpis['overdue_sla']) }}</p>
            </div>
            <div class="bg-orange-50 rounded-xl p-3 border border-orange-200 text-center flex flex-col justify-between">
                <p class="text-xs text-orange-700 font-bold uppercase tracking-wider">Duplicate</p>
                <p class="text-xl font-bold text-orange-800 mt-2">{{ $kpis['duplicate_rate'] }}%</p>
            </div>
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-200 text-center flex flex-col justify-between">
                <p class="text-xs text-slate-700 font-bold uppercase tracking-wider">Expired</p>
                <p class="text-xl font-bold text-slate-800 mt-2">{{ $kpis['expired_rate'] }}%</p>
            </div>
            <div class="bg-rose-50 rounded-xl p-3 border border-rose-200 text-center flex flex-col justify-between">
                <p class="text-xs text-rose-700 font-bold uppercase tracking-wider">Hủy ko update</p>
                <p class="text-xl font-bold text-rose-800 mt-2">{{ number_format($kpis['cancelled_no_update']) }}</p>
            </div>
            <div class="bg-purple-50 rounded-xl p-3 border border-purple-200 text-center flex flex-col justify-between col-span-1 md:col-span-2">
                <p class="text-xs text-purple-700 font-bold uppercase tracking-wider">PM xử lý TB</p>
                <p class="text-lg font-bold text-purple-900 mt-2">{{ $kpis['avg_pm_hours'] }} Giờ</p>
            </div>
            <div class="bg-indigo-50 rounded-xl p-3 border border-indigo-200 text-center flex flex-col justify-between">
                <p class="text-xs text-indigo-700 font-bold uppercase tracking-wider">Hãng phản hồi TB</p>
                <p class="text-lg font-bold text-indigo-900 mt-2">{{ $kpis['avg_vendor_hours'] }} Giờ</p>
            </div>
        </div>

        <!-- Summary Financial Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                <p class="text-sm text-gray-500">Tổng dự toán</p>
                <p class="text-xl font-bold text-gray-900">{{ number_format($totals['budget']) }} đ</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <p class="text-sm text-blue-600">Tổng doanh thu</p>
                <p class="text-xl font-bold text-blue-700">{{ number_format($totals['revenue']) }} đ</p>
            </div>
            <div class="bg-orange-50 rounded-lg p-4 border border-orange-100">
                <p class="text-sm text-orange-600">Tổng giá vốn</p>
                <p class="text-xl font-bold text-orange-700">{{ number_format($totals['cost']) }} đ</p>
            </div>
            <div class="{{ $totals['profit'] >= 0 ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' }} rounded-lg p-4 border">
                <p class="text-sm {{ $totals['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">Tổng lợi nhuận</p>
                <p class="text-xl font-bold {{ $totals['profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                    {{ number_format($totals['profit']) }} đ</p>
            </div>
            <div class="bg-red-50 rounded-lg p-4 border border-red-100">
                <p class="text-sm text-red-600">Tổng công nợ</p>
                <p class="text-xl font-bold text-red-700">{{ number_format($totals['debt']) }} đ</p>
            </div>
        </div>

        <!-- Report Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Chi tiết theo dự án</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã dự án</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên dự án</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Khách hàng</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Dự toán</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Doanh thu</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Giá vốn</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Lợi nhuận</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Công nợ</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($projects as $project)
                            @php
                                $revenue = $project->total_revenue;
                                $cost = $project->total_cost;
                                $profit = $project->profit;
                                $profitPercent = $project->profit_percent;
                                $debt = $project->total_debt;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('projects.show', $project->id) }}"
                                        class="font-medium text-primary hover:underline">
                                        {{ $project->code }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $project->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $project->customer_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-right">{{ number_format($project->budget) }} đ</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-blue-600">{{ number_format($revenue) }}
                                    đ</td>
                                <td class="px-4 py-3 text-sm text-right text-orange-600">{{ number_format($cost) }} đ</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <span class="font-medium {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($profit) }} đ
                                    </span>
                                    <span class="text-xs text-gray-500">({{ number_format($profitPercent, 1) }}%)</span>
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-right {{ $debt > 0 ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                    {{ number_format($debt) }} đ
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $project->status_color }}">
                                        {{ $project->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    Không có dữ liệu
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($projects->count() > 0)
                        <tfoot class="bg-gray-100 font-semibold">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right">Tổng cộng:</td>
                                <td class="px-4 py-3 text-right">{{ number_format($totals['budget']) }} đ</td>
                                <td class="px-4 py-3 text-right text-blue-600">{{ number_format($totals['revenue']) }} đ</td>
                                <td class="px-4 py-3 text-right text-orange-600">{{ number_format($totals['cost']) }} đ</td>
                                <td
                                    class="px-4 py-3 text-right {{ $totals['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($totals['profit']) }} đ
                                </td>
                                <td class="px-4 py-3 text-right text-red-600">{{ number_format($totals['debt']) }} đ</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection