@extends('layouts.app')

@section('title', 'Quản lý công nợ khách hàng')
@section('page-title', 'Quản lý công nợ khách hàng')

@section('content')
    <div class="space-y-6">
        <!-- Dashboard Charts & Summary Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Summary Cards -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">KH có công nợ</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ number_format($summary['total_customers_with_debt']) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-money-bill-wave text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Tổng công nợ</p>
                            <p class="text-2xl font-bold text-red-600">{{ number_format($summary['total_debt'] ?? 0) }}đ</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <i class="fas fa-exclamation-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Nợ quá hạn</p>
                            <p class="text-2xl font-bold text-orange-600">
                                {{ number_format($summary['total_overdue'] ?? 0) }}đ
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Trong hạn (0-30 ngày)</p>
                            <p class="text-2xl font-bold text-green-600">
                                {{ number_format($summary['current'] ?? 0) }}đ
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-5">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-sky-100 text-sky-600">
                            <i class="fas fa-hand-holding-usd text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500" title="Khách đã chuyển tiền cho đơn chưa xuất hóa đơn - chưa tính vào công nợ">Ứng trước (chưa XHĐ)</p>
                            <p class="text-2xl font-bold text-sky-600">
                                {{ number_format($summary['total_advance'] ?? 0) }}đ
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aging Chart -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow p-5 flex flex-col justify-center items-center">
                <h3 class="text-lg font-bold text-gray-800 mb-4 w-full text-left">Phân bổ tuổi nợ</h3>
                <div class="w-full max-w-md">
                    <canvas id="agingChart"></canvas>
                </div>
            </div>
        </div>


        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4">
            <form method="GET" class="flex flex-wrap gap-4 items-end" x-data="{ period: @js($period) }">
                <div class="w-44">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Giai đoạn báo cáo</label>
                    <select name="period" x-model="period"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                        <option value="all">Tất cả</option>
                        <option value="this_month">Tháng này</option>
                        <option value="last_month">Tháng trước</option>
                        <option value="this_quarter">Quý này</option>
                        <option value="last_quarter">Quý trước</option>
                        <option value="this_year">Năm nay</option>
                        <option value="custom">Tùy chọn...</option>
                    </select>
                </div>
                <div class="w-40" x-show="period === 'custom'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Từ ngày (XHĐ)</label>
                    <input type="date" name="date_from" value="{{ $period === 'custom' ? $dateFrom : '' }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                </div>
                <div class="w-40" x-show="period === 'custom'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Đến ngày (XHĐ)</label>
                    <input type="date" name="date_to" value="{{ $period === 'custom' ? $dateTo : '' }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Tên, mã KH, SĐT..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div class="w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái nợ</label>
                    <select name="debt_status"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary">
                        <option value="">Tất cả</option>
                        <option value="has_debt" {{ $debtStatus === 'has_debt' ? 'selected' : '' }}>Có công nợ</option>
                        <option value="no_debt" {{ $debtStatus === 'no_debt' ? 'selected' : '' }}>Không có nợ</option>
                        <option value="over_limit" {{ $debtStatus === 'over_limit' ? 'selected' : '' }}>Vượt hạn mức</option>
                        <option value="overdue" {{ $debtStatus === 'overdue' ? 'selected' : '' }}>Quá hạn thanh toán</option>
                        <option value="due_soon" {{ $debtStatus === 'due_soon' ? 'selected' : '' }}>Sắp đến hạn (7 ngày)
                        </option>
                        <option value="advance" {{ $debtStatus === 'advance' ? 'selected' : '' }}>Có ứng trước (chưa XHĐ)</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
                        <i class="fas fa-search mr-1"></i> Lọc
                    </button>
                    <a href="{{ route('customer-debts.index') }}"
                        class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-redo mr-1"></i> Reset
                    </a>
                </div>
            </form>
            <p class="text-xs text-gray-500 mt-3">
                <i class="fas fa-info-circle mr-1"></i>
                Công nợ chỉ gồm đơn đã duyệt <b>và đã xuất hóa đơn</b>; giai đoạn báo cáo lọc theo ngày XHĐ.
                @if($dateFrom || $dateTo)
                    Đang xem: <b>{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '...' }} - {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '...' }}</b>.
                @endif
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center hidden">
            <div class="flex gap-2">
                <a href="{{ route('customer-debts.export') }}"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    <i class="fas fa-file-excel mr-1"></i> Xuất Excel
                </a>
            </div>
        </div>

        <!-- Customer Debt Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã KH</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên khách hàng</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Điện thoại</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng mua</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Đã TT</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Công nợ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase" title="Khách đã chuyển tiền cho đơn chưa XHĐ - không tính vào công nợ">Ứng trước</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Hạn mức</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">ĐH chưa TT</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" x-data="{ openId: null }">
                        @forelse($customers as $customer)
                            @php
                                $debtOrders = $ordersByCustomer[$customer->id] ?? collect();
                                $advanceOrders = $advanceOrdersByCustomer[$customer->id] ?? collect();
                                $hasOrders = $debtOrders->isNotEmpty() || $advanceOrders->isNotEmpty();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                                    @if($hasOrders)
                                        <button type="button" class="text-gray-400 hover:text-primary mr-1 w-4"
                                            @click="openId = openId === {{ $customer->id }} ? null : {{ $customer->id }}"
                                            title="Xem danh sách đơn công nợ">
                                            <i class="fas" :class="openId === {{ $customer->id }} ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                        </button>
                                    @endif
                                    {{ $customer->tax_code }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $customer->phone }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900">
                                    {{ number_format($customer->receivable_sales) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-green-600">
                                    {{ number_format($customer->receivable_paid) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-right font-medium {{ $customer->receivable_debt > 0 ? 'text-red-600' : 'text-gray-600' }}">
                                    {{ number_format($customer->receivable_debt) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right {{ $customer->total_advance > 0 ? 'text-sky-600 font-medium' : 'text-gray-400' }}">
                                    {{ number_format($customer->total_advance) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-right {{ $customer->debt_limit > 0 && $customer->receivable_debt > $customer->debt_limit ? 'text-orange-600 font-medium' : 'text-gray-600' }}">
                                    {{ $customer->debt_limit ? number_format($customer->debt_limit) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    @if($customer->unpaid_orders > 0)
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ $customer->unpaid_orders }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <a href="{{ route('customer-debts.show', $customer) }}"
                                        class="text-primary hover:text-primary/80" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @if($hasOrders)
                                {{-- BS 16: danh sách đơn của khách hàng trong kỳ (tổng khớp các cột phía trên) --}}
                                <tr class="excel-detail-row bg-gray-50/70" x-show="openId === {{ $customer->id }}" x-cloak>
                                    <td colspan="10" class="px-6 py-3">
                                        <table class="w-full text-xs border border-gray-200 bg-white rounded" data-no-excel-filter>
                                            <thead class="bg-gray-100 text-gray-600 uppercase">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">Mã đơn</th>
                                                    <th class="px-3 py-2 text-left">Nhân viên</th>
                                                    <th class="px-3 py-2 text-left">Ngày XHĐ</th>
                                                    <th class="px-3 py-2 text-left">Hạn thanh toán</th>
                                                    <th class="px-3 py-2 text-right">Tổng tiền</th>
                                                    <th class="px-3 py-2 text-right">Đã TT</th>
                                                    <th class="px-3 py-2 text-right">Còn nợ</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach($debtOrders as $order)
                                                    @php
                                                        $recognizedAt = \Carbon\Carbon::parse($order->invoice_date ?? $order->date);
                                                        $dueAt = $recognizedAt->copy()->addDays($customer->debt_days ?? 30);
                                                    @endphp
                                                    <tr>
                                                        <td class="px-3 py-1.5"><a href="{{ route('sales.show', $order->id) }}" class="text-primary hover:underline font-medium">{{ $order->code }}</a></td>
                                                        <td class="px-3 py-1.5 text-gray-600">{{ $order->user->name ?? '-' }}</td>
                                                        <td class="px-3 py-1.5">{{ $recognizedAt->format('d/m/Y') }}</td>
                                                        <td class="px-3 py-1.5 {{ $order->debt_amount > 0 && now()->gt($dueAt) ? 'text-red-600 font-semibold' : '' }}">{{ $order->debt_amount > 0 ? $dueAt->format('d/m/Y') : 'Đã thanh toán đủ' }}</td>
                                                        <td class="px-3 py-1.5 text-right">{{ number_format($order->total) }}</td>
                                                        <td class="px-3 py-1.5 text-right text-green-600">{{ number_format($order->paid_amount) }}</td>
                                                        <td class="px-3 py-1.5 text-right text-red-600 font-semibold">{{ number_format($order->debt_amount) }}</td>
                                                    </tr>
                                                @endforeach
                                                @if($debtOrders->isNotEmpty())
                                                    <tr class="bg-gray-50 font-semibold">
                                                        <td colspan="4" class="px-3 py-1.5 text-right">Cộng ({{ $debtOrders->count() }} đơn):</td>
                                                        <td class="px-3 py-1.5 text-right">{{ number_format($debtOrders->sum('total')) }}</td>
                                                        <td class="px-3 py-1.5 text-right text-green-600">{{ number_format($debtOrders->sum('paid_amount')) }}</td>
                                                        <td class="px-3 py-1.5 text-right text-red-600">{{ number_format($debtOrders->sum('debt_amount')) }}</td>
                                                    </tr>
                                                @endif
                                                @foreach($advanceOrders as $order)
                                                    <tr class="bg-sky-50/60">
                                                        <td class="px-3 py-1.5"><a href="{{ route('sales.show', $order->id) }}" class="text-primary hover:underline font-medium">{{ $order->code }}</a></td>
                                                        <td class="px-3 py-1.5 text-gray-600">{{ $order->user->name ?? '-' }}</td>
                                                        <td class="px-3 py-1.5 text-sky-700" colspan="2">Ứng trước - chưa XHĐ (đơn ngày {{ $order->date?->format('d/m/Y') }})</td>
                                                        <td class="px-3 py-1.5 text-right">{{ number_format($order->total) }}</td>
                                                        <td class="px-3 py-1.5 text-right text-sky-700">{{ number_format($order->paid_amount) }}</td>
                                                        <td class="px-3 py-1.5 text-right text-gray-400">-</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                    Không có dữ liệu
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $customers->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('agingChart').getContext('2d');
        
        const data = {
            labels: [
                'Trong hạn (0-30 ngày)',
                'Quá hạn (31-60 ngày)',
                'Quá hạn (61-90 ngày)',
                'Quá hạn (>90 ngày)'
            ],
            datasets: [{
                data: [
                    {{ $summary['current'] ?? 0 }},
                    {{ $summary['days_31_60'] ?? 0 }},
                    {{ $summary['days_61_90'] ?? 0 }},
                    {{ $summary['over_90'] ?? 0 }}
                ],
                backgroundColor: [
                    '#10B981', // green-500
                    '#F59E0B', // amber-500
                    '#F97316', // orange-500
                    '#EF4444'  // red-500
                ],
                hoverOffset: 4
            }]
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('en-US').format(context.parsed) + ' VNĐ';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush