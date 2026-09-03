@extends('layouts.app')

@section('title', 'Dashboard & Báo cáo kỹ thuật')
@section('page-title', 'Dashboard & Báo cáo kỹ thuật')

@section('content')
<div class="space-y-6" x-data="{ openExportModal: false }">
    <!-- Filter & Export Section -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 border-b border-gray-100 pb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-filter text-primary mr-2"></i> Lọc và Xuất Báo Cáo
                </h2>
                <p class="text-xs text-gray-500">Lọc dữ liệu thống kê và xuất báo cáo Excel cho BOD và Leader</p>
            </div>
            
            @can('export_technical_tickets')
            <div class="mt-4 md:mt-0">
                <button type="button" @click="openExportModal = true" class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                    <i class="fas fa-file-excel mr-2"></i> Xuất Báo Cáo Excel
                </button>
            </div>
            @endcan
        </div>

        <form id="dashboardFilterForm" method="GET" action="{{ route('technical.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label for="date_from" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Từ ngày</label>
                <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label for="date_to" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Đến ngày</label>
                <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
            </div>
            <div>
                <label for="assigned_to" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kỹ sư</label>
                <select name="assigned_to" id="assigned_to" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả kỹ sư</option>
                    @foreach($engineers as $eng)
                        <option value="{{ $eng->id }}" {{ (isset($filters['assigned_to']) && $filters['assigned_to'] == $eng->id) ? 'selected' : '' }}>{{ $eng->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="created_by" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nhân viên Sales</label>
                <select name="created_by" id="created_by" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả Sales</option>
                    @foreach($salesUsers as $sale)
                        <option value="{{ $sale->id }}" {{ (isset($filters['created_by']) && $filters['created_by'] == $sale->id) ? 'selected' : '' }}>{{ $sale->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="customer_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Khách hàng</label>
                <select name="customer_id" id="customer_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả khách hàng</option>
                    @foreach($customers as $cust)
                        <option value="{{ $cust->id }}" {{ (isset($filters['customer_id']) && $filters['customer_id'] == $cust->id) ? 'selected' : '' }}>{{ $cust->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="supplier_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Hãng / Vendor</label>
                <select name="supplier_id" id="supplier_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả Vendor</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ (isset($filters['supplier_id']) && $filters['supplier_id'] == $sup->id) ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="project_id" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Dự án</label>
                <select name="project_id" id="project_id" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả dự án</option>
                    @foreach($projects as $proj)
                        <option value="{{ $proj->id }}" {{ (isset($filters['project_id']) && $filters['project_id'] == $proj->id) ? 'selected' : '' }}>{{ $proj->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="work_type" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Loại công việc</label>
                <select name="work_type" id="work_type" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả loại</option>
                    <option value="survey" {{ (isset($filters['work_type']) && $filters['work_type'] == 'survey') ? 'selected' : '' }}>Khảo sát / Tư vấn / Thiết kế</option>
                    <option value="BOM" {{ (isset($filters['work_type']) && $filters['work_type'] == 'BOM') ? 'selected' : '' }}>BOM Support</option>
                    <option value="documentation" {{ (isset($filters['work_type']) && $filters['work_type'] == 'documentation') ? 'selected' : '' }}>Technical Documents</option>
                    <option value="POC" {{ (isset($filters['work_type']) && $filters['work_type'] == 'POC') ? 'selected' : '' }}>POC / Demo</option>
                    <option value="deployment" {{ (isset($filters['work_type']) && $filters['work_type'] == 'deployment') ? 'selected' : '' }}>Deployment</option>
                    <option value="after_sales" {{ (isset($filters['work_type']) && $filters['work_type'] == 'after_sales') ? 'selected' : '' }}>After-sales support</option>
                    <option value="training" {{ (isset($filters['work_type']) && $filters['work_type'] == 'training') ? 'selected' : '' }}>Training / Update</option>
                    <option value="event" {{ (isset($filters['work_type']) && $filters['work_type'] == 'event') ? 'selected' : '' }}>Event / Speaker</option>
                    <option value="other" {{ (isset($filters['work_type']) && $filters['work_type'] == 'other') ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div>
                <label for="sla_status" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Trạng thái SLA</label>
                <select name="sla_status" id="sla_status" class="w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="ontime" {{ (isset($filters['sla_status']) && $filters['sla_status'] == 'ontime') ? 'selected' : '' }}>Kịp hạn (On time)</option>
                    <option value="overdue" {{ (isset($filters['sla_status']) && $filters['sla_status'] == 'overdue') ? 'selected' : '' }}>Trễ hạn (Overdue)</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/95 transition-colors shadow-sm">
                    Lọc dữ liệu
                </button>
                <a href="{{ route('technical.dashboard') }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-colors border border-gray-200">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- 1. Team Dashboard Overview -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-5">
        <!-- Card 1: Total -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-5 rounded-xl border border-blue-200 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-blue-500/10 text-7xl"><i class="fas fa-ticket-alt"></i></div>
            <h4 class="text-xs font-bold text-blue-700 uppercase tracking-wider">Tổng Ticket</h4>
            <p class="text-3xl font-extrabold text-blue-900 mt-2">{{ $totalTickets }}</p>
            <p class="text-xs text-blue-600 mt-1">Trong khoảng thời gian lọc</p>
        </div>

        <!-- Card 2: Open/Assigned -->
        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-5 rounded-xl border border-indigo-200 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-indigo-500/10 text-7xl"><i class="fas fa-spinner"></i></div>
            <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Đang Xử Lý</h4>
            <p class="text-3xl font-extrabold text-indigo-900 mt-2">{{ $openTickets }}</p>
            <p class="text-xs text-indigo-600 mt-1">Chưa hoàn thành</p>
        </div>

        <!-- Card 3: Pending/Escalate -->
        <div class="bg-gradient-to-br from-amber-50 to-amber-100 p-5 rounded-xl border border-amber-200 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-amber-500/10 text-7xl"><i class="fas fa-exclamation-triangle"></i></div>
            <h4 class="text-xs font-bold text-amber-700 uppercase tracking-wider">Pending / Escalate</h4>
            <p class="text-3xl font-extrabold text-amber-900 mt-2">{{ $pendingTickets + $escalateTickets }}</p>
            <p class="text-xs text-amber-600 mt-1">Pending: {{ $pendingTickets }} | Escalate: {{ $escalateTickets }}</p>
        </div>

        <!-- Card 4: Closed -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 p-5 rounded-xl border border-green-200 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-green-500/10 text-7xl"><i class="fas fa-check-circle"></i></div>
            <h4 class="text-xs font-bold text-green-700 uppercase tracking-wider">Đã Hoàn Thành</h4>
            <p class="text-3xl font-extrabold text-green-900 mt-2">{{ $closedTickets }}</p>
            <p class="text-xs text-green-600 mt-1">Đã hoàn thành & đóng</p>
        </div>

        <!-- Card 5: Overdue -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 p-5 rounded-xl border border-red-200 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-red-500/10 text-7xl"><i class="fas fa-clock"></i></div>
            <h4 class="text-xs font-bold text-red-700 uppercase tracking-wider">Trễ Hạn</h4>
            <p class="text-3xl font-extrabold text-red-900 mt-2">{{ $overdueTickets }}</p>
            <p class="text-xs text-red-600 mt-1">Vượt quá SLA deadline</p>
        </div>

        <!-- Card 6: SLA Rate -->
        <div class="bg-gradient-to-br from-teal-50 to-teal-100 p-5 rounded-xl border border-teal-200 shadow-sm relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-teal-500/10 text-7xl"><i class="fas fa-percent"></i></div>
            <h4 class="text-xs font-bold text-teal-700 uppercase tracking-wider">Tỷ lệ Đạt SLA</h4>
            <p class="text-3xl font-extrabold text-teal-900 mt-2">{{ $slaRate }}%</p>
            <p class="text-xs text-teal-600 mt-1">Trên tổng số có SLA</p>
        </div>
    </div>

    <!-- 2. Per Engineer Performance -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-150 bg-gray-50 flex justify-between items-center">
            <h3 class="text-md font-bold text-gray-800 flex items-center">
                <i class="fas fa-users-cog text-primary mr-2"></i> Hiệu suất đội ngũ Kỹ sư (Per Engineer Dashboard)
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-xs text-gray-500 uppercase font-semibold">
                    <tr class="divide-x divide-gray-200 border-b border-gray-200">
                        <th class="px-6 py-3 text-center w-12">STT</th>
                        <th class="px-6 py-3">Kỹ sư phụ trách</th>
                        <th class="px-6 py-3 text-center">Được giao (Assigned)</th>
                        <th class="px-6 py-3 text-center">Hoàn thành (Completed)</th>
                        <th class="px-6 py-3 text-center">Tạm dừng (Pending/Escalate)</th>
                        <th class="px-6 py-3 text-center">Trễ Hạn (Overdue)</th>
                        <th class="px-6 py-3 text-center">Thời gian xử lý TB (Giờ)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($engineerStats as $stat)
                        <tr class="hover:bg-gray-50/50 divide-x divide-gray-100">
                            <td class="px-6 py-4 text-center text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $stat['engineer']->name }}</td>
                            <td class="px-6 py-4 text-center text-blue-600 font-bold">{{ $stat['assigned'] }}</td>
                            <td class="px-6 py-4 text-center text-green-600 font-bold">{{ $stat['completed'] }}</td>
                            <td class="px-6 py-4 text-center text-yellow-600 font-bold">{{ $stat['pending'] }}</td>
                            <td class="px-6 py-4 text-center text-red-600 font-bold">{{ $stat['overdue'] }}</td>
                            <td class="px-6 py-4 text-center text-gray-700 font-medium bg-gray-50/30">{{ $stat['avg_time'] }}h</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400 italic">Không tìm thấy dữ liệu kỹ sư phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Recent Updated Tickets & Discussions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-150 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="text-md font-bold text-gray-800 flex items-center">
                    <i class="fas fa-bell text-amber-500 mr-2"></i> Ticket có Cập nhật & Trao đổi Mới nhất
                </h3>
                <p class="text-xs text-gray-500">Giúp Sales và Kỹ thuật nhanh chóng nắm bắt các Ticket vừa có tin nhắn trao đổi hoặc cập nhật tiến độ mới</p>
            </div>
            <a href="{{ route('technical-tickets.index') }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1 self-start sm:self-auto">
                <span>Xem tất cả ticket</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-xs text-gray-500 uppercase font-semibold">
                    <tr class="divide-x divide-gray-200 border-b border-gray-200">
                        <th class="px-4 py-3 text-center w-12">STT</th>
                        <th class="px-4 py-3 w-32">Mã Ticket</th>
                        <th class="px-4 py-3">Tiêu đề / Dự án</th>
                        <th class="px-4 py-3 w-36">Sales phụ trách</th>
                        <th class="px-4 py-3 w-40">Kỹ sư xử lý</th>
                        <th class="px-4 py-3 text-center w-40">Trao đổi & Cập nhật</th>
                        <th class="px-4 py-3 text-center w-28">Trạng thái</th>
                        <th class="px-4 py-3 text-center w-24">Xem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentUpdatedTickets as $ticket)
                        <tr class="hover:bg-gray-50/60 divide-x divide-gray-100">
                            <td class="px-4 py-3 text-center text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-bold text-gray-800 whitespace-nowrap">
                                <a href="{{ route('technical-tickets.show', $ticket->id) }}" class="text-blue-600 hover:underline">
                                    {{ $ticket->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900 leading-snug">{{ $ticket->title }}</div>
                                @if($ticket->project)
                                    <div class="text-xs text-purple-600 font-medium mt-0.5"><i class="fas fa-diagram-project mr-1"></i> {{ $ticket->project->name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700 text-xs font-medium">
                                {{ $ticket->creator->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 text-xs font-medium">
                                {{ $ticket->assignedEngineers->pluck('name')->join(', ') ?: 'Chưa phân công' }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Tin nhắn / Trao đổi -->
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ ($ticket->comments_count ?? 0) > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-gray-50 text-gray-400' }}" title="Số tin nhắn trao đổi">
                                            <i class="fas fa-comment-dots text-blue-500"></i> {{ $ticket->comments_count ?? 0 }}
                                        </span>
                                        <!-- Nhật ký xử lý / Cập nhật -->
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ ($ticket->support_logs_count ?? 0) > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-gray-50 text-gray-400' }}" title="Số lần cập nhật tiến độ / nhật ký hỗ trợ">
                                            <i class="fas fa-history text-amber-500"></i> {{ $ticket->support_logs_count ?? 0 }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-gray-400" title="{{ $ticket->updated_at ? $ticket->updated_at->format('d/m/Y H:i') : '' }}">
                                        <i class="far fa-clock mr-0.5"></i>{{ $ticket->updated_at ? $ticket->updated_at->diffForHumans() : '-' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-800">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('technical-tickets.show', $ticket->id) }}" 
                                   title="Xem chi tiết ticket" 
                                   class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-semibold transition-colors border border-blue-200">
                                    <i class="fas fa-eye mr-1"></i> Xem
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-400 italic">Không có ticket nào trong phạm vi tìm kiếm.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts Breakdowns -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Categories Dashboard -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="text-md font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center">
                <i class="fas fa-layer-group text-primary mr-2"></i> Thống kê theo loại công việc (Categories Dashboard)
            </h3>
            <div class="h-64 relative">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Sales Dashboard -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="text-md font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center">
                <i class="fas fa-user-friends text-primary mr-2"></i> Thống kê theo Nhân viên Sales
            </h3>
            <div class="h-64 relative">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Vendor Breakdown -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="text-md font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center">
                <i class="fas fa-building text-primary mr-2"></i> Thống kê theo Hãng / Vendor
            </h3>
            <div class="h-64 relative">
                <canvas id="vendorChart"></canvas>
            </div>
        </div>

        <!-- Project Breakdown -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <h3 class="text-md font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center">
                <i class="fas fa-diagram-project text-primary mr-2"></i> Thống kê theo Dự án
            </h3>
            <div class="h-64 relative">
                <canvas id="projectChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 🌟 ADVANCED EXPORT MODAL -->
    <div x-show="openExportModal"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black bg-opacity-50" x-cloak>
        <div @click.away="openExportModal = false"
            class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-gray-200 overflow-hidden transform transition-all text-left">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-700 text-white flex justify-between items-center shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center text-white">
                        <i class="fas fa-file-excel text-lg"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-white leading-tight">Xuất Báo Cáo Kỹ Thuật (Excel)</h3>
                        <p class="text-xs text-green-100">Báo cáo đa tầng theo Kỹ sư, Workload, Loại việc, Sales và SLA</p>
                    </div>
                </div>
                <button @click="openExportModal = false" class="text-white/80 hover:text-white transition-colors focus:outline-none">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="{{ route('technical.export') }}" method="GET" class="p-6 space-y-5">
                <!-- Select Report Structure -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                        1. Chọn Mẫu & Tiêu Chí Xuất Báo Cáo <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2">
                        <label class="relative flex items-start p-3 border-2 border-green-500 bg-green-50/40 rounded-xl cursor-pointer hover:bg-green-50 transition-colors">
                            <input type="radio" name="report_type" value="all" checked class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">
                                    <i class="fas fa-layer-group text-green-600 mr-1.5"></i> Báo Cáo Tổng Hợp Toàn Diện (Đầy đủ tất cả các Sheet)
                                </span>
                                <span class="block text-xs text-gray-600 mt-0.5">
                                    Gồm 7 Sheet: <strong>Theo Engineer (Workload)</strong> + <strong>Theo Sales</strong> + <strong>Theo Vendor</strong> + <strong>Theo Loại việc</strong> + <strong>Theo Dự án</strong> + <strong>Theo Khách hàng</strong> + <strong>Chi tiết Raw Data</strong>.
                                </span>
                            </div>
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <label class="relative flex items-start p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="report_type" value="engineer" class="mt-0.5 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                <div class="ml-2">
                                    <span class="block text-xs font-bold text-gray-900">Theo Engineer</span>
                                    <span class="block text-[11px] text-gray-500">Workload, giờ xử lý & SLA</span>
                                </div>
                            </label>

                            <label class="relative flex items-start p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="report_type" value="sales" class="mt-0.5 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                <div class="ml-2">
                                    <span class="block text-xs font-bold text-gray-900">Theo Sales</span>
                                    <span class="block text-[11px] text-gray-500">Yêu cầu theo từng Sales</span>
                                </div>
                            </label>

                            <label class="relative flex items-start p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="report_type" value="vendor" class="mt-0.5 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                <div class="ml-2">
                                    <span class="block text-xs font-bold text-gray-900">Theo Vendor (Hãng)</span>
                                    <span class="block text-[11px] text-gray-500">Sophos, Fortinet, Cisco...</span>
                                </div>
                            </label>

                            <label class="relative flex items-start p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="report_type" value="project" class="mt-0.5 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                <div class="ml-2">
                                    <span class="block text-xs font-bold text-gray-900">Theo Dự án</span>
                                    <span class="block text-[11px] text-gray-500">Tiến độ theo từng dự án</span>
                                </div>
                            </label>

                            <label class="relative flex items-start p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="report_type" value="customer" class="mt-0.5 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                <div class="ml-2">
                                    <span class="block text-xs font-bold text-gray-900">Theo Khách hàng</span>
                                    <span class="block text-[11px] text-gray-500">Ticket của từng khách hàng</span>
                                </div>
                            </label>

                            <label class="relative flex items-start p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="report_type" value="work_type" class="mt-0.5 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                <div class="ml-2">
                                    <span class="block text-xs font-bold text-gray-900">Theo Loại Ticket</span>
                                    <span class="block text-[11px] text-gray-500">POC, BOM, Triển khai...</span>
                                </div>
                            </label>

                            <label class="relative flex items-start p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="report_type" value="raw_data" class="mt-0.5 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                <div class="ml-2">
                                    <span class="block text-xs font-bold text-gray-900">Danh Sách Chi Tiết</span>
                                    <span class="block text-[11px] text-gray-500">Dữ liệu thô 21 cột chi tiết</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Filters Section in Modal -->
                <div class="border-t border-gray-150 pt-4 space-y-3">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                        2. Phạm Vi Thời Gian & Bộ Lọc Dữ Liệu
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Từ ngày</label>
                            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Đến ngày</label>
                            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Kỹ sư</label>
                            <select name="assigned_to" class="w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary">
                                <option value="">Tất cả kỹ sư</option>
                                @foreach($engineers as $eng)
                                    <option value="{{ $eng->id }}" {{ (isset($filters['assigned_to']) && $filters['assigned_to'] == $eng->id) ? 'selected' : '' }}>{{ $eng->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Nhân viên Sales</label>
                            <select name="created_by" class="w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary">
                                <option value="">Tất cả Sales</option>
                                @foreach($salesUsers as $sale)
                                    <option value="{{ $sale->id }}" {{ (isset($filters['created_by']) && $filters['created_by'] == $sale->id) ? 'selected' : '' }}>{{ $sale->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Hãng / Vendor</label>
                            <select name="supplier_id" class="w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary">
                                <option value="">Tất cả Vendor</option>
                                @foreach($suppliers as $sup)
                                    <option value="{{ $sup->id }}" {{ (isset($filters['supplier_id']) && $filters['supplier_id'] == $sup->id) ? 'selected' : '' }}>{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Khách hàng</label>
                            <select name="customer_id" class="w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary">
                                <option value="">Tất cả khách hàng</option>
                                @foreach($customers as $cust)
                                    <option value="{{ $cust->id }}" {{ (isset($filters['customer_id']) && $filters['customer_id'] == $cust->id) ? 'selected' : '' }}>{{ $cust->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Dự án</label>
                            <select name="project_id" class="w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary">
                                <option value="">Tất cả dự án</option>
                                @foreach($projects as $proj)
                                    <option value="{{ $proj->id }}" {{ (isset($filters['project_id']) && $filters['project_id'] == $proj->id) ? 'selected' : '' }}>{{ $proj->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                    <button type="button" @click="openExportModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors bg-white">
                        Đóng
                    </button>
                    <button type="submit" @click="setTimeout(() => openExportModal = false, 1000)"
                        class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-xl transition-all shadow-md shadow-green-600/20 flex items-center">
                        <i class="fas fa-file-excel mr-2 text-base"></i> Tải File Báo Cáo Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Category Chart
        const categoryData = {
            labels: {!! json_encode($categoryStats->map(fn($item) => $item->work_type_label)->toArray()) !!},
            datasets: [{
                label: 'Số lượng Ticket',
                data: {!! json_encode($categoryStats->pluck('count')->toArray()) !!},
                backgroundColor: 'rgba(59, 130, 246, 0.75)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1.5,
                borderRadius: 4
            }]
        };
        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: categoryData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // 2. Sales Chart
        const salesData = {
            labels: {!! json_encode($salesStats->map(fn($item) => $item->creator->name ?? 'N/A')->toArray()) !!},
            datasets: [{
                data: {!! json_encode($salesStats->pluck('count')->toArray()) !!},
                backgroundColor: [
                    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6'
                ]
            }]
        };
        new Chart(document.getElementById('salesChart'), {
            type: 'pie',
            data: salesData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        // 3. Vendor Chart
        const vendorData = {
            labels: {!! json_encode($vendorStats->map(fn($item) => $item->supplier->name ?? 'N/A')->toArray()) !!},
            datasets: [{
                label: 'Số lượng Ticket',
                data: {!! json_encode($vendorStats->pluck('count')->toArray()) !!},
                backgroundColor: 'rgba(16, 185, 129, 0.75)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1.5,
                borderRadius: 4
            }]
        };
        new Chart(document.getElementById('vendorChart'), {
            type: 'bar',
            data: vendorData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // 4. Project Chart
        const projectData = {
            labels: {!! json_encode($projectStats->map(fn($item) => $item->project->name ?? 'N/A')->toArray()) !!},
            datasets: [{
                data: {!! json_encode($projectStats->pluck('count')->toArray()) !!},
                backgroundColor: [
                    '#8B5CF6', '#EC4899', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#14B8A6'
                ]
            }]
        };
        new Chart(document.getElementById('projectChart'), {
            type: 'doughnut',
            data: projectData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    });
</script>
@endpush
