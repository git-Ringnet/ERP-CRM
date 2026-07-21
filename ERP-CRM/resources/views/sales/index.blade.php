@extends('layouts.app')

@section('title', 'Đơn hàng bán')
@section('page-title', 'Quản lý Đơn hàng bán')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <!-- Header -->
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 space-y-4">
            <!-- Row 1: Search and Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <!-- Search -->
                <div class="relative flex-1">
                    <form action="{{ route('sales.index') }}" method="GET" class="flex w-full gap-2">
                        <div class="relative flex-1">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo mã đơn, khách hàng..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <div class="relative flex-1 max-w-[250px]">
                            <input type="text" name="note_search" value="{{ request('note_search') }}" placeholder="Tìm theo ghi chú/note..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <i class="fas fa-sticky-note absolute left-3 top-1/2 transform -translate-y-1/2 text-amber-400"></i>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors" title="Tìm kiếm">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="{{ route('sales.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors flex items-center justify-center" title="Làm mới">
                            <i class="fas fa-sync-alt"></i>
                        </a>
                    </form>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 flex-shrink-0">
                    <a href="{{ route('sales.export') }}?{{ http_build_query(request()->query()) }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>
                        Xuất Excel
                    </a>
                    <a href="{{ route('sales.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Tạo đơn hàng
                    </a>
                </div>
            </div>

            <!-- Row 2: Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Date Filter -->
                <div class="flex items-center gap-2 bg-white rounded-lg border border-gray-300 h-10 px-2 shadow-sm focus-within:ring-2 focus-within:ring-primary focus-within:border-primary">
                    <input type="text" id="date_from" name="date_from" value="{{ request('date_from') }}" autocomplete="off"
                        placeholder="Từ ngày"
                        style="border: none !important; box-shadow: none !important;"
                        class="w-24 bg-transparent border-none border-transparent focus:border-transparent focus:ring-0 text-sm datepicker p-0 text-gray-700 placeholder-gray-400">
                    <span class="text-gray-400">-</span>
                    <input type="text" id="date_to" name="date_to" value="{{ request('date_to') }}" autocomplete="off"
                        placeholder="Đến ngày"
                        style="border: none !important; box-shadow: none !important;"
                        class="w-24 bg-transparent border-none border-transparent focus:border-transparent focus:ring-0 text-sm datepicker p-0 text-gray-700 placeholder-gray-400">
                </div>

                <!-- Filter by Customer -->
                <select name="customer_id" id="customer_id" onchange="applyFilters()"
                    class="h-10 border border-gray-300 rounded-lg pl-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-sm bg-white truncate max-w-[200px] appearance-none cursor-pointer"
                    style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23333%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 0.65em auto;">
                    <option value="">Tất cả khách hàng</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>

                <!-- Filter by Status -->
                <select name="status" id="status" onchange="applyFilters()"
                    class="h-10 border border-gray-300 rounded-lg pl-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-sm bg-white appearance-none cursor-pointer"
                    style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23333%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 0.65em auto;">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pnl_pending" {{ request('status') == 'pnl_pending' ? 'selected' : '' }}>Chờ duyệt PL</option>
                    <option value="so_pending" {{ request('status') == 'so_pending' ? 'selected' : '' }}>Chờ duyệt đơn hàng</option>
                    <option value="pnl_need_revision" {{ request('status') == 'pnl_need_revision' ? 'selected' : '' }}>Cần sửa PL</option>
                    <option value="pnl_rejected" {{ request('status') == 'pnl_rejected' ? 'selected' : '' }}>PL bị từ chối</option>
                    <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>Chờ xác nhận TT</option>
                    <option value="pending_export" {{ request('status') == 'pending_export' ? 'selected' : '' }}>Chờ duyệt xuất kho</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt (Tất cả)</option>
                    <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Đang giao</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>

                <!-- Filter by Type -->
                <select name="type" id="type" onchange="applyFilters()"
                    class="h-10 border border-gray-300 rounded-lg pl-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-sm bg-white appearance-none cursor-pointer"
                    style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23333%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 0.65em auto;">
                    <option value="">Loại đơn hàng</option>
                    <option value="retail" {{ request('type') == 'retail' ? 'selected' : '' }}>Bán lẻ</option>
                    <option value="project" {{ request('type') == 'project' ? 'selected' : '' }}>Bán theo dự án</option>
                </select>

                <!-- Filter by Project -->
                @if(isset($projects) && $projects->count() > 0)
                    <select name="project_id" id="project_id" onchange="applyFilters()"
                        class="h-10 border border-gray-300 rounded-lg pl-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 shadow-sm bg-white max-w-[200px] truncate appearance-none cursor-pointer"
                        style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23333%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 0.65em auto;">
                        <option value="">Tất cả dự án</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->code }} - {{ Str::limit($project->name, 20) }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="hidden px-4 py-3 bg-blue-50 border-b border-blue-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-blue-800">
                        Đã chọn <span id="selectedCount" class="font-bold">0</span> đơn hàng
                    </span>
                    <button type="button" onclick="clearSelection()" class="text-sm text-blue-600 hover:underline">
                        Bỏ chọn tất cả
                    </button>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="sendBulkInvoice()"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                        <i class="fas fa-envelope mr-2"></i>
                        Gửi hóa đơn (<span id="sendCount">0</span>)
                    </button>
                </div>
            </div>
        </div>

        <!-- Table - Desktop View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã đơn
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã báo giá</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dự án
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[300px]">Khách
                            hàng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nhân viên
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày tạo / HĐ
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tổng
                            tiền</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Margin
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thanh
                            toán</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng
                            thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao
                            tác</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sales as $sale)
                        @php
                            $rowClass = match(true) {
                                $sale->status === 'pending' && $sale->pl_status === 'rejected' => 'bg-red-50/50 border-l-4 border-l-red-400',
                                $sale->status === 'pending' && $sale->pl_status === 'need_revision' => 'bg-amber-50/50 border-l-4 border-l-amber-400',
                                $sale->status === 'pending' => 'bg-yellow-50/50 border-l-4 border-l-yellow-400',
                                $sale->status === 'approved' => 'bg-blue-50/50 border-l-4 border-l-blue-400',
                                $sale->status === 'shipping' => 'bg-purple-50/50 border-l-4 border-l-purple-400',
                                $sale->status === 'completed' => 'bg-green-50/50 border-l-4 border-l-green-400',
                                $sale->status === 'cancelled' => 'bg-red-50/50 border-l-4 border-l-red-400',
                                default => '',
                            };
                        @endphp
                        <tr class="hover:bg-gray-100 transition-colors sale-row {{ $rowClass }}" data-sale-id="{{ $sale->id }}">
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <input type="checkbox"
                                    class="sale-checkbox w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                                    value="{{ $sale->id }}" onchange="updateSelection()">
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                                {{ ($sales->currentPage() - 1) * $sales->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('sales.show', $sale->id) }}"
                                    class="font-medium text-blue-600 hover:underline">
                                    {{ $sale->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($sale->quotation)
                                    <a href="{{ route('quotations.show', $sale->quotation) }}"
                                        class="font-medium text-blue-600 hover:underline">
                                        {{ $sale->quotation->code }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->type == 'project' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $sale->type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($sale->project)
                                    <a href="{{ route('projects.show', $sale->project_id) }}"
                                        class="text-purple-600 hover:underline text-sm">
                                        {{ $sale->project->code }}
                                    </a>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 min-w-[300px]">
                                <div class="text-sm font-medium text-gray-900">{{ $sale->customer_name }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $sale->user->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $sale->date->format('d/m/Y') }}</div>
                                @if($sale->invoice_date)
                                    <div class="text-xs text-blue-600 mt-1" title="Ngày xuất hóa đơn / Nhận công nợ">
                                        HĐ: {{ \Carbon\Carbon::parse($sale->invoice_date)->format('d/m/Y') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium">
                                @if($sale->currency && !$sale->currency->is_base)
                                    <div class="text-gray-900">
                                        {{ $sale->currency->symbol }} {{ number_format($sale->total_foreign ?? ($sale->total / $sale->exchange_rate), $sale->currency->decimal_places ?? 2) }}
                                    </div>
                                    <div class="text-xs text-gray-500 font-normal mt-0.5">
                                        {{ number_format($sale->total) }} đ
                                    </div>
                                @else
                                    <div class="text-gray-900">{{ number_format($sale->total) }} đ</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right">
                                @if(empty($sale->pl_status))
                                    <div class="text-gray-400 text-center">-</div>
                                @else
                                    @if($sale->currency && !$sale->currency->is_base && $sale->exchange_rate)
                                        <div class="font-medium {{ $sale->margin_color }}">
                                            {{ $sale->margin >= 0 ? '+' : ($sale->margin < 0 ? '-' : '') }}{{ $sale->currency->symbol }} {{ number_format(abs($sale->margin) / $sale->exchange_rate, $sale->currency->decimal_places ?? 2) }}
                                        </div>
                                        <div class="text-xs {{ $sale->margin_color }} opacity-75 mt-0.5">
                                            {{ $sale->margin >= 0 ? '+' : '' }}{{ number_format($sale->margin) }} đ ({{ number_format($sale->margin_percent, 1) }}%)
                                        </div>
                                    @else
                                        <div class="font-medium {{ $sale->margin_color }}">
                                            {{ $sale->margin >= 0 ? '+' : '' }}{{ number_format($sale->margin) }} đ
                                        </div>
                                        <div class="text-xs {{ $sale->margin_color }}">
                                            ({{ number_format($sale->margin_percent, 1) }}%)
                                        </div>
                                    @endif
                                    @if($sale->margin < 0)
                                        <div class="text-xs text-red-600 mt-0.5">
                                            <i class="fas fa-exclamation-triangle"></i> Lỗ
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                {{-- Trạng thái thanh toán --}}
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->payment_status_color }}">
                                    {{ $sale->payment_status_label }}
                                </span>

                                @php
                                    $paidPercent = $sale->total > 0 ? round($sale->paid_amount / $sale->total * 100, 1) : 0;
                                    $payments = $sale->payment_history ?? collect();
                                @endphp

                                {{-- Progress bar --}}
                                @if($sale->total > 0 && $sale->payment_status !== 'unpaid')
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1.5">
                                    <div class="h-1.5 rounded-full {{ $paidPercent >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ min($paidPercent, 100) }}%"></div>
                                </div>
                                <div class="text-[10px] text-gray-500 mt-0.5">{{ $paidPercent }}% đã thanh toán</div>
                                @endif

                                {{-- Danh sách % thanh toán --}}
                                @if($payments->count() > 0)
                                <div class="mt-1 flex flex-col items-center gap-1 max-w-[200px] mx-auto">
                                    @foreach($payments as $pm)
                                        @php
                                            $pmNote = $pm->note ?? '';
                                            $pmPercent = $sale->total > 0 ? round($pm->amount / $sale->total * 100, 1) : 0;
                                            
                                            // Trích xuất label đợt thanh toán từ note
                                            $pmLabel = '';
                                            if (preg_match('/"([^"]+)"/', $pmNote, $m)) {
                                                $pmLabel = $m[1];
                                            } else {
                                                $pmLabel = preg_replace('/^Xác nhận thanh toán đợt\s*/u', '', $pmNote);
                                            }
                                            
                                            // Loại bỏ các đoạn trùng lặp phần trăm hoặc từ thừa
                                            $pmLabel = preg_replace('/\s*\([\d.]*%\)/', '', $pmLabel);
                                            $pmLabel = preg_replace('/\s*[\d.]*%\s*$/', '', $pmLabel);
                                            $pmLabel = trim($pmLabel, '" ');
                                            
                                            $pmLabel = $pmLabel ?: 'Thanh toán';
                                        @endphp
                                        <div class="text-[10px] px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-medium border border-blue-100 w-full text-center truncate" title="{{ $pmNote }}">
                                            {{ $pmLabel }} ({{ $pmPercent }}%)
                                        </div>
                                    @endforeach
                                </div>
                                @endif

                                {{-- Hạn thanh toán tính từ ngày xuất HĐ --}}
                                @if($sale->payment_status !== 'paid')
                                    @if($sale->invoice_date)
                                        @php
                                            $debtDays = $sale->customer->debt_days ?? 0;
                                            $dueDate = \Carbon\Carbon::parse($sale->invoice_date)->addDays($debtDays);
                                            $daysLeft = now()->diffInDays($dueDate, false);
                                        @endphp
                                        @if($daysLeft < 0)
                                            <div class="text-[10px] font-bold mt-1 px-1.5 py-0.5 rounded bg-red-100 text-red-700 inline-block">
                                                <i class="fas fa-exclamation-circle"></i> Quá hạn {{ abs((int)$daysLeft) }}d
                                            </div>
                                        @elseif($daysLeft <= 3)
                                            <div class="text-[10px] font-bold mt-1 px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 inline-block">
                                                <i class="fas fa-clock"></i> {{ $daysLeft == 0 ? 'Tới hạn' : 'Còn ' . (int)$daysLeft . 'd' }}
                                            </div>
                                        @else
                                            <div class="text-[10px] text-gray-400 mt-1">
                                                <i class="far fa-calendar-alt"></i> {{ $dueDate->format('d/m/Y') }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-[10px] text-gray-400 italic mt-1">
                                            Chờ xuất HĐ
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                {{-- Delivery Progress --}}
                                @php
                                    $dashStatus = $sale->dashboard_status;
                                    $step = match($dashStatus) {
                                        'completed' => 3,
                                        'invoiced', 'shipping' => 2,
                                        'approved', 'waiting_order', 'ordered', 'in_transit', 'received', 'ready_in_stock', 'invoicing', 'pending_export_approval' => 1,
                                        'pending', 'pnl_pending', 'pnl_need_revision', 'pnl_rejected' => 0,
                                        'cancelled' => -1,
                                        default => ($sale->status === 'completed' ? 3 : 1),
                                    };
                                    if ($sale->status === 'completed') {
                                        $step = 3;
                                    }
                                    $isCancelled = $sale->status === 'cancelled' || $dashStatus === 'cancelled';
                                @endphp
                                @if($isCancelled)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>Đã hủy
                                    </span>
                                @else
                                    <div class="flex items-center justify-center gap-0.5">
                                        {{-- Step 1: Duyệt / Sẵn sàng --}}
                                        <div class="flex flex-col items-center" title="Đã duyệt">
                                            <div class="w-3 h-3 rounded-full {{ $step >= 1 ? ($step >= 3 ? 'bg-green-500' : 'bg-blue-500') : 'bg-gray-300' }}"></div>
                                        </div>
                                        <div class="w-3 h-0.5 {{ $step >= 2 ? ($step >= 3 ? 'bg-green-500' : 'bg-orange-400') : 'bg-gray-200' }}"></div>
                                        {{-- Step 2: Giao hàng --}}
                                        <div class="flex flex-col items-center" title="Giao hàng">
                                            <div class="w-3 h-3 rounded-full {{ $step >= 2 ? ($step >= 3 ? 'bg-green-500' : 'bg-orange-400') : 'bg-gray-300' }} {{ $step === 2 ? 'animate-pulse ring-2 ring-orange-200' : '' }}"></div>
                                        </div>
                                        <div class="w-3 h-0.5 {{ $step >= 3 ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                                        {{-- Step 3: Hoàn thành --}}
                                        <div class="flex flex-col items-center" title="Hoàn thành">
                                            <div class="w-3 h-3 rounded-full {{ $step >= 3 ? 'bg-green-500' : 'bg-gray-300' }}"></div>
                                        </div>
                                    </div>
                                    <div class="text-[10px] mt-1 font-bold {{ $sale->dashboard_status_color }} px-2 py-0.5 rounded-full inline-block">
                                        {{ $sale->dashboard_status_label }}
                                    </div>
                                    @if(count($sale->pending_action_badges) > 0)
                                        <div class="mt-1 flex flex-col gap-1 items-center">
                                            @foreach($sale->pending_action_badges as $badge)
                                                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full {{ $badge['color'] }} inline-flex items-center gap-1 shadow-xs">
                                                    <i class="{{ $badge['icon'] }}"></i>{{ $badge['label'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('sales.show', $sale->id) }}"
                                        class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors"
                                        title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('sales.edit', $sale->id) }}"
                                        class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors"
                                        title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($sale->status === 'pending')
                                    <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" 
                                            onclick="confirmDelete(this.form, 'đơn hàng {{ $sale->code }}')"
                                            class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200"
                                            title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Không có dữ liệu đơn hàng</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Card View - Mobile -->
        <div class="md:hidden divide-y divide-gray-200">
            @forelse($sales as $sale)
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">
                                <a href="{{ route('sales.show', $sale->id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $sale->code }}
                                </a>
                                @if($sale->quotation)
                                    <span class="mx-1 text-gray-300">|</span>
                                    <a href="{{ route('quotations.show', $sale->quotation) }}"
                                        class="font-medium text-blue-600 text-xs hover:underline">
                                        {{ $sale->quotation->code }}
                                    </a>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">{{ $sale->customer_name }}</div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                                {{ $sale->status_label }}
                            </span>
                            @foreach($sale->pending_action_badges as $badge)
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $badge['color'] }} inline-flex items-center gap-1">
                                    <i class="{{ $badge['icon'] }}"></i>{{ $badge['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <div class="space-y-1 text-sm text-gray-600 mb-3">
                        <div><i class="fas fa-tag w-4"></i> {{ $sale->type_label }}</div>
                        <div><i class="fas fa-calendar w-4"></i> {{ $sale->date->format('d/m/Y') }}</div>
                        @if($sale->currency && !$sale->currency->is_base)
                            <div class="font-medium text-gray-900"><i class="fas fa-money-bill w-4 text-gray-500"></i> {{ $sale->currency->symbol }} {{ number_format($sale->total_foreign ?? ($sale->total / $sale->exchange_rate), $sale->currency->decimal_places ?? 2) }}</div>
                            <div class="text-xs text-gray-500 ml-5">{{ number_format($sale->total) }} đ</div>
                        @else
                            <div><i class="fas fa-money-bill w-4"></i> {{ number_format($sale->total) }} đ</div>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('sales.show', $sale->id) }}"
                            class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                            <i class="fas fa-eye mr-1"></i>Xem
                        </a>
                        <a href="{{ route('sales.edit', $sale->id) }}"
                            class="flex-1 text-center px-3 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm">
                            <i class="fas fa-edit mr-1"></i>Sửa
                        </a>
                        @if($sale->status === 'pending')
                        <form action="{{ route('sales.destroy', $sale) }}" method="POST"
                            class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="button" 
                                onclick="confirmDelete(this.form, 'đơn hàng {{ $sale->code }}')"
                                class="w-full px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm">
                                <i class="fas fa-trash mr-1"></i>Xóa
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>Không có dữ liệu đơn hàng</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($sales->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $sales->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    <!-- Hidden form for bulk email -->
    <form id="bulkEmailForm" action="{{ route('sales.bulkEmail') }}" method="POST" class="hidden">
        @csrf
        <div id="bulkEmailInputs"></div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dateConfig = {
                dateFormat: "d/m/Y",
                locale: "vn",
                allowInput: true,
                onChange: function (selectedDates, dateStr, instance) {
                    applyFilters();
                }
            };
            flatpickr("#date_from", dateConfig);
            flatpickr("#date_to", dateConfig);
        });

        function applyFilters() {
            const params = new URLSearchParams(window.location.search);

            // Update/Set params from inputs
            const fields = ['status', 'type', 'project_id', 'customer_id', 'date_from', 'date_to'];

            fields.forEach(field => {
                const element = document.getElementById(field);
                if (element && element.value) {
                    params.set(field, element.value);
                } else if (element) {
                    params.delete(field); // Remove param if value is empty
                }
            });

            // Preserve search from form if needed
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput && searchInput.value) {
                params.set('search', searchInput.value);
            } else if (searchInput) {
                params.delete('search');
            }

            // Redirect
            window.location.href = `{{ route('sales.index') }}?${params.toString()}`;
        }

        let selectedSales = new Set();

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.sale-checkbox');

            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
                if (selectAll.checked) {
                    selectedSales.add(cb.value);
                } else {
                    selectedSales.delete(cb.value);
                }
            });

            updateBulkActionsBar();
        }

        function updateSelection() {
            const checkboxes = document.querySelectorAll('.sale-checkbox');
            selectedSales.clear();

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    selectedSales.add(cb.value);
                }
            });

            // Update select all checkbox
            const selectAll = document.getElementById('selectAll');
            const allChecked = [...checkboxes].every(cb => cb.checked);
            const someChecked = [...checkboxes].some(cb => cb.checked);
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;

            updateBulkActionsBar();
        }

        function updateBulkActionsBar() {
            const bar = document.getElementById('bulkActionsBar');
            const count = selectedSales.size;

            document.getElementById('selectedCount').textContent = count;
            document.getElementById('sendCount').textContent = count;

            if (count > 0) {
                bar.classList.remove('hidden');
            } else {
                bar.classList.add('hidden');
            }
        }

        function clearSelection() {
            selectedSales.clear();
            document.querySelectorAll('.sale-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            document.getElementById('selectAll').indeterminate = false;
            updateBulkActionsBar();
        }

        function sendBulkInvoice() {
            if (selectedSales.size === 0) {
                Swal.fire({
                    title: 'Thông báo',
                    text: 'Vui lòng chọn ít nhất 1 đơn hàng',
                    icon: 'info'
                });
                return;
            }

            Swal.fire({
                title: 'Gửi hóa đơn hàng loạt?',
                text: `Bạn có chắc muốn gửi hóa đơn cho ${selectedSales.size} đơn hàng đã chọn?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Đồng ý, gửi ngay!',
                cancelButtonText: 'Hủy bỏ',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const inputsContainer = document.getElementById('bulkEmailInputs');
                    inputsContainer.innerHTML = '';

                    selectedSales.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'sale_ids[]';
                        input.value = id;
                        inputsContainer.appendChild(input);
                    });

                    document.getElementById('bulkEmailForm').submit();
                }
            });
        }
    </script>
@endpush