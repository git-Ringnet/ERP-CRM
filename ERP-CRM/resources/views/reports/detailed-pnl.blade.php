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
    .report-font { font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .table-pnl th { 
        background-color: #fef08a; /* Yellow-200 */
        border: 1px solid #eab308;
        padding: 6px 4px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        vertical-align: middle;
    }
    .table-pnl td {
        border: 1px solid #d1d5db; /* Stronger gray */
        padding: 8px 6px;
        font-size: 14px;
    }
    .bg-blue-light { background-color: #dbeafe; }
    .bg-orange-light { background-color: #ffedd5; }
    .bg-gray-light { background-color: #f3f4f6; }
    .text-header-small { font-size: 10px; font-weight: normal; font-style: italic; color: #ef4444; }
</style>

<div class="py-6 report-font">
    <div class="max-w-[100%] mx-auto sm:px-4">
        <div class="bg-white shadow-xl rounded-lg p-4 overflow-x-auto border border-gray-200">
            <div class="mb-4">
                <h1 class="text-xl font-bold text-gray-900">BẢNG PHÂN TÍCH HIỆU QUẢ KINH DOANH (P&L - Profit and Loss Statement)</h1>
            </div>

            <table class="w-full border-collapse table-pnl min-w-[2000px]">
                <thead>
                    <tr>
                        <th rowspan="2" class="w-24">P/N Supplier</th>
                        <th rowspan="2" class="w-64">Hàng hóa/Dịch vụ</th>
                        <th rowspan="2" class="w-12">SL</th>
                        <th rowspan="2" class="w-24">PriceList USD</th>
                        <th rowspan="2" class="w-16">Tỷ lệ discount</th>
                        <th rowspan="2" class="w-20">Tỷ lệ chi phí nhập hàng</th>
                        <th rowspan="2" class="w-24">Giá kho tạm tính (USD)</th>
                        <th rowspan="2" class="w-20">Tỷ giá</th>
                        <th colspan="2" class="bg-yellow-100">Giá đầu vào (chưa VAT)</th>
                        <th colspan="2" class="bg-blue-100">Giá bán (chưa VAT)</th>
                        <th colspan="2" class="bg-green-100">Lợi nhuận gộp</th>
                        <th colspan="8" class="bg-orange-100">Chi phí</th>
                        <th rowspan="2" class="bg-gray-100">Tổng chi phí VND</th>
                        <th colspan="2" class="bg-indigo-100">Lợi nhuận sau chi phí</th>
                    </tr>
                    <tr>
                        <th class="bg-yellow-50">Giá VND</th>
                        <th class="bg-yellow-50">Thành tiền</th>
                        <th class="bg-blue-50">Đơn Giá bán</th>
                        <th class="bg-blue-50">Thành tiền</th>
                        <th class="bg-green-50">VND</th>
                        <th class="bg-green-50">%</th>
                        
                        <th class="bg-orange-50 px-1">Chi phí Tài chính<br><span class="text-header-small">1%</span></th>
                        <th class="bg-orange-50 px-1">Lãi vay phát sinh do nợ quá hạn</th>
                        <th class="bg-orange-50 px-1">Chi phí Quản lý, Back Office & kỹ thuật<br><span class="text-header-small">1%</span></th>
                        <th class="bg-orange-50 px-1">24x7 Support cost<br><span class="text-header-small">0.5%</span></th>
                        <th class="bg-orange-50 px-1">Other Support<br><span class="text-header-small">1%</span></th>
                        <th class="bg-orange-50 px-1">Technical support/POC 30%</th>
                        <th class="bg-orange-50 px-1">Chi phí triển khai...</th>
                        <th class="bg-orange-50 px-1">Thuế nhà thầu</th>
                        
                        <th class="bg-indigo-50 border-l border-gray-400">VND</th>
                        <th class="bg-indigo-50 border-l border-gray-400">%</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totals = [
                            'revenue' => 0, 'cost' => 0, 'gross_profit' => 0, 'total_expenses' => 0, 'net_profit' => 0,
                            'finance' => 0, 'mgmt' => 0, 'support_247' => 0, 'other_support' => 0, 'poc' => 0, 'imp' => 0, 'tax' => 0
                        ];
                    @endphp
                    @forelse($rows as $row)
                        @php
                            $rowExpTotal = array_sum($row['expenses']);
                            $totals['revenue'] += $row['revenue'];
                            $totals['cost'] += $row['cost'];
                            $totals['gross_profit'] += $row['gross_profit'];
                            $totals['total_expenses'] += $rowExpTotal;
                            $totals['net_profit'] += $row['net_profit'];
                            
                            $totals['finance'] += $row['expenses']['finance'];
                            $totals['mgmt'] += $row['expenses']['management'];
                            $totals['support_247'] += $row['expenses']['support_247'];
                            $totals['other_support'] += $row['expenses']['other_support'];
                            $totals['poc'] += $row['expenses']['technical_poc'];
                            $totals['imp'] += $row['expenses']['implementation'];
                            $totals['tax'] += $row['expenses']['contractor_tax'];
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="text-center font-medium">{{ $row['supplier'] }}</td>
                            <td class="px-2">{{ $row['product_name'] }}</td>
                            <td class="text-center">{{ number_format($row['qty'], 0) }}</td>
                            <td class="text-right bg-blue-50/30">${{ number_format($row['usd_price'], 0) }}</td>
                            <td class="text-center">{{ round($row['discount_rate'], 1) }}%</td>
                            <td class="text-center">{{ round($row['import_cost_rate'], 1) }}%</td>
                            <td class="text-right bg-blue-50/50 font-bold">${{ number_format($row['estimated_cost_usd'], 0) }}</td>
                            <td class="text-right">{{ number_format($row['exchange_rate'], 0, ',', '.') }}</td>
                            
                            <td class="text-right bg-yellow-50/50">{{ number_format($row['cost'] / ($row['qty'] ?: 1), 0, ',', '.') }}</td>
                            <td class="text-right bg-yellow-50 font-bold">{{ number_format($row['cost'], 0, ',', '.') }}</td>
                            
                            <td class="text-right bg-blue-50/50">{{ number_format($row['unit_price'], 0, ',', '.') }}</td>
                            <td class="text-right bg-blue-50 font-bold">{{ number_format($row['revenue'], 0, ',', '.') }}</td>
                            
                            <td class="text-right bg-green-50 font-bold text-green-700">{{ number_format($row['gross_profit'], 0, ',', '.') }}</td>
                            <td class="text-center bg-green-50 text-green-700">{{ $row['revenue'] > 0 ? round(($row['gross_profit'] / $row['revenue']) * 100, 1) : 0 }}%</td>
                            
                            <!-- Expenses mapped to form columns -->
                            <td class="text-right bg-orange-50/30">{{ number_format($row['expenses']['finance'], 0, ',', '.') }}</td>
                            <td class="text-center bg-orange-50/30 text-gray-400 italic text-[10px]">n/a</td>
                            <td class="text-right bg-orange-50/30">{{ number_format($row['expenses']['management'], 0, ',', '.') }}</td>
                            <td class="text-right bg-orange-50/30 text-blue-600">{{ number_format($row['expenses']['support_247'], 0, ',', '.') }}</td>
                            <td class="text-right bg-orange-50/30">{{ number_format($row['expenses']['other_support'], 0, ',', '.') }}</td>
                            <td class="text-right bg-orange-50/30">{{ number_format($row['expenses']['technical_poc'], 0, ',', '.') }}</td>
                            <td class="text-right bg-orange-50/30">{{ number_format($row['expenses']['implementation'], 0, ',', '.') }}</td>
                            <td class="text-right bg-orange-50/30">{{ number_format($row['expenses']['contractor_tax'], 0, ',', '.') }}</td>
                            
                            <td class="text-right bg-gray-100 font-bold">{{ number_format($rowExpTotal, 0, ',', '.') }}</td>
                            
                            <td class="text-right bg-indigo-100 font-bold text-indigo-800 border-l border-gray-300">{{ number_format($row['net_profit'], 0, ',', '.') }}</td>
                            <td class="text-center bg-indigo-100 font-bold {{ $row['net_profit'] < 0 ? 'text-red-500' : 'text-indigo-800' }} border-l border-gray-300">
                                {{ round($row['margin_percent'], 1) }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="25" class="px-4 py-16 text-center text-gray-400 italic">Không có dữ liệu kinh doanh trong khoảng thời gian này.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-200 font-bold text-gray-900 border-t-2 border-gray-400">
                    <tr>
                        <td colspan="3" class="px-4 py-2 text-right uppercase">Tổng cộng hệ thống</td>
                        <td colspan="5"></td>
                        <td class="text-right"></td>
                        <td class="text-right">{{ number_format($totals['cost'], 0, ',', '.') }}</td>
                        <td class="text-right"></td>
                        <td class="text-right text-blue-800">{{ number_format($totals['revenue'], 0, ',', '.') }}</td>
                        <td class="text-right text-green-800">{{ number_format($totals['gross_profit'], 0, ',', '.') }}</td>
                        <td class="text-center">{{ $totals['revenue'] > 0 ? round(($totals['gross_profit'] / $totals['revenue']) * 100, 1) : 0 }}%</td>
                        
                        <td class="text-right">{{ number_format($totals['finance'], 0, ',', '.') }}</td>
                        <td class="text-center italic text-xs">n/a</td>
                        <td class="text-right">{{ number_format($totals['mgmt'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totals['support_247'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totals['other_support'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totals['poc'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totals['imp'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($totals['tax'], 0, ',', '.') }}</td>
                        
                        <td class="text-right bg-gray-300">{{ number_format($totals['total_expenses'], 0, ',', '.') }}</td>
                        <td class="text-right bg-indigo-200 text-lg border-l border-gray-400 font-bold text-indigo-900 px-2">{{ number_format($totals['net_profit'], 0, ',', '.') }}</td>
                        <td class="text-center bg-indigo-200 text-lg border-l border-gray-400 font-bold text-indigo-900 px-2">{{ $totals['revenue'] > 0 ? round(($totals['net_profit'] / $totals['revenue']) * 100, 1) : 0 }}%</td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="mt-4 flex justify-between items-center text-xs text-gray-400 italic">
                <p>Ghi chú: Các chi phí (%) được tính dựa trên Giá VND đầu vào.</p>
                <p>Ngày xuất: {{ now()->format('d/m/Y H:i') }} | Người lập: {{ Auth::user()->name }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
