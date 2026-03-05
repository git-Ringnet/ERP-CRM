@extends('layouts.app')

@section('title', 'Báo cáo P&L Chi tiết')

@section('header')
<div class="flex justify-between items-center">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Báo cáo P&L Chi tiết') }} (Profit & Loss Statement)
    </h2>
    <div class="flex items-center space-x-2">
        <form method="GET" action="{{ route('reports.detailed-pnl') }}" class="flex items-center space-x-2">
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
            <span class="text-gray-500">đến</span>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-sm">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                {{ __('Lọc') }}
            </button>
        </form>
        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-print mr-2"></i> {{ __('In') }}
        </button>
    </div>
</div>
@endsection

@section('content')
<style>
    .report-font { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .font-bold-misa { font-weight: 700 !important; }
    .font-medium-misa { font-weight: 500 !important; }
    .font-normal-misa { font-weight: 400 !important; }
</style>
<div class="py-12 report-font text-gray-800">
    <div class="">
        <div class="bg-white shadow-xl rounded-2xl p-6 overflow-x-auto border border-gray-100">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold-misa text-gray-900 uppercase tracking-tight">Báo cáo Kết quả Kinh doanh Chi tiết</h1>
                <p class="text-gray-500 mt-2 text-lg font-normal-misa">
                    Từ ngày <span class="font-bold-misa text-gray-800">{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</span> 
                    đến ngày <span class="font-bold-misa text-gray-800">{{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</span>
                </p>
            </div>

            <table class="min-w-full border-collapse border border-gray-200 text-sm">
                <thead class="bg-gray-50 text-gray-700 font-bold-misa uppercase text-[11px] tracking-wider">
                    <tr>
                        <th rowspan="2" class="border border-gray-200 px-3 py-2 text-center bg-gray-100">Supplier</th>
                        <th rowspan="2" class="border border-gray-200 px-3 py-2 text-left bg-gray-100 min-w-[200px]">Sản phẩm / Hạng mục</th>
                        <th rowspan="2" class="border border-gray-200 px-2 py-2 text-center bg-gray-100">SL</th>
                        <th colspan="2" class="border border-gray-200 px-2 py-2 text-center bg-gray-100">Đơn giá</th>
                        <th rowspan="2" class="border border-gray-200 px-3 py-2 text-right bg-blue-50 text-blue-900">Doanh thu (VND)</th>
                        <th rowspan="2" class="border border-gray-200 px-3 py-2 text-right bg-orange-50 text-orange-900">Giá vốn (VND)</th>
                        <th rowspan="2" class="border border-gray-200 px-3 py-2 text-right bg-emerald-50 text-emerald-900">Lợi nhuận gộp</th>
                        <th colspan="5" class="border border-gray-200 px-2 py-2 text-center bg-gray-50">Chi phí phát sinh (VND)</th>
                        <th rowspan="2" class="border border-gray-200 px-3 py-2 text-right font-bold-misa bg-yellow-100 text-gray-900">LN ròng</th>
                        <th rowspan="2" class="border border-gray-200 px-2 py-2 text-center bg-gray-100">% LN</th>
                    </tr>
                    <tr class="bg-gray-50 text-[9px] font-medium-misa">
                        <th class="border border-gray-200 px-2 py-1 text-center italic text-gray-400 font-normal-misa">USD</th>
                        <th class="border border-gray-200 px-2 py-1 text-center font-bold-misa">VND</th>
                        <th class="border border-gray-200 px-2 py-1 text-center">Support</th>
                        <th class="border border-gray-200 px-2 py-1 text-center">Tài chính</th>
                        <th class="border border-gray-200 px-2 py-1 text-center">Quản lý</th>
                        <th class="border border-gray-200 px-2 py-1 text-center">Triển khai</th>
                        <th class="border border-gray-200 px-2 py-1 text-center">Thuế</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-700 font-normal-misa">
                    @php
                        $totalRevenue = 0;
                        $totalCost = 0;
                        $totalGrossProfit = 0;
                        $totalNetProfit = 0;
                        $totalExp = ['support' => 0, 'finance' => 0, 'admin' => 0, 'imp' => 0, 'tax' => 0];
                    @endphp
                    @forelse($rows as $row)
                        @php
                            $totalRevenue += $row['revenue'];
                            $totalCost += $row['cost'];
                            $totalGrossProfit += $row['gross_profit'];
                            
                            $rowExpTotal = array_sum($row['expenses']);
                            $netProfit = $row['gross_profit'] - $rowExpTotal;
                            $totalNetProfit += $netProfit;
                            
                            $totalExp['support'] += ($row['expenses']['support'] ?? 0);
                            $totalExp['finance'] += ($row['expenses']['finance'] ?? 0);
                            $totalExp['admin'] += ($row['expenses']['marketing'] ?? 0);
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="border border-gray-200 px-3 py-2 text-center italic text-gray-400 font-normal-misa">{{ $row['supplier'] }}</td>
                            <td class="border border-gray-200 px-3 py-2 font-medium-misa text-gray-900">{{ $row['product_name'] }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-center">{{ number_format($row['qty'], 0) }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-right italic text-gray-300">-</td>
                            <td class="border border-gray-200 px-2 py-2 text-right">{{ number_format($row['unit_price'], 0, ',', '.') }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-right font-bold-misa text-blue-700">{{ number_format($row['revenue'], 0, ',', '.') }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-right text-orange-700">{{ number_format($row['cost'], 0, ',', '.') }}</td>
                            <td class="border border-gray-200 px-3 py-2 text-right bg-emerald-50/50 font-bold-misa text-emerald-800">{{ number_format($row['gross_profit'], 0, ',', '.') }}</td>
                            
                            <!-- Expenses -->
                            <td class="border border-gray-200 px-2 py-2 text-right text-gray-500">{{ number_format($row['expenses']['support'] ?? 0, 0, ',', '.') }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-right text-gray-500">{{ number_format($row['expenses']['finance'] ?? 0, 0, ',', '.') }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-right text-gray-500">{{ number_format($row['expenses']['marketing'] ?? 0, 0, ',', '.') }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-right text-gray-300">0</td>
                            <td class="border border-gray-200 px-2 py-2 text-right text-gray-300">0</td>
                            
                            <td class="border border-gray-200 px-3 py-2 text-right font-bold-misa bg-yellow-50 text-indigo-900">{{ number_format($netProfit, 0, ',', '.') }}</td>
                            <td class="border border-gray-200 px-2 py-2 text-center font-bold-misa {{ $netProfit < 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                {{ $row['revenue'] > 0 ? round(($netProfit / $row['revenue']) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="px-4 py-16 text-center text-gray-400 italic">Không có dữ liệu kinh doanh trong khoảng thời gian này.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-100 font-bold-misa text-gray-900">
                    <tr class="border-t-2 border-gray-300 text-sm">
                        <td colspan="3" class="border border-gray-200 px-4 py-3 text-right uppercase tracking-wider">Tổng cộng hệ thống</td>
                        <td class="border border-gray-200 px-2 py-1"></td>
                        <td class="border border-gray-200 px-2 py-1"></td>
                        <td class="border border-gray-200 px-3 py-3 text-right text-blue-800">{{ number_format($totalRevenue, 0, ',', '.') }}</td>
                        <td class="border border-gray-200 px-3 py-3 text-right text-orange-800">{{ number_format($totalCost, 0, ',', '.') }}</td>
                        <td class="border border-gray-200 px-3 py-3 text-right bg-emerald-100 text-emerald-900">{{ number_format($totalGrossProfit, 0, ',', '.') }}</td>
                        
                        <td class="border border-gray-200 px-2 py-1 text-right text-gray-600 font-medium-misa">{{ number_format($totalExp['support'], 0, ',', '.') }}</td>
                        <td class="border border-gray-200 px-2 py-1 text-right text-gray-600 font-medium-misa">{{ number_format($totalExp['finance'], 0, ',', '.') }}</td>
                        <td class="border border-gray-200 px-2 py-1 text-right text-gray-600 font-medium-misa">{{ number_format($totalExp['admin'], 0, ',', '.') }}</td>
                        <td class="border border-gray-200 px-2 py-1 text-right text-gray-400 font-normal-misa">0</td>
                        <td class="border border-gray-200 px-2 py-1 text-right text-gray-400 font-normal-misa">0</td>
                        
                        <td class="border border-gray-200 px-3 py-3 text-right bg-yellow-200 text-gray-900 text-lg font-bold-misa">{{ number_format($totalNetProfit, 0, ',', '.') }}</td>
                        <td class="border border-gray-200 px-2 py-3 text-center text-lg">{{ $totalRevenue > 0 ? round(($totalNetProfit / $totalRevenue) * 100, 1) : 0 }}%</td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="mt-10 flex gap-6 font-normal-misa">
                <div class="flex-1 bg-blue-50/50 p-5 rounded-xl border border-blue-100">
                    <h4 class="font-bold-misa text-blue-900 mb-3 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i> Diễn giải nghiệp vụ:
                    </h4>
                    <ul class="text-[11px] text-blue-800 space-y-2 leading-relaxed font-medium-misa">
                        <li>• <strong>Lợi nhuận gộp (Gross Profit):</strong> Doanh thu thuần trừ đi giá vốn hàng bán trực tiếp.</li>
                        <li>• <strong>Lợi nhuận ròng (Net Profit):</strong> Lợi nhuận gộp trừ đi toàn bộ chi phí vận hành, quản lý và thuế.</li>
                    </ul>
                </div>
                <div class="w-1/3 flex flex-col justify-end items-center italic text-gray-400 text-xs">
                    <p>Ngày xuất: {{ now()->format('d/m/Y H:i') }} | Người lập: {{ Auth::user()->name }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
